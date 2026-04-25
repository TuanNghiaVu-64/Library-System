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

$cardId = (int) ($_GET['card_id'] ?? 0);
if ($cardId <= 0) {
    header('Location: manageBorrows.php');
    exit;
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid request token. Please refresh and try again.';
    } else {
        $actionResult = handleLibrarianBorrowersPost($pdo, $_POST);
        if (isset($actionResult['messages'])) {
            $messages = array_merge($messages, $actionResult['messages']);
        }
        if (isset($actionResult['errors'])) {
            $errors = array_merge($errors, $actionResult['errors']);
        }
    }
}

// Just checking if card exists and get user info
$stmt = $pdo->prepare("
    SELECT lc.card_number, a.first_name, a.last_name 
    FROM library_cards lc JOIN accounts a ON lc.account_id = a.account_id 
    WHERE lc.card_id = :card_id
");
$stmt->execute(['card_id' => $cardId]);
$cardInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cardInfo) {
    header('Location: manageBorrows.php');
    exit;
}

$loans = fetchBorrowerLoans($pdo, $cardId);
$csrfToken = $_SESSION['csrf_token'];

require __DIR__ . '/../views/librarian/manageBorrowerLoansView.php';
