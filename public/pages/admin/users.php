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
    
    if ($action === 'add_driver') {
        $driverId = trim($_POST['driver_id'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        if (empty($driverId) || empty($password)) {
            $error = 'Driver ID and password are required';
        } elseif (!preg_match('/^[0-9]{5}$/', $driverId)) {
            $error = 'Driver ID must be exactly 5 digits';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            try {
                // Generate email from driver ID
                $email = 'driver' . $driverId . '@orangeroute.local';
                OrangeRoute\Auth::register($email, $password, 'driver', $username);
                // Mark driver as verified
                OrangeRoute\Database::query("UPDATE users SET email_verified = 1 WHERE email = ?", [$email]);
                $success = 'Driver account created successfully (ID: ' . $driverId . ')';
            } catch (Exception $e) {
                $error = 'Driver ID already exists';
            }
        }
    }
    
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
    <title>Manage Drivers - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
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
<?php $title='Drivers'; $backHref='../admin.php'; include __DIR__ . '/../_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Add New Driver</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_driver">
                <div class="form-group">
                    <label>Driver ID</label>
                    <input type="text" name="driver_id" required placeholder="12345" pattern="[0-9]{5}" maxlength="5">
                    <small class="text-muted">Exactly 5 digits for driver login</small>
                </div>
                <div class="form-group">
                    <label>Driver Name</label>
                    <input type="text" name="username" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Minimum 6 characters" minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">Add Driver</button>
            </form>
        </div>
        
        <h2>All Users (<?= count($users) ?>)</h2>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; align-items: center;">
            <input id="user-search" type="text" placeholder="Search by name or email..." style="flex:1; min-width:180px; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
            <select id="user-role-filter" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="driver">Driver</option>
            </select>
            <select id="user-status-filter" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div id="user-list">
        <?php foreach ($users as $i => $u): ?>
        <div class="user-card" 
            data-username="<?= strtolower(e($u['username'] ?? '')) ?>"
            data-email="<?= strtolower(e($u['email'])) ?>"
            data-role="<?= e($u['role']) ?>"
            data-status="<?= $u['is_active'] ? 'active' : 'inactive' ?>"
            data-index="<?= $i ?>">
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
                <form method="POST" style="display: inline;" onsubmit="return confirmToggle(this, '<?= e($u['username'] ?? $u['email']) ?>', <?= $u['is_active'] ? 1 : 0 ?>);">
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

        <div id="user-pagination" style="display:none; justify-content:center; gap:8px; margin:16px 0;"></div>

        <script>
        // Confirmation dialog for activate/deactivate
        function confirmToggle(form, name, isActive) {
            return confirm((isActive ? 'Deactivate' : 'Activate') + ' user "' + name + '"?');
        }

        // Responsive tweaks
        const style = document.createElement('style');
        style.innerHTML = `
        @media (max-width: 600px) {
            .user-card { padding: 10px; font-size: 15px; }
            .user-header { flex-direction: column; align-items: flex-start; gap: 4px; }
            .user-actions { flex-direction: column; gap: 6px; }
            .container { padding: 6px; }
        }
        `;
        document.head.appendChild(style);

        // Pagination (if more than 20 users)
        const userCards = Array.from(document.querySelectorAll('.user-card'));
        const perPage = 20;
        if (userCards.length > perPage) {
            const pagination = document.getElementById('user-pagination');
            pagination.style.display = 'flex';
            let currentPage = 1;
            const totalPages = Math.ceil(userCards.length / perPage);
            function showPage(page) {
                userCards.forEach(card => {
                    const idx = parseInt(card.dataset.index);
                    card.style.display = (idx >= (page-1)*perPage && idx < page*perPage) ? '' : 'none';
                });
                pagination.innerHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.textContent = i;
                    btn.className = 'btn btn-sm' + (i === page ? ' btn-primary' : '');
                    btn.onclick = () => { currentPage = i; showPage(i); };
                    pagination.appendChild(btn);
                }
            }
            showPage(1);
        }
        </script>

        <script>
        // Simple client-side filter for users
        const searchInput = document.getElementById('user-search');
        const roleFilter = document.getElementById('user-role-filter');
        const statusFilter = document.getElementById('user-status-filter');
        const userCards = Array.from(document.querySelectorAll('.user-card'));

        function filterUsers() {
            const search = searchInput.value.trim().toLowerCase();
            const role = roleFilter.value;
            const status = statusFilter.value;
            userCards.forEach(card => {
                const username = card.dataset.username;
                const email = card.dataset.email;
                const cardRole = card.dataset.role;
                const cardStatus = card.dataset.status;
                let show = true;
                if (search && !(username.includes(search) || email.includes(search))) show = false;
                if (role && cardRole !== role) show = false;
                if (status && cardStatus !== status) show = false;
                card.style.display = show ? '' : 'none';
            });
        }
        searchInput.addEventListener('input', filterUsers);
        roleFilter.addEventListener('change', filterUsers);
        statusFilter.addEventListener('change', filterUsers);
        </script>
    </div>
</body>
</html>
