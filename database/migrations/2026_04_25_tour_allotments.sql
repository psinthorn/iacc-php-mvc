-- ============================================================
-- Migration: Tour Allotment Management
-- Fleet definitions + daily seat allotments + audit log
-- Date: 2026-04-25
-- ============================================================

-- 1. tour_fleets — Fleet/vehicle definitions with seat capacity
CREATE TABLE IF NOT EXISTS `tour_fleets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `fleet_name` VARCHAR(255) NOT NULL COMMENT 'e.g. Speedboat Alpha',
    `fleet_type` VARCHAR(50) NOT NULL DEFAULT 'speedboat' COMMENT 'speedboat, ferry, van, bus',
    `capacity` INT(11) NOT NULL DEFAULT 38 COMMENT 'Total seats per unit',
    `unit_count` INT(11) NOT NULL DEFAULT 1 COMMENT 'How many units in this fleet',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fleet/vehicle definitions with seat capacity';

-- 2. tour_allotments — Daily seat allotment per fleet
CREATE TABLE IF NOT EXISTS `tour_allotments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `fleet_id` INT(11) NOT NULL COMMENT 'FK tour_fleets.id',
    `travel_date` DATE NOT NULL,
    `total_seats` INT(11) NOT NULL DEFAULT 38 COMMENT 'Copied from fleet.capacity * unit_count at creation',
    `booked_seats` INT(11) NOT NULL DEFAULT 0 COMMENT 'Maintained counter of confirmed pax',
    `manual_override` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=admin manually set total_seats',
    `is_closed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=no more bookings accepted',
    `closed_reason` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_fleet_date` (`company_id`, `fleet_id`, `travel_date`),
    KEY `idx_travel_date` (`travel_date`),
    KEY `idx_company_date` (`company_id`, `travel_date`),
    KEY `idx_fleet` (`fleet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily seat allotment per fleet, auto-deducted on booking confirmation';

-- 3. tour_allotment_logs — Audit trail for allotment changes
CREATE TABLE IF NOT EXISTS `tour_allotment_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `allotment_id` INT(11) NOT NULL,
    `booking_id` INT(11) DEFAULT NULL,
    `action` ENUM('book','release','manual_set','close','reopen','recalculate') NOT NULL,
    `seats_delta` INT(11) NOT NULL DEFAULT 0 COMMENT 'Positive = consumed, negative = released',
    `booked_seats_after` INT(11) NOT NULL DEFAULT 0,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_allotment` (`allotment_id`),
    KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for allotment changes';

-- 4. Seed: Default fleet for company 165
INSERT IGNORE INTO `tour_fleets` (`company_id`, `fleet_name`, `fleet_type`, `capacity`, `unit_count`)
VALUES (165, 'Speedboat 1', 'speedboat', 38, 1);
