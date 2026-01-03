<?php
/**
 * Database Helper Class with Prepared Statement Support
 * 
 * This class provides a secure wrapper around mysqli with:
 * - Prepared statements for parameterized queries
 * - Transaction support
 * - Query logging (optional)
 * - Connection pooling ready
 * 
 * @version 1.0.0
 * @date 2026-01-03
 */

class Database {
    private static $instance = null;
    private $conn;
    private $inTransaction = false;
    private $queryLog = [];
    private $logQueries = false;
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - uses existing dbconn connection
     */
    private function __construct() {
        global $db;
        if ($db && isset($db->conn)) {
            $this->conn = $db->conn;
        } else {
            // Fallback: create new connection
            require_once dirname(__FILE__) . '/class.dbconn.php';
            $db = new dbconn();
            $this->conn = $db->conn;
        }
    }
    
    /**
     * Get the mysqli connection object
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a prepared statement with parameters
     * 
     * Usage:
     *   $db->query("SELECT * FROM users WHERE id = ?", [1]);
     *   $db->query("SELECT * FROM users WHERE name LIKE ? AND status = ?", ['%john%', 'active']);
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @return mysqli_result|bool
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        if (empty($params)) {
            // No parameters, execute directly
            $result = mysqli_query($this->conn, $sql);
        } else {
            // Use prepared statement
            $stmt = mysqli_prepare($this->conn, $sql);
            
            if (!$stmt) {
                $this->logError($sql, $params, mysqli_error($this->conn));
                return false;
            }
            
            // Bind parameters dynamically
            $types = $this->getParamTypes($params);
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            
            // Execute
            $success = mysqli_stmt_execute($stmt);
            
            if (!$success) {
                $this->logError($sql, $params, mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return false;
            }
            
            // Get result (for SELECT queries)
            $result = mysqli_stmt_get_result($stmt);
            
            // If no result (INSERT/UPDATE/DELETE), return success status
            if ($result === false && mysqli_stmt_affected_rows($stmt) >= 0) {
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                $insertId = mysqli_stmt_insert_id($stmt);
                mysqli_stmt_close($stmt);
                
                // Return object with affected rows info
                $resultObj = new stdClass();
                $resultObj->affected_rows = $affectedRows;
                $resultObj->insert_id = $insertId;
                $resultObj->success = true;
                
                if ($this->logQueries) {
                    $this->logQuery($sql, $params, microtime(true) - $startTime);
                }
                
                return $resultObj;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        if ($this->logQueries) {
            $this->logQuery($sql, $params, microtime(true) - $startTime);
        }
        
        return $result;
    }
    
    /**
     * Fetch single row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|null
     */
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($result && $result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $row;
        }
        
        return null;
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $rows = [];
        
        if ($result && $result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            mysqli_free_result($result);
        }
        
        return $rows;
    }
    
    /**
     * Fetch single value (first column of first row)
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed|null
     */
    public function fetchValue($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($result && $result instanceof mysqli_result) {
            $row = mysqli_fetch_row($result);
            mysqli_free_result($result);
            return $row ? $row[0] : null;
        }
        
        return null;
    }
    
    /**
     * Execute INSERT and return insert ID
     * 
     * @param string $sql INSERT query
     * @param array $params Parameters
     * @return int|false Insert ID or false on failure
     */
    public function insert($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($result && isset($result->insert_id)) {
            return $result->insert_id;
        }
        
        return mysqli_insert_id($this->conn);
    }
    
    /**
     * Execute UPDATE/DELETE and return affected rows
     * 
     * @param string $sql UPDATE/DELETE query
     * @param array $params Parameters
     * @return int|false Affected rows or false on failure
     */
    public function execute($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($result && isset($result->affected_rows)) {
            return $result->affected_rows;
        }
        
        if ($result !== false) {
            return mysqli_affected_rows($this->conn);
        }
        
        return false;
    }
    
    // =========================================================================
    // Transaction Support
    // =========================================================================
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        if ($this->inTransaction) {
            return false; // Already in transaction
        }
        
        mysqli_begin_transaction($this->conn);
        $this->inTransaction = true;
        return true;
    }
    
    /**
     * Commit the current transaction
     * 
     * @return bool
     */
    public function commit() {
        if (!$this->inTransaction) {
            return false;
        }
        
        $result = mysqli_commit($this->conn);
        $this->inTransaction = false;
        return $result;
    }
    
    /**
     * Rollback the current transaction
     * 
     * @return bool
     */
    public function rollback() {
        if (!$this->inTransaction) {
            return false;
        }
        
        $result = mysqli_rollback($this->conn);
        $this->inTransaction = false;
        return $result;
    }
    
    /**
     * Check if in transaction
     * 
     * @return bool
     */
    public function isInTransaction() {
        return $this->inTransaction;
    }
    
    /**
     * Execute callback within a transaction
     * 
     * Usage:
     *   $db->transaction(function($db) {
     *       $db->execute("INSERT INTO orders ...");
     *       $db->execute("UPDATE inventory ...");
     *   });
     * 
     * @param callable $callback
     * @return mixed Callback result
     * @throws Exception If callback fails
     */
    public function transaction($callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    // =========================================================================
    // Helper Methods
    // =========================================================================
    
    /**
     * Escape a string for safe SQL use
     * 
     * @param string $value
     * @return string
     */
    public function escape($value) {
        return mysqli_real_escape_string($this->conn, $value);
    }
    
    /**
     * Get last error message
     * 
     * @return string
     */
    public function getLastError() {
        return mysqli_error($this->conn);
    }
    
    /**
     * Get last error number
     * 
     * @return int
     */
    public function getLastErrorNo() {
        return mysqli_errno($this->conn);
    }
    
    /**
     * Get last insert ID
     * 
     * @return int
     */
    public function getLastInsertId() {
        return mysqli_insert_id($this->conn);
    }
    
    /**
     * Get affected rows from last query
     * 
     * @return int
     */
    public function getAffectedRows() {
        return mysqli_affected_rows($this->conn);
    }
    
    /**
     * Enable/disable query logging
     * 
     * @param bool $enable
     */
    public function setQueryLogging($enable) {
        $this->logQueries = $enable;
    }
    
    /**
     * Get query log
     * 
     * @return array
     */
    public function getQueryLog() {
        return $this->queryLog;
    }
    
    /**
     * Clear query log
     */
    public function clearQueryLog() {
        $this->queryLog = [];
    }
    
    // =========================================================================
    // Private Methods
    // =========================================================================
    
    /**
     * Determine parameter types for bind_param
     * 
     * @param array $params
     * @return string
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i'; // integer
            } elseif (is_float($param)) {
                $types .= 'd'; // double
            } elseif (is_null($param)) {
                $types .= 's'; // treat null as string
            } else {
                $types .= 's'; // string (default)
            }
        }
        return $types;
    }
    
    /**
     * Log a query for debugging
     * 
     * @param string $sql
     * @param array $params
     * @param float $duration
     */
    private function logQuery($sql, $params, $duration) {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => round($duration * 1000, 2) . 'ms',
            'time' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Log an error
     * 
     * @param string $sql
     * @param array $params
     * @param string $error
     */
    private function logError($sql, $params, $error) {
        error_log("[Database Error] SQL: $sql | Params: " . json_encode($params) . " | Error: $error");
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// =========================================================================
// Helper Functions for Easy Access
// =========================================================================

/**
 * Get Database instance
 * 
 * @return Database
 */
function db() {
    return Database::getInstance();
}

/**
 * Execute query with prepared statement
 * 
 * @param string $sql
 * @param array $params
 * @return mysqli_result|object|bool
 */
function db_query($sql, $params = []) {
    return Database::getInstance()->query($sql, $params);
}

/**
 * Fetch single row
 * 
 * @param string $sql
 * @param array $params
 * @return array|null
 */
function db_fetch_one($sql, $params = []) {
    return Database::getInstance()->fetchOne($sql, $params);
}

/**
 * Fetch all rows
 * 
 * @param string $sql
 * @param array $params
 * @return array
 */
function db_fetch_all($sql, $params = []) {
    return Database::getInstance()->fetchAll($sql, $params);
}

/**
 * Fetch single value
 * 
 * @param string $sql
 * @param array $params
 * @return mixed|null
 */
function db_fetch_value($sql, $params = []) {
    return Database::getInstance()->fetchValue($sql, $params);
}

/**
 * Execute INSERT and return insert ID
 * 
 * @param string $sql
 * @param array $params
 * @return int|false
 */
function db_insert($sql, $params = []) {
    return Database::getInstance()->insert($sql, $params);
}

/**
 * Execute UPDATE/DELETE and return affected rows
 * 
 * @param string $sql
 * @param array $params
 * @return int|false
 */
function db_execute($sql, $params = []) {
    return Database::getInstance()->execute($sql, $params);
}
