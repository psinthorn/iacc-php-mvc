<?php
/**
 * Database Configuration
 */
return [
    'default' => getenv('DB_CONNECTION') ?: 'mysql',
    'mysql' => [
        'host' => getenv('DB_HOST') ?: 'mysql',
        'port' => getenv('DB_PORT') ?: 3306,
        'database' => getenv('DB_NAME') ?: 'iacc',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'root',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
];
