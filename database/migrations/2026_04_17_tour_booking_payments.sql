-- ============================================================
-- Migration: Tour Booking Payments
-- Add payment tracking to tour_bookings + create payment records table
-- Date: 2026-04-17
-- ============================================================

-- 1. Add payment tracking columns to tour_bookings
ALTER TABLE `tour_bookings`
  ADD COLUMN `payment_status` ENUM('unpaid','deposit','partial','paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER `status`,
  ADD COLUMN `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`,
  ADD COLUMN `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_paid`,
  ADD COLUMN `deposit_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_due`;

-- Backfill: amount_due = total_amount for existing bookings
UPDATE `tour_bookings` SET `amount_due` = `total_amount` WHERE `deleted_at` IS NULL;

-- 2. Create tour_booking_payments table
CREATE TABLE IF NOT EXISTS `tour_booking_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL COMMENT 'FK tour_bookings.id',
    `company_id` INT(11) NOT NULL COMMENT 'Tenant FK',
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash' COMMENT 'cash, bank_transfer, credit_card, promptpay, stripe, cheque',
    `gateway` VARCHAR(30) DEFAULT NULL COMMENT 'stripe, promptpay, paypal — NULL for manual',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'THB',
    `reference_id` VARCHAR(255) DEFAULT NULL COMMENT 'Bank ref, Stripe payment_intent, etc.',
    `payment_date` DATE NOT NULL,
    `status` ENUM('pending','pending_review','completed','rejected','refunded') NOT NULL DEFAULT 'pending',
    `payment_type` ENUM('deposit','partial','full','refund') NOT NULL DEFAULT 'full' COMMENT 'Type of this payment',
    `slip_image` VARCHAR(500) DEFAULT NULL COMMENT 'Upload path for bank slip / PromptPay proof',
    `notes` TEXT DEFAULT NULL,
    `reject_reason` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL COMMENT 'FK authorize.usr_id',
    `approved_by` INT(11) DEFAULT NULL COMMENT 'FK authorize.usr_id — for slip review',
    `approved_at` DATETIME DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_company` (`company_id`),
    KEY `idx_status` (`status`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_gateway` (`gateway`),
    CONSTRAINT `fk_tbp_booking` FOREIGN KEY (`booking_id`) REFERENCES `tour_bookings` (`id`),
    CONSTRAINT `fk_tbp_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual payment records for tour bookings (manual + gateway)';

-- 3. Index for payment_status on tour_bookings
ALTER TABLE `tour_bookings` ADD KEY `idx_payment_status` (`payment_status`);
