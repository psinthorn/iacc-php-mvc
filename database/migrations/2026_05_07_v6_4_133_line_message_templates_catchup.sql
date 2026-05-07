-- Migration: 2026_05_07_v6_4_133_line_message_templates_catchup.sql
-- v6.4 #133 — Catch-up migration for line_message_templates
--
-- The v6.2 LINE OA Rich Messaging code references this table
-- (App\Models\LineMessaging::getTemplates etc.) but no migration file
-- existed in database/migrations/ to create it. Production DBs already
-- have it (created out-of-band during the v6.2 deploy); this migration
-- is a CREATE TABLE IF NOT EXISTS so:
--   * Local dev DBs get the table for the first time
--   * New tenant DBs get it on next provision
--   * Production is unchanged (table already exists, statement is no-op)
--
-- The schema mirrors what's currently on production staging
-- (f2coth_dev) per phpMyAdmin's SHOW COLUMNS output. The enum on
-- template_type intentionally only includes values we control here;
-- production may have additional values from the v6.2 deploy and they
-- are preserved (this migration won't run there).
--
-- Compatible: MySQL 5.7 / MariaDB / cPanel phpMyAdmin (no CLI required)

CREATE TABLE IF NOT EXISTS `line_message_templates` (
    `id`             INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`     INT(11) NOT NULL,
    `name`           VARCHAR(150) NOT NULL,
    -- Matches the production enum verified on f2coth_dev 2026-05-07.
    -- Agent-flow templates are identified by `name` prefix (e.g.
    -- 'agent.booking_confirmed') with template_type='custom' — see
    -- LineTemplateRenderer. Adding new enum values here would diverge
    -- from prod and force a schema change at next deploy.
    `template_type`  ENUM(
                        'tour_package',
                        'quotation',
                        'booking_confirm',
                        'payment_reminder',
                        'voucher',
                        'custom'
                     ) DEFAULT 'custom',
    `message_type`   ENUM('text','flex') DEFAULT 'flex',
    `alt_text`       VARCHAR(400) DEFAULT NULL,
    `content_th`     TEXT DEFAULT NULL,
    `content_en`     TEXT DEFAULT NULL,
    `variables_json` TEXT DEFAULT NULL
                     COMMENT 'JSON list of placeholder definitions for the editor UI',
    `is_active`      TINYINT(1) DEFAULT 1,
    `created_by`     INT(11) DEFAULT NULL,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lmt_company`        (`company_id`),
    KEY `idx_lmt_template_type`  (`template_type`),
    KEY `idx_lmt_is_active`      (`is_active`),
    KEY `idx_lmt_company_name`   (`company_id`, `name`)
        COMMENT 'Used by LineTemplateRenderer for per-tenant lookups by template name'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
