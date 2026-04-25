<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/librarianDashboardService.php';

if (!isset($_SESSION['account_id']) || $_SESSION['role_name'] !== 'librarian') {
    header('Location: ../index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];
$errors = [];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'CSRF token validation failed.';
    } else {
        $result = handleLibrarianBorrowersPost($pdo, $_POST);
        $messages = array_merge($messages, $result['messages']);
        $errors = array_merge($errors, $result['errors']);
    }
}

$filters = normalizeBorrowerFilters($_GET);
$borrowers = fetchBorrowersForLibrarian($pdo, $filters);

$csrfToken = $_SESSION['csrf_token'];
$librarianDisplayName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($isAjax) {
    // Return only the table region for AJAX requests
    ?>
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
    <?php
} else {
    // Return full page for regular requests
    require __DIR__ . '/../views/librarian/manageBorrowsView.php';
}
