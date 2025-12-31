<?php

/**
 * Create Role Permission Junction Table
 * 
 * Migration: 2026_01_01_000005_create_role_permission_table
 * 
 * Creates the role_permission junction table for many-to-many relationship
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS role_permission (
            id INT PRIMARY KEY AUTO_INCREMENT,
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_role_permission (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permission(id) ON DELETE CASCADE,
            INDEX idx_role_id (role_id),
            INDEX idx_permission_id (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS role_permission");
        return true;
    }
];
