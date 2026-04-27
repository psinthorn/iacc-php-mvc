-- ============================================================
-- Migration: Product-Aware Allotment Configs
-- Adds per-product allotment tracking (opt-in via config table)
-- Date: 2026-04-27
-- ============================================================

-- 1. tour_allotment_configs — Which products are allotment-tracked
CREATE TABLE IF NOT EXISTS `tour_allotment_configs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `model_id` INT(11) NOT NULL COMMENT 'FK model.id — the product to track',
    `fleet_id` INT(11) DEFAULT NULL COMMENT 'FK tour_fleets.id — assigned fleet (NULL = use default)',
    `default_capacity` INT(11) NOT NULL DEFAULT 38 COMMENT 'Seats per date for this product',
    `schedule_type` ENUM('daily','monthly','custom') NOT NULL DEFAULT 'daily',
    `schedule_days` VARCHAR(100) DEFAULT NULL COMMENT 'Comma-separated day-of-month for monthly (e.g. 14,15)',
    `show_on_dashboard` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_model` (`company_id`, `model_id`),
    KEY `idx_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Product allotment configuration — which products are tracked';

-- 2. Add model_id to tour_allotments for per-product tracking
ALTER TABLE `tour_allotments`
    ADD COLUMN `model_id` INT(11) DEFAULT NULL COMMENT 'FK model.id — NULL = legacy fleet-wide'
    AFTER `fleet_id`;

-- 3. Rebuild unique key to include model_id
ALTER TABLE `tour_allotments`
    DROP INDEX `idx_company_fleet_date`,
    ADD UNIQUE KEY `idx_company_fleet_model_date` (`company_id`, `fleet_id`, `model_id`, `travel_date`),
    ADD KEY `idx_model_date` (`model_id`, `travel_date`);
