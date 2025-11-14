<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
if ($user['role'] !== 'admin') {
    redirect('pages/map.php');
}

// Get statistics
$stats = [
    'users' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM users WHERE role != 'admin'"),
    'drivers' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'driver'"),
    'shuttles' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM shuttles WHERE is_active = 1"),
    'routes' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM routes WHERE is_active = 1"),
];

// Get recent activity
$recentUsers = OrangeRoute\Database::fetchAll("SELECT email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$activeShuttles = OrangeRoute\Database::fetchAll("
    SELECT s.shuttle_name, s.registration_number, u.email as driver_email,
           MAX(sl.created_at) as last_update
    FROM shuttles s
    LEFT JOIN shuttle_assignments sa ON s.id = sa.shuttle_id AND sa.is_current = 1
    LEFT JOIN users u ON sa.driver_id = u.id
    LEFT JOIN shuttle_locations sl ON s.id = sl.shuttle_id
    WHERE s.is_active = 1
    GROUP BY s.id
    ORDER BY last_update DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Admin Dashboard - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <?php $title = 'Admin'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
        <h2>Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);">
                <div class="stat-value"><?= $stats['drivers'] ?></div>
                <div class="stat-label">Active Drivers</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #2196F3 0%, #42A5F5 100%);">
                <div class="stat-value"><?= $stats['shuttles'] ?></div>
                <div class="stat-label">Shuttles</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #9C27B0 0%, #BA68C8 100%);">
                <div class="stat-value"><?= $stats['routes'] ?></div>
                <div class="stat-label">Routes</div>
            </div>
        </div>
        
        <h3>Management</h3>
        <div class="admin-menu">
            <a href="admin/users.php" class="menu-btn">
                <span class="menu-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="7" r="4"></circle>
                        <circle cx="17" cy="10" r="3"></circle>
                        <path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6M13 21c0-2.5 1.8-4.5 4-4.5s4 2 4 4.5"></path>
                    </svg>
                </span>
                <span class="menu-label">Users</span>
            </a>
            <a href="admin/shuttles.php" class="menu-btn">
                <span class="menu-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                        <path d="M5 11V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5"></path>
                    </svg>
                </span>
                <span class="menu-label">Shuttles</span>
            </a>
            <a href="admin/routes.php" class="menu-btn">
                <span class="menu-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                </span>
                <span class="menu-label">Routes</span>
            </a>
            <a href="admin/assignments.php" class="menu-btn">
                <span class="menu-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </span>
                <span class="menu-label">Assignments</span>
            </a>
        </div>
        
        <div class="card">
            <h3>Recent Users</h3>
            <?php foreach ($recentUsers as $u): ?>
            <div class="activity-item">
                <div><strong><?= e($u['email']) ?></strong></div>
                <div class="activity-time">
                    <span class="badge badge-<?= $u['role'] === 'driver' ? 'success' : 'primary' ?>"><?= e($u['role']) ?></span>
                    • <?= date('M d, Y', strtotime($u['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h3>Active Shuttles</h3>
            <?php foreach ($activeShuttles as $shuttle): ?>
            <div class="activity-item">
                <div><strong><?= e($shuttle['shuttle_name']) ?></strong></div>
                <div class="activity-time">
                    <?= e($shuttle['registration_number']) ?>
                    <?php if ($shuttle['driver_email']): ?>
                        • Driver: <?= e($shuttle['driver_email']) ?>
                    <?php endif; ?>
                    <?php if ($shuttle['last_update']): ?>
                        • Updated <?= date('H:i', strtotime($shuttle['last_update'])) ?>
                    <?php else: ?>
                        • No updates yet
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <?php $active = 'dashboard'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
</body>
</html>
