<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (OrangeRoute\Auth::check()) {
    redirect('pages/map.php');
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            OrangeRoute\Auth::register($email, $password, $role);
            $success = 'Account created! Please login.';
        } catch (\Exception $e) {
            $error = 'Email already exists';
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
    <title>Sign Up - OrangeRoute</title>
    <link rel="stylesheet" href="/OrangeRoute/assets/css/mobile.css">
</head>
<body>
    <div class="signup-wrapper">
        <div class="signup-container">
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
                <p class="logo-subtitle">Join us and never miss your ride</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;"><?= e($success) ?></div>
            <?php endif; ?>
            
            <div class="signup-card">
                <h2 class="welcome-text">Create Account</h2>
                <p class="welcome-sub">Get started in seconds</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="your.email@university.edu" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="At least 6 characters" autocomplete="new-password" minlength="6">
                        <small class="text-muted">Must be at least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label>I am a...</label>
                        <div class="role-selector">
                            <div class="role-option">
                                <input type="radio" id="role-student" name="role" value="student" checked>
                                <label for="role-student" class="role-label">
                                    <span class="role-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="8" r="4"></circle>
                                            <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                                        </svg>
                                    </span>
                                    <span class="role-name">Student</span>
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role-driver" name="role" value="driver">
                                <label for="role-driver" class="role-label">
                                    <span class="role-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                            <path d="M5 11V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5"></path>
                                            <circle cx="9" cy="16" r="1"></circle>
                                            <circle cx="15" cy="16" r="1"></circle>
                                        </svg>
                                    </span>
                                    <span class="role-name">Driver</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>
            </div>
            
            <div class="signup-links">
                <a href="login.php">Already have an account? Sign in →</a>
            </div>
        </div>
    </div>
</body>
</html>
