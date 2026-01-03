<?php
/**
 * System Monitoring Helper
 * Provides metrics, health checks, and monitoring utilities
 * 
 * @version 1.0
 */

/**
 * Get system health status
 * 
 * @param mysqli $conn Database connection
 * @return array Health status for each component
 */
function get_system_health($conn) {
    $health = [
        'overall' => 'healthy',
        'components' => []
    ];
    
    // Database check
    $db_status = 'healthy';
    $db_message = '';
    try {
        $result = mysqli_query($conn, "SELECT 1");
        if (!$result) {
            $db_status = 'unhealthy';
            $db_message = mysqli_error($conn);
        }
    } catch (Exception $e) {
        $db_status = 'unhealthy';
        $db_message = $e->getMessage();
    }
    
    $health['components']['database'] = [
        'status' => $db_status,
        'message' => $db_message
    ];
    
    // Disk space check
    $disk_free = disk_free_space('/');
    $disk_total = disk_total_space('/');
    $disk_percent = ($disk_total > 0) ? round((1 - $disk_free / $disk_total) * 100, 1) : 0;
    
    $health['components']['disk'] = [
        'status' => $disk_percent > 90 ? 'warning' : ($disk_percent > 95 ? 'unhealthy' : 'healthy'),
        'used_percent' => $disk_percent,
        'free_bytes' => $disk_free,
        'total_bytes' => $disk_total
    ];
    
    // Log directory writable
    $log_dir = dirname(__DIR__) . '/logs/';
    $health['components']['logs'] = [
        'status' => is_writable($log_dir) ? 'healthy' : 'unhealthy',
        'path' => $log_dir
    ];
    
    // Upload directory writable
    $upload_dir = dirname(__DIR__) . '/upload/';
    $health['components']['uploads'] = [
        'status' => is_writable($upload_dir) ? 'healthy' : 'unhealthy',
        'path' => $upload_dir
    ];
    
    // Cache directory
    $cache_dir = dirname(__DIR__) . '/cache/';
    $health['components']['cache'] = [
        'status' => is_writable($cache_dir) ? 'healthy' : 'unhealthy',
        'path' => $cache_dir
    ];
    
    // Check if any component is unhealthy
    foreach ($health['components'] as $component) {
        if ($component['status'] === 'unhealthy') {
            $health['overall'] = 'unhealthy';
            break;
        } elseif ($component['status'] === 'warning' && $health['overall'] !== 'unhealthy') {
            $health['overall'] = 'warning';
        }
    }
    
    return $health;
}

/**
 * Get database statistics
 * 
 * @param mysqli $conn Database connection
 * @return array Database stats
 */
function get_database_stats($conn) {
    $stats = [];
    
    // Table sizes
    $result = mysqli_query($conn, "
        SELECT 
            table_name,
            table_rows,
            ROUND(data_length / 1024 / 1024, 2) AS data_mb,
            ROUND(index_length / 1024 / 1024, 2) AS index_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
        ORDER BY data_length DESC
        LIMIT 20
    ");
    
    $stats['tables'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['tables'][] = $row;
    }
    
    // Total database size
    $result = mysqli_query($conn, "
        SELECT 
            ROUND(SUM(data_length) / 1024 / 1024, 2) AS total_data_mb,
            ROUND(SUM(index_length) / 1024 / 1024, 2) AS total_index_mb,
            COUNT(*) as table_count
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    $stats['totals'] = mysqli_fetch_assoc($result);
    
    // Connection stats
    $result = mysqli_query($conn, "SHOW STATUS LIKE 'Connections'");
    $row = mysqli_fetch_assoc($result);
    $stats['connections'] = $row['Value'] ?? 0;
    
    // Slow queries
    $result = mysqli_query($conn, "SHOW STATUS LIKE 'Slow_queries'");
    $row = mysqli_fetch_assoc($result);
    $stats['slow_queries'] = $row['Value'] ?? 0;
    
    return $stats;
}

/**
 * Get recent slow queries from log file
 * 
 * @param int $limit Number of entries
 * @return array Recent slow queries
 */
function get_slow_queries($limit = 20) {
    $log_file = dirname(__DIR__) . '/logs/slow_queries.log';
    
    if (!file_exists($log_file)) {
        return [];
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_slice($lines, -$limit);
    
    $queries = [];
    foreach (array_reverse($lines) as $line) {
        if (preg_match('/^\[(.*?)\] SLOW QUERY \(([\d.]+)s\): (.*)$/', $line, $matches)) {
            $queries[] = [
                'timestamp' => $matches[1],
                'duration' => $matches[2],
                'query' => $matches[3]
            ];
        }
    }
    
    return $queries;
}

/**
 * Get recent errors from log file
 * 
 * @param int $limit Number of entries
 * @return array Recent errors
 */
function get_recent_errors($limit = 50) {
    $log_file = dirname(__DIR__) . '/logs/error.log';
    
    if (!file_exists($log_file)) {
        return [];
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_slice($lines, -$limit);
    
    return array_reverse($lines);
}

/**
 * Get login activity summary
 * 
 * @param mysqli $conn Database connection
 * @param int $days Number of days to look back
 * @return array Login statistics
 */
function get_login_activity($conn, $days = 7) {
    $stats = [];
    
    // Daily login counts
    $result = mysqli_query($conn, "
        SELECT 
            DATE(created_at) as date,
            SUM(CASE WHEN action = 'login' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) as failed
        FROM audit_logs
        WHERE action IN ('login', 'login_failed')
        AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
    $stats['daily'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['daily'][] = $row;
    }
    
    // Unique users today
    $result = mysqli_query($conn, "
        SELECT COUNT(DISTINCT user_id) as unique_users
        FROM audit_logs
        WHERE action = 'login'
        AND DATE(created_at) = CURDATE()
    ");
    $row = mysqli_fetch_assoc($result);
    $stats['unique_today'] = $row['unique_users'] ?? 0;
    
    // Failed attempts by IP (potential brute force)
    $result = mysqli_query($conn, "
        SELECT ip_address, COUNT(*) as attempts
        FROM audit_logs
        WHERE action = 'login_failed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address
        HAVING attempts >= 5
        ORDER BY attempts DESC
        LIMIT 10
    ");
    
    $stats['suspicious_ips'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['suspicious_ips'][] = $row;
    }
    
    return $stats;
}

/**
 * Get application metrics for dashboard
 * 
 * @param mysqli $conn Database connection
 * @return array Application metrics
 */
function get_app_metrics($conn) {
    $metrics = [];
    
    // Total records counts
    $tables = ['company', 'iv', 'receipt', 'voucher', 'product', 'pr', 'authorize'];
    
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $table");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $metrics['counts'][$table] = $row['cnt'] ?? 0;
        }
    }
    
    // Recent activity (last 24h)
    $result = mysqli_query($conn, "
        SELECT action, COUNT(*) as count
        FROM audit_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY action
        ORDER BY count DESC
    ");
    
    $metrics['activity_24h'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $metrics['activity_24h'][$row['action']] = $row['count'];
    }
    
    // Revenue this month (if iv table has total)
    $result = mysqli_query($conn, "
        SELECT COALESCE(SUM(total), 0) as revenue
        FROM iv
        WHERE MONTH(createdate) = MONTH(CURDATE())
        AND YEAR(createdate) = YEAR(CURDATE())
        AND payment_status IN ('paid', 'partial')
    ");
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $metrics['revenue_mtd'] = $row['revenue'] ?? 0;
    }
    
    return $metrics;
}

/**
 * Enable MySQL slow query logging (requires SUPER privilege)
 * 
 * @param mysqli $conn Database connection
 * @param int $threshold Slow query threshold in seconds
 * @return bool Success
 */
function enable_slow_query_log($conn, $threshold = 1) {
    try {
        mysqli_query($conn, "SET GLOBAL slow_query_log = 'ON'");
        mysqli_query($conn, "SET GLOBAL long_query_time = $threshold");
        mysqli_query($conn, "SET GLOBAL log_queries_not_using_indexes = 'ON'");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get log file sizes
 * 
 * @return array Log file information
 */
function get_log_stats() {
    $log_dir = dirname(__DIR__) . '/logs/';
    $logs = [];
    
    if (!is_dir($log_dir)) {
        return $logs;
    }
    
    $files = glob($log_dir . '*.log');
    
    foreach ($files as $file) {
        $logs[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'size_formatted' => format_bytes(filesize($file)),
            'modified' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    return $logs;
}

/**
 * Format bytes to human readable
 * 
 * @param int $bytes Bytes
 * @return string Formatted string
 */
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Rotate log files if too large
 * 
 * @param int $max_size Max size in bytes before rotation (default 10MB)
 */
function rotate_logs($max_size = 10485760) {
    $log_dir = dirname(__DIR__) . '/logs/';
    $files = glob($log_dir . '*.log');
    
    foreach ($files as $file) {
        if (filesize($file) > $max_size) {
            $rotated = $file . '.' . date('Ymd_His') . '.bak';
            rename($file, $rotated);
            
            // Keep only last 5 rotated files per log type
            $pattern = $file . '.*.bak';
            $rotated_files = glob($pattern);
            if (count($rotated_files) > 5) {
                array_multisort(array_map('filemtime', $rotated_files), SORT_DESC, $rotated_files);
                foreach (array_slice($rotated_files, 5) as $old_file) {
                    @unlink($old_file);
                }
            }
        }
    }
}
