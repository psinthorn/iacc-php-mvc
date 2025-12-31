<?php
/**
 * Session Security Manager - Phase 1 Implementation
 * 
 * Handles:
 * - Session timeout enforcement
 * - Session regeneration
 * - Activity tracking
 * - Concurrent session control
 * - Session cleanup
 * 
 * Part of iACC Phase 1: Security Hardening
 * Last Updated: December 31, 2025
 */

class SessionManager {
    
    // Configuration
    private static $sessionTimeout = 1800; // 30 minutes in seconds
    private static $warningThreshold = 300; // Show warning 5 minutes before timeout
    
    /**
     * Initialize session security
     * Call this at the start of every page that requires authentication
     */
    public static function initializeSecureSession() {
        // Configure session security settings
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', self::$sessionTimeout);
        
        session_start();
        
        // Enforce session timeout
        self::enforceSessionTimeout();
    }
    
    /**
     * Enforce session timeout
     * Destroys session if inactive for longer than timeout period
     */
    public static function enforceSessionTimeout() {
        $timeout = self::$sessionTimeout;
        $currentTime = time();
        $lastActivityTime = $_SESSION['last_activity'] ?? null;
        
        // First visit in session
        if ($lastActivityTime === null) {
            $_SESSION['last_activity'] = $currentTime;
            return;
        }
        
        // Check if session has timed out
        $inactiveTime = $currentTime - $lastActivityTime;
        
        if ($inactiveTime > $timeout) {
            // Session timeout - destroy session
            self::destroySession('Session timeout due to inactivity');
            header('Location: login.php?msg=Session+expired');
            exit;
        }
        
        // Check if warning should be shown (5 minutes before timeout)
        $timeUntilTimeout = $timeout - $inactiveTime;
        if ($timeUntilTimeout < self::$warningThreshold && $timeUntilTimeout > 0) {
            $_SESSION['session_timeout_warning'] = true;
            $_SESSION['minutes_until_timeout'] = ceil($timeUntilTimeout / 60);
        } else {
            $_SESSION['session_timeout_warning'] = false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = $currentTime;
    }
    
    /**
     * Require user to be logged in
     * Redirects to login if not authenticated
     */
    public static function requireLogin() {
        if (empty($_SESSION['usr_id'])) {
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Get current logged-in user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public static function getUserId() {
        return $_SESSION['usr_id'] ?? null;
    }
    
    /**
     * Get current logged-in username
     * 
     * @return string|null Username or null if not logged in
     */
    public static function getUsername() {
        return $_SESSION['usr_name'] ?? null;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in
     */
    public static function isLoggedIn() {
        return !empty($_SESSION['usr_id']);
    }
    
    /**
     * Check if password reset is required
     * 
     * @return bool True if user must reset password
     */
    public static function requiresPasswordReset() {
        return !empty($_SESSION['require_password_reset']);
    }
    
    /**
     * Get session timeout warning information
     * 
     * @return array|null Warning info with minutes_until_timeout, or null if no warning
     */
    public static function getTimeoutWarning() {
        if (!empty($_SESSION['session_timeout_warning'])) {
            return [
                'show_warning' => true,
                'minutes_until_timeout' => $_SESSION['minutes_until_timeout'] ?? 5
            ];
        }
        return null;
    }
    
    /**
     * Regenerate session ID
     * Call after successful login to prevent session fixation attacks
     */
    public static function regenerateSessionId() {
        session_regenerate_id(true);
    }
    
    /**
     * Log out user and destroy session
     * 
     * @param string $reason Reason for logout
     */
    public static function destroySession($reason = 'User logout') {
        $userId = $_SESSION['usr_id'] ?? null;
        
        // Log the logout
        if ($userId) {
            self::logSessionEvent($userId, 'LOGOUT', $reason);
        }
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Log session event for audit trail
     * 
     * @param int $userId User ID
     * @param string $event Event type
     * @param string $details Event details
     */
    public static function logSessionEvent($userId, $event, $details) {
        // This would connect to audit log table
        // Placeholder for integration with database
        // In production, log to database or audit system
    }
    
    /**
     * Get user's current session information
     * 
     * @return array Session information
     */
    public static function getSessionInfo() {
        return [
            'user_id' => $_SESSION['usr_id'] ?? null,
            'username' => $_SESSION['usr_name'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'language' => $_SESSION['lang'] ?? 'en',
        ];
    }
    
    /**
     * Check if session should be refreshed
     * Useful for AJAX requests to maintain activity
     * 
     * @return bool True if session should be refreshed
     */
    public static function shouldRefreshSession() {
        $lastRefresh = $_SESSION['last_refresh'] ?? 0;
        $currentTime = time();
        
        // Refresh if it's been more than 1 minute since last refresh
        if ($currentTime - $lastRefresh > 60) {
            $_SESSION['last_refresh'] = $currentTime;
            return true;
        }
        
        return false;
    }
}
?>
