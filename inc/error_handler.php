<?php
/**
 * Error Handler Class
 * Centralized error handling with logging and user-friendly messages
 * 
 * @version 1.0
 */

class ErrorHandler {
    private static $log_dir = null;
    private static $is_production = false;
    private static $error_log_file = 'error.log';
    private static $exception_log_file = 'exceptions.log';
    
    /**
     * Initialize error handler
     * @param bool $is_production Set to true in production to hide detailed errors
     */
    public static function init($is_production = false) {
        self::$is_production = $is_production;
        self::$log_dir = dirname(__DIR__) . '/logs/';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(self::$log_dir)) {
            @mkdir(self::$log_dir, 0755, true);
        }
        
        // Register handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Set error reporting based on environment
        if ($is_production) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }
    
    /**
     * Custom error handler
     */
    public static function handleError($severity, $message, $file, $line) {
        // Don't handle suppressed errors
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Convert to exception for fatal errors
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($severity, $fatal)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
        
        // Log non-fatal errors
        self::logError($severity, $message, $file, $line);
        
        return true;
    }
    
    /**
     * Custom exception handler
     */
    public static function handleException($exception) {
        self::logException($exception);
        
        if (self::$is_production) {
            self::showFriendlyError();
        } else {
            self::showDetailedError($exception);
        }
    }
    
    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null) {
            $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            
            if (in_array($error['type'], $fatal)) {
                self::logError(
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line']
                );
                
                if (self::$is_production) {
                    self::showFriendlyError();
                }
            }
        }
    }
    
    /**
     * Log error to file
     */
    private static function logError($severity, $message, $file, $line) {
        $severity_names = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        $severity_name = $severity_names[$severity] ?? 'UNKNOWN';
        
        $log_entry = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $severity_name,
            $message,
            $file,
            $line
        );
        
        @file_put_contents(
            self::$log_dir . self::$error_log_file,
            $log_entry,
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Log exception to file
     */
    private static function logException($exception) {
        $log_entry = sprintf(
            "[%s] EXCEPTION: %s\n  File: %s (line %d)\n  Stack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        @file_put_contents(
            self::$log_dir . self::$exception_log_file,
            $log_entry,
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Show user-friendly error page
     */
    private static function showFriendlyError() {
        // Clean output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code(500);
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - iACC</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
               background: #f5f5f5; margin: 0; padding: 20px; }
        .error-container { max-width: 600px; margin: 50px auto; background: white; 
                          border-radius: 10px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin: 0 0 20px; font-size: 24px; }
        p { color: #666; line-height: 1.6; margin: 0 0 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #8e44ad; color: white; 
               text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #6c3483; }
        .error-icon { font-size: 48px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Something went wrong</h1>
        <p>We apologize for the inconvenience. An error has occurred while processing your request. 
           Our team has been notified and is working to fix the issue.</p>
        <p>Please try again in a few moments, or contact support if the problem persists.</p>
        <a href="javascript:history.back()" class="btn">← Go Back</a>
        <a href="index.php" class="btn">Home</a>
    </div>
</body>
</html>';
        exit;
    }
    
    /**
     * Show detailed error (development only)
     */
    private static function showDetailedError($exception) {
        // Clean output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code(500);
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Details - iACC</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #eee; margin: 0; padding: 20px; }
        .error-container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #e74c3c; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .error-type { color: #f39c12; }
        .error-message { background: #16213e; padding: 20px; border-radius: 5px; 
                        margin: 20px 0; border-left: 4px solid #e74c3c; }
        .file-info { color: #3498db; }
        .stack-trace { background: #0f0f23; padding: 20px; border-radius: 5px; 
                      overflow-x: auto; white-space: pre; font-size: 12px; }
        .line-number { color: #f39c12; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>🔴 Exception Caught</h1>
        <div class="error-message">
            <span class="error-type">' . get_class($exception) . '</span><br>
            ' . htmlspecialchars($exception->getMessage()) . '
        </div>
        <p class="file-info">
            <strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '<br>
            <strong>Line:</strong> <span class="line-number">' . $exception->getLine() . '</span>
        </p>
        <h2>Stack Trace</h2>
        <div class="stack-trace">' . htmlspecialchars($exception->getTraceAsString()) . '</div>
    </div>
</body>
</html>';
        exit;
    }
    
    /**
     * Log a custom message
     * @param string $level Log level (info, warning, error, debug)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public static function log($level, $message, $context = []) {
        $log_entry = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        $log_file = self::$log_dir . 'app.log';
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log info message
     */
    public static function info($message, $context = []) {
        self::log('info', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning($message, $context = []) {
        self::log('warning', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error($message, $context = []) {
        self::log('error', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public static function debug($message, $context = []) {
        if (!self::$is_production) {
            self::log('debug', $message, $context);
        }
    }
}

/**
 * Try-catch wrapper for database operations
 * 
 * @param callable $callback The operation to execute
 * @param mixed $default Default value to return on error
 * @param string $error_message Custom error message
 * @return mixed Result of callback or default value
 */
function safe_db_operation($callback, $default = null, $error_message = 'Database operation failed') {
    try {
        return $callback();
    } catch (Exception $e) {
        ErrorHandler::error($error_message, [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return $default;
    }
}

/**
 * Show flash message to user
 * 
 * @param string $type Message type: success, error, warning, info
 * @param string $message The message to display
 */
function flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * 
 * @return array Flash messages
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages HTML
 * 
 * @return string HTML for flash messages
 */
function render_flash_messages() {
    $messages = get_flash_messages();
    
    if (empty($messages)) {
        return '';
    }
    
    $type_classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $type_icons = [
        'success' => '✓',
        'error' => '✕',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];
    
    $html = '';
    foreach ($messages as $msg) {
        $class = $type_classes[$msg['type']] ?? 'alert-info';
        $icon = $type_icons[$msg['type']] ?? 'ℹ';
        
        $html .= '<div class="alert ' . $class . ' alert-dismissible fade in" role="alert">';
        $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span></button>';
        $html .= '<strong>' . $icon . '</strong> ' . htmlspecialchars($msg['message']);
        $html .= '</div>';
    }
    
    return $html;
}
