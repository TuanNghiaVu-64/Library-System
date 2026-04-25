<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/adminDashboardService.php';

if (!isset($_SESSION['account_id']) || $_SESSION['role_name'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentAdminId = (int) $_SESSION['account_id'];
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $actionResult = handleAdminDashboardPost($pdo, $_POST, $currentAdminId);
        $messages = $actionResult['messages'];
        $errors = $actionResult['errors'];

        if (!empty($actionResult['should_logout'])) {
            session_unset();
            session_destroy();
            header('Location: ../index.php');
            exit;
        }
    }
}

$filters = normalizeUserFilters($_GET);
$users = fetchUsersForAdminDashboard($pdo, $filters);
$csrfToken = $_SESSION['csrf_token'];
$adminDisplayName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

require __DIR__ . '/../views/admin/dashboardView.php';
