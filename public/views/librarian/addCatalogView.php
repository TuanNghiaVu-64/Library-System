<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Catalog</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title">Add Catalog</h1>
            <div>
                <a href="dashboard.php" class="btn btn-inline" style="margin-right: 0.5rem;">Back to Dashboard</a>
                <a href="/logout.php" class="btn btn-inline btn-danger">Logout</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading">Expand the Catalog, <?= htmlspecialchars($librarianDisplayName) ?>!</h2>
            <p class="panel-subtext">Add new book categories and register new books.</p>
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
            <h3 class="section-title">Add New Category</h3>
            <form method="POST" action="addCatalog.php" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_category">
                <div class="form-group" style="display:inline-block; margin-right: 0.5rem; margin-bottom: 0;">
                    <input type="text" name="category_name" class="form-input" placeholder="Category Name" required>
                </div>
                <button type="submit" class="btn btn-inline">Add Category</button>
            </form>
        </div>

        <div class="panel">
            <h3 class="section-title">Add New Book</h3>
            <form method="POST" action="addCatalog.php" class="admin-form-grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_book">
                
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-input" required>
                        <option value="">Select category</option>
                        <?php foreach ($filterOptions['categories'] as $category): ?>
                            <option value="<?= (int) $category['category_id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Publisher</label>
                    <input type="text" name="publisher" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Publish Year</label>
                    <input type="number" name="publish_year" class="form-input" min="1000" max="<?= date('Y') + 1 ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" class="form-input">
                </div>
                <div class="form-group" style="grid-column: span 3;">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" style="resize: vertical;"></textarea>
                </div>
                <div class="form-group form-actions" style="grid-column: span 3; justify-content: flex-end;">
                    <button type="submit" class="btn btn-inline" style="width: auto; padding-left: 2rem; padding-right: 2rem;">Add Book</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
