<?php 
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/guestDashboardService.php';

if (!isset($_SESSION['account_id']) || $_SESSION['role_name'] !== 'guest') {
    header("Location: ../index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentGuestId = (int) $_SESSION['account_id'];
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $actionResult = handleGuestDashboardPost($pdo, $_POST, $currentGuestId);
        if (isset($actionResult['messages'])) {
            $messages = array_merge($messages, $actionResult['messages']);
        }
        if (isset($actionResult['errors'])) {
            $errors = array_merge($errors, $actionResult['errors']);
        }
    }
}

$libraryCard = fetchLibraryCard($pdo, $currentGuestId);

$activeBorrows = [];
$pendingPayments = [];
if ($libraryCard) {
    if ($libraryCard['is_active']) {
        $activeBorrows = fetchActiveBorrows($pdo, $libraryCard['card_id']);
    }
    $pendingPayments = fetchPendingPayments($pdo, $libraryCard['card_id']);
}

$availableBooks = fetchAvailableBooksForGuest($pdo);

$csrfToken = $_SESSION['csrf_token'];
$guestDisplayName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

require __DIR__ . '/../views/guest/dashboardView.php';
