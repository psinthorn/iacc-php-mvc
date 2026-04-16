-- ============================================================
-- Migration 012: Add default Adult/Child fallback fields
-- When Thai/Foreigner specific fields are 0, system uses default
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/012_contract_rate_default_fields.sql
-- ============================================================

-- 1. Add default service rate columns
ALTER TABLE `contract_rate`
  ADD COLUMN `adult_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default adult rate — fallback when Thai/Foreigner is 0' AFTER `rate_type`,
  ADD COLUMN `child_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default child rate — fallback when Thai/Foreigner is 0' AFTER `adult_default`;

-- 2. Add default entrance fee columns
ALTER TABLE `contract_rate`
  ADD COLUMN `entrance_adult_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default entrance adult — fallback when Thai/Foreigner is 0' AFTER `child_foreigner`,
  ADD COLUMN `entrance_child_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default entrance child — fallback when Thai/Foreigner is 0' AFTER `entrance_adult_default`;

-- 3. Backfill: copy existing Thai values into defaults (reasonable baseline)
UPDATE `contract_rate`
SET `adult_default` = `adult_thai`,
    `child_default` = `child_thai`,
    `entrance_adult_default` = `entrance_adult_thai`,
    `entrance_child_default` = `entrance_child_thai`
WHERE `deleted_at` IS NULL;
