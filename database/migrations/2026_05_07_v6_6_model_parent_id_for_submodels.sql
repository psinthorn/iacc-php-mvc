-- Migration: 2026_05_07_v6_6_model_parent_id_for_submodels.sql
-- v6.6 — sub-model relationship via parent_model_id
--
-- Lets a model be expressed as a child of another model (e.g. an entrance
-- fee under a tour). The carousel and customer-facing flows continue to
-- show only top-level rows (parent_model_id IS NULL); the LINE booking
-- write path auto-seeds child models as additional line items so admins
-- don't have to add entrance fees / extras manually for every booking.
--
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)
-- Idempotent via stored-procedure column-existence check.

DROP PROCEDURE IF EXISTS _migrate_v66_model_parent;
DELIMITER $$
CREATE PROCEDURE _migrate_v66_model_parent()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'model'
          AND COLUMN_NAME  = 'parent_model_id'
    ) THEN
        ALTER TABLE `model`
            ADD COLUMN `parent_model_id` INT(11) NULL DEFAULT NULL
            COMMENT 'v6.6 — when set, this model is a sub-item of the parent (e.g. entrance fee under a tour). Auto-seeded as a line item when parent is booked. Top-level models have NULL.';
    END IF;

    -- Composite index on (parent_model_id, is_active) supports both
    --   (a) "find children of tour X for auto-seed" (parent_model_id = X)
    --   (b) "list top-level tours" (parent_model_id IS NULL)
    -- without a separate index for each.
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'model'
          AND INDEX_NAME   = 'idx_model_parent_active'
    ) THEN
        CREATE INDEX `idx_model_parent_active`
            ON `model` (`parent_model_id`, `is_active`);
    END IF;
END$$
DELIMITER ;
CALL _migrate_v66_model_parent();
DROP PROCEDURE IF EXISTS _migrate_v66_model_parent;
