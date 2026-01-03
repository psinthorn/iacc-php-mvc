<?php
/**
 * Audit Log Helper Functions
 * Track user actions for accountability
 * 
 * Table structure (audit_logs):
 * - id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at
 */

/**
 * Log an action to the audit log
 * 
 * @param mysqli $conn Database connection
 * @param string $action Action type (create, update, delete, login, logout, view, export)
 * @param string $table_name Type of entity/table (company, invoice, po, pr, user, session, etc.)
 * @param int|null $record_id ID of the record (optional)
 * @param array|null $old_values Previous values (for updates)
 * @param array|null $new_values New values (for creates/updates)
 */
function audit_log($conn, $action, $table_name, $record_id = null, $old_values = null, $new_values = null) {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    // Convert arrays to JSON for storage
    $old_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
    $new_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
    
    $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ississss', 
            $user_id, 
            $action, 
            $table_name, 
            $record_id, 
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
    audit_log($conn, $action, 'session', $user_id);
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
function audit_create($conn, $table_name, $record_id, $new_values = null) {
    audit_log($conn, 'create', $table_name, $record_id, null, $new_values);
}

/**
 * Log an update event
 */
function audit_update($conn, $table_name, $record_id, $old_values = null, $new_values = null) {
    audit_log($conn, 'update', $table_name, $record_id, $old_values, $new_values);
}

/**
 * Log a delete event
 */
function audit_delete($conn, $table_name, $record_id, $old_values = null) {
    audit_log($conn, 'delete', $table_name, $record_id, $old_values, null);
}

/**
 * Log a view/access event (for sensitive data)
 */
function audit_view($conn, $table_name, $record_id = null) {
    audit_log($conn, 'view', $table_name, $record_id);
}

/**
 * Log an export event
 */
function audit_export($conn, $table_name, $description = null) {
    audit_log($conn, 'export', $table_name, null, null, ['description' => $description]);
}

/**
 * Get recent audit logs with user info
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of records to return
 * @param array $filters Optional filters (user_id, table_name, action, date_from, date_to)
 * @return array Audit log entries
 */
function get_audit_logs($conn, $limit = 100, $filters = []) {
    $where = "1=1";
    
    if (!empty($filters['user_id'])) {
        $user_id = intval($filters['user_id']);
        $where .= " AND a.user_id = $user_id";
    }
    
    if (!empty($filters['entity_type'])) {
        $table_name = mysqli_real_escape_string($conn, $filters['entity_type']);
        $where .= " AND a.table_name = '$table_name'";
    }
    
    if (!empty($filters['action'])) {
        $action = mysqli_real_escape_string($conn, $filters['action']);
        $where .= " AND a.action = '$action'";
    }
    
    if (!empty($filters['date_from'])) {
        $date_from = mysqli_real_escape_string($conn, $filters['date_from']);
        $where .= " AND DATE(a.created_at) >= '$date_from'";
    }
    
    if (!empty($filters['date_to'])) {
        $date_to = mysqli_real_escape_string($conn, $filters['date_to']);
        $where .= " AND DATE(a.created_at) <= '$date_to'";
    }
    
    $limit = intval($limit);
    
    // Join with authorize table to get user email
    $sql = "SELECT a.*, 
                   COALESCE(u.email, CONCAT('User #', a.user_id)) as user_email,
                   a.table_name as entity_type,
                   a.record_id as entity_id
            FROM audit_logs a
            LEFT JOIN authorize u ON a.user_id = u.id
            WHERE $where 
            ORDER BY a.created_at DESC 
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}
