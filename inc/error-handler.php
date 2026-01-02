<?php
/**
 * PHP Compatibility & Error Handler
 * Provides error suppression and helpful error messages
 */

// Set error reporting to suppress undefined array key notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// Custom error handler for better debugging
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log errors to file instead of displaying
    $log_file = '/var/www/html/error.log';
    $log_message = date('Y-m-d H:i:s') . " - ";
    $log_message .= "[$errno] $errstr in $errfile on line $errline\n";
    
    // Only log important errors (not notices and warnings)
    if($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)){
        error_log($log_message, 3, $log_file);
    }
    
    // Don't execute PHP internal error handler
    return true;
}, E_ALL);

// Suppress warnings from deprecated functions
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>
