-- ============================================================
-- Migration 008: Add company_type to company + create contract_rate table
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/008_company_type_and_contract_rate.sql
-- ============================================================

-- 1. Add company_type column to company table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='company' AND COLUMN_NAME='company_type');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `company` ADD COLUMN `company_type` VARCHAR(20) NOT NULL DEFAULT ''general'' COMMENT ''general, tour_agent, hotel, reseller, direct'' AFTER `vender`, ADD INDEX `idx_company_type` (`company_type`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Create contract_rate table
CREATE TABLE IF NOT EXISTS `contract_rate` (
    `id`            INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`    INT(11) NOT NULL COMMENT 'Tenant owner (multi-tenant FK)',
    `vendor_id`     INT(11) NOT NULL COMMENT 'Who sells (tour operator)',
    `customer_id`   INT(11) NOT NULL COMMENT 'Who buys (agent/hotel)',
    `model_id`      INT(11) DEFAULT NULL COMMENT 'Specific product (NULL = all products)',
    `rate_label`    VARCHAR(30) NOT NULL COMMENT 'adult, child, full_moon, etc.',
    `rate_amount`   DECIMAL(12,2) NOT NULL COMMENT 'Contracted price',
    `min_quantity`  INT(11) NOT NULL DEFAULT 1 COMMENT 'Minimum qty for volume tier',
    `currency`      VARCHAR(3) NOT NULL DEFAULT 'THB',
    `valid_from`    DATE NOT NULL,
    `valid_to`      DATE NOT NULL,
    `notes`         TEXT DEFAULT NULL,
    `deleted_at`    DATETIME DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cr_company` (`company_id`),
    KEY `idx_cr_vendor` (`vendor_id`),
    KEY `idx_cr_customer` (`customer_id`),
    KEY `idx_cr_model` (`model_id`),
    KEY `idx_cr_label` (`rate_label`),
    KEY `idx_cr_validity` (`valid_from`, `valid_to`),
    KEY `idx_cr_deleted` (`deleted_at`),
    CONSTRAINT `fk_cr_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
    CONSTRAINT `fk_cr_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `company` (`id`),
    CONSTRAINT `fk_cr_customer` FOREIGN KEY (`customer_id`) REFERENCES `company` (`id`),
    CONSTRAINT `fk_cr_model` FOREIGN KEY (`model_id`) REFERENCES `model` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Per-agent/customer contract pricing with variant labels and volume tiers';

-- 3. Log migration
INSERT INTO `_migration_log` (`migration_name`, `status`, `notes`) 
VALUES ('008_company_type_and_contract_rate', 'success', 'Added company_type to company, created contract_rate table');
