<?php

namespace App\Config;

/**
 * Cache Configuration
 * 
 * Defines cache TTLs and strategies for different data types
 */
class CacheConfig
{
    /**
     * Cache TTL (Time To Live) in seconds
     */
    const TTL_SECONDS = [
        // Authentication & Authorization (frequently updated)
        'user' => 120,                      // 2 minutes
        'user_permissions' => 300,          // 5 minutes
        'user_roles' => 600,                // 10 minutes
        'auth_token' => 3600,               // 1 hour

        // Company/Product Data (moderate change frequency)
        'company' => 3600,                  // 1 hour
        'product' => 1800,                  // 30 minutes
        'category' => 7200,                 // 2 hours
        'brand' => 7200,                    // 2 hours
        'product_type' => 7200,             // 2 hours

        // Purchase Orders (moderate change frequency)
        'purchase_order' => 900,            // 15 minutes
        'purchase_order_items' => 900,      // 15 minutes
        'purchase_orders_list' => 600,      // 10 minutes

        // Invoices (moderate change frequency)
        'invoice' => 600,                   // 10 minutes
        'invoice_items' => 600,             // 10 minutes
        'invoices_list' => 600,             // 10 minutes

        // Payments (moderate change frequency)
        'payment' => 300,                   // 5 minutes
        'payments_list' => 300,             // 5 minutes

        // Configuration (rarely changes)
        'config' => 86400,                  // 24 hours
        'system_settings' => 86400,         // 24 hours
        'roles_all' => 43200,               // 12 hours
        'permissions_all' => 43200,         // 12 hours

        // Reports (on-demand)
        'report' => 300,                    // 5 minutes
        'report_list' => 600,               // 10 minutes

        // Session data
        'session' => 1800,                  // 30 minutes
        'session_user' => 1800,             // 30 minutes

        // Hot data (very frequently accessed)
        'top_companies' => 3600,            // 1 hour
        'top_products' => 3600,             // 1 hour
        'recent_orders' => 300,             // 5 minutes
    ];

    /**
     * Cache key prefixes
     */
    const KEY_PREFIXES = [
        'user' => 'user:',
        'company' => 'company:',
        'product' => 'product:',
        'purchase_order' => 'po:',
        'invoice' => 'iv:',
        'payment' => 'payment:',
        'category' => 'category:',
        'brand' => 'brand:',
        'report' => 'report:',
        'session' => 'session:',
        'query' => 'query:',
        'config' => 'config:',
    ];

    /**
     * Get TTL for data type
     */
    public static function getTTL(string $type): int
    {
        return self::TTL_SECONDS[$type] ?? 3600; // Default 1 hour
    }

    /**
     * Get cache key prefix
     */
    public static function getPrefix(string $type): string
    {
        return self::KEY_PREFIXES[$type] ?? $type . ':';
    }

    /**
     * Make cache key
     */
    public static function makeKey(string $type, string $identifier): string
    {
        return self::getPrefix($type) . $identifier;
    }

    /**
     * Cache invalidation patterns for collections
     */
    public static function getInvalidationPattern(string $type): string
    {
        return self::getPrefix($type) . '*';
    }

    /**
     * Data types that should warm cache on startup
     */
    const CACHE_WARMABLE = [
        'roles',
        'permissions',
        'config',
        'categories',
    ];

    /**
     * Data types sensitive to user context (not globally cached)
     */
    const USER_CONTEXT_SENSITIVE = [
        'user_permissions',
        'user_roles',
        'reports',
        'saved_filters',
    ];

    /**
     * Data types requiring immediate invalidation on change
     */
    const IMMEDIATE_INVALIDATION = [
        'user',
        'auth_token',
        'user_permissions',
        'user_roles',
    ];

    /**
     * Data types with lazy invalidation (eventual consistency)
     */
    const LAZY_INVALIDATION = [
        'category',
        'brand',
        'company',
        'product',
    ];

    /**
     * Get cache strategy for data type
     */
    public static function getStrategy(string $type): string
    {
        if (in_array($type, self::IMMEDIATE_INVALIDATION)) {
            return 'immediate';
        }

        if (in_array($type, self::LAZY_INVALIDATION)) {
            return 'lazy';
        }

        return 'standard';
    }

    /**
     * Check if type should cache user context
     */
    public static function isUserContextSensitive(string $type): bool
    {
        return in_array($type, self::USER_CONTEXT_SENSITIVE);
    }

    /**
     * Get cache warmup data
     */
    public static function getWarmupData(): array
    {
        return self::CACHE_WARMABLE;
    }

    /**
     * Get all cache configuration
     */
    public static function getAll(): array
    {
        return [
            'ttls' => self::TTL_SECONDS,
            'prefixes' => self::KEY_PREFIXES,
            'warmable' => self::CACHE_WARMABLE,
            'user_context_sensitive' => self::USER_CONTEXT_SENSITIVE,
            'immediate_invalidation' => self::IMMEDIATE_INVALIDATION,
            'lazy_invalidation' => self::LAZY_INVALIDATION,
        ];
    }

    /**
     * Cache statistics thresholds
     */
    const STATS_THRESHOLDS = [
        'min_hit_rate' => 60,           // Alert if hit rate < 60%
        'max_memory_percent' => 80,     // Alert if Redis uses > 80% memory
        'min_items_cached' => 1000,     // Warn if < 1000 items cached
    ];

    /**
     * Check cache health
     */
    public static function checkHealth(array $stats): array
    {
        $issues = [];

        if ($stats['hit_rate'] < self::STATS_THRESHOLDS['min_hit_rate']) {
            $issues[] = "Cache hit rate low: {$stats['hit_rate']}%";
        }

        return $issues;
    }
}
