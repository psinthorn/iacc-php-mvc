<?php

/**
 * AuditLog Class - Security Event Logging
 * 
 * Logs all security-related events to audit_logs table for compliance.
 * Supports JSON storage of old and new values for tracking changes.
 */
class AuditLog {
    private $db;
    private $user_id;
    private $ip_address;
    private $user_agent;
    
    /**
     * Constructor
     */
    public function __construct($db, $user_id = 0) {
        // Handle both DbConn wrapper and direct mysqli objects
        if (is_object($db) && property_exists($db, 'conn')) {
            // It's a DbConn wrapper object
            $this->db = $db->conn;
        } else {
            // It's a direct mysqli connection
            $this->db = $db;
        }
        
        $this->user_id = $user_id;
        $this->ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 255);
    }
    
    /**
     * Log an action
     */
    public function log($action, $table_name, $record_id, $old_values = null, $new_values = null) {
        try {
            $sql = "INSERT INTO audit_logs 
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("AuditLog: Prepare failed - " . $this->db->error);
                return false;
            }
            
            $old_json = $old_values ? json_encode($old_values) : null;
            $new_json = $new_values ? json_encode($new_values) : null;
            
            $stmt->bind_param(
                'isiiisss',
                $this->user_id,
                $action,
                $table_name,
                $record_id,
                $old_json,
                $new_json,
                $this->ip_address,
                $this->user_agent
            );
            
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("AuditLog Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all audit logs
     */
    public function getLogs($limit = 500, $offset = 0) {
        try {
            $sql = "SELECT al.*, u.username 
                    FROM audit_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $logs ?? [];
        } catch (Exception $e) {
            error_log("AuditLog getLogs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get logs for a specific user
     */
    public function getUserLogs($user_id, $limit = 100) {
        try {
            $sql = "SELECT * FROM audit_logs 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $logs ?? [];
        } catch (Exception $e) {
            error_log("AuditLog getUserLogs: " . $e->getMessage());
            return [];
        }
    }
}
