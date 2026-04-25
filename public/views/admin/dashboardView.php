<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
</head>
<body>
    <div class="glass-container dashboard-container admin-dashboard">
        <div class="nav">
            <h1 class="title dashboard-title">Admin Dashboard</h1>
            <a href="/logout.php" class="btn btn-inline btn-danger">Logout</a>
        </div>

        <div class="panel">
            <h2 class="panel-heading">Welcome, <?= htmlspecialchars($adminDisplayName) ?>!</h2>
            <p class="panel-subtext">Manage accounts, roles, and access from this dashboard.</p>
        </div>

        <?php foreach ($messages as $message): ?>
            <div class="alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert-error left-align"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <div class="panel">
            <h3 class="section-title">Create Admin/Librarian Account</h3>
            <form method="POST" class="admin-form-grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="create_user">

                <div class="form-group">
                    <label class="form-label">First name</label>
                    <input type="text" name="first_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last name</label>
                    <input type="text" name="last_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" minlength="8" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role_name" class="form-input" required>
                        <option value="admin">Admin</option>
                        <option value="librarian">Librarian</option>
                    </select>
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_active" checked>
                        Active account
                    </label>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-inline">Create Account</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <h3 class="section-title">System Actions</h3>
            <p class="panel-subtext">Trigger automated system processes manually.</p>
            <form method="POST" class="inline-form" onsubmit="return confirm('This will pause all active subscriptions and generate payment records. Are you sure?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="simulate_billing">
                <button type="submit" class="btn btn-inline" style="background: #eab308; color: #000;">Simulate 1st of Month (Billing)</button>
            </form>
        </div>

        <div class="panel">
            <h3 class="section-title">User Management</h3>
            <form method="GET" class="filter-grid" id="userFilterForm">
                <div class="form-group">
                    <label class="form-label">Search by name</label>
                    <input type="text" name="search" class="form-input" placeholder="First or last name" value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-input">
                        <option value="">All roles</option>
                        <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="librarian" <?= $filters['role'] === 'librarian' ? 'selected' : '' ?>>Librarian</option>
                        <option value="guest" <?= $filters['role'] === 'guest' ? 'selected' : '' ?>>Guest</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-input">
                        <option value="">All statuses</option>
                        <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group form-actions">
                    <button type="submit" class="btn btn-inline">Apply Filters</button>
                </div>
            </form>

            <div class="table-wrapper" id="userTableRegion">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Set Active</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr>
                            <td colspan="8" class="empty-state">No users found for the current filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php $isSelf = ((int) $user['account_id'] === (int) ($_SESSION['account_id'] ?? 0)); ?>
                            <tr>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td class="cap"><?= htmlspecialchars($user['role_name']) ?></td>
                                <td>
                                    <span class="status-pill <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="inline-form status-toggle-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle_user_active">
                                        <input type="hidden" name="target_id" value="<?= (int) $user['account_id'] ?>">
                                        <input type="hidden" name="next_is_active" value="<?= $user['is_active'] ? '0' : '1' ?>">
                                        <label class="status-toggle">
                                            <input
                                                type="checkbox"
                                                class="status-toggle-input"
                                                <?= $user['is_active'] ? 'checked' : '' ?>
                                                <?= $isSelf ? 'disabled' : '' ?>
                                                onchange="event.preventDefault(); handleToggleUserActive(this.form)"
                                            >
                                            <span class="status-toggle-indicator"></span>
                                        </label>
                                    </form>
                                    <?php if ($isSelf): ?>
                                        <span class="text-muted self-status-note">Self</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))) ?></td>
                                <td>
                                    <?php if (in_array($user['role_name'], ['guest', 'librarian'], true)): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_id" value="<?= (int) $user['account_id'] ?>">
                                            <button type="submit" class="btn btn-inline btn-danger">Delete</button>
                                        </form>
                                    <?php elseif ($isSelf): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Delete your own admin account? You will be logged out.');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_id" value="<?= (int) $user['account_id'] ?>">
                                            <button type="submit" class="btn btn-inline btn-danger">Delete My Account</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Not allowed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="/assets/js/adminDashboard.js"></script>
</body>
</html>
