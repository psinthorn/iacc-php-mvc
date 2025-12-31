<?php

/**
 * PHPUnit Bootstrap File
 * Initializes the test environment and autoloading
 */

// Define testing constant
define('TESTING', true);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../.env.testing';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Set test database environment
putenv('DB_DATABASE=iacc_test');
putenv('DB_HOST=localhost');
putenv('DB_PORT=3306');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=root');

// Initialize test helpers
require_once __DIR__ . '/helpers.php';
