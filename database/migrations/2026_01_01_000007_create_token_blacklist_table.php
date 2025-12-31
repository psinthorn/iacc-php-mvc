<?php

/**
 * Create Token Blacklist Table
 * 
 * Migration: 2026_01_01_000007_create_token_blacklist_table
 * 
 * Creates the token_blacklist table for storing revoked tokens (logout)
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS token_blacklist (
            id INT PRIMARY KEY AUTO_INCREMENT,
            token_jti VARCHAR(255) NOT NULL UNIQUE,
            user_id INT,
            revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS token_blacklist");
        return true;
    }
];
