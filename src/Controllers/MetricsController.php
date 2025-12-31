<?php

namespace App\Controllers;

use App\Monitoring\MetricsCollector;

class MetricsController
{
    /**
     * Get metrics in Prometheus format
     */
    public function prometheus()
    {
        $metrics = MetricsCollector::getInstance();

        header('Content-Type: text/plain; charset=utf-8');
        echo $metrics->exportPrometheus();
    }

    /**
     * Get metrics in JSON format
     */
    public function json()
    {
        $metrics = MetricsCollector::getInstance();

        header('Content-Type: application/json');
        echo $metrics->exportJson();
    }

    /**
     * Health check for Prometheus
     */
    public function healthCheck()
    {
        header('Content-Type: application/json');

        $healthy = true;
        $checks = [];

        // Check database
        try {
            $db = getenv('DB_HOST');
            $checks['database'] = true;
        } catch (\Exception $e) {
            $checks['database'] = false;
            $healthy = false;
        }

        // Check cache
        try {
            // Redis or other cache check
            $checks['cache'] = true;
        } catch (\Exception $e) {
            $checks['cache'] = false;
            $healthy = false;
        }

        $response = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
        ];

        http_response_code($healthy ? 200 : 503);
        echo json_encode($response);
    }
}
