<?php
/**
 * PHP Error Handler
 * 
 * Provides:
 * - Fatal error detection via shutdown function (catches class-not-found, parse errors, etc.)
 * - Error logging to logs/error.log
 * - User-friendly error page instead of blank page on production
 * - Works identically on Docker and cPanel environments
 */

// Error reporting — log everything, display nothing
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Use project log file if possible
$_errorLogFile = dirname(__DIR__) . '/logs/error.log';
if (is_dir(dirname($_errorLogFile)) && is_writable(dirname($_errorLogFile))) {
    ini_set('error_log', $_errorLogFile);
}

/**
 * Custom error handler for non-fatal errors (warnings, notices, deprecations)
 * Fatal errors (E_ERROR, E_PARSE, etc.) CANNOT be caught here — the shutdown handler handles those.
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Always log warnings and above
    if ($errno & (E_WARNING | E_USER_WARNING | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
        $msg = date('Y-m-d H:i:s') . " [{$errno}] {$errstr} in {$errfile}:{$errline}\n";
        error_log($msg, 3, dirname(__DIR__) . '/logs/error.log');
    }
    
    // Suppress display but let PHP continue
    return true;
}, E_ALL);

/**
 * Shutdown handler — catches FATAL errors that set_error_handler cannot
 * This is the safety net that prevents blank pages on production.
 */
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error === null) return;
    
    // Only handle fatal errors
    $fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;
    if (!($error['type'] & $fatalTypes)) return;
    
    // Log the fatal error
    $logFile = dirname(__DIR__) . '/logs/error.log';
    $msg = date('Y-m-d H:i:s') . " FATAL [{$error['type']}] {$error['message']} in {$error['file']}:{$error['line']}\n";
    @error_log($msg, 3, $logFile);
    
    // Determine environment
    $appEnv = getenv('APP_ENV') ?: (isset($GLOBALS['appEnv']) ? $GLOBALS['appEnv'] : 'development');
    $isDev = ($appEnv !== 'production');
    
    // Clean any partial output
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Send proper HTTP status
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    
    // Show error page (not blank!)
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>System Error</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:40px}';
    echo '.error-box{max-width:600px;margin:40px auto;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);overflow:hidden}';
    echo '.error-header{background:#e74c3c;color:#fff;padding:20px 30px}';
    echo '.error-header h1{margin:0;font-size:20px}';
    echo '.error-body{padding:30px}';
    echo '.error-body p{color:#555;line-height:1.6}';
    echo '.error-detail{background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;padding:15px;margin:15px 0;font-family:monospace;font-size:13px;white-space:pre-wrap;word-wrap:break-word}';
    echo '.btn{display:inline-block;padding:10px 20px;background:#3498db;color:#fff;text-decoration:none;border-radius:4px;margin-top:15px}';
    echo '.btn:hover{background:#2980b9}</style></head><body>';
    echo '<div class="error-box">';
    echo '<div class="error-header"><h1>⚠️ System Error</h1></div>';
    echo '<div class="error-body">';
    echo '<p>An unexpected error occurred. The system administrator has been notified.</p>';
    
    if ($isDev) {
        echo '<div class="error-detail">';
        echo htmlspecialchars("Error: {$error['message']}\nFile: {$error['file']}\nLine: {$error['line']}\nType: {$error['type']}");
        echo '</div>';
    } else {
        echo '<p style="color:#999;font-size:13px">Error ID: ' . substr(md5($error['file'] . $error['line'] . time()), 0, 8) . '</p>';
    }
    
    echo '<a href="index.php?page=dashboard" class="btn">← Return to Dashboard</a>';
    echo '</div></div></body></html>';
});

