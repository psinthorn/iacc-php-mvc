<?php

namespace App\Monitoring;

use stdClass;

class MetricsCollector
{
    /**
     * Singleton instance
     */
    private static ?MetricsCollector $instance = null;

    /**
     * Collected metrics
     */
    private array $metrics = [];

    /**
     * Metric counters
     */
    private array $counters = [];

    /**
     * Metric gauges
     */
    private array $gauges = [];

    /**
     * Metric histograms
     */
    private array $histograms = [];

    /**
     * Start times for duration tracking
     */
    private array $timers = [];

    /**
     * Metrics export format
     */
    private string $exportFormat = 'prometheus';

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        // Initialize default metrics
        $this->initializeDefaultMetrics();
    }

    /**
     * Initialize default metrics
     */
    private function initializeDefaultMetrics(): void
    {
        // HTTP Metrics
        $this->counters['http_requests_total'] = 0;
        $this->counters['http_requests_success'] = 0;
        $this->counters['http_requests_error'] = 0;
        $this->gauges['http_request_duration_ms'] = [];

        // Database Metrics
        $this->counters['db_queries_total'] = 0;
        $this->counters['db_queries_slow'] = 0;
        $this->gauges['db_query_duration_ms'] = [];

        // Cache Metrics
        $this->counters['cache_hits'] = 0;
        $this->counters['cache_misses'] = 0;
        $this->gauges['cache_hit_rate'] = 0;

        // Application Metrics
        $this->counters['errors_total'] = 0;
        $this->counters['exceptions_total'] = 0;
        $this->counters['business_events'] = [];

        // Performance Metrics
        $this->gauges['memory_usage_mb'] = 0;
        $this->gauges['memory_peak_mb'] = 0;
        $this->gauges['uptime_seconds'] = 0;
    }

    /**
     * Record an HTTP request
     */
    public function recordHttpRequest(string $method, string $endpoint, int $statusCode, float $duration): void
    {
        $this->incrementCounter('http_requests_total');

        if ($statusCode >= 400) {
            $this->incrementCounter('http_requests_error');
        } else {
            $this->incrementCounter('http_requests_success');
        }

        // Record duration histogram
        $key = "{$method}_{$endpoint}";
        if (!isset($this->histograms[$key])) {
            $this->histograms[$key] = [];
        }
        $this->histograms[$key][] = $duration;

        // Record duration gauge (latest value)
        $this->gauges['http_request_duration_ms'][] = $duration;
    }

    /**
     * Record a database query
     */
    public function recordDatabaseQuery(string $query, float $duration, bool $isSlow = false): void
    {
        $this->incrementCounter('db_queries_total');

        if ($isSlow) {
            $this->incrementCounter('db_queries_slow');
        }

        // Record duration histogram
        if (!isset($this->histograms['db_queries'])) {
            $this->histograms['db_queries'] = [];
        }
        $this->histograms['db_queries'][] = $duration;

        // Record query type
        $queryType = $this->extractQueryType($query);
        $counterKey = "db_queries_{$queryType}";
        $this->incrementCounter($counterKey);
    }

    /**
     * Record cache hit/miss
     */
    public function recordCacheAccess(string $key, bool $hit): void
    {
        if ($hit) {
            $this->incrementCounter('cache_hits');
        } else {
            $this->incrementCounter('cache_misses');
        }

        // Calculate hit rate
        $total = $this->counters['cache_hits'] + $this->counters['cache_misses'];
        if ($total > 0) {
            $this->gauges['cache_hit_rate'] = ($this->counters['cache_hits'] / $total) * 100;
        }
    }

    /**
     * Record an error
     */
    public function recordError(string $message, string $type = 'error'): void
    {
        $this->incrementCounter('errors_total');

        // Record by type
        $typeKey = "errors_{$type}";
        $this->incrementCounter($typeKey);
    }

    /**
     * Record an exception
     */
    public function recordException(string $exceptionType, string $message): void
    {
        $this->incrementCounter('exceptions_total');

        // Record by exception type
        $typeKey = "exceptions_" . str_replace('\\', '_', $exceptionType);
        $this->incrementCounter($typeKey);
    }

    /**
     * Record a business event
     */
    public function recordBusinessEvent(string $eventName, array $data = []): void
    {
        if (!isset($this->counters["event_{$eventName}"])) {
            $this->counters["event_{$eventName}"] = 0;
        }
        $this->incrementCounter("event_{$eventName}");

        // Record event details for analysis
        if (!isset($this->metrics['business_events'])) {
            $this->metrics['business_events'] = [];
        }
        $this->metrics['business_events'][] = [
            'event' => $eventName,
            'timestamp' => time(),
            'data' => $data,
        ];
    }

    /**
     * Start a timer
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    /**
     * Stop a timer and record duration
     */
    public function stopTimer(string $name, string $metricName = null): float
    {
        if (!isset($this->timers[$name])) {
            return 0;
        }

        $duration = (microtime(true) - $this->timers[$name]) * 1000; // Convert to milliseconds
        unset($this->timers[$name]);

        $metric = $metricName ?? $name;
        if (!isset($this->histograms[$metric])) {
            $this->histograms[$metric] = [];
        }
        $this->histograms[$metric][] = $duration;

        return $duration;
    }

    /**
     * Increment a counter
     */
    public function incrementCounter(string $name, int $amount = 1): void
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        $this->counters[$name] += $amount;
    }

    /**
     * Set a gauge value
     */
    public function setGauge(string $name, float $value): void
    {
        $this->gauges[$name] = $value;
    }

    /**
     * Increment a gauge
     */
    public function incrementGauge(string $name, float $amount = 1): void
    {
        if (!isset($this->gauges[$name])) {
            $this->gauges[$name] = 0;
        }
        $this->gauges[$name] += $amount;
    }

    /**
     * Record system metrics
     */
    public function recordSystemMetrics(): void
    {
        // Memory usage
        $this->gauges['memory_usage_mb'] = round(memory_get_usage() / 1024 / 1024, 2);
        $this->gauges['memory_peak_mb'] = round(memory_get_peak_usage() / 1024 / 1024, 2);

        // CPU usage (if available)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $this->gauges['cpu_load_1min'] = $load[0];
            $this->gauges['cpu_load_5min'] = $load[1];
            $this->gauges['cpu_load_15min'] = $load[2];
        }

        // Disk usage
        $this->recordDiskMetrics();

        // Process info
        if (function_exists('getrusage')) {
            $usage = getrusage();
            $this->gauges['process_cpu_time_seconds'] = $usage['ru_utime.tv_sec'] + ($usage['ru_utime.tv_usec'] / 1000000);
        }
    }

    /**
     * Record disk metrics
     */
    private function recordDiskMetrics(): void
    {
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? '/tmp/uploads';

        if (is_dir($uploadPath)) {
            $freeSpace = disk_free_space($uploadPath);
            $totalSpace = disk_total_space($uploadPath);

            if ($freeSpace !== false && $totalSpace !== false) {
                $this->gauges['disk_free_gb'] = round($freeSpace / 1024 / 1024 / 1024, 2);
                $this->gauges['disk_total_gb'] = round($totalSpace / 1024 / 1024 / 1024, 2);
                $this->gauges['disk_used_percent'] = round(((($totalSpace - $freeSpace) / $totalSpace) * 100), 2);
            }
        }
    }

    /**
     * Get all metrics
     */
    public function getMetrics(): array
    {
        $this->recordSystemMetrics();

        return [
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'counters' => $this->counters,
            'gauges' => $this->gauges,
            'histograms' => $this->getHistogramSummaries(),
        ];
    }

    /**
     * Get histogram summaries (min, max, avg, p50, p95, p99)
     */
    private function getHistogramSummaries(): array
    {
        $summaries = [];

        foreach ($this->histograms as $name => $values) {
            if (empty($values)) {
                continue;
            }

            sort($values);
            $count = count($values);

            $summaries[$name] = [
                'count' => $count,
                'sum' => array_sum($values),
                'min' => min($values),
                'max' => max($values),
                'avg' => array_sum($values) / $count,
                'p50' => $this->percentile($values, 50),
                'p95' => $this->percentile($values, 95),
                'p99' => $this->percentile($values, 99),
            ];
        }

        return $summaries;
    }

    /**
     * Calculate percentile
     */
    private function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        $index = ceil(($percentile / 100) * $count) - 1;
        return $values[$index] ?? 0;
    }

    /**
     * Export metrics in Prometheus format
     */
    public function exportPrometheus(): string
    {
        $metrics = $this->getMetrics();
        $output = "# HELP app_metrics iACC Application Metrics\n";
        $output .= "# TYPE app_metrics gauge\n\n";

        // Export counters
        foreach ($metrics['counters'] as $name => $value) {
            $output .= "app_{$name}{} {$value}\n";
        }

        // Export gauges
        foreach ($metrics['gauges'] as $name => $value) {
            if (is_array($value)) {
                continue;
            }
            $output .= "app_{$name}{} {$value}\n";
        }

        // Export histogram summaries
        foreach ($metrics['histograms'] as $name => $summary) {
            $output .= "app_{$name}_count{} {$summary['count']}\n";
            $output .= "app_{$name}_sum{} {$summary['sum']}\n";
            $output .= "app_{$name}_min{} {$summary['min']}\n";
            $output .= "app_{$name}_max{} {$summary['max']}\n";
            $output .= "app_{$name}_avg{} {$summary['avg']}\n";
            $output .= "app_{$name}_p95{} {$summary['p95']}\n";
        }

        return $output;
    }

    /**
     * Export metrics as JSON
     */
    public function exportJson(): string
    {
        return json_encode($this->getMetrics(), JSON_PRETTY_PRINT);
    }

    /**
     * Reset all metrics
     */
    public function reset(): void
    {
        $this->counters = [];
        $this->gauges = [];
        $this->histograms = [];
        $this->metrics = [];
        $this->initializeDefaultMetrics();
    }

    /**
     * Extract query type from SQL query
     */
    private function extractQueryType(string $query): string
    {
        $query = strtoupper(trim($query));

        if (str_starts_with($query, 'SELECT')) {
            return 'select';
        } elseif (str_starts_with($query, 'INSERT')) {
            return 'insert';
        } elseif (str_starts_with($query, 'UPDATE')) {
            return 'update';
        } elseif (str_starts_with($query, 'DELETE')) {
            return 'delete';
        } elseif (str_starts_with($query, 'CREATE')) {
            return 'create';
        } elseif (str_starts_with($query, 'ALTER')) {
            return 'alter';
        } else {
            return 'other';
        }
    }
}
