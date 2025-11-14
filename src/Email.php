<?php

namespace OrangeRoute;

/**
 * Email utility for sending notifications
 */
class Email
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $from = $_ENV['MAIL_FROM'] ?? 'noreply@orangeroute.local';
        $fromName = $_ENV['APP_NAME'] ?? 'OrangeRoute';
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            "From: {$fromName} <{$from}>",
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // For development, log emails instead of sending
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            self::logEmail($to, $subject, $htmlBody);
            return true;
        }
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    public static function sendVerification(string $email, string $token): bool
    {
        $url = ($_ENV['APP_URL'] ?? 'http://localhost') . '/pages/verify.php?token=' . urlencode($token);
        
        $html = self::template('Email Verification', "
        <h2>Welcome to OrangeRoute</h2>
        <p>Thanks for signing up. Please verify your email address by clicking the button below:</p>
        <p style='text-align: center; margin: 30px 0;'>
        <a href='{$url}' style='background: #FF6B35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
            Verify Email Address
        </a>
        </p>
        <p>Or copy this link: <a href='{$url}'>{$url}</a></p>
        <p>This link expires in 24 hours.</p>
    ");
            <h2>Welcome to OrangeRoute</h2>
            <p>Thanks for signing up. Please verify your email address by clicking the button below:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$url}' style='background: #FF6B35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                    Verify Email Address
                </a>
            </p>
            <p>Or copy this link: <a href='{$url}'>{$url}</a></p>
            <p>This link expires in 24 hours.</p>
        ");
        
        return self::send($email, 'Verify your OrangeRoute account', $html);
    }
    
    public static function sendPasswordReset(string $email, string $token): bool
    {
        $url = ($_ENV['APP_URL'] ?? 'http://localhost') . '/pages/reset_password.php?token=' . urlencode($token);
        
        $html = self::template('Password Reset', "
            <h2>Reset Your Password</h2>
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$url}' style='background: #FF6B35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                    Reset Password
                </a>
            </p>
            <p>Or copy this link: <a href='{$url}'>{$url}</a></p>
            <p>This link expires in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ");
        
        return self::send($email, 'Reset your OrangeRoute password', $html);
    }
    
    private static function template(string $title, string $content): string
    {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
</head>
<body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); padding: 20px; text-align: center; border-radius: 12px 12px 0 0;'>
        <h1 style='color: white; margin: 0; font-size: 24px;'>OrangeRoute</h1>
    </div>
    <div style='background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 12px 12px;'>
        {$content}
    </div>
    <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
        <p>© " . date('Y') . " OrangeRoute. All rights reserved.</p>
    </div>
</body>
</html>";
    }
    
    private static function logEmail(string $to, string $subject, string $body): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/emails.log';
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            strip_tags($body)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
