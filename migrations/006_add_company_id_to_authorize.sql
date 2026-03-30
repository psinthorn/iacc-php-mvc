-- Migration: Add company_id to authorize table
-- Date: 2026-01-02
-- Description: Link users to companies for role-based access control

-- Add company_id column to authorize table
ALTER TABLE authorize ADD COLUMN company_id INT(11) NULL DEFAULT NULL AFTER level;

-- Add index for company_id
ALTER TABLE authorize ADD INDEX idx_company_id (company_id);

-- Note: Normal users (level=0) should have a company_id assigned
-- Admin (level=1) and Super Admin (level=2) have company_id = NULL (can access all companies)
