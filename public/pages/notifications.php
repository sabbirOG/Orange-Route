<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();

// Generate activity-based notifications (simple implementation)
$notifications = [];

// For drivers: recent assignments
if ($user['role'] === 'driver') {
    $assignments = OrangeRoute\Database::fetchAll(
        "SELECT sa.assigned_at, s.shuttle_name, r.route_name
         FROM shuttle_assignments sa
         JOIN shuttles s ON sa.shuttle_id = s.id
         JOIN routes r ON sa.route_id = r.id
         WHERE sa.driver_id = ? AND sa.is_current = 1
         ORDER BY sa.assigned_at DESC LIMIT 5",
        [$user['id']]
    );
    foreach ($assignments as $a) {
        $notifications[] = [
            'type' => 'assignment',
            'message' => "You were assigned to {$a['shuttle_name']} on {$a['route_name']}",
            'time' => $a['assigned_at'],
            'icon' => 'shuttle'
        ];
    }
}

// For students: active shuttles count
if ($user['role'] === 'student') {
    $activeCount = OrangeRoute\Database::fetchValue(
        "SELECT COUNT(*) FROM shuttles WHERE is_active = 1"
    );
    if ($activeCount > 0) {
        $notifications[] = [
            'type' => 'info',
            'message' => "{$activeCount} shuttle" . ($activeCount > 1 ? 's are' : ' is') . " currently active",
            'time' => date('Y-m-d H:i:s'),
            'icon' => 'info'
        ];
    }
}

// For admins: recent activity
if ($user['role'] === 'admin') {
    $recentUsers = OrangeRoute\Database::fetchValue(
        "SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    if ($recentUsers > 0) {
        $notifications[] = [
            'type' => 'admin',
            'message' => "{$recentUsers} new user" . ($recentUsers > 1 ? 's' : '') . " joined this week",
            'time' => date('Y-m-d H:i:s'),
            'icon' => 'user'
        ];
    }
}

// Welcome message for all
if (empty($notifications)) {
    $notifications[] = [
        'type' => 'welcome',
        'message' => 'Welcome to OrangeRoute! You\'ll receive updates here.',
        'time' => date('Y-m-d H:i:s'),
        'icon' => 'info'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Notifications - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
    <style>
        .notification-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
            border-left: 4px solid;
        }
        .notification-card.assignment {
            border-left-color: #4CAF50;
        }
        .notification-card.info {
            border-left-color: #2196F3;
        }
        .notification-card.admin {
            border-left-color: var(--primary);
        }
        .notification-card.welcome {
            border-left-color: #9C27B0;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--bg);
        }
        .notification-content {
            flex: 1;
        }
        .notification-message {
            font-size: 15px;
            line-height: 1.4;
            margin-bottom: 4px;
        }
        .notification-time {
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <?php $title = 'Notifications'; $backHref = 'map.php'; include __DIR__ . '/_partials/top_bar.php'; ?>

    <div class="container">
        <h2>Notifications</h2>
        
        <?php foreach ($notifications as $notif): ?>
        <div class="notification-card <?= $notif['type'] ?>">
            <div class="notification-icon">
                <?php if ($notif['icon'] === 'shuttle'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="8" width="18" height="12" rx="2"></rect>
                        <path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path>
                        <circle cx="8" cy="16" r="1"></circle>
                        <circle cx="16" cy="16" r="1"></circle>
                    </svg>
                <?php elseif ($notif['icon'] === 'user'): ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                <?php else: ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="notification-content">
                <div class="notification-message"><?= e($notif['message']) ?></div>
                <div class="notification-time"><?= date('M d, Y H:i', strtotime($notif['time'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php $active = 'notifications'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
</body>
</html>
