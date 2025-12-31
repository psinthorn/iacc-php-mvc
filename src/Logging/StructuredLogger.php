<?php

namespace App\Logging;

use JsonSerializable;
use stdClass;

class StructuredLogger implements JsonSerializable
{
    /**
     * Log levels
     */
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    /**
     * Log context
     */
    private array $context = [];

    /**
     * Log metadata
     */
    private array $metadata = [];

    /**
     * Sensitive keys to redact
     */
    private array $sensitiveKeys = [
        'password',
        'token',
        'secret',
        'api_key',
        'credit_card',
        'ssn',
        'cvv',
        'authorization',
        'x-api-key',
        'jwt',
    ];

    /**
     * Create a new structured logger instance
     */
    public function __construct()
    {
        $this->initializeMetadata();
    }

    /**
     * Initialize metadata
     */
    private function initializeMetadata(): void
    {
        $this->metadata = [
            'timestamp' => date('Y-m-d\TH:i:s.000\Z'),
            'request_id' => $this->getRequestId(),
            'user_id' => $this->getUserId(),
            'session_id' => $this->getSessionId(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];
    }

    /**
     * Get request ID from headers or generate new one
     */
    private function getRequestId(): string
    {
        $headers = getallheaders();
        return $headers['X-Request-ID'] ?? $headers['X-Correlation-ID'] ?? uniqid('req_', true);
    }

    /**
     * Get authenticated user ID
     */
    private function getUserId(): ?string
    {
        // This should be replaced with your actual auth implementation
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get session ID
     */
    private function getSessionId(): ?string
    {
        return session_id() ?: null;
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log an exception
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => array_map(function ($trace) {
                return [
                    'file' => $trace['file'] ?? null,
                    'line' => $trace['line'] ?? null,
                    'function' => $trace['function'] ?? null,
                    'class' => $trace['class'] ?? null,
                ];
            }, $exception->getTrace()),
        ];

        $level = $exception instanceof \LogicException ? self::WARNING : self::ERROR;
        $this->log($level, "Exception: {$exception->getMessage()}", $context);
    }

    /**
     * Log an HTTP request
     */
    public function logRequest(string $method, string $uri, array $headers = [], array $body = []): void
    {
        $this->info('HTTP Request', [
            'method' => $method,
            'uri' => $uri,
            'headers' => $this->redactSensitiveData($headers),
            'body' => $this->redactSensitiveData($body),
        ]);
    }

    /**
     * Log an HTTP response
     */
    public function logResponse(int $statusCode, array $headers = [], $body = null): void
    {
        $level = $statusCode >= 500 ? self::ERROR : ($statusCode >= 400 ? self::WARNING : self::INFO);

        $this->log($level, 'HTTP Response', [
            'status_code' => $statusCode,
            'headers' => $this->redactSensitiveData($headers),
            'body' => is_string($body) ? mb_substr($body, 0, 1000) : $body,
        ]);
    }

    /**
     * Log a database query
     */
    public function logQuery(string $query, array $bindings = [], float $executionTime = 0.0): void
    {
        $level = $executionTime > 1.0 ? self::WARNING : self::DEBUG;

        $this->log($level, 'Database Query', [
            'query' => $query,
            'bindings' => $this->redactSensitiveData($bindings),
            'execution_time' => $executionTime,
        ]);
    }

    /**
     * Log a business event
     */
    public function logBusinessEvent(string $eventName, array $data = []): void
    {
        $this->info("Business Event: {$eventName}", [
            'event_name' => $eventName,
            'event_data' => $data,
        ]);
    }

    /**
     * Log a security event
     */
    public function logSecurityEvent(string $event, array $details = []): void
    {
        $this->critical("Security Event: {$event}", [
            'event' => $event,
            'details' => $details,
        ]);
    }

    /**
     * Main logging method
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Update timestamp
        $this->metadata['timestamp'] = date('Y-m-d\TH:i:s.000\Z');

        // Redact sensitive data
        $context = $this->redactSensitiveData($context);

        // Build log entry
        $logEntry = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'metadata' => $this->metadata,
        ];

        // Write to appropriate channels
        $this->writeToChannel($level, $logEntry);
    }

    /**
     * Write log to appropriate channels
     */
    private function writeToChannel(string $level, array $logEntry): void
    {
        $json = json_encode($logEntry);

        // Write to stdout/stderr based on level
        if ($level === self::ERROR || $level === self::CRITICAL) {
            fwrite(STDERR, $json . PHP_EOL);
        } else {
            fwrite(STDOUT, $json . PHP_EOL);
        }

        // Write to file if configured
        $logFile = $this->getLogFilePath($level);
        if ($logFile) {
            file_put_contents($logFile, $json . PHP_EOL, FILE_APPEND);
        }

        // Send to external service if configured
        if (getenv('LOG_EXTERNAL_SERVICE')) {
            $this->sendToExternalService($logEntry);
        }
    }

    /**
     * Get log file path for level
     */
    private function getLogFilePath(string $level): ?string
    {
        $logsDir = getenv('LOG_PATH') ?: '/var/log/app';

        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $filename = match ($level) {
            self::DEBUG, self::INFO => 'app.log',
            self::WARNING => 'warnings.log',
            self::ERROR, self::CRITICAL => 'errors.log',
        };

        return "$logsDir/$filename";
    }

    /**
     * Send log to external service (ELK, Datadog, etc.)
     */
    private function sendToExternalService(array $logEntry): void
    {
        // Implement external service integration
        // This is a placeholder for Elasticsearch, Datadog, Sentry, etc.
    }

    /**
     * Redact sensitive data from arrays
     */
    private function redactSensitiveData(array $data): array
    {
        $redacted = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if key is sensitive
            $isSensitive = false;
            foreach ($this->sensitiveKeys as $sensitiveKey) {
                if (stripos($lowerKey, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $redacted[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveData($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Add custom context data
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Add custom metadata
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Clear context
     */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * Get context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Implement JsonSerializable interface
     */
    public function jsonSerialize(): mixed
    {
        return [
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Add a sensitive key to redaction list
     */
    public function addSensitiveKey(string $key): void
    {
        $this->sensitiveKeys[] = $key;
    }

    /**
     * Add multiple sensitive keys
     */
    public function addSensitiveKeys(array $keys): void
    {
        $this->sensitiveKeys = array_merge($this->sensitiveKeys, $keys);
    }
}
