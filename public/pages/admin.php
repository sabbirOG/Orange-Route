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
    'assignments' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM route_assignments WHERE is_current = 1"),
    'routes' => OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM routes WHERE is_active = 1"),
];

// Get recent activity
$recentUsers = OrangeRoute\Database::fetchAll("SELECT username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$activeRoutes = OrangeRoute\Database::fetchAll("SELECT r.route_name, r.distance_type as category, u.username as driver_name, MAX(rl.created_at) as last_update FROM routes r LEFT JOIN route_assignments ra ON r.id = ra.route_id AND ra.is_current = 1 LEFT JOIN users u ON ra.driver_id = u.id LEFT JOIN route_locations rl ON r.id = rl.route_id WHERE r.is_active = 1 GROUP BY r.id ORDER BY last_update DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Admin Dashboard - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title = 'Admin'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
        <h2>Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="10" r="3"></circle>
                        <path d="M10.5 15.5L8 21M13.5 15.5L16 21"></path>
                        <path d="M3 7h18"></path>
                        <path d="M5 7l2-4h10l2 4"></path>
                    </svg>
                </div>
                <div class="stat-value"><?= $stats['drivers'] ?></div>
                <div class="stat-label">Drivers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                <div class="stat-value"><?= $stats['assignments'] ?></div>
                <div class="stat-label">Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="6" cy="19" r="3"></circle>
                        <path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"></path>
                        <circle cx="18" cy="5" r="3"></circle>
                    </svg>
                </div>
                <div class="stat-value"><?= $stats['routes'] ?></div>
                <div class="stat-label">Routes</div>
            </div>
        </div>
        
        <h3 class="section-title">Management</h3>
        <div class="quick-actions">
            <a href="admin/users.php" class="action-btn">
                <div class="action-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="7" r="4"></circle>
                        <circle cx="17" cy="10" r="3"></circle>
                        <path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6M13 21c0-2.5 1.8-4.5 4-4.5s4 2 4 4.5"></path>
                    </svg>
                </div>
                <span class="action-label">Drivers</span>
            </a>
            <a href="admin/routes.php" class="action-btn">
                <div class="action-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                </div>
                <span class="action-label">Routes</span>
            </a>
            <a href="admin/assignments.php" class="action-btn">
                <div class="action-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </div>
                <span class="action-label">Assignments</span>
            </a>
        </div>
        
        <div class="card">
            <h3>Recent Users</h3>
            <?php foreach ($recentUsers as $u): ?>
            <div class="activity-item">
                <div><strong><?= e($u['username'] ?: 'User') ?></strong></div>
                <div class="activity-time">
                    <span class="badge badge-<?= $u['role'] === 'driver' ? 'success' : 'primary' ?>"><?= e($u['role']) ?></span>
                    • <?= date('M d, Y', strtotime($u['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h3>Active Routes</h3>
            <?php foreach ($activeRoutes as $route): ?>
            <div class="activity-item">
                <div><strong><?= e($route['route_name']) ?></strong></div>
                <div class="activity-time">
                    <?php if ($route['driver_name']): ?>
                        Driver: <?= e($route['driver_name']) ?>
                    <?php else: ?>
                        No driver assigned
                    <?php endif; ?>
                    <?php if ($route['last_update']): ?>
                        • Updated <?= date('H:i', strtotime($route['last_update'])) ?>
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
