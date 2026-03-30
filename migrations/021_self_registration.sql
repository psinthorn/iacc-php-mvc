-- Migration: 021_self_registration.sql
-- Description: Self-registration system tables for v6.0
-- Date: 2026-03-30
-- MySQL: 5.7 compatible

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    payload TEXT NOT NULL COMMENT 'JSON: name, password_hash, company_name, etc.',
    expires_at DATETIME NOT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add registration source tracking to authorize table
-- Using stored procedure for MySQL 5.7 compatibility (no ADD COLUMN IF NOT EXISTS)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_registration_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'authorize' AND column_name = 'registered_via') THEN
        ALTER TABLE authorize ADD COLUMN registered_via ENUM('admin','self','api') DEFAULT 'admin' AFTER password_migrated;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'authorize' AND column_name = 'email_verified_at') THEN
        ALTER TABLE authorize ADD COLUMN email_verified_at DATETIME DEFAULT NULL AFTER registered_via;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'company' AND column_name = 'registered_via') THEN
        ALTER TABLE company ADD COLUMN registered_via ENUM('admin','self','api') DEFAULT 'admin' AFTER deleted_at;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'company' AND column_name = 'onboarding_completed') THEN
        ALTER TABLE company ADD COLUMN onboarding_completed TINYINT(1) DEFAULT 0 AFTER registered_via;
    END IF;
END //
DELIMITER ;

CALL add_registration_columns();
DROP PROCEDURE IF EXISTS add_registration_columns;
