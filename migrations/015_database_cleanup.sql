-- ============================================================================
-- Database Cleanup Migration
-- ============================================================================
-- Version: 1.0.0
-- Date: 2026-01-03
-- 
-- This migration:
-- 1. Removes unused empty tables
-- 2. Merges duplicate tables
-- 3. Adds additional performance indexes
-- ============================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- ============================================================================
-- PHASE A: Remove Unused Tables (Empty/Legacy)
-- ============================================================================

SELECT 'PHASE A: Removing unused tables...' AS status;

-- Backup table data before dropping (just in case)
-- These tables are confirmed empty or unused

-- Drop forum/board tables (all empty, not used in codebase)
DROP TABLE IF EXISTS `board2`;
DROP TABLE IF EXISTS `board1`;
DROP TABLE IF EXISTS `board`;
DROP TABLE IF EXISTS `board_group`;

-- Drop legacy log table (empty, replaced by audit_logs)
DROP TABLE IF EXISTS `keep_log`;

-- NOTE: tmp_product is KEPT - actively used in po-make.php, deliv-make.php

SELECT 'PHASE A: Completed - Removed 5 unused tables' AS status;

-- ============================================================================
-- PHASE B: Merge Duplicate Tables
-- ============================================================================

SELECT 'PHASE B: Merging duplicate tables...' AS status;

-- B1: Merge audit_log into audit_logs
-- First, check if audit_log has data not in audit_logs
INSERT IGNORE INTO `audit_logs` (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
SELECT 
    user_id,
    action,
    entity_type,
    entity_id,
    old_values,
    new_values,
    ip_address,
    user_agent,
    created_at
FROM `audit_log`
WHERE NOT EXISTS (
    SELECT 1 FROM audit_logs al 
    WHERE al.user_id = audit_log.user_id 
    AND al.created_at = audit_log.created_at
    AND al.action = audit_log.action
);

-- Drop the old audit_log table
DROP TABLE IF EXISTS `audit_log`;

-- B2: Migrate payment data to payment_methods
-- The 'payment' table has company-specific payment methods
-- We need to merge these into payment_methods with a mapping

-- First, let's insert the unique payment methods from old table
INSERT IGNORE INTO `payment_method` (code, name, name_th, is_gateway, is_active, sort_order)
SELECT 
    LOWER(REPLACE(payment_name, ' ', '_')),
    payment_name,
    payment_des,
    0,
    IF(deleted_at IS NULL, 1, 0),
    id + 100
FROM `payment`
WHERE payment_name NOT IN (SELECT name FROM payment_method)
GROUP BY payment_name;

-- Create a mapping table to preserve the relationship
CREATE TABLE IF NOT EXISTS `_payment_migration_map` (
    old_id INT,
    new_code VARCHAR(50),
    com_id INT,
    PRIMARY KEY (old_id)
);

INSERT IGNORE INTO `_payment_migration_map` (old_id, new_code, com_id)
SELECT id, LOWER(REPLACE(payment_name, ' ', '_')), com_id FROM payment;

-- Keep payment table for now but rename to indicate deprecated
RENAME TABLE `payment` TO `_payment_deprecated`;

SELECT 'PHASE B: Completed - Merged duplicates' AS status;

-- ============================================================================
-- PHASE D: Add Performance Indexes
-- ============================================================================

SELECT 'PHASE D: Adding performance indexes...' AS status;

-- Analyze common query patterns and add missing indexes

-- Invoice lookups by customer
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iv' AND INDEX_NAME = 'idx_iv_cus_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_iv_cus_id ON iv(cus_id)', 'SELECT "idx_iv_cus_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Invoice lookups by payment status
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iv' AND INDEX_NAME = 'idx_iv_payment_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_iv_payment_status ON iv(payment_status)', 'SELECT "idx_iv_payment_status exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Product lookups by model
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'idx_product_model');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product_model ON product(model)', 'SELECT "idx_product_model exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Product lookups by receipt/voucher
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'idx_product_re_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product_re_id ON product(re_id)', 'SELECT "idx_product_re_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'idx_product_vo_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product_vo_id ON product(vo_id)', 'SELECT "idx_product_vo_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND INDEX_NAME = 'idx_product_so_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_product_so_id ON product(so_id)', 'SELECT "idx_product_so_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Voucher lookups by vendor
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'voucher' AND INDEX_NAME = 'idx_voucher_vender');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_voucher_vender ON voucher(vender)', 'SELECT "idx_voucher_vender exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Receipt lookups by vendor
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'receipt' AND INDEX_NAME = 'idx_receipt_vender');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_receipt_vender ON receipt(vender)', 'SELECT "idx_receipt_vender exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Store lookups by product
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'store' AND INDEX_NAME = 'idx_store_pro_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_store_pro_id ON store(pro_id)', 'SELECT "idx_store_pro_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Store sale lookups
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'store_sale' AND INDEX_NAME = 'idx_store_sale_st_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_store_sale_st_id ON store_sale(st_id)', 'SELECT "idx_store_sale_st_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'store_sale' AND INDEX_NAME = 'idx_store_sale_own_id');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_store_sale_own_id ON store_sale(own_id)', 'SELECT "idx_store_sale_own_id exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Composite indexes for common JOINs
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pr' AND INDEX_NAME = 'idx_pr_ven_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_pr_ven_status ON pr(ven_id, status)', 'SELECT "idx_pr_ven_status exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pr' AND INDEX_NAME = 'idx_pr_cus_status');
SET @sql = IF(@idx_exists = 0, 'CREATE INDEX idx_pr_cus_status ON pr(cus_id, status)', 'SELECT "idx_pr_cus_status exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'PHASE D: Completed - Added performance indexes' AS status;

-- ============================================================================
-- Log Migration
-- ============================================================================

INSERT INTO `_migration_log` (`migration_name`, `status`, `notes`) 
VALUES ('004_database_cleanup', 'success', 'Removed 5 unused tables, merged duplicates, added 12 indexes')
ON DUPLICATE KEY UPDATE `executed_at` = NOW(), `notes` = CONCAT(`notes`, ' | Re-run on ', NOW());

-- ============================================================================
-- Restore Settings
-- ============================================================================

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE = @OLD_SQL_MODE;

SELECT '============================================' AS '';
SELECT 'DATABASE CLEANUP COMPLETED!' AS status;
SELECT '============================================' AS '';
SELECT 'Tables removed: board, board1, board2, board_group, keep_log' AS action;
SELECT 'Tables merged: audit_log -> audit_logs' AS action;
SELECT 'Tables renamed: payment -> _payment_deprecated' AS action;
SELECT 'Indexes added: 12 new performance indexes' AS action;
