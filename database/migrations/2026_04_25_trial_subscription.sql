-- =============================================================================
-- Migration: 2026_04_25_trial_subscription.sql
-- Description: Trial period management and subscription billing enhancements
-- Adds: subscription_plans table, subscription_payments table
--       trial_locked_at column to api_subscriptions
-- MySQL 5.7 compatible, idempotent
-- =============================================================================

-- ── subscription_plans — Product catalogue ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`            VARCHAR(30) NOT NULL COMMENT 'trial|starter|professional|enterprise',
    `name`            VARCHAR(100) NOT NULL,
    `price_monthly`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'THB per month (0 = free/trial)',
    `price_annual`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'THB per year (0 = free/trial)',
    `orders_limit`    INT(11) NOT NULL DEFAULT 50,
    `keys_limit`      INT(11) NOT NULL DEFAULT 1,
    `duration_days`   INT(11) NOT NULL DEFAULT 30,
    `features`        TEXT                   COMMENT 'JSON array of feature strings for display',
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`      INT(11) NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subscription plan catalogue';

-- Seed default plans (idempotent)
INSERT IGNORE INTO `subscription_plans` (`code`,`name`,`price_monthly`,`price_annual`,`orders_limit`,`keys_limit`,`duration_days`,`features`,`sort_order`) VALUES
('trial',        'Free Trial',    0,      0,      50,    1,   14,  '["14-day free trial","Tour booking module","1 user","Email support"]', 0),
('starter',      'Starter',       990,    9900,   500,   3,   30,  '["Tour + Accounting modules","3 API keys","5 users","Email support"]', 1),
('professional', 'Professional',  2490,   24900,  5000,  10,  30,  '["All modules","10 API keys","Unlimited users","Priority support","AI features"]', 2),
('enterprise',   'Enterprise',    5990,   59900,  999999,999, 365, '["Everything in Professional","Custom integrations","Dedicated support","SLA guarantee"]', 3);

-- ── subscription_payments — Payment ledger for plan upgrades ─────────────────
CREATE TABLE IF NOT EXISTS `subscription_payments` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`      INT(11) NOT NULL,
    `subscription_id` INT(11) NOT NULL,
    `plan_code`       VARCHAR(30) NOT NULL,
    `billing_cycle`   ENUM('monthly','annual') NOT NULL DEFAULT 'monthly',
    `amount`          DECIMAL(10,2) NOT NULL,
    `currency`        VARCHAR(3) NOT NULL DEFAULT 'THB',
    `payment_method`  VARCHAR(50) NOT NULL DEFAULT 'manual',
    `gateway_ref`     VARCHAR(255) NULL DEFAULT NULL COMMENT 'Stripe/PromptPay reference',
    `status`          ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    `paid_at`         DATETIME NULL DEFAULT NULL,
    `expires_at`      DATETIME NULL DEFAULT NULL COMMENT 'Period this payment covers until',
    `notes`           TEXT NULL,
    `created_by`      INT(11) NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company`  (`company_id`),
    KEY `idx_sub`      (`subscription_id`),
    KEY `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subscription payment records';

-- ── api_subscriptions: add trial_locked_at ───────────────────────────────────
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `api_subscriptions` ADD COLUMN `trial_locked_at` DATETIME NULL DEFAULT NULL COMMENT ''Set when trial expires and account is locked'' AFTER `trial_end`',
    'SELECT 1 -- already exists'
) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_subscriptions' AND COLUMN_NAME = 'trial_locked_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
