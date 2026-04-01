-- Migration: 007_line_oa_sales_channel.sql
-- LINE OA Sales Channel Module
-- Creates tables for LINE Messaging API integration

-- 1. LINE OA Configuration (per company)
CREATE TABLE IF NOT EXISTS line_oa_config (
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

-- 2. LINE Users (customers/agents who interact via LINE)
CREATE TABLE IF NOT EXISTS line_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    line_user_id VARCHAR(255) NOT NULL COMMENT 'LINE userId from webhook',
    display_name VARCHAR(255) DEFAULT NULL,
    picture_url VARCHAR(500) DEFAULT NULL,
    status_message TEXT DEFAULT NULL,
    user_type ENUM('customer', 'agent') DEFAULT 'customer',
    linked_user_id INT DEFAULT NULL COMMENT 'FK to user table if linked',
    linked_company_customer_id INT DEFAULT NULL COMMENT 'FK to company table if linked as customer',
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

-- 3. LINE Messages Log (inbound + outbound)
CREATE TABLE IF NOT EXISTS line_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    line_user_id INT NOT NULL COMMENT 'FK to line_users.id',
    direction ENUM('inbound', 'outbound') NOT NULL,
    message_type ENUM('text', 'image', 'sticker', 'location', 'flex', 'template', 'video', 'audio', 'file') DEFAULT 'text',
    message_id VARCHAR(255) DEFAULT NULL COMMENT 'LINE message ID',
    reply_token VARCHAR(255) DEFAULT NULL,
    content TEXT DEFAULT NULL COMMENT 'Message text or JSON payload',
    media_url VARCHAR(500) DEFAULT NULL COMMENT 'URL for image/video/file',
    status ENUM('received', 'sent', 'failed', 'read') DEFAULT 'received',
    error_message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_direction (direction),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. LINE Orders (orders placed via LINE conversation)
CREATE TABLE IF NOT EXISTS line_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    line_user_id INT NOT NULL COMMENT 'FK to line_users.id',
    order_ref VARCHAR(50) DEFAULT NULL COMMENT 'e.g. LINE-20260401-001',
    status ENUM('pending', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    order_type ENUM('customer_order', 'agent_order', 'booking') DEFAULT 'customer_order',
    guest_name VARCHAR(255) DEFAULT NULL,
    guest_phone VARCHAR(50) DEFAULT NULL,
    guest_email VARCHAR(255) DEFAULT NULL,
    items_json TEXT DEFAULT NULL COMMENT 'JSON array of ordered items',
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'THB',
    notes TEXT DEFAULT NULL,
    booking_date DATE DEFAULT NULL,
    booking_time TIME DEFAULT NULL,
    payment_slip_url VARCHAR(500) DEFAULT NULL COMMENT 'Payment slip image URL',
    payment_status ENUM('unpaid', 'slip_uploaded', 'confirmed', 'rejected') DEFAULT 'unpaid',
    linked_po_id INT DEFAULT NULL COMMENT 'FK to po.id when processed',
    linked_pr_id INT DEFAULT NULL COMMENT 'FK to pr.id when processed',
    processed_by INT DEFAULT NULL COMMENT 'FK to user who confirmed',
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

-- 5. LINE Auto-Reply Rules
CREATE TABLE IF NOT EXISTS line_auto_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    trigger_keyword VARCHAR(255) NOT NULL COMMENT 'Keyword or regex to match',
    match_type ENUM('exact', 'contains', 'regex') DEFAULT 'contains',
    reply_type ENUM('text', 'flex', 'template', 'image') DEFAULT 'text',
    reply_content TEXT NOT NULL COMMENT 'Reply text or JSON for flex/template',
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0 COMMENT 'Higher = checked first',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. LINE Webhook Events Log (raw events for debugging)
CREATE TABLE IF NOT EXISTS line_webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL COMMENT 'message, follow, unfollow, postback, etc.',
    event_json TEXT NOT NULL COMMENT 'Raw JSON event from LINE',
    processed TINYINT(1) DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_event_type (event_type),
    INDEX idx_processed (processed),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
