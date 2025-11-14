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
        $name = $_POST['shuttle_name'] ?? '';
        $reg = $_POST['registration_number'] ?? '';
        $capacity = (int)($_POST['capacity'] ?? 30);
        
        try {
            OrangeRoute\Database::query(
                "INSERT INTO shuttles (shuttle_name, registration_number, capacity) VALUES (?, ?, ?)",
                [$name, $reg, $capacity]
            );
            $success = 'Shuttle added successfully';
        } catch (Exception $e) {
            $error = 'Error: Registration number already exists';
        }
    }
    
    if ($action === 'toggle_active') {
        $id = $_POST['shuttle_id'] ?? 0;
        $current = OrangeRoute\Database::fetchValue("SELECT is_active FROM shuttles WHERE id = ?", [$id]);
        OrangeRoute\Database::query("UPDATE shuttles SET is_active = ? WHERE id = ?", [!$current, $id]);
        $success = 'Shuttle status updated';
    }
    
    if ($action === 'delete') {
        $id = $_POST['shuttle_id'] ?? 0;
        OrangeRoute\Database::query("DELETE FROM shuttles WHERE id = ?", [$id]);
        $success = 'Shuttle deleted';
    }
}

// Get all shuttles with route info
$shuttles = OrangeRoute\Database::fetchAll("
    SELECT *
    FROM shuttles
    ORDER BY created_at DESC
");
    


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Manage Shuttles - Admin</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <style>
        .shuttle-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
        }
        .shuttle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .shuttle-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <a href="../admin.php" style="text-decoration: none;">← Back</a>
        <div class="logo">Shuttles</div>
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
            <h3>Add New Shuttle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Shuttle Name</label>
                    <input type="text" name="shuttle_name" required placeholder="Orange Express 1">
                </div>
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_number" required placeholder="DHK-001">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" value="30" min="10" max="100">
                </div>
                <!-- Route assignment is managed from Assignments page -->
                <button type="submit" class="btn btn-primary">Add Shuttle</button>
            </form>
        </div>
        
        <h2>All Shuttles (<?= count($shuttles) ?>)</h2>
        
        <?php foreach ($shuttles as $s): ?>
        <div class="shuttle-card">
            <div class="shuttle-header">
                <div>
                    <strong style="font-size: 18px;"><?= e($s['shuttle_name']) ?></strong>
                    <?php if (!$s['is_active']): ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-muted" style="font-size: 14px;">
                Registration: <?= e($s['registration_number']) ?><br>
                Capacity: <?= $s['capacity'] ?> passengers
            </div>
            
            <div class="shuttle-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="shuttle_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: <?= $s['is_active'] ? '#f44336' : '#4CAF50' ?>; color: white;">
                        <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this shuttle?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="shuttle_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background: #757575; color: white;">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
