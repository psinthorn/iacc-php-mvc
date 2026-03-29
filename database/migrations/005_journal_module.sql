-- =====================================================
-- Migration 005: Journal Module & Chart of Accounts
-- Version: 5.6
-- Date: 2026-03-29
-- Description: Add Chart of Accounts, Journal Vouchers,
--              and Journal Entries for double-entry bookkeeping
-- =====================================================

-- -----------------------------------------------------
-- 1. Chart of Accounts (COA)
-- Standard accounting chart with 5 account types
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `com_id` int(11) NOT NULL COMMENT 'Company ID (multi-tenant)',
    `account_code` varchar(20) NOT NULL COMMENT 'Account code (e.g. 1100, 2100)',
    `account_name` varchar(150) NOT NULL COMMENT 'Account name in English',
    `account_name_th` varchar(150) DEFAULT NULL COMMENT 'Account name in Thai',
    `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL COMMENT 'Account classification',
    `parent_id` int(11) DEFAULT NULL COMMENT 'Parent account for sub-accounts',
    `level` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Hierarchy level (1=top, 2=sub, 3=detail)',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `description` varchar(255) DEFAULT NULL,
    `normal_balance` enum('debit','credit') NOT NULL COMMENT 'Normal balance side',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_coa_com_id` (`com_id`),
    KEY `idx_coa_account_code` (`account_code`),
    KEY `idx_coa_account_type` (`account_type`),
    KEY `idx_coa_parent_id` (`parent_id`),
    UNIQUE KEY `uq_coa_com_code` (`com_id`, `account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Journal Vouchers (Header)
-- Each journal voucher contains multiple entries
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `journal_vouchers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `com_id` int(11) NOT NULL COMMENT 'Company ID (multi-tenant)',
    `jv_number` varchar(20) NOT NULL COMMENT 'Auto-generated: JV-YYYYMM-XXXX',
    `voucher_type` enum('general','payment','receipt','adjustment','opening','closing') NOT NULL DEFAULT 'general' COMMENT 'Voucher classification',
    `transaction_date` date NOT NULL COMMENT 'Date of transaction',
    `description` text DEFAULT NULL COMMENT 'Journal description/narration',
    `reference` varchar(100) DEFAULT NULL COMMENT 'Reference document (PO#, INV#, etc.)',
    `reference_type` enum('po','invoice','receipt','voucher','expense','other') DEFAULT NULL,
    `reference_id` int(11) DEFAULT NULL COMMENT 'ID of referenced document',
    `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
    `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
    `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
    `posted_at` datetime DEFAULT NULL,
    `posted_by` int(11) DEFAULT NULL,
    `cancelled_at` datetime DEFAULT NULL,
    `cancelled_by` int(11) DEFAULT NULL,
    `cancel_reason` varchar(255) DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_jv_com_id` (`com_id`),
    KEY `idx_jv_number` (`jv_number`),
    KEY `idx_jv_type` (`voucher_type`),
    KEY `idx_jv_date` (`transaction_date`),
    KEY `idx_jv_status` (`status`),
    KEY `idx_jv_reference` (`reference_type`, `reference_id`),
    UNIQUE KEY `uq_jv_com_number` (`com_id`, `jv_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Journal Entries (Line items - Debit/Credit)
-- Double-entry: total debits must equal total credits
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `journal_entries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `journal_voucher_id` int(11) NOT NULL COMMENT 'FK to journal_vouchers',
    `account_id` int(11) NOT NULL COMMENT 'FK to chart_of_accounts',
    `description` varchar(255) DEFAULT NULL COMMENT 'Line item description',
    `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
    `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_je_voucher_id` (`journal_voucher_id`),
    KEY `idx_je_account_id` (`account_id`),
    CONSTRAINT `fk_je_journal_voucher` FOREIGN KEY (`journal_voucher_id`) REFERENCES `journal_vouchers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_je_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Seed: Default Chart of Accounts (Standard Thai/EN)
-- Will be inserted per-company on first use
-- Template accounts seeded for com_id=0 (template)
-- -----------------------------------------------------
INSERT INTO `chart_of_accounts` (`com_id`, `account_code`, `account_name`, `account_name_th`, `account_type`, `parent_id`, `level`, `normal_balance`, `description`) VALUES
-- Assets (1xxx)
(0, '1000', 'Assets', 'สินทรัพย์', 'asset', NULL, 1, 'debit', 'Top-level asset group'),
(0, '1100', 'Cash and Cash Equivalents', 'เงินสดและรายการเทียบเท่าเงินสด', 'asset', NULL, 2, 'debit', 'Cash on hand and bank deposits'),
(0, '1110', 'Cash on Hand', 'เงินสดในมือ', 'asset', NULL, 3, 'debit', NULL),
(0, '1120', 'Cash at Bank', 'เงินฝากธนาคาร', 'asset', NULL, 3, 'debit', NULL),
(0, '1200', 'Accounts Receivable', 'ลูกหนี้การค้า', 'asset', NULL, 2, 'debit', 'Trade receivables'),
(0, '1300', 'Inventory', 'สินค้าคงคลัง', 'asset', NULL, 2, 'debit', 'Stock and inventory'),
(0, '1400', 'Prepaid Expenses', 'ค่าใช้จ่ายจ่ายล่วงหน้า', 'asset', NULL, 2, 'debit', NULL),
(0, '1500', 'Fixed Assets', 'สินทรัพย์ถาวร', 'asset', NULL, 2, 'debit', 'Property, plant, equipment'),
(0, '1510', 'Land', 'ที่ดิน', 'asset', NULL, 3, 'debit', NULL),
(0, '1520', 'Buildings', 'อาคาร', 'asset', NULL, 3, 'debit', NULL),
(0, '1530', 'Equipment', 'อุปกรณ์', 'asset', NULL, 3, 'debit', NULL),
(0, '1540', 'Vehicles', 'ยานพาหนะ', 'asset', NULL, 3, 'debit', NULL),
(0, '1600', 'Accumulated Depreciation', 'ค่าเสื่อมราคาสะสม', 'asset', NULL, 2, 'credit', 'Contra-asset'),
-- Liabilities (2xxx)
(0, '2000', 'Liabilities', 'หนี้สิน', 'liability', NULL, 1, 'credit', 'Top-level liability group'),
(0, '2100', 'Accounts Payable', 'เจ้าหนี้การค้า', 'liability', NULL, 2, 'credit', 'Trade payables'),
(0, '2200', 'Accrued Expenses', 'ค่าใช้จ่ายค้างจ่าย', 'liability', NULL, 2, 'credit', NULL),
(0, '2300', 'VAT Payable', 'ภาษีมูลค่าเพิ่มค้างจ่าย', 'liability', NULL, 2, 'credit', 'Output VAT'),
(0, '2310', 'WHT Payable', 'ภาษีหัก ณ ที่จ่ายค้างจ่าย', 'liability', NULL, 2, 'credit', 'Withholding tax'),
(0, '2400', 'Short-term Loans', 'เงินกู้ยืมระยะสั้น', 'liability', NULL, 2, 'credit', NULL),
(0, '2500', 'Long-term Loans', 'เงินกู้ยืมระยะยาว', 'liability', NULL, 2, 'credit', NULL),
-- Equity (3xxx)
(0, '3000', 'Equity', 'ส่วนของเจ้าของ', 'equity', NULL, 1, 'credit', 'Top-level equity group'),
(0, '3100', 'Share Capital', 'ทุนจดทะเบียน', 'equity', NULL, 2, 'credit', 'Paid-up capital'),
(0, '3200', 'Retained Earnings', 'กำไรสะสม', 'equity', NULL, 2, 'credit', NULL),
(0, '3300', 'Current Year Earnings', 'กำไร(ขาดทุน)ปีปัจจุบัน', 'equity', NULL, 2, 'credit', NULL),
-- Revenue (4xxx)
(0, '4000', 'Revenue', 'รายได้', 'revenue', NULL, 1, 'credit', 'Top-level revenue group'),
(0, '4100', 'Sales Revenue', 'รายได้จากการขาย', 'revenue', NULL, 2, 'credit', 'Sales of goods/services'),
(0, '4200', 'Service Revenue', 'รายได้จากการบริการ', 'revenue', NULL, 2, 'credit', NULL),
(0, '4300', 'Other Income', 'รายได้อื่น', 'revenue', NULL, 2, 'credit', NULL),
(0, '4310', 'Interest Income', 'ดอกเบี้ยรับ', 'revenue', NULL, 3, 'credit', NULL),
(0, '4320', 'Gain on Asset Sale', 'กำไรจากการขายสินทรัพย์', 'revenue', NULL, 3, 'credit', NULL),
-- Expenses (5xxx)
(0, '5000', 'Expenses', 'ค่าใช้จ่าย', 'expense', NULL, 1, 'debit', 'Top-level expense group'),
(0, '5100', 'Cost of Goods Sold', 'ต้นทุนขาย', 'expense', NULL, 2, 'debit', 'Direct cost of sales'),
(0, '5200', 'Salary & Wages', 'เงินเดือนและค่าจ้าง', 'expense', NULL, 2, 'debit', NULL),
(0, '5300', 'Rent Expense', 'ค่าเช่า', 'expense', NULL, 2, 'debit', NULL),
(0, '5400', 'Utilities', 'ค่าสาธารณูปโภค', 'expense', NULL, 2, 'debit', NULL),
(0, '5500', 'Depreciation Expense', 'ค่าเสื่อมราคา', 'expense', NULL, 2, 'debit', NULL),
(0, '5600', 'Office Supplies', 'วัสดุสำนักงาน', 'expense', NULL, 2, 'debit', NULL),
(0, '5700', 'Insurance', 'ค่าประกันภัย', 'expense', NULL, 2, 'debit', NULL),
(0, '5800', 'Professional Fees', 'ค่าบริการวิชาชีพ', 'expense', NULL, 2, 'debit', NULL),
(0, '5900', 'Other Expenses', 'ค่าใช้จ่ายอื่น', 'expense', NULL, 2, 'debit', NULL),
(0, '5910', 'Bank Charges', 'ค่าธรรมเนียมธนาคาร', 'expense', NULL, 3, 'debit', NULL),
(0, '5920', 'Loss on Asset Sale', 'ขาดทุนจากการขายสินทรัพย์', 'expense', NULL, 3, 'debit', NULL);
