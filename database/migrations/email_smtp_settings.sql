-- =============================================================================
-- Migration:    email_smtp_settings.sql
-- Description:  Per-company SMTP configuration for outbound email delivery.
--               Stores host, port, auth credentials, from address/name.
-- Tables:       smtp_settings (CREATE TABLE IF NOT EXISTS)
-- Compatibility: MySQL 5.7+, idempotent
-- =============================================================================

CREATE TABLE IF NOT EXISTS `smtp_settings` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`  INT(11) NOT NULL                  COMMENT 'FK company.id — one row per tenant',
    `host`        VARCHAR(255) NOT NULL DEFAULT ''   COMMENT 'SMTP host e.g. smtp.gmail.com',
    `port`        SMALLINT(5) UNSIGNED NOT NULL DEFAULT 587 COMMENT '25/465/587',
    `encryption`  ENUM('none','ssl','tls') NOT NULL DEFAULT 'tls' COMMENT 'Connection security',
    `username`    VARCHAR(255) NOT NULL DEFAULT ''   COMMENT 'SMTP auth username / email',
    `password`    VARCHAR(500) NOT NULL DEFAULT ''   COMMENT 'SMTP auth password (stored encrypted)',
    `from_email`  VARCHAR(255) NOT NULL DEFAULT ''   COMMENT 'Envelope From address',
    `from_name`   VARCHAR(255) NOT NULL DEFAULT ''   COMMENT 'Display name in From header',
    `is_enabled`  TINYINT(1) NOT NULL DEFAULT 1      COMMENT '0=disabled, 1=enabled',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-company SMTP configuration for outbound email';
