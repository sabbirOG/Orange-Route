<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$user = OrangeRoute\Auth::user();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill all fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (!password_verify($currentPassword, $user['password_hash'])) {
        $error = 'Current password is incorrect';
    } else {
        try {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            OrangeRoute\Database::query(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$newHash, $user['id']]
            );
            $success = 'Password changed successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update password';
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
    <title>Change Password - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
    <script src="/OrangeRoute/assets/js/theme.js"></script>
</head>
<body>
    <?php $title = 'Change Password'; $backHref = 'profile.php'; include __DIR__ . '/_partials/top_bar.php'; ?>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error" style="display: flex; align-items: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>in t
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="display: flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 16px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); border: none;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span style="font-weight: 600; font-size: 15px;"><?= e($success) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Change Password</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password" style="padding-right: 60px;">
                        <button type="button" id="current_toggle" onclick="togglePassword('current_password', 'current_toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                            Show
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new password" minlength="6" style="padding-right: 60px;">
                        <button type="button" id="new_toggle" onclick="togglePassword('new_password', 'new_toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                            Show
                        </button>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password" minlength="6" style="padding-right: 60px;">
                        <button type="button" id="confirm_toggle" onclick="togglePassword('confirm_password', 'confirm_toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                            Show
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Change Password</button>
                    <a href="profile.php" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none;">Cancel</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h3>Password Tips</h3>
            <ul style="margin-left: 20px; color: var(--text-light); font-size: 14px;">
                <li>Use at least 8 characters</li>
                <li>Include uppercase and lowercase letters</li>
                <li>Add numbers and special characters</li>
                <li>Don't use common words or personal information</li>
            </ul>
        </div>
    </div>
    
    <?php $active = 'profile'; include __DIR__ . '/_partials/bottom_nav.php'; ?>
    
    <script>
        function togglePassword(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Hide';
            } else {
                input.type = 'password';
                button.textContent = 'Show';
            }
        }
    </script>
</body>
</html>
