<?php

function handleAdminDashboardPost(PDO $pdo, array $postData, int $currentAdminId): array
{
    $messages = [];
    $errors = [];
    $shouldLogout = false;

    $action = $postData['action'] ?? '';

    if ($action === 'delete_user') {
        $deleteResult = deleteUserForAdminDashboard($pdo, (int) ($postData['target_id'] ?? 0), $currentAdminId);
        $messages = array_merge($messages, $deleteResult['messages']);
        $errors = array_merge($errors, $deleteResult['errors']);
        $shouldLogout = $deleteResult['should_logout'];
    } elseif ($action === 'toggle_user_active') {
        $toggleResult = toggleUserActiveStatusForAdminDashboard(
            $pdo,
            (int) ($postData['target_id'] ?? 0),
            $currentAdminId,
            ($postData['next_is_active'] ?? '0') === '1'
        );
        $messages = array_merge($messages, $toggleResult['messages']);
        $errors = array_merge($errors, $toggleResult['errors']);
    } elseif ($action === 'create_user') {
        $createResult = createPrivilegedAccount($pdo, $postData, $currentAdminId);
        $messages = array_merge($messages, $createResult['messages']);
        $errors = array_merge($errors, $createResult['errors']);
    } elseif ($action === 'simulate_billing') {
        $billingResult = processMonthlySubscriptions($pdo);
        $messages = array_merge($messages, $billingResult['messages']);
        $errors = array_merge($errors, $billingResult['errors']);
    } else {
        $errors[] = 'Unknown action.';
    }

    return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
}

function deleteUserForAdminDashboard(PDO $pdo, int $targetId, int $currentAdminId): array
{
    $messages = [];
    $errors = [];
    $shouldLogout = false;

    if ($targetId <= 0) {
        $errors[] = 'Invalid user selected for deletion.';
        return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
    }

    $targetStmt = $pdo->prepare("
        SELECT a.account_id, r.role_name
        FROM accounts a
        JOIN roles r ON a.role_id = r.role_id
        WHERE a.account_id = :target_id
    ");
    $targetStmt->execute(['target_id' => $targetId]);
    $targetAccount = $targetStmt->fetch();

    if (!$targetAccount) {
        $errors[] = 'User not found.';
        return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
    }

    $isSelfDelete = ((int) $targetAccount['account_id'] === $currentAdminId);
    $targetRole = $targetAccount['role_name'];

    if ($isSelfDelete && $targetRole === 'admin') {
        $adminCountStmt = $pdo->query("
            SELECT COUNT(*)
            FROM accounts a
            JOIN roles r ON a.role_id = r.role_id
            WHERE r.role_name = 'admin'
        ");
        $adminCount = (int) $adminCountStmt->fetchColumn();

        if ($adminCount <= 1) {
            $errors[] = 'You cannot delete the last remaining admin account.';
            return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
        }
    } elseif (!in_array($targetRole, ['guest', 'librarian'], true)) {
        $errors[] = 'Only guest or librarian accounts can be deleted (except your own admin account).';
        return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
    }

    $deleteStmt = $pdo->prepare("DELETE FROM accounts WHERE account_id = :target_id");
    $deleteStmt->execute(['target_id' => $targetId]);

    if ($deleteStmt->rowCount() > 0) {
        if ($isSelfDelete) {
            $messages[] = 'Your admin account was deleted.';
            $shouldLogout = true;
        } else {
            $messages[] = 'User deleted successfully.';
        }
    } else {
        $errors[] = 'Unable to delete user.';
    }

    return ['messages' => $messages, 'errors' => $errors, 'should_logout' => $shouldLogout];
}

function toggleUserActiveStatusForAdminDashboard(PDO $pdo, int $targetId, int $currentAdminId, bool $nextIsActive): array
{
    $messages = [];
    $errors = [];

    if ($targetId <= 0) {
        $errors[] = 'Invalid user selected.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    if ($targetId === $currentAdminId && !$nextIsActive) {
        $errors[] = 'You cannot mark your own admin account as inactive.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    $updateStmt = $pdo->prepare("
        UPDATE accounts
        SET is_active = :is_active
        WHERE account_id = :target_id
    ");
    $updateStmt->execute([
        'is_active' => $nextIsActive ? 'TRUE' : 'FALSE',
        'target_id' => $targetId,
    ]);

    if ($updateStmt->rowCount() > 0) {
        $messages[] = $nextIsActive ? 'Account marked as active.' : 'Account marked as inactive.';
    } else {
        $errors[] = 'No status update was made.';
    }

    return ['messages' => $messages, 'errors' => $errors];
}

function createPrivilegedAccount(PDO $pdo, array $postData, int $currentAdminId): array
{
    $messages = [];
    $errors = [];

    $firstName = trim($postData['first_name'] ?? '');
    $lastName = trim($postData['last_name'] ?? '');
    $email = trim($postData['email'] ?? '');
    $password = $postData['password'] ?? '';
    $roleName = trim($postData['role_name'] ?? '');
    $isActive = isset($postData['is_active']) ? 1 : 0;

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $roleName === '') {
        $errors[] = 'All create-account fields are required.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    if (!in_array($roleName, ['admin', 'librarian'], true)) {
        $errors[] = 'Only admin and librarian roles can be created here.';
        return ['messages' => $messages, 'errors' => $errors];
    }

    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO accounts (role_id, created_by, first_name, last_name, email, password_hash, is_active)
            VALUES (
                (SELECT role_id FROM roles WHERE role_name = :role_name),
                :created_by,
                :first_name,
                :last_name,
                :email,
                crypt(:password, gen_salt('bf')),
                :is_active
            )
        ");

        $insertStmt->execute([
            'role_name' => $roleName,
            'created_by' => $currentAdminId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'is_active' => $isActive ? 'TRUE' : 'FALSE',
        ]);

        $messages[] = ucfirst($roleName) . ' account created successfully.';
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23505') {
            $errors[] = 'An account with that email already exists.';
        } else {
            $errors[] = 'Unable to create account right now. Please try again.';
        }
    }

    return ['messages' => $messages, 'errors' => $errors];
}

function normalizeUserFilters(array $queryParams): array
{
    $search = trim($queryParams['search'] ?? '');
    $roleFilter = trim($queryParams['role'] ?? '');
    $statusFilter = trim($queryParams['is_active'] ?? '');

    $allowedRoles = ['guest', 'librarian', 'admin'];
    if (!in_array($roleFilter, $allowedRoles, true)) {
        $roleFilter = '';
    }

    if (!in_array($statusFilter, ['1', '0'], true)) {
        $statusFilter = '';
    }

    return [
        'search' => $search,
        'role' => $roleFilter,
        'is_active' => $statusFilter,
    ];
}

function fetchUsersForAdminDashboard(PDO $pdo, array $filters): array
{
    $sql = "
        SELECT a.account_id, a.first_name, a.last_name, a.email, a.is_active, a.created_at, r.role_name
        FROM accounts a
        JOIN roles r ON a.role_id = r.role_id
        WHERE 1=1
    ";
    $params = [];

    if ($filters['search'] !== '') {
        $sql .= " AND (LOWER(a.first_name) LIKE :search OR LOWER(a.last_name) LIKE :search)";
        $params['search'] = '%' . mb_strtolower($filters['search']) . '%';
    }

    if ($filters['role'] !== '') {
        $sql .= " AND r.role_name = :role_name";
        $params['role_name'] = $filters['role'];
    }

    if ($filters['is_active'] !== '') {
        $sql .= " AND a.is_active = :is_active";
        $params['is_active'] = ($filters['is_active'] === '1') ? 'TRUE' : 'FALSE';
    }

    $sql .= " ORDER BY a.created_at DESC";

    $userStmt = $pdo->prepare($sql);
    $userStmt->execute($params);

    return $userStmt->fetchAll();
}

function processMonthlySubscriptions(PDO $pdo): array
{
    $messages = [];
    $errors = [];

    try {
        $stmt = $pdo->query("
            UPDATE subscriptions
            SET status = 'paused'
            WHERE status = 'active'
        ");

        $rowCount = $stmt->rowCount();

        $messages[] = "Successfully processed billing. {$rowCount} active subscriptions paused.";
    } catch (PDOException $e) {
        $errors[] = 'An error occurred while processing monthly subscriptions: ' . $e->getMessage();
    }

    return ['messages' => $messages, 'errors' => $errors];
}
