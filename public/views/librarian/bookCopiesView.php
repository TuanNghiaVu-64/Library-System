<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Copies</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title">Book Copies</h1>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <form method="POST" action="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>" onsubmit="return confirm('Are you sure you want to delete this book? This will fail if there are past or active borrow records.');" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_book">
                    <button type="submit" class="btn btn-inline btn-danger">Delete Book</button>
                </form>
                <a href="dashboard.php" class="btn btn-inline">Back to Books</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading"><?= htmlspecialchars($book['title']) ?></h2>
            <p class="panel-subtext">
                <?= htmlspecialchars($book['author']) ?> | <?= htmlspecialchars($book['category_name']) ?>
                <?php if (!empty($book['publisher'])): ?>
                    | <?= htmlspecialchars($book['publisher']) ?>
                <?php endif; ?>
                <?php if (!empty($book['publish_year'])): ?>
                    | <?= htmlspecialchars((string) $book['publish_year']) ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="panel">
            <h3 class="section-title">Add New Copy</h3>
            <form method="POST" action="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_copy">
                <div class="form-group" style="display:inline-block; margin-right: 0.5rem; margin-bottom: 0;">
                    <select name="condition" class="form-input">
                        <option value="new">New</option>
                        <option value="good" selected>Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-inline">Add Copy</button>
            </form>
        </div>

        <div class="panel">
            <h3 class="section-title">Copy Filters</h3>
            <form method="GET" class="filter-grid librarian-copy-filter-grid" id="copyFilterForm">
                <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                <div class="form-group">
                    <label class="form-label">Condition</label>
                    <select name="condition" class="form-input">
                        <option value="">All conditions</option>
                        <option value="new" <?= $filters['condition'] === 'new' ? 'selected' : '' ?>>New</option>
                        <option value="good" <?= $filters['condition'] === 'good' ? 'selected' : '' ?>>Good</option>
                        <option value="fair" <?= $filters['condition'] === 'fair' ? 'selected' : '' ?>>Fair</option>
                        <option value="poor" <?= $filters['condition'] === 'poor' ? 'selected' : '' ?>>Poor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Availability</label>
                    <select name="is_available" class="form-input">
                        <option value="">All</option>
                        <option value="1" <?= $filters['is_available'] === '1' ? 'selected' : '' ?>>Available</option>
                        <option value="0" <?= $filters['is_available'] === '0' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-inline">Apply Filters</button>
                </div>
            </form>

            <div class="table-wrapper" id="bookCopiesRegion">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Copy ID</th>
                            <th>Condition</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($copies) === 0): ?>
                            <tr>
                                <td colspan="3" class="empty-state">No copies found for the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($copies as $copy): ?>
                                <tr>
                                    <td>#<?= (int) $copy['copy_id'] ?></td>
                                    <td>
                                        <form method="POST" action="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>" class="inline-form" style="display:flex; gap:0.5rem; align-items:center;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="update_copy_condition">
                                            <input type="hidden" name="copy_id" value="<?= (int) $copy['copy_id'] ?>">
                                            <select name="condition" class="form-input" style="padding: 0.3rem; width: auto;" onchange="this.form.submit()">
                                                <option value="new" <?= $copy['condition'] === 'new' ? 'selected' : '' ?>>New</option>
                                                <option value="good" <?= $copy['condition'] === 'good' ? 'selected' : '' ?>>Good</option>
                                                <option value="fair" <?= $copy['condition'] === 'fair' ? 'selected' : '' ?>>Fair</option>
                                                <option value="poor" <?= $copy['condition'] === 'poor' ? 'selected' : '' ?>>Poor</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>" class="status-toggle-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="update_copy_status">
                                            <input type="hidden" name="copy_id" value="<?= (int) $copy['copy_id'] ?>">
                                            <label class="status-toggle">
                                                <input type="checkbox" name="is_available" value="1" class="status-toggle-input" onchange="event.preventDefault(); handleToggleCopyStatus(this.form)" <?= $copy['is_available'] ? 'checked' : '' ?>>
                                                <span class="status-toggle-indicator"></span>
                                            </label>
                                            <span style="margin-left: 0.5rem;" class="status-pill <?= $copy['is_available'] ? 'status-active' : 'status-inactive' ?>">
                                                <?= $copy['is_available'] ? 'Available' : 'Unavailable' ?>
                                            </span>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" action="bookCopies.php?book_id=<?= (int) $book['book_id'] ?>" onsubmit="return confirm('Are you sure you want to delete this copy?');" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_copy">
                                            <input type="hidden" name="copy_id" value="<?= (int) $copy['copy_id'] ?>">
                                            <button type="submit" class="btn btn-inline btn-danger" style="font-size: 0.8rem; padding: 0.25rem 0.5rem; line-height: 1;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="/assets/js/librarianBookCopies.js"></script>
</body>
</html>
