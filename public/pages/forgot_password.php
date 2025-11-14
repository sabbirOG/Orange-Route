<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email';
    } else {
        OrangeRoute\Auth::requestPasswordReset($email);
        $success = 'If an account exists with this email, you will receive a password reset link.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Forgot Password - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <div class="container" style="margin-top: 80px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 class="text-primary" style="margin-bottom:8px;">Forgot Password</h2>
            <p class="text-muted">Enter your email to reset it</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
            <a href="login.php" class="btn btn-primary">Back to Login</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="your.email@university.edu" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </form>
            </div>
            
            <p class="text-center mt-2">
                <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Back to Login</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
