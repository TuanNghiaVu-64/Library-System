<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pending Fees</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=6">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title" style="margin: 0; background: linear-gradient(135deg, #a855f7, #6b21a8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Pending Fees</h1>
            <div>
                <a href="dashboard.php" class="btn btn-inline">Back to Dashboard</a>
                <a href="/logout.php" class="btn btn-inline btn-danger" style="margin-left: 0.5rem;">Logout</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading">Review Outstanding Balances</h2>
            <p class="panel-subtext">Filter records by name, card number, or specific fee types across all guests.</p>
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
            <h3 class="section-title">Fee Filters</h3>
            <form method="GET" class="filter-grid librarian-filter-grid" id="pendingFeesFilterForm">
                <div class="form-group">
                    <label class="form-label">Search by Name or Card</label>
                    <input type="text" name="search" class="form-input" placeholder="First, Last, or Card No." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Fee Type</label>
                    <select name="fee_type" class="form-input">
                        <option value="">All Fees</option>
                        <option value="late_fee" <?= $filters['fee_type'] === 'late_fee' ? 'selected' : '' ?>>Late Fees Only</option>
                        <option value="subscription" <?= $filters['fee_type'] === 'subscription' ? 'selected' : '' ?>>Subscriptions Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Card Status</label>
                    <select name="is_active" class="form-input">
                        <option value="">All Card Statuses</option>
                        <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Active Cards</option>
                        <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactive Cards</option>
                    </select>
                </div>
            </form>

            <div class="table-wrapper" id="pendingFeesResultsRegion">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Card Owner</th>
                            <th>Email</th>
                            <th>Card Number</th>
                            <th>Card Status</th>
                            <th>Set Active</th>
                            <th>Fee Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pendingFees) === 0): ?>
                            <tr>
                                <td colspan="8" class="empty-state">No pending fees found matching the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingFees as $fee): ?>
                                <?php $isExpired = strtotime($fee['expiry_date']) < time(); ?>
                                <tr>
                                    <td><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></td>
                                    <td><?= htmlspecialchars($fee['email']) ?></td>
                                    <td><?= htmlspecialchars($fee['card_number']) ?></td>
                                    <td>
                                        <span class="status-pill <?= $fee['is_active'] && !$isExpired ? 'status-active' : 'status-inactive' ?>">
                                            <?= $fee['is_active'] && !$isExpired ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-toggle-form card-toggle-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="toggle_card_status">
                                            <input type="hidden" name="card_id" value="<?= (int)$fee['card_id'] ?>">
                                            <input type="hidden" name="next_is_active" value="<?= $fee['is_active'] ? '0' : '1' ?>">
                                            <label class="status-toggle" title="<?= $fee['is_active'] ? 'Disable card' : 'Enable card' ?>">
                                                <input
                                                    type="checkbox"
                                                    class="status-toggle-input"
                                                    <?= $fee['is_active'] ? 'checked' : '' ?>
                                                    onchange="event.preventDefault(); handleCardToggle(this.form)"
                                                >
                                                <span class="status-toggle-indicator"></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $fee['fee_type']))) ?></td>
                                    <td><?= htmlspecialchars($fee['description']) ?></td>
                                    <td style="color: var(--danger); font-weight: bold;">$<?= htmlspecialchars(number_format($fee['amount'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <script src="/assets/js/librarianPendingFees.js"></script>
</body>
</html>
