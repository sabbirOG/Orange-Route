<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (OrangeRoute\Auth::check()) {
    redirect('pages/map.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'student';
    
    if (empty($studentId) || empty($password)) {
        $error = 'Please fill all fields';
    } elseif (!preg_match('/^[0-9]{9,10}$/', $studentId)) {
        $error = 'Invalid student ID format. Must be 9 or 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            // Generate email from student ID
            $email = $studentId . '@student.orangeroute.local';
            OrangeRoute\Auth::register($email, $password, $role, $studentId);
            
            // Auto-login after successful registration
            if (OrangeRoute\Auth::login($email, $password)) {
                redirect('pages/map.php');
            }
        } catch (\Exception $e) {
            $error = 'Account already exists with this Student ID';
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
    <script src="/OrangeRoute/assets/js/theme.js"></script>
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
            
            <div class="signup-card">
                <h2 class="welcome-text">Create Account</h2>
                <p class="welcome-sub">Get started in seconds</p>
                
                <div class="info-box" style="background: #e8f4f8; border-left: 4px solid var(--primary); padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: start; gap: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div style="font-size: 13px; color: #0d1a2b; line-height: 1.5;">
                            <strong style="display: block; margin-bottom: 4px;">Student ID Format</strong>
                            Use your 9 or 10 digit student ID to sign up.<br>
                            <span style="color: #555;">Example: <code style="background: rgba(255,102,0,0.1); padding: 2px 6px; border-radius: 3px; font-family: monospace;">123456789</code> or <code style="background: rgba(255,102,0,0.1); padding: 2px 6px; border-radius: 3px; font-family: monospace;">1234567890</code></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id" required placeholder="123456789" pattern="[0-9]{9,10}" maxlength="10" autocomplete="off" value="<?= e($_POST['student_id'] ?? '') ?>">
                        <small class="text-muted">Enter your 9 or 10 digit student ID</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div style="position: relative;">
                            <input type="password" id="signup-password" name="password" required placeholder="At least 6 characters" autocomplete="new-password" minlength="6" oninput="checkPasswordStrength(this.value)" style="padding-right: 60px;">
                            <button type="button" id="signup-toggle" onclick="togglePassword('signup-password', 'signup-toggle')" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px 8px; color: var(--primary); font-size: 13px; font-weight: 500; transition: opacity 0.2s;" aria-label="Toggle password visibility">
                                Show
                            </button>
                        </div>
                        <div id="password-strength" style="margin-top: 8px; display: none;">
                            <div style="height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden; margin-bottom: 6px;">
                                <div id="strength-bar" style="height: 100%; width: 0%; transition: all 0.3s; background: #ccc;"></div>
                            </div>
                            <small id="strength-text" style="font-size: 12px; color: var(--text-muted);"></small>
                        </div>
                        <small class="text-muted">Use 8+ characters with uppercase, numbers, and symbols for strong password</small>
                    </div>
                    
                    <input type="hidden" name="role" value="student">
                    
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>
            </div>
            
            <div class="signup-links">
                <a href="login.php">Already have an account? Sign in →</a>
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
    
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('password-strength');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }
        
        strengthDiv.style.display = 'block';
        
        let strength = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) strength += 25;
        else feedback.push('8+ characters');
        
        // Uppercase check
        if (/[A-Z]/.test(password)) strength += 25;
        else feedback.push('uppercase letter');
        
        // Number check
        if (/[0-9]/.test(password)) strength += 25;
        else feedback.push('number');
        
        // Special character check
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        else feedback.push('special character');
        
        // Update bar
        strengthBar.style.width = strength + '%';
        
        // Update color and text
        if (strength <= 25) {
            strengthBar.style.background = '#dc3545';
            strengthText.textContent = 'Weak - Add: ' + feedback.join(', ');
            strengthText.style.color = '#dc3545';
        } else if (strength <= 50) {
            strengthBar.style.background = '#ffc107';
            strengthText.textContent = 'Fair - Add: ' + feedback.join(', ');
            strengthText.style.color = '#ffc107';
        } else if (strength <= 75) {
            strengthBar.style.background = '#17a2b8';
            strengthText.textContent = 'Good - Add: ' + feedback.join(', ');
            strengthText.style.color = '#17a2b8';
        } else {
            strengthBar.style.background = '#28a745';
            strengthText.textContent = 'Strong password!';
            strengthText.style.color = '#28a745';
        }
    }
    </script>
</body>
</html>
