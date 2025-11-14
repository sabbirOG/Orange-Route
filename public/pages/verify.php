<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = null;

if ($token) {
    try {
        $success = OrangeRoute\Auth::verifyEmail($token);
        if ($success) {
            OrangeRoute\Session::flash('success', 'Email verified! You can now login.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Email Verification - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <div class="container" style="margin-top: 100px; text-align: center;">
        <?php if ($success): ?>
            <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
            <h2>Email Verified!</h2>
            <p>Your email has been verified successfully.</p>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        <?php elseif ($error): ?>
            <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
            <h2>Verification Failed</h2>
            <div class="alert alert-error"><?= e($error) ?></div>
            <a href="signup.php" class="btn btn-primary">Sign Up Again</a>
        <?php else: ?>
            <div style="font-size: 64px; margin-bottom: 20px;">📧</div>
            <h2>Invalid Link</h2>
            <p>This verification link is invalid or expired.</p>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
