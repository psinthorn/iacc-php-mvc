-- Migration: 015_split_invoice_wht.sql
-- Purpose: Add split invoice support for WHT (Withholding Tax) separation
-- When a quotation has both material and labour items, invoices are split into two:
--   1. Material invoice (no WHT)
--   2. Labour invoice (with WHT)
-- Both share the same split_group_id to link them together.

ALTER TABLE po
  ADD COLUMN split_group_id INT(11) DEFAULT NULL
    COMMENT 'Links split invoices together. Stores the first PO ID in the group. NULL for normal invoices.',
  ADD COLUMN split_type ENUM('full', 'material', 'labour') DEFAULT 'full'
    COMMENT 'full=normal invoice, material=materials only, labour=labour only',
  ADD INDEX idx_split_group (split_group_id);
