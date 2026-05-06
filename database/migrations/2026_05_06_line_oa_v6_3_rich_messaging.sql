-- Migration: 2026_05_06_line_oa_v6_3_rich_messaging.sql
-- v6.3 — LINE OA Rich Messaging
-- Adds: Flex message templates, Broadcast campaigns + recipients, User tags, bot-info cache
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)
-- Idempotent: safe to run multiple times via stored-procedure column/index checks

-- =============================================================================
-- 1. line_message_templates — bilingual Flex/text message templates
-- =============================================================================
CREATE TABLE IF NOT EXISTS line_message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    template_type ENUM('tour_package','quotation','booking_confirm','payment_reminder','voucher','custom') DEFAULT 'custom',
    message_type ENUM('text','flex') DEFAULT 'flex',
    alt_text VARCHAR(400) DEFAULT NULL COMMENT 'Notification fallback text',
    content_th TEXT DEFAULT NULL COMMENT 'Thai version: text body OR Flex JSON',
    content_en TEXT DEFAULT NULL COMMENT 'English version: text body OR Flex JSON',
    variables_json TEXT DEFAULT NULL COMMENT 'JSON array of variable names like ["tour_name","price"]',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    INDEX idx_type (template_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. line_user_tags — segmentation tags per company
-- =============================================================================
CREATE TABLE IF NOT EXISTS line_user_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(80) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    UNIQUE KEY uk_company_name (company_id, name, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. line_user_tag_map — pivot: line_users <-> line_user_tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS line_user_tag_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    line_user_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_tag (tag_id),
    UNIQUE KEY uk_user_tag (line_user_id, tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. line_broadcasts — campaign records (one per send/scheduled send)
-- =============================================================================
CREATE TABLE IF NOT EXISTS line_broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    status ENUM('draft','scheduled','sending','sent','partial','failed','cancelled') DEFAULT 'draft',

    -- Audience selector
    audience_type ENUM('all','tag','language','has_booked','last_active') DEFAULT 'all',
    audience_filter_json TEXT DEFAULT NULL COMMENT 'JSON: {tag_id, language, days, ...}',
    recipient_count INT DEFAULT 0 COMMENT 'Snapshot count at time of send',

    -- Message payload
    message_kind ENUM('text','template','custom_flex') DEFAULT 'text',
    template_id INT DEFAULT NULL COMMENT 'FK to line_message_templates if message_kind=template',
    text_content_th TEXT DEFAULT NULL,
    text_content_en TEXT DEFAULT NULL,
    flex_content_th TEXT DEFAULT NULL,
    flex_content_en TEXT DEFAULT NULL,
    alt_text VARCHAR(400) DEFAULT NULL,

    -- Schedule
    scheduled_at DATETIME DEFAULT NULL,
    sent_started_at DATETIME DEFAULT NULL,
    sent_completed_at DATETIME DEFAULT NULL,

    -- Results
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    last_error TEXT DEFAULT NULL,

    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. line_broadcast_recipients — per-user delivery log for each broadcast
-- =============================================================================
CREATE TABLE IF NOT EXISTS line_broadcast_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    broadcast_id INT NOT NULL,
    line_user_id INT NOT NULL,
    status ENUM('pending','sent','failed','skipped') DEFAULT 'pending',
    error_message VARCHAR(500) DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_broadcast (broadcast_id),
    INDEX idx_line_user (line_user_id),
    INDEX idx_status (status),
    UNIQUE KEY uk_broadcast_user (broadcast_id, line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. ALTER line_oa_config — add bot-info cache columns (idempotent)
-- =============================================================================
DROP PROCEDURE IF EXISTS _migrate_line_v63_alters;
DELIMITER $$
CREATE PROCEDURE _migrate_line_v63_alters()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'bot_display_name'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `bot_display_name` VARCHAR(255) DEFAULT NULL AFTER `rich_menu_id`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'bot_picture_url'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `bot_picture_url` VARCHAR(500) DEFAULT NULL AFTER `bot_display_name`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'bot_basic_id'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `bot_basic_id` VARCHAR(80) DEFAULT NULL AFTER `bot_picture_url`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'last_probe_at'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `last_probe_at` DATETIME DEFAULT NULL AFTER `bot_basic_id`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'last_probe_status'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `last_probe_status` ENUM('connected','invalid_credentials','unreachable','unknown') DEFAULT 'unknown' AFTER `last_probe_at`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'last_probe_error'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `last_probe_error` VARCHAR(500) DEFAULT NULL AFTER `last_probe_status`;
    END IF;

    -- Monthly broadcast quota tracking (LINE free-tier = 500/month)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_oa_config'
          AND COLUMN_NAME  = 'broadcast_quota_monthly'
    ) THEN
        ALTER TABLE `line_oa_config` ADD COLUMN `broadcast_quota_monthly` INT DEFAULT 500 AFTER `last_probe_error`;
    END IF;
END$$
DELIMITER ;
CALL _migrate_line_v63_alters();
DROP PROCEDURE IF EXISTS _migrate_line_v63_alters;

-- =============================================================================
-- 7. Seed default templates per company (idempotent — only inserts if missing)
-- =============================================================================
-- Note: tour operators can edit these after first save. JSON is the LINE Flex
-- bubble structure; placeholders like {tour_name} are interpolated at send time.
INSERT INTO line_message_templates (company_id, name, template_type, message_type, alt_text, content_th, content_en, variables_json, is_active)
SELECT c.id, 'Tour Package Card', 'tour_package', 'flex',
       'Tour package: {tour_name}',
       '{"type":"bubble","hero":{"type":"image","url":"{image_url}","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"{tour_name}","weight":"bold","size":"xl","wrap":true},{"type":"text","text":"ราคา ฿{price}","color":"#06C755","weight":"bold","size":"lg","margin":"md"},{"type":"text","text":"{description}","wrap":true,"size":"sm","color":"#666666","margin":"md"}]},"footer":{"type":"box","layout":"vertical","contents":[{"type":"button","style":"primary","color":"#06C755","action":{"type":"uri","label":"จองเลย","uri":"{book_url}"}}]}}',
       '{"type":"bubble","hero":{"type":"image","url":"{image_url}","size":"full","aspectRatio":"20:13","aspectMode":"cover"},"body":{"type":"box","layout":"vertical","contents":[{"type":"text","text":"{tour_name}","weight":"bold","size":"xl","wrap":true},{"type":"text","text":"From ฿{price}","color":"#06C755","weight":"bold","size":"lg","margin":"md"},{"type":"text","text":"{description}","wrap":true,"size":"sm","color":"#666666","margin":"md"}]},"footer":{"type":"box","layout":"vertical","contents":[{"type":"button","style":"primary","color":"#06C755","action":{"type":"uri","label":"Book Now","uri":"{book_url}"}}]}}',
       '["tour_name","price","description","image_url","book_url"]',
       1
FROM company c
WHERE c.id > 0
  AND NOT EXISTS (
    SELECT 1 FROM line_message_templates t
    WHERE t.company_id = c.id AND t.template_type = 'tour_package' AND t.deleted_at IS NULL
  );
