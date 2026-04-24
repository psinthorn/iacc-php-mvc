-- =============================================================================
-- Migration:    tour_checkin_module.sql
-- Description:  Tour operator self-check-in module — columns, indexes, and
--               audit-log table for HMAC-token-based customer self-check-in.
-- Date:         2026-04-24
-- Tables:       tour_bookings (ADD COLUMN × 5, ADD INDEX × 2)
--               tour_checkin_log (CREATE TABLE IF NOT EXISTS)
-- Compatibility: MySQL 5.7 — idempotent via INFORMATION_SCHEMA PREPARE/EXECUTE
-- =============================================================================

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 1 — Add columns to tour_bookings
-- Each block is independently idempotent: the column is added only when absent.
-- All columns are placed AFTER `remark`.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1a. checkin_token — HMAC-SHA256 token for self-check-in URL
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @col    = 'checkin_token';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_token` VARCHAR(64) NULL DEFAULT NULL COMMENT ''HMAC-SHA256 token for self-check-in URL'' AFTER `remark`',
    'SELECT 1 -- checkin_token already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1b. checkin_token_exp — token expiry date (travel_date + 1 day)
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @col    = 'checkin_token_exp';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_token_exp` DATE NULL DEFAULT NULL COMMENT ''Token valid through this date (travel_date + 1 day)'' AFTER `checkin_token`',
    'SELECT 1 -- checkin_token_exp already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1c. checkin_status — 0 = not checked in, 1 = checked in
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @col    = 'checkin_status';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''0=not checked in, 1=checked in'' AFTER `checkin_token_exp`',
    'SELECT 1 -- checkin_status already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1d. checkin_at — timestamp of check-in
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @col    = 'checkin_at';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_at` DATETIME NULL DEFAULT NULL COMMENT ''UTC timestamp of check-in'' AFTER `checkin_status`',
    'SELECT 1 -- checkin_at already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1e. checkin_by — who performed the check-in: 'self' | 'staff' | 'reset'
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @col    = 'checkin_by';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `checkin_by` VARCHAR(20) NOT NULL DEFAULT ''self'' COMMENT ''self|staff|reset'' AFTER `checkin_at`',
    'SELECT 1 -- checkin_by already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 2 — Add indexes to tour_bookings
-- ─────────────────────────────────────────────────────────────────────────────

-- 2a. UNIQUE KEY idx_checkin_token — fast single-row lookup by token
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @idx    = 'idx_checkin_token';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD UNIQUE KEY `idx_checkin_token` (`checkin_token`)',
    'SELECT 1 -- idx_checkin_token already exists'
) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND INDEX_NAME = @idx);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2b. KEY idx_company_travel_checkin — staff dashboard: filter by tenant + date + status
SET @dbname = DATABASE();
SET @tbl    = 'tour_bookings';
SET @idx    = 'idx_company_travel_checkin';
SET @sql    = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_bookings` ADD KEY `idx_company_travel_checkin` (`company_id`, `travel_date`, `checkin_status`)',
    'SELECT 1 -- idx_company_travel_checkin already exists'
) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND INDEX_NAME = @idx);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- SECTION 3 — Create tour_checkin_log (audit trail)
-- CREATE TABLE IF NOT EXISTS is natively idempotent in MySQL 5.7.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `tour_checkin_log` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL                  COMMENT 'FK tour_bookings.id',
    `action`     ENUM('checkin','reset','staff_override') NOT NULL,
    `actor_type` ENUM('customer','staff') NOT NULL DEFAULT 'customer',
    `actor_id`   INT(11) UNSIGNED NULL DEFAULT NULL COMMENT 'usr_id for staff; NULL for customer self-check-in',
    `actor_ip`   VARCHAR(45) NULL DEFAULT NULL      COMMENT 'IPv4 or IPv6 address',
    `actor_ua`   VARCHAR(255) NULL DEFAULT NULL     COMMENT 'User-Agent header snippet',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking`  (`booking_id`),
    KEY `idx_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for customer self-check-in and staff override actions';
