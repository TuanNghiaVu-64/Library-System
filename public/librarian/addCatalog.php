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

$currentLibrarianId = (int) $_SESSION['account_id'];
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $actionResult = handleLibrarianDashboardPost($pdo, $_POST, $currentLibrarianId);
        if (isset($actionResult['messages'])) {
            $messages = array_merge($messages, $actionResult['messages']);
        }
        if (isset($actionResult['errors'])) {
            $errors = array_merge($errors, $actionResult['errors']);
        }
    }
}

$filterOptions = fetchBookFilterOptions($pdo);
$csrfToken = $_SESSION['csrf_token'];
$librarianDisplayName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

require __DIR__ . '/../views/librarian/addCatalogView.php';
