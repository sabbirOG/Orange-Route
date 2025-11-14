<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (OrangeRoute\Auth::check()) {
    redirect('pages/map.php');
}

$error = OrangeRoute\Session::flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($studentId) || empty($password)) {
        $error = 'Please fill all fields';
    } elseif (!preg_match('/^[0-9]{9,10}$/', $studentId)) {
        $error = 'Invalid student ID format. Must be 9 or 10 digits.';
    } else {
        // Convert student ID to email
        $email = $studentId . '@student.orangeroute.local';
        
        if (OrangeRoute\Auth::login($email, $password)) {
            redirect('pages/map.php');
        } else {
            $error = 'Invalid credentials';
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
    <title>Login - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                        <path d="M5 11V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5"></path>
                        <circle cx="9" cy="16" r="1" fill="white"></circle>
                        <circle cx="15" cy="16" r="1" fill="white"></circle>
                    </svg>
                </div>
                <h1 class="logo-title">OrangeRoute</h1>
                <p class="logo-subtitle">Track your shuttle in real-time</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <div class="login-card">
                <h2 class="welcome-text">Welcome back</h2>
                <p class="welcome-sub">Sign in to continue tracking</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id" required placeholder="123456789" pattern="[0-9]{9,10}" maxlength="10" autocomplete="off" value="<?= e($_POST['student_id'] ?? '') ?>">
                        <small class="text-muted">Enter your 9 or 10 digit student ID</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div style="position: relative;">
                            <input type="password" id="login-password" name="password" required placeholder="Enter your password" autocomplete="current-password" style="padding-right: 60px;">
                            <button type="button" id="login-toggle" onclick="togglePassword('login-password', 'login-toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                                Show
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Sign In</button>
                    
                    <p class="text-center mt-2">
                        <a href="forgot_password.php" style="color: var(--primary); text-decoration: none;">Forgot password?</a>
                    </p>
                </form>
            </div>
            
            <div class="login-links">
                <a href="signup.php">Don't have an account? Sign up →</a>
            </div>
        </div>
    </div>
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
