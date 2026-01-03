-- ============================================================================
-- Migration: 010_add_company_id_multi_tenant.sql
-- Purpose: Add company_id to all tables for multi-tenant data isolation
-- Date: 2026-01-04
-- Author: System Migration
-- ============================================================================

-- IMPORTANT: Run backup before executing this migration!
-- mysqldump -u root -p iacc > backup_before_multitenant_$(date +%Y%m%d_%H%M%S).sql

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PHASE 1: ADD company_id COLUMNS TO ALL TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1.1 MASTER DATA TABLES
-- ----------------------------------------------------------------------------

-- Brand table (currently has ven_id, add company_id for clear ownership)
ALTER TABLE brand 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_brand_company_id (company_id);

-- Category table  
ALTER TABLE category 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_category_company_id (company_id);

-- Type table
ALTER TABLE type 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_type_company_id (company_id);

-- Model table
ALTER TABLE model 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_model_company_id (company_id);

-- Map type to brand (junction table)
ALTER TABLE map_type_to_brand 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_mtb_company_id (company_id);

-- ----------------------------------------------------------------------------
-- 1.2 CONFIGURATION TABLES
-- ----------------------------------------------------------------------------

-- Payment methods table (payment_methods - legacy)
ALTER TABLE payment_methods 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_pm_company_id (company_id);

-- Payment method table (payment_method - new)
ALTER TABLE payment_method 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_pmethod_company_id (company_id);

-- Payment gateway config (if not already has company scope)
ALTER TABLE payment_gateway_config 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_pgc_company_id (company_id);

-- ----------------------------------------------------------------------------
-- 1.3 TRANSACTION TABLES
-- ----------------------------------------------------------------------------

-- Purchase Orders (add explicit owner company)
ALTER TABLE po 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_po_company_id (company_id);

-- Invoices (add owner company alongside cus_id)
ALTER TABLE iv 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_iv_company_id (company_id);

-- Products (line items - inherit from PO, but add for direct queries)
ALTER TABLE product 
    ADD COLUMN company_id INT NULL AFTER pro_id,
    ADD INDEX idx_product_company_id (company_id);

-- Deliveries
ALTER TABLE deliver 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_deliver_company_id (company_id);

-- Payments (pay table)
ALTER TABLE pay 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_pay_company_id (company_id);

-- Purchase Requests
ALTER TABLE pr 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_pr_company_id (company_id);

-- Vouchers
ALTER TABLE voucher 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_voucher_company_id (company_id);

-- Receipts
ALTER TABLE receipt 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_receipt_company_id (company_id);

-- Store/Inventory
ALTER TABLE store 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_store_company_id (company_id);

-- Send out items
ALTER TABLE sendoutitem 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_sendoutitem_company_id (company_id);

-- Receive items (uses rec_id as primary key, not id)
ALTER TABLE receive 
    ADD COLUMN company_id INT NULL AFTER rec_id,
    ADD INDEX idx_receive_company_id (company_id);

-- ----------------------------------------------------------------------------
-- 1.4 AUDIT/LOG TABLES
-- ----------------------------------------------------------------------------

-- Audit log (add company scope for filtering)
ALTER TABLE audit_log 
    ADD COLUMN company_id INT NULL AFTER id,
    ADD INDEX idx_auditlog_company_id (company_id);

-- ============================================================================
-- PHASE 2: DATA MIGRATION - POPULATE company_id
-- ============================================================================

-- Note: Default company_id = 7 (Direct Booking Co.,Ltd.) as the main operating company
-- For F2 Co.,Ltd. = 95
-- Adjust based on your actual data ownership

-- ----------------------------------------------------------------------------
-- 2.1 MASTER DATA - Set to operating company
-- ----------------------------------------------------------------------------

-- Brands: Use ven_id mapping or default to main company
UPDATE brand b
LEFT JOIN company c ON b.ven_id = c.id AND c.vender = 1
SET b.company_id = COALESCE(
    CASE WHEN b.ven_id > 0 AND c.id IS NOT NULL THEN b.ven_id ELSE NULL END,
    7  -- Default to Direct Booking
)
WHERE b.company_id IS NULL;

-- Categories: Default to main company
UPDATE category SET company_id = 7 WHERE company_id IS NULL;

-- Types: Default to main company  
UPDATE type SET company_id = 7 WHERE company_id IS NULL;

-- Models: Default to main company
UPDATE model SET company_id = 7 WHERE company_id IS NULL;

-- Map type to brand: Default to main company
UPDATE map_type_to_brand SET company_id = 7 WHERE company_id IS NULL;

-- ----------------------------------------------------------------------------
-- 2.2 CONFIGURATION DATA
-- ----------------------------------------------------------------------------

-- Payment methods (legacy table): Try to derive from payment table or default
UPDATE payment_methods SET company_id = 7 WHERE company_id IS NULL;

-- Payment method (new table): Default to main company
UPDATE payment_method SET company_id = 7 WHERE company_id IS NULL;

-- Payment gateway config: Default to main company
UPDATE payment_gateway_config SET company_id = 7 WHERE company_id IS NULL;

-- ----------------------------------------------------------------------------
-- 2.3 TRANSACTION DATA - Derive from existing relationships
-- ----------------------------------------------------------------------------

-- POs: Derive company from the vendor (bandven) or default
-- POs created by Direct Booking (vendor company_id 7)
UPDATE po SET company_id = 7 WHERE company_id IS NULL;

-- Invoices: Set company_id based on which company issued it
-- For now, default to company 7 (Direct Booking) as the issuing company
UPDATE iv SET company_id = 7 WHERE company_id IS NULL;

-- Products: Inherit from their PO
UPDATE product p
JOIN po ON p.po_id = po.id
SET p.company_id = po.company_id
WHERE p.company_id IS NULL;

-- Deliveries: Inherit from their PO
UPDATE deliver d
JOIN po ON d.po_id = po.id
SET d.company_id = po.company_id
WHERE d.company_id IS NULL;

-- Payments (pay): Inherit from their PO
UPDATE pay p
JOIN po ON p.po_id = po.id
SET p.company_id = po.company_id
WHERE p.company_id IS NULL;

-- Purchase Requests: Default to main company
UPDATE pr SET company_id = 7 WHERE company_id IS NULL;

-- Vouchers: Default to main company
UPDATE voucher SET company_id = 7 WHERE company_id IS NULL;

-- Receipts: Default to main company  
UPDATE receipt SET company_id = 7 WHERE company_id IS NULL;

-- Store/Inventory
UPDATE store SET company_id = 7 WHERE company_id IS NULL;

-- Send out items
UPDATE sendoutitem SET company_id = 7 WHERE company_id IS NULL;

-- Receive items
UPDATE receive SET company_id = 7 WHERE company_id IS NULL;

-- ----------------------------------------------------------------------------
-- 2.4 AUDIT DATA - Set based on user's company
-- ----------------------------------------------------------------------------

UPDATE audit_log al
LEFT JOIN authorize a ON al.user_id = a.id
SET al.company_id = COALESCE(a.company_id, 7)
WHERE al.company_id IS NULL;

-- ============================================================================
-- PHASE 3: ADD FOREIGN KEY CONSTRAINTS (Optional - for data integrity)
-- ============================================================================

-- Uncomment these if you want strict referential integrity
-- Note: This may fail if there are orphaned records

/*
ALTER TABLE brand
    ADD CONSTRAINT fk_brand_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE category
    ADD CONSTRAINT fk_category_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE type
    ADD CONSTRAINT fk_type_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE model
    ADD CONSTRAINT fk_model_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE po
    ADD CONSTRAINT fk_po_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE iv
    ADD CONSTRAINT fk_iv_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE product
    ADD CONSTRAINT fk_product_company
    FOREIGN KEY (company_id) REFERENCES company(id)
    ON DELETE SET NULL ON UPDATE CASCADE;
*/

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- PHASE 4: VERIFY MIGRATION
-- ============================================================================

-- Check that all tables have company_id populated
SELECT 'brand' AS table_name, COUNT(*) AS total, SUM(company_id IS NULL) AS null_count FROM brand
UNION ALL
SELECT 'category', COUNT(*), SUM(company_id IS NULL) FROM category
UNION ALL
SELECT 'type', COUNT(*), SUM(company_id IS NULL) FROM type
UNION ALL
SELECT 'model', COUNT(*), SUM(company_id IS NULL) FROM model
UNION ALL
SELECT 'po', COUNT(*), SUM(company_id IS NULL) FROM po
UNION ALL
SELECT 'iv', COUNT(*), SUM(company_id IS NULL) FROM iv
UNION ALL
SELECT 'product', COUNT(*), SUM(company_id IS NULL) FROM product
UNION ALL
SELECT 'deliver', COUNT(*), SUM(company_id IS NULL) FROM deliver
UNION ALL
SELECT 'pay', COUNT(*), SUM(company_id IS NULL) FROM pay;

-- ============================================================================
-- Log migration
-- ============================================================================
INSERT INTO _migration_log (migration_name, status, notes)
VALUES ('010_add_company_id_multi_tenant', 'success', 'Added company_id to all tables for multi-tenant isolation');

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
