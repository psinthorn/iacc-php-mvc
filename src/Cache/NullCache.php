<?php

namespace App\Cache;

/**
 * Null Cache Implementation
 * 
 * No-op cache that doesn't actually cache anything
 * Useful for disabling caching in testing or development
 */
class NullCache implements CacheInterface
{
    /**
     * Get value - always returns default
     */
    public function get(string $key, $default = null)
    {
        return $default;
    }

    /**
     * Put value - does nothing
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return true;
    }

    /**
     * Has key - always returns false
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * Delete key - does nothing
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * Delete multiple - does nothing
     */
    public function deleteMultiple(array $keys): bool
    {
        return true;
    }

    /**
     * Flush - does nothing
     */
    public function flush(string $pattern = "*"): bool
    {
        return true;
    }

    /**
     * Clear - does nothing
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * Get multiple - always returns empty
     */
    public function getMultiple(array $keys): array
    {
        return [];
    }

    /**
     * Put multiple - does nothing
     */
    public function putMultiple(array $items, ?int $ttl = null): bool
    {
        return true;
    }

    /**
     * Remember - always computes and returns value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        return $callback();
    }

    /**
     * Get stats - no stats to return
     */
    public function stats(): array
    {
        return [
            'driver' => 'null',
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'hit_rate' => 0,
            'note' => 'Caching is disabled',
        ];
    }
}
