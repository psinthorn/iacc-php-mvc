<?php

namespace App\Cache;

/**
 * Cache Manager
 * 
 * Factory and abstraction for cache operations
 * Handles cache selection and provides unified interface
 */
class CacheManager
{
    /**
     * Cache instances
     */
    private static array $instances = [];

    /**
     * Default cache driver
     */
    private static string $defaultDriver = 'array';

    /**
     * Get cache instance
     */
    public static function cache(string $driver = null): CacheInterface
    {
        $driver = $driver ?? self::$defaultDriver;

        // Return existing instance
        if (isset(self::$instances[$driver])) {
            return self::$instances[$driver];
        }

        // Create new instance
        $cache = match ($driver) {
            'redis' => new RedisCache(),
            'array' => new ArrayCache(),
            'null' => new NullCache(),
            default => throw new \InvalidArgumentException("Unknown cache driver: {$driver}"),
        };

        self::$instances[$driver] = $cache;
        return $cache;
    }

    /**
     * Set default driver
     */
    public static function setDefaultDriver(string $driver): void
    {
        self::$defaultDriver = $driver;
    }

    /**
     * Get default driver
     */
    public static function getDefaultDriver(): string
    {
        return self::$defaultDriver;
    }

    /**
     * Initialize from environment
     */
    public static function initializeFromEnvironment(): void
    {
        $driver = getenv('CACHE_DRIVER') ?: 'array';
        self::setDefaultDriver($driver);

        // Pre-initialize Redis if configured
        if ($driver === 'redis') {
            try {
                self::cache('redis');
            } catch (\Throwable $e) {
                // Fall back to array cache if Redis fails
                self::setDefaultDriver('array');
            }
        }
    }

    /**
     * Shortcut: get value
     */
    public static function get(string $key, $default = null)
    {
        return self::cache()->get($key, $default);
    }

    /**
     * Shortcut: put value
     */
    public static function put(string $key, $value, ?int $ttl = null): bool
    {
        return self::cache()->put($key, $value, $ttl);
    }

    /**
     * Shortcut: check if exists
     */
    public static function has(string $key): bool
    {
        return self::cache()->has($key);
    }

    /**
     * Shortcut: delete key
     */
    public static function delete(string $key): bool
    {
        return self::cache()->delete($key);
    }

    /**
     * Shortcut: remember
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null)
    {
        return self::cache()->remember($key, $callback, $ttl);
    }

    /**
     * Shortcut: flush matching pattern
     */
    public static function flush(string $pattern = "*"): bool
    {
        return self::cache()->flush($pattern);
    }

    /**
     * Shortcut: clear all
     */
    public static function clear(): bool
    {
        return self::cache()->clear();
    }

    /**
     * Shortcut: get stats
     */
    public static function stats(): array
    {
        return self::cache()->stats();
    }

    /**
     * Clear all cache drivers
     */
    public static function clearAllDrivers(): bool
    {
        foreach (self::$instances as $cache) {
            $cache->clear();
        }
        return true;
    }

    /**
     * Get all cache instances
     */
    public static function getAllInstances(): array
    {
        return self::$instances;
    }
}
