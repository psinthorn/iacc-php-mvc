<?php

namespace App\Auth;

/**
 * PasswordHasher - Securely hash and verify passwords
 * Uses bcrypt algorithm with configurable cost factor
 */
class PasswordHasher
{
    private static $cost = 12;

    /**
     * Hash password using bcrypt
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => self::$cost,
        ]);
    }

    /**
     * Verify password against hash
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => self::$cost,
        ]);
    }

    /**
     * Set bcrypt cost factor (4-31)
     */
    public static function setCost(int $cost): void
    {
        if ($cost < 4 || $cost > 31) {
            throw new \InvalidArgumentException('Cost must be between 4 and 31');
        }
        self::$cost = $cost;
    }

    /**
     * Validate password strength
     */
    public static function validateStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }
}
