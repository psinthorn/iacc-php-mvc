-- Migration: 007_line_oa_sales_channel.sql (MySQL 5.7 compatible)
-- LINE OA Sales Channel Module — Idempotent

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS migrate_line_oa_tables()
BEGIN
    -- 1. LINE OA Configuration
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_oa_config') THEN
        CREATE TABLE line_oa_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            channel_id VARCHAR(255) DEFAULT NULL,
            channel_secret VARCHAR(255) DEFAULT NULL,
            channel_access_token TEXT DEFAULT NULL,
            webhook_url VARCHAR(500) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 0,
            rich_menu_id VARCHAR(255) DEFAULT NULL,
            greeting_message TEXT DEFAULT NULL,
            auto_reply_enabled TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            INDEX idx_company (company_id),
            UNIQUE KEY uk_company (company_id, deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 2. LINE Users
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_users') THEN
        CREATE TABLE line_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            line_user_id VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) DEFAULT NULL,
            picture_url VARCHAR(500) DEFAULT NULL,
            status_message TEXT DEFAULT NULL,
            user_type ENUM('customer', 'agent') DEFAULT 'customer',
            linked_user_id INT DEFAULT NULL,
            linked_company_customer_id INT DEFAULT NULL,
            is_blocked TINYINT(1) DEFAULT 0,
            last_interaction_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            INDEX idx_company (company_id),
            INDEX idx_line_user (line_user_id),
            INDEX idx_user_type (user_type),
            UNIQUE KEY uk_company_line_user (company_id, line_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 3. LINE Messages
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_messages') THEN
        CREATE TABLE line_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            line_user_id INT NOT NULL,
            direction ENUM('inbound', 'outbound') NOT NULL,
            message_type ENUM('text', 'image', 'sticker', 'location', 'flex', 'template', 'video', 'audio', 'file') DEFAULT 'text',
            message_id VARCHAR(255) DEFAULT NULL,
            reply_token VARCHAR(255) DEFAULT NULL,
            content TEXT DEFAULT NULL,
            media_url VARCHAR(500) DEFAULT NULL,
            status ENUM('received', 'sent', 'failed', 'read') DEFAULT 'received',
            error_message TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id),
            INDEX idx_line_user (line_user_id),
            INDEX idx_direction (direction),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 4. LINE Orders
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_orders') THEN
        CREATE TABLE line_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            line_user_id INT NOT NULL,
            order_ref VARCHAR(50) DEFAULT NULL,
            status ENUM('pending', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            order_type ENUM('customer_order', 'agent_order', 'booking') DEFAULT 'customer_order',
            guest_name VARCHAR(255) DEFAULT NULL,
            guest_phone VARCHAR(50) DEFAULT NULL,
            guest_email VARCHAR(255) DEFAULT NULL,
            items_json TEXT DEFAULT NULL,
            total_amount DECIMAL(12,2) DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'THB',
            notes TEXT DEFAULT NULL,
            booking_date DATE DEFAULT NULL,
            booking_time TIME DEFAULT NULL,
            payment_slip_url VARCHAR(500) DEFAULT NULL,
            payment_status ENUM('unpaid', 'slip_uploaded', 'confirmed', 'rejected') DEFAULT 'unpaid',
            linked_po_id INT DEFAULT NULL,
            linked_pr_id INT DEFAULT NULL,
            processed_by INT DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            INDEX idx_company (company_id),
            INDEX idx_line_user (line_user_id),
            INDEX idx_status (status),
            INDEX idx_order_type (order_type),
            INDEX idx_order_ref (order_ref),
            INDEX idx_payment_status (payment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 5. LINE Auto-Reply Rules
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_auto_replies') THEN
        CREATE TABLE line_auto_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            trigger_keyword VARCHAR(255) NOT NULL,
            match_type ENUM('exact', 'contains', 'regex') DEFAULT 'contains',
            reply_type ENUM('text', 'flex', 'template', 'image') DEFAULT 'text',
            reply_content TEXT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            priority INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            INDEX idx_company (company_id),
            INDEX idx_active (is_active),
            INDEX idx_priority (priority DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 6. LINE Webhook Events Log
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'line_webhook_events') THEN
        CREATE TABLE line_webhook_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            event_json TEXT NOT NULL,
            processed TINYINT(1) DEFAULT 0,
            error_message TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_company (company_id),
            INDEX idx_event_type (event_type),
            INDEX idx_processed (processed),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;
END //

DELIMITER ;

CALL migrate_line_oa_tables();
DROP PROCEDURE IF EXISTS migrate_line_oa_tables;
