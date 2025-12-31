<?php

/**
 * Test Helper Functions
 */

/**
 * Create test user fixture
 */
function createTestUser(array $attributes = [])
{
    $defaults = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Test123!@',
    ];

    return array_merge($defaults, $attributes);
}

/**
 * Create test role fixture
 */
function createTestRole(array $attributes = [])
{
    $defaults = [
        'name' => 'admin',
        'description' => 'Administrator role',
    ];

    return array_merge($defaults, $attributes);
}

/**
 * Create test permission fixture
 */
function createTestPermission(array $attributes = [])
{
    $defaults = [
        'name' => 'company:view',
        'resource' => 'company',
        'action' => 'view',
        'description' => 'View companies',
    ];

    return array_merge($defaults, $attributes);
}

/**
 * Create test company fixture
 */
function createTestCompany(array $attributes = [])
{
    $defaults = [
        'name' => 'Test Company',
        'email' => 'company@test.com',
        'phone' => '1234567890',
    ];

    return array_merge($defaults, $attributes);
}

/**
 * Generate test JWT token
 */
function generateTestToken(array $claims = [])
{
    $defaults = [
        'id' => 1,
        'email' => 'test@example.com',
        'name' => 'Test User',
    ];

    $userData = array_merge($defaults, $claims);
    $secret = getenv('JWT_SECRET') ?: 'test_secret_key_for_testing_only';

    return \App\Auth\Jwt::encode($userData, $secret);
}

/**
 * Truncate table
 */
function truncateTable($db, string $table): void
{
    $db->exec("TRUNCATE TABLE {$table}");
}

/**
 * Truncate all tables
 */
function truncateAllTables($db): void
{
    $tables = [
        'token_blacklist',
        'user_permission',
        'role_permission',
        'user_role',
        'permission',
        'role',
        'user',
    ];

    foreach ($tables as $table) {
        try {
            truncateTable($db, $table);
        } catch (\Exception $e) {
            // Table may not exist
        }
    }
}
