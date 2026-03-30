-- Migration: Add company_id to company table for multi-tenant customer/vendor isolation
-- This allows each company to have its own set of customers and vendors
-- Date: 2026-01-04

-- Add company_id column to company table
ALTER TABLE company ADD COLUMN company_id INT(11) NULL AFTER deleted_at;

-- Add index for performance
ALTER TABLE company ADD INDEX idx_company_company_id (company_id);

-- Set existing customers/vendors to belong to F2 Co.,Ltd (company_id = 95)
-- Only update records that are customers or vendors (not the main companies)
UPDATE company 
SET company_id = 95 
WHERE (customer = 1 OR vender = 1) 
  AND id != 95 
  AND company_id IS NULL;

-- Main companies (like F2 Co.,Ltd itself) should have NULL company_id
-- They are the parent companies, not owned by anyone

-- Verify the update
SELECT 
    COUNT(*) as total_companies,
    SUM(CASE WHEN company_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_to_company,
    SUM(CASE WHEN company_id IS NULL THEN 1 ELSE 0 END) as main_companies
FROM company;
