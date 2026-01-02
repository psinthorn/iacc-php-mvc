<?php
/**
 * Error Logger Helper
 * Usage: require_once 'inc/error-logger.php';
 *        app_log('message', 'INFO');
 */

// Set error reporting based on environment
$is_production = !isset($_SERVER['APP_ENV']) || $_SERVER['APP_ENV'] !== 'development';

if ($is_production) {
    // Production: log errors, don't display
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Development: show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

/**
 * Log application messages
 * @param string $message The message to log
 * @param string $level Log level: INFO, WARNING, ERROR, DEBUG
 */
function app_log($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log errors with stack trace
 * @param Exception $e The exception to log
 */
function log_exception($e) {
    $message = sprintf(
        "Exception: %s in %s:%d\nStack trace:\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    app_log($message, 'ERROR');
}

// Register custom error handler
set_exception_handler(function($e) {
    log_exception($e);
    
    // Show user-friendly error in production
    if (!isset($_SERVER['APP_ENV']) || $_SERVER['APP_ENV'] !== 'development') {
        http_response_code(500);
        echo "<h1>An error occurred</h1><p>Please try again later.</p>";
        exit;
    }
    
    throw $e; // Re-throw in development for normal error display
});
