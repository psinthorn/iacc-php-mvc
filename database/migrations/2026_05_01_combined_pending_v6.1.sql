-- =============================================================================
-- Migration:    2026_05_01_combined_pending_v6.1.sql
-- Type:         COMBINED — supersedes the two individual files below for deploy
-- Supersedes:
--   - database/migrations/2026_04_27_product_allotment_configs.sql
--   - database/migrations/2026_05_01_task_queue.sql
-- Purpose:      One-shot deploy of all migrations on develop that haven't yet
--               reached staging or production. Designed to be re-runnable —
--               every step is wrapped in INFORMATION_SCHEMA presence checks
--               so partial application is recoverable.
-- Compatibility: MySQL 5.7+ (cPanel)
-- Author:       PM (combined per release request 2026-05-01)
-- =============================================================================
--
-- HOW TO APPLY (cPanel phpMyAdmin):
--   1. phpMyAdmin → select the target database → Import tab
--   2. Upload this file → Go
--   3. Run the verification queries at the bottom of this file
--
-- HOW TO APPLY (Docker local):
--   docker exec -i iacc_mysql mysql -uroot -proot iacc \
--     < database/migrations/2026_05_01_combined_pending_v6.1.sql
--
-- HOW TO APPLY (cPanel SSH if available):
--   mysql -u <user> -p <database> < 2026_05_01_combined_pending_v6.1.sql
--
-- IDEMPOTENCY:
--   Every CREATE / ALTER below is guarded. Running this file twice in a row
--   will succeed both times and leave the schema in the same final state.
--   The only thing that will appear changed is the _migration_log audit row.
--
-- ROLLBACK:
--   See the commented `-- ROLLBACK` block at the very bottom.
-- =============================================================================


-- =============================================================================
-- PART 1 — From: 2026_04_27_product_allotment_configs.sql
-- Adds product-aware allotment tracking (per-product opt-in via config table).
-- =============================================================================

-- 1.1 — Create tour_allotment_configs (idempotent via IF NOT EXISTS)
CREATE TABLE IF NOT EXISTS `tour_allotment_configs` (
    `id`                 INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`         INT(11) NOT NULL,
    `model_id`           INT(11) NOT NULL                COMMENT 'FK model.id — the product to track',
    `fleet_id`           INT(11) DEFAULT NULL            COMMENT 'FK tour_fleets.id — assigned fleet (NULL = use default)',
    `default_capacity`   INT(11) NOT NULL DEFAULT 38     COMMENT 'Seats per date for this product',
    `schedule_type`      ENUM('daily','monthly','custom') NOT NULL DEFAULT 'daily',
    `schedule_days`      VARCHAR(100) DEFAULT NULL       COMMENT 'Comma-separated day-of-month for monthly (e.g. 14,15)',
    `show_on_dashboard`  TINYINT(1) NOT NULL DEFAULT 1,
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
    `notes`              TEXT DEFAULT NULL,
    `deleted_at`         DATETIME DEFAULT NULL,
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company_model` (`company_id`, `model_id`),
    KEY `idx_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Product allotment configuration — which products are tracked';


-- 1.2 — Add model_id column to tour_allotments (idempotent)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_allotments` ADD COLUMN `model_id` INT(11) DEFAULT NULL COMMENT ''FK model.id — NULL = legacy fleet-wide'' AFTER `fleet_id`',
    'SELECT ''skip: tour_allotments.model_id already exists'' AS note'
) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tour_allotments'
      AND COLUMN_NAME  = 'model_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 1.3 — Drop old unique key (idempotent: only if exists)
SET @sql = (SELECT IF(
    COUNT(*) > 0,
    'ALTER TABLE `tour_allotments` DROP INDEX `idx_company_fleet_date`',
    'SELECT ''skip: idx_company_fleet_date already dropped'' AS note'
) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tour_allotments'
      AND INDEX_NAME   = 'idx_company_fleet_date');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 1.4 — Add new unique key including model_id (idempotent)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_allotments` ADD UNIQUE KEY `idx_company_fleet_model_date` (`company_id`, `fleet_id`, `model_id`, `travel_date`)',
    'SELECT ''skip: idx_company_fleet_model_date already exists'' AS note'
) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tour_allotments'
      AND INDEX_NAME   = 'idx_company_fleet_model_date');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 1.5 — Add lookup index for per-model queries (idempotent)
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `tour_allotments` ADD KEY `idx_model_date` (`model_id`, `travel_date`)',
    'SELECT ''skip: idx_model_date already exists'' AS note'
) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tour_allotments'
      AND INDEX_NAME   = 'idx_model_date');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- =============================================================================
-- PART 2 — From: 2026_05_01_task_queue.sql
-- Creates background job queue + per-attempt result history (v6.1 sprint #75).
-- All-new tables — already idempotent via IF NOT EXISTS.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `task_queue` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT             COMMENT 'Surrogate PK; queue can grow large',
    `company_id`     INT(11) UNSIGNED NOT NULL                            COMMENT 'FK company.id — multi-tenant scope (NOT NULL by design)',
    `task_type`      VARCHAR(100) NOT NULL                                COMMENT 'Handler key, e.g. send_email, generate_pdf_invoice, sync_channel_inventory',
    `payload`        JSON NOT NULL                                        COMMENT 'Task input data (validated by MySQL JSON type)',
    `priority`       TINYINT UNSIGNED NOT NULL DEFAULT 5                  COMMENT '1=high, 5=normal (default), 10=low. Lower = picked up first',
    `status`         ENUM('pending','locked','running','done','failed','dead_letter')
                     NOT NULL DEFAULT 'pending'                           COMMENT 'State machine; dead_letter is terminal failure',
    `attempts`       SMALLINT UNSIGNED NOT NULL DEFAULT 0                 COMMENT 'Incremented each time worker claims this row',
    `max_attempts`   SMALLINT UNSIGNED NOT NULL DEFAULT 5                 COMMENT 'When attempts >= max_attempts AND last try failed → dead_letter',
    `locked_at`      DATETIME NULL DEFAULT NULL                           COMMENT 'Set when worker claims; cleared on done/failed; reaped if > 10 min stale',
    `locked_by`      VARCHAR(64) NULL DEFAULT NULL                        COMMENT 'Worker process identifier (debug only, e.g. cpanel-php-9824)',
    `scheduled_for`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP          COMMENT 'Earliest moment task may run; supports delayed execution',
    `last_error`     TEXT NULL DEFAULT NULL                               COMMENT 'Truncated error from most recent failure (4KB cap enforced in app)',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP          COMMENT 'Row creation time (UTC)',
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP                          COMMENT 'Last mutation time (UTC)',
    `deleted_at`     DATETIME NULL DEFAULT NULL                           COMMENT 'Soft delete; admin "clear" actions set this rather than DROP',
    PRIMARY KEY (`id`),
    KEY `idx_polling`        (`status`, `scheduled_for`, `priority`)      COMMENT 'Covering index for worker poll',
    KEY `idx_company_status` (`company_id`, `status`)                     COMMENT 'Admin dashboard filter: per-tenant + status',
    KEY `idx_type`           (`task_type`)                                COMMENT 'Analytics + per-type retry strategies',
    KEY `idx_locked`         (`locked_at`)                                COMMENT 'Stale-lock reaper'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Background job queue (#75) — fed by app, drained by worker.php (#76)';


CREATE TABLE IF NOT EXISTS `task_results` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT             COMMENT 'Surrogate PK',
    `task_id`         BIGINT UNSIGNED NOT NULL                             COMMENT 'FK task_queue.id ON DELETE CASCADE',
    `attempt_number`  SMALLINT UNSIGNED NOT NULL                           COMMENT '1-indexed; matches task_queue.attempts at the moment this row was written',
    `success`         TINYINT(1) NOT NULL                                  COMMENT '0=error, 1=success',
    `result_data`     JSON NULL DEFAULT NULL                               COMMENT 'Output payload on success',
    `error_message`   TEXT NULL DEFAULT NULL                               COMMENT 'Full error including stack on failure (64KB cap)',
    `duration_ms`     INT UNSIGNED NULL DEFAULT NULL                       COMMENT 'Wall-clock execution time in milliseconds',
    `completed_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP          COMMENT 'When this attempt finished (success or fail)',
    PRIMARY KEY (`id`),
    KEY `idx_task_attempt` (`task_id`, `attempt_number`)                   COMMENT 'Per-task history lookup (admin dashboard detail modal)',
    CONSTRAINT `fk_task_results_task_id`
        FOREIGN KEY (`task_id`) REFERENCES `task_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-attempt result history for task_queue (#75)';


-- =============================================================================
-- AUDIT — record this combined migration in _migration_log
-- =============================================================================
--
-- _migration_log is unmaintained (last entry was 2026-04-07), but we re-establish
-- the discipline starting here. Idempotent: ON DUPLICATE KEY UPDATE no-ops.

INSERT INTO `_migration_log` (`migration_name`, `executed_at`, `status`, `notes`)
VALUES (
    '2026_05_01_combined_pending_v6.1',
    NOW(),
    'success',
    'Combined: 2026_04_27_product_allotment_configs + 2026_05_01_task_queue'
)
ON DUPLICATE KEY UPDATE `executed_at` = `executed_at`;


-- =============================================================================
-- VERIFICATION (run these AFTER import — expect all four to return 1)
-- =============================================================================
--
-- SELECT
--   (SELECT COUNT(*) FROM information_schema.tables  WHERE table_schema=DATABASE() AND table_name='tour_allotment_configs')                              AS allotment_configs_table,
--   (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tour_allotments' AND column_name='model_id')         AS allotments_model_id_col,
--   (SELECT COUNT(*) FROM information_schema.tables  WHERE table_schema=DATABASE() AND table_name='task_queue')                                          AS task_queue_table,
--   (SELECT COUNT(*) FROM information_schema.tables  WHERE table_schema=DATABASE() AND table_name='task_results')                                        AS task_results_table;
-- -- Expect: 1, 1, 1, 1
--
-- -- Worker polling query plan (must hit idx_polling)
-- EXPLAIN
-- SELECT id FROM task_queue
--  WHERE status='pending' AND scheduled_for<=NOW() AND deleted_at IS NULL
--  ORDER BY priority ASC, scheduled_for ASC LIMIT 1;
--
-- -- Allotments key health
-- SHOW INDEX FROM tour_allotments;
-- -- Expect: PRIMARY + idx_company_fleet_model_date (UNIQUE) + idx_travel_date + idx_company_date + idx_fleet + idx_model_date
-- -- (idx_company_fleet_date should NOT appear)


-- =============================================================================
-- ROLLBACK (emergency only — destructive, takes data with it)
-- =============================================================================
--
-- WARNING: This drops both v6.1 tables and reverses the allotment changes.
-- Always back up before running.
--
--   SET FOREIGN_KEY_CHECKS = 0;
--
--   -- v6.1 task queue
--   DROP TABLE IF EXISTS `task_results`;
--   DROP TABLE IF EXISTS `task_queue`;
--
--   -- product allotment configs
--   DROP TABLE IF EXISTS `tour_allotment_configs`;
--   ALTER TABLE `tour_allotments`
--       DROP INDEX `idx_company_fleet_model_date`,
--       DROP INDEX `idx_model_date`,
--       ADD UNIQUE KEY `idx_company_fleet_date` (`company_id`, `fleet_id`, `travel_date`),
--       DROP COLUMN `model_id`;
--
--   SET FOREIGN_KEY_CHECKS = 1;
--
-- =============================================================================
