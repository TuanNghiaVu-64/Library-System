<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

function dashboardPathByRole(string $roleName): string
{
    $roleToPath = [
        'admin' => 'admin/dashboard.php',
        'librarian' => 'librarian/dashboard.php',
        'guest' => 'guest/dashboard.php',
    ];

    return $roleToPath[$roleName] ?? 'index.php';
}

// Redirect to dashboard if already logged in
if (isset($_SESSION['account_id']) && isset($_SESSION['role_name'])) {
    $dashboardPath = dashboardPathByRole((string) $_SESSION['role_name']);
    header("Location: {$dashboardPath}");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("
            SELECT a.account_id, a.password_hash, a.first_name, a.last_name, r.role_name 
            FROM accounts a
            JOIN roles r ON a.role_id = r.role_id
            WHERE a.email = :email AND a.is_active = TRUE
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Use PostgreSQL pgcrypto crypt function to verify the password hash
        if ($user) {
            $verifyStmt = $pdo->prepare("SELECT (crypt(:password, :hash) = :hash) AS is_valid");
            $verifyStmt->execute(['password' => $password, 'hash' => $user['password_hash']]);
            $isValid = $verifyStmt->fetchColumn();

            if ($isValid) {
                $_SESSION['account_id'] = $user['account_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role_name'] = $user['role_name'];

                header("Location: " . dashboardPathByRole((string) $user['role_name']));
                exit;
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Account not found or inactive.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Login</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
    <div class="glass-container">
        <h1 class="title">Welcome Back</h1>
        <p class="subtitle">Sign in to the Library Management System</p>
        
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" required placeholder="admin@library.local" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; color: var(--text-secondary); font-size: 0.8rem; line-height: 1.5;">
            <p>Admin: admin@library.local / Admin@1234</p>
            <p>Librarian: sarah.c@library.local / Librarian@2026</p>
            <p>Guest: john.doe@email.com / MemberPass123</p>
        </div>
    </div>
</body>
</html>
