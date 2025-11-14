<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$token = $_GET['token'] ?? '';
$error = null;
$success = false;

if (!$token) {
    redirect('pages/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        try {
            $success = OrangeRoute\Auth::resetPassword($token, $password);
            if ($success) {
                OrangeRoute\Session::flash('success', 'Password reset successfully! Please login.');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
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
    <title>Reset Password - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <div class="container" style="margin-top: 80px;">
        <?php if ($success): ?>
            <div style="text-align: center;">
                <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                <h2>Password Reset!</h2>
                <p>Your password has been reset successfully.</p>
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="font-size: 48px; margin-bottom: 8px;">🔑</h1>
                <h2 class="text-primary">Reset Password</h2>
                <p class="text-muted">Enter your new password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required placeholder="At least 6 characters" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Re-enter password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
