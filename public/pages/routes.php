<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();

// Get all active routes with stop count
$routes = OrangeRoute\Database::fetchAll("
    SELECT r.*, COUNT(rs.id) as stop_count
    FROM routes r
    LEFT JOIN route_stops rs ON r.id = rs.route_id
    WHERE r.is_active = 1
    GROUP BY r.id
    ORDER BY r.route_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Routes - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <style>
        .route-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .route-card:active {
            transform: scale(0.98);
        }
        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .route-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
        .stop-badge {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        .route-description {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.4;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <?php $title = 'Routes'; include __DIR__ . '/_partials/top_bar.php'; ?>

    <div class="container">
        <h2>Available Routes</h2>
        
        <?php if (empty($routes)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                    <circle cx="12" cy="11" r="2"></circle>
                </svg>
                <p class="text-muted">No routes available at the moment</p>
            </div>
        <?php else: ?>
            <?php foreach ($routes as $route): ?>
            <div class="route-card" onclick="toggleDetails(<?= $route['id'] ?>)">
                <div class="route-header">
                    <div class="route-name"><?= e($route['route_name']) ?></div>
                    <div class="stop-badge"><?= $route['stop_count'] ?> stops</div>
                </div>
                <?php if ($route['description']): ?>
                <div class="route-description"><?= e($route['description']) ?></div>
                <?php endif; ?>
                
                <div id="stops-<?= $route['id'] ?>" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                    <?php
                    $stops = OrangeRoute\Database::fetchAll(
                        "SELECT * FROM route_stops WHERE route_id = ? ORDER BY stop_order ASC",
                        [$route['id']]
                    );
                    ?>
                    <?php if (!empty($stops)): ?>
                        <div style="font-weight: 600; margin-bottom: 12px; font-size: 14px;">Route Stops:</div>
                        <?php foreach ($stops as $stop): ?>
                        <div style="display: flex; align-items: center; margin-bottom: 8px; font-size: 14px;">
                            <div style="background: var(--primary); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; margin-right: 12px; flex-shrink: 0;">
                                <?= $stop['stop_order'] ?>
                            </div>
                            <div style="flex: 1;">
                                <div><?= e($stop['stop_name']) ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?= (int)$stop['estimated_time'] ?> min from start
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted" style="font-size: 14px;">No stops defined yet</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php $active = 'routes'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script>
        function toggleDetails(routeId) {
            const el = document.getElementById('stops-' + routeId);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
