-- Migration: Create payment_methods table
-- Date: 2026-01-02
-- Description: Multi-channel payment methods (bank, gateway, QR code, etc.)

-- First, ensure company table uses InnoDB for FK support
ALTER TABLE company ENGINE=InnoDB;

-- Create payment_methods table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    com_id INT(11) NOT NULL COMMENT 'Company ID (vendor)',
    method_type ENUM('bank', 'gateway', 'qrcode', 'cash', 'other') NOT NULL DEFAULT 'bank',
    method_name VARCHAR(100) NOT NULL COMMENT 'e.g., SCB, KBank, PayPal, PromptPay',
    account_name VARCHAR(150) NULL COMMENT 'Account holder name',
    account_number VARCHAR(50) NULL COMMENT 'Bank account number',
    branch VARCHAR(100) NULL COMMENT 'Bank branch',
    gateway_id VARCHAR(100) NULL COMMENT 'For payment gateway merchant ID',
    qr_image VARCHAR(200) NULL COMMENT 'Path to QR code image',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_com_id (com_id),
    INDEX idx_method_type (method_type),
    INDEX idx_active (is_active),
    CONSTRAINT fk_payment_methods_company 
        FOREIGN KEY (com_id) REFERENCES company(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback (if needed):
-- DROP TABLE IF EXISTS payment_methods;
