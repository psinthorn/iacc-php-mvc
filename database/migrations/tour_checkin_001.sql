-- =============================================================================
-- Migration: tour_checkin_001.sql
-- Description: Customer self-check-in module schema
-- Tables affected: tour_bookings (add columns), tour_booking_pax (add columns)
-- New table: tour_checkin_log
-- MySQL 5.7 compatible — uses INFORMATION_SCHEMA guards instead of IF NOT EXISTS
-- =============================================================================

-- ── tour_bookings: checkin_token ─────────────────────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND COLUMN_NAME = 'checkin_token');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_token` VARCHAR(64) NULL DEFAULT NULL COMMENT ''HMAC-SHA256 token for self-check-in URL'' AFTER `notes`',
    'SELECT ''checkin_token already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── tour_bookings: checkin_token_exp ─────────────────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND COLUMN_NAME = 'checkin_token_exp');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_token_exp` DATE NULL DEFAULT NULL COMMENT ''Token valid through this date (travel_date + 1 day)'' AFTER `checkin_token`',
    'SELECT ''checkin_token_exp already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── tour_bookings: checkin_status ────────────────────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND COLUMN_NAME = 'checkin_status');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''0=not checked in, 1=checked in'' AFTER `checkin_token_exp`',
    'SELECT ''checkin_status already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── tour_bookings: checkin_at ────────────────────────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND COLUMN_NAME = 'checkin_at');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_at` DATETIME NULL DEFAULT NULL COMMENT ''UTC timestamp of check-in'' AFTER `checkin_status`',
    'SELECT ''checkin_at already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── tour_bookings: checkin_by ────────────────────────────────────────────────
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND COLUMN_NAME = 'checkin_by');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_by` VARCHAR(20) NOT NULL DEFAULT ''self'' COMMENT ''self|staff|reset'' AFTER `checkin_at`',
    'SELECT ''checkin_by already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Index on checkin_token for fast lookup ───────────────────────────────────
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND INDEX_NAME = 'idx_checkin_token');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `tour_bookings` ADD UNIQUE KEY `idx_checkin_token` (`checkin_token`)',
    'SELECT ''idx_checkin_token already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Index for staff dashboard (company + travel_date + checkin_status) ────────
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND INDEX_NAME = 'idx_company_travel_checkin');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `tour_bookings` ADD KEY `idx_company_travel_checkin` (`company_id`, `travel_date`, `checkin_status`)',
    'SELECT ''idx_company_travel_checkin already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── tour_checkin_log ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tour_checkin_log` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL COMMENT 'FK tour_bookings.id',
    `action`     ENUM('checkin','reset','staff_override') NOT NULL,
    `actor_type` ENUM('customer','staff') NOT NULL DEFAULT 'customer',
    `actor_id`   INT(11) UNSIGNED NULL DEFAULT NULL COMMENT 'usr_id for staff; NULL for customer',
    `actor_ip`   VARCHAR(45) NULL DEFAULT NULL,
    `actor_ua`   VARCHAR(255) NULL DEFAULT NULL COMMENT 'User-Agent snippet',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for customer self-check-in actions';
