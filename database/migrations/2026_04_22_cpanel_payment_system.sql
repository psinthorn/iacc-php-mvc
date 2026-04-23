-- ============================================================
-- cPanel Migration: Payment System + Booking Enhancements
-- Date: 2026-04-22
-- Branch: feature/tour-booking-payments
-- Applies on top of: tour_all_migrations.sql (committed c000c4f)
--
-- Run via phpMyAdmin or:
--   mysql -u<user> -p <dbname> < 2026_04_22_cpanel_payment_system.sql
--
-- Fully idempotent — safe to re-run.
-- ============================================================

-- ============================================================
-- PART 1: tour_bookings — sales_rep_id column
-- (Agent/SalesRep separation, 2026-04-21)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'sales_rep_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `sales_rep_id` INT(11) DEFAULT NULL COMMENT ''FK to tour_agent_profiles.id - sales rep/referrer'' AFTER `agent_id`, ADD KEY `idx_sales_rep` (`sales_rep_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 2: tour_booking_contacts — contact_messengers column
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_booking_contacts' AND COLUMN_NAME = 'contact_messengers'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `tour_booking_contacts` ADD COLUMN `contact_messengers` TEXT DEFAULT NULL AFTER `nationality`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 3: tour_agent_profiles — messaging columns
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_agent_profiles' AND COLUMN_NAME = 'contact_telegram'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `tour_agent_profiles` ADD COLUMN `contact_telegram` VARCHAR(100) DEFAULT NULL COMMENT ''Telegram username/ID'' AFTER `contact_whatsapp`, ADD COLUMN `contact_wechat` VARCHAR(100) DEFAULT NULL COMMENT ''WeChat ID'' AFTER `contact_telegram`, ADD COLUMN `contact_messengers` TEXT DEFAULT NULL COMMENT ''Flexible messengers'' AFTER `contact_wechat`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 4: payment_method table (gateway-capable, per-company)
-- This is the gateway config table — distinct from legacy payment_methods
-- ============================================================
CREATE TABLE IF NOT EXISTS `payment_method` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`  INT(11) NOT NULL,
    `code`        VARCHAR(50) NOT NULL COMMENT 'stripe, paypal, promptpay, cash, etc.',
    `name`        VARCHAR(100) NOT NULL,
    `name_th`     VARCHAR(100) DEFAULT NULL,
    `icon`        VARCHAR(50) DEFAULT NULL COMMENT 'FontAwesome class e.g. fa-qrcode',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_gateway`  TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = online gateway, 0 = manual method',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`  INT(11) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code_company` (`code`, `company_id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_gateway` (`is_gateway`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment methods per company (manual + online gateways)';

-- Seed payment methods for company 165 (My Samui Island Tour)
INSERT IGNORE INTO `payment_method` (`code`, `name`, `name_th`, `icon`, `description`, `is_gateway`, `is_active`, `sort_order`, `company_id`) VALUES
('cash',          'Cash',          'เงินสด',     'fa-money',       'Cash payment',         0, 1, 1, 165),
('bank_transfer', 'Bank Transfer', 'โอนเงิน',    'fa-university',  'Bank transfer',        0, 1, 2, 165),
('credit_card',   'Credit Card',   'บัตรเครดิต', 'fa-credit-card', 'Credit card',          0, 1, 3, 165),
('promptpay',     'PromptPay',     'พร้อมเพย์',  'fa-qrcode',      'PromptPay QR',         1, 1, 4, 165),
('stripe',        'Stripe',        'Stripe',      'fa-cc-stripe',   'Stripe Card Gateway',  1, 1, 5, 165),
('paypal',        'PayPal',        'PayPal',      'fa-paypal',      'PayPal Gateway',       1, 1, 6, 165);

-- Fix unique key if only global code key exists (idempotent)
SET @key_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_method' AND INDEX_NAME = 'code' AND SEQ_IN_INDEX = 1
);
SET @sql = IF(@key_exists > 0, 'ALTER TABLE `payment_method` DROP INDEX `code`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 5: payment_gateway_config table
-- Stores per-company API keys/settings for each gateway
-- ============================================================
CREATE TABLE IF NOT EXISTS `payment_gateway_config` (
    `id`                INT(11) NOT NULL AUTO_INCREMENT,
    `payment_method_id` INT(11) NOT NULL COMMENT 'FK payment_method.id',
    `company_id`        INT(11) NOT NULL,
    `config_key`        VARCHAR(100) NOT NULL COMMENT 'e.g. secret_key, client_id, promptpay_id',
    `config_value`      TEXT DEFAULT NULL,
    `is_encrypted`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = value is encrypted at rest',
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_gw_config` (`payment_method_id`, `company_id`, `config_key`),
    KEY `idx_company` (`company_id`),
    KEY `idx_method` (`payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-company API credentials and settings for each payment gateway';

-- Seed placeholder PromptPay config for company 165
-- Admin must update these values via Payment Gateway Config page
SET @pm_id = (SELECT `id` FROM `payment_method` WHERE `code` = 'promptpay' AND `company_id` = 165 LIMIT 1);
INSERT IGNORE INTO `payment_gateway_config` (`payment_method_id`, `company_id`, `config_key`, `config_value`, `is_encrypted`) VALUES
(@pm_id, 165, 'promptpay_id',           '',         0),
(@pm_id, 165, 'promptpay_name',         '',         0),
(@pm_id, 165, 'promptpay_auto_confirm', '0',        0);

-- ============================================================
-- PART 6: tour_bookings — payment tracking columns
-- (2026-04-17)
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'payment_status'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `payment_status` ENUM(''unpaid'',''deposit'',''partial'',''paid'',''refunded'') NOT NULL DEFAULT ''unpaid'' AFTER `status`, ADD COLUMN `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`, ADD COLUMN `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_paid`, ADD COLUMN `deposit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_due`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill amount_due for existing bookings (safe — only updates rows where 0)
UPDATE `tour_bookings`
SET `amount_due` = `total_amount`
WHERE `amount_due` = 0 AND `total_amount` > 0 AND `deleted_at` IS NULL;

-- Add index on payment_status if not exists
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND INDEX_NAME = 'idx_payment_status'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `tour_bookings` ADD KEY `idx_payment_status` (`payment_status`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 7: tour_booking_payments table
-- Individual payment records (manual + gateway)
-- ============================================================
CREATE TABLE IF NOT EXISTS `tour_booking_payments` (
    `id`             INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id`     INT(11) NOT NULL COMMENT 'FK tour_bookings.id',
    `company_id`     INT(11) NOT NULL COMMENT 'Tenant FK',
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
    `gateway`        VARCHAR(30) DEFAULT NULL COMMENT 'stripe, promptpay, paypal — NULL for manual',
    `amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency`       VARCHAR(3) NOT NULL DEFAULT 'THB',
    `reference_id`   VARCHAR(255) DEFAULT NULL,
    `payment_date`   DATE NOT NULL,
    `status`         ENUM('pending','pending_review','completed','rejected','refunded') NOT NULL DEFAULT 'pending',
    `payment_type`   ENUM('deposit','partial','full','refund') NOT NULL DEFAULT 'full',
    `slip_image`     VARCHAR(500) DEFAULT NULL,
    `notes`          TEXT DEFAULT NULL,
    `reject_reason`  VARCHAR(500) DEFAULT NULL,
    `created_by`     INT(11) DEFAULT NULL,
    `approved_by`    INT(11) DEFAULT NULL,
    `approved_at`    DATETIME DEFAULT NULL,
    `deleted_at`     DATETIME DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking`      (`booking_id`),
    KEY `idx_company`      (`company_id`),
    KEY `idx_status`       (`status`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_gateway`      (`gateway`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment records for tour bookings (manual + gateway)';

-- ============================================================
-- End of migration 2026_04_22_cpanel_payment_system.sql
-- ============================================================
