-- =============================================================================
-- Migration:    2026_05_01_task_queue.sql
-- Issue:        #75 — task_queue + task_results database schema (v6.1 sprint)
-- Description:  Persistent, multi-tenant-safe job queue for the iACC background
--               worker (#76). Supports priority lanes, retry with backoff,
--               dead-letter handling, and admin observability (#77, #78).
-- Tables:
--   - task_queue    — pending/locked/done/failed/dead_letter task records
--   - task_results  — per-attempt result history (FK ON DELETE CASCADE)
-- Compatibility: MySQL 5.7+ (cPanel production); idempotent (CREATE IF NOT EXISTS)
-- Dependencies:  None — both tables are NEW. No ALTER on existing tables.
-- Related PRs:   v6.1 milestone — #75, #76, #77, #78
-- Author:        DBA agent (per PM spec by Claude Code, 2026-05-01)
-- =============================================================================
--
-- HOW TO APPLY (cPanel phpMyAdmin):
--   1. phpMyAdmin → select database → Import tab → upload this file → Go
--   2. Verify with the queries at the bottom of this file (commented)
--
-- HOW TO APPLY (Docker local):
--   docker exec -i iacc_mysql mysql -uroot -proot iacc \
--     < database/migrations/2026_05_01_task_queue.sql
--
-- ROLLBACK: see commented `-- ROLLBACK` block at end of this file.
--
-- =============================================================================
-- DESIGN NOTES
--
-- Type choices (per agent-dba.md):
--   - DATETIME (not TIMESTAMP) for cPanel timezone safety and 2038 immunity
--   - INT(11) UNSIGNED for company_id — matches existing iACC convention
--   - BIGINT UNSIGNED for task_queue.id — queue can churn millions of rows
--   - JSON (not LONGTEXT) for payload/result_data — MySQL 5.7+ native, validates
--   - ENUM for status — locks the state machine at schema level
--
-- Index strategy:
--   - idx_polling (status, scheduled_for, priority)  — covers worker SELECT
--   - idx_company_status (company_id, status)        — admin dashboard filter
--   - idx_type (task_type)                           — analytics / per-type retry
--   - idx_locked (locked_at)                         — stale-lock reaper query
--
-- Multi-tenant isolation:
--   - company_id NOT NULL on task_queue
--   - All app-layer SELECTs MUST filter by company_id (worker uses it for context;
--     admin dashboard #78 enforces it for per-company admins)
--
-- Soft delete:
--   - task_queue.deleted_at — admin "clear" actions set this rather than hard DELETE
--   - task_results has NO deleted_at — it's a history/audit trail; rotated by the
--     v6.3 #92 cleanup-worker
-- =============================================================================


-- ─────────────────────────────────────────────────────────────────────────────
-- Table: task_queue
-- ─────────────────────────────────────────────────────────────────────────────

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
    KEY `idx_polling`        (`status`, `scheduled_for`, `priority`)      COMMENT 'Covering index for worker poll: WHERE status=pending AND scheduled_for<=NOW() ORDER BY priority,scheduled_for',
    KEY `idx_company_status` (`company_id`, `status`)                     COMMENT 'Admin dashboard filter: per-tenant + status',
    KEY `idx_type`           (`task_type`)                                COMMENT 'Analytics + per-type retry strategies',
    KEY `idx_locked`         (`locked_at`)                                COMMENT 'Stale-lock reaper: WHERE status=locked AND locked_at<NOW()-INTERVAL 10 MINUTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Background job queue (#75) — fed by app, drained by worker.php (#76)';


-- ─────────────────────────────────────────────────────────────────────────────
-- Table: task_results
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `task_results` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT             COMMENT 'Surrogate PK',
    `task_id`         BIGINT UNSIGNED NOT NULL                             COMMENT 'FK task_queue.id ON DELETE CASCADE',
    `attempt_number`  SMALLINT UNSIGNED NOT NULL                           COMMENT '1-indexed; matches task_queue.attempts at the moment this row was written',
    `success`         TINYINT(1) NOT NULL                                  COMMENT '0=error, 1=success',
    `result_data`     JSON NULL DEFAULT NULL                               COMMENT 'Output payload on success (e.g. PDF path, email message-id)',
    `error_message`   TEXT NULL DEFAULT NULL                               COMMENT 'Full error including stack on failure (64KB cap)',
    `duration_ms`     INT UNSIGNED NULL DEFAULT NULL                       COMMENT 'Wall-clock execution time in milliseconds',
    `completed_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP          COMMENT 'When this attempt finished (success or fail)',
    PRIMARY KEY (`id`),
    KEY `idx_task_attempt` (`task_id`, `attempt_number`)                   COMMENT 'Per-task history lookup (admin dashboard detail modal)',
    CONSTRAINT `fk_task_results_task_id`
        FOREIGN KEY (`task_id`) REFERENCES `task_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-attempt result history for task_queue (#75) — pruned by v6.3 #92';


-- =============================================================================
-- VERIFICATION (uncomment + run after import)
-- =============================================================================
--
-- -- 1. Tables exist
-- SHOW TABLES LIKE 'task_queue';      -- expect 1 row
-- SHOW TABLES LIKE 'task_results';    -- expect 1 row
--
-- -- 2. Column counts (sanity check, expect 15 + 8)
-- SELECT COUNT(*) AS cols FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'task_queue';   -- expect 15
-- SELECT COUNT(*) AS cols FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'task_results'; -- expect 8
--
-- -- 3. Indexes present
-- SHOW INDEX FROM `task_queue`;       -- expect PRIMARY + 4 secondary indexes
-- SHOW INDEX FROM `task_results`;     -- expect PRIMARY + idx_task_attempt + FK index
--
-- -- 4. FK enforced
-- SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
--   FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'task_results'
--     AND REFERENCED_TABLE_NAME = 'task_queue';  -- expect 1 row
--
-- -- 5. Worker polling query uses idx_polling (the most important AC)
-- EXPLAIN
-- SELECT id, company_id, task_type, payload, attempts, max_attempts
--   FROM task_queue
--   WHERE status = 'pending'
--     AND scheduled_for <= NOW()
--     AND deleted_at IS NULL
--   ORDER BY priority ASC, scheduled_for ASC
--   LIMIT 1;
-- -- Expect: key='idx_polling' OR key='idx_polling,...' in the plan
--
-- -- 6. Cascade delete works
-- INSERT INTO task_queue (company_id, task_type, payload)
--   VALUES (1, 'echo', JSON_OBJECT('msg','test'));
-- SET @t := LAST_INSERT_ID();
-- INSERT INTO task_results (task_id, attempt_number, success, completed_at)
--   VALUES (@t, 1, 1, NOW());
-- DELETE FROM task_queue WHERE id = @t;
-- SELECT COUNT(*) FROM task_results WHERE task_id = @t;  -- expect 0


-- =============================================================================
-- SEED — DEV ONLY (commented out by default, uncomment for local testing)
-- =============================================================================
--
-- INSERT INTO task_queue (company_id, task_type, payload, priority, scheduled_for) VALUES
--   (1, 'echo',                 JSON_OBJECT('msg','hello world'),                  5, NOW()),
--   (1, 'send_email',           JSON_OBJECT('to','test@example.com','subject','x'), 3, NOW()),
--   (1, 'generate_pdf_invoice', JSON_OBJECT('invoice_id',42),                        5, NOW() + INTERVAL 5 MINUTE),
--   (1, 'sync_channel',         JSON_OBJECT('channel','shopee'),                    7, NOW()),
--   (1, 'cleanup_old_files',    JSON_OBJECT('days',30),                            10, NOW() + INTERVAL 1 HOUR);


-- =============================================================================
-- ROLLBACK (emergency only — drops both tables + ALL data)
-- =============================================================================
--
-- WARNING: This is destructive. There is no recovery path other than backup
-- restore. Always back up before running.
--
--   SET FOREIGN_KEY_CHECKS = 0;
--   DROP TABLE IF EXISTS `task_results`;
--   DROP TABLE IF EXISTS `task_queue`;
--   SET FOREIGN_KEY_CHECKS = 1;
--
-- =============================================================================
