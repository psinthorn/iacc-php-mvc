-- Migration: 006_self_registration.sql
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
ALTER TABLE authorize
    ADD COLUMN IF NOT EXISTS registered_via ENUM('admin','self','api') DEFAULT 'admin' AFTER password_migrated,
    ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL AFTER registered_via;

-- Add registration source tracking to company table  
ALTER TABLE company
    ADD COLUMN IF NOT EXISTS registered_via ENUM('admin','self','api') DEFAULT 'admin' AFTER deleted_at,
    ADD COLUMN IF NOT EXISTS onboarding_completed TINYINT(1) DEFAULT 0 AFTER registered_via;
