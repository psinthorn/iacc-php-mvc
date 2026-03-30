-- Migration: Add Quotation Support to Receipt Table
-- Date: 2026-01-04
-- Description: Extend receipt system to support both quotations and invoices with VAT toggle
-- Compatible with MySQL 5.7

-- Add source_type column to support quotation/invoice selection
-- The existing receipt table has: id, name, phone, email, createdate, description, 
--   payment_method, status, invoice_id, vender, rep_no, rep_rw, brand, vat, dis, deleted_at

-- First check if columns exist, if not add them
-- Note: In MySQL 5.7 we need to use stored procedure or ignore errors

-- Add quotation_id column (analogous to invoice_id)
ALTER TABLE receipt ADD COLUMN quotation_id INT NULL AFTER invoice_id;

-- Add source_type column
ALTER TABLE receipt ADD COLUMN source_type ENUM('quotation','invoice','manual') DEFAULT 'manual' AFTER quotation_id;

-- Add include_vat flag (1 = include VAT in total, 0 = exclude VAT / no VAT)
ALTER TABLE receipt ADD COLUMN include_vat TINYINT(1) NOT NULL DEFAULT 1 AFTER dis;

-- Add payment reference for bank transfers / cheques
ALTER TABLE receipt ADD COLUMN payment_ref VARCHAR(100) NULL AFTER payment_method;

-- Add payment_date for when payment was received (distinct from createdate)
ALTER TABLE receipt ADD COLUMN payment_date DATE NULL AFTER payment_ref;

-- Add subtotal and calculated totals for clarity
ALTER TABLE receipt ADD COLUMN subtotal DECIMAL(12,2) DEFAULT 0 AFTER brand;
ALTER TABLE receipt ADD COLUMN after_discount DECIMAL(12,2) DEFAULT 0 AFTER subtotal;
ALTER TABLE receipt ADD COLUMN vat_amount DECIMAL(12,2) DEFAULT 0 AFTER after_discount;
ALTER TABLE receipt ADD COLUMN total_amount DECIMAL(12,2) DEFAULT 0 AFTER vat_amount;

-- Add updated_at for tracking modifications
ALTER TABLE receipt ADD COLUMN updated_at DATETIME NULL AFTER createdate;

-- Migrate existing data: set source_type based on invoice_id
UPDATE receipt SET source_type = 'invoice' WHERE invoice_id IS NOT NULL AND (source_type IS NULL OR source_type = 'manual');
UPDATE receipt SET source_type = 'manual' WHERE invoice_id IS NULL AND quotation_id IS NULL AND (source_type IS NULL OR source_type = '');
