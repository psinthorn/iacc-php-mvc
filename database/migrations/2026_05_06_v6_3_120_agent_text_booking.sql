-- Migration: 2026_05_06_v6_3_120_agent_text_booking.sql
-- v6.3 #120 — Agent text-template booking via LINE OA
-- Adds: tour_bookings.created_via (source attribution), line_users binding-audit columns
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)
-- Idempotent via stored-procedure column-existence checks

DROP PROCEDURE IF EXISTS _migrate_v63_120;
DELIMITER $$
CREATE PROCEDURE _migrate_v63_120()
BEGIN
    -- 1. tour_bookings.created_via — tracks where the booking originated
    --    (web_form, line_oa_agent_text, line_oa_agent_file, line_oa_agent_image, csv_import, ...)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tour_bookings'
          AND COLUMN_NAME  = 'created_via'
    ) THEN
        ALTER TABLE `tour_bookings`
            ADD COLUMN `created_via` VARCHAR(50) DEFAULT NULL
            COMMENT 'Source channel: web_form, line_oa_agent_text, line_oa_agent_file, line_oa_agent_image, csv_import';
    END IF;

    -- 2. tour_bookings index on created_via for source-channel reporting
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tour_bookings'
          AND INDEX_NAME   = 'idx_tb_created_via'
    ) THEN
        CREATE INDEX `idx_tb_created_via` ON `tour_bookings` (`created_via`);
    END IF;

    -- 3. line_users.linked_at — when the binding was created (audit)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_users'
          AND COLUMN_NAME  = 'linked_at'
    ) THEN
        ALTER TABLE `line_users`
            ADD COLUMN `linked_at` DATETIME DEFAULT NULL
            COMMENT 'When linked_user_id was bound by an admin';
    END IF;

    -- 4. line_users.linked_by — which admin did the binding (audit)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_users'
          AND COLUMN_NAME  = 'linked_by'
    ) THEN
        ALTER TABLE `line_users`
            ADD COLUMN `linked_by` INT DEFAULT NULL
            COMMENT 'FK to authorize.id of the admin who created the binding';
    END IF;

    -- 5. line_users index on linked_user_id for fast bound-agent lookup
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'line_users'
          AND INDEX_NAME   = 'idx_lu_linked_user'
    ) THEN
        CREATE INDEX `idx_lu_linked_user` ON `line_users` (`linked_user_id`);
    END IF;
END$$
DELIMITER ;
CALL _migrate_v63_120();
DROP PROCEDURE IF EXISTS _migrate_v63_120;
