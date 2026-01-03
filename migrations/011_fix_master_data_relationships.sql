-- ============================================================================
-- Migration: 011_fix_master_data_relationships.sql
-- Purpose: Fix data types, add foreign keys, clean orphaned data, add indexes
-- Date: 2026-01-04
-- Author: System Migration
-- ============================================================================

-- IMPORTANT: Run backup before executing this migration!

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PHASE 1: CLEAN ORPHANED DATA
-- ============================================================================

-- 1.1 Delete models without valid type
DELETE FROM model WHERE type_id NOT IN (SELECT id FROM type);

-- 1.2 Delete models without valid brand  
DELETE FROM model WHERE brand_id NOT IN (SELECT id FROM brand);

-- 1.3 Delete map_type_to_brand orphans
DELETE FROM map_type_to_brand WHERE type_id NOT IN (SELECT id FROM type);
DELETE FROM map_type_to_brand WHERE brand_id NOT IN (SELECT id FROM brand);

-- 1.4 Set empty product.model to 0 (will be NULL after conversion)
UPDATE product SET model = '0' WHERE model = '' OR model IS NULL;

-- 1.5 Set orphaned product.model to 0 (products referencing deleted models)
UPDATE product SET model = '0' 
WHERE CAST(model AS UNSIGNED) NOT IN (SELECT id FROM model) AND model != '0';

-- 1.6 Set orphaned product.type to 0
UPDATE product SET type = 0 WHERE type NOT IN (SELECT id FROM type) AND type != 0;

-- 1.7 Set orphaned product.ban_id to 0
UPDATE product SET ban_id = 0 WHERE ban_id NOT IN (SELECT id FROM brand) AND ban_id != 0;

-- ============================================================================
-- PHASE 2: FIX DATA TYPES
-- ============================================================================

-- 2.1 Convert product.model from VARCHAR(30) to INT
-- First, add a temporary column
ALTER TABLE product ADD COLUMN model_id INT NULL AFTER model;

-- Copy data with conversion
UPDATE product SET model_id = CASE 
    WHEN model = '0' OR model = '' THEN NULL 
    ELSE CAST(model AS UNSIGNED) 
END;

-- Drop old column and rename
ALTER TABLE product DROP COLUMN model;
ALTER TABLE product CHANGE COLUMN model_id model INT NULL;

-- 2.2 Convert product.quantity from VARCHAR(10) to DECIMAL
ALTER TABLE product ADD COLUMN quantity_new DECIMAL(10,2) NULL AFTER quantity;
UPDATE product SET quantity_new = CASE 
    WHEN quantity = '' THEN 0 
    ELSE CAST(quantity AS DECIMAL(10,2)) 
END;
ALTER TABLE product DROP COLUMN quantity;
ALTER TABLE product CHANGE COLUMN quantity_new quantity DECIMAL(10,2) NOT NULL DEFAULT 0;

-- 2.3 Convert product.pack_quantity from VARCHAR(10) to DECIMAL
ALTER TABLE product ADD COLUMN pack_quantity_new DECIMAL(10,2) NULL AFTER pack_quantity;
UPDATE product SET pack_quantity_new = CASE 
    WHEN pack_quantity = '' THEN 0 
    ELSE CAST(pack_quantity AS DECIMAL(10,2)) 
END;
ALTER TABLE product DROP COLUMN pack_quantity;
ALTER TABLE product CHANGE COLUMN pack_quantity_new pack_quantity DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ============================================================================
-- PHASE 3: ADD FOREIGN KEY CONSTRAINTS
-- ============================================================================

-- 3.1 type → category
ALTER TABLE type 
    ADD CONSTRAINT fk_type_category 
    FOREIGN KEY (cat_id) REFERENCES category(id) 
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3.2 type → company
ALTER TABLE type 
    ADD CONSTRAINT fk_type_company 
    FOREIGN KEY (company_id) REFERENCES company(id) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 3.3 brand → company
ALTER TABLE brand 
    ADD CONSTRAINT fk_brand_company 
    FOREIGN KEY (company_id) REFERENCES company(id) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 3.4 category → company
ALTER TABLE category 
    ADD CONSTRAINT fk_category_company 
    FOREIGN KEY (company_id) REFERENCES company(id) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- 3.5 product → type (allowing NULL for products without type)
ALTER TABLE product ADD INDEX idx_product_type_fk (type);

-- 3.6 product → brand (allowing NULL for products without brand)
ALTER TABLE product ADD INDEX idx_product_brand_fk (ban_id);

-- 3.7 product → model (allowing NULL)
ALTER TABLE product ADD INDEX idx_product_model_fk (model);

-- Note: Not adding strict FK on product→type/brand/model as they allow 0/NULL values
-- This preserves flexibility for legacy data

-- ============================================================================
-- PHASE 4: ADD COMPOSITE INDEXES FOR PERFORMANCE
-- ============================================================================

-- 4.1 Multi-tenant query optimization
CREATE INDEX idx_category_company_name ON category(company_id, cat_name);
CREATE INDEX idx_type_company_cat ON type(company_id, cat_id);
CREATE INDEX idx_type_company_name ON type(company_id, name);
CREATE INDEX idx_brand_company_name ON brand(company_id, brand_name);
CREATE INDEX idx_model_company_type_brand ON model(company_id, type_id, brand_id);

-- 4.2 Product query optimization
CREATE INDEX idx_product_company_type ON product(company_id, type);
CREATE INDEX idx_product_company_po_type ON product(company_id, po_id, type);

-- 4.3 Transaction optimization
-- Note: po table doesn't have 'status' column, skip that index
CREATE INDEX idx_iv_company_tex ON iv(company_id, tex);

-- ============================================================================
-- PHASE 5: VERIFY MIGRATION
-- ============================================================================

SELECT 'Orphan Check - Types without Category' as check_name, COUNT(*) as cnt 
FROM type WHERE cat_id NOT IN (SELECT id FROM category)
UNION ALL
SELECT 'Orphan Check - Models without Type', COUNT(*) 
FROM model WHERE type_id NOT IN (SELECT id FROM type)
UNION ALL
SELECT 'Orphan Check - Models without Brand', COUNT(*) 
FROM model WHERE brand_id NOT IN (SELECT id FROM brand)
UNION ALL
SELECT 'Products with NULL model (OK)', COUNT(*) 
FROM product WHERE model IS NULL
UNION ALL
SELECT 'Products with valid model', COUNT(*) 
FROM product WHERE model IN (SELECT id FROM model);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
