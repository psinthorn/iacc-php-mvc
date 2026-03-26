<?php
namespace App\Controllers;

/**
 * AuthController - Handles authentication pages
 * Pre-auth routes (no login required): authorize, forgotPassword, resetPassword
 * All methods render standalone pages and exit
 */
class AuthController
{
    /** Process login/logout (POST: authenticate, GET: logout) */
    public function authenticate(): void
    {
        include __DIR__ . '/../Views/auth/authorize.php';
        exit;
    }

    /** Forgot password page + email handler */
    public function forgotPassword(): void
    {
        include __DIR__ . '/../Views/auth/forgot-password.php';
        exit;
    }

    /** Reset password page (accessed via email token) */
    public function resetPassword(): void
    {
        include __DIR__ . '/../Views/auth/reset-password.php';
        exit;
    }

    /** Language switcher (POST handler, redirects) */
    public function switchLanguage(): void
    {
        include __DIR__ . '/../Views/auth/lang.php';
        exit;
    }
}
