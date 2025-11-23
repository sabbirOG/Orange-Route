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
    
    if ($action === 'add') {
        $name = trim($_POST['route_name'] ?? '');
        $from_location = trim($_POST['from_location'] ?? 'UIU');
        $to_location = trim($_POST['to_location'] ?? '');
        $distance_type = $_POST['distance_type'] ?? 'short';
        $description = trim($_POST['description'] ?? '');
        
        try {
            OrangeRoute\Database::query(
                "INSERT INTO routes (route_name, from_location, to_location, distance_type, description) VALUES (?, ?, ?, ?, ?)",
                [$name, $from_location, $to_location, $distance_type, $description]
            );
            $success = 'Route added successfully';
        } catch (Exception $e) {
            $error = 'Error adding route';
        }
    }
    
    if ($action === 'toggle_active') {
        $id = $_POST['route_id'] ?? 0;
        $current = OrangeRoute\Database::fetchValue("SELECT is_active FROM routes WHERE id = ?", [$id]);
        OrangeRoute\Database::query("UPDATE routes SET is_active = ? WHERE id = ?", [!$current, $id]);
        $success = 'Route status updated';
    }
    
    if ($action === 'delete') {
        $id = $_POST['route_id'] ?? 0;
        OrangeRoute\Database::query("DELETE FROM routes WHERE id = ?", [$id]);
        $success = 'Route deleted';
    }
}

// Get all routes
$routes = OrangeRoute\Database::fetchAll("
    SELECT r.*
    FROM routes r
    ORDER BY r.distance_type DESC, r.created_at DESC
");

// Add route info display and separate by type
$long_routes = [];
$short_routes = [];

foreach ($routes as &$route) {
    if (empty($route['from_location'])) $route['from_location'] = 'UIU';
    if (empty($route['to_location'])) $route['to_location'] = 'Unknown';
    if (empty($route['distance_type'])) $route['distance_type'] = 'short';
    
    if ($route['distance_type'] === 'long') {
        $long_routes[] = $route;
    } else {
        $short_routes[] = $route;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Manage Routes - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
    <style>
        .route-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border-left: 4px solid;
        }
        .route-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .route-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        .color-picker {
            height: 44px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }
    </style>
</head>
<body>
<?php $title='Routes'; $backHref='../admin.php'; include __DIR__ . '/../_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Add New Route</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Route Name</label>
                    <input type="text" name="route_name" required placeholder="UIU to Kuril 1">
                </div>
                <div class="form-group">
                    <label>From Location</label>
                    <input type="text" name="from_location" value="UIU" required placeholder="UIU">
                </div>
                <div class="form-group">
                    <label>To Location (Destination)</label>
                    <input type="text" name="to_location" required placeholder="Kuril / Aftabnagar / Natunbazar">
                </div>
                <div class="form-group">
                    <label>Route Type</label>
                    <select name="distance_type" required class="form-control" style="height: 44px; padding: 0 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 16px;">
                        <option value="short">Short Route</option>
                        <option value="long">Long Route</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Covers main academic buildings..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Route</button>
            </form>
        </div>
        
        <h2>Long Routes (<?= count($long_routes) ?>)</h2>
        
        <?php if (count($long_routes) > 0): ?>
            <?php foreach ($long_routes as $r): ?>
            <div class="route-card" style="border-left-color: #2196F3;">
                <div class="route-header">
                    <div>
                        <strong style="font-size: 18px;"><?= e($r['route_name']) ?></strong>
                        <span class="badge badge-primary" style="margin-left: 8px;">
                            Long Route
                        </span>
                        <?php if (!$r['is_active']): ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p style="font-size: 14px; margin: 8px 0; color: var(--text);">
                    <strong><?= e($r['from_location']) ?> → <?= e($r['to_location']) ?></strong>
                </p>
                <?php if ($r['description']): ?>
                <p style="font-size: 14px; margin: 8px 0; color: var(--text-light);"><?= e($r['description']) ?></p>
                <?php endif; ?>
                
                <div class="route-actions">
                    <a href="route_edit.php?route_id=<?= $r['id'] ?>" class="btn btn-sm" style="background: #2196F3; color: white;">
                        Edit Route
                    </a>
                    <button onclick="toggleRouteStatus(<?= $r['id'] ?>, <?= $r['is_active'] ? 1 : 0 ?>)" class="btn btn-sm toggle-route-btn" data-route-id="<?= $r['id'] ?>" style="background: <?= $r['is_active'] ? '#FF9800' : '#4CAF50' ?>; color: white;">
                        <?= $r['is_active'] ? 'Stop Route' : 'Start Route' ?>
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this route?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="route_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background: #757575; color: white;">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 20px;">
                <p class="text-muted">No long routes yet</p>
            </div>
        <?php endif; ?>
        
        <h2 style="margin-top: 24px;">Short Routes (<?= count($short_routes) ?>)</h2>
        
        <?php if (count($short_routes) > 0): ?>
            <?php foreach ($short_routes as $r): ?>
            <div class="route-card" style="border-left-color: #4CAF50;">
                <div class="route-header">
                    <div>
                        <strong style="font-size: 18px;"><?= e($r['route_name']) ?></strong>
                        <span class="badge badge-success" style="margin-left: 8px;">
                            Short Route
                        </span>
                        <?php if (!$r['is_active']): ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p style="font-size: 14px; margin: 8px 0; color: var(--text);">
                    <strong><?= e($r['from_location']) ?> → <?= e($r['to_location']) ?></strong>
                </p>
                <?php if ($r['description']): ?>
                <p style="font-size: 14px; margin: 8px 0; color: var(--text-light);"><?= e($r['description']) ?></p>
                <?php endif; ?>
                
                <div class="route-actions">
                    <a href="route_edit.php?route_id=<?= $r['id'] ?>" class="btn btn-sm" style="background: #2196F3; color: white;">
                        Edit Route
                    </a>
                    <button onclick="toggleRouteStatus(<?= $r['id'] ?>, <?= $r['is_active'] ? 1 : 0 ?>)" class="btn btn-sm toggle-route-btn" data-route-id="<?= $r['id'] ?>" style="background: <?= $r['is_active'] ? '#FF9800' : '#4CAF50' ?>; color: white;">
                        <?= $r['is_active'] ? 'Stop Route' : 'Start Route' ?>
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this route?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="route_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background: #757575; color: white;">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 20px;">
                <p class="text-muted">No short routes yet</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    async function toggleRouteStatus(routeId, currentStatus) {
        const btn = document.querySelector(`button[data-route-id="${routeId}"]`);
        const newStatus = currentStatus ? 0 : 1;
        
        try {
            const resp = await fetch('../../api/toggle_route_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'route_id=' + routeId + '&active=' + newStatus
            });
            const result = await resp.json();
            
            if (result.success) {
                // Update button appearance
                if (newStatus) {
                    btn.textContent = 'Stop Route';
                    btn.style.background = '#FF9800';
                } else {
                    btn.textContent = 'Start Route';
                    btn.style.background = '#4CAF50';
                }
                btn.setAttribute('onclick', `toggleRouteStatus(${routeId}, ${newStatus})`);
            } else {
                alert('Failed to toggle route');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error toggling route status');
        }
    }
    </script>
</body>
</html>
