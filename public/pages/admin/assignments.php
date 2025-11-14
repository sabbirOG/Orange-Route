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
        $shuttleId = (int)($_POST['shuttle_id'] ?? 0);
        $driverId = (int)($_POST['driver_id'] ?? 0);
        $routeId = (int)($_POST['route_id'] ?? 0);
        
        try {
            // Unassign current driver from this shuttle
            OrangeRoute\Database::query("UPDATE shuttle_assignments SET is_current = 0 WHERE shuttle_id = ?", [$shuttleId]);
            
            // Assign new driver to selected route
            OrangeRoute\Database::query(
                "INSERT INTO shuttle_assignments (shuttle_id, driver_id, route_id) VALUES (?, ?, ?)",
                [$shuttleId, $driverId, $routeId]
            );
            $success = 'Driver assigned successfully';
        } catch (Exception $e) {
            $error = 'Error assigning driver';
        }
    }
    
    if ($action === 'unassign') {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        OrangeRoute\Database::query("UPDATE shuttle_assignments SET is_current = 0 WHERE id = ?", [$assignmentId]);
        $success = 'Driver unassigned';
    }
}

// Get current assignments
$assignments = OrangeRoute\Database::fetchAll("
    SELECT sa.id, sa.assigned_at, s.shuttle_name, s.registration_number, 
           u.email as driver_email, u.username as driver_name
    FROM shuttle_assignments sa
    JOIN shuttles s ON sa.shuttle_id = s.id
    JOIN users u ON sa.driver_id = u.id
    WHERE sa.is_current = 1
    ORDER BY sa.assigned_at DESC
");

// Get available drivers (not currently assigned)
$drivers = OrangeRoute\Database::fetchAll("
    SELECT u.id, u.email, u.username
    FROM users u
    WHERE u.role = 'driver' AND u.is_active = 1
");

// Get available shuttles
$shuttles = OrangeRoute\Database::fetchAll("
    SELECT id, shuttle_name, registration_number FROM shuttles WHERE is_active = 1
");

// Get available routes
$routes = OrangeRoute\Database::fetchAll("
    SELECT id, route_name FROM routes WHERE is_active = 1
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
            <h3>Assign Driver to Shuttle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <div class="form-group">
                    <label>Select Shuttle</label>
                    <select name="shuttle_id" required>
                        <option value="">Choose shuttle...</option>
                        <?php foreach ($shuttles as $shuttle): ?>
                        <option value="<?= $shuttle['id'] ?>">
                            <?= e($shuttle['shuttle_name']) ?> (<?= e($shuttle['registration_number']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Route</label>
                    <select name="route_id" required>
                        <option value="">Choose route...</option>
                        <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>">
                            <?= e($route['route_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Driver</label>
                    <select name="driver_id" required>
                        <option value="">Choose driver...</option>
                        <?php foreach ($drivers as $driver): ?>
                        <option value="<?= $driver['id'] ?>">
                            <?= e($driver['email']) ?> <?= $driver['username'] ? '(' . e($driver['username']) . ')' : '' ?>
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
                <strong style="font-size: 16px;"><?= e($assignment['shuttle_name']) ?></strong>
                <span class="badge badge-primary"><?= e($assignment['registration_number']) ?></span>
            </div>
            <div class="text-muted" style="font-size: 14px; margin-bottom: 12px;">
                Driver: <?= e($assignment['driver_email']) ?><br>
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
