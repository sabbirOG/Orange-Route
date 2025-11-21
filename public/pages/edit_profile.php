<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();

// Redirect drivers to profile page
if ($user['role'] === 'driver') {
    header('Location: profile.php');
    exit;
}
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $department = trim($_POST['department'] ?? '');
    $session = trim($_POST['session'] ?? '');
    
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $error = 'Username can only contain lowercase letters, numbers, and underscores';
    } else {
        try {
            // Check if username is already taken by another user
            $existing = OrangeRoute\Database::fetch(
                "SELECT id FROM users WHERE username = ? AND id != ?",
                [$username, $user['id']]
            );
            
            if ($existing) {
                $error = 'Username already taken. Please choose another one.';
            } else {
                OrangeRoute\Database::query(
                    "UPDATE users SET username = ?, department = ?, session = ? WHERE id = ?",
                    [$username, $department, $session, $user['id']]
                );
                // Redirect to profile after successful update
                header('Location: profile.php?updated=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Failed to update profile: ' . $e->getMessage();
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
    <title>Edit Profile - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title = 'Edit Profile'; $backHref = 'profile.php'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Edit Profile</h3>
            <p class="text-muted" style="margin-bottom: 20px;">Update your profile information.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= e($user['username']) ?>" placeholder="Enter your username" required minlength="3" pattern="[a-z0-9_]+" autocomplete="off" style="text-transform: lowercase;">
                    <small class="text-muted">Lowercase letters, numbers, and underscores only. Minimum 3 characters.</small>
                </div>
                
                <div class="form-group">
                    <label>Department</label>
                    <select name="department">
                        <option value="">Select Department</option>
                        <option value="CSE" <?= ($user['department'] ?? '') === 'CSE' ? 'selected' : '' ?>>Computer Science & Engineering</option>
                        <option value="EEE" <?= ($user['department'] ?? '') === 'EEE' ? 'selected' : '' ?>>Electrical & Electronic Engineering</option>
                        <option value="CE" <?= ($user['department'] ?? '') === 'CE' ? 'selected' : '' ?>>Civil Engineering</option>
                        <option value="BBA" <?= ($user['department'] ?? '') === 'BBA' ? 'selected' : '' ?>>Business Administration</option>
                        <option value="LAW" <?= ($user['department'] ?? '') === 'LAW' ? 'selected' : '' ?>>Law</option>
                        <option value="ENG" <?= ($user['department'] ?? '') === 'ENG' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Session</label>
                    <input type="text" name="session" value="<?= e($user['session'] ?? '') ?>" placeholder="e.g., 223" maxlength="3" pattern="[0-9]{3}">
                    <small class="text-muted">Format: XXX (221=Spring 2022, 222=Summer 2022, 223=Fall 2022)</small>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <a href="profile.php" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php $active = 'profile'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
</body>
</html>
