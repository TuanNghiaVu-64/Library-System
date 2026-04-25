<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title">Librarian Dashboard</h1>
            <div>
                <a href="manageBorrows.php" class="btn btn-inline" style="margin-right: 0.5rem; background: #6366f1;">Manage Borrows</a>
                <a href="pendingFees.php" class="btn btn-inline" style="margin-right: 0.5rem; background: #a855f7;">Pending Fees</a>
                <a href="addCatalog.php" class="btn btn-inline" style="margin-right: 0.5rem; background: #10b981;">Add Catalog</a>
                <a href="/logout.php" class="btn btn-inline btn-danger">Logout</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading">Welcome, <?= htmlspecialchars($librarianDisplayName) ?>!</h2>
            <p class="panel-subtext">Manage the library catalog and drill into book copies.</p>
        </div>

        <div class="panel">
            <h3 class="section-title">Book Management</h3>
            <?php
                $publishYearMinBound = $filterOptions['publishYearRange']['min'];
                $publishYearMaxBound = $filterOptions['publishYearRange']['max'];
                $selectedYearMin = $filters['publish_year_min'] !== '' ? (int) $filters['publish_year_min'] : $publishYearMinBound;
                $selectedYearMax = $filters['publish_year_max'] !== '' ? (int) $filters['publish_year_max'] : $publishYearMaxBound;
            ?>
            <form method="GET" class="filter-grid librarian-filter-grid" id="librarianFilterForm">
                <div class="form-group">
                    <label class="form-label">Search by title</label>
                    <input type="text" name="search" class="form-input" placeholder="Book name" value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">All categories</option>
                        <?php foreach ($filterOptions['categories'] as $category): ?>
                            <option value="<?= (int) $category['category_id'] ?>" <?= $filters['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Author</label>
                    <select name="author" class="form-input">
                        <option value="">All authors</option>
                        <?php foreach ($filterOptions['authors'] as $author): ?>
                            <option value="<?= htmlspecialchars($author) ?>" <?= $filters['author'] === $author ? 'selected' : '' ?>>
                                <?= htmlspecialchars($author) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Publisher</label>
                    <select name="publisher" class="form-input">
                        <option value="">All publishers</option>
                        <?php foreach ($filterOptions['publishers'] as $publisher): ?>
                            <option value="<?= htmlspecialchars($publisher) ?>" <?= $filters['publisher'] === $publisher ? 'selected' : '' ?>>
                                <?= htmlspecialchars($publisher) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Publish year</label>
                    <div class="year-range-group">
                        <div class="year-range-values">
                            <span id="publishYearMinValue"><?= $selectedYearMin ?></span>
                            <span id="publishYearMaxValue"><?= $selectedYearMax ?></span>
                        </div>
                        <input
                            type="range"
                            name="publish_year_min"
                            id="publishYearMin"
                            min="<?= $publishYearMinBound ?>"
                            max="<?= $publishYearMaxBound ?>"
                            value="<?= $selectedYearMin ?>"
                            class="year-range-input"
                        >
                        <input
                            type="range"
                            name="publish_year_max"
                            id="publishYearMax"
                            min="<?= $publishYearMinBound ?>"
                            max="<?= $publishYearMaxBound ?>"
                            value="<?= $selectedYearMax ?>"
                            class="year-range-input"
                        >
                    </div>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-inline">Apply Filters</button>
                </div>
            </form>

            <div class="table-wrapper" id="librarianBooksRegion">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>Copies</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($books) === 0): ?>
                            <tr>
                                <td colspan="7" class="empty-state">No books found for the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['category_name']) ?></td>
                                    <td><?= htmlspecialchars($book['publisher'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars((string) ($book['publish_year'] ?? '-')) ?></td>
                                    <td>
                                        <span class="text-muted">
                                            <?= (int) $book['available_copies'] ?>/<?= (int) $book['total_copies'] ?> available
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-inline" href="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>">View Copies</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="/assets/js/librarianDashboard.js"></script>
</body>
</html>
