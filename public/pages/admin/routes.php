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
        $description = trim($_POST['description'] ?? '');
        
        try {
            OrangeRoute\Database::query(
                "INSERT INTO routes (route_name, description) VALUES (?, ?)",
                [$name, $description]
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

// Get all routes with stop count
$routes = OrangeRoute\Database::fetchAll("
    SELECT r.*, COUNT(rs.id) as stop_count
    FROM routes r
    LEFT JOIN route_stops rs ON r.id = rs.route_id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Manage Routes - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
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
    <div class="top-bar">
        <a href="../admin.php" style="text-decoration: none;">← Back</a>
        <div class="logo">Routes</div>
        <div></div>
    </div>
    
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
                    <input type="text" name="route_name" required placeholder="Main Campus Loop">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Covers main academic buildings..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Route</button>
            </form>
        </div>
        
        <h2>All Routes (<?= count($routes) ?>)</h2>
        
        <?php foreach ($routes as $r): ?>
        <div class="route-card" style="border-left-color: var(--primary);">
            <div class="route-header">
                <div>
                    <strong style="font-size: 18px;"><?= e($r['route_name']) ?></strong>
                    <?php if (!$r['is_active']): ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($r['description']): ?>
            <p style="font-size: 14px; margin: 8px 0;"><?= e($r['description']) ?></p>
            <?php endif; ?>
            <div class="text-muted" style="font-size: 14px;">
                <?= $r['stop_count'] ?> stops defined
            </div>
            
            <div class="route-actions">
                <a href="route_stops.php?route_id=<?= $r['id'] ?>" class="btn btn-sm" style="background: #2196F3; color: white;">
                    Manage Stops
                </a>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="route_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: <?= $r['is_active'] ? '#f44336' : '#4CAF50' ?>; color: white;">
                        <?= $r['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this route?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="route_id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: #757575; color: white;">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
