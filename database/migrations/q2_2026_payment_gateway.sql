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
ALTER TABLE `pay`
    ADD COLUMN `wht_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'Withholding tax rate (%)' AFTER `pay_vat`,
    ADD COLUMN `wht_amount` DECIMAL(15,2) DEFAULT NULL COMMENT 'Withholding tax amount' AFTER `wht_rate`,
    ADD COLUMN `wht_type` ENUM('PND3','PND53') DEFAULT NULL COMMENT 'WHT form type' AFTER `wht_amount`;

-- =============================================
-- 5. Add default_currency to company table
-- =============================================
ALTER TABLE `company`
    ADD COLUMN `default_currency` VARCHAR(3) NOT NULL DEFAULT 'THB' COMMENT 'Default currency code' AFTER `com_email`;

-- =============================================
-- 6. Add currency to invoice (iv) table
-- =============================================
ALTER TABLE `iv`
    ADD COLUMN `currency_code` VARCHAR(3) DEFAULT 'THB' COMMENT 'Invoice currency' AFTER `total`,
    ADD COLUMN `exchange_rate` DECIMAL(16,6) DEFAULT NULL COMMENT 'Exchange rate to THB at time of creation' AFTER `currency_code`;

-- =============================================
-- 7. Add PromptPay as payment method
-- =============================================
INSERT INTO `payment_methods` (`pm_name`, `pm_code`, `pm_type`, `pm_active`, `pm_sort`)
SELECT 'PromptPay', 'promptpay', 'online', 1, 
       COALESCE((SELECT MAX(`pm_sort`) FROM `payment_methods` AS pm2), 0) + 1
WHERE NOT EXISTS (SELECT 1 FROM `payment_methods` WHERE `pm_code` = 'promptpay');

-- =============================================
-- 8. Add currency to payment_log for multi-currency payments
-- =============================================
ALTER TABLE `payment_log`
    ADD COLUMN `currency_code` VARCHAR(3) DEFAULT 'THB' COMMENT 'Payment currency' AFTER `amount`,
    ADD COLUMN `exchange_rate` DECIMAL(16,6) DEFAULT NULL COMMENT 'Exchange rate to THB' AFTER `currency_code`;

-- =============================================================================
-- End of Q2 2026 Migration
-- =============================================================================
