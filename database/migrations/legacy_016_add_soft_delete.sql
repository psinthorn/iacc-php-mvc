-- ============================================================================
-- Migration: Add Soft Delete Support
-- 
-- This migration adds deleted_at columns to enable soft delete functionality.
-- Soft delete keeps records in the database but marks them as deleted,
-- allowing for audit trails and data recovery.
--
-- Run this migration to enable soft delete on the tables you need.
-- ============================================================================

-- To use soft delete, run the ALTER TABLE commands for tables you want to enable.
-- Then use the HardClass methods:
--   $hard->softDelete('tablename', ['id' => $id]);     -- Mark as deleted
--   $hard->restore('tablename', ['id' => $id]);        -- Restore deleted record
--   $hard->selectActiveSafe('tablename', []);          -- Get non-deleted records
--   $hard->selectDeletedSafe('tablename', []);         -- Get deleted records
--   $hard->forceDelete('tablename', ['id' => $id]);    -- Permanently delete

-- ============================================================================
-- CORE TABLES - Uncomment and run for tables you want soft delete on
-- ============================================================================

-- Company table (recommended for audit trail)
-- ALTER TABLE `company` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `id`;
-- CREATE INDEX `idx_company_deleted_at` ON `company` (`deleted_at`);

-- Type (Product categories)
-- ALTER TABLE `type` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `id`;
-- CREATE INDEX `idx_type_deleted_at` ON `type` (`deleted_at`);

-- Brand
-- ALTER TABLE `brand` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `id`;
-- CREATE INDEX `idx_brand_deleted_at` ON `brand` (`deleted_at`);

-- Model
-- ALTER TABLE `model` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `id`;
-- CREATE INDEX `idx_model_deleted_at` ON `model` (`deleted_at`);

-- Category
-- ALTER TABLE `category` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `id`;
-- CREATE INDEX `idx_category_deleted_at` ON `category` (`deleted_at`);

-- ============================================================================
-- TRANSACTION TABLES - Uncomment for business document audit trails
-- ============================================================================

-- Purchase Requisition (PR)
-- ALTER TABLE `pr` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_pr_deleted_at` ON `pr` (`deleted_at`);

-- Purchase Order (PO)
-- ALTER TABLE `po` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_po_deleted_at` ON `po` (`deleted_at`);

-- Invoice (IV)
-- ALTER TABLE `iv` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_iv_deleted_at` ON `iv` (`deleted_at`);

-- Delivery
-- ALTER TABLE `deliver` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_deliver_deleted_at` ON `deliver` (`deleted_at`);

-- Payment
-- ALTER TABLE `pay` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_pay_deleted_at` ON `pay` (`deleted_at`);

-- Receipt
-- ALTER TABLE `receipt` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_receipt_deleted_at` ON `receipt` (`deleted_at`);

-- Voucher
-- ALTER TABLE `voucher` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;
-- CREATE INDEX `idx_voucher_deleted_at` ON `voucher` (`deleted_at`);

-- ============================================================================
-- HELPER VIEW EXAMPLES
-- Create views that automatically exclude soft-deleted records
-- ============================================================================

-- Example view for active companies only:
-- CREATE OR REPLACE VIEW `v_company_active` AS 
--   SELECT * FROM `company` WHERE `deleted_at` IS NULL;

-- Example view for active products only:
-- CREATE OR REPLACE VIEW `v_type_active` AS 
--   SELECT * FROM `type` WHERE `deleted_at` IS NULL;

-- ============================================================================
-- ROLLBACK SCRIPT (if needed)
-- ============================================================================

-- To remove soft delete from a table:
-- ALTER TABLE `company` DROP COLUMN `deleted_at`;
-- ALTER TABLE `type` DROP COLUMN `deleted_at`;
-- etc.
