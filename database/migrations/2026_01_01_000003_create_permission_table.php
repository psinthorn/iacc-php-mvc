<?php

/**
 * Create Permission Table for RBAC
 * 
 * Migration: 2026_01_01_000003_create_permission_table
 * 
 * Creates the permission table for fine-grained access control
 */

return [
    'up' => function($pdo) {
        $sql = "
        CREATE TABLE IF NOT EXISTS permission (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            resource VARCHAR(255) NOT NULL,
            action VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_resource_action (resource, action),
            UNIQUE KEY uk_resource_action (resource, action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        return true;
    },

    'down' => function($pdo) {
        $pdo->exec("DROP TABLE IF EXISTS permission");
        return true;
    }
];
