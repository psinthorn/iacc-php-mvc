-- =============================================================================
-- Q3 2026: Expense Module Migration
-- Version: 1.0
-- Date: 2026-03-28
-- =============================================================================
-- Run: mysql -u<user> -p <dbname> < 004_expense_tables.sql
-- Docker: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/004_expense_tables.sql
-- =============================================================================

-- =============================================
-- 1. Expense Categories
-- =============================================
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `com_id` INT(11) NOT NULL COMMENT 'Company ID',
    `name` VARCHAR(100) NOT NULL COMMENT 'Category name (English)',
    `name_th` VARCHAR(100) DEFAULT NULL COMMENT 'Category name (Thai)',
    `code` VARCHAR(20) DEFAULT NULL COMMENT 'Category code (e.g. EXP-RENT)',
    `icon` VARCHAR(50) DEFAULT 'fa-folder' COMMENT 'FontAwesome icon class',
    `color` VARCHAR(7) DEFAULT '#6366f1' COMMENT 'Category color hex',
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_company` (`com_id`),
    KEY `idx_active` (`is_active`, `com_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Expense categories';

-- Seed default categories (for company 95 — adjust as needed)
INSERT INTO `expense_categories` (`com_id`, `name`, `name_th`, `code`, `icon`, `color`, `sort_order`) VALUES
(95, 'Office Rent',        'ค่าเช่าสำนักงาน',     'EXP-RENT',   'fa-building',    '#6366f1', 1),
(95, 'Utilities',          'ค่าสาธารณูปโภค',      'EXP-UTIL',   'fa-bolt',        '#f59e0b', 2),
(95, 'Office Supplies',    'วัสดุสำนักงาน',        'EXP-SUP',    'fa-pencil',      '#10b981', 3),
(95, 'Travel & Transport', 'ค่าเดินทาง',          'EXP-TRVL',   'fa-car',         '#3b82f6', 4),
(95, 'Meals & Entertainment', 'ค่าอาหารและเลี้ยงรับรอง', 'EXP-MEAL', 'fa-cutlery',  '#ef4444', 5),
(95, 'Salary & Wages',     'เงินเดือนและค่าจ้าง',   'EXP-SAL',    'fa-users',       '#8b5cf6', 6),
(95, 'Insurance',          'ค่าประกันภัย',         'EXP-INS',    'fa-shield',      '#06b6d4', 7),
(95, 'Marketing & Ads',    'ค่าการตลาดและโฆษณา',   'EXP-MKT',    'fa-bullhorn',    '#ec4899', 8),
(95, 'Professional Fees',  'ค่าบริการวิชาชีพ',      'EXP-PROF',   'fa-briefcase',   '#14b8a6', 9),
(95, 'Miscellaneous',      'ค่าใช้จ่ายอื่นๆ',       'EXP-MISC',   'fa-ellipsis-h',  '#64748b', 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =============================================
-- 2. Expenses Table
-- =============================================
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `com_id` INT(11) NOT NULL COMMENT 'Company ID',
    `expense_number` VARCHAR(30) DEFAULT NULL COMMENT 'Auto-generated: EXP-YYYYMM-XXXX',
    `category_id` INT(11) DEFAULT NULL COMMENT 'FK to expense_categories',
    `title` VARCHAR(255) NOT NULL COMMENT 'Expense title/description',
    `description` TEXT DEFAULT NULL COMMENT 'Detailed notes',
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount before tax',
    `vat_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'VAT rate (7% standard)',
    `vat_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'VAT amount',
    `wht_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'WHT rate if applicable',
    `wht_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'WHT deducted amount',
    `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Net payable (amount + vat - wht)',
    `currency_code` VARCHAR(3) DEFAULT 'THB',
    `exchange_rate` DECIMAL(16,6) DEFAULT NULL COMMENT 'Exchange rate to THB',
    `expense_date` DATE NOT NULL COMMENT 'Date of expense',
    `due_date` DATE DEFAULT NULL COMMENT 'Payment due date',
    `paid_date` DATE DEFAULT NULL COMMENT 'Actual payment date',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'Cash, Transfer, Credit Card, etc.',
    `reference_no` VARCHAR(100) DEFAULT NULL COMMENT 'Receipt/invoice number from vendor',
    `vendor_name` VARCHAR(255) DEFAULT NULL COMMENT 'Vendor/supplier name',
    `vendor_tax_id` VARCHAR(20) DEFAULT NULL COMMENT 'Vendor tax ID for WHT',
    `po_id` INT(11) DEFAULT NULL COMMENT 'Link to Purchase Order (optional)',
    `pr_id` INT(11) DEFAULT NULL COMMENT 'Link to Purchase Request (optional)',
    `project_name` VARCHAR(255) DEFAULT NULL COMMENT 'Project name for cost tracking',
    `receipt_file` VARCHAR(255) DEFAULT NULL COMMENT 'Uploaded receipt/document path',
    `status` ENUM('draft','pending','approved','paid','rejected','cancelled') NOT NULL DEFAULT 'draft',
    `approved_by` INT(11) DEFAULT NULL COMMENT 'User who approved',
    `approved_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL COMMENT 'User who created',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_expense_number` (`expense_number`),
    KEY `idx_company` (`com_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`, `com_id`),
    KEY `idx_expense_date` (`expense_date`),
    KEY `idx_vendor` (`vendor_name`(50)),
    KEY `idx_project` (`project_name`(50)),
    KEY `idx_po` (`po_id`),
    KEY `idx_pr` (`pr_id`),
    CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Company expenses tracking';

-- =============================================================================
-- End of Q3 2026 Expense Module Migration
-- =============================================================================
