<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/librarianDashboardService.php';

if (!isset($_SESSION['account_id']) || $_SESSION['role_name'] !== 'librarian') {
    header('Location: ../index.php');
    exit;
}

$bookId = (int) ($_GET['book_id'] ?? 0);
if ($bookId <= 0) {
    header('Location: dashboard.php');
    exit;
}

$book = fetchBookForCopyPage($pdo, $bookId);
if (!$book) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $actionResult = handleLibrarianBookCopiesPost($pdo, $_POST, $bookId);
        if (!empty($actionResult['book_deleted'])) {
            header('Location: dashboard.php');
            exit;
        }
        if (isset($actionResult['messages'])) {
            $messages = array_merge($messages, $actionResult['messages']);
        }
        if (isset($actionResult['errors'])) {
            $errors = array_merge($errors, $actionResult['errors']);
        }
    }
}

$filters = normalizeCopyFilters($_GET);
$copies = fetchBookCopiesForLibrarian($pdo, $bookId, $filters);
$csrfToken = $_SESSION['csrf_token'];

require __DIR__ . '/../views/librarian/bookCopiesView.php';
