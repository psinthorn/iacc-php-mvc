<?php
// Helper function to generate base URL
if (!function_exists('base_url')) {
    function base_url($path = '') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domain = $_SERVER['HTTP_HOST'];
        return $protocol . $domain . '/' . $path;
    }
}

// Helper function to check if user has permission
if (!function_exists('can')) {
    function can($permission) {
        global $db;
        if (!isset($db)) {
            return false;
        }
        return $db->can($permission);
    }
}

// Helper function to check if user has role
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return $_SESSION['role'] ?? null === $role;
    }
}

// Helper function to translate text
if (!function_exists('trans')) {
    function trans($key, $default = '') {
        global $translations;
        if (isset($translations[$key])) {
            return $translations[$key];
        }
        return $default ?: $key;
    }
}

// Helper function for form CSRF token
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Helper function to render CSRF input
if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

// Helper function to format currency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2) . ' ฿';
    }
}

// Helper function to format date
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'M d, Y') {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        return date($format, $date);
    }
}

// Helper function to format datetime
if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'M d, Y H:i') {
        if (is_string($datetime)) {
            $datetime = strtotime($datetime);
        }
        return date($format, $datetime);
    }
}

// Helper function to get flash message
if (!function_exists('flash')) {
    function flash($message, $type = 'success') {
        $_SESSION['flash_' . $type] = $message;
    }
}

// Helper function to get user avatar
if (!function_exists('getAvatar')) {
    function getAvatar($name, $size = 40) {
        $url = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=" . $size . "&background=3b82f6&color=fff";
        return $url;
    }
}

// Helper function to check if current page is active
if (!function_exists('isActive')) {
    function isActive($path) {
        $current = $_SERVER['REQUEST_URI'];
        return strpos($current, $path) !== false ? 'active' : '';
    }
}

// ============================================================================
// PHASE 2: AUTHORIZATION HELPER FUNCTIONS (RBAC)
// ============================================================================

/**
 * Check if user has a specific permission
 * 
 * @param string $permission Permission key (e.g., 'po.create')
 * @return bool True if user has permission
 */
if (!function_exists('auth_can')) {
    function auth_can($permission) {
        global $authorization;
        if (!isset($authorization)) {
            return false;
        }
        return $authorization->can($permission);
    }
}

/**
 * Check if user does NOT have a permission
 * 
 * @param string $permission Permission key
 * @return bool True if user does NOT have permission
 */
if (!function_exists('auth_cannot')) {
    function auth_cannot($permission) {
        global $authorization;
        if (!isset($authorization)) {
            return true;
        }
        return $authorization->cannot($permission);
    }
}

/**
 * Check if user has a specific role
 * 
 * @param string $role Role name (e.g., 'Admin')
 * @return bool True if user has role
 */
if (!function_exists('auth_has_role')) {
    function auth_has_role($role) {
        global $authorization;
        if (!isset($authorization)) {
            return false;
        }
        return $authorization->hasRole($role);
    }
}

/**
 * Check if user has ANY of multiple roles
 * 
 * @param array $roles Array of role names
 * @return bool True if user has at least one role
 */
if (!function_exists('auth_has_any_role')) {
    function auth_has_any_role($roles) {
        global $authorization;
        if (!isset($authorization)) {
            return false;
        }
        return $authorization->hasAnyRole($roles);
    }
}

/**
 * Check if user has ALL of multiple roles
 * 
 * @param array $roles Array of role names
 * @return bool True if user has all roles
 */
if (!function_exists('auth_has_all_roles')) {
    function auth_has_all_roles($roles) {
        global $authorization;
        if (!isset($authorization)) {
            return false;
        }
        return $authorization->hasAllRoles($roles);
    }
}

/**
 * Get user's roles
 * 
 * @return array Array of role names
 */
if (!function_exists('auth_get_roles')) {
    function auth_get_roles() {
        global $authorization;
        if (!isset($authorization)) {
            return [];
        }
        return $authorization->getRoles();
    }
}

/**
 * Get user's permissions
 * 
 * @return array Array of permission keys
 */
if (!function_exists('auth_get_permissions')) {
    function auth_get_permissions() {
        global $authorization;
        if (!isset($authorization)) {
            return [];
        }
        return $authorization->getPermissions();
    }
}

/**
 * Check if user is Admin
 * 
 * @return bool True if user is Admin
 */
if (!function_exists('auth_is_admin')) {
    function auth_is_admin() {
        global $authorization;
        if (!isset($authorization)) {
            return false;
        }
        return $authorization->isAdmin();
    }
}

/**
 * Require a permission or redirect
 * Redirects user to dashboard if they don't have the permission
 * 
 * @param string $permission Permission key
 * @param string $redirect_url URL to redirect to if denied
 * @return void
 */
if (!function_exists('auth_require')) {
    function auth_require($permission, $redirect_url = '?page=dashboard') {
        global $authorization;
        if (!isset($authorization) || $authorization->cannot($permission)) {
            header("Location: $redirect_url");
            exit;
        }
    }
}

/**
 * Require multiple permissions (AND logic)
 * 
 * @param array $permissions Array of permission keys
 * @param string $redirect_url URL to redirect to if denied
 * @return void
 */
if (!function_exists('auth_require_all')) {
    function auth_require_all($permissions, $redirect_url = '?page=dashboard') {
        global $authorization;
        if (!isset($authorization) || !$authorization->canAll($permissions)) {
            header("Location: $redirect_url");
            exit;
        }
    }
}

/**
 * Require at least one permission (OR logic)
 * 
 * @param array $permissions Array of permission keys
 * @param string $redirect_url URL to redirect to if denied
 * @return void
 */
if (!function_exists('auth_require_any')) {
    function auth_require_any($permissions, $redirect_url = '?page=dashboard') {
        global $authorization;
        if (!isset($authorization) || !$authorization->canAny($permissions)) {
            header("Location: $redirect_url");
            exit;
        }
    }
}

/**
 * Conditionally render HTML if user has permission
 * Returns HTML if user has permission, empty string otherwise
 * 
 * @param string $permission Permission key
 * @param string $html HTML to render if authorized
 * @return string HTML or empty string
 */
if (!function_exists('auth_if')) {
    function auth_if($permission, $html) {
        global $authorization;
        if (!isset($authorization)) {
            return '';
        }
        return $authorization->can($permission) ? $html : '';
    }
}

/**
 * Conditionally render HTML if user does NOT have permission
 * 
 * @param string $permission Permission key
 * @param string $html HTML to render if NOT authorized
 * @return string HTML or empty string
 */
if (!function_exists('auth_if_not')) {
    function auth_if_not($permission, $html) {
        global $authorization;
        if (!isset($authorization)) {
            return $html;
        }
        return $authorization->cannot($permission) ? $html : '';
    }
}

/**
 * Conditionally render HTML if user has role
 * 
 * @param string $role Role name
 * @param string $html HTML to render if user has role
 * @return string HTML or empty string
 */
if (!function_exists('auth_if_role')) {
    function auth_if_role($role, $html) {
        global $authorization;
        if (!isset($authorization)) {
            return '';
        }
        return $authorization->hasRole($role) ? $html : '';
    }
}

/**
 * Log an audit action
 * 
 * @param string $action Action name
 * @param string $table Table name
 * @param int $record_id Record ID
 * @param array $old_values Old values
 * @param array $new_values New values
 * @return bool Success
 */
if (!function_exists('audit_log')) {
    function audit_log($action, $table, $record_id, $old_values = null, $new_values = null) {
        global $audit_log;
        if (!isset($audit_log)) {
            return false;
        }
        return $audit_log->log($action, $table, $record_id, $old_values, $new_values);
    }
}
