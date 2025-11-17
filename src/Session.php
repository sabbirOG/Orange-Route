<?php

namespace OrangeRoute;

/**
 * Simple Session - Mobile-optimized
 */
class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
    }
    
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function userId(): ?int
    {
        return self::get('user_id');
    }
    
    public static function userRole(): ?string
    {
        return self::get('role');
    }
    
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }
    
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
    
    public static function flash(string $key, $value = null)
    {
        if ($value === null) {
            $val = self::get("_flash_{$key}");
            self::remove("_flash_{$key}");
            return $val;
        }
        self::set("_flash_{$key}", $value);
    }
    
    public static function setFlash(string $key, $value): void
    {
        self::set("_flash_{$key}", $value);
    }
    
    public static function getFlash(string $key)
    {
        $val = self::get("_flash_{$key}");
        self::remove("_flash_{$key}");
        return $val;
    }
}
