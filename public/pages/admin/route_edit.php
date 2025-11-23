<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
if ($user['role'] !== 'admin') {
    redirect('pages/map.php');
}

$route_id = $_GET['route_id'] ?? 0;
$success = null;
$error = null;

// Get route details
$route = OrangeRoute\Database::fetch(
    "SELECT * FROM routes WHERE id = ?",
    [$route_id]
);

if (!$route) {
    redirect('pages/admin/routes.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $route_name = trim($_POST['route_name'] ?? '');
        $from_location = trim($_POST['from_location'] ?? 'UIU');
        $to_location = trim($_POST['to_location'] ?? '');
        $distance_type = $_POST['distance_type'] ?? 'short';
        $description = trim($_POST['description'] ?? '');
        
        try {
            OrangeRoute\Database::query(
                "UPDATE routes SET route_name = ?, from_location = ?, to_location = ?, distance_type = ?, description = ? WHERE id = ?",
                [$route_name, $from_location, $to_location, $distance_type, $description, $route_id]
            );
            $success = 'Route updated successfully';
            
            // Refresh route data
            $route = OrangeRoute\Database::fetch(
                "SELECT * FROM routes WHERE id = ?",
                [$route_id]
            );
        } catch (Exception $e) {
            $error = 'Error updating route';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Edit Route - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title='Edit Route'; $backHref='routes.php'; include __DIR__ . '/../_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Edit Route Details</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                
                <div class="form-group">
                    <label>Route Name</label>
                    <input type="text" name="route_name" value="<?= e($route['route_name']) ?>" required placeholder="UIU to Kuril 1">
                </div>
                
                <div class="form-group">
                    <label>Starting Point</label>
                    <input type="text" name="from_location" value="<?= e($route['from_location'] ?: 'UIU') ?>" required placeholder="UIU">
                </div>
                
                <div class="form-group">
                    <label>Destination</label>
                    <input type="text" name="to_location" value="<?= e($route['to_location']) ?>" required placeholder="Kuril / Aftabnagar / Natunbazar">
                </div>
                
                <div class="form-group">
                    <label>Route Type</label>
                    <select name="distance_type" required class="form-control" style="height: 44px; padding: 0 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 16px;">
                        <option value="short" <?= $route['distance_type'] === 'short' ? 'selected' : '' ?>>Short Route</option>
                        <option value="long" <?= $route['distance_type'] === 'long' ? 'selected' : '' ?>>Long Route</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="3" placeholder="Additional route information..."><?= e($route['description']) ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Route</button>
            </form>
        </div>
        
        <div class="card" style="background: #f5f5f5; margin-top: 20px;">
            <h4 style="margin-bottom: 8px;">Route Information</h4>
            <p style="font-size: 14px; margin: 4px 0;"><strong>Route ID:</strong> <?= $route['id'] ?></p>
            <p style="font-size: 14px; margin: 4px 0;"><strong>Created:</strong> <?= date('M d, Y g:i A', strtotime($route['created_at'])) ?></p>
            <p style="font-size: 14px; margin: 4px 0;"><strong>Status:</strong> 
                <span class="badge badge-<?= $route['is_active'] ? 'success' : 'danger' ?>">
                    <?= $route['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </p>
        </div>
    </div>
</body>
</html>
