<?php

namespace OrangeRoute;

/**
 * Simple Auth - Mobile-first with email verification and password reset
 */
class Auth
{
    public static function login(string $email, string $password): bool
    {
        $user = Database::fetch(
            "SELECT id, email, password_hash, role, email_verified FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Admins don't need email verification
        if ($user['role'] !== 'admin' && !$user['email_verified']) {
            throw new \RuntimeException('Please verify your email first');
        }
        
        Session::set('user_id', $user['id']);
        Session::set('email', $user['email']);
        Session::set('role', $user['role']);
        
        Database::query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);
        return true;
    }
    
    public static function register(string $email, string $password, string $role = 'student'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $userId = Database::insert(
            "INSERT INTO users (email, password_hash, role, verification_token, verification_expires_at, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$email, $hash, $role, $token, $expires]
        );
        
        // Send verification email
        Email::sendVerification($email, $token);
        
        return $userId;
    }
    
    public static function verifyEmail(string $token): bool
    {
        $user = Database::fetch(
            "SELECT id, verification_expires_at FROM users 
             WHERE verification_token = ? AND email_verified = 0",
            [$token]
        );
        
        if (!$user) {
            return false;
        }
        
        if (strtotime($user['verification_expires_at']) < time()) {
            throw new \RuntimeException('Verification link expired');
        }
        
        Database::query(
            "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?",
            [$user['id']]
        );
        
        return true;
    }
    
    public static function requestPasswordReset(string $email): bool
    {
        $user = Database::fetch("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if (!$user) {
            // Don't reveal if email exists
            return true;
        }
        
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        Database::query(
            "INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address) VALUES (?, ?, ?, ?)",
            [$user['id'], $tokenHash, $expires, $_SERVER['REMOTE_ADDR'] ?? '']
        );
        
        // Send reset email
        Email::sendPasswordReset($email, $token);
        
        return true;
    }
    
    public static function resetPassword(string $token, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $token);
        
        $reset = Database::fetch(
            "SELECT pr.id, pr.user_id, pr.expires_at, u.password_hash as current_hash
             FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token_hash = ? AND pr.used = 0",
            [$tokenHash]
        );
        
        if (!$reset) {
            return false;
        }
        
        if (strtotime($reset['expires_at']) < time()) {
            throw new \RuntimeException('Reset link expired');
        }
        
        // Check if new password is same as old
        if (password_verify($newPassword, $reset['current_hash'])) {
            throw new \RuntimeException('New password must be different');
        }
        
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        Database::query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $reset['user_id']]);
        
        // Mark token as used
        Database::query("UPDATE password_resets SET used = 1, used_at = NOW() WHERE id = ?", [$reset['id']]);
        
        // Add to password history
        Database::query(
            "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)",
            [$reset['user_id'], $newHash]
        );
        
        return true;
    }
    
    public static function logout(): void
    {
        Session::destroy();
    }
    
    public static function check(): bool
    {
        return Session::isLoggedIn();
    }
    
    public static function user(): ?array
    {
        $id = Session::userId();
        return $id ? Database::fetch("SELECT * FROM users WHERE id = ?", [$id]) : null;
    }
    
    public static function isRole(string $role): bool
    {
        return Session::userRole() === $role;
    }
}
