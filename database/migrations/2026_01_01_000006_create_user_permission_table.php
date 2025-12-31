<?php

/**
 * Create User Permission Junction Table
 * 
 * Migration: 2026_01_01_000006_create_user_permission_table
 * 
 * Creates the user_permission junction table for direct permission assignment
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS user_permission (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            permission_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_permission (user_id, permission_id),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_permission_id (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS user_permission");
        return true;
    }
];
