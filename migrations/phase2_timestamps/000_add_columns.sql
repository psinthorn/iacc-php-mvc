-- ============================================================
-- Phase 2c: Add created_at / updated_at to all tables
-- Date: 2026-03-29
-- Safe to run multiple times (uses IF NOT EXISTS checks via procedure)
-- ============================================================

DELIMITER //

-- Helper: Add column only if it doesn't exist
DROP PROCEDURE IF EXISTS add_column_if_not_exists//
CREATE PROCEDURE add_column_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition VARCHAR(255)
)
BEGIN
    DECLARE col_count INT;
    SELECT COUNT(*) INTO col_count
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND BINARY TABLE_NAME = BINARY p_table
      AND BINARY COLUMN_NAME = BINARY p_column;
    IF col_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

-- ============================================================
-- GROUP 1: Tables needing BOTH created_at AND updated_at
-- (16 tables - no timestamp columns exist)
-- ============================================================

CALL add_column_if_not_exists('authorize',        'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('authorize',        'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('billing_items',    'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('billing_items',    'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('gen_serial',       'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('gen_serial',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('keep_log',         'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('keep_log',         'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('login_attempts',   'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('login_attempts',   'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('map_type_to_brand','created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('map_type_to_brand','updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('permissions',      'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('permissions',      'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('receive',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('receive',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('role_permissions', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('role_permissions', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('sendoutitem',      'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('sendoutitem',      'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('store',            'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('store',            'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('store_sale',       'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('store_sale',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('tmp_product',      'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('tmp_product',      'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('user',             'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('user',             'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('user_roles',       'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('user_roles',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('_migration_log',   'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('_migration_log',   'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

-- ============================================================
-- GROUP 2: Tables needing created_at only (already have deleted_at)
-- (15 tables)
-- ============================================================

CALL add_column_if_not_exists('brand',            'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('brand',            'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('category',         'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('category',         'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('company',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('company',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('company_addr',     'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('company_addr',     'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('company_credit',   'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('company_credit',   'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('deliver',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('deliver',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('iv',               'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('iv',               'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('model',            'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('model',            'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('pay',              'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('pay',              'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('payment',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('payment',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('po',               'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('po',               'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('pr',               'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('pr',               'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('product',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('product',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('type',             'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('type',             'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

CALL add_column_if_not_exists('voucher',          'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('voucher',          'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

-- ============================================================
-- GROUP 3: Tables needing updated_at only (already have created_at)
-- (10 tables)
-- ============================================================

CALL add_column_if_not_exists('ai_action_log',         'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('ai_conversations',      'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('ai_sessions',           'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('api_usage_logs',        'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('api_webhook_deliveries','updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('audit_logs',            'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('billing',               'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('journal_entries',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('password_resets',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
CALL add_column_if_not_exists('remember_tokens',       'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

-- ============================================================
-- Cleanup helper procedure
-- ============================================================
DROP PROCEDURE IF EXISTS add_column_if_not_exists;

SELECT 'Phase 2c: Timestamp columns added successfully' AS result;
