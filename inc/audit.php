<?php
/**
 * Audit Log Helper Functions
 * Track user actions for accountability
 */

/**
 * Log an action to the audit log
 * 
 * @param mysqli $conn Database connection
 * @param string $action Action type (create, update, delete, login, logout, view, export)
 * @param string $entity_type Type of entity (company, invoice, po, pr, user, etc.)
 * @param int|null $entity_id ID of the entity (optional)
 * @param string|null $entity_name Name/description of the entity (optional)
 * @param array|null $old_values Previous values (for updates)
 * @param array|null $new_values New values (for creates/updates)
 */
function audit_log($conn, $action, $entity_type, $entity_id = null, $entity_name = null, $old_values = null, $new_values = null) {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'system';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    
    // Convert arrays to JSON for storage
    $old_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
    $new_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
    
    $sql = "INSERT INTO audit_log (user_id, user_email, action, entity_type, entity_id, entity_name, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssssssss', 
            $user_id, 
            $user_email, 
            $action, 
            $entity_type, 
            $entity_id, 
            $entity_name, 
            $old_json, 
            $new_json, 
            $ip_address, 
            $user_agent
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Log a login event
 */
function audit_login($conn, $user_id, $user_email, $success = true) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $user_email;
    $action = $success ? 'login' : 'login_failed';
    audit_log($conn, $action, 'session', $user_id, $user_email);
}

/**
 * Log a logout event
 */
function audit_logout($conn) {
    audit_log($conn, 'logout', 'session');
}

/**
 * Log a create event
 */
function audit_create($conn, $entity_type, $entity_id, $entity_name = null, $new_values = null) {
    audit_log($conn, 'create', $entity_type, $entity_id, $entity_name, null, $new_values);
}

/**
 * Log an update event
 */
function audit_update($conn, $entity_type, $entity_id, $entity_name = null, $old_values = null, $new_values = null) {
    audit_log($conn, 'update', $entity_type, $entity_id, $entity_name, $old_values, $new_values);
}

/**
 * Log a delete event
 */
function audit_delete($conn, $entity_type, $entity_id, $entity_name = null, $old_values = null) {
    audit_log($conn, 'delete', $entity_type, $entity_id, $entity_name, $old_values, null);
}

/**
 * Log a view/access event (for sensitive data)
 */
function audit_view($conn, $entity_type, $entity_id, $entity_name = null) {
    audit_log($conn, 'view', $entity_type, $entity_id, $entity_name);
}

/**
 * Log an export event
 */
function audit_export($conn, $entity_type, $description = null) {
    audit_log($conn, 'export', $entity_type, null, $description);
}

/**
 * Get recent audit logs
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of records to return
 * @param array $filters Optional filters (user_id, entity_type, action, date_from, date_to)
 * @return array Audit log entries
 */
function get_audit_logs($conn, $limit = 100, $filters = []) {
    $where = "1=1";
    
    if (!empty($filters['user_id'])) {
        $user_id = intval($filters['user_id']);
        $where .= " AND user_id = $user_id";
    }
    
    if (!empty($filters['entity_type'])) {
        $entity_type = mysqli_real_escape_string($conn, $filters['entity_type']);
        $where .= " AND entity_type = '$entity_type'";
    }
    
    if (!empty($filters['action'])) {
        $action = mysqli_real_escape_string($conn, $filters['action']);
        $where .= " AND action = '$action'";
    }
    
    if (!empty($filters['date_from'])) {
        $date_from = mysqli_real_escape_string($conn, $filters['date_from']);
        $where .= " AND DATE(created_at) >= '$date_from'";
    }
    
    if (!empty($filters['date_to'])) {
        $date_to = mysqli_real_escape_string($conn, $filters['date_to']);
        $where .= " AND DATE(created_at) <= '$date_to'";
    }
    
    $limit = intval($limit);
    $sql = "SELECT * FROM audit_log WHERE $where ORDER BY created_at DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}
