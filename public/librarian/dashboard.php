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

$filters = normalizeBookFilters($_GET);
$filterOptions = fetchBookFilterOptions($pdo);
$books = fetchBooksForLibrarian($pdo, $filters);
$csrfToken = $_SESSION['csrf_token'];
$librarianDisplayName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

require __DIR__ . '/../views/librarian/dashboardView.php';
