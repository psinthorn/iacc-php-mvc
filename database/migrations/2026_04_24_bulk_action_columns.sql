-- =============================================================================
-- Migration: Add bulk-action tracking columns to tour_bookings
-- Date: 2026-04-24
-- MySQL 5.7 compatible — safe to run multiple times (checks before adding)
-- Run via: phpMyAdmin → SQL tab → paste and execute
-- =============================================================================

DROP PROCEDURE IF EXISTS _migrate_bulk_action_columns;

DELIMITER $$
CREATE PROCEDURE _migrate_bulk_action_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tour_bookings'
          AND COLUMN_NAME  = 'voucher_sent_at'
    ) THEN
        ALTER TABLE `tour_bookings`
            ADD COLUMN `voucher_sent_at` DATETIME NULL DEFAULT NULL AFTER `status`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tour_bookings'
          AND COLUMN_NAME  = 'invoice_sent_at'
    ) THEN
        ALTER TABLE `tour_bookings`
            ADD COLUMN `invoice_sent_at` DATETIME NULL DEFAULT NULL AFTER `voucher_sent_at`;
    END IF;
END$$
DELIMITER ;

CALL _migrate_bulk_action_columns();
DROP PROCEDURE IF EXISTS _migrate_bulk_action_columns;
