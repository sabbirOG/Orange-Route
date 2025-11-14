<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = null;

// Handle OTP form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    try {
        if (empty($email) || empty($otp)) {
            throw new Exception('Please enter your email and OTP');
        }
        $verified = OrangeRoute\Auth::verifyEmailOtp($email, $otp);
        if ($verified) {
            OrangeRoute\Session::flash('success', 'Email verified! You can now login.');
            $success = true;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Legacy token verification support
if ($token && !$success) {
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
    <div class="container" style="margin-top: 100px; text-align: center; max-width:520px;">
        <?php if ($success): ?>
            <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
            <h2>Email Verified!</h2>
            <p>Your email has been verified successfully.</p>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        <?php else: ?>
            <div style="font-size: 64px; margin-bottom: 20px;">📧</div>
            <h2>Verify Your Email</h2>
            <p>Enter the 6-digit code we sent to your UIU email.</p>

            <?php if ($error): ?>
                <div class="alert alert-error" style="text-align:left; display:inline-block;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="text-align:left; display:inline-block; width:100%; max-width:420px;">
                <div class="form-group">
                    <label>UIU Email</label>
                    <input type="email" name="email" required placeholder="yourname@bscse.uiu.ac.bd" value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>6-digit OTP</label>
                    <input type="text" name="otp" required placeholder="123456" minlength="6" maxlength="6" pattern="[0-9]{6}">
                </div>
                <button type="submit" class="btn btn-primary">Verify</button>
            </form>

            <div id="resend-otp" style="margin-top: 12px;">
                <button id="resend-btn" class="btn btn-outline btn-small" type="button">Resend OTP</button>
                <div id="resend-msg" style="margin-top:8px; display:none;"></div>
            </div>
        <?php endif; ?>
    </div>
    <script>
    (function(){
        const resendBtn = document.getElementById('resend-btn');
        const msgEl = document.getElementById('resend-msg');
        if (!resendBtn) return;
        const getEmail = () => {
            const formEmail = document.querySelector('input[name="email"]');
            return formEmail ? formEmail.value.trim() : '';
        };
        const showMsg = (text, ok) => {
            msgEl.style.display = 'block';
            msgEl.className = ok ? 'alert alert-success' : 'alert alert-error';
            msgEl.textContent = text;
        };
        resendBtn.addEventListener('click', async () => {
            const email = getEmail();
            if (!email) {
                showMsg('Please enter your email above first.', false);
                return;
            }
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            try {
                const form = new URLSearchParams();
                form.append('email', email);
                const res = await fetch('../api/resend_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.toString()
                });
                const data = await res.json();
                if (data && data.success) {
                    showMsg('OTP resent! Check your inbox and spam folder.', true);
                } else {
                    showMsg(data && data.message ? data.message : 'Unable to resend OTP.', false);
                }
            } catch (e) {
                showMsg('Network error. Please try again.', false);
            } finally {
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend OTP';
            }
        });
    })();
    </script>
</body>
</html>
