<?php
/**
 * Performance Helper Functions
 * Query optimization, caching, and monitoring
 * 
 * @version 1.0
 */

/**
 * Simple query result cache using APCu or file-based fallback
 */
class QueryCache {
    private static $cache_dir = null;
    private static $default_ttl = 300; // 5 minutes
    
    /**
     * Initialize cache directory
     */
    private static function init() {
        if (self::$cache_dir === null) {
            self::$cache_dir = dirname(__DIR__) . '/cache/queries/';
            if (!is_dir(self::$cache_dir)) {
                @mkdir(self::$cache_dir, 0755, true);
            }
        }
    }
    
    /**
     * Get cached query result
     * @param string $key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public static function get($key) {
        // Try APCu first if available
        if (function_exists('apcu_fetch')) {
            $data = apcu_fetch('query_' . $key, $success);
            if ($success) return $data;
        }
        
        // Fall back to file cache
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = unserialize($content);
            
            if ($data && isset($data['expires']) && $data['expires'] > time()) {
                return $data['value'];
            }
            
            // Expired, delete file
            @unlink($file);
        }
        
        return null;
    }
    
    /**
     * Set cached query result
     * @param string $key Cache key
     * @param mixed $value Data to cache
     * @param int $ttl Time to live in seconds
     * @return bool Success
     */
    public static function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? self::$default_ttl;
        
        // Try APCu first if available
        if (function_exists('apcu_store')) {
            apcu_store('query_' . $key, $value, $ttl);
        }
        
        // Also store in file cache
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    /**
     * Delete cached item
     * @param string $key Cache key
     */
    public static function delete($key) {
        if (function_exists('apcu_delete')) {
            apcu_delete('query_' . $key);
        }
        
        self::init();
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    /**
     * Clear all query cache
     */
    public static function clear() {
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        
        self::init();
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

/**
 * Query execution with profiling
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @param bool $log Whether to log slow queries
 * @return mysqli_result|bool Query result
 */
function query_with_profiling($conn, $sql, $log = true) {
    $start = microtime(true);
    $result = mysqli_query($conn, $sql);
    $duration = microtime(true) - $start;
    
    // Log slow queries (> 1 second)
    if ($log && $duration > 1.0) {
        $log_dir = dirname(__DIR__) . '/logs/';
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_entry = sprintf(
            "[%s] SLOW QUERY (%.3fs): %s\n",
            date('Y-m-d H:i:s'),
            $duration,
            substr($sql, 0, 1000)
        );
        
        @file_put_contents($log_dir . 'slow_queries.log', $log_entry, FILE_APPEND);
    }
    
    return $result;
}

/**
 * Cached query execution
 * @param mysqli $conn Database connection  
 * @param string $sql SQL query
 * @param int $ttl Cache TTL in seconds
 * @param string|null $cache_key Optional custom cache key
 * @return array Fetched rows
 */
function query_cached($conn, $sql, $ttl = 300, $cache_key = null) {
    $key = $cache_key ?? md5($sql);
    
    // Try to get from cache
    $cached = QueryCache::get($key);
    if ($cached !== null) {
        return $cached;
    }
    
    // Execute query
    $result = query_with_profiling($conn, $sql);
    
    if (!$result) {
        return [];
    }
    
    // Fetch all rows
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    
    // Store in cache
    QueryCache::set($key, $rows, $ttl);
    
    return $rows;
}

/**
 * Get lookup table data (cached for 10 minutes)
 * Common lookup tables like type, brand, category, etc.
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $key_field Field to use as array key
 * @param string $value_field Field to use as value (or * for full row)
 * @return array Lookup data
 */
function get_lookup($conn, $table, $key_field = 'id', $value_field = '*') {
    $cache_key = "lookup_{$table}_{$key_field}_{$value_field}";
    
    $cached = QueryCache::get($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    
    // Build safe query
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $key_field = preg_replace('/[^a-zA-Z0-9_]/', '', $key_field);
    
    if ($value_field === '*') {
        $sql = "SELECT * FROM `{$table}`";
    } else {
        $value_field = preg_replace('/[^a-zA-Z0-9_]/', '', $value_field);
        $sql = "SELECT `{$key_field}`, `{$value_field}` FROM `{$table}`";
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($value_field === '*') {
            $data[$row[$key_field]] = $row;
        } else {
            $data[$row[$key_field]] = $row[$value_field];
        }
    }
    mysqli_free_result($result);
    
    // Cache for 10 minutes
    QueryCache::set($cache_key, $data, 600);
    
    return $data;
}

/**
 * Invalidate lookup cache when data changes
 * Call this after INSERT/UPDATE/DELETE on lookup tables
 * 
 * @param string $table Table name
 */
function invalidate_lookup($table) {
    // Clear all cache entries for this table
    QueryCache::delete("lookup_{$table}_id_*");
    QueryCache::delete("lookup_{$table}_id_name");
    QueryCache::delete("lookup_{$table}_id_name_en");
}

/**
 * Optimize SELECT * queries by specifying needed columns
 * Returns a helper comment for developers
 * 
 * @param string $table Table name
 * @param array $needed_columns Columns actually needed
 * @return string Optimized column list
 */
function select_columns($table, $needed_columns) {
    $safe_columns = array_map(function($col) {
        return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . '`';
    }, $needed_columns);
    
    return implode(', ', $safe_columns);
}

/**
 * Batch fetch related records to avoid N+1 queries
 * 
 * @param mysqli $conn Database connection
 * @param string $table Related table
 * @param string $foreign_key Foreign key column
 * @param array $ids Array of IDs to fetch
 * @param string $columns Columns to select (default *)
 * @return array Records grouped by foreign key
 */
function batch_fetch($conn, $table, $foreign_key, $ids, $columns = '*') {
    if (empty($ids)) {
        return [];
    }
    
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $foreign_key = preg_replace('/[^a-zA-Z0-9_]/', '', $foreign_key);
    
    // Clean IDs
    $clean_ids = array_map('intval', $ids);
    $id_list = implode(',', $clean_ids);
    
    $sql = "SELECT {$columns} FROM `{$table}` WHERE `{$foreign_key}` IN ({$id_list})";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        return [];
    }
    
    $grouped = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $key = $row[$foreign_key];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $row;
    }
    mysqli_free_result($result);
    
    return $grouped;
}

/**
 * Count query with optional caching
 * 
 * @param mysqli $conn Database connection
 * @param string $sql COUNT query
 * @param int $ttl Cache TTL (0 = no cache)
 * @return int Count result
 */
function count_query($conn, $sql, $ttl = 60) {
    if ($ttl > 0) {
        $cached = QueryCache::get('count_' . md5($sql));
        if ($cached !== null) {
            return (int)$cached;
        }
    }
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    
    $row = mysqli_fetch_row($result);
    $count = (int)($row[0] ?? 0);
    mysqli_free_result($result);
    
    if ($ttl > 0) {
        QueryCache::set('count_' . md5($sql), $count, $ttl);
    }
    
    return $count;
}
