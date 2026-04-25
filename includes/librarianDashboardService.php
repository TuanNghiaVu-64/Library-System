<?php

function handleLibrarianBookCopiesPost(PDO $pdo, array $postData, int $bookId): array
{
    $action = $postData['action'] ?? '';
    $messages = [];
    $errors = [];

    if ($action === 'add_copy') {
        $condition = $postData['condition'] ?? 'good';
        $allowedConditions = ['new', 'good', 'fair', 'poor'];

        if (!in_array($condition, $allowedConditions, true)) {
            $errors[] = 'Invalid copy condition specified.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO book_copies (book_id, condition, is_available) VALUES (:book_id, :condition, TRUE)");
                $stmt->execute([
                    'book_id' => $bookId,
                    'condition' => $condition,
                ]);
                $messages[] = 'Book copy added successfully.';
            } catch (PDOException $e) {
                $errors[] = 'An error occurred while adding the book copy.';
            }
        }
    } elseif ($action === 'update_copy_status') {
        $copyId = (int) ($postData['copy_id'] ?? 0);
        $isAvailable = isset($postData['is_available']) ? 1 : 0;
        
        if ($copyId > 0) {
            try {
                if ($isAvailable === 1) {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM borrow_records WHERE copy_id = :copy_id AND is_returned = FALSE");
                    $stmtCheck->execute(['copy_id' => $copyId]);
                    if ((int)$stmtCheck->fetchColumn() > 0) {
                        $errors[] = 'Cannot mark this copy as available because it is currently borrowed.';
                        return ['messages' => $messages, 'errors' => $errors];
                    }
                }

                $stmt = $pdo->prepare("UPDATE book_copies SET is_available = :is_available WHERE copy_id = :copy_id AND book_id = :book_id");
                $stmt->execute([
                    'is_available' => $isAvailable,
                    'copy_id' => $copyId,
                    'book_id' => $bookId,
                ]);
                $messages[] = 'Copy status updated successfully.';
            } catch (PDOException $e) {
                $errors[] = 'Failed to update copy status.';
            }
        }
    } elseif ($action === 'update_copy_condition') {
        $copyId = (int) ($postData['copy_id'] ?? 0);
        $condition = $postData['condition'] ?? '';
        $allowedConditions = ['new', 'good', 'fair', 'poor'];

        if ($copyId > 0 && in_array($condition, $allowedConditions, true)) {
            try {
                $stmt = $pdo->prepare("UPDATE book_copies SET condition = :condition WHERE copy_id = :copy_id AND book_id = :book_id");
                $stmt->execute([
                    'condition' => $condition,
                    'copy_id' => $copyId,
                    'book_id' => $bookId,
                ]);
                $messages[] = 'Copy condition updated successfully.';
            } catch (PDOException $e) {
                $errors[] = 'Failed to update copy condition.';
            }
        }
    } elseif ($action === 'delete_copy') {
        $copyId = (int) ($postData['copy_id'] ?? 0);
        if ($copyId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM book_copies WHERE copy_id = :copy_id AND book_id = :book_id");
                $stmt->execute(['copy_id' => $copyId, 'book_id' => $bookId]);
                if ($stmt->rowCount() > 0) {
                    $messages[] = 'Copy deleted successfully.';
                } else {
                    $errors[] = 'Copy not found.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23503') { 
                    $errors[] = 'Cannot delete this copy because it has borrowing history.';
                } else {
                    $errors[] = 'Failed to delete copy due to a database error.';
                }
            }
        }
    } elseif ($action === 'delete_book') {
        try {
            $stmt = $pdo->prepare("DELETE FROM books WHERE book_id = :book_id");
            $stmt->execute(['book_id' => $bookId]);
            if ($stmt->rowCount() > 0) {
                return ['messages' => ['Book deleted successfully.'], 'errors' => [], 'book_deleted' => true];
            } else {
                $errors[] = 'Book not found.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23503') { 
                $errors[] = 'Cannot delete this book because its copies have borrowing history.';
            } else {
                $errors[] = 'Failed to delete book due to a database error.';
            }
        }
    }

    return [
        'messages' => $messages,
        'errors' => $errors,
    ];
}

function handleLibrarianDashboardPost(PDO $pdo, array $postData, int $librarianId): array
{
    $action = $postData['action'] ?? '';
    $messages = [];
    $errors = [];

    if ($action === 'add_category') {
        $categoryName = trim($postData['category_name'] ?? '');
        if ($categoryName === '') {
            $errors[] = 'Category name cannot be empty.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO book_categories (name) VALUES (:name)");
                $stmt->execute(['name' => $categoryName]);
                $messages[] = 'Category added successfully.';
            } catch (PDOException $e) {
                // Check if it's a unique constraint violation (SQLSTATE 23505)
                if ($e->getCode() == '23505') {
                    $errors[] = 'A category with this name already exists.';
                } else {
                    $errors[] = 'An error occurred while adding the category.';
                }
            }
        }
    } elseif ($action === 'add_book') {
        $title = trim($postData['title'] ?? '');
        $author = trim($postData['author'] ?? '');
        $categoryId = trim($postData['category_id'] ?? '');
        $publisher = trim($postData['publisher'] ?? '');
        $publishYear = trim($postData['publish_year'] ?? '');
        $isbn = trim($postData['isbn'] ?? '');
        $description = trim($postData['description'] ?? '');

        if ($title === '' || $author === '' || $categoryId === '') {
            $errors[] = 'Title, Author, and Category are required fields.';
        } else {
            $categoryId = (int) $categoryId;
            $publishYearVal = $publishYear !== '' ? (int) $publishYear : null;
            $isbnVal = $isbn !== '' ? $isbn : null;

            if ($publishYearVal !== null && ($publishYearVal < 1000 || $publishYearVal > (int) date('Y') + 1)) {
                $errors[] = 'Invalid publish year.';
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO books (category_id, added_by, title, author, isbn, publisher, publish_year, description)
                        VALUES (:category_id, :added_by, :title, :author, :isbn, :publisher, :publish_year, :description)
                    ");
                    $stmt->execute([
                        'category_id' => $categoryId,
                        'added_by' => $librarianId,
                        'title' => $title,
                        'author' => $author,
                        'isbn' => $isbnVal,
                        'publisher' => $publisher ?: null,
                        'publish_year' => $publishYearVal,
                        'description' => $description ?: null,
                    ]);
                    $messages[] = 'Book added successfully.';
                } catch (PDOException $e) {
                    if ($e->getCode() == '23505') { // UNIQUE constraint on ISBN
                        $errors[] = 'A book with this ISBN already exists.';
                    } else {
                        $errors[] = 'An error occurred while adding the book.';
                    }
                }
            }
        }
    }

    return [
        'messages' => $messages,
        'errors' => $errors,
    ];
}

function normalizeBookFilters(array $queryParams): array
{
    $filters = [
        'search' => trim($queryParams['search'] ?? ''),
        'category_id' => trim($queryParams['category_id'] ?? ''),
        'author' => trim($queryParams['author'] ?? ''),
        'publisher' => trim($queryParams['publisher'] ?? ''),
        'publish_year_min' => trim($queryParams['publish_year_min'] ?? ''),
        'publish_year_max' => trim($queryParams['publish_year_max'] ?? ''),
    ];

    if ($filters['category_id'] !== '' && !ctype_digit($filters['category_id'])) {
        $filters['category_id'] = '';
    }
    if ($filters['publish_year_min'] !== '' && !ctype_digit($filters['publish_year_min'])) {
        $filters['publish_year_min'] = '';
    }
    if ($filters['publish_year_max'] !== '' && !ctype_digit($filters['publish_year_max'])) {
        $filters['publish_year_max'] = '';
    }

    if ($filters['publish_year_min'] !== '' && $filters['publish_year_max'] !== '') {
        $yearMin = (int) $filters['publish_year_min'];
        $yearMax = (int) $filters['publish_year_max'];

        if ($yearMin > $yearMax) {
            $filters['publish_year_min'] = (string) $yearMax;
            $filters['publish_year_max'] = (string) $yearMin;
        }
    }

    return $filters;
}

function fetchBookFilterOptions(PDO $pdo): array
{
    $categories = $pdo->query("
        SELECT category_id, name
        FROM book_categories
        ORDER BY name
    ")->fetchAll();

    $authors = $pdo->query("
        SELECT DISTINCT author
        FROM books
        WHERE author IS NOT NULL AND TRIM(author) <> ''
        ORDER BY author
    ")->fetchAll();

    $publishers = $pdo->query("
        SELECT DISTINCT publisher
        FROM books
        WHERE publisher IS NOT NULL AND TRIM(publisher) <> ''
        ORDER BY publisher
    ")->fetchAll();

    $publishYearRange = $pdo->query("
        SELECT MIN(publish_year) AS min_year, MAX(publish_year) AS max_year
        FROM books
        WHERE publish_year IS NOT NULL
    ")->fetch();

    $yearRange = [
        'min' => $publishYearRange && $publishYearRange['min_year'] !== null ? (int) $publishYearRange['min_year'] : (int) date('Y'),
        'max' => $publishYearRange && $publishYearRange['max_year'] !== null ? (int) $publishYearRange['max_year'] : (int) date('Y'),
    ];

    return [
        'categories' => $categories,
        'authors' => array_map(static fn(array $row) => $row['author'], $authors),
        'publishers' => array_map(static fn(array $row) => $row['publisher'], $publishers),
        'publishYearRange' => $yearRange,
    ];
}

function fetchBooksForLibrarian(PDO $pdo, array $filters): array
{
    $sql = "
        SELECT
            b.book_id,
            b.title,
            b.author,
            b.publisher,
            b.publish_year,
            c.name AS category_name,
            COUNT(bc.copy_id) AS total_copies,
            SUM(CASE WHEN bc.is_available THEN 1 ELSE 0 END) AS available_copies
        FROM books b
        JOIN book_categories c ON b.category_id = c.category_id
        LEFT JOIN book_copies bc ON b.book_id = bc.book_id
        WHERE 1=1
    ";
    $params = [];

    if ($filters['search'] !== '') {
        $sql .= " AND LOWER(b.title) LIKE :search";
        $params['search'] = '%' . mb_strtolower($filters['search']) . '%';
    }
    if ($filters['category_id'] !== '') {
        $sql .= " AND b.category_id = :category_id";
        $params['category_id'] = (int) $filters['category_id'];
    }
    if ($filters['author'] !== '') {
        $sql .= " AND b.author = :author";
        $params['author'] = $filters['author'];
    }
    if ($filters['publisher'] !== '') {
        $sql .= " AND b.publisher = :publisher";
        $params['publisher'] = $filters['publisher'];
    }
    if ($filters['publish_year_min'] !== '') {
        $sql .= " AND b.publish_year >= :publish_year_min";
        $params['publish_year_min'] = (int) $filters['publish_year_min'];
    }
    if ($filters['publish_year_max'] !== '') {
        $sql .= " AND b.publish_year <= :publish_year_max";
        $params['publish_year_max'] = (int) $filters['publish_year_max'];
    }

    $sql .= "
        GROUP BY b.book_id, b.title, b.author, b.publisher, b.publish_year, c.name
        ORDER BY b.added_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function normalizeCopyFilters(array $queryParams): array
{
    $filters = [
        'condition' => trim($queryParams['condition'] ?? ''),
        'is_available' => trim($queryParams['is_available'] ?? ''),
    ];

    $allowedConditions = ['new', 'good', 'fair', 'poor'];
    if ($filters['condition'] !== '' && !in_array($filters['condition'], $allowedConditions, true)) {
        $filters['condition'] = '';
    }
    if ($filters['is_available'] !== '' && !in_array($filters['is_available'], ['1', '0'], true)) {
        $filters['is_available'] = '';
    }

    return $filters;
}

function fetchBookForCopyPage(PDO $pdo, int $bookId): ?array
{
    $stmt = $pdo->prepare("
        SELECT b.book_id, b.title, b.author, b.publisher, b.publish_year, c.name AS category_name
        FROM books b
        JOIN book_categories c ON b.category_id = c.category_id
        WHERE b.book_id = :book_id
    ");
    $stmt->execute(['book_id' => $bookId]);
    $book = $stmt->fetch();

    return $book ?: null;
}

function fetchBookCopiesForLibrarian(PDO $pdo, int $bookId, array $filters): array
{
    $sql = "
        SELECT copy_id, condition, is_available
        FROM book_copies
        WHERE book_id = :book_id
    ";
    $params = ['book_id' => $bookId];

    if ($filters['condition'] !== '') {
        $sql .= " AND condition = :condition";
        $params['condition'] = $filters['condition'];
    }
    if ($filters['is_available'] !== '') {
        $sql .= " AND is_available = :is_available";
        $params['is_available'] = ($filters['is_available'] === '1') ? 'TRUE' : 'FALSE';
    }

    $sql .= " ORDER BY copy_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function normalizeBorrowerFilters(array $getParams): array
{
    return [
        'search' => trim($getParams['search'] ?? ''),
        'is_active' => $getParams['is_active'] ?? '',
        'is_borrowing' => $getParams['is_borrowing'] ?? '',
    ];
}

function fetchBorrowersForLibrarian(PDO $pdo, array $filters): array
{
    $where = [];
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = "(a.first_name ILIKE :search OR a.last_name ILIKE :search OR lc.card_number ILIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }

    if ($filters['is_active'] !== '') {
        $where[] = "lc.is_active = :is_active";
        $params['is_active'] = $filters['is_active'] === '1' ? 'TRUE' : 'FALSE';
    }

    $having = "";
    if ($filters['is_borrowing'] === '1') {
        $having = "HAVING SUM(CASE WHEN br.is_returned = FALSE THEN 1 ELSE 0 END) > 0";
    } elseif ($filters['is_borrowing'] === '0') {
        $having = "HAVING SUM(CASE WHEN br.is_returned = FALSE THEN 1 ELSE 0 END) = 0";
    }

    $whereSql = '';
    if (count($where) > 0) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "
        SELECT lc.card_id, lc.card_number, lc.is_active, lc.expiry_date,
               a.first_name, a.last_name, a.email,
               SUM(CASE WHEN br.is_returned = FALSE THEN 1 ELSE 0 END) as active_loans_count
        FROM library_cards lc
        JOIN accounts a ON lc.account_id = a.account_id
        LEFT JOIN borrow_records br ON lc.card_id = br.card_id
        $whereSql
        GROUP BY lc.card_id, lc.card_number, lc.is_active, lc.expiry_date, a.first_name, a.last_name, a.email
        $having
        ORDER BY a.last_name ASC, a.first_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchBorrowerLoans(PDO $pdo, int $cardId): array
{
    $stmt = $pdo->prepare("
        SELECT br.borrow_id, br.borrow_date, br.expected_return_date, br.actual_return_date, br.is_returned,
               bc.copy_id, b.title, b.author
        FROM borrow_records br
        JOIN book_copies bc ON br.copy_id = bc.copy_id
        JOIN books b ON bc.book_id = b.book_id
        WHERE br.card_id = :card_id
        ORDER BY br.is_returned ASC, br.expected_return_date ASC
    ");
    $stmt->execute(['card_id' => $cardId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function disableLibraryCard(PDO $pdo, int $cardId): array
{
    $messages = [];
    $errors = [];

    if ($cardId <= 0) {
        $errors[] = 'Invalid card ID.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    try {
        $stmt = $pdo->prepare("UPDATE library_cards SET is_active = FALSE WHERE card_id = :card_id");
        $stmt->execute(['card_id' => $cardId]);
        if ($stmt->rowCount() > 0) {
            $messages[] = 'Library card has been disabled successfully.';
        } else {
            $errors[] = 'Card not found.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Failed to disable library card.';
    }

    return ['messages' => $messages, 'errors' => $errors];
}

function enableLibraryCard(PDO $pdo, int $cardId): array
{
    $messages = [];
    $errors = [];

    if ($cardId <= 0) {
        $errors[] = 'Invalid card ID.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    try {
        $stmt = $pdo->prepare("UPDATE library_cards SET is_active = TRUE WHERE card_id = :card_id");
        $stmt->execute(['card_id' => $cardId]);
        if ($stmt->rowCount() > 0) {
            $messages[] = 'Library card has been enabled successfully.';
        } else {
            $errors[] = 'Card not found.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Failed to enable library card.';
    }

    return ['messages' => $messages, 'errors' => $errors];
}

function handleLibrarianBorrowersPost(PDO $pdo, array $postData): array
{
    $action = $postData['action'] ?? '';
    $messages = [];
    $errors = [];

    if ($action === 'return_book') {
        $borrowId = (int) ($postData['borrow_id'] ?? 0);
        $lateDays = (int) ($postData['simulate_late_days'] ?? 0);
        if ($borrowId > 0) {
            try {
                if ($lateDays > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE borrow_records 
                        SET is_returned = TRUE, actual_return_date = GREATEST(CURRENT_DATE, expected_return_date) + (INTERVAL '1 day' * :late_days)
                        WHERE borrow_id = :borrow_id AND is_returned = FALSE
                    ");
                    $stmt->execute(['borrow_id' => $borrowId, 'late_days' => $lateDays]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE borrow_records 
                        SET is_returned = TRUE, actual_return_date = CURRENT_DATE 
                        WHERE borrow_id = :borrow_id AND is_returned = FALSE
                    ");
                    $stmt->execute(['borrow_id' => $borrowId]);
                }
                if ($stmt->rowCount() > 0) {
                    $messages[] = 'Book successfully marked as returned.';
                } else {
                    $errors[] = 'Record not found or already returned.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to mark book as returned.';
            }
        }
    } elseif ($action === 'toggle_card_status') {
        $cardId = (int) ($postData['card_id'] ?? 0);
        $nextIsActive = (int) ($postData['next_is_active'] ?? 0);
        
        $result = $nextIsActive ? enableLibraryCard($pdo, $cardId) : disableLibraryCard($pdo, $cardId);
        $messages = array_merge($messages, $result['messages']);
        $errors = array_merge($errors, $result['errors']);
    }

    return ['messages' => $messages, 'errors' => $errors];
}

function normalizePendingFeesFilters(array $getParams): array
{
    return [
        'search' => trim($getParams['search'] ?? ''),
        'fee_type' => trim($getParams['fee_type'] ?? ''),
        'is_active' => $getParams['is_active'] ?? '',
    ];
}

function fetchPendingFeesForLibrarian(PDO $pdo, array $filters): array
{
    $whereSubs = [];
    $whereLate = [];
    $params = [];

    if ($filters['search'] !== '') {
        $searchSql = "(a.first_name ILIKE :search OR a.last_name ILIKE :search OR lc.card_number ILIKE :search)";
        $whereSubs[] = $searchSql;
        $whereLate[] = $searchSql;
        $params['search'] = '%' . $filters['search'] . '%';
    }

    if ($filters['is_active'] !== '') {
        $activeSql = "lc.is_active = :is_active";
        $whereSubs[] = $activeSql;
        $whereLate[] = $activeSql;
        $params['is_active'] = $filters['is_active'] === '1' ? 'TRUE' : 'FALSE';
    }

    $subsWhereSql = "";
    if (count($whereSubs) > 0) {
        $subsWhereSql = "AND " . implode(' AND ', $whereSubs);
    }
    
    $lateWhereSql = "";
    if (count($whereLate) > 0) {
        $lateWhereSql = "AND " . implode(' AND ', $whereLate);
    }

    $includeSubs = true;
    $includeLate = true;

    if ($filters['fee_type'] === 'subscription') {
        $includeLate = false;
    } elseif ($filters['fee_type'] === 'late_fee') {
        $includeSubs = false;
    }

    $queries = [];
    if ($includeSubs) {
        $queries[] = "
            SELECT a.first_name, a.last_name, a.email, lc.card_id, lc.card_number, lc.is_active, lc.expiry_date,
                   CAST('subscription' AS VARCHAR) as fee_type, s.monthly_fee as amount, CAST('Paused Subscription' AS VARCHAR) as description, s.subscription_id as fee_id
            FROM subscriptions s
            JOIN library_cards lc ON s.card_id = lc.card_id
            JOIN accounts a ON lc.account_id = a.account_id
            WHERE s.status = 'paused' $subsWhereSql
        ";
    }

    if ($includeLate) {
        $queries[] = "
            SELECT a.first_name, a.last_name, a.email, lc.card_id, lc.card_number, lc.is_active, lc.expiry_date,
                   CAST('late_fee' AS VARCHAR) as fee_type, lf.fee_amount as amount, CAST('Late Return (' || lf.days_overdue || ' days)' AS VARCHAR) as description, lf.fee_id as fee_id
            FROM late_fees lf
            JOIN borrow_records br ON lf.borrow_id = br.borrow_id
            JOIN library_cards lc ON br.card_id = lc.card_id
            JOIN accounts a ON lc.account_id = a.account_id
            WHERE lf.payment_status = 'unpaid' $lateWhereSql
        ";
    }

    if (empty($queries)) {
        return [];
    }
    
    $sql = implode(" UNION ALL ", $queries) . " ORDER BY amount DESC, last_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
