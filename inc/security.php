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
