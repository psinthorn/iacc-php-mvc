<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Foundation\ServiceContainer;
use App\Foundation\Database;

/**
 * Base Test Case Class
 * Provides common setup for all tests
 */
abstract class TestCase extends BaseTestCase
{
    protected $container;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize service container
        $this->container = new ServiceContainer();

        // Initialize test database
        $this->db = $this->createTestDatabase();

        // Register common services
        $this->registerServices();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        if ($this->db) {
            $this->db = null;
        }
    }

    /**
     * Create test database connection
     */
    protected function createTestDatabase(): Database
    {
        $config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_DATABASE') ?: 'iacc_test',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
        ];

        return new Database($config);
    }

    /**
     * Register common services in container
     */
    protected function registerServices(): void
    {
        $this->container->register('database', function () {
            return $this->db;
        });
    }

    /**
     * Get service from container
     */
    protected function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * Begin transaction for test
     */
    protected function beginTransaction(): void
    {
        if ($this->db) {
            $this->db->getPdo()->beginTransaction();
        }
    }

    /**
     * Rollback transaction after test
     */
    protected function rollbackTransaction(): void
    {
        if ($this->db && $this->db->getPdo()->inTransaction()) {
            $this->db->getPdo()->rollBack();
        }
    }

    /**
     * Assert that exception was thrown
     */
    protected function assertThrowsException(callable $callback, string $exceptionClass): void
    {
        try {
            $callback();
            $this->fail("Expected {$exceptionClass} but no exception was thrown");
        } catch (\Exception $e) {
            $this->assertInstanceOf($exceptionClass, $e);
        }
    }

    /**
     * Assert that array has keys
     */
    protected function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
