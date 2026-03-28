<?php
namespace App\Controllers;

/**
 * HealthController - System health check & version info
 * 
 * Provides:
 * - Public health check (basic up/down)
 * - Super Admin detailed system status
 */
class HealthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /index.php?page=health
     * 
     * Public: returns basic {"status":"ok"}
     * Super Admin (level >= 2): returns full system details
     */
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $isAdmin = isset($this->user['level']) && $this->user['level'] >= 2;

        if (!$isAdmin) {
            echo json_encode([
                'status' => 'ok',
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
            ]);
            return;
        }

        // Full system status for Super Admin
        $health = [
            'status' => 'ok',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'environment' => $this->getEnvironment(),
            'php' => $this->getPhpInfo(),
            'database' => $this->getDatabaseInfo(),
            'application' => $this->getAppInfo(),
            'disk' => $this->getDiskInfo(),
            'errors' => $this->getRecentErrors(),
        ];

        // Overall status based on checks
        if ($health['database']['status'] !== 'connected') {
            $health['status'] = 'degraded';
        }

        echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function getEnvironment(): array
    {
        $isDocker = file_exists('/.dockerenv') || file_exists('/proc/1/cgroup');
        return [
            'type' => $isDocker ? 'docker' : 'cpanel',
            'app_env' => getenv('APP_ENV') ?: 'development',
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'hostname' => gethostname() ?: 'unknown',
        ];
    }

    private function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'extensions' => [
                'mysqli' => extension_loaded('mysqli'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'gd' => extension_loaded('gd'),
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
            ],
        ];
    }

    private function getDatabaseInfo(): array
    {
        try {
            if (!$this->conn) {
                return ['status' => 'disconnected', 'error' => 'No connection'];
            }

            $result = mysqli_query($this->conn, "SELECT VERSION() as version");
            $row = $result ? mysqli_fetch_assoc($result) : null;

            $tableResult = mysqli_query($this->conn, "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()");
            $tableRow = $tableResult ? mysqli_fetch_assoc($tableResult) : null;

            $sizeResult = mysqli_query($this->conn, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
            $sizeRow = $sizeResult ? mysqli_fetch_assoc($sizeResult) : null;

            return [
                'status' => 'connected',
                'version' => $row['version'] ?? 'unknown',
                'database' => 'iacc',
                'tables' => intval($tableRow['cnt'] ?? 0),
                'size_mb' => floatval($sizeRow['size_mb'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function getAppInfo(): array
    {
        // Read version.json if available
        $versionFile = __DIR__ . '/../../version.json';
        $version = ['version' => '5.0-mvc'];
        if (file_exists($versionFile)) {
            $data = json_decode(file_get_contents($versionFile), true);
            if ($data) $version = $data;
        }

        // Count routes
        $routes = require __DIR__ . '/../Config/routes.php';
        $mvcRoutes = 0;
        $legacyRoutes = 0;
        foreach ($routes as $k => $v) {
            if (is_array($v)) $mvcRoutes++;
            else $legacyRoutes++;
        }

        // Count controllers and models
        $controllers = count(glob(__DIR__ . '/*.php')) - 1; // -1 for BaseController
        $models = count(glob(__DIR__ . '/../Models/*.php')) - 1; // -1 for BaseModel
        $views = 0;
        $viewDir = __DIR__ . '/../../views';
        if (is_dir($viewDir)) {
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewDir));
            foreach ($rii as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') $views++;
            }
        }

        return array_merge($version, [
            'routes_mvc' => $mvcRoutes,
            'routes_legacy' => $legacyRoutes,
            'controllers' => $controllers,
            'models' => $models,
            'views' => $views,
        ]);
    }

    private function getDiskInfo(): array
    {
        $root = __DIR__ . '/../../';
        $free = @disk_free_space($root);
        $total = @disk_total_space($root);

        return [
            'free_mb' => $free ? round($free / 1024 / 1024, 0) : null,
            'total_mb' => $total ? round($total / 1024 / 1024, 0) : null,
            'usage_percent' => ($free && $total) ? round((1 - $free / $total) * 100, 1) : null,
            'log_size_kb' => $this->getLogSize(),
        ];
    }

    private function getLogSize(): ?int
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) return null;

        $total = 0;
        foreach (glob("$logDir/*.log") as $file) {
            $total += filesize($file);
        }
        return round($total / 1024);
    }

    /**
     * GET /index.php?page=health_diagnose
     * 
     * Super Admin only: Returns comprehensive environment diagnostic
     * Checks PHP version, extensions, autoloading, file paths, error logs
     * Useful for debugging "works locally but blank on production" issues
     */
    public function diagnose(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // Super Admin only (level >= 2)
        if (!isset($this->user['level']) || $this->user['level'] < 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Super Admin access required']);
            return;
        }

        $results = [];
        $errors = [];

        // 1. PHP Version & SAPI
        $results['php'] = [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'version_ok' => version_compare(PHP_VERSION, '8.1', '>='),
            'os' => PHP_OS,
            'uname' => php_uname(),
        ];
        if (!$results['php']['version_ok']) {
            $errors[] = "PHP " . PHP_VERSION . " is below required 8.1 — typed properties and return types will fail";
        }

        // 2. Extensions
        $required = ['mysqli', 'mbstring', 'json', 'session', 'gd'];
        $results['extensions'] = [];
        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            $results['extensions'][$ext] = $loaded;
            if (!$loaded) $errors[] = "Missing PHP extension: $ext";
        }

        // 3. Autoloader
        $root = realpath(__DIR__ . '/../..');
        $autoload = $root . '/vendor/autoload.php';
        $results['autoloader'] = [
            'exists' => file_exists($autoload),
            'path' => $autoload,
        ];
        if (!file_exists($autoload)) {
            $errors[] = "vendor/autoload.php not found — run composer install";
        }

        // 4. Controller loading test (the actual MVC dispatch)
        $controllerTests = [
            'DashboardController', 'PurchaseOrderController', 'CompanyController',
            'PdfController', 'InvoiceController', 'BrandController',
        ];
        $results['controllers'] = [];
        foreach ($controllerTests as $c) {
            $class = 'App\\Controllers\\' . $c;
            $file = __DIR__ . '/' . $c . '.php';
            try {
                $fileExists = file_exists($file);
                $classExists = class_exists($class, true);
                $results['controllers'][$c] = [
                    'file_exists' => $fileExists,
                    'class_loads' => $classExists,
                    'status' => ($fileExists && $classExists) ? 'OK' : 'FAIL',
                ];
                if (!$fileExists) $errors[] = "Controller file missing: $file";
                if (!$classExists) $errors[] = "Cannot autoload: $class";
            } catch (\Throwable $e) {
                $results['controllers'][$c] = ['error' => $e->getMessage()];
                $errors[] = "Error loading $c: " . $e->getMessage();
            }
        }

        // 5. Model loading test
        $modelTests = ['PurchaseOrder', 'Company', 'Dashboard', 'Invoice'];
        $results['models'] = [];
        foreach ($modelTests as $m) {
            $class = 'App\\Models\\' . $m;
            $file = __DIR__ . '/../Models/' . $m . '.php';
            try {
                $results['models'][$m] = [
                    'file_exists' => file_exists($file),
                    'class_loads' => class_exists($class, true),
                ];
            } catch (\Throwable $e) {
                $results['models'][$m] = ['error' => $e->getMessage()];
                $errors[] = "Error loading model $m: " . $e->getMessage();
            }
        }

        // 6. Critical files
        $criticalFiles = [
            'inc/sys.configs.php', 'inc/class.dbconn.php', 'inc/security.php',
            'inc/error-handler.php', 'inc/class.hard.php', 'inc/class.current.php',
            'inc/class.company_filter.php', 'inc/pdf-template.php',
            'app/Config/routes.php', 'app/Views/layouts/head.php',
            'app/Views/layouts/sidebar.php', 'app/Views/layouts/scripts.php',
        ];
        $results['files'] = [];
        foreach ($criticalFiles as $f) {
            $exists = file_exists($root . '/' . $f);
            $results['files'][$f] = $exists;
            if (!$exists) $errors[] = "Missing: $f";
        }

        // 7. Paths & Working Directory
        $results['paths'] = [
            'cwd' => getcwd(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
            'project_root' => $root,
            'open_basedir' => ini_get('open_basedir') ?: '(none)',
        ];

        // 8. PHP Settings
        $results['settings'] = [
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => error_reporting(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? false) : 'N/A',
        ];

        // 9. OPcache details (often the culprit for "works locally, blank on production")
        if (function_exists('opcache_get_status')) {
            $opcache = @opcache_get_status(false);
            if ($opcache) {
                $results['opcache'] = [
                    'enabled' => $opcache['opcache_enabled'] ?? false,
                    'cache_full' => $opcache['cache_full'] ?? false,
                    'restart_pending' => $opcache['restart_pending'] ?? false,
                    'num_cached_scripts' => $opcache['opcache_statistics']['num_cached_scripts'] ?? 0,
                    'hits' => $opcache['opcache_statistics']['hits'] ?? 0,
                    'misses' => $opcache['opcache_statistics']['misses'] ?? 0,
                    'memory_used_mb' => round(($opcache['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 1),
                    'validate_timestamps' => ini_get('opcache.validate_timestamps'),
                    'revalidate_freq' => ini_get('opcache.revalidate_freq'),
                ];
            }
        }

        // 10. Routes
        $routes = require __DIR__ . '/../Config/routes.php';
        $results['routes'] = [
            'total' => count($routes),
            'po_edit' => $routes['po_edit'] ?? 'NOT FOUND',
        ];

        // 11. Error logs (last 15 entries)
        $errorLog = $root . '/logs/error.log';
        if (file_exists($errorLog) && filesize($errorLog) > 0) {
            $lines = array_filter(explode("\n", file_get_contents($errorLog)));
            $results['error_log'] = [
                'file' => $errorLog,
                'total_lines' => count($lines),
                'last_15' => array_values(array_slice($lines, -15)),
            ];
        } else {
            $results['error_log'] = ['file' => $errorLog, 'status' => 'empty or not found'];
        }

        // 12. Database
        $results['database'] = [
            'connected' => ($this->conn !== null),
            'version' => $this->conn ? mysqli_get_server_info($this->conn) : 'N/A',
        ];

        // Summary
        $results['total_errors'] = count($errors);
        $results['errors'] = $errors;
        $results['status'] = count($errors) === 0 ? 'ALL OK' : 'ISSUES FOUND';

        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getRecentErrors(): array
    {
        $logFile = __DIR__ . '/../../logs/php_errors.log';
        if (!file_exists($logFile)) {
            $logFile = __DIR__ . '/../../logs/php-error.log';
        }
        if (!file_exists($logFile)) {
            return ['count' => 0, 'last_entries' => []];
        }

        // Get last 5 lines
        $lines = [];
        $fp = @fopen($logFile, 'r');
        if ($fp) {
            $buffer = [];
            while (($line = fgets($fp)) !== false) {
                $buffer[] = trim($line);
                if (count($buffer) > 5) array_shift($buffer);
            }
            fclose($fp);
            $lines = $buffer;
        }

        return [
            'log_file' => basename($logFile),
            'last_entries' => $lines,
        ];
    }
}
