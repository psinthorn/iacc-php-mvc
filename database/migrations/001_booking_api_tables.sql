-- ============================================================
-- Booking API Phase 1 — Migration
-- 4 new tables for the Booking API SaaS module
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/001_booking_api_tables.sql
-- ============================================================

-- 1. API Subscriptions (one per company)
-- Tracks which plan a company is on and when it expires
CREATE TABLE IF NOT EXISTS `api_subscriptions` (
    `id`            INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`    INT(11) NOT NULL,
    `plan`          ENUM('trial','starter','professional','enterprise') NOT NULL DEFAULT 'trial',
    `status`        ENUM('active','expired','cancelled','suspended') NOT NULL DEFAULT 'active',
    `bookings_limit` INT(11) NOT NULL DEFAULT 50 COMMENT 'Max bookings per month',
    `keys_limit`    INT(11) NOT NULL DEFAULT 1 COMMENT 'Max API keys allowed',
    `channels`      VARCHAR(255) NOT NULL DEFAULT 'website' COMMENT 'Comma-separated: website,email,line,facebook,manual',
    `ai_providers`  VARCHAR(255) NOT NULL DEFAULT 'ollama' COMMENT 'Comma-separated: ollama,openai,claude,gemini',
    `trial_start`   DATE DEFAULT NULL,
    `trial_end`     DATE DEFAULT NULL,
    `started_at`    DATETIME DEFAULT NULL COMMENT 'When paid plan started',
    `expires_at`    DATETIME DEFAULT NULL COMMENT 'When current period expires',
    `enabled`       TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Super Admin can disable',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_company` (`company_id`),
    KEY `idx_status` (`status`),
    KEY `idx_plan` (`plan`),
    CONSTRAINT `fk_sub_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. API Keys (multiple per company based on plan)
-- Each key has a secret; both sent in headers for authentication
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id`            INT(11) NOT NULL AUTO_INCREMENT,
    `company_id`    INT(11) NOT NULL,
    `subscription_id` INT(11) NOT NULL,
    `key_name`      VARCHAR(100) NOT NULL DEFAULT 'Default' COMMENT 'Friendly name',
    `api_key`       VARCHAR(64) NOT NULL COMMENT 'Public key sent in X-API-Key header',
    `api_secret`    VARCHAR(64) NOT NULL COMMENT 'Secret sent in X-API-Secret header',
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `last_used_at`  DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_key` (`api_key`),
    KEY `idx_company` (`company_id`),
    KEY `idx_subscription` (`subscription_id`),
    CONSTRAINT `fk_key_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_key_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. API Usage Logs (per-request tracking for quotas & analytics)
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
    `id`            BIGINT(20) NOT NULL AUTO_INCREMENT,
    `company_id`    INT(11) NOT NULL,
    `api_key_id`    INT(11) NOT NULL,
    `endpoint`      VARCHAR(100) NOT NULL COMMENT 'e.g. POST /api/v1/bookings',
    `channel`       VARCHAR(20) DEFAULT 'website' COMMENT 'website, email, line, facebook, manual',
    `status_code`   INT(3) NOT NULL DEFAULT 200,
    `request_ip`    VARCHAR(45) DEFAULT NULL COMMENT 'Supports IPv6',
    `request_body`  TEXT DEFAULT NULL COMMENT 'Truncated request payload',
    `response_body` TEXT DEFAULT NULL COMMENT 'Truncated response',
    `processing_ms` INT(11) DEFAULT NULL COMMENT 'Processing time in milliseconds',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company_date` (`company_id`, `created_at`),
    KEY `idx_api_key` (`api_key_id`),
    KEY `idx_channel` (`channel`),
    CONSTRAINT `fk_log_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_log_key` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Booking Requests (the actual bookings received via API)
-- Links to the PO/PR created in the accounting system
CREATE TABLE IF NOT EXISTS `booking_requests` (
    `id`            BIGINT(20) NOT NULL AUTO_INCREMENT,
    `company_id`    INT(11) NOT NULL COMMENT 'The company that owns this API subscription',
    `api_key_id`    INT(11) DEFAULT NULL,
    `channel`       VARCHAR(20) NOT NULL DEFAULT 'website',
    `status`        ENUM('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
    
    -- Guest/Customer info from the booking source
    `guest_name`    VARCHAR(255) NOT NULL,
    `guest_email`   VARCHAR(255) DEFAULT NULL,
    `guest_phone`   VARCHAR(50) DEFAULT NULL,
    
    -- Booking details
    `check_in`      DATE DEFAULT NULL,
    `check_out`     DATE DEFAULT NULL,
    `room_type`     VARCHAR(100) DEFAULT NULL,
    `guests`        INT(11) DEFAULT 1,
    `total_amount`  DECIMAL(12,2) DEFAULT NULL,
    `currency`      VARCHAR(3) NOT NULL DEFAULT 'THB',
    `notes`         TEXT DEFAULT NULL,
    `raw_data`      JSON DEFAULT NULL COMMENT 'Original payload from source',
    
    -- Linked iACC records (populated after processing)
    `linked_company_id` INT(11) DEFAULT NULL COMMENT 'Customer company created/matched',
    `linked_pr_id`      INT(11) DEFAULT NULL COMMENT 'PR created',
    `linked_po_id`      INT(11) DEFAULT NULL COMMENT 'PO created',
    
    -- AI parsing metadata
    `ai_parsed`     TINYINT(1) NOT NULL DEFAULT 0,
    `ai_provider`   VARCHAR(20) DEFAULT NULL COMMENT 'ollama, openai, claude, gemini',
    `ai_confidence` DECIMAL(5,2) DEFAULT NULL COMMENT 'AI confidence score 0-100',
    
    `error_message` TEXT DEFAULT NULL,
    `processed_at`  DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company_status` (`company_id`, `status`),
    KEY `idx_channel` (`channel`),
    KEY `idx_checkin` (`check_in`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_booking_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
