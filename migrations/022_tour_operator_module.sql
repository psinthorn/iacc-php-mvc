-- Migration: 022_tour_operator_module.sql
-- Description: Tour Operator Agent module — MySQL 5.7 compatible (idempotent)
-- Date: 2026-04-10
-- Revised: agents = company records + tour_agent_profiles extension

-- ============================================================================
-- Table 1: company_modules
-- ============================================================================
CREATE TABLE IF NOT EXISTS `company_modules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `module_key` VARCHAR(50) NOT NULL COMMENT 'tour_operator, api_channel, etc.',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `plan` VARCHAR(20) NOT NULL DEFAULT 'trial' COMMENT 'trial, basic, pro',
    `usage_count` INT(11) NOT NULL DEFAULT 0,
    `usage_limit` INT(11) DEFAULT NULL COMMENT 'NULL = unlimited',
    `valid_from` DATE DEFAULT NULL,
    `valid_to` DATE DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_module` (`company_id`, `module_key`),
    KEY `idx_module` (`module_key`),
    KEY `idx_enabled` (`company_id`, `is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 2: tour_agent_profiles (extension for company vendors acting as agents)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_agent_profiles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_ref_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent vendor record',
    `company_id` INT(11) NOT NULL COMMENT 'FK company.id — tenant owner',
    `commission_type` ENUM('net_rate','percentage') NOT NULL DEFAULT 'percentage',
    `commission_adult` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `commission_child` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `contract_start` DATE DEFAULT NULL,
    `contract_end` DATE DEFAULT NULL,
    `contact_line` VARCHAR(100) DEFAULT NULL,
    `contact_whatsapp` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_agent_company` (`company_ref_id`, `company_id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_contract` (`contract_start`, `contract_end`),
    KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 3: tour_locations
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_locations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `location_type` ENUM('pickup','dropoff','activity','hotel') NOT NULL DEFAULT 'pickup',
    `address` TEXT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_type` (`location_type`),
    KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 4: tour_bookings
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `booking_number` VARCHAR(30) NOT NULL,
    `customer_id` INT(11) DEFAULT NULL COMMENT 'FK company.id (customer)',
    `agent_id` INT(11) DEFAULT NULL COMMENT 'FK company.id (agent vendor)',
    `booking_by` VARCHAR(255) DEFAULT NULL,
    `travel_date` DATE NOT NULL,
    `pax_adult` INT(11) NOT NULL DEFAULT 0,
    `pax_child` INT(11) NOT NULL DEFAULT 0,
    `pax_infant` INT(11) NOT NULL DEFAULT 0,
    `total_pax` INT(11) GENERATED ALWAYS AS (`pax_adult` + `pax_child` + `pax_infant`) STORED,
    `pickup_location_id` INT(11) DEFAULT NULL,
    `pickup_hotel` VARCHAR(255) DEFAULT NULL,
    `pickup_room` VARCHAR(50) DEFAULT NULL,
    `pickup_time` TIME DEFAULT NULL,
    `voucher_number` VARCHAR(100) DEFAULT NULL,
    `entrance_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `vat` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'THB',
    `status` ENUM('draft','confirmed','completed','cancelled') NOT NULL DEFAULT 'draft',
    `remark` TEXT DEFAULT NULL,
    `pr_id` INT(11) DEFAULT NULL,
    `po_id` INT(11) DEFAULT NULL,
    `invoice_id` INT(11) DEFAULT NULL,
    `receipt_id` INT(11) DEFAULT NULL,
    `delivery_id` INT(11) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_booking_num` (`company_id`, `booking_number`),
    KEY `idx_company` (`company_id`),
    KEY `idx_travel_date` (`travel_date`),
    KEY `idx_status` (`status`),
    KEY `idx_agent` (`agent_id`),
    KEY `idx_customer` (`customer_id`),
    KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 5: tour_booking_items
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_booking_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `item_type` ENUM('tour','transfer','entrance','extra','hotel') NOT NULL DEFAULT 'tour',
    `description` VARCHAR(500) NOT NULL,
    `contract_rate_id` INT(11) DEFAULT NULL,
    `rate_label` VARCHAR(30) DEFAULT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_type` (`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table 6: tour_booking_pax
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_booking_pax` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `pax_type` ENUM('adult','child','infant') NOT NULL DEFAULT 'adult',
    `full_name` VARCHAR(255) NOT NULL,
    `nationality` VARCHAR(100) DEFAULT NULL,
    `passport_number` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed data
-- ============================================================================
INSERT IGNORE INTO `company_modules` (`company_id`, `module_key`, `is_enabled`, `plan`)
VALUES (165, 'tour_operator', 1, 'trial');

INSERT IGNORE INTO `permissions` (`key`, `name`, `description`) VALUES
('tour.view', 'View Tour Bookings', 'View tour bookings'),
('tour.create', 'Create Tour Bookings', 'Create tour bookings'),
('tour.edit', 'Edit Tour Bookings', 'Edit tour bookings'),
('tour.delete', 'Delete Tour Bookings', 'Delete tour bookings'),
('tour.export', 'Export Tour Data', 'Export tour data'),
('tour_agent.view', 'View Tour Agents', 'View tour agent profiles'),
('tour_agent.manage', 'Manage Tour Agents', 'Manage tour agent profiles'),
('tour_location.manage', 'Manage Tour Locations', 'Manage tour locations');
