<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Member Loans</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=6">
</head>
<body>
    <div class="glass-container dashboard-container librarian-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title librarian-title">Manage Member Loans</h1>
            <div>
                <a href="manageBorrows.php" class="btn btn-inline">Back to Borrowers</a>
                <a href="/logout.php" class="btn btn-inline btn-danger" style="margin-left: 0.5rem;">Logout</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel-heading"><?= htmlspecialchars($cardInfo['first_name'] . ' ' . $cardInfo['last_name']) ?></h2>
            <p class="panel-subtext">Card Number: <?= htmlspecialchars($cardInfo['card_number']) ?></p>
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
            <h3 class="section-title">Loan History</h3>
            <div class="table-wrapper">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrowed On</th>
                            <th>Status/Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($loans) === 0): ?>
                            <tr>
                                <td colspan="5" class="empty-state">This member holds no borrowing history.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($loans as $loan): ?>
                                <?php 
                                    $isOverdue = !$loan['is_returned'] && strtotime($loan['expected_return_date']) < time();
                                ?>
                                <tr style="<?= $loan['is_returned'] ? 'opacity: 0.7; background: rgba(255,255,255,0.02);' : '' ?>">
                                    <td><?= htmlspecialchars($loan['title']) ?></td>
                                    <td><?= htmlspecialchars($loan['author']) ?></td>
                                    <td><?= htmlspecialchars($loan['borrow_date']) ?></td>
                                    <td>
                                        <?php if ($loan['is_returned']): ?>
                                            <span class="status-pill status-active" style="background: rgba(16, 185, 129, 0.1); color: #34d399;">
                                                Returned: <?= htmlspecialchars($loan['actual_return_date']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="<?= $isOverdue ? 'color: var(--danger); font-weight: bold;' : '' ?>">
                                                Due: <?= htmlspecialchars($loan['expected_return_date']) ?>
                                                <?= $isOverdue ? '(Overdue)' : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$loan['is_returned']): ?>
                                            <form method="POST" action="manageBorrowerLoans.php?card_id=<?= (int)$cardId ?>" class="inline-form" style="display: flex; gap: 0.5rem; align-items: center;" onsubmit="return confirm('Confirm this book copy has been physically returned?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="action" value="return_book">
                                                <input type="hidden" name="borrow_id" value="<?= (int) $loan['borrow_id'] ?>">
                                                <input type="number" name="simulate_late_days" class="form-input" placeholder="+ days" min="0" style="width: 80px; padding: 0.25rem 0.5rem;" title="Simulate late days">
                                                <button type="submit" class="btn btn-inline" style="background: #10b981;">Mark Returned</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Cleared</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
