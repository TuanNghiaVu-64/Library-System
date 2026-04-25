<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Member Borrows</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=5">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title">Manage Member Borrows</h1>
            <div>
                <a href="dashboard.php" class="btn btn-inline">Back to Dashboard</a>
                <a href="/logout.php" class="btn btn-inline btn-danger" style="margin-left: 0.5rem;">Logout</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading">Review Library Cards</h2>
            <p class="panel-subtext">Filter cards by name, number, or currently unreturned loans.</p>
        </div>

        <?php if (!empty($messages)): ?>
            <div class="panel" style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981;">
                <?php foreach ($messages as $message): ?>
                    <p style="color: #10b981; margin: 0.5rem 0;">✓ <?= htmlspecialchars($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="panel" style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;">
                <?php foreach ($errors as $error): ?>
                    <p style="color: #ef4444; margin: 0.5rem 0;">✗ <?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h3 class="section-title">Borrower Filters</h3>
            <form method="GET" class="filter-grid librarian-filter-grid" id="borrowerFilterForm">
                <div class="form-group">
                    <label class="form-label">Search by Name or Card</label>
                    <input type="text" name="search" class="form-input" placeholder="First, Last, or Card No." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Card Status</label>
                    <select name="is_active" class="form-input">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Loan Status</label>
                    <select name="is_borrowing" class="form-input">
                        <option value="">All Loan States</option>
                        <option value="1" <?= $filters['is_borrowing'] === '1' ? 'selected' : '' ?>>Has Active Loans</option>
                        <option value="0" <?= $filters['is_borrowing'] === '0' ? 'selected' : '' ?>>No Active Loans</option>
                    </select>
                </div>
            </form>

            <div class="table-wrapper" id="borrowerResultsRegion">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Card Owner</th>
                            <th>Email</th>
                            <th>Card Number</th>
                            <th>Card Status</th>
                            <th>Set Active</th>
                            <th>Active Loans</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($borrowers) === 0): ?>
                            <tr>
                                <td colspan="7" class="empty-state">No borrowers found matching the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($borrowers as $member): ?>
                                <?php $isExpired = strtotime($member['expiry_date']) < time(); ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                                    <td><?= htmlspecialchars($member['email']) ?></td>
                                    <td><?= htmlspecialchars($member['card_number']) ?></td>
                                    <td>
                                        <span class="status-pill <?= $member['is_active'] && !$isExpired ? 'status-active' : 'status-inactive' ?>">
                                            <?= $member['is_active'] && !$isExpired ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-toggle-form card-toggle-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="toggle_card_status">
                                            <input type="hidden" name="card_id" value="<?= (int)$member['card_id'] ?>">
                                            <input type="hidden" name="next_is_active" value="<?= $member['is_active'] ? '0' : '1' ?>">
                                            <label class="status-toggle" title="<?= $member['is_active'] ? 'Disable card' : 'Enable card' ?>">
                                                <input
                                                    type="checkbox"
                                                    class="status-toggle-input"
                                                    <?= $member['is_active'] ? 'checked' : '' ?>
                                                    onchange="event.preventDefault(); handleCardToggle(this.form)"
                                                >
                                                <span class="status-toggle-indicator"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <span style="font-weight: bold; color: <?= $member['active_loans_count'] > 0 ? 'var(--accent)' : 'inherit' ?>;">
                                            <?= (int)$member['active_loans_count'] ?> borrowed
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <a href="manageBorrowerLoans.php?card_id=<?= (int)$member['card_id'] ?>" class="btn btn-inline" style="background: #3b82f6;">View Loans</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <script src="/assets/js/librarianBorrows.js"></script>
</body>
</html>
