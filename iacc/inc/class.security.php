<?php
/**
 * Security Helper Class - Phase 1 Implementation
 * 
 * Provides secure password hashing, CSRF protection, and input validation
 * Part of iACC Phase 1: Security Hardening (December 31, 2025)
 */

class SecurityHelper {
    
    /**
     * Hash a password using bcrypt with configurable cost factor
     * 
     * @param string $password Plain text password
     * @param int $cost Bcrypt cost factor (10-12 recommended, default 12)
     * @return string|false Hashed password or false on failure
     */
    public static function hashPassword($password, $cost = 12) {
        if (empty($password)) {
            return false;
        }
        
        $options = [
            'cost' => $cost,
            'algorithm' => PASSWORD_BCRYPT
        ];
        
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }
    
    /**
     * Verify a password against a bcrypt hash
     * 
     * @param string $password Plain text password to verify
     * @param string $hash Stored password hash
     * @return bool True if password matches hash
     */
    public static function verifyPassword($password, $hash) {
        if (empty($password) || empty($hash)) {
            return false;
        }
        
        return password_verify($password, $hash);
    }
    
    /**
     * Check if a password hash needs rehashing (e.g., cost factor outdated)
     * 
     * @param string $hash Stored password hash
     * @param int $newCost New bcrypt cost factor
     * @return bool True if hash should be rehashed
     */
    public static function needsRehash($hash, $newCost = 12) {
        $options = [
            'cost' => $newCost,
            'algorithm' => PASSWORD_BCRYPT
        ];
        
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $options);
    }
    
    /**
     * Migrate from MD5 hash to bcrypt hash
     * Used during transition period - verifies old hash and returns new hash
     * 
     * @param string $password Plain text password
     * @param string $oldMd5Hash Old MD5 hash to verify against
     * @return string|false New bcrypt hash if old hash matches, false otherwise
     */
    public static function migrateFromMd5($password, $oldMd5Hash) {
        // Verify against old MD5 hash
        if (md5($password) === $oldMd5Hash) {
            // Hash is valid, generate new bcrypt hash
            return self::hashPassword($password);
        }
        
        return false;
    }
    
    // =====================================================
    // CSRF TOKEN PROTECTION
    // =====================================================
    
    /**
     * Generate a CSRF token and store in session
     * 
     * @param string $tokenName Session key name (default: 'csrf_token')
     * @return string The CSRF token
     */
    public static function generateCsrfToken($tokenName = 'csrf_token') {
        if (!isset($_SESSION[$tokenName]) || empty($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[$tokenName];
    }
    
    /**
     * Get current CSRF token from session
     * 
     * @param string $tokenName Session key name
     * @return string|null The CSRF token or null if not set
     */
    public static function getCsrfToken($tokenName = 'csrf_token') {
        return $_SESSION[$tokenName] ?? null;
    }
    
    /**
     * Validate CSRF token from POST request
     * 
     * @param string $tokenName Session key name
     * @param string|null $providedToken Token from form/request (if null, attempts to get from $_POST)
     * @return bool True if token is valid
     */
    public static function validateCsrfToken($tokenName = 'csrf_token', $providedToken = null) {
        if ($providedToken === null) {
            $providedToken = $_POST[$tokenName] ?? null;
        }
        
        if (empty($providedToken)) {
            return false;
        }
        
        $sessionToken = $_SESSION[$tokenName] ?? null;
        
        if (empty($sessionToken)) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $providedToken);
    }
    
    /**
     * Regenerate CSRF token (call after successful login, etc.)
     * 
     * @param string $tokenName Session key name
     * @return string New CSRF token
     */
    public static function regenerateCsrfToken($tokenName = 'csrf_token') {
        unset($_SESSION[$tokenName]);
        return self::generateCsrfToken($tokenName);
    }
    
    // =====================================================
    // INPUT VALIDATION
    // =====================================================
    
    /**
     * Validate email format
     * 
     * @param string $email Email address to validate
     * @return bool True if valid email format
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            return false;
        }
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username (alphanumeric, underscore, dash, 3-20 chars)
     * 
     * @param string $username Username to validate
     * @return bool True if valid username format
     */
    public static function validateUsername($username) {
        if (empty($username)) {
            return false;
        }
        
        return preg_match('/^[a-zA-Z0-9_\-]{3,20}$/', $username) === 1;
    }
    
    /**
     * Validate password strength
     * 
     * Requirements:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one number
     * - At least one special character
     * 
     * @param string $password Password to validate
     * @return array [isValid => bool, errors => string[]]
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        $isValid = true;
        
        if (empty($password)) {
            return [
                'isValid' => false,
                'errors' => ['Password is required']
            ];
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
            $isValid = false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
            $isValid = false;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
            $isValid = false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
            $isValid = false;
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*...)';
            $isValid = false;
        }
        
        return [
            'isValid' => $isValid,
            'errors' => $errors
        ];
    }
    
    /**
     * Validate input length
     * 
     * @param string $input Input to validate
     * @param int $minLength Minimum length (0 for no minimum)
     * @param int $maxLength Maximum length
     * @return bool True if length is valid
     */
    public static function validateLength($input, $minLength = 0, $maxLength = 255) {
        if (empty($input) && $minLength > 0) {
            return false;
        }
        
        $length = strlen($input);
        return $length >= $minLength && $length <= $maxLength;
    }
    
    /**
     * Sanitize string input (prevent XSS)
     * Removes potentially dangerous HTML/JS
     * 
     * @param string $input Input to sanitize
     * @return string Sanitized input
     */
    public static function sanitizeInput($input) {
        if (empty($input)) {
            return $input;
        }
        
        // Remove any script tags and event handlers
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/i', '', $input);
        $input = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/i', '', $input);
        $input = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $input);
        
        // HTML encode special characters
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate input against whitelist of allowed characters
     * 
     * @param string $input Input to validate
     * @param string $pattern Regex pattern of allowed characters
     * @return bool True if input matches pattern
     */
    public static function validatePattern($input, $pattern) {
        if (empty($input)) {
            return false;
        }
        
        return preg_match($pattern, $input) === 1;
    }
    
    /**
     * Validate integer input
     * 
     * @param mixed $input Input to validate
     * @param int|null $min Minimum value (null for no minimum)
     * @param int|null $max Maximum value (null for no maximum)
     * @return bool True if valid integer within range
     */
    public static function validateInteger($input, $min = null, $max = null) {
        if (!is_numeric($input) || intval($input) != $input) {
            return false;
        }
        
        $value = intval($input);
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate numeric input (includes decimals)
     * 
     * @param mixed $input Input to validate
     * @param int $decimalPlaces Number of decimal places allowed
     * @return bool True if valid number
     */
    public static function validateNumeric($input, $decimalPlaces = 2) {
        if (!is_numeric($input)) {
            return false;
        }
        
        if ($decimalPlaces === 0) {
            return preg_match('/^\d+$/', $input) === 1;
        }
        
        $pattern = '/^\d+(\.\d{1,' . $decimalPlaces . '})?$/';
        return preg_match($pattern, $input) === 1;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string to validate
     * @return bool True if valid date
     */
    public static function validateDate($date) {
        if (empty($date)) {
            return false;
        }
        
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate date time format (YYYY-MM-DD HH:MM:SS)
     * 
     * @param string $dateTime DateTime string to validate
     * @return bool True if valid datetime
     */
    public static function validateDateTime($dateTime) {
        if (empty($dateTime)) {
            return false;
        }
        
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $d && $d->format('Y-m-d H:i:s') === $dateTime;
    }
    
    /**
     * Validate phone number (basic international format)
     * 
     * @param string $phone Phone number to validate
     * @return bool True if valid phone format
     */
    public static function validatePhone($phone) {
        if (empty($phone)) {
            return false;
        }
        
        // Allow digits, +, -, (), spaces
        return preg_match('/^[\d\+\-\(\)\s]{7,20}$/', $phone) === 1;
    }
    
    /**
     * Get validation error messages
     * 
     * @return array Last validation errors
     */
    public static function getValidationErrors() {
        return $_SESSION['validation_errors'] ?? [];
    }
    
    /**
     * Set validation error messages
     * 
     * @param array $errors Array of error messages
     */
    public static function setValidationErrors($errors) {
        $_SESSION['validation_errors'] = $errors;
    }
    
    /**
     * Clear validation errors
     */
    public static function clearValidationErrors() {
        unset($_SESSION['validation_errors']);
    }
}
?>
