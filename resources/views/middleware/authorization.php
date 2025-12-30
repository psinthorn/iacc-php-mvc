<?php

/**
 * Authorization Middleware
 * 
 * Provides functions to check permissions and enforce access control
 * throughout the application
 * 
 * @package iACC
 * @author Development Team
 * @since v1.9
 */

/**
 * Require a specific permission
 * Redirects to error page if user doesn't have it
 * 
 * @param string|array $permission Permission key or array of keys
 * @param string $redirect_url Where to redirect if denied (default: dashboard)
 * @return void
 */
function require_permission($permission, $redirect_url = '?page=dashboard') {
    global $auth, $audit;
    
    if (!$auth) {
        redirect('?page=login', 'Session expired. Please login again.');
    }
    
    if (is_array($permission)) {
        if (!$auth->canAny($permission)) {
            log_unauthorized_access('permission', $permission);
            show_forbidden_error();
        }
    } else {
        if (!$auth->cannot($permission)) {
            log_unauthorized_access('permission', $permission);
            show_forbidden_error();
        }
    }
}

/**
 * Require a specific role
 * Redirects to error page if user doesn't have it
 * 
 * @param string|array $role Role name or array of names
 * @return void
 */
function require_role($role) {
    global $auth;
    
    if (!$auth) {
        redirect('?page=login', 'Session expired. Please login again.');
    }
    
    if (is_array($role)) {
        if (!$auth->hasAnyRole($role)) {
            log_unauthorized_access('role', $role);
            show_forbidden_error();
        }
    } else {
        if (!$auth->hasRole($role)) {
            log_unauthorized_access('role', $role);
            show_forbidden_error();
        }
    }
}

/**
 * Log unauthorized access attempt
 * 
 * @param string $type Type of check (permission|role)
 * @param string|array $requirement What was required
 * @return void
 */
function log_unauthorized_access($type, $requirement) {
    global $auth, $audit;
    
    if (!$auth || !$audit) {
        return;
    }
    
    $requirement_str = is_array($requirement) ? implode(',', $requirement) : $requirement;
    
    if ($audit) {
        $audit->log(
            'UNAUTHORIZED_ACCESS',
            'permission_check',
            null,
            null,
            [
                'type' => $type,
                'requirement' => $requirement_str,
                'page' => $_GET['page'] ?? 'unknown',
                'requested_url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]
        );
    }
}

/**
 * Display forbidden error page (403)
 * 
 * @return void
 */
function show_forbidden_error() {
    http_response_code(403);
    include_once 'errors/403.php';
    exit;
}

/**
 * Display unauthorized error page (401)
 * 
 * @return void
 */
function show_unauthorized_error() {
    http_response_code(401);
    include_once 'errors/401.php';
    exit;
}

/**
 * Check if current user can access a menu item
 * 
 * @param string|array $permission Permission key(s)
 * @param string|array $role Role name(s)
 * @return bool True if user can access
 */
function can_access_menu($permission = null, $role = null) {
    global $auth;
    
    if (!$auth) {
        return false;
    }
    
    // If permission specified, check it
    if ($permission) {
        if (is_array($permission)) {
            if (!$auth->canAny($permission)) {
                return false;
            }
        } else {
            if ($auth->cannot($permission)) {
                return false;
            }
        }
    }
    
    // If role specified, check it
    if ($role) {
        if (is_array($role)) {
            if (!$auth->hasAnyRole($role)) {
                return false;
            }
        } else {
            if (!$auth->hasRole($role)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Audit a user action
 * Call this after creating/updating/deleting data
 * 
 * @param string $action Action type (CREATE|UPDATE|DELETE|EXPORT|etc)
 * @param string $table_name Table name
 * @param int $record_id Record ID
 * @param array $old_values Old values (for updates)
 * @param array $new_values New values
 * @return bool Success
 */
function audit_action($action, $table_name, $record_id, $old_values = null, $new_values = null) {
    global $audit;
    
    if (!$audit) {
        return false;
    }
    
    return $audit->log($action, $table_name, $record_id, $old_values, $new_values);
}

/**
 * Check and log page access
 * Call at top of every protected page
 * 
 * @param string|array $permission Required permission(s)
 * @param string $action Action name for audit log
 * @return bool True if access granted
 */
function check_page_access($permission = null, $action = 'PAGE_VIEW') {
    global $auth, $audit;
    
    if (!$auth) {
        return false;
    }
    
    // Check permission if specified
    if ($permission) {
        if (is_array($permission)) {
            if (!$auth->canAny($permission)) {
                return false;
            }
        } else {
            if ($auth->cannot($permission)) {
                return false;
            }
        }
    }
    
    // Log the page view
    if ($audit) {
        $audit->log(
            $action,
            'page_view',
            null,
            null,
            [
                'page' => $_GET['page'] ?? 'unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]
        );
    }
    
    return true;
}
