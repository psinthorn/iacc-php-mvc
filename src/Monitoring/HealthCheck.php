<?php

namespace App\Monitoring;

/**
 * Application Health Check Service
 * 
 * Provides comprehensive health and readiness checks
 */
class HealthCheck
{
    /**
     * Run all health checks
     * 
     * @return array
     */
    public static function all(): array
    {
        return [
            'status' => 'ok',
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'version' => self::getVersion(),
            'uptime' => self::getUptime(),
            'checks' => [
                'database' => self::database(),
                'cache' => self::cache(),
                'filesystem' => self::filesystem(),
                'memory' => self::memory(),
                'disk' => self::disk(),
            ],
        ];
    }

    /**
     * Run readiness checks (critical only)
     * 
     * @return array
     */
    public static function readiness(): array
    {
        return [
            'ready' => true,
            'checks' => [
                'database' => self::database(),
                'cache' => self::cache(),
                'filesystem' => self::filesystem(),
            ],
        ];
    }

    /**
     * Check database
     * 
     * @return array
     */
    public static function database(): array
    {
        try {
            $start = microtime(true);
            $db = app('db');
            $db->query('SELECT 1');
            $duration = (microtime(true) - $start) * 1000;

            return [
                'status' => 'ok',
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache
     * 
     * @return array
     */
    public static function cache(): array
    {
        try {
            $cache = app('cache');
            if (!$cache) {
                return ['status' => 'unavailable'];
            }

            $start = microtime(true);
            $cache->set('health_check_' . time(), 'ok', 60);
            $duration = (microtime(true) - $start) * 1000;

            return [
                'status' => 'ok',
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check filesystem
     * 
     * @return array
     */
    public static function filesystem(): array
    {
        try {
            $uploadDir = storage_path('uploads');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (!is_writable($uploadDir)) {
                return [
                    'status' => 'error',
                    'message' => "Upload directory is not writable",
                ];
            }

            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory
     * 
     * @return array
     */
    public static function memory(): array
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');

        return [
            'status' => 'ok',
            'usage_bytes' => $usage,
            'usage_mb' => round($usage / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit' => $limit,
        ];
    }

    /**
     * Check disk
     * 
     * @return array
     */
    public static function disk(): array
    {
        $rootDir = base_path();
        $free = disk_free_space($rootDir);
        $total = disk_total_space($rootDir);

        if ($free === false || $total === false) {
            return [
                'status' => 'error',
                'message' => 'Unable to determine disk space',
            ];
        }

        $percentFree = ($free / $total) * 100;
        $status = $percentFree > 10 ? 'ok' : 'warning';

        return [
            'status' => $status,
            'free_bytes' => $free,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'percent_free' => round($percentFree, 2),
        ];
    }

    /**
     * Get version
     * 
     * @return string
     */
    private static function getVersion(): string
    {
        $version = getenv('APP_VERSION') ?: '1.0.0';
        $commit = getenv('GIT_COMMIT') ? substr(getenv('GIT_COMMIT'), 0, 8) : 'unknown';
        return "$version+$commit";
    }

    /**
     * Get uptime
     * 
     * @return array
     */
    private static function getUptime(): array
    {
        $startTime = (int)(getenv('APP_START_TIME') ?: 0);
        if ($startTime === 0) {
            return ['status' => 'unknown'];
        }

        $uptime = time() - $startTime;
        $hours = floor($uptime / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        $seconds = $uptime % 60;

        return [
            'seconds' => $uptime,
            'formatted' => sprintf('%dh %dm %ds', $hours, $minutes, $seconds),
        ];
    }
}
