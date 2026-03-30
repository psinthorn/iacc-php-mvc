-- ============================================================================
-- Remaining Database Fixes Migration
-- ============================================================================
-- Version: 1.1.0
-- Date: 2026-01-03
-- 
-- This migration addresses remaining issues:
-- 1. Convert remaining MyISAM tables to InnoDB
-- 2. Standardize character set to utf8mb4_unicode_ci
-- 3. Add missing indexes for performance
-- ============================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS;
SET @OLD_SQL_MODE = @@SQL_MODE;

SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- ============================================================================
-- PHASE 1: Convert remaining MyISAM tables to InnoDB
-- ============================================================================

SELECT 'PHASE 1: Converting remaining MyISAM tables to InnoDB...' AS status;

-- Reference tables still on MyISAM
ALTER TABLE `brand` ENGINE=InnoDB;
ALTER TABLE `category` ENGINE=InnoDB;
ALTER TABLE `model` ENGINE=InnoDB;
ALTER TABLE `map_type_to_brand` ENGINE=InnoDB;

-- Utility tables
ALTER TABLE `billing` ENGINE=InnoDB;
ALTER TABLE `gen_serial` ENGINE=InnoDB;
ALTER TABLE `keep_log` ENGINE=InnoDB;
ALTER TABLE `receive` ENGINE=InnoDB;
ALTER TABLE `sendoutitem` ENGINE=InnoDB;
ALTER TABLE `store` ENGINE=InnoDB;
ALTER TABLE `store_sale` ENGINE=InnoDB;
ALTER TABLE `tmp_product` ENGINE=InnoDB;
ALTER TABLE `user` ENGINE=InnoDB;

-- Board tables (legacy forum)
ALTER TABLE `board` ENGINE=InnoDB;
ALTER TABLE `board1` ENGINE=InnoDB;
ALTER TABLE `board2` ENGINE=InnoDB;
ALTER TABLE `board_group` ENGINE=InnoDB;

SELECT 'PHASE 1: Completed - All tables converted to InnoDB' AS status;

-- ============================================================================
-- PHASE 2: Standardize Character Sets to utf8mb4
-- ============================================================================

SELECT 'PHASE 2: Standardizing character sets to utf8mb4...' AS status;

-- Core tables
ALTER TABLE `authorize` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `company` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `company_addr` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `company_credit` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Transaction tables
ALTER TABLE `po` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pr` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `product` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pay` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `payment` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `deliver` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `iv` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `receipt` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `voucher` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Reference tables
ALTER TABLE `type` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `brand` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `model` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `category` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `map_type_to_brand` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- User and utility tables
ALTER TABLE `user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `billing` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gen_serial` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `keep_log` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `receive` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sendoutitem` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `store` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `store_sale` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tmp_product` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Board tables
ALTER TABLE `board` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `board1` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `board2` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `board_group` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'PHASE 2: Completed - Character sets standardized' AS status;

-- ============================================================================
-- PHASE 3: Add Missing Indexes for Performance
-- ============================================================================

SELECT 'PHASE 3: Adding missing indexes...' AS status;

-- Check and add indexes only if they don't exist
-- PO table indexes (date already has index, no status column in po)
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'po' AND index_name = 'idx_po_ref');
SET @sql = IF(@exists = 0, 'ALTER TABLE `po` ADD INDEX `idx_po_ref` (`ref`)', 'SELECT "idx_po_ref already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- PR table indexes
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pr' AND index_name = 'idx_pr_date');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pr` ADD INDEX `idx_pr_date` (`date`)', 'SELECT "idx_pr_date already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pr' AND index_name = 'idx_pr_status');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pr` ADD INDEX `idx_pr_status` (`status`)', 'SELECT "idx_pr_status already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pr' AND index_name = 'idx_pr_cus_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pr` ADD INDEX `idx_pr_cus_id` (`cus_id`)', 'SELECT "idx_pr_cus_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pr' AND index_name = 'idx_pr_ven_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pr` ADD INDEX `idx_pr_ven_id` (`ven_id`)', 'SELECT "idx_pr_ven_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Product table indexes
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product' AND index_name = 'idx_product_po_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE `product` ADD INDEX `idx_product_po_id` (`po_id`)', 'SELECT "idx_product_po_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product' AND index_name = 'idx_product_type');
SET @sql = IF(@exists = 0, 'ALTER TABLE `product` ADD INDEX `idx_product_type` (`type`)', 'SELECT "idx_product_type already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Pay table indexes
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pay' AND index_name = 'idx_pay_po_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pay` ADD INDEX `idx_pay_po_id` (`po_id`)', 'SELECT "idx_pay_po_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'pay' AND index_name = 'idx_pay_date');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pay` ADD INDEX `idx_pay_date` (`date`)', 'SELECT "idx_pay_date already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Deliver table indexes
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'deliver' AND index_name = 'idx_deliver_po_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE `deliver` ADD INDEX `idx_deliver_po_id` (`po_id`)', 'SELECT "idx_deliver_po_id already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Company table indexes
SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'company' AND index_name = 'idx_company_name_en');
SET @sql = IF(@exists = 0, 'ALTER TABLE `company` ADD INDEX `idx_company_name_en` (`name_en`)', 'SELECT "idx_company_name_en already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'company' AND index_name = 'idx_company_name_th');
SET @sql = IF(@exists = 0, 'ALTER TABLE `company` ADD INDEX `idx_company_name_th` (`name_th`)', 'SELECT "idx_company_name_th already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'PHASE 3: Completed - Indexes added' AS status;

-- ============================================================================
-- PHASE 4: Create Migration Log Table
-- ============================================================================

SELECT 'PHASE 4: Creating migration log table...' AS status;

CREATE TABLE IF NOT EXISTS `_migration_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration_name` VARCHAR(255) NOT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('success', 'failed', 'rolled_back') DEFAULT 'success',
    `notes` TEXT,
    UNIQUE KEY `uk_migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log this migration
INSERT INTO `_migration_log` (`migration_name`, `status`, `notes`) 
VALUES ('002_remaining_database_fixes_v1.1', 'success', 'Converted remaining MyISAM to InnoDB, standardized charset, added indexes')
ON DUPLICATE KEY UPDATE `executed_at` = NOW(), `notes` = CONCAT(`notes`, ' | Re-run on ', NOW());

SELECT 'PHASE 4: Completed - Migration logged' AS status;

-- ============================================================================
-- Restore original settings
-- ============================================================================

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET SQL_MODE = @OLD_SQL_MODE;

-- ============================================================================
-- Migration Complete
-- ============================================================================

SELECT '============================================' AS '';
SELECT 'MIGRATION COMPLETED SUCCESSFULLY!' AS status;
SELECT '============================================' AS '';
