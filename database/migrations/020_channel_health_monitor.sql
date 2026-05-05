-- =========================================================================
-- Migration 020: Channel Health Monitor (v6.2 / issue #83)
-- =========================================================================
-- Tables for the v6.2 Channel Health Monitor — periodic heartbeat against
-- LINE OA, Sales Channel API, outbound webhook system, and email SMTP, plus
-- alert state machine for downtime > 5 consecutive failures.
--
-- Idempotent — safe to run twice. CREATE TABLE IF NOT EXISTS is enough since
-- this migration only adds new tables; no ALTERs on existing tables.
--
-- Date: 2026-05-05
-- Run (local):     docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/020_channel_health_monitor.sql
-- Run (cPanel):    phpMyAdmin → Import → 020_channel_health_monitor.sql
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────
-- 1. channel_health_log — One row per heartbeat tick per channel
-- ─────────────────────────────────────────────────────────────────────────
-- Volume estimate: 213 companies × ~4 channels × 12 ticks/hr × 24h ≈ 245k rows/day.
-- 30-day retention (cleaned up by daily cron worker — see v6.3 #92) ≈ 7.4M rows.
-- BIGINT UNSIGNED PK to match task_queue pattern for high-volume queue/log tables.
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `channel_health_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT          COMMENT 'High-volume log; BIGINT to avoid future overflow',
    `company_id`    INT(11) NOT NULL                                  COMMENT 'FK company.id — multi-tenant scope',
    `channel_type`  ENUM('line_oa','sales_channel_api','outbound_webhook','email_smtp')
                    NOT NULL                                          COMMENT 'Discriminator for which subsystem was checked',
    `channel_ref`   VARCHAR(255) DEFAULT NULL                         COMMENT 'Channel-specific identifier (webhook URL, LINE channel id, etc.)',
    `status`        ENUM('success','failure','not_configured')
                    NOT NULL                                          COMMENT 'Heartbeat outcome',
    `response_ms`   INT UNSIGNED DEFAULT NULL                         COMMENT 'Wall-clock latency of the check; NULL when not_configured',
    `error_message` VARCHAR(1024) DEFAULT NULL                        COMMENT 'Truncated error from the failed call (raw is not stored — too noisy)',
    `checked_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP      COMMENT 'When this heartbeat fired; primary timeline column',
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP      COMMENT 'Row insert time (= checked_at, kept for codebase convention)',
    PRIMARY KEY (`id`),
    KEY `idx_dashboard`     (`company_id`, `channel_type`, `checked_at`)  COMMENT 'Per-tenant dashboard: status grid + 24h chart',
    KEY `idx_recent_status` (`company_id`, `checked_at`)                  COMMENT 'Cross-channel "last 100" timeline',
    KEY `idx_retention`     (`checked_at`)                                COMMENT 'Daily retention cleanup (delete WHERE checked_at < NOW() - INTERVAL 30 DAY)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-heartbeat log for v6.2 Channel Health Monitor (#83). High-volume; 30-day retention.';


-- ─────────────────────────────────────────────────────────────────────────
-- 2. channel_alerts — Alert state machine, opened on 5+ consecutive failures
-- ─────────────────────────────────────────────────────────────────────────
-- One row per (company, channel, downtime episode). Auto-resolves when channel
-- comes back up. Email is sent on transitions (open and resolved), not on
-- sustained downtime — alert_email_sent_count caps at 2 per row.
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `channel_alerts` (
    `id`                     INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`             INT(11) NOT NULL                              COMMENT 'FK company.id',
    `channel_type`           VARCHAR(50) NOT NULL                          COMMENT 'Same enum values as channel_health_log; VARCHAR to allow future channels without ALTER',
    `channel_ref`            VARCHAR(255) DEFAULT NULL                     COMMENT 'Optional channel-specific identifier (matches channel_health_log)',
    `status`                 ENUM('open','acknowledged','resolved')
                             NOT NULL DEFAULT 'open'                       COMMENT 'open=active downtime; acknowledged=admin saw it; resolved=channel back up',
    `opened_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP  COMMENT 'When the 5th consecutive failure occurred',
    `acknowledged_at`        TIMESTAMP NULL DEFAULT NULL                   COMMENT 'When admin clicked Acknowledge (suppresses repeat alerts)',
    `acknowledged_by`        INT(11) DEFAULT NULL                          COMMENT 'FK user.usr_id of acknowledger',
    `resolved_at`            TIMESTAMP NULL DEFAULT NULL                   COMMENT 'When the channel became healthy again',
    `last_error`             VARCHAR(1024) DEFAULT NULL                    COMMENT 'Most recent error message at time of alert (debugging aid)',
    `alert_email_sent_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0          COMMENT 'Increment on each email send; cap at 2 (open + resolved)',
    `created_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_open_alerts`    (`company_id`, `status`, `channel_type`)      COMMENT 'Dashboard "open alerts" panel + per-channel state lookup',
    KEY `idx_active_episode` (`company_id`, `channel_type`, `status`, `opened_at`)
                                                                          COMMENT 'Find the currently-open alert for (company, channel) on heartbeat eval'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alert state machine for v6.2 Channel Health Monitor (#83). One row per downtime episode.';


-- ─────────────────────────────────────────────────────────────────────────
-- 3. Log this migration in _migration_log (idempotent UPSERT)
-- ─────────────────────────────────────────────────────────────────────────
-- Uses ON DUPLICATE KEY to handle re-runs cleanly. Column names match the
-- production _migration_log schema verified on 2026-05-04 (executed_at, status).
-- ─────────────────────────────────────────────────────────────────────────
INSERT INTO `_migration_log` (`migration_name`, `status`, `notes`)
VALUES (
    '020_channel_health_monitor',
    'success',
    'v6.2 #83: created channel_health_log + channel_alerts tables. No ALTERs.'
)
ON DUPLICATE KEY UPDATE
    `executed_at` = CURRENT_TIMESTAMP,
    `status`      = 'success',
    `notes`       = VALUES(`notes`);

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- Verification queries (run manually after import to confirm)
-- =========================================================================
-- SELECT
--   (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='channel_health_log') AS log_table_exists,
--   (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='channel_alerts')     AS alerts_table_exists,
--   (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='channel_health_log') AS log_indexes,
--   (SELECT migration_name FROM _migration_log WHERE migration_name='020_channel_health_monitor' ORDER BY executed_at DESC LIMIT 1) AS logged;
-- Expected: log_table_exists=1, alerts_table_exists=1, log_indexes>=4, logged='020_channel_health_monitor'

-- =========================================================================
-- ROLLBACK (manual — only run if you know what you're doing)
-- =========================================================================
-- DROP TABLE IF EXISTS `channel_alerts`;
-- DROP TABLE IF EXISTS `channel_health_log`;
-- DELETE FROM `_migration_log` WHERE `migration_name`='020_channel_health_monitor';
