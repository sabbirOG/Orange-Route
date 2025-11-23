<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
$role = $user['role'] ?? 'student';

// ==================== ADMIN DASHBOARD ====================
if ($role === 'admin') {
    $stats['total_users'] = OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM users") ?? 0;
    $stats['total_drivers'] = OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'driver'") ?? 0;
    $stats['active_routes'] = OrangeRoute\Database::fetchValue("
            SELECT COUNT(DISTINCT route_id) 
            FROM route_locations 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ") ?? 0;
    $stats['active_assignments'] = OrangeRoute\Database::fetchValue("SELECT COUNT(*) FROM route_assignments WHERE is_current = 1") ?? 0;
    
    $recent_activity = OrangeRoute\Database::fetchAll("
        SELECT u.email, u.last_login_at, u.role
        FROM users u
        WHERE u.last_login_at IS NOT NULL
        ORDER BY u.last_login_at DESC
        LIMIT 5
    ");
}

// ==================== DRIVER DASHBOARD ====================
elseif ($role === 'driver') {
    // Get driver's current assignment
    $current_assignment = OrangeRoute\Database::fetch("
        SELECT 
            ra.id,
            r.id as route_id,
            r.route_name,
            r.distance_type as category,
            r.description as route_description,
            r.is_active,
            ra.assigned_at
        FROM route_assignments ra
        JOIN routes r ON ra.route_id = r.id
        WHERE ra.driver_id = ? AND ra.is_current = 1
        LIMIT 1
    ", [$user['id']]);
    
    if ($current_assignment) {
        // Get last location update
        $last_location = OrangeRoute\Database::fetch("
                SELECT latitude, longitude, created_at, speed
                FROM route_locations
                WHERE route_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ", [$current_assignment['route_id']]);
        
        // Get route stops
        $route_stops = OrangeRoute\Database::fetchAll("
            SELECT stop_name, latitude, longitude, stop_order, estimated_time
            FROM route_stops
            WHERE route_id = ?
            ORDER BY stop_order ASC
        ", [$current_assignment['route_id']]);
        
        // Count today's updates
        $updates_today = OrangeRoute\Database::fetchValue("
                SELECT COUNT(*)
                FROM route_locations
                WHERE route_id = ? AND DATE(created_at) = CURDATE()
            ", [$current_assignment['route_id']]) ?? 0;
    }
}

// ==================== STUDENT DASHBOARD ====================
else {
    // Get only routes that are actively tracking RIGHT NOW (within last 5 minutes)
    $active_routes = OrangeRoute\Database::fetchAll("
        SELECT 
            r.id,
            r.route_name,
            r.distance_type as category,
            r.description,
            r.is_active,
            u.username as driver_name,
            rl.latitude,
            rl.longitude,
            rl.created_at as last_seen,
            rl.speed,
            TIMESTAMPDIFF(MINUTE, rl.created_at, NOW()) as minutes_ago
        FROM routes r
        INNER JOIN route_assignments ra ON r.id = ra.route_id AND ra.is_current = 1
        INNER JOIN users u ON ra.driver_id = u.id
        INNER JOIN (
            SELECT route_id, latitude, longitude, created_at, speed, heading, accuracy,
            ROW_NUMBER() OVER (PARTITION BY route_id ORDER BY created_at DESC) as rn
            FROM route_locations
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ) rl ON r.id = rl.route_id AND rl.rn = 1
        WHERE r.is_active = 1
        ORDER BY rl.created_at DESC
    ");
    
    $stats['active_routes_now'] = count($active_routes);
    $stats['total_routes'] = count($active_routes);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title><?= $role === 'admin' ? 'Admin' : ($role === 'driver' ? 'Driver' : 'Student') ?> Dashboard - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
            padding: 24px 20px;
            margin-bottom: 20px;
            border-radius: 0 0 24px 24px;
        }
        .welcome-msg { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .welcome-sub { opacity: 0.9; font-size: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 16px; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
        .stat-icon { width: 40px; height: 40px; background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(255, 140, 97, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
        .stat-icon svg { color: var(--primary); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--text); }
        .stat-label { font-size: 13px; color: var(--text-light); font-weight: 500; margin-top: 2px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--text); }
        .assignment-card { background: linear-gradient(135deg, #004E89 0%, #1A5FA0 100%); color: white; padding: 20px; border-radius: 16px; margin-bottom: 20px; box-shadow: var(--shadow-md); }
        .assignment-card h3 { margin: 0 0 12px 0; color: white; display: flex; align-items: center; gap: 8px; }
        .assignment-card p { margin: 6px 0; color: rgba(255, 255, 255, 0.95); font-size: 14px; }
        .tracking-card { background: white; padding: 20px; border-radius: 16px; margin-bottom: 16px; border: 2px solid var(--border); }
        .tracking-btn { width: 100%; padding: 16px; border-radius: 12px; border: none; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .tracking-btn.active { background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; }
        .tracking-btn.inactive { background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); color: white; }
        .tracking-status { text-align: center; margin-top: 12px; font-size: 14px; color: var(--text-light); }
        .shuttle-item { background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 12px; display: flex; gap: 12px; align-items: center; transition: all 0.2s; }
        .shuttle-item:active { transform: scale(0.98); }
        .shuttle-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
        .shuttle-info { flex: 1; min-width: 0; }
        .shuttle-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
        .shuttle-meta { font-size: 13px; color: var(--text-light); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .status-active { background: var(--success); }
        .status-inactive { background: var(--text-light); }
        .route-card { background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 12px; }
        .route-card h4 { margin: 0 0 8px 0; font-size: 16px; color: var(--text); display: flex; align-items: center; gap: 8px; }
        .route-card p { margin: 0; font-size: 13px; color: var(--text-light); }
        .stop-list { margin-top: 12px; }
        .stop-item { padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13px; display: flex; justify-content: space-between; }
        .stop-item:last-child { border-bottom: none; }
        .quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .action-btn { background: white; border: 2px solid var(--border); padding: 16px; border-radius: 16px; text-decoration: none; color: var(--text); display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s; }
        .action-btn:active { border-color: var(--primary); transform: scale(0.98); }
        .action-icon { width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .action-label { font-size: 14px; font-weight: 600; }
        .activity-item { padding: 12px 0; border-bottom: 1px solid var(--border); }
        .activity-item:last-child { border-bottom: none; }
        .activity-time { font-size: 12px; color: var(--text-light); margin-top: 4px; }
    </style>
</head>
<body>
    <?php $title = 'Dashboard'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container" style="padding-bottom: 80px;">
        <div class="dashboard-header">
            <div class="welcome-msg">
                <?php if ($role === 'admin'): ?>
                    Admin Dashboard
                <?php elseif ($role === 'driver'): ?>
                    Driver Dashboard
                <?php else: ?>
                    Track Your Routes
                <?php endif; ?>
            </div>
            <div class="welcome-sub"><?= e($user['email']) ?> • <?= ucfirst(e($role)) ?></div>
        </div>

        <?php if ($role === 'admin'): ?>
            <!-- ==================== ADMIN VIEW ==================== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                            <circle cx="12" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['active_routes'] ?></div>
                    <div class="stat-label">Active Routes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['total_drivers'] ?></div>
                    <div class="stat-label">Drivers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['active_assignments'] ?></div>
                    <div class="stat-label">Assignments</div>
                </div>
            </div>

            <h3 class="section-title">Quick Actions</h3>
            <div class="quick-actions">
                <a href="admin.php" class="action-btn">
                    <div class="action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"></path>
                        </svg>
                    </div>
                    <span class="action-label">Manage System</span>
                </a>
                <a href="admin/users.php" class="action-btn">
                    <div class="action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="7" r="4"></circle>
                            <circle cx="17" cy="10" r="3"></circle>
                            <path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6M13 21c0-2.5 1.8-4.5 4-4.5s4 2 4 4.5"></path>
                        </svg>
                    </div>
                    <span class="action-label">Users</span>
                </a>
                <!-- Removed Shuttles action (deprecated) -->
                <a href="admin/routes.php" class="action-btn">
                    <div class="action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                            <circle cx="12" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <span class="action-label">Routes</span>
                </a>
            </div>

            <?php if (isset($recent_activity) && count($recent_activity) > 0): ?>
            <h3 class="section-title">Recent Activity</h3>
            <div class="card">
                <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <strong><?= e($activity['email']) ?></strong>
                    <span class="badge badge-<?= $activity['role'] === 'admin' ? 'danger' : ($activity['role'] === 'driver' ? 'success' : 'primary') ?>" style="margin-left: 8px; font-size: 10px;">
                        <?= ucfirst(e($activity['role'])) ?>
                    </span>
                    <div class="activity-time">Last login: <?= date('M d, g:i A', strtotime($activity['last_login_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php elseif ($role === 'driver'): ?>
            <!-- ==================== DRIVER VIEW ==================== -->
            <?php if (isset($current_assignment)): ?>
                <div class="assignment-card">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                            <circle cx="12" cy="11" r="2"></circle>
                        </svg>
                        Current Assignment
                    </h3>
                    <p><strong><?= e($current_assignment['route_name']) ?></strong></p>
                    <p>
                        <span class="badge badge-<?= $current_assignment['category'] === 'long' ? 'primary' : 'success' ?>">
                            <?= $current_assignment['category'] === 'long' ? 'Long Route' : 'Short Route' ?>
                        </span>
                    </p>
                    <p><?= e($current_assignment['route_description']) ?></p>
                    <p style="font-size: 12px; opacity: 0.8; margin-top: 8px;">Assigned: <?= date('M d, Y', strtotime($current_assignment['assigned_at'])) ?></p>
                </div>

                <div class="tracking-card">
                    <h3 style="margin: 0 0 12px 0; font-size: 16px;">Location Tracking</h3>
                    <button id="trackingBtn" class="tracking-btn <?= $current_assignment['is_active'] ? 'active' : 'inactive' ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                        <?= $current_assignment['is_active'] ? 'Stop Tracking' : 'Start Tracking' ?>
                    </button>
                    <div id="trackingStatus" class="tracking-status"><?= $current_assignment['is_active'] ? 'Tracking active...' : 'Not tracking' ?></div>
                    <?php if (isset($last_location)): ?>
                    <p style="font-size: 12px; color: var(--text-light); margin-top: 12px; text-align: center;">
                        Last update: <?= date('M d, g:i A', strtotime($last_location['created_at'])) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                                <circle cx="12" cy="11" r="2"></circle>
                            </svg>
                        </div>
                        <div class="stat-value"><?= count($route_stops ?? []) ?></div>
                        <div class="stat-label">Route Stops</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div class="stat-value"><?= $updates_today ?? 0 ?></div>
                        <div class="stat-label">Updates Today</div>
                    </div>
                </div>

                <?php if (isset($route_stops) && count($route_stops) > 0): ?>
                <h3 class="section-title">Route Stops</h3>
                <div class="card">
                    <div class="stop-list">
                        <?php foreach ($route_stops as $stop): ?>
                        <div class="stop-item">
                            <span><strong><?= $stop['stop_order'] ?>.</strong> <?= e($stop['stop_name']) ?></span>
                            <?php if ($stop['estimated_time']): ?>
                            <span class="text-muted"><?= $stop['estimated_time'] ?> min</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="card">
                    <div style="text-align: center; padding: 40px 20px;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-light)" stroke-width="2" style="margin-bottom: 16px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>No Assignment</h3>
                        <p class="text-muted">You don't have an active route assignment. Contact your admin.</p>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ==================== STUDENT VIEW ==================== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                            <circle cx="12" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['active_routes_now'] ?></div>
                    <div class="stat-label">Active Now</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                            <circle cx="12" cy="11" r="2"></circle>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['total_routes'] ?></div>
                    <div class="stat-label">Routes</div>
                </div>
            </div>

            <h3 class="section-title">Live Routes</h3>
            <?php if (count($active_routes) > 0): ?>
                <?php foreach ($active_routes as $route): ?>
                    <?php 
                        $is_active = $route['last_seen'] && $route['minutes_ago'] < 5;
                        $maps_url = ($route['latitude'] && $route['longitude']) 
                            ? 'https://www.google.com/maps?q=' . $route['latitude'] . ',' . $route['longitude']
                            : '#';
                    ?>
                    <a href="<?= $maps_url ?>" target="_blank" class="shuttle-item" style="text-decoration: none; color: inherit; display: flex;">
                        <div class="shuttle-avatar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                                <circle cx="12" cy="11" r="2"></circle>
                            </svg>
                        </div>
                        <div class="shuttle-info">
                            <div class="shuttle-name"><?= e($route['route_name']) ?></div>
                            <div class="shuttle-meta">
                                <span class="status-dot <?= $is_active ? 'status-active' : 'status-inactive' ?>"></span>
                                <?php if ($is_active): ?>
                                    Live • <?= $route['driver_name'] ? e($route['driver_name']) : 'No driver' ?>
                                <?php else: ?>
                                    Offline
                                <?php endif; ?>
                                 • 
                                <span class="badge badge-<?= $route['category'] === 'long' ? 'primary' : 'success' ?>" style="font-size: 10px; padding: 2px 6px;">
                                    <?= $route['category'] === 'long' ? 'Long' : 'Short' ?>
                                </span>
                            </div>
                            <?php if ($route['last_seen']): ?>
                            <div class="shuttle-meta" style="font-size: 11px; margin-top: 2px;">
                                <?php if ($route['minutes_ago'] < 1): ?>
                                    Just now
                                <?php elseif ($route['minutes_ago'] < 60): ?>
                                    <?= $route['minutes_ago'] ?> min ago
                                <?php else: ?>
                                    <?= date('g:i A', strtotime($route['last_seen'])) ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 30px 20px;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-light)" stroke-width="2" style="margin: 0 auto 16px;">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3 style="margin-bottom: 8px;">No Active Routes</h3>
                    <p class="text-muted">No routes are currently active. Check back later!</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php $active = 'dashboard'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <?php if ($role === 'student'): ?>
    <script>
        // Auto-refresh shuttle positions every 15 seconds for students
        setInterval(() => {
            location.reload();
        }, 15000);
    </script>
    <?php endif; ?>
    
    <?php if ($role === 'driver' && isset($current_assignment)): ?>
    <script>
        let isTracking = false;
        let watchId = null;
        const btn = document.getElementById('trackingBtn');
        const status = document.getElementById('trackingStatus');
        const routeId = <?= $current_assignment['route_id'] ?>;
        
        btn.addEventListener('click', async () => {
            const isActive = btn.classList.contains('active');
            
            try {
                // Toggle backend route status
                const resp = await fetch('../api/toggle_route_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'route_id=' + routeId + '&active=' + (isActive ? 0 : 1)
                });
                const result = await resp.json();
                
                if (result.success) {
                    if (!isActive) {
                        // Start tracking
                        startTracking();
                    } else {
                        // Stop tracking
                        stopTracking();
                    }
                } else {
                    alert('Failed to toggle route status');
                }
            } catch (error) {
                console.error('Toggle error:', error);
                alert('Error toggling route status');
            }
        });
        
        function startTracking() {
            if (!navigator.geolocation) {
                alert('Geolocation not supported');
                return;
            }
            
            watchId = navigator.geolocation.watchPosition(
                async (position) => {
                    const data = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        speed: position.coords.speed || 0,
                        heading: position.coords.heading || 0,
                        accuracy: position.coords.accuracy || 0
                    };
                    
                    try {
                        const response = await fetch('../api/locations/update.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(data)
                        });
                        const result = await response.json();
                        if (result.success) {
                            status.textContent = 'Tracking active • Updated ' + new Date().toLocaleTimeString();
                            status.style.color = 'var(--success)';
                        }
                    } catch (error) {
                        console.error('Update failed:', error);
                    }
                },
                (error) => {
                    console.error('Location error:', error);
                    stopTracking();
                    alert('Location access denied or unavailable');
                },
                { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 }
            );
            
            isTracking = true;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Stop Tracking';
            btn.classList.remove('inactive');
            btn.classList.add('active');
            status.textContent = 'Tracking active...';
            status.style.color = 'var(--success)';
        }
        
        function stopTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            isTracking = false;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> Start Tracking';
            btn.classList.remove('active');
            btn.classList.add('inactive');
            status.textContent = 'Not tracking';
            status.style.color = 'var(--text-light)';
        }
    </script>
    <?php endif; ?>
</body>
</html>
