<?php
/**
 * Staging Diagnostic - TEMPORARY
 * Delete after troubleshooting
 */
header('Content-Type: text/plain');
echo "=== Staging Diagnostic ===\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "CWD: " . getcwd() . "\n";
echo "Doc Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n\n";

// Check critical files
$files = [
    'inc/sys.configs.php',
    'inc/class.dbconn.php',
    'inc/security.php',
    'inc/error-handler.php',
    'vendor/autoload.php',
    'app/Config/routes.php',
    'app/Controllers/AuthController.php',
    'inc/string-us.xml',
    'inc/string-th.xml',
];
echo "=== File Check ===\n";
foreach ($files as $f) {
    echo ($f) . ": " . (file_exists($f) ? "OK" : "MISSING") . "\n";
}

// Check DB connection
echo "\n=== DB Connection ===\n";
try {
    require_once 'inc/sys.configs.php';
    echo "Config loaded. Host: " . ($config['hostname'] ?? 'NOT SET') . "\n";
    echo "DB Name: " . ($config['dbname'] ?? 'NOT SET') . "\n";
    echo "Username: " . ($config['username'] ?? 'NOT SET') . "\n";
    
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $ok = @mysqli_real_connect(
        $conn,
        $config['hostname'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );
    if ($ok) {
        echo "DB: CONNECTED OK\n";
        $r = mysqli_query($conn, "SELECT 1 AS test");
        $row = mysqli_fetch_assoc($r);
        echo "Query test: " . ($row['test'] ?? 'FAIL') . "\n";
        mysqli_close($conn);
    } else {
        echo "DB: FAILED - " . mysqli_connect_error() . "\n";
    }
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Check autoloader
echo "\n=== Autoloader ===\n";
if (class_exists('App\\Controllers\\AuthController')) {
    echo "AuthController: LOADABLE\n";
} else {
    echo "AuthController: NOT FOUND\n";
}
