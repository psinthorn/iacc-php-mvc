-- ============================================================
-- cPanel Migration: Master Data — is_active toggle column
-- Date: 2026-04-23
-- Branch: feature/tour-booking-payments
-- Applies on top of: 2026_04_22_cpanel_payment_system.sql
--
-- Adds is_active TINYINT(1) DEFAULT 1 to:
--   category, brand, type, model
--
-- Run via phpMyAdmin or:
--   mysql -u<user> -p <dbname> < 2026_04_23_master_data_is_active.sql
--
-- Fully idempotent — safe to re-run.
-- ============================================================

-- ============================================================
-- PART 1: category.is_active
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'category'
      AND COLUMN_NAME  = 'is_active'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `category` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add index for is_active filter performance
SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'category'
      AND INDEX_NAME   = 'idx_cat_active'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `category` ADD KEY `idx_cat_active` (`is_active`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 2: brand.is_active
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'brand'
      AND COLUMN_NAME  = 'is_active'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `brand` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'brand'
      AND INDEX_NAME   = 'idx_brand_active'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `brand` ADD KEY `idx_brand_active` (`is_active`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 3: type.is_active
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'type'
      AND COLUMN_NAME  = 'is_active'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `type` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'type'
      AND INDEX_NAME   = 'idx_type_active'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `type` ADD KEY `idx_type_active` (`is_active`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 4: model.is_active
-- ============================================================
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'model'
      AND COLUMN_NAME  = 'is_active'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `model` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0=disabled,1=enabled'' AFTER `des`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'model'
      AND INDEX_NAME   = 'idx_model_active'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `model` ADD KEY `idx_model_active` (`is_active`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- End of migration 2026_04_23_master_data_is_active.sql
-- ============================================================
