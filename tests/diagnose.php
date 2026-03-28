<?php
/**
 * Production Environment Diagnostic
 * Checks all critical requirements for MVC routing to work
 * Access: https://iacc.f2.co.th/tests/diagnose.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$results = [];
$errors = [];

// 1. PHP Version
$results['php_version'] = PHP_VERSION;
$results['php_sapi'] = php_sapi_name();
if (version_compare(PHP_VERSION, '8.1', '<')) {
    $errors[] = "PHP version " . PHP_VERSION . " is below required 8.1";
}

// 2. Required PHP extensions
$requiredExt = ['mysqli', 'mbstring', 'json', 'session', 'gd'];
$results['extensions'] = [];
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    $results['extensions'][$ext] = $loaded;
    if (!$loaded) $errors[] = "Missing PHP extension: $ext";
}

// 3. Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
$results['composer_autoload_exists'] = file_exists($autoloadPath);
if (!file_exists($autoloadPath)) {
    $errors[] = "vendor/autoload.php not found at: $autoloadPath";
} else {
    require_once $autoloadPath;
    $results['composer_autoload_loaded'] = true;
}

// 4. PSR-4 autoloading - can we load controllers?
$controllerTests = [
    'App\\Controllers\\BaseController',
    'App\\Controllers\\DashboardController',
    'App\\Controllers\\PurchaseOrderController',
    'App\\Controllers\\CompanyController',
    'App\\Controllers\\PdfController',
];
$results['controllers'] = [];
foreach ($controllerTests as $class) {
    try {
        $exists = class_exists($class, true);
        $results['controllers'][$class] = $exists ? 'OK' : 'NOT FOUND';
        if (!$exists) $errors[] = "Cannot autoload: $class";
    } catch (\Throwable $e) {
        $results['controllers'][$class] = 'ERROR: ' . $e->getMessage();
        $errors[] = "Error loading $class: " . $e->getMessage();
    }
}

// 5. Model autoloading
$modelTests = [
    'App\\Models\\PurchaseOrder',
    'App\\Models\\Company',
    'App\\Models\\Dashboard',
];
$results['models'] = [];
foreach ($modelTests as $class) {
    try {
        $exists = class_exists($class, true);
        $results['models'][$class] = $exists ? 'OK' : 'NOT FOUND';
        if (!$exists) $errors[] = "Cannot autoload: $class";
    } catch (\Throwable $e) {
        $results['models'][$class] = 'ERROR: ' . $e->getMessage();
        $errors[] = "Error loading $class: " . $e->getMessage();
    }
}

// 6. Critical file paths
$criticalFiles = [
    'inc/sys.configs.php',
    'inc/class.dbconn.php',
    'inc/security.php',
    'inc/error-handler.php',
    'inc/class.hard.php',
    'inc/class.current.php',
    'inc/class.company_filter.php',
    'inc/pdf-template.php',
    'app/Config/routes.php',
    'app/Controllers/BaseController.php',
    'app/Controllers/PurchaseOrderController.php',
    'app/Views/layouts/head.php',
    'app/Views/layouts/sidebar.php',
    'app/Views/layouts/scripts.php',
    'vendor/autoload.php',
];
$results['files'] = [];
foreach ($criticalFiles as $f) {
    $fullPath = __DIR__ . '/../' . $f;
    $exists = file_exists($fullPath);
    $results['files'][$f] = $exists;
    if (!$exists) $errors[] = "Missing file: $f";
}

// 7. Directory structure
$dirs = ['app', 'app/Controllers', 'app/Models', 'app/Views', 'app/Config', 'vendor', 'inc', 'logs'];
$results['directories'] = [];
foreach ($dirs as $d) {
    $fullPath = __DIR__ . '/../' . $d;
    $exists = is_dir($fullPath);
    $results['directories'][$d] = $exists;
    if (!$exists) $errors[] = "Missing directory: $d";
}

// 8. Working directory and paths
$results['cwd'] = getcwd();
$results['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
$results['script_filename'] = $_SERVER['SCRIPT_FILENAME'] ?? 'N/A';
$results['__dir__'] = __DIR__;
$results['realpath_root'] = realpath(__DIR__ . '/..');

// 9. PHP settings that affect operation
$results['php_settings'] = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'open_basedir' => ini_get('open_basedir') ?: '(none)',
    'short_open_tag' => ini_get('short_open_tag'),
];

// 10. open_basedir restrictions
$openBasedir = ini_get('open_basedir');
if ($openBasedir) {
    $results['open_basedir_paths'] = explode(':', $openBasedir);
}

// 11. Test route loading
$routesFile = __DIR__ . '/../app/Config/routes.php';
if (file_exists($routesFile)) {
    $routes = require $routesFile;
    $results['total_routes'] = count($routes);
    $results['po_edit_route'] = $routes['po_edit'] ?? 'NOT FOUND';
    
    $mvcCount = 0;
    foreach ($routes as $k => $v) {
        if (is_array($v)) $mvcCount++;
    }
    $results['mvc_routes'] = $mvcCount;
}

// 12. Check error logs
$errorLog = __DIR__ . '/../logs/error.log';
if (file_exists($errorLog) && filesize($errorLog) > 0) {
    $lines = array_filter(explode("\n", file_get_contents($errorLog)));
    $results['error_log_lines'] = count($lines);
    $results['error_log_last_10'] = array_slice($lines, -10);
}

// 13. Check app.log
$appLog = __DIR__ . '/../logs/app.log';
if (file_exists($appLog) && filesize($appLog) > 0) {
    $lines = array_filter(explode("\n", file_get_contents($appLog)));
    $results['app_log_lines'] = count($lines);
    $results['app_log_last_5'] = array_slice($lines, -5);
}

// 14. Check PHP error log (system)
$phpErrorLog = ini_get('error_log');
$results['php_error_log_path'] = $phpErrorLog ?: 'default';

// 15. Try to instantiate a controller (the real test)
try {
    // Load required files first
    require_once __DIR__ . '/../inc/sys.configs.php';
    require_once __DIR__ . '/../inc/class.dbconn.php';
    require_once __DIR__ . '/../inc/security.php';
    
    $db = new DbConn($config);
    $results['db_connection'] = $db->conn ? 'OK' : 'FAILED';
    
    // Simulate session for controller test
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['com_id'] = $_SESSION['com_id'] ?? 1;
    $_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
    $_SESSION['user_level'] = $_SESSION['user_level'] ?? 0;
    $_SESSION['user_email'] = $_SESSION['user_email'] ?? 'test';
    $_SESSION['lang'] = $_SESSION['lang'] ?? 'en';
    $_SESSION['com_name'] = $_SESSION['com_name'] ?? 'test';
    
    // Test instantiating controller
    $controller = new App\Controllers\DashboardController();
    $results['controller_instantiation'] = 'OK';
} catch (\Throwable $e) {
    $results['controller_error'] = $e->getMessage();
    $results['controller_error_file'] = $e->getFile() . ':' . $e->getLine();
    $results['controller_error_trace'] = array_slice(explode("\n", $e->getTraceAsString()), 0, 5);
    $errors[] = "Controller instantiation failed: " . $e->getMessage();
}

// Summary
$results['total_errors'] = count($errors);
$results['errors'] = $errors;
$results['status'] = count($errors) === 0 ? 'ALL OK' : 'ISSUES FOUND';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
