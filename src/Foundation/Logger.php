<?php

namespace App\Foundation;

use DateTime;

/**
 * Logger - Application Logging System
 * 
 * Logs messages at different levels:
 * - DEBUG: Detailed debug information
 * - INFO: Informational messages
 * - WARNING: Warning messages
 * - ERROR: Error messages
 */
class Logger
{
    /**
     * Log levels
     */
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';

    /**
     * Log file path
     * @var string
     */
    protected $logFile;

    /**
     * Minimum log level to record
     * @var int
     */
    protected $minLevel = 0;

    /**
     * Log level priorities
     * @var array
     */
    protected $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
    ];

    /**
     * Constructor
     * 
     * @param string $logFile Log file path
     * @param string $minLevel Minimum level to log
     */
    public function __construct($logFile, $minLevel = 'DEBUG')
    {
        $this->logFile = $logFile;
        $this->minLevel = $this->levels[$minLevel] ?? 0;

        // Create log directory if it doesn't exist
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log message at specified level
     * 
     * @param string $level Log level
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function log($level, $message, $context = [])
    {
        // Check if we should log this level
        if ($this->levels[$level] < $this->minLevel) {
            return;
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // Write to file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);

        // Also write to error log for critical errors
        if ($level === self::ERROR) {
            error_log($message . ' ' . json_encode($context));
        }
    }

    /**
     * Get log file path
     * 
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * Clear log file
     * 
     * @return void
     */
    public function clear()
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }

    /**
     * Get log contents
     * 
     * @param int $lines Number of lines to get (0 = all)
     * @return string
     */
    public function getContents($lines = 0)
    {
        if (!file_exists($this->logFile)) {
            return '';
        }

        $content = file_get_contents($this->logFile);

        if ($lines > 0) {
            $logLines = array_slice(explode("\n", $content), -$lines);
            $content = implode("\n", $logLines);
        }

        return $content;
    }

    /**
     * Get last N log entries
     * 
     * @param int $n Number of entries
     * @return array
     */
    public function getLastEntries($n = 10)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
        return array_slice($lines, -$n);
    }
}
