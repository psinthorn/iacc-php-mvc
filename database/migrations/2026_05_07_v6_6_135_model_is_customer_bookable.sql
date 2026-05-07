-- Migration: 2026_05_07_v6_6_135_model_is_customer_bookable.sql
-- v6.6 #135 follow-up — add is_customer_bookable flag to model
--
-- Carousel discovery (#135) shows every active model row, including
-- non-tour entries like entrance fees that operators don't want
-- customers booking directly. This flag lets the operator hide
-- specific models from the LINE OA catalog while keeping them
-- otherwise active for internal pricing/reporting.
--
-- Default = 1 so existing tours stay visible; admin flips specific
-- rows to 0 for entrance fees and other internal-only models.
--
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)
-- Idempotent via stored-procedure column-existence check

DROP PROCEDURE IF EXISTS _migrate_v66_135_bookable;
DELIMITER $$
CREATE PROCEDURE _migrate_v66_135_bookable()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'model'
          AND COLUMN_NAME  = 'is_customer_bookable'
    ) THEN
        ALTER TABLE `model`
            ADD COLUMN `is_customer_bookable` TINYINT(1) NOT NULL DEFAULT 1
            COMMENT 'v6.6 #135 — show this model in LINE OA customer-facing catalog (1=yes, 0=hidden, e.g. entrance fees)';
    END IF;

    -- Index to keep the LineTourCatalog query fast even on tenants with
    -- many models. Composite (company_id, is_active, is_customer_bookable)
    -- matches the WHERE clause exactly.
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'model'
          AND INDEX_NAME   = 'idx_model_company_active_bookable'
    ) THEN
        CREATE INDEX `idx_model_company_active_bookable`
            ON `model` (`company_id`, `is_active`, `is_customer_bookable`);
    END IF;
END$$
DELIMITER ;
CALL _migrate_v66_135_bookable();
DROP PROCEDURE IF EXISTS _migrate_v66_135_bookable;
