-- Migration: 010_tour_operator_module.sql
-- Description: Tour Operator Agent module tables (revised: agents = company + tour_agent_profiles)
-- Date: 2026-04-10
-- Tables: company_modules, tour_agent_profiles, tour_locations, tour_bookings, tour_booking_items, tour_booking_pax

-- ============================================================================
-- Table 1: company_modules — Feature gating + future pay-per-use billing
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-company feature flags with future pay-per-use billing hooks';

-- ============================================================================
-- Table 2: tour_agent_profiles — Tour-specific extension for company vendors
-- Agents are existing company records (vender=1). This adds tour-specific fields.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_agent_profiles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_ref_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent vendor record',
    `company_id` INT(11) NOT NULL COMMENT 'FK company.id — tenant owner',
    `commission_type` ENUM('net_rate','percentage') NOT NULL DEFAULT 'percentage',
    `commission_adult` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Rate or % for adults',
    `commission_child` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Rate or % for children',
    `contract_start` DATE DEFAULT NULL,
    `contract_end` DATE DEFAULT NULL,
    `contact_person` VARCHAR(200) DEFAULT NULL COMMENT 'Contact person name',
    `contact_email` VARCHAR(200) DEFAULT NULL COMMENT 'Email address',
    `contact_phone` VARCHAR(50) DEFAULT NULL COMMENT 'Phone number',
    `contact_fax` VARCHAR(50) DEFAULT NULL COMMENT 'Fax number',
    `contact_line` VARCHAR(100) DEFAULT NULL COMMENT 'LINE ID',
    `contact_whatsapp` VARCHAR(100) DEFAULT NULL COMMENT 'WhatsApp number',
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_agent_company` (`company_ref_id`, `company_id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_contract` (`contract_start`, `contract_end`),
    KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tour-specific profile extension for company vendor records acting as agents';

-- ============================================================================
-- Table 3: tour_locations — Pickup/dropoff/activity locations
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_locations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'Location name',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tour pickup/dropoff/activity locations';

-- ============================================================================
-- Table 4: tour_bookings — Core booking record
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL,
    `booking_number` VARCHAR(30) NOT NULL COMMENT 'BK-YYMMDD-001 format',
    `customer_id` INT(11) DEFAULT NULL COMMENT 'FK company.id (customer)',
    `agent_id` INT(11) DEFAULT NULL COMMENT 'FK company.id (agent vendor)',
    `booking_by` VARCHAR(255) DEFAULT NULL COMMENT 'Person who booked / lead name',
    `travel_date` DATE NOT NULL,
    `pax_adult` INT(11) NOT NULL DEFAULT 0,
    `pax_child` INT(11) NOT NULL DEFAULT 0,
    `pax_infant` INT(11) NOT NULL DEFAULT 0,
    `total_pax` INT(11) GENERATED ALWAYS AS (`pax_adult` + `pax_child` + `pax_infant`) STORED,
    `pickup_location_id` INT(11) DEFAULT NULL COMMENT 'FK tour_locations.id',
    `pickup_hotel` VARCHAR(255) DEFAULT NULL,
    `pickup_room` VARCHAR(50) DEFAULT NULL,
    `pickup_time` TIME DEFAULT NULL,
    `voucher_number` VARCHAR(100) DEFAULT NULL COMMENT 'External voucher/receipt ref',
    `entrance_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `vat` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'THB',
    `status` ENUM('draft','confirmed','completed','cancelled') NOT NULL DEFAULT 'draft',
    `remark` TEXT DEFAULT NULL,
    `pr_id` INT(11) DEFAULT NULL COMMENT 'FK pr.id — linked PR',
    `po_id` INT(11) DEFAULT NULL COMMENT 'FK po.id — linked PO',
    `invoice_id` INT(11) DEFAULT NULL COMMENT 'FK iv.tex — linked Invoice',
    `receipt_id` INT(11) DEFAULT NULL COMMENT 'FK receipt.id — linked Receipt',
    `delivery_id` INT(11) DEFAULT NULL COMMENT 'FK deliver.id — linked Delivery',
    `created_by` INT(11) DEFAULT NULL COMMENT 'FK users.usr_id',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tour booking records with document links';

-- ============================================================================
-- Table 5: tour_booking_items — Line items (tour/transfer/entrance/extra)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_booking_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL COMMENT 'FK tour_bookings.id',
    `item_type` ENUM('tour','transfer','entrance','extra','hotel') NOT NULL DEFAULT 'tour',
    `description` VARCHAR(500) NOT NULL,
    `contract_rate_id` INT(11) DEFAULT NULL COMMENT 'FK contract_rate.id',
    `rate_label` VARCHAR(30) DEFAULT NULL COMMENT 'adult, child, full_moon',
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_type` (`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tour booking line items with optional contract rate links';

-- ============================================================================
-- Table 6: tour_booking_pax — Passenger manifest
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tour_booking_pax` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL COMMENT 'FK tour_bookings.id',
    `pax_type` ENUM('adult','child','infant') NOT NULL DEFAULT 'adult',
    `full_name` VARCHAR(255) NOT NULL,
    `nationality` VARCHAR(100) DEFAULT NULL,
    `passport_number` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual passenger details per booking';

-- ============================================================================
-- Seed: Enable tour_operator module for company 165
-- ============================================================================
INSERT IGNORE INTO `company_modules` (`company_id`, `module_key`, `is_enabled`, `plan`)
VALUES (165, 'tour_operator', 1, 'trial');

-- ============================================================================
-- RBAC: Tour Operator permissions
-- ============================================================================
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`) VALUES
('tour.view', 'View Tour Bookings', 'View tour bookings'),
('tour.create', 'Create Tour Bookings', 'Create tour bookings'),
('tour.edit', 'Edit Tour Bookings', 'Edit tour bookings'),
('tour.delete', 'Delete Tour Bookings', 'Delete tour bookings'),
('tour.export', 'Export Tour Data', 'Export tour data'),
('tour_agent.view', 'View Tour Agents', 'View tour agent profiles'),
('tour_agent.manage', 'Manage Tour Agents', 'Manage tour agent profiles'),
('tour_location.manage', 'Manage Tour Locations', 'Manage tour locations');
-- Add booking_date column to separate booking date from trip/travel date
-- booking_date = when the booking was made
-- travel_date  = when the trip happens

ALTER TABLE tour_bookings ADD COLUMN booking_date DATE NULL AFTER booking_number;

-- Backfill existing records with created_at date
UPDATE tour_bookings SET booking_date = DATE(created_at) WHERE booking_date IS NULL;
-- Add product/model FK and pax lines JSON to tour_booking_items
ALTER TABLE tour_booking_items
  ADD COLUMN product_type_id INT DEFAULT NULL AFTER notes,
  ADD COLUMN model_id INT DEFAULT NULL AFTER product_type_id,
  ADD COLUMN pax_lines_json TEXT DEFAULT NULL AFTER model_id;
-- Tour booking contacts: per-booking customer contact info
-- Module-isolated table (does NOT touch core company table)
CREATE TABLE IF NOT EXISTS tour_booking_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    contact_name VARCHAR(255) DEFAULT '',
    mobile VARCHAR(50) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    gender ENUM('male','female','other') NULL,
    nationality VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_id (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Add driver and vehicle columns to tour_bookings (2026-04-16)
ALTER TABLE tour_bookings
  ADD COLUMN driver_name VARCHAR(100) DEFAULT NULL AFTER remark,
  ADD COLUMN vehicle_no VARCHAR(50) DEFAULT NULL AFTER driver_name;

-- ============================================================================
-- Customer & Sales Rep Contact Separation with Flexible Messengers (2026-04-21)
-- ============================================================================

-- Add sales_rep_id to tour_bookings (FK to tour_agent_profiles / agent company)
-- Separate from agent_id which is the main tour partner channel
ALTER TABLE tour_bookings
  ADD COLUMN sales_rep_id INT(11) DEFAULT NULL COMMENT 'FK to tour_agent_profiles.id - sales rep/referrer' AFTER agent_id,
  ADD KEY idx_sales_rep (sales_rep_id);

-- Add contact_messengers to tour_booking_contacts (customer messengers)
-- Single TEXT field: flexible format "Line: @user, WhatsApp: +668123456, Telegram: @username"
ALTER TABLE tour_booking_contacts
  ADD COLUMN contact_messengers TEXT DEFAULT NULL AFTER nationality;

-- Add contact_messengers to tour_agent_profiles (sales rep messengers)
-- Same flexible format for Line, WhatsApp, Telegram, WeChat
ALTER TABLE tour_agent_profiles
  ADD COLUMN contact_telegram VARCHAR(100) DEFAULT NULL COMMENT 'Telegram username/ID' AFTER contact_whatsapp,
  ADD COLUMN contact_wechat VARCHAR(100) DEFAULT NULL COMMENT 'WeChat ID' AFTER contact_telegram,
  ADD COLUMN contact_messengers TEXT DEFAULT NULL COMMENT 'Flexible messengers: Line: @user, WhatsApp: +668123456, Telegram: @tg, WeChat: wxid' AFTER contact_wechat;

-- ============================================================================
-- Payment Method: fix unique key to be per-company (2026-04-21)
-- ============================================================================

-- Drop global unique key on code, replace with composite (code, company_id)
ALTER TABLE payment_method DROP INDEX IF EXISTS `code`;
ALTER TABLE payment_method ADD UNIQUE KEY `code_company` (`code`, `company_id`);

-- Seed payment methods for company 165 (My Samui Island Tour)
INSERT IGNORE INTO payment_method (code, name, name_th, icon, description, is_gateway, is_active, sort_order, company_id) VALUES
('cash',          'Cash',          'เงินสด',     'fa-money',       'Cash payment',           0, 1, 1, 165),
('bank_transfer', 'Bank Transfer', 'โอนเงิน',    'fa-university',  'Bank transfer',          0, 1, 2, 165),
('credit_card',   'Credit Card',   'บัตรเครดิต', 'fa-credit-card', 'Credit card',            0, 1, 3, 165),
('promptpay',     'PromptPay',     'พร้อมเพย์',  'fa-qrcode',      'PromptPay QR',           1, 1, 4, 165),
('stripe',        'Stripe',        'Stripe',      'fa-cc-stripe',   'Stripe gateway',         1, 1, 5, 165),
('paypal',        'PayPal',        'PayPal',      'fa-paypal',      'PayPal gateway',         1, 1, 6, 165);
