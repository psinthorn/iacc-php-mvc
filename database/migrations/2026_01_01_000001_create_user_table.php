<?php

/**
 * Create User Table for Authentication
 * 
 * Migration: 2026_01_01_000001_create_user_table
 * 
 * Creates the user table with authentication fields
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS user (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email_verified_at DATETIME NULL,
            last_login_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS user");
        return true;
    }
];
