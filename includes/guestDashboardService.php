<?php

function fetchLibraryCard(PDO $pdo, int $accountId): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM library_cards
        WHERE account_id = :account_id
        LIMIT 1
    ");
    $stmt->execute(['account_id' => $accountId]);
    $card = $stmt->fetch();
    return $card ?: null;
}

function fetchActiveBorrows(PDO $pdo, int $cardId): array
{
    $stmt = $pdo->prepare("
        SELECT br.borrow_id, br.borrow_date, br.expected_return_date, 
               bc.copy_id, b.title, b.author
        FROM borrow_records br
        JOIN book_copies bc ON br.copy_id = bc.copy_id
        JOIN books b ON bc.book_id = b.book_id
        WHERE br.card_id = :card_id AND br.is_returned = FALSE
        ORDER BY br.expected_return_date ASC
    ");
    $stmt->execute(['card_id' => $cardId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchAvailableBooksForGuest(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT b.book_id, b.title, b.author, c.name AS category_name, b.publish_year,
               COUNT(bc.copy_id) as available_copies
        FROM books b
        JOIN book_categories c ON b.category_id = c.category_id
        JOIN book_copies bc ON b.book_id = bc.book_id
        WHERE bc.is_available = TRUE AND bc.condition != 'poor'
        GROUP BY b.book_id, b.title, b.author, c.name, b.publish_year
        ORDER BY b.title ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchPendingPayments(PDO $pdo, int $cardId): array
{
    $stmt = $pdo->prepare("
        SELECT subscription_id as id, CAST('subscription' AS VARCHAR) as fee_type, monthly_fee as amount, CAST('Monthly Subscription (Paused)' AS VARCHAR) as description
        FROM subscriptions
        WHERE card_id = :card_id AND status = 'paused'
        UNION ALL
        SELECT lf.fee_id as id, CAST('late_fee' AS VARCHAR) as fee_type, lf.fee_amount as amount, CAST('Late Return (' || lf.days_overdue || ' days overdue)' AS VARCHAR) as description
        FROM late_fees lf
        JOIN borrow_records br ON lf.borrow_id = br.borrow_id
        WHERE br.card_id = :card_id AND lf.payment_status = 'unpaid'
        ORDER BY amount DESC
    ");
    $stmt->execute(['card_id' => $cardId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function handleGuestDashboardPost(PDO $pdo, array $postData, int $accountId): array
{
    $action = $postData['action'] ?? '';
    $messages = [];
    $errors = [];

    if ($action === 'borrow_book') {
        $bookId = (int)($postData['book_id'] ?? 0);
        if ($bookId <= 0) {
            $errors[] = "Invalid book selection.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        $card = fetchLibraryCard($pdo, $accountId);
        if (!$card) {
            $errors[] = "You do not have a library card yet.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        if (!$card['is_active']) {
            $errors[] = "Your library card is inactive. Please see a librarian.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        if (strtotime($card['expiry_date']) < time()) {
            $errors[] = "Your library card has expired.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        $activeBorrows = fetchActiveBorrows($pdo, $card['card_id']);
        if (count($activeBorrows) >= 3) {
            $errors[] = "You have already borrowed 3 books. Please return a book before borrowing a new one.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        $stmtSub = $pdo->prepare("SELECT status FROM subscriptions WHERE card_id = :card_id ORDER BY subscription_id DESC LIMIT 1");
        $stmtSub->execute(['card_id' => $card['card_id']]);
        $subStatus = $stmtSub->fetchColumn();
        if ($subStatus === 'paused') {
            $errors[] = "Your subscription is paused. Please pay any pending balances in order to borrow.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT copy_id 
                FROM book_copies 
                WHERE book_id = :book_id AND is_available = TRUE AND condition != 'poor'
                LIMIT 1 FOR UPDATE
            ");
            $stmt->execute(['book_id' => $bookId]);
            $copyId = $stmt->fetchColumn();

            if (!$copyId) {
                $errors[] = "Sorry, no copies of this book are currently available.";
                $pdo->rollBack();
                return ['messages' => $messages, 'errors' => $errors];
            }

            $borrowDate = date('Y-m-d');
            $expectedReturnDate = date('Y-m-d', strtotime('+14 days'));

            $stmtInsert = $pdo->prepare("
                INSERT INTO borrow_records (copy_id, card_id, issued_by, borrow_date, expected_return_date)
                VALUES (:copy_id, :card_id, :issued_by, :borrow_date, :expected_return_date)
            ");
            $stmtInsert->execute([
                'copy_id' => $copyId,
                'card_id' => $card['card_id'],
                'issued_by' => $accountId, // Utilizing guest account id for self-service checkout
                'borrow_date' => $borrowDate,
                'expected_return_date' => $expectedReturnDate
            ]);

            $pdo->commit();
            $messages[] = "Successfully borrowed the book. Please return it by " . $expectedReturnDate . ".";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error while processing your request: " . $e->getMessage();
        }
    } elseif ($action === 'pay_fee') {
        $feeType = $postData['fee_type'] ?? '';
        $feeId = (int) ($postData['fee_id'] ?? 0);
        
        $card = fetchLibraryCard($pdo, $accountId);
        if (!$card) {
            $errors[] = "No library card found.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        if ($feeId <= 0) {
            $errors[] = "Invalid fee selected.";
            return ['messages' => $messages, 'errors' => $errors];
        }

        try {
            $pdo->beginTransaction();

            if ($feeType === 'subscription') {
                $stmt = $pdo->prepare("SELECT monthly_fee FROM subscriptions WHERE subscription_id = :id AND card_id = :card_id AND status = 'paused' FOR UPDATE");
                $stmt->execute(['id' => $feeId, 'card_id' => $card['card_id']]);
                $amount = $stmt->fetchColumn();

                if (!$amount) {
                    throw new Exception("Subscription not found or already active.");
                }

                $pdo->prepare("UPDATE subscriptions SET status = 'active' WHERE subscription_id = :id")->execute(['id' => $feeId]);

                $pdo->prepare("INSERT INTO payments (card_id, subscription_id, amount, payment_type, method, paid_at) VALUES (:card, :id, :amt, 'subscription', 'card', CURRENT_TIMESTAMP)")
                    ->execute(['card' => $card['card_id'], 'id' => $feeId, 'amt' => $amount]);

            } elseif ($feeType === 'late_fee') {
                $stmt = $pdo->prepare("
                    SELECT lf.fee_amount 
                    FROM late_fees lf
                    JOIN borrow_records br ON lf.borrow_id = br.borrow_id
                    WHERE lf.fee_id = :id AND br.card_id = :card_id AND lf.payment_status = 'unpaid'
                    FOR UPDATE
                ");
                $stmt->execute(['id' => $feeId, 'card_id' => $card['card_id']]);
                $amount = $stmt->fetchColumn();

                if (!$amount) {
                    throw new Exception("Late fee not found or already paid.");
                }

                $pdo->prepare("UPDATE late_fees SET payment_status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE fee_id = :id")->execute(['id' => $feeId]);

                $pdo->prepare("INSERT INTO payments (card_id, fee_id, amount, payment_type, method, paid_at) VALUES (:card, :id, :amt, 'late_fee', 'card', CURRENT_TIMESTAMP)")
                    ->execute(['card' => $card['card_id'], 'id' => $feeId, 'amt' => $amount]);
            } else {
                throw new Exception("Unknown fee type.");
            }

            $pdo->commit();
            $messages[] = "Payment successfully processed via card.";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }

    return ['messages' => $messages, 'errors' => $errors];
}
