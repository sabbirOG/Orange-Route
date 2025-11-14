<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
if ($user['role'] !== 'admin') {
    redirect('pages/map.php');
}

$routeId = $_GET['route_id'] ?? 0;
$route = OrangeRoute\Database::fetch("SELECT * FROM routes WHERE id = ?", [$routeId]);

if (!$route) {
    redirect('pages/admin/routes.php');
}

$success = null;
$error = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stopName = trim($_POST['stop_name'] ?? '');
        $lat = (float)($_POST['latitude'] ?? 0);
        $lng = (float)($_POST['longitude'] ?? 0);
        $order = (int)($_POST['stop_order'] ?? 1);
        $time = (int)($_POST['estimated_time'] ?? 0);
        
        try {
            OrangeRoute\Database::query(
                "INSERT INTO route_stops (route_id, stop_name, latitude, longitude, stop_order, estimated_time) VALUES (?, ?, ?, ?, ?, ?)",
                [$routeId, $stopName, $lat, $lng, $order, $time]
            );
            $success = 'Stop added successfully';
        } catch (Exception $e) {
            $error = 'Error adding stop';
        }
    }
    
    if ($action === 'delete') {
        $stopId = $_POST['stop_id'] ?? 0;
        OrangeRoute\Database::query("DELETE FROM route_stops WHERE id = ?", [$stopId]);
        $success = 'Stop deleted';
    }
}

// Get all stops for this route
$stops = OrangeRoute\Database::fetchAll("
    SELECT * FROM route_stops
    WHERE route_id = ?
    ORDER BY stop_order ASC
", [$routeId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Route Stops - <?= e($route['route_name']) ?></title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <style>
        .stop-card {
            background: white;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 8px;
            border-left: 3px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stop-number {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php $title='Route Stops'; $backHref='routes.php'; include __DIR__ . '/../_partials/top_bar.php'; ?>
    <!-- Legacy inline top bar removed; using shared partial -->
    
    <div class="container">
        <h2><?= e($route['route_name']) ?></h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Add Stop</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Stop Name</label>
                    <input type="text" name="stop_name" required placeholder="Main Gate">
                </div>
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" name="latitude" step="0.00000001" required placeholder="23.7937">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" name="longitude" step="0.00000001" required placeholder="90.4066">
                </div>
                <div class="form-group">
                    <label>Stop Order</label>
                    <input type="number" name="stop_order" value="<?= count($stops) + 1 ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Est. Time from Start (minutes)</label>
                    <input type="number" name="estimated_time" value="0" min="0">
                </div>
                <button type="submit" class="btn btn-primary">Add Stop</button>
            </form>
        </div>
        
        <h3>Route Stops (<?= count($stops) ?>)</h3>
        
        <?php if (empty($stops)): ?>
            <div class="alert" style="background: #f5f5f5;">No stops defined yet. Add your first stop above!</div>
        <?php endif; ?>
        
        <?php foreach ($stops as $stop): ?>
        <div class="stop-card" style="border-left: 3px solid var(--primary);">
            <div style="display: flex; align-items: center; flex: 1;">
                <div class="stop-number" style="background: var(--primary);">
                    <?= $stop['stop_order'] ?>
                </div>
                <div>
                    <strong><?= e($stop['stop_name']) ?></strong><br>
                    <span class="text-muted" style="font-size: 12px;">
                        <?= number_format($stop['latitude'], 6) ?>, <?= number_format($stop['longitude'], 6) ?>
                        • <?= (int)$stop['estimated_time'] ?> min
                    </span>
                </div>
            </div>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this stop?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="stop_id" value="<?= $stop['id'] ?>">
                <button type="submit" class="btn btn-sm" style="background: #f44336; color: white;">Delete</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
