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
