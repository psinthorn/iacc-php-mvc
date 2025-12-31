<?php

namespace App\Cache;

/**
 * Array Cache Implementation
 * 
 * In-memory cache for development and testing
 * Not suitable for production (not shared across processes)
 */
class ArrayCache implements CacheInterface
{
    /**
     * Cache storage
     */
    private array $store = [];

    /**
     * Cache expiration times
     */
    private array $expiration = [];

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
     * Get value from cache
     */
    public function get(string $key, $default = null)
    {
        // Check if key exists and not expired
        if (!$this->has($key)) {
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $this->store[$key];
    }

    /**
     * Put value in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $this->store[$key] = $value;

        if ($ttl !== null) {
            $this->expiration[$key] = time() + $ttl;
        } else {
            unset($this->expiration[$key]);
        }

        $this->stats['sets']++;
        return true;
    }

    /**
     * Check if key exists and not expired
     */
    public function has(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        // Check expiration
        if (isset($this->expiration[$key]) && $this->expiration[$key] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * Delete single key
     */
    public function delete(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        unset($this->store[$key]);
        unset($this->expiration[$key]);

        $this->stats['deletes']++;
        return true;
    }

    /**
     * Delete multiple keys
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * Delete keys matching pattern
     */
    public function flush(string $pattern = "*"): bool
    {
        // Simple pattern matching (convert * to regex)
        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

        foreach (array_keys($this->store) as $key) {
            if (preg_match($regex, $key)) {
                $this->delete($key);
            }
        }

        return true;
    }

    /**
     * Clear entire cache
     */
    public function clear(): bool
    {
        $this->store = [];
        $this->expiration = [];
        return true;
    }

    /**
     * Get multiple values
     */
    public function getMultiple(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->store[$key];
            }
        }

        return $result;
    }

    /**
     * Put multiple values
     */
    public function putMultiple(array $items, ?int $ttl = null): bool
    {
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
     * Get cache statistics
     */
    public function stats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];

        return [
            'driver' => 'array',
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'hit_rate' => $total > 0 ? round(100 * $this->stats['hits'] / $total, 2) : 0,
            'items_stored' => count($this->store),
            'memory_usage' => number_format(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }

    /**
     * Clean up expired items
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $now = time();

        foreach ($this->expiration as $key => $expireTime) {
            if ($expireTime < $now) {
                $this->delete($key);
                $deleted++;
            }
        }

        return $deleted;
    }
}
