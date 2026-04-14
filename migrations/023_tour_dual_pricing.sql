-- Migration: 023_tour_dual_pricing.sql
-- Description: Add dual pricing (Thai/Foreigner) to booking items + is_thai flag on passengers
-- Date: 2025-07-16

-- ============================================================================
-- 1. tour_booking_items: add price_thai, price_foreigner, qty_thai, qty_foreigner
-- ============================================================================
ALTER TABLE `tour_booking_items`
    ADD COLUMN `price_thai` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`,
    ADD COLUMN `price_foreigner` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `price_thai`,
    ADD COLUMN `qty_thai` INT(11) NOT NULL DEFAULT 0 AFTER `price_foreigner`,
    ADD COLUMN `qty_foreigner` INT(11) NOT NULL DEFAULT 0 AFTER `qty_thai`;

-- Migrate existing data: copy unit_price to both price columns, quantity to both qty columns
UPDATE `tour_booking_items`
SET `price_thai` = `unit_price`,
    `price_foreigner` = `unit_price`,
    `qty_thai` = `quantity`,
    `qty_foreigner` = 0
WHERE `price_thai` = 0 AND `price_foreigner` = 0 AND `unit_price` > 0;

-- ============================================================================
-- 2. tour_booking_pax: add is_thai flag
-- ============================================================================
ALTER TABLE `tour_booking_pax`
    ADD COLUMN `is_thai` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pax_type`;

-- Migrate existing data: set is_thai=1 where nationality is Thai
UPDATE `tour_booking_pax`
SET `is_thai` = 1
WHERE LOWER(`nationality`) IN ('thai', 'thailand', 'ไทย');
