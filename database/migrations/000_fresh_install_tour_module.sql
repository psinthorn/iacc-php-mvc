-- ============================================================================
-- 000_fresh_install_tour_module.sql
-- Fresh-install superset for the iACC Tour Operator Module
-- ============================================================================
-- ONE-FILE installer that bundles every migration related to the tour module
-- in a single idempotent script. Use this on production / fresh installs
-- INSTEAD of importing the individual files in sequence.
--
-- Replaces the need to import:
--   tour_all_migrations.sql, 008_company_type_and_contract_rate.sql,
--   phpmyadmin_develop_to_main.sql, 2026_04_main_catchup.sql,
--   2026_04_25_tour_allotments.sql, 2026_04_27_product_allotment_configs.sql,
--   018_tour_contract_v2_combined.sql
--
-- DESIGN:
--   - All tables are created in their FINAL (v2) shape with CREATE TABLE IF NOT EXISTS
--     → no v1→v2 column renames or destructive DROP COLUMNs needed
--   - All ALTERs to pre-existing core tables (model, category, brand, type) are
--     wrapped in INFORMATION_SCHEMA guards → safe to re-run
--   - Data seeds use INSERT IGNORE → safe to re-run
--   - No FK-dependent ordering issues — child tables created after parents
--
-- ASSUMES core iACC tables already exist:
--   company, model, type, category, brand, authorize, permissions
--
-- AFTER IMPORT:
--   1. Hit cron once to populate synced products (if any V2 contracts exist):
--        curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--   2. Verify:
--        SHOW TABLES LIKE 'tour_%';   -- expect 16+ rows
--        SHOW COLUMNS FROM tour_bookings LIKE 'status';
--        -- expect: enum('draft','confirmed','paid','completed','no_show','cancelled')
-- ============================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART A — Module gating + agent profiles + locations                      ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `company_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `module_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'tour_operator, api_channel, etc.',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `plan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial' COMMENT 'trial, basic, pro',
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `usage_limit` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `default_contract_id` int(11) DEFAULT NULL COMMENT 'FK agent_contracts.id — default contract for new agents',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_company_module` (`company_id`,`module_key`),
  KEY `idx_module` (`module_key`),
  KEY `idx_enabled` (`company_id`,`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-company feature flags with future pay-per-use billing hooks';

CREATE TABLE IF NOT EXISTS `tour_agent_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_ref_id` int(11) NOT NULL COMMENT 'FK company.id — the agent vendor record',
  `company_id` int(11) NOT NULL COMMENT 'FK company.id — tenant owner',
  `commission_type` enum('net_rate','percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `commission_adult` decimal(12,2) NOT NULL DEFAULT '0.00',
  `commission_child` decimal(12,2) NOT NULL DEFAULT '0.00',
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `contact_line` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LINE ID',
  `contact_whatsapp` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_telegram` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_wechat` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_messengers` text COLLATE utf8mb4_unicode_ci,
  `contact_person` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_mobile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_agent_company` (`company_ref_id`,`company_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_contract` (`contract_start`,`contract_end`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tour-specific profile extension for company vendor records acting as agents';

CREATE TABLE IF NOT EXISTS `tour_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_type` enum('pickup','dropoff','activity','hotel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pickup',
  `address` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_type` (`location_type`),
  KEY `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tour pickup/dropoff/activity locations';


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART B — Bookings (already includes payment + status v2 + sales_rep)     ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `tour_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `booking_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BK-YYMMDD-001 format',
  `booking_date` date DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'FK company.id (customer)',
  `agent_id` int(11) DEFAULT NULL COMMENT 'FK company.id (agent vendor)',
  `sales_rep_id` int(11) DEFAULT NULL COMMENT 'FK to tour_agent_profiles.id - sales rep/referrer',
  `booking_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `travel_date` date NOT NULL,
  `pax_adult` int(11) NOT NULL DEFAULT '0',
  `pax_child` int(11) NOT NULL DEFAULT '0',
  `pax_infant` int(11) NOT NULL DEFAULT '0',
  `total_pax` int(11) GENERATED ALWAYS AS (((`pax_adult` + `pax_child`) + `pax_infant`)) STORED,
  `pickup_location_id` int(11) DEFAULT NULL,
  `pickup_hotel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `voucher_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entrance_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_due` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THB',
  `status` enum('draft','confirmed','paid','completed','no_show','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `voucher_sent_at` datetime DEFAULT NULL,
  `invoice_sent_at` datetime DEFAULT NULL,
  `payment_status` enum('unpaid','deposit','partial','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `checkin_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkin_token_exp` date DEFAULT NULL,
  `checkin_status` tinyint(1) NOT NULL DEFAULT '0',
  `checkin_at` datetime DEFAULT NULL,
  `checkin_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self',
  `driver_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pr_id` int(11) DEFAULT NULL,
  `po_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `delivery_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_booking_num` (`company_id`,`booking_number`),
  UNIQUE KEY `idx_checkin_token` (`checkin_token`),
  KEY `idx_company` (`company_id`),
  KEY `idx_travel_date` (`travel_date`),
  KEY `idx_status` (`status`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_deleted` (`deleted_at`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_sales_rep` (`sales_rep_id`),
  KEY `idx_company_travel_checkin` (`company_id`,`travel_date`,`checkin_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tour booking records with document links';

-- Idempotency: if tour_bookings already exists from an older install (without
-- 'paid' / 'no_show' in the enum), upgrade the column. No-op when fresh.
SET @needs_status_alter := (
    SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
      AND COLUMN_NAME = 'status' AND COLUMN_TYPE LIKE '%paid%'
);
SET @sql := IF(@needs_status_alter,
    'ALTER TABLE `tour_bookings` MODIFY COLUMN `status` ENUM(''draft'',''confirmed'',''paid'',''completed'',''no_show'',''cancelled'') NOT NULL DEFAULT ''draft''',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `tour_booking_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `item_type` enum('tour','transfer','entrance','extra','hotel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tour',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_rate_id` int(11) DEFAULT NULL,
  `rate_label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `unit_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `price_thai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `price_foreigner` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty_thai` int(11) NOT NULL DEFAULT '0',
  `qty_foreigner` int(11) NOT NULL DEFAULT '0',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `product_type_id` int(11) DEFAULT NULL,
  `model_id` int(11) DEFAULT NULL,
  `pax_lines_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_type` (`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tour booking line items with optional contract rate links';

CREATE TABLE IF NOT EXISTS `tour_booking_pax` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `pax_type` enum('adult','child','infant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'adult',
  `is_thai` tinyint(1) NOT NULL DEFAULT '0',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual passenger details per booking';

CREATE TABLE IF NOT EXISTS `tour_booking_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `contact_messengers` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_id` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tour_booking_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `gateway` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THB',
  `reference_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `status` enum('pending','pending_review','completed','rejected','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_type` enum('deposit','partial','full','refund') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `slip_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `reject_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_gateway` (`gateway`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual payment records for tour bookings';


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART C — Contracts (final V2 shape — no v1 columns or pivot data)        ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `agent_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT 'Tenant FK',
  `agent_company_id` int(11) DEFAULT NULL COMMENT 'FK company.id — NULL for v2 operator-level contracts',
  `contract_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contract_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','active','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `payment_terms` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_days` int(11) NOT NULL DEFAULT '0',
  `deposit_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_operator_level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = v2 operator-level contract',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contract_number` (`company_id`,`contract_number`),
  KEY `idx_agent` (`company_id`,`agent_company_id`),
  KEY `idx_status` (`status`),
  KEY `fk_ac_agent` (`agent_company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_contract_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contract_type` (`contract_id`,`type_id`),
  KEY `idx_company_type` (`company_id`,`type_id`),
  KEY `fk_act_type` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contract_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `agent_company_id` int(11) NOT NULL COMMENT '0 = applies to all agents (v2 operator-level)',
  `model_id` int(11) DEFAULT NULL,
  `rate_type` enum('net_rate','percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'net_rate',
  `season_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL = base/default rate',
  `season_start` date DEFAULT NULL,
  `season_end` date DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `adult_default` decimal(12,2) NOT NULL DEFAULT '0.00',
  `child_default` decimal(12,2) NOT NULL DEFAULT '0.00',
  `adult_thai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `adult_foreigner` decimal(12,2) NOT NULL DEFAULT '0.00',
  `child_thai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `child_foreigner` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_adult_default` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_child_default` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_adult_thai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_adult_foreigner` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_child_thai` decimal(12,2) NOT NULL DEFAULT '0.00',
  `entrance_child_foreigner` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THB',
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cr_contract_model_season` (`contract_id`,`model_id`,`season_name`),
  KEY `idx_cr_company` (`company_id`),
  KEY `idx_cr_customer` (`agent_company_id`),
  KEY `idx_cr_model` (`model_id`),
  KEY `idx_cr_validity` (`valid_from`,`valid_to`),
  KEY `idx_cr_deleted` (`deleted_at`),
  KEY `idx_cr_contract_model` (`contract_id`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Contract rates with optional season periods (V2)';

-- If contract_rate already existed from an older install, ensure season columns
-- are present (idempotent guards)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                      AND COLUMN_NAME = 'season_name');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `contract_rate`
        ADD COLUMN `season_name` VARCHAR(100) DEFAULT NULL AFTER `rate_type`,
        ADD COLUMN `season_start` DATE DEFAULT NULL AFTER `season_name`,
        ADD COLUMN `season_end` DATE DEFAULT NULL AFTER `season_start`,
        ADD COLUMN `priority` INT(11) NOT NULL DEFAULT 0 AFTER `season_end`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- agent_contracts: ensure is_operator_level column exists (idempotent)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_contracts'
                      AND COLUMN_NAME = 'is_operator_level');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `agent_contracts`
        MODIFY COLUMN `agent_company_id` INT(11) DEFAULT NULL,
        ADD COLUMN `is_operator_level` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_default`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop fk_cr_agent if it exists (v2 needs agent_company_id=0 sentinel)
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                     AND CONSTRAINT_NAME = 'fk_cr_agent');
SET @sql := IF(@fk_exists > 0, 'ALTER TABLE contract_rate DROP FOREIGN KEY fk_cr_agent', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART D — Allotments + Fleets                                             ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `tour_fleets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `fleet_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fleet_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'speedboat',
  `capacity` int(11) NOT NULL DEFAULT '38',
  `unit_count` int(11) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_active` (`company_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fleet/vehicle definitions with seat capacity';

CREATE TABLE IF NOT EXISTS `tour_allotments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `fleet_id` int(11) NOT NULL,
  `model_id` int(11) DEFAULT NULL COMMENT 'FK model.id — NULL = legacy fleet-wide',
  `travel_date` date NOT NULL,
  `total_seats` int(11) NOT NULL DEFAULT '38',
  `booked_seats` int(11) NOT NULL DEFAULT '0',
  `manual_override` tinyint(1) NOT NULL DEFAULT '0',
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `closed_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_company_fleet_model_date` (`company_id`,`fleet_id`,`model_id`,`travel_date`),
  KEY `idx_travel_date` (`travel_date`),
  KEY `idx_company_date` (`company_id`,`travel_date`),
  KEY `idx_fleet` (`fleet_id`),
  KEY `idx_model_date` (`model_id`,`travel_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Daily seat allotment per fleet+product, auto-deducted on booking confirmation';

-- If tour_allotments already existed without model_id (legacy install), upgrade
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_allotments'
                      AND COLUMN_NAME = 'model_id');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `tour_allotments` ADD COLUMN `model_id` INT(11) DEFAULT NULL AFTER `fleet_id`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Swap unique key to include model_id (idempotent)
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_allotments'
                      AND INDEX_NAME = 'idx_company_fleet_date');
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `tour_allotments` DROP INDEX `idx_company_fleet_date`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_allotments'
                      AND INDEX_NAME = 'idx_company_fleet_model_date');
SET @sql := IF(@idx_exists = 0,
    'ALTER TABLE `tour_allotments` ADD UNIQUE KEY `idx_company_fleet_model_date` (`company_id`,`fleet_id`,`model_id`,`travel_date`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `tour_allotment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allotment_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `action` enum('book','release','manual_set','close','reopen','recalculate') COLLATE utf8mb4_unicode_ci NOT NULL,
  `seats_delta` int(11) NOT NULL DEFAULT '0',
  `booked_seats_after` int(11) NOT NULL DEFAULT '0',
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_allotment` (`allotment_id`),
  KEY `idx_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for allotment changes';

CREATE TABLE IF NOT EXISTS `tour_allotment_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `model_id` int(11) NOT NULL,
  `fleet_id` int(11) DEFAULT NULL,
  `default_capacity` int(11) NOT NULL DEFAULT '38',
  `schedule_type` enum('daily','monthly','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `schedule_days` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_on_dashboard` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_company_model` (`company_id`,`model_id`),
  KEY `idx_active` (`company_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product allotment configuration';


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART E — V2 Operator/Agent + Sync + Documents                            ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `tour_operator_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_company_id` int(11) NOT NULL,
  `agent_company_id` int(11) NOT NULL,
  `status` enum('pending','approved','suspended','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `registered_via` enum('self','invitation','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `invitation_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invitation_expires` datetime DEFAULT NULL,
  `default_contract_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_operator_agent` (`operator_company_id`,`agent_company_id`),
  KEY `idx_status` (`operator_company_id`,`status`),
  KEY `idx_invitation` (`invitation_token`),
  KEY `idx_agent` (`agent_company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agent registration and approval for tour operators';

CREATE TABLE IF NOT EXISTS `tour_contract_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract_id` int(11) NOT NULL,
  `agent_company_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contract_agent` (`contract_id`,`agent_company_id`),
  KEY `idx_agent_company` (`agent_company_id`,`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Many-to-many: which agents are assigned to which contracts';

CREATE TABLE IF NOT EXISTS `tour_operator_agent_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_company_id` int(11) NOT NULL,
  `agent_company_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `model_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `synced_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_agent_contract_model` (`agent_company_id`,`contract_id`,`model_id`),
  KEY `idx_operator` (`operator_company_id`),
  KEY `idx_agent_active` (`agent_company_id`,`is_active`),
  KEY `idx_contract` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Synced product catalog (operator → agent)';

CREATE TABLE IF NOT EXISTS `tour_contract_sync_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `agent_company_id` int(11) DEFAULT NULL,
  `action` enum('sync','resync','product_added','product_removed','rate_updated','contract_assigned','contract_unassigned') COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `triggered_by` enum('auto','operator','agent','api','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `products_added` int(11) NOT NULL DEFAULT '0',
  `products_removed` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_contract` (`company_id`,`contract_id`),
  KEY `idx_agent` (`agent_company_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for contract sync operations';

CREATE TABLE IF NOT EXISTS `tour_operator_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_company_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT '0',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('contract','brochure','terms','rate_sheet','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `visibility` enum('all_agents','contract','operator_only') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all_agents',
  `download_count` int(11) NOT NULL DEFAULT '0',
  `uploaded_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_operator` (`operator_company_id`,`deleted_at`),
  KEY `idx_contract` (`contract_id`),
  KEY `idx_visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documents shared by tour operators with their agents';


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART F — Payment system (used by booking payments)                       ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `payment_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_th` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-money',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_gateway` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_company` (`code`,`company_id`),
  KEY `idx_pmethod_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_gateway_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `payment_method_id` int(11) NOT NULL,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_unicode_ci,
  `is_encrypted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_method_key` (`payment_method_id`,`config_key`),
  KEY `idx_pgc_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART G — Guarded ALTERs to existing core tables (model, type, etc.)      ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

-- model.is_active (used by tour rate filtering)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'model' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `model` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1, ADD INDEX `idx_model_active` (`is_active`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- type.is_active
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'type' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `type` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1, ADD INDEX `idx_type_active` (`is_active`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- category.is_active
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `category` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1, ADD INDEX `idx_category_active` (`is_active`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- brand.is_active
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brand' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `brand` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1, ADD INDEX `idx_brand_active` (`is_active`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART H — V1→V2 promotion (no-op on fresh, helpful on upgrade)            ║
-- ╚══════════════════════════════════════════════════════════════════════════╝
-- If any v1 contracts exist (with junction rows), flip them to operator-level.
-- On a true fresh install this UPDATEs zero rows.

UPDATE `agent_contracts` ac
INNER JOIN (
    SELECT DISTINCT contract_id FROM `tour_contract_agents`
) ca ON ac.id = ca.contract_id
SET ac.is_operator_level = 1
WHERE ac.is_operator_level = 0
  AND ac.deleted_at IS NULL;


SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;


-- ============================================================================
-- POST-INSTALL CHECKLIST
-- ============================================================================
-- 1. Add cron token to inc/sys.configs.php (or the deploy workflow handles it):
--      $config['cron_token'] = '...your-secret...';
--
-- 2. If you have any v2 operator-level contracts already, sync products:
--      curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--
-- 3. Verify install:
--      SHOW TABLES LIKE 'tour_%';
--      -- expect 16 rows
--      SHOW COLUMNS FROM tour_bookings LIKE 'status';
--      -- expect: enum('draft','confirmed','paid','completed','no_show','cancelled')
--      SHOW COLUMNS FROM contract_rate LIKE 'season_name';
--      -- expect: 1 row (varchar 100)
--
-- 4. Login as super admin → Admin → Tour Operator Platform → confirm load
-- ============================================================================
