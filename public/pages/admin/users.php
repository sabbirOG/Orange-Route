<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
if ($user['role'] !== 'admin') {
    redirect('pages/map.php');
}

$success = null;
$error = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_active') {
        $userId = $_POST['user_id'] ?? 0;
        $current = OrangeRoute\Database::fetchValue("SELECT is_active FROM users WHERE id = ?", [$userId]);
        OrangeRoute\Database::query("UPDATE users SET is_active = ? WHERE id = ?", [!$current, $userId]);
        $success = 'User status updated';
    }
    
    if ($action === 'delete') {
        $userId = $_POST['user_id'] ?? 0;
        OrangeRoute\Database::query("DELETE FROM users WHERE id = ? AND role != 'admin'", [$userId]);
        $success = 'User deleted';
    }
}

// Get all users
$users = OrangeRoute\Database::fetchAll("
    SELECT id, username, email, role, is_active, email_verified, created_at, last_login_at
    FROM users
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <style>
        .user-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .user-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="../admin.php" style="text-decoration: none;">← Back</a>
        <div class="logo">Users</div>
        <div></div>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <h2>All Users (<?= count($users) ?>)</h2>
        
        <?php foreach ($users as $u): ?>
        <div class="user-card">
            <div class="user-header">
                <div>
                    <strong><?= e($u['username'] ?? $u['email']) ?></strong>
                    <?php if (!$u['is_active']): ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                    <?php if (!$u['email_verified']): ?>
                        <span class="badge" style="background: #FF9800;">Unverified</span>
                    <?php endif; ?>
                </div>
                <span class="badge badge-<?= $u['role'] === 'driver' ? 'success' : 'primary' ?>">
                    <?= e(ucfirst($u['role'])) ?>
                </span>
            </div>
            <div class="text-muted" style="font-size: 13px;">
                Email: <?= e($u['email']) ?><br>
                Joined: <?= date('M d, Y', strtotime($u['created_at'])) ?><br>
                <?php if ($u['last_login_at']): ?>
                    Last login: <?= date('M d, H:i', strtotime($u['last_login_at'])) ?>
                <?php else: ?>
                    Last login: Never
                <?php endif; ?>
            </div>
            
            <?php if ($u['role'] !== 'admin'): ?>
            <div class="user-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: <?= $u['is_active'] ? '#f44336' : '#4CAF50' ?>; color: white;">
                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: #757575; color: white;">Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
