<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=4">
</head>
<body>
    <div class="glass-container dashboard-container guest-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title" style="margin:0; background: linear-gradient(135deg, #f472b6, #db2777); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Member Dashboard</h1>
            <a href="/logout.php" class="btn btn-inline btn-danger">Logout</a>
        </div>
        
        <div class="panel">
            <h2 class="panel-heading" style="margin-bottom: 0.5rem;">Welcome, <?= htmlspecialchars($guestDisplayName) ?>!</h2>
            <p class="panel-subtext">
                Browse our catalog, securely borrow books, and verify your loan deadlines.
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
            <h3 class="section-title">Your Member Status</h3>
            <?php if (!$libraryCard): ?>
                <div class="alert-error left-align">You do not have a library card associated with this account. Please see a librarian.</div>
            <?php else: ?>
                <?php
                    $isExpired = strtotime($libraryCard['expiry_date']) < time();
                    $borrowsCount = count($activeBorrows);
                ?>
                <p><strong>Card Number:</strong> <?= htmlspecialchars($libraryCard['card_number']) ?></p>
                <p>
                    <strong>Status:</strong> 
                    <span class="status-pill <?= $libraryCard['is_active'] && !$isExpired ? 'status-active' : 'status-inactive' ?>">
                        <?= $libraryCard['is_active'] && !$isExpired ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                    </span>
                </p>
                <p><strong>Expiry Date:</strong> <?= htmlspecialchars($libraryCard['expiry_date']) ?></p>
                <div style="margin-top: 1rem;">
                    <strong>Active Borrowed Books limit:</strong> <span style="font-size: 1.25rem; font-weight: bold; color: <?= $borrowsCount >= 3 ? 'var(--danger)' : 'var(--accent)' ?>;"><?= $borrowsCount ?> / 3</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($libraryCard && count($pendingPayments) > 0): ?>
            <div class="panel" style="border: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);">
                <h3 class="section-title" style="color: var(--danger);">Pending Payments</h3>
                <p class="panel-subtext" style="color: rgba(255,255,255,0.7);">Your account carries outstanding balances. Please pay to restore full functionality.</p>
                <div class="table-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Fee Description</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['description']) ?></td>
                                    <td style="color: var(--danger); font-weight: bold;">$<?= htmlspecialchars(number_format($payment['amount'], 2)) ?></td>
                                    <td>
                                        <form method="POST" action="dashboard.php" class="inline-form" onsubmit="return confirm('Process this payment securely using your saved card?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="pay_fee">
                                            <input type="hidden" name="fee_type" value="<?= htmlspecialchars($payment['fee_type']) ?>">
                                            <input type="hidden" name="fee_id" value="<?= (int) $payment['id'] ?>">
                                            <button type="submit" class="btn btn-inline" style="background: #10b981; min-width: 100px;">Pay Now</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($libraryCard && count($activeBorrows) > 0): ?>
            <div class="panel">
                <h3 class="section-title">Your Active Loans</h3>
                <div class="table-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Borrowed On</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeBorrows as $loan): ?>
                                <?php 
                                    $isOverdue = strtotime($loan['expected_return_date']) < time();
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['title']) ?></td>
                                    <td><?= htmlspecialchars($loan['author']) ?></td>
                                    <td><?= htmlspecialchars($loan['borrow_date']) ?></td>
                                    <td <?= $isOverdue ? 'style="color: var(--danger); font-weight: bold;"' : '' ?>>
                                        <?= htmlspecialchars($loan['expected_return_date']) ?>
                                        <?= $isOverdue ? '(Overdue)' : '' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h3 class="section-title">Book Catalog</h3>
            <div class="table-wrapper">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($availableBooks) === 0): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No books are currently available for borrowing. Check back later!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($availableBooks as $book): ?>
                                <tr>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['category_name']) ?></td>
                                    <td><?= htmlspecialchars((string) ($book['publish_year'] ?? '-')) ?></td>
                                    <td>
                                        <span class="status-pill status-active">Available (<?= $book['available_copies'] ?>)</span>
                                    </td>
                                    <td>
                                        <form method="POST" action="dashboard.php" class="inline-form" onsubmit="return confirm('Borrow this book?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="borrow_book">
                                            <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                            <button type="submit" class="btn btn-inline" style="background: #3b82f6;" <?= (!$libraryCard || !$libraryCard['is_active'] || strtotime($libraryCard['expiry_date']) < time() || count($activeBorrows) >= 3) ? 'disabled' : '' ?>>Borrow</button>
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
</body>
</html>
