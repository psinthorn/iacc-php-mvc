<?php

namespace App\Cache;

/**
 * Cache Interface
 * 
 * Defines the contract for cache implementations
 */
interface CacheInterface
{
    /**
     * Get value from cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Put value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int|null $ttl Time to live in seconds (null = never expire)
     * @return bool
     */
    public function put(string $key, $value, ?int $ttl = null): bool;

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Delete value from cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple keys from cache
     * 
     * @param array $keys Cache keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Delete all keys matching pattern
     * 
     * @param string $pattern Pattern (e.g., "cache:*")
     * @return bool
     */
    public function flush(string $pattern = "*"): bool;

    /**
     * Clear entire cache
     * 
     * @return bool
     */
    public function clear(): bool;

    /**
     * Get multiple values
     * 
     * @param array $keys Cache keys
     * @return array
     */
    public function getMultiple(array $keys): array;

    /**
     * Put multiple values
     * 
     * @param array $items Key-value pairs
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function putMultiple(array $items, ?int $ttl = null): bool;

    /**
     * Get or put - fetch from cache or compute and store
     * 
     * @param string $key Cache key
     * @param callable $callback Function to compute value
     * @param int|null $ttl Time to live in seconds
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null);

    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function stats(): array;
}
