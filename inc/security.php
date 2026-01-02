<?php
/**
 * Security Helper Functions
 * Provides CSRF protection, input validation, and XSS prevention
 */

/**
 * Generate CSRF token and store in session
 * @return string The CSRF token
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF hidden input field
 * @return string HTML hidden input with CSRF token
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token from request
 * @param string $token The token to verify (optional, reads from POST if not provided)
 * @return bool True if valid, false otherwise
 */
function csrf_verify($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (call after successful form submission)
 */
function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Sanitize output to prevent XSS
 * @param string $string The string to sanitize
 * @return string HTML-escaped string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output (alias for e())
 */
function escape($string) {
    return e($string);
}

/**
 * Validate and sanitize input
 * @param mixed $input The input to validate
 * @param string $type The expected type: string, int, float, email, url, bool
 * @param array $options Additional options (min, max, required, default)
 * @return mixed Validated value or null/default if invalid
 */
function validate_input($input, $type = 'string', $options = []) {
    $required = $options['required'] ?? false;
    $default = $options['default'] ?? null;
    
    // Check required
    if ($input === null || $input === '') {
        if ($required) {
            return null; // Validation failed
        }
        return $default;
    }
    
    switch ($type) {
        case 'int':
        case 'integer':
            $value = filter_var($input, FILTER_VALIDATE_INT);
            if ($value === false) return $default;
            
            if (isset($options['min']) && $value < $options['min']) return $default;
            if (isset($options['max']) && $value > $options['max']) return $default;
            return $value;
            
        case 'float':
        case 'double':
            $value = filter_var($input, FILTER_VALIDATE_FLOAT);
            if ($value === false) return $default;
            
            if (isset($options['min']) && $value < $options['min']) return $default;
            if (isset($options['max']) && $value > $options['max']) return $default;
            return $value;
            
        case 'email':
            $value = filter_var($input, FILTER_VALIDATE_EMAIL);
            return $value !== false ? $value : $default;
            
        case 'url':
            $value = filter_var($input, FILTER_VALIDATE_URL);
            return $value !== false ? $value : $default;
            
        case 'bool':
        case 'boolean':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
            
        case 'string':
        default:
            $value = trim((string) $input);
            
            if (isset($options['min']) && strlen($value) < $options['min']) return $default;
            if (isset($options['max']) && strlen($value) > $options['max']) return $default;
            if (isset($options['pattern']) && !preg_match($options['pattern'], $value)) return $default;
            
            return $value;
    }
}

/**
 * Get validated request parameter
 * @param string $key Parameter name
 * @param string $type Expected type
 * @param array $options Validation options
 * @return mixed Validated value
 */
function input($key, $type = 'string', $options = []) {
    $value = $_REQUEST[$key] ?? null;
    return validate_input($value, $type, $options);
}

/**
 * Get validated POST parameter
 * @param string $key Parameter name
 * @param string $type Expected type
 * @param array $options Validation options
 * @return mixed Validated value
 */
function post($key, $type = 'string', $options = []) {
    $value = $_POST[$key] ?? null;
    return validate_input($value, $type, $options);
}

/**
 * Get validated GET parameter
 * @param string $key Parameter name
 * @param string $type Expected type
 * @param array $options Validation options
 * @return mixed Validated value
 */
function get($key, $type = 'string', $options = []) {
    $value = $_GET[$key] ?? null;
    return validate_input($value, $type, $options);
}

/**
 * Sanitize string for SQL LIKE queries (escape % and _)
 * @param string $string The string to sanitize
 * @return string Escaped string safe for LIKE
 */
function escape_like($string) {
    return addcslashes($string, '%_');
}

/**
 * Check if request is POST
 * @return bool
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request is AJAX
 * @return bool
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Redirect with optional flash message
 * @param string $url The URL to redirect to
 * @param string $message Optional flash message
 * @param string $type Message type: success, error, warning, info
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 * @return array|null ['message' => string, 'type' => string] or null
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

// ============================================================================
// SQL INJECTION PREVENTION HELPERS
// Use these functions when building SQL queries with user input
// ============================================================================

/**
 * Safely escape a string value for SQL queries
 * Prevents SQL injection attacks
 * 
 * @param mixed $value The value to escape
 * @return string Escaped value safe for SQL
 * 
 * Usage: $query = "SELECT * FROM users WHERE name='" . sql_escape($_POST['name']) . "'";
 */
function sql_escape($value) {
    global $__MYSQL_COMPAT_CONNECTION;
    
    if ($value === null) {
        return '';
    }
    
    $value = (string) $value;
    
    if ($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)) {
        return $__MYSQL_COMPAT_CONNECTION->conn->real_escape_string($value);
    }
    
    // Fallback if no connection (should rarely happen)
    return addslashes($value);
}

/**
 * Get a safe integer value from user input
 * Returns 0 if input is not a valid integer
 * 
 * @param mixed $value The value to convert
 * @return int Safe integer value
 * 
 * Usage: $id = sql_int($_GET['id']);
 */
function sql_int($value) {
    return intval($value);
}

/**
 * Get a safe float value from user input
 * Returns 0.0 if input is not a valid number
 * 
 * @param mixed $value The value to convert
 * @return float Safe float value
 * 
 * Usage: $price = sql_float($_POST['price']);
 */
function sql_float($value) {
    return floatval($value);
}

/**
 * Get escaped string input from request (shorthand)
 * 
 * @param string $key The key to look for
 * @param string $default Default value if not found
 * @return string Escaped string safe for SQL
 * 
 * Usage: $name = input_string('name');
 */
function input_string($key, $default = '') {
    $value = $_REQUEST[$key] ?? $default;
    return sql_escape($value);
}

/**
 * Get integer input from request (shorthand)
 * 
 * @param string $key The key to look for
 * @param int $default Default value if not found
 * @return int Safe integer
 * 
 * Usage: $id = input_int('id');
 */
function input_int($key, $default = 0) {
    $value = $_REQUEST[$key] ?? $default;
    return sql_int($value);
}

// ============================================================================
// FORM VALIDATION HELPERS
// Use these to validate form submissions
// ============================================================================

/**
 * Validate that required fields are present and non-empty
 * 
 * @param array $fields Array of field names that are required
 * @param array $data Data to validate (defaults to $_POST)
 * @return array Array of missing field names (empty if all present)
 * 
 * Usage: 
 *   $missing = validate_required(['name', 'email', 'phone']);
 *   if (!empty($missing)) { echo "Missing: " . implode(', ', $missing); }
 */
function validate_required($fields, $data = null) {
    if ($data === null) {
        $data = $_POST;
    }
    
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Thai format or international)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid phone format
 */
function validate_phone($phone) {
    // Remove spaces, dashes, parentheses
    $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);
    // Allow Thai mobile (0x-xxx-xxxx) or international (+66...)
    return preg_match('/^(\+?[0-9]{9,15}|0[0-9]{8,9})$/', $phone) === 1;
}

/**
 * Validate that a value is within a numeric range
 * 
 * @param mixed $value Value to validate
 * @param float $min Minimum value
 * @param float $max Maximum value
 * @return bool True if within range
 */
function validate_range($value, $min, $max) {
    $num = is_numeric($value) ? floatval($value) : null;
    if ($num === null) return false;
    return $num >= $min && $num <= $max;
}

/**
 * Validate string length
 * 
 * @param string $value String to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool True if length is within range
 */
function validate_length($value, $min, $max) {
    $len = mb_strlen($value);
    return $len >= $min && $len <= $max;
}

/**
 * Validate date format
 * 
 * @param string $date Date string to validate
 * @param string $format Expected format (default: d-m-Y)
 * @return bool True if valid date format
 */
function validate_date($date, $format = 'd-m-Y') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate that a value exists in an allowed list
 * 
 * @param mixed $value Value to check
 * @param array $allowed Array of allowed values
 * @return bool True if value is in allowed list
 */
function validate_in($value, $allowed) {
    return in_array($value, $allowed, true);
}

/**
 * Validate Thai tax ID (13 digits)
 * 
 * @param string $taxId Tax ID to validate
 * @return bool True if valid format
 */
function validate_tax_id($taxId) {
    // Remove dashes and spaces
    $taxId = preg_replace('/[\s\-]+/', '', $taxId);
    return preg_match('/^[0-9]{13}$/', $taxId) === 1;
}

/**
 * Sanitize filename for safe upload
 * 
 * @param string $filename Original filename
 * @return string Safe filename
 */
function sanitize_filename($filename) {
    // Remove path components
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename);
    // Prevent double extensions
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    return $filename;
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES element
 * @param array $options ['extensions' => [...], 'max_size' => bytes]
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validate_file_upload($file, $options = []) {
    $allowed = $options['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large (max ' . round($maxSize / 1024 / 1024) . 'MB)'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true, 'error' => null];
}

// ============================================================================
// PASSWORD SECURITY HELPERS
// Modern password hashing using bcrypt (replaces MD5)
// ============================================================================

/**
 * Hash a password using bcrypt
 * @param string $password Plain text password
 * @return string Hashed password (60+ characters)
 */
function password_hash_secure($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 * Also checks if it's a legacy MD5 hash
 * @param string $password Plain text password
 * @param string $hash Stored hash (bcrypt or MD5)
 * @param bool &$needsRehash Set to true if password needs migration
 * @return bool True if password matches
 */
function password_verify_secure($password, $hash, &$needsRehash = false) {
    // Check if it's a bcrypt hash (starts with $2y$ or $2a$)
    if (preg_match('/^\$2[aby]\$/', $hash)) {
        $valid = password_verify($password, $hash);
        $needsRehash = $valid && password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
        return $valid;
    }
    
    // Legacy MD5 check (32 character hex string)
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        $valid = (md5($password) === $hash);
        $needsRehash = $valid; // MD5 always needs rehash
        return $valid;
    }
    
    return false;
}

/**
 * Update user password to modern hash
 * @param mysqli $conn Database connection
 * @param int|string $userId User ID or email
 * @param string $newHash New bcrypt hash
 * @param string $idField Field name for user identifier (id or email)
 * @return bool Success
 */
function password_migrate($conn, $userId, $newHash, $idField = 'id') {
    $stmt = $conn->prepare("UPDATE authorize SET password = ?, password_migrated = 1 WHERE $idField = ?");
    if (!$stmt) return false;
    
    if ($idField === 'id') {
        $stmt->bind_param('si', $newHash, $userId);
    } else {
        $stmt->bind_param('ss', $newHash, $userId);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ============================================================================
// RATE LIMITING HELPERS
// Prevent brute force attacks on login
// ============================================================================

/**
 * Record a login attempt
 * @param mysqli $conn Database connection
 * @param string $username Username attempted
 * @param bool $successful Whether login was successful
 */
function record_login_attempt($conn, $username, $successful = false) {
    $ip = get_client_ip();
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, successful) VALUES (?, ?, ?)");
    if ($stmt) {
        $success = $successful ? 1 : 0;
        $stmt->bind_param('ssi', $ip, $username, $success);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Check if IP is rate limited
 * @param mysqli $conn Database connection
 * @param int $maxAttempts Max attempts allowed (default 5)
 * @param int $windowMinutes Time window in minutes (default 15)
 * @return array ['limited' => bool, 'remaining' => int, 'retry_after' => int seconds]
 */
function check_rate_limit($conn, $maxAttempts = 5, $windowMinutes = 15) {
    $ip = get_client_ip();
    $windowStart = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
    
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts, MIN(attempted_at) as first_attempt 
                            FROM login_attempts 
                            WHERE ip_address = ? AND attempted_at > ? AND successful = 0");
    if (!$stmt) {
        return ['limited' => false, 'remaining' => $maxAttempts, 'retry_after' => 0];
    }
    
    $stmt->bind_param('ss', $ip, $windowStart);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $attempts = (int)$row['attempts'];
    $remaining = max(0, $maxAttempts - $attempts);
    
    if ($attempts >= $maxAttempts) {
        $firstAttempt = strtotime($row['first_attempt']);
        $retryAfter = ($firstAttempt + ($windowMinutes * 60)) - time();
        return ['limited' => true, 'remaining' => 0, 'retry_after' => max(0, $retryAfter)];
    }
    
    return ['limited' => false, 'remaining' => $remaining, 'retry_after' => 0];
}

/**
 * Clear old login attempts (cleanup)
 * @param mysqli $conn Database connection
 * @param int $olderThanDays Delete attempts older than X days
 */
function cleanup_login_attempts($conn, $olderThanDays = 7) {
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    if ($stmt) {
        $stmt->bind_param('s', $cutoff);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Get client IP address
 * @return string IP address
 */
function get_client_ip() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated list (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// ============================================================================
// ACCOUNT LOCKOUT HELPERS
// Lock accounts after too many failed login attempts
// ============================================================================

/**
 * Check if account is locked
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @return array ['locked' => bool, 'until' => datetime string or null]
 */
function is_account_locked($conn, $email) {
    $stmt = $conn->prepare("SELECT locked_until FROM authorize WHERE email = ?");
    if (!$stmt) return ['locked' => false, 'until' => null];
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row || !$row['locked_until']) {
        return ['locked' => false, 'until' => null];
    }
    
    $lockedUntil = strtotime($row['locked_until']);
    if ($lockedUntil > time()) {
        return ['locked' => true, 'until' => $row['locked_until']];
    }
    
    // Lock expired, clear it
    unlock_account($conn, $email);
    return ['locked' => false, 'until' => null];
}

/**
 * Increment failed attempts and lock if threshold reached
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param int $maxAttempts Lock after this many attempts (default 10)
 * @param int $lockMinutes Lock duration in minutes (default 30)
 * @return bool True if account is now locked
 */
function increment_failed_attempts($conn, $email, $maxAttempts = 10, $lockMinutes = 30) {
    $stmt = $conn->prepare("UPDATE authorize SET failed_attempts = failed_attempts + 1 WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
    
    // Check if should lock
    $stmt = $conn->prepare("SELECT failed_attempts FROM authorize WHERE email = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['failed_attempts'] >= $maxAttempts) {
        lock_account($conn, $email, $lockMinutes);
        return true;
    }
    
    return false;
}

/**
 * Lock an account
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param int $minutes Lock duration in minutes
 */
function lock_account($conn, $email, $minutes = 30) {
    $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
    $stmt = $conn->prepare("UPDATE authorize SET locked_until = ? WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('ss', $lockedUntil, $email);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Unlock an account and reset failed attempts
 * @param mysqli $conn Database connection
 * @param string $email User email
 */
function unlock_account($conn, $email) {
    $stmt = $conn->prepare("UPDATE authorize SET locked_until = NULL, failed_attempts = 0 WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Reset failed attempts on successful login
 * @param mysqli $conn Database connection
 * @param string $email User email
 */
function reset_failed_attempts($conn, $email) {
    $stmt = $conn->prepare("UPDATE authorize SET failed_attempts = 0 WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
}

// ============================================================================
// PASSWORD RESET HELPERS
// Forgot password functionality
// ============================================================================

/**
 * Generate password reset token
 * @param mysqli $conn Database connection
 * @param string $email User email
 * @param int $expiresMinutes Token validity in minutes (default 60)
 * @return string|false Token on success, false on failure
 */
function generate_password_reset_token($conn, $email, $expiresMinutes = 60) {
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM authorize WHERE email = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return false; // User not found
    }
    $stmt->close();
    
    // Invalidate old tokens
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
    
    // Generate new token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresMinutes} minutes"));
    
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    
    $stmt->bind_param('sss', $email, $token, $expiresAt);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result ? $token : false;
}

/**
 * Verify password reset token
 * @param mysqli $conn Database connection
 * @param string $token Reset token
 * @return string|false Email if valid, false otherwise
 */
function verify_password_reset_token($conn, $token) {
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? AND used = 0");
    if (!$stmt) return false;
    
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return false;
    
    if (strtotime($row['expires_at']) < time()) {
        return false; // Token expired
    }
    
    return $row['email'];
}

/**
 * Reset password using token
 * @param mysqli $conn Database connection
 * @param string $token Reset token
 * @param string $newPassword New password (plain text)
 * @return bool Success
 */
function reset_password_with_token($conn, $token, $newPassword) {
    $email = verify_password_reset_token($conn, $token);
    if (!$email) return false;
    
    // Hash new password
    $hash = password_hash_secure($newPassword);
    
    // Update password
    $stmt = $conn->prepare("UPDATE authorize SET password = ?, password_migrated = 1, failed_attempts = 0, locked_until = NULL WHERE email = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param('ss', $hash, $email);
    $result = $stmt->execute();
    $stmt->close();
    
    if (!$result) return false;
    
    // Mark token as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }
    
    return true;
}

// ============================================================================
// REMEMBER ME HELPERS
// Persistent login functionality
// ============================================================================

/**
 * Generate remember me token and set cookie
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $days Cookie validity in days (default 30)
 * @return bool Success
 */
function create_remember_token($conn, $userId, $days = 30) {
    // Generate token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
    
    // Clean old tokens for this user
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ? OR expires_at < NOW()");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    // Store token hash
    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) return false;
    
    $stmt->bind_param('iss', $userId, $tokenHash, $expiresAt);
    $result = $stmt->execute();
    $stmt->close();
    
    if (!$result) return false;
    
    // Set cookie with plain token
    $cookieExpires = time() + ($days * 24 * 60 * 60);
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('remember_token', $token, $cookieExpires, '/', '', $secure, true);
    setcookie('remember_user', $userId, $cookieExpires, '/', '', $secure, true);
    
    return true;
}

/**
 * Verify remember me token and auto-login
 * @param mysqli $conn Database connection
 * @return array|false User data if valid, false otherwise
 */
function verify_remember_token($conn) {
    if (empty($_COOKIE['remember_token']) || empty($_COOKIE['remember_user'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    $userId = (int)$_COOKIE['remember_user'];
    $tokenHash = hash('sha256', $token);
    
    $stmt = $conn->prepare("SELECT rt.user_id, a.email, a.lang 
                            FROM remember_tokens rt 
                            JOIN authorize a ON rt.user_id = a.id 
                            WHERE rt.token_hash = ? AND rt.user_id = ? AND rt.expires_at > NOW()");
    if (!$stmt) return false;
    
    $stmt->bind_param('si', $tokenHash, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        // Invalid token, clear cookies
        clear_remember_token();
        return false;
    }
    
    return [
        'user_id' => $row['user_id'],
        'email' => $row['email'],
        'lang' => $row['lang']
    ];
}

/**
 * Clear remember me token and cookies
 * @param mysqli|null $conn Database connection (optional)
 * @param int|null $userId User ID (optional)
 */
function clear_remember_token($conn = null, $userId = null) {
    // Clear cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
    
    // Clear from database if connection provided
    if ($conn && $userId) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Cleanup expired tokens (call periodically)
 * @param mysqli $conn Database connection
 */
function cleanup_expired_tokens($conn) {
    $conn->query("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
    $conn->query("DELETE FROM remember_tokens WHERE expires_at < NOW()");
}
