<?php

/**
 * Create User Role Junction Table
 * 
 * Migration: 2026_01_01_000004_create_user_role_table
 * 
 * Creates the user_role junction table for many-to-many relationship
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS user_role (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_role (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_role_id (role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS user_role");
        return true;
    }
];
