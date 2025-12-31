<?php
/**
 * Environment Detection & Configuration
 * Automatically detects staging vs production and loads appropriate config
 * 
 * Usage: Include at top of index.php or config files
 * require_once(__DIR__ . '/env-detect.php');
 */

// Detect environment based on domain
$hostname = $_SERVER['HTTP_HOST'] ?? gethostname();
$is_staging = (strpos($hostname, 'staging') !== false);
$is_local = (strpos($hostname, 'localhost') !== false || strpos($hostname, '127.0.0.1') !== false);

// Define environment constant
if (!defined('ENVIRONMENT')) {
    if ($is_local) {
        define('ENVIRONMENT', 'local');
    } elseif ($is_staging) {
        define('ENVIRONMENT', 'staging');
    } else {
        define('ENVIRONMENT', 'production');
    }
}

// Set database configuration based on environment
if (!defined('DB_HOST')) {
    if (ENVIRONMENT === 'staging') {
        // Staging database configuration
        define('DB_HOST', 'localhost');
        define('DB_USER', 'iacc_user');
        define('DB_PASS', 'your_password_here');  // Update for staging
        define('DB_NAME', 'iacc_staging');
        define('DEBUG_MODE', true);
        define('LOG_QUERIES', true);
        define('DISPLAY_ERRORS', true);
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } elseif (ENVIRONMENT === 'local') {
        // Local/Docker configuration
        define('DB_HOST', 'mysql');  // Docker service name
        define('DB_USER', 'root');
        define('DB_PASS', 'password');
        define('DB_NAME', 'iacc_database');
        define('DEBUG_MODE', true);
        define('LOG_QUERIES', true);
        define('DISPLAY_ERRORS', true);
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } else {
        // Production database configuration
        define('DB_HOST', 'localhost');
        define('DB_USER', 'iacc_user');
        define('DB_PASS', 'your_password_here');  // Update for production
        define('DB_NAME', 'iacc_database');
        define('DEBUG_MODE', false);
        define('LOG_QUERIES', false);
        define('DISPLAY_ERRORS', false);
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_DEPRECATED);
    }
}

// Set application URL based on environment
if (!defined('APP_URL')) {
    if (ENVIRONMENT === 'local') {
        define('APP_URL', 'http://localhost:8080');
    } elseif (ENVIRONMENT === 'staging') {
        define('APP_URL', 'https://iacc-staging.f2.co.th');
    } else {
        define('APP_URL', 'https://iacc.f2.co.th');
    }
}

// Set upload path based on environment
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', 'upload/');
}

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Logging based on environment
if (!defined('LOG_FILE')) {
    if (ENVIRONMENT === 'local') {
        define('LOG_FILE', '/tmp/iacc_' . ENVIRONMENT . '.log');
    } else {
        define('LOG_FILE', dirname(__FILE__) . '/../../logs/iacc_' . ENVIRONMENT . '.log');
    }
}

/**
 * Get environment name for display
 */
function getEnvironmentLabel() {
    switch (ENVIRONMENT) {
        case 'production':
            return '<span style="background: #d32f2f; color: white; padding: 2px 8px; border-radius: 3px;">PRODUCTION</span>';
        case 'staging':
            return '<span style="background: #f57c00; color: white; padding: 2px 8px; border-radius: 3px;">STAGING</span>';
        case 'local':
            return '<span style="background: #1976d2; color: white; padding: 2px 8px; border-radius: 3px;">LOCAL</span>';
        default:
            return 'UNKNOWN';
    }
}

/**
 * Log message with environment context
 */
function logMessage($message, $level = 'INFO') {
    if (DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [" . ENVIRONMENT . "] [$level] $message\n";
        
        if (defined('LOG_FILE') && is_writable(dirname(LOG_FILE))) {
            file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
        }
        
        if ($level === 'ERROR') {
            error_log($log_entry);
        }
    }
}

// Log application startup
logMessage("Application started in " . ENVIRONMENT . " environment");

?>
