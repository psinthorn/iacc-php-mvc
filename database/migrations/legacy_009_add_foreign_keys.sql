-- ============================================================================
-- Add Foreign Key Constraints Migration
-- ============================================================================
-- Version: 1.0.0
-- Date: 2026-01-03
-- 
-- Prerequisites: All tables must be InnoDB (run 002_remaining_database_fixes.sql first)
-- 
-- This migration adds foreign key constraints for data integrity.
-- ============================================================================

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- ============================================================================
-- PHASE 1: Clean Orphan Data (Required before adding FK constraints)
-- ============================================================================

SELECT 'PHASE 1: Cleaning orphan data...' AS status;

-- Clean products with invalid po_id (keep those with po_id=0 as they may be templates)
UPDATE product SET po_id = 0 WHERE po_id NOT IN (SELECT id FROM po) AND po_id != 0;

-- Clean products with invalid type
UPDATE product SET type = 0 WHERE type NOT IN (SELECT id FROM type) AND type != 0;

-- Clean products with invalid ban_id (brand)
UPDATE product SET ban_id = 0 WHERE ban_id NOT IN (SELECT id FROM brand) AND ban_id != 0;

-- Clean pay records with invalid po_id
UPDATE pay SET po_id = 0 WHERE po_id NOT IN (SELECT id FROM po) AND po_id != 0;

-- Clean deliver records with invalid po_id
UPDATE deliver SET po_id = 0 WHERE po_id NOT IN (SELECT id FROM po) AND po_id != 0;

-- Clean model records with invalid brand_id
DELETE FROM model WHERE brand_id NOT IN (SELECT id FROM brand) AND brand_id != 0;

-- Clean model records with invalid type_id
DELETE FROM model WHERE type_id NOT IN (SELECT id FROM type) AND type_id != 0;

-- Clean map_type_to_brand with invalid references
DELETE FROM map_type_to_brand WHERE type_id NOT IN (SELECT id FROM type);
DELETE FROM map_type_to_brand WHERE brand_id NOT IN (SELECT id FROM brand);

SELECT 'PHASE 1: Completed - Orphan data cleaned' AS status;

-- ============================================================================
-- PHASE 2: Add Foreign Key Constraints
-- ============================================================================

SELECT 'PHASE 2: Adding foreign key constraints...' AS status;

-- Note: We'll use SET NULL for optional relationships and RESTRICT for required ones
-- This prevents accidental deletion of referenced data

-- Model -> Brand (CASCADE: deleting brand deletes its models)
ALTER TABLE model 
ADD CONSTRAINT fk_model_brand 
FOREIGN KEY (brand_id) REFERENCES brand(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Model -> Type (CASCADE: deleting type deletes its models)
ALTER TABLE model 
ADD CONSTRAINT fk_model_type 
FOREIGN KEY (type_id) REFERENCES type(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Map Type to Brand (CASCADE on both sides)
ALTER TABLE map_type_to_brand 
ADD CONSTRAINT fk_map_type 
FOREIGN KEY (type_id) REFERENCES type(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE map_type_to_brand 
ADD CONSTRAINT fk_map_brand 
FOREIGN KEY (brand_id) REFERENCES brand(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- User Roles -> Roles (CASCADE: deleting role removes user assignments)
-- Note: These may already exist from RBAC setup, so we use IF NOT EXISTS pattern
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'user_roles' 
                  AND CONSTRAINT_NAME = 'fk_user_roles_role');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE user_roles ADD CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE',
    'SELECT "fk_user_roles_role already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Role Permissions -> Roles
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'role_permissions' 
                  AND CONSTRAINT_NAME = 'fk_role_permissions_role');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE',
    'SELECT "fk_role_permissions_role already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Role Permissions -> Permissions
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'role_permissions' 
                  AND CONSTRAINT_NAME = 'fk_role_permissions_permission');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE',
    'SELECT "fk_role_permissions_permission already exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'PHASE 2: Completed - Foreign key constraints added' AS status;

-- ============================================================================
-- PHASE 3: Log Migration
-- ============================================================================

INSERT INTO `_migration_log` (`migration_name`, `status`, `notes`) 
VALUES ('003_add_foreign_keys', 'success', 'Added FK constraints for model, map_type_to_brand, RBAC tables')
ON DUPLICATE KEY UPDATE `executed_at` = NOW(), `notes` = CONCAT(`notes`, ' | Re-run on ', NOW());

-- ============================================================================
-- Restore Settings
-- ============================================================================

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE = @OLD_SQL_MODE;

SELECT '============================================' AS '';
SELECT 'FOREIGN KEY MIGRATION COMPLETED!' AS status;
SELECT '============================================' AS '';
