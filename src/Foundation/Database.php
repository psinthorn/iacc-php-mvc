<?php

namespace App\Foundation;

use PDO;
use PDOStatement;
use Exception;

/**
 * Database - PDO database wrapper
 * 
 * Provides:
 * - Connection management
 * - Query execution with prepared statements
 * - Transaction support
 * - Error handling
 */
class Database
{
    /**
     * PDO connection instance
     * @var PDO
     */
    protected $connection;

    /**
     * Database configuration
     * @var array
     */
    protected $config;

    /**
     * Query log
     * @var array
     */
    protected $queryLog = [];

    /**
     * Logging enabled
     * @var bool
     */
    protected $loggingEnabled = false;

    /**
     * Constructor
     * 
     * @param array $config Database configuration
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Create database connection
     * 
     * @return void
     * @throws Exception
     */
    protected function connect()
    {
        try {
            $driver = $this->config['driver'] ?? 'mysql';

            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $this->config['host'],
                    $this->config['port'] ?? 3306,
                    $this->config['database']
                );
            } elseif ($driver === 'sqlite') {
                $dsn = 'sqlite:' . $this->config['database'];
            } elseif ($driver === 'pgsql') {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $this->config['host'],
                    $this->config['port'] ?? 5432,
                    $this->config['database']
                );
            } else {
                throw new Exception("Unsupported database driver: {$driver}");
            }

            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            // Set timezone for MySQL
            if ($driver === 'mysql') {
                $this->connection->exec("SET time_zone='+00:00'");
            }
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a query with prepared statement
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return PDOStatement
     */
    public function statement($sql, $bindings = [])
    {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($bindings);

            // Log query if enabled
            if ($this->loggingEnabled) {
                $this->logQuery($sql, $bindings);
            }

            return $statement;
        } catch (Exception $e) {
            throw new Exception("Query failed: " . $e->getMessage() . "\nSQL: {$sql}");
        }
    }

    /**
     * Run a SELECT query and return all results
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return array
     */
    public function select($sql, $bindings = [])
    {
        return $this->statement($sql, $bindings)->fetchAll();
    }

    /**
     * Run a SELECT query and return first result
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return array|null
     */
    public function selectOne($sql, $bindings = [])
    {
        return $this->statement($sql, $bindings)->fetch();
    }

    /**
     * Run an INSERT query
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return int Last inserted ID
     */
    public function insert($sql, $bindings = [])
    {
        $this->statement($sql, $bindings);
        return $this->connection->lastInsertId();
    }

    /**
     * Run an UPDATE query
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return int Number of affected rows
     */
    public function update($sql, $bindings = [])
    {
        return $this->statement($sql, $bindings)->rowCount();
    }

    /**
     * Run a DELETE query
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return int Number of affected rows
     */
    public function delete($sql, $bindings = [])
    {
        return $this->statement($sql, $bindings)->rowCount();
    }

    /**
     * Execute arbitrary SQL
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return mixed Result count or ID
     */
    public function execute($sql, $bindings = [])
    {
        $statement = $this->statement($sql, $bindings);

        if (stripos($sql, 'INSERT') === 0) {
            return $this->connection->lastInsertId();
        } elseif (stripos($sql, 'UPDATE') === 0 || stripos($sql, 'DELETE') === 0) {
            return $statement->rowCount();
        } elseif (stripos($sql, 'SELECT') === 0) {
            return $statement->fetchAll();
        }

        return true;
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }

    /**
     * Execute a transaction
     * 
     * @param callable $callback Transaction callback
     * @return mixed
     * @throws Exception
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Enable query logging
     * 
     * @return void
     */
    public function enableLogging()
    {
        $this->loggingEnabled = true;
    }

    /**
     * Disable query logging
     * 
     * @return void
     */
    public function disableLogging()
    {
        $this->loggingEnabled = false;
    }

    /**
     * Log a query
     * 
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return void
     */
    protected function logQuery($sql, $bindings = [])
    {
        $this->queryLog[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => microtime(true),
        ];
    }

    /**
     * Get query log
     * 
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear query log
     * 
     * @return void
     */
    public function clearQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Get total query count
     * 
     * @return int
     */
    public function getQueryCount()
    {
        return count($this->queryLog);
    }

    /**
     * Get total query time
     * 
     * @return float
     */
    public function getTotalTime()
    {
        if (empty($this->queryLog)) {
            return 0;
        }

        $start = $this->queryLog[0]['time'];
        $end = end($this->queryLog)['time'];

        return $end - $start;
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Close the connection
     * 
     * @return void
     */
    public function close()
    {
        $this->connection = null;
    }

    /**
     * Check if connected
     * 
     * @return bool
     */
    public function isConnected()
    {
        return $this->connection !== null;
    }

    /**
     * Get last error
     * 
     * @return array|null
     */
    public function lastError()
    {
        return $this->connection ? $this->connection->errorInfo() : null;
    }
}
