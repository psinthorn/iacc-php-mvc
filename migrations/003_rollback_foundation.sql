-- ============================================================================
-- Manual Rollback Script (SQL Only)
-- ============================================================================
-- Version: 1.0.0
-- Date: 2026-01-03
-- 
-- Use this if you need to manually revert changes without the shell script.
-- This reverts the structural changes but keeps InnoDB (recommended).
-- For full rollback, use 003_rollback_migration.sh with backup file.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- ROLLBACK PHASE 1: Revert authorize table to original structure
-- ============================================================================

SELECT 'ROLLBACK: Reverting authorize table...' AS status;

-- Remove the new id column and restore usr_name as primary key
-- WARNING: This will lose any auto-generated id values

-- First, check if id column exists before trying to drop
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
                   WHERE table_schema = DATABASE() 
                   AND table_name = 'authorize' 
                   AND column_name = 'id');

-- Only run if id column exists
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `authorize` 
     DROP PRIMARY KEY,
     DROP COLUMN `id`,
     MODIFY COLUMN `usr_name` VARCHAR(10) NOT NULL,
     ADD PRIMARY KEY (`usr_name`)',
    'SELECT "id column does not exist, skipping..." AS note');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove unique key if it exists
SET @key_exists = (SELECT COUNT(*) FROM information_schema.statistics 
                   WHERE table_schema = DATABASE() 
                   AND table_name = 'authorize' 
                   AND index_name = 'uk_authorize_usr_name');

SET @sql = IF(@key_exists > 0,
    'ALTER TABLE `authorize` DROP INDEX `uk_authorize_usr_name`',
    'SELECT "unique key does not exist, skipping..." AS note');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'ROLLBACK: authorize table reverted' AS status;

-- ============================================================================
-- ROLLBACK PHASE 2: Remove added indexes
-- ============================================================================

SELECT 'ROLLBACK: Removing added indexes...' AS status;

-- PO table indexes
ALTER TABLE `po` 
DROP INDEX IF EXISTS `idx_po_date`,
DROP INDEX IF EXISTS `idx_po_status`,
DROP INDEX IF EXISTS `idx_po_cus_id`,
DROP INDEX IF EXISTS `idx_po_ven_id`,
DROP INDEX IF EXISTS `idx_po_usr_id`;

-- PR table indexes
ALTER TABLE `pr` 
DROP INDEX IF EXISTS `idx_pr_date`,
DROP INDEX IF EXISTS `idx_pr_status`,
DROP INDEX IF EXISTS `idx_pr_cus_id`,
DROP INDEX IF EXISTS `idx_pr_ven_id`,
DROP INDEX IF EXISTS `idx_pr_usr_id`;

-- Product table indexes
ALTER TABLE `product` 
DROP INDEX IF EXISTS `idx_product_po_id`,
DROP INDEX IF EXISTS `idx_product_type`,
DROP INDEX IF EXISTS `idx_product_ban_id`;

-- Pay table indexes
ALTER TABLE `pay` 
DROP INDEX IF EXISTS `idx_pay_po_id`,
DROP INDEX IF EXISTS `idx_pay_date`;

-- Deliver table indexes
ALTER TABLE `deliver` 
DROP INDEX IF EXISTS `idx_deliver_po_id`,
DROP INDEX IF EXISTS `idx_deliver_date`;

-- Company table indexes
ALTER TABLE `company` 
DROP INDEX IF EXISTS `idx_company_name`,
DROP INDEX IF EXISTS `idx_company_customer`,
DROP INDEX IF EXISTS `idx_company_vendor`;

SELECT 'ROLLBACK: Indexes removed' AS status;

-- ============================================================================
-- ROLLBACK PHASE 3: Revert to MyISAM (OPTIONAL - NOT RECOMMENDED)
-- ============================================================================
-- Uncomment below if you REALLY want to go back to MyISAM (not recommended)

/*
SELECT 'ROLLBACK: Converting tables back to MyISAM (NOT RECOMMENDED)...' AS status;

ALTER TABLE `authorize` ENGINE=MyISAM;
ALTER TABLE `company` ENGINE=MyISAM;
ALTER TABLE `company_addr` ENGINE=MyISAM;
ALTER TABLE `company_credit` ENGINE=MyISAM;
ALTER TABLE `po` ENGINE=MyISAM;
ALTER TABLE `pr` ENGINE=MyISAM;
ALTER TABLE `product` ENGINE=MyISAM;
ALTER TABLE `pay` ENGINE=MyISAM;
ALTER TABLE `payment` ENGINE=MyISAM;
ALTER TABLE `deliver` ENGINE=MyISAM;
ALTER TABLE `iv` ENGINE=MyISAM;
ALTER TABLE `receipt` ENGINE=MyISAM;
ALTER TABLE `voucher` ENGINE=MyISAM;
ALTER TABLE `type` ENGINE=MyISAM;
ALTER TABLE `band` ENGINE=MyISAM;
ALTER TABLE `model` ENGINE=MyISAM;
ALTER TABLE `category` ENGINE=MyISAM;
ALTER TABLE `map_type_to_brand` ENGINE=MyISAM;
ALTER TABLE `user` ENGINE=MyISAM;

SELECT 'ROLLBACK: Tables converted to MyISAM' AS status;
*/

-- ============================================================================
-- ROLLBACK PHASE 4: Revert character set (OPTIONAL)
-- ============================================================================
-- Uncomment below if you want to revert to utf8 (not recommended)

/*
SELECT 'ROLLBACK: Reverting character sets...' AS status;

ALTER TABLE `authorize` CONVERT TO CHARACTER SET latin1;
ALTER TABLE `company` CONVERT TO CHARACTER SET utf8;
-- ... add other tables as needed

SELECT 'ROLLBACK: Character sets reverted' AS status;
*/

-- ============================================================================
-- Update migration log
-- ============================================================================

UPDATE `_migration_log` 
SET `status` = 'rolled_back', 
    `notes` = CONCAT(`notes`, ' | Rolled back on ', NOW())
WHERE `migration_name` = '002_critical_database_fixes';

SET FOREIGN_KEY_CHECKS = 1;

SELECT '============================================' AS '';
SELECT 'ROLLBACK COMPLETED' AS status;
SELECT '============================================' AS '';
SELECT 'Note: InnoDB and utf8mb4 were kept (recommended)' AS info;
SELECT 'For full rollback, restore from backup file' AS info2;
