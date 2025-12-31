<?php

namespace App\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Health Check Controller
 * 
 * Provides system health and readiness checks for monitoring and load balancers
 */
class HealthController
{
    /**
     * Health check endpoint
     * 
     * Returns system health status and basic metrics
     * Used by load balancers and monitoring systems
     * 
     * @return Response
     */
    public function health(): Response
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'version' => $this->getAppVersion(),
            'uptime' => $this->getUptime(),
            'checks' => $this->runHealthChecks(),
        ];

        // If any critical check fails, return 503
        $statusCode = $this->allChecksPassed($health['checks']) ? 200 : 503;

        return Response::json($health, $statusCode);
    }

    /**
     * Readiness check endpoint
     * 
     * Returns whether service is ready to handle requests
     * More strict than health check
     * 
     * @return Response
     */
    public function ready(): Response
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'files' => $this->checkFilesystem(),
        ];

        $ready = array_every($checks, fn($check) => $check['status'] === 'ok');

        return Response::json([
            'ready' => $ready,
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }

    /**
     * Run all health checks
     * 
     * @return array
     */
    private function runHealthChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'filesystem' => $this->checkFilesystem(),
            'memory' => $this->checkMemory(),
            'disk' => $this->checkDisk(),
        ];
    }

    /**
     * Check database connectivity
     * 
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $db = app('db');
            $result = $db->query('SELECT 1');
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
     * Check cache connectivity
     * 
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $cache = app('cache');
            if (!$cache) {
                return ['status' => 'unavailable'];
            }

            $start = microtime(true);
            $cache->set('health_check', time(), 60);
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
     * Check filesystem writability
     * 
     * @return array
     */
    private function checkFilesystem(): array
    {
        try {
            $uploadDir = storage_path('uploads');
            if (!is_writable($uploadDir)) {
                return [
                    'status' => 'error',
                    'message' => "Upload directory is not writable: $uploadDir",
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
     * Check memory usage
     * 
     * @return array
     */
    private function checkMemory(): array
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
     * Check disk space
     * 
     * @return array
     */
    private function checkDisk(): array
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
     * Get application version
     * 
     * @return string
     */
    private function getAppVersion(): string
    {
        $version = getenv('APP_VERSION') ?: '1.0.0';
        $commit = getenv('GIT_COMMIT') ? substr(getenv('GIT_COMMIT'), 0, 8) : 'unknown';
        return "$version+$commit";
    }

    /**
     * Get application uptime
     * 
     * @return array
     */
    private function getUptime(): array
    {
        $startTime = getenv('APP_START_TIME');
        if (!$startTime) {
            return ['status' => 'unknown'];
        }

        $uptime = time() - $startTime;
        $hours = floor($uptime / 3600);
        $minutes = floor(($uptime % 3600) / 60);

        return [
            'seconds' => $uptime,
            'formatted' => "{$hours}h {$minutes}m",
        ];
    }

    /**
     * Check if all checks passed
     * 
     * @param array $checks
     * @return bool
     */
    private function allChecksPassed(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                return false;
            }
        }
        return true;
    }
}
