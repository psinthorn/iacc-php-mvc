-- Migration: 003_webhooks_idempotency_key_rotation
-- Date: 2026-03-27
-- Description: Phase 3 — Webhooks, Idempotency, API Key Rotation

-- ============================================================
-- 1. Webhooks table
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_webhooks` (
    `id`             INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`     INT(11) NOT NULL,
    `url`            VARCHAR(500) NOT NULL,
    `secret`         VARCHAR(64) NOT NULL COMMENT 'Used for HMAC-SHA256 signature',
    `events`         VARCHAR(255) NOT NULL DEFAULT 'booking.created,booking.completed,booking.failed,booking.cancelled' COMMENT 'CSV of event types',
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `failure_count`  INT(11) NOT NULL DEFAULT 0 COMMENT 'Consecutive failures — auto-disable at 10',
    `last_triggered` DATETIME DEFAULT NULL,
    `last_status`    INT(3) DEFAULT NULL COMMENT 'HTTP status of last delivery',
    `last_error`     TEXT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_webhook_company` (`company_id`),
    KEY `idx_webhook_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Webhook delivery log (audit trail for webhook attempts)
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_webhook_deliveries` (
    `id`             BIGINT(20) NOT NULL AUTO_INCREMENT,
    `webhook_id`     INT(11) NOT NULL,
    `event`          VARCHAR(50) NOT NULL,
    `payload`        TEXT NOT NULL,
    `response_code`  INT(3) DEFAULT NULL,
    `response_body`  TEXT DEFAULT NULL,
    `duration_ms`    INT(11) DEFAULT NULL,
    `success`        TINYINT(1) NOT NULL DEFAULT 0,
    `error`          TEXT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_delivery_webhook` (`webhook_id`),
    KEY `idx_delivery_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Idempotency key column on booking_requests
-- ============================================================
ALTER TABLE `booking_requests` ADD COLUMN `idempotency_key` VARCHAR(64) DEFAULT NULL AFTER `raw_data`;
ALTER TABLE `booking_requests` ADD UNIQUE KEY `idx_idempotency` (`company_id`, `idempotency_key`);

-- ============================================================
-- 4. API Key Rotation support columns
-- ============================================================
ALTER TABLE `api_keys` ADD COLUMN `previous_key`    VARCHAR(64) DEFAULT NULL AFTER `api_secret`;
ALTER TABLE `api_keys` ADD COLUMN `previous_secret` VARCHAR(64) DEFAULT NULL AFTER `previous_key`;
ALTER TABLE `api_keys` ADD COLUMN `grace_expires_at` DATETIME DEFAULT NULL AFTER `previous_secret`;
