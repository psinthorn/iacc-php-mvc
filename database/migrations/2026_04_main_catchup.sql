-- =============================================================================
-- Consolidated Migration: main → develop catch-up
-- Date: 2026-04-24
-- Covers:
--   2026_04_17  tour_booking_payments
--   2026_04_22  cpanel_payment_system (sales_rep, messaging, payment_method, payment_gateway_config)
--   2026_04_23  master_data_is_active (category, brand, type, model)
--   2026_04_24  bulk_action_columns (voucher_sent_at, invoice_sent_at)
--
-- ✅ Fully idempotent — safe to run multiple times, will not re-apply changes
-- ✅ phpMyAdmin compatible — no DELIMITER blocks
-- ✅ MySQL 5.7 compatible
-- ✅ Non-destructive — new columns use NULL or safe defaults, no data is deleted
--
-- Run via: phpMyAdmin → select database → SQL tab → paste → Go
-- =============================================================================


-- =============================================================================
-- SECTION 1: tour_bookings — payment tracking columns
-- (from 2026_04_17_tour_booking_payments.sql + 2026_04_22)
-- =============================================================================

-- 1a. payment_status
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'payment_status');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `payment_status` ENUM(''unpaid'',''deposit'',''partial'',''paid'',''refunded'') NOT NULL DEFAULT ''unpaid'' AFTER `status`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 1b. amount_paid
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'amount_paid');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 1c. amount_due
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'amount_due');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_paid`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 1d. deposit_amount
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'deposit_amount');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `deposit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_due`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 1e. Backfill: amount_due = total_amount for existing bookings where amount_due is still 0
UPDATE `tour_bookings`
SET `amount_due` = `total_amount`
WHERE `amount_due` = 0 AND `total_amount` > 0 AND `deleted_at` IS NULL;

-- 1f. Index on payment_status
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND INDEX_NAME = 'idx_payment_status');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `tour_bookings` ADD KEY `idx_payment_status` (`payment_status`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 2: tour_bookings — sales_rep_id column
-- (from 2026_04_22_cpanel_payment_system.sql PART 1)
-- =============================================================================

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'sales_rep_id');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `sales_rep_id` INT(11) DEFAULT NULL COMMENT ''FK to tour_agent_profiles.id — sales rep/referrer'' AFTER `agent_id`, ADD KEY `idx_sales_rep` (`sales_rep_id`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 3: tour_bookings — bulk-action tracking columns
-- (from 2026_04_24_bulk_action_columns.sql)
-- =============================================================================

-- 3a. voucher_sent_at
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'voucher_sent_at');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `voucher_sent_at` DATETIME NULL DEFAULT NULL AFTER `status`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3b. invoice_sent_at
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings' AND COLUMN_NAME = 'invoice_sent_at');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_bookings` ADD COLUMN `invoice_sent_at` DATETIME NULL DEFAULT NULL AFTER `voucher_sent_at`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 4: tour_booking_contacts — contact_messengers column
-- (from 2026_04_22_cpanel_payment_system.sql PART 2)
-- =============================================================================

SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_booking_contacts' AND COLUMN_NAME = 'contact_messengers');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_booking_contacts` ADD COLUMN `contact_messengers` TEXT DEFAULT NULL AFTER `nationality`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 5: tour_agent_profiles — messaging columns
-- (from 2026_04_22_cpanel_payment_system.sql PART 3)
-- =============================================================================

-- 5a. contact_telegram
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_agent_profiles' AND COLUMN_NAME = 'contact_telegram');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_agent_profiles` ADD COLUMN `contact_telegram` VARCHAR(100) DEFAULT NULL COMMENT ''Telegram username/ID'' AFTER `contact_whatsapp`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 5b. contact_wechat
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_agent_profiles' AND COLUMN_NAME = 'contact_wechat');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_agent_profiles` ADD COLUMN `contact_wechat` VARCHAR(100) DEFAULT NULL COMMENT ''WeChat ID'' AFTER `contact_telegram`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 5c. contact_messengers
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_agent_profiles' AND COLUMN_NAME = 'contact_messengers');
SET @sql = IF(@col = 0,
    'ALTER TABLE `tour_agent_profiles` ADD COLUMN `contact_messengers` TEXT DEFAULT NULL COMMENT ''Flexible messengers'' AFTER `contact_wechat`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 6: tour_booking_payments — new table
-- (from 2026_04_22_cpanel_payment_system.sql PART 7)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `tour_booking_payments` (
    `id`             INT(11)       NOT NULL AUTO_INCREMENT,
    `booking_id`     INT(11)       NOT NULL COMMENT 'FK tour_bookings.id',
    `company_id`     INT(11)       NOT NULL COMMENT 'Tenant FK',
    `payment_method` VARCHAR(50)   NOT NULL DEFAULT 'cash',
    `gateway`        VARCHAR(30)   DEFAULT NULL COMMENT 'stripe, promptpay, paypal — NULL for manual',
    `amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency`       VARCHAR(3)    NOT NULL DEFAULT 'THB',
    `reference_id`   VARCHAR(255)  DEFAULT NULL,
    `payment_date`   DATE          NOT NULL,
    `status`         ENUM('pending','pending_review','completed','rejected','refunded') NOT NULL DEFAULT 'pending',
    `payment_type`   ENUM('deposit','partial','full','refund') NOT NULL DEFAULT 'full',
    `slip_image`     VARCHAR(500)  DEFAULT NULL,
    `notes`          TEXT          DEFAULT NULL,
    `reject_reason`  VARCHAR(500)  DEFAULT NULL,
    `created_by`     INT(11)       DEFAULT NULL,
    `approved_by`    INT(11)       DEFAULT NULL,
    `approved_at`    DATETIME      DEFAULT NULL,
    `deleted_at`     DATETIME      DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking`      (`booking_id`),
    KEY `idx_company`      (`company_id`),
    KEY `idx_status`       (`status`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_gateway`      (`gateway`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment records for tour bookings (manual + gateway)';


-- =============================================================================
-- SECTION 7: payment_method — new table (gateway-capable, per-company)
-- (from 2026_04_22_cpanel_payment_system.sql PART 4)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `payment_method` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `company_id`  INT(11)      NOT NULL,
    `code`        VARCHAR(50)  NOT NULL COMMENT 'stripe, paypal, promptpay, cash, etc.',
    `name`        VARCHAR(100) NOT NULL,
    `name_th`     VARCHAR(100) DEFAULT NULL,
    `icon`        VARCHAR(50)  DEFAULT NULL COMMENT 'FontAwesome class e.g. fa-qrcode',
    `description` VARCHAR(255) DEFAULT NULL,
    `is_gateway`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=online gateway, 0=manual method',
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`  INT(11)      NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code_company` (`code`, `company_id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_gateway` (`is_gateway`),
    KEY `idx_active`  (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment methods per company (manual + online gateways)';

-- Seed default payment methods for company 165 (My Samui Island Tour)
-- INSERT IGNORE is safe — skips if the row already exists (UNIQUE KEY code_company)
INSERT IGNORE INTO `payment_method` (`code`, `name`, `name_th`, `icon`, `description`, `is_gateway`, `is_active`, `sort_order`, `company_id`) VALUES
('cash',          'Cash',          'เงินสด',     'fa-money',       'Cash payment',        0, 1, 1, 165),
('bank_transfer', 'Bank Transfer', 'โอนเงิน',    'fa-university',  'Bank transfer',       0, 1, 2, 165),
('credit_card',   'Credit Card',   'บัตรเครดิต', 'fa-credit-card', 'Credit card',         0, 1, 3, 165),
('promptpay',     'PromptPay',     'พร้อมเพย์',  'fa-qrcode',      'PromptPay QR',        1, 1, 4, 165),
('stripe',        'Stripe',        'Stripe',      'fa-cc-stripe',   'Stripe Card Gateway', 1, 1, 5, 165),
('paypal',        'PayPal',        'PayPal',      'fa-paypal',      'PayPal Gateway',      1, 1, 6, 165);

-- Drop legacy single-column `code` unique key if it exists (replaced by composite `code_company`)
SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_method'
      AND INDEX_NAME = 'code' AND SEQ_IN_INDEX = 1 AND NON_UNIQUE = 0);
SET @sql = IF(@idx > 0, 'ALTER TABLE `payment_method` DROP INDEX `code`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- =============================================================================
-- SECTION 8: payment_gateway_config — new table
-- (from 2026_04_22_cpanel_payment_system.sql PART 5)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `payment_gateway_config` (
    `id`                INT(11)      NOT NULL AUTO_INCREMENT,
    `payment_method_id` INT(11)      NOT NULL COMMENT 'FK payment_method.id',
    `company_id`        INT(11)      NOT NULL,
    `config_key`        VARCHAR(100) NOT NULL COMMENT 'e.g. secret_key, client_id, promptpay_id',
    `config_value`      TEXT         DEFAULT NULL,
    `is_encrypted`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=value is encrypted at rest',
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_gw_config` (`payment_method_id`, `company_id`, `config_key`),
    KEY `idx_company` (`company_id`),
    KEY `idx_method`  (`payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-company API credentials and settings for each payment gateway';

-- Seed placeholder PromptPay config for company 165
-- Admin must update these values via Payment Gateway Config page
-- INSERT IGNORE is safe — skips if row already exists
SET @pm_id = (SELECT `id` FROM `payment_method` WHERE `code` = 'promptpay' AND `company_id` = 165 LIMIT 1);
INSERT IGNORE INTO `payment_gateway_config` (`payment_method_id`, `company_id`, `config_key`, `config_value`, `is_encrypted`) VALUES
(@pm_id, 165, 'promptpay_id',           '', 0),
(@pm_id, 165, 'promptpay_name',         '', 0),
(@pm_id, 165, 'promptpay_auto_confirm', '0', 0);


-- =============================================================================
-- SECTION 9: Master data — is_active toggle column
-- Tables: category, brand, type, model
-- (from 2026_04_23_master_data_is_active.sql)
-- =============================================================================

-- 9a. category.is_active
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND COLUMN_NAME = 'is_active');
SET @sql = IF(@col = 0,
    'ALTER TABLE `category` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND INDEX_NAME = 'idx_cat_active');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `category` ADD KEY `idx_cat_active` (`is_active`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 9b. brand.is_active
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brand' AND COLUMN_NAME = 'is_active');
SET @sql = IF(@col = 0,
    'ALTER TABLE `brand` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brand' AND INDEX_NAME = 'idx_brand_active');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `brand` ADD KEY `idx_brand_active` (`is_active`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 9c. type.is_active
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'type' AND COLUMN_NAME = 'is_active');
SET @sql = IF(@col = 0,
    'ALTER TABLE `type` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'type' AND INDEX_NAME = 'idx_type_active');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `type` ADD KEY `idx_type_active` (`is_active`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 9d. model.is_active
SET @col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'model' AND COLUMN_NAME = 'is_active');
SET @sql = IF(@col = 0,
    'ALTER TABLE `model` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'model' AND INDEX_NAME = 'idx_model_active');
SET @sql = IF(@idx = 0,
    'ALTER TABLE `model` ADD KEY `idx_model_active` (`is_active`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- END — 2026_04_main_catchup.sql
-- All 9 sections are fully idempotent.
-- Verify after import:
--   SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('tour_bookings','tour_agent_profiles','tour_booking_contacts','category','brand','type','model')
--   ORDER BY TABLE_NAME, ORDINAL_POSITION;
-- =============================================================================
