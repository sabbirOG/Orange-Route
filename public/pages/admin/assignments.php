<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
if ($user['role'] !== 'admin') {
    redirect('pages/map.php');
}

$success = null;
$error = null;

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign') {
        $driverId = (int)($_POST['driver_id'] ?? 0);
        $routeId = (int)($_POST['route_id'] ?? 0);
        
        try {
            // Unassign current driver from this route
            OrangeRoute\Database::query("UPDATE route_assignments SET is_current = 0 WHERE route_id = ?", [$routeId]);
            
            // Assign new driver to selected route
            OrangeRoute\Database::query(
                "INSERT INTO route_assignments (driver_id, route_id, is_current) VALUES (?, ?, 1)",
                [$driverId, $routeId]
            );
            $success = 'Driver assigned successfully';
        } catch (Exception $e) {
            $error = 'Error assigning driver: ' . $e->getMessage();
        }
    }
    
    if ($action === 'unassign') {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        OrangeRoute\Database::query("UPDATE route_assignments SET is_current = 0 WHERE id = ?", [$assignmentId]);
        $success = 'Driver unassigned';
    }
}

// Get current assignments
$assignments = OrangeRoute\Database::fetchAll("
    SELECT ra.id, ra.assigned_at, r.route_name, r.distance_type as category,
           u.email as driver_email, u.username as driver_name, u.student_id as driver_id
    FROM route_assignments ra
    JOIN routes r ON ra.route_id = r.id
    JOIN users u ON ra.driver_id = u.id
    WHERE ra.is_current = 1
    ORDER BY ra.assigned_at DESC
");

// Get available drivers
$drivers = OrangeRoute\Database::fetchAll("
    SELECT u.id, u.email, u.username
    FROM users u
    WHERE u.role = 'driver' AND u.is_active = 1
    ORDER BY u.email
");

// Get available routes
$routes = OrangeRoute\Database::fetchAll("
    SELECT id, route_name, distance_type as category FROM routes WHERE is_active = 1 ORDER BY distance_type, route_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Manage Assignments - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
    <style>
        .assignment-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<?php $title='Assignments'; $backHref='../admin.php'; include __DIR__ . '/../_partials/top_bar.php'; ?>
    <!-- Legacy inline top bar removed; using shared partial -->
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Assign Driver to Route</h3>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <div class="form-group">
                    <label>Select Route</label>
                    <select name="route_id" required>
                        <option value="">Choose route...</option>
                        <?php 
                        $currentCategory = null;
                        foreach ($routes as $route): 
                            if ($currentCategory !== $route['category']):
                                if ($currentCategory !== null) echo '</optgroup>';
                                $categoryLabel = $route['category'] === 'long' ? 'Long Route' : 'Short Route';
                                echo '<optgroup label="' . $categoryLabel . '">';
                                $currentCategory = $route['category'];
                            endif;
                        ?>
                        <option value="<?= $route['id'] ?>">
                            <?= e($route['route_name']) ?>
                        </option>
                        <?php 
                        endforeach; 
                        if ($currentCategory !== null) echo '</optgroup>';
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Driver</label>
                    <select name="driver_id" required>
                        <option value="">Choose driver...</option>
                        <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['id'] ?>">
                            <?= $driver['username'] ? e($driver['username']) : 'Driver' ?>
                            <?= $driver['email'] ? ' (' . e($driver['email']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Assign Driver</button>
            </form>
        </div>
        
        <h2>Current Assignments (<?= count($assignments) ?>)</h2>
        
        <?php if (empty($assignments)): ?>
            <div class="alert" style="background: #f5f5f5;">No active assignments. Assign a driver above!</div>
        <?php endif; ?>
        
        <?php foreach ($assignments as $assignment): ?>
        <div class="assignment-card">
            <div style="margin-bottom: 12px;">
                <strong style="font-size: 16px;"><?= e($assignment['route_name']) ?></strong>
                <span class="badge badge-<?= $assignment['category'] === 'long' ? 'primary' : 'success' ?>">
                    <?= $assignment['category'] === 'long' ? 'Long Route' : 'Short Route' ?>
                </span>
            </div>
            <div class="text-muted" style="font-size: 14px; margin-bottom: 12px;">
                Driver: <?= $assignment['driver_name'] ? e($assignment['driver_name']) : 'Driver' ?>
                <?= $assignment['driver_id'] ? ' (ID: ' . e($assignment['driver_id']) . ')' : '' ?><br>
                Assigned: <?= date('M d, Y H:i', strtotime($assignment['assigned_at'])) ?>
            </div>
            <form method="POST" onsubmit="return confirm('Unassign this driver?');">
                <input type="hidden" name="action" value="unassign">
                <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                <button type="submit" class="btn btn-sm" style="background: #f44336; color: white;">Unassign Driver</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
