<?php

namespace OrangeRoute;

/**
 * Simple CSRF - One method
 */
class CSRF
{
    public static function token(): string
    {
        if (!Session::has('csrf')) {
            Session::set('csrf', bin2hex(random_bytes(16)));
        }
        return Session::get('csrf');
    }
    
    public static function check(?string $token): bool
    {
        return hash_equals(self::token(), $token ?? '');
    }
    
    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . self::token() . '">';
    }
}

