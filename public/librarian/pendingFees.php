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

$filters = normalizePendingFeesFilters($_GET);
$pendingFees = fetchPendingFeesForLibrarian($pdo, $filters);

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
    <?php
} else {
    // Return full page for regular requests
    require __DIR__ . '/../views/librarian/pendingFeesView.php';
}
