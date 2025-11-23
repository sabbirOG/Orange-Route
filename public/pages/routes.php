<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();


// All routes (active and inactive)
$all_routes = OrangeRoute\Database::fetchAll("SELECT id, route_name, from_location, to_location, distance_type, description, is_active FROM routes ORDER BY distance_type DESC, route_name ASC");

// Only currently operating routes (driver tracking in last 5 min)
$active_routes = OrangeRoute\Database::fetchAll("
    SELECT 
        r.id,
        r.route_name,
        r.from_location,
        r.to_location,
        r.distance_type,
        r.description,
        r.is_active
    FROM routes r
    INNER JOIN route_assignments ra ON r.id = ra.route_id AND ra.is_current = 1
    INNER JOIN users u ON ra.driver_id = u.id
    INNER JOIN (
        SELECT route_id,
        ROW_NUMBER() OVER (PARTITION BY route_id ORDER BY created_at DESC) as rn
        FROM route_locations
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ) rl ON r.id = rl.route_id AND rl.rn = 1
    WHERE r.is_active = 1
    ORDER BY r.distance_type DESC, r.route_name ASC
");

function split_routes_by_type($routes) {
    $long = [];
    $short = [];
    foreach ($routes as &$route) {
        if (empty($route['from_location'])) $route['from_location'] = 'UIU';
        if (empty($route['to_location'])) $route['to_location'] = 'Unknown';
        if (empty($route['distance_type'])) $route['distance_type'] = 'short';
        if ($route['distance_type'] === 'long') {
            $long[] = $route;
        } else {
            $short[] = $route;
        }
    }
    return [$long, $short];
}

list($long_routes, $short_routes) = split_routes_by_type($all_routes);
list($active_long_routes, $active_short_routes) = split_routes_by_type($active_routes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Routes - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
    <style>
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }
        .filter-tab {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: var(--text-light);
        }
        .filter-tab.active {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%);
            color: white;
        }
        .filter-tab:not(.active):active {
            transform: scale(0.98);
        }
        .route-section {
            display: block;
        }
        .route-section.hidden {
            display: none;
        }
        .route-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary);
            transition: transform 0.2s, box-shadow 0.2s;
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
    <?php $title = 'Routes'; $backHref = 'map.php'; include __DIR__ . '/_partials/top_bar.php'; ?>

    <div class="container">
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <input id="route-search" type="text" placeholder="Search routes by name, location, or description..." style="flex:1;padding:10px;border-radius:8px;border:1px solid #ccc;font-size:15px;">
            <button id="route-search-btn" class="btn btn-primary" style="padding:10px 18px;border-radius:8px;font-size:15px;">Search</button>
        </div>
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterRoutes('short')">Short Routes</button>
            <button class="filter-tab" onclick="filterRoutes('active')">Active Now</button>
            <button class="filter-tab" onclick="filterRoutes('long')">Long Routes</button>
        </div>
        

        <div class="route-section hidden" data-type="short">
            <?php if (empty($short_routes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3>No Short Routes</h3>
                    <p class="text-muted">No short routes found</p>
                </div>
            <?php else: ?>
                <h2>Short Routes (<?= count($short_routes) ?>)</h2>
                <?php foreach ($short_routes as $route): ?>
                <div class="route-card" style="border-left-color: #4CAF50;"
                    data-name="<?= strtolower(e($route['route_name'])) ?>"
                    data-from="<?= strtolower(e($route['from_location'])) ?>"
                    data-to="<?= strtolower(e($route['to_location'])) ?>"
                    data-desc="<?= strtolower(e($route['description'] ?? '')) ?>">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span style="display: inline-flex; gap: 8px; align-items: center;">
                            <span class="badge badge-success">Short</span>
                            <?php if ($route['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="route-section" data-type="active">
            <?php if (empty($active_routes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3>No Active Routes</h3>
                    <p class="text-muted">No routes are currently operating</p>
                </div>
            <?php else: ?>
                <?php if (!empty($active_long_routes)): ?>
                <h2>Long Routes (<?= count($active_long_routes) ?>)</h2>
                <?php foreach ($active_long_routes as $route): ?>
                <div class="route-card" style="border-left-color: #2196F3;"
                    data-name="<?= strtolower(e($route['route_name'])) ?>"
                    data-from="<?= strtolower(e($route['from_location'])) ?>"
                    data-to="<?= strtolower(e($route['to_location'])) ?>"
                    data-desc="<?= strtolower(e($route['description'] ?? '')) ?>">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-primary">Long</span>
                        <span class="badge badge-success" style="margin-left:8px;">Active</span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($active_short_routes)): ?>
                <h2 style="margin-top: 24px;">Short Routes (<?= count($active_short_routes) ?>)</h2>
                <?php foreach ($active_short_routes as $route): ?>
                <div class="route-card" style="border-left-color: #4CAF50;"
                    data-name="<?= strtolower(e($route['route_name'])) ?>"
                    data-from="<?= strtolower(e($route['from_location'])) ?>"
                    data-to="<?= strtolower(e($route['to_location'])) ?>"
                    data-desc="<?= strtolower(e($route['description'] ?? '')) ?>">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-success">Short</span>
                        <span class="badge badge-success" style="margin-left:8px;">Active</span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="route-section hidden" data-type="long">
            <?php if (empty($long_routes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3>No Long Routes</h3>
                    <p class="text-muted">No long routes found</p>
                </div>
            <?php else: ?>
                <h2>Long Routes (<?= count($long_routes) ?>)</h2>
                <?php foreach ($long_routes as $route): ?>
                <div class="route-card" style="border-left-color: #2196F3;"
                    data-name="<?= strtolower(e($route['route_name'])) ?>"
                    data-from="<?= strtolower(e($route['from_location'])) ?>"
                    data-to="<?= strtolower(e($route['to_location'])) ?>"
                    data-desc="<?= strtolower(e($route['description'] ?? '')) ?>">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span style="display: inline-flex; gap: 8px; align-items: center;">
                            <span class="badge badge-primary">Long</span>
                            <?php if ($route['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="route-section hidden" data-type="short">
            <?php if (empty($short_routes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3>No Short Routes</h3>
                    <p class="text-muted">No short routes found</p>
                </div>
            <?php else: ?>
                <h2>Short Routes (<?= count($short_routes) ?>)</h2>
                <?php foreach ($short_routes as $route): ?>
                <div class="route-card" style="border-left-color: #4CAF50;">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-success">Short</span>
                        <?php if ($route['is_active']): ?><span class="badge badge-success" style="margin-left:8px;">Active</span><?php else: ?><span class="badge badge-danger" style="margin-left:8px;">Inactive</span><?php endif; ?>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="route-section hidden" data-type="active">
            <?php if (empty($active_routes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 21s-6-5.5-6-10a6 6 0 1 1 12 0c0 4.5-6 10-6 10z"></path>
                        <circle cx="12" cy="11" r="2"></circle>
                    </svg>
                    <h3>No Active Routes</h3>
                    <p class="text-muted">No routes are currently operating</p>
                </div>
            <?php else: ?>
                <?php if (!empty($active_long_routes)): ?>
                <h2>Long Routes (<?= count($active_long_routes) ?>)</h2>
                <?php foreach ($active_long_routes as $route): ?>
                <div class="route-card" style="border-left-color: #2196F3;">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-primary">Long</span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($active_short_routes)): ?>
                <h2 style="margin-top: 24px;">Short Routes (<?= count($active_short_routes) ?>)</h2>
                <?php foreach ($active_short_routes as $route): ?>
                <div class="route-card" style="border-left-color: #4CAF50;">
                    <div class="route-header">
                        <div>
                            <div class="route-name"><?= e($route['route_name']) ?></div>
                            <div style="font-size: 14px; color: var(--text); margin-top: 4px;">
                                <strong><?= e($route['from_location']) ?> → <?= e($route['to_location']) ?></strong>
                            </div>
                        </div>
                        <span class="badge badge-success">Short</span>
                    </div>
                    <?php if ($route['description']): ?>
                    <div class="route-description"><?= e($route['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php $active = 'routes'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script>
        function filterRoutes(type) {
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            const sections = document.querySelectorAll('.route-section');
            sections.forEach(section => {
                section.classList.add('hidden');
            });
            if (type === 'active') {
                document.querySelector('.route-section[data-type="active"]').classList.remove('hidden');
            } else if (type === 'long') {
                document.querySelector('.route-section[data-type="long"]').classList.remove('hidden');
            } else if (type === 'short') {
                document.querySelector('.route-section[data-type="short"]').classList.remove('hidden');
            }
            filterRouteCards();
        }

        // Search button triggers filtering
        const searchInput = document.getElementById('route-search');
        const searchBtn = document.getElementById('route-search-btn');
        searchBtn.addEventListener('click', filterRouteCards);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterRouteCards();
            }
        });

        function filterRouteCards() {
            const q = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('.route-section:not(.hidden) .route-card').forEach(card => {
                const name = card.dataset.name || '';
                const from = card.dataset.from || '';
                const to = card.dataset.to || '';
                const desc = card.dataset.desc || '';
                if (!q || name.includes(q) || from.includes(q) || to.includes(q) || desc.includes(q)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
