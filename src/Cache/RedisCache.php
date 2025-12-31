<?php

namespace App\Cache;

/**
 * Redis Cache Implementation
 * 
 * High-performance cache using Redis
 * Suitable for production environments with shared cache needs
 */
class RedisCache implements CacheInterface
{
    /**
     * Redis connection
     */
    private \Redis $redis;

    /**
     * Cache key prefix
     */
    private string $prefix = 'cache:';

    /**
     * Default TTL in seconds (1 hour)
     */
    private int $defaultTTL = 3600;

    /**
     * Stats
     */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    /**
     * Initialize Redis connection
     */
    public function __construct()
    {
        $this->redis = new \Redis();

        $host = getenv('REDIS_HOST') ?: 'localhost';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $password = getenv('REDIS_PASSWORD') ?: null;
        $database = (int)(getenv('REDIS_DB') ?: 0);

        // Connect to Redis
        if (!$this->redis->connect($host, $port, 5)) {
            throw new \RuntimeException("Failed to connect to Redis at {$host}:{$port}");
        }

        // Authenticate if password provided
        if ($password && !$this->redis->auth($password)) {
            throw new \RuntimeException("Failed to authenticate with Redis");
        }

        // Select database
        $this->redis->select($database);

        // Set prefix
        $this->prefix = getenv('CACHE_PREFIX') ?: 'iacc:';
    }

    /**
     * Get value from cache
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->makeKey($key);
        $value = $this->redis->get($cacheKey);

        if ($value === false) {
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return unserialize($value);
    }

    /**
     * Put value in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->makeKey($key);
        $ttl = $ttl ?? $this->defaultTTL;
        $serialized = serialize($value);

        $result = $this->redis->setex($cacheKey, $ttl, $serialized);

        if ($result) {
            $this->stats['sets']++;
        }

        return (bool)$result;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->makeKey($key);
        return (bool)$this->redis->exists($cacheKey);
    }

    /**
     * Delete single key
     */
    public function delete(string $key): bool
    {
        $cacheKey = $this->makeKey($key);
        $deleted = $this->redis->delete($cacheKey);

        if ($deleted > 0) {
            $this->stats['deletes']++;
        }

        return $deleted > 0;
    }

    /**
     * Delete multiple keys
     */
    public function deleteMultiple(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }

        $cacheKeys = array_map([$this, 'makeKey'], $keys);
        $deleted = $this->redis->delete(...$cacheKeys);

        $this->stats['deletes'] += $deleted;

        return true;
    }

    /**
     * Delete keys matching pattern
     */
    public function flush(string $pattern = "*"): bool
    {
        $pattern = $this->makeKey($pattern);
        $keys = $this->redis->keys($pattern);

        if (empty($keys)) {
            return true;
        }

        $deleted = $this->redis->delete(...$keys);
        $this->stats['deletes'] += $deleted;

        return true;
    }

    /**
     * Clear entire cache
     */
    public function clear(): bool
    {
        // Flush only this prefix's keys
        return $this->flush("*");
    }

    /**
     * Get multiple values
     */
    public function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $cacheKeys = array_map([$this, 'makeKey'], $keys);
        $values = $this->redis->mget($cacheKeys);

        $result = [];
        foreach ($keys as $key) {
            $cacheKey = $this->makeKey($key);
            foreach ($values as $value) {
                if ($value !== false) {
                    $result[$key] = unserialize($value);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Put multiple values
     */
    public function putMultiple(array $items, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;

        foreach ($items as $key => $value) {
            $this->put($key, $value, $ttl);
        }

        return true;
    }

    /**
     * Remember - get or compute
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Increment counter
     */
    public function increment(string $key, int $value = 1): int
    {
        $cacheKey = $this->makeKey($key);
        return (int)$this->redis->incrBy($cacheKey, $value);
    }

    /**
     * Decrement counter
     */
    public function decrement(string $key, int $value = 1): int
    {
        $cacheKey = $this->makeKey($key);
        return (int)$this->redis->decrBy($cacheKey, $value);
    }

    /**
     * Add to set
     */
    public function addToSet(string $key, ...$members): int
    {
        $cacheKey = $this->makeKey($key);
        return (int)$this->redis->sadd($cacheKey, ...$members);
    }

    /**
     * Get set members
     */
    public function getSetMembers(string $key): array
    {
        $cacheKey = $this->makeKey($key);
        return $this->redis->smembers($cacheKey) ?: [];
    }

    /**
     * Push to list
     */
    public function push(string $key, $value): int
    {
        $cacheKey = $this->makeKey($key);
        return (int)$this->redis->lpush($cacheKey, serialize($value));
    }

    /**
     * Get list items
     */
    public function getList(string $key, int $start = 0, int $stop = -1): array
    {
        $cacheKey = $this->makeKey($key);
        $items = $this->redis->lrange($cacheKey, $start, $stop);

        return array_map('unserialize', $items);
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        $redisInfo = $this->redis->info('stats');

        return [
            'driver' => 'redis',
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'hit_rate' => $this->stats['hits'] + $this->stats['misses'] > 0
                ? round(100 * $this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses']), 2)
                : 0,
            'memory_usage' => $redisInfo['used_memory_human'] ?? 'unknown',
            'connected_clients' => $redisInfo['connected_clients'] ?? 0,
            'total_commands' => $redisInfo['total_commands_processed'] ?? 0,
        ];
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }

    /**
     * Make cache key with prefix
     */
    private function makeKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->close();
    }
}
