-- =============================================================================
-- Q2 2026: Payment Gateway & Multi-Currency Migration
-- Version: 8.0 (Phase 8)
-- Date: 2026-04-01
-- =============================================================================

-- =============================================
-- 1. Currency Master Table
-- =============================================
CREATE TABLE IF NOT EXISTS `currencies` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(3) NOT NULL COMMENT 'ISO 4217 currency code',
    `name` VARCHAR(100) NOT NULL COMMENT 'Currency name in English',
    `name_th` VARCHAR(100) DEFAULT NULL COMMENT 'Currency name in Thai',
    `symbol` VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'Currency symbol (฿, $, €)',
    `decimal_places` TINYINT(1) NOT NULL DEFAULT 2,
    `symbol_position` ENUM('before','after') NOT NULL DEFAULT 'before',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_currency_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Supported currencies';

-- Seed default currencies
INSERT INTO `currencies` (`code`, `name`, `name_th`, `symbol`, `decimal_places`, `symbol_position`, `is_active`, `sort_order`) VALUES
('THB', 'Thai Baht',             'บาท',           '฿',  2, 'before', 1, 1),
('USD', 'US Dollar',             'ดอลลาร์สหรัฐ',    '$',  2, 'before', 1, 2),
('EUR', 'Euro',                  'ยูโร',           '€',  2, 'before', 1, 3),
('GBP', 'British Pound',         'ปอนด์สเตอร์ลิง',  '£',  2, 'before', 1, 4),
('JPY', 'Japanese Yen',          'เยนญี่ปุ่น',      '¥',  0, 'before', 1, 5),
('CNY', 'Chinese Yuan',          'หยวนจีน',        '¥',  2, 'before', 1, 6),
('SGD', 'Singapore Dollar',      'ดอลลาร์สิงคโปร์',  'S$', 2, 'before', 0, 7),
('MYR', 'Malaysian Ringgit',     'ริงกิตมาเลเซีย',  'RM', 2, 'before', 0, 8),
('KRW', 'South Korean Won',      'วอนเกาหลีใต้',    '₩',  0, 'before', 0, 9),
('AUD', 'Australian Dollar',     'ดอลลาร์ออสเตรเลีย', 'A$', 2, 'before', 0, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =============================================
-- 2. Exchange Rates Table (daily rates cache)
-- =============================================
CREATE TABLE IF NOT EXISTS `exchange_rates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `from_currency` VARCHAR(3) NOT NULL,
    `to_currency` VARCHAR(3) NOT NULL DEFAULT 'THB',
    `rate` DECIMAL(16,6) NOT NULL COMMENT 'How many to_currency per 1 from_currency',
    `rate_date` DATE NOT NULL,
    `source` VARCHAR(50) NOT NULL DEFAULT 'BOT' COMMENT 'Data source (BOT, manual, etc.)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_exchange_rate` (`from_currency`, `to_currency`, `rate_date`),
    KEY `idx_rate_date` (`rate_date`),
    KEY `idx_from_currency` (`from_currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily exchange rate cache';

-- =============================================
-- 3. Tax Reports Table
-- =============================================
CREATE TABLE IF NOT EXISTS `tax_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `com_id` INT(11) NOT NULL COMMENT 'Company ID',
    `report_type` ENUM('PP30','PND3','PND53') NOT NULL COMMENT 'Thai tax form type',
    `tax_year` INT(4) NOT NULL,
    `tax_month` INT(2) NOT NULL COMMENT '1-12',
    `output_vat` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Output VAT (ภาษีขาย)',
    `input_vat` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Input VAT (ภาษีซื้อ)',
    `net_vat` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Net VAT (output - input)',
    `total_wht` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total WHT amount',
    `report_data` JSON DEFAULT NULL COMMENT 'Full report data as JSON',
    `status` ENUM('draft','submitted','filed') NOT NULL DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tax_report` (`com_id`, `report_type`, `tax_year`, `tax_month`),
    KEY `idx_tax_year_month` (`tax_year`, `tax_month`),
    KEY `idx_company` (`com_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Saved tax reports (PP30, PND3, PND53)';

-- =============================================
-- 4. Add WHT fields to pay table
-- =============================================
-- Use IF NOT EXISTS pattern via procedure for safe re-runs
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='iacc' AND TABLE_NAME='pay' AND COLUMN_NAME='wht_rate');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `pay` ADD COLUMN `wht_rate` DECIMAL(5,2) DEFAULT NULL COMMENT ''Withholding tax rate (%)'', ADD COLUMN `wht_amount` DECIMAL(15,2) DEFAULT NULL COMMENT ''Withholding tax amount'', ADD COLUMN `wht_type` ENUM(''PND3'',''PND53'') DEFAULT NULL COMMENT ''WHT form type''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 5. Add default_currency to company table
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='iacc' AND TABLE_NAME='company' AND COLUMN_NAME='default_currency');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `company` ADD COLUMN `default_currency` VARCHAR(3) NOT NULL DEFAULT ''THB'' COMMENT ''Default currency code'' AFTER `email`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 6. Add currency to invoice (iv) table
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='iacc' AND TABLE_NAME='iv' AND COLUMN_NAME='currency_code');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `iv` ADD COLUMN `currency_code` VARCHAR(3) DEFAULT ''THB'' COMMENT ''Invoice currency'', ADD COLUMN `exchange_rate` DECIMAL(16,6) DEFAULT NULL COMMENT ''Exchange rate to THB at time of creation''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 7. Add PromptPay as payment method
-- =============================================
-- Insert PromptPay for each active company that doesn't already have it
INSERT IGNORE INTO `payment_methods` (`method_name`, `method_type`, `is_active`, `sort_order`, `com_id`)
SELECT 'PromptPay', 'qrcode', 1,
       COALESCE((SELECT MAX(`sort_order`) FROM `payment_methods` AS pm2 WHERE pm2.com_id = c.id), 0) + 1, c.id
FROM `company` c WHERE c.deleted_at IS NULL
AND NOT EXISTS (SELECT 1 FROM `payment_methods` pm3 WHERE pm3.method_name = 'PromptPay' AND pm3.com_id = c.id);

-- =============================================
-- 8. Add currency to payment_log for multi-currency payments
-- =============================================
-- payment_log already has 'currency' column; add exchange_rate and slip_image if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='iacc' AND TABLE_NAME='payment_log' AND COLUMN_NAME='exchange_rate');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `payment_log` ADD COLUMN `exchange_rate` DECIMAL(16,6) DEFAULT NULL COMMENT ''Exchange rate to THB'' AFTER `currency`, ADD COLUMN `slip_image` VARCHAR(255) DEFAULT NULL COMMENT ''PromptPay slip upload path''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 9. Seed PromptPay in payment_method (gateway config table)
-- =============================================
-- This is the gateway-level payment_method table (singular) used by payment_gateway_config
INSERT INTO `payment_method` (`company_id`, `code`, `name`, `name_th`, `icon`, `description`, `is_gateway`, `is_active`, `sort_order`)
SELECT 95, 'promptpay', 'PromptPay', 'พร้อมเพย์', 'fa-qrcode', 'Thai PromptPay QR Payment', 1, 1, 10
FROM dual WHERE NOT EXISTS (SELECT 1 FROM `payment_method` WHERE `code` = 'promptpay');

-- Seed default PromptPay config keys (demo values — admin should update)
SET @pm_id = (SELECT `id` FROM `payment_method` WHERE `code` = 'promptpay' LIMIT 1);
INSERT IGNORE INTO `payment_gateway_config` (`company_id`, `payment_method_id`, `config_key`, `config_value`, `is_encrypted`) VALUES
(95, @pm_id, 'promptpay_id', '0812345678', 0),
(95, @pm_id, 'promptpay_name', 'Demo Company', 0),
(95, @pm_id, 'promptpay_auto_confirm', '0', 0);

-- =============================================================================
-- End of Q2 2026 Migration
-- =============================================================================
