-- ============================================================
-- Phase 2c ROLLBACK: Remove timestamp columns added by migration
-- Date: 2026-03-29
-- USE ONLY if migration needs to be reversed
-- ============================================================

-- GROUP 1: Tables that had NO timestamp columns before migration
ALTER TABLE authorize        DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE billing_items    DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE gen_serial       DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE keep_log         DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE login_attempts   DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE map_type_to_brand DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE permissions      DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE receive          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE role_permissions DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE sendoutitem      DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE store            DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE store_sale       DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE tmp_product      DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE `user`           DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE user_roles       DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE _migration_log   DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;

-- GROUP 2: Tables that only had deleted_at - remove created_at and updated_at
ALTER TABLE brand            DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE category         DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE company          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE company_addr     DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE company_credit   DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE deliver          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE iv               DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE model            DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE pay              DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE payment          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE po               DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE pr               DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE product          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE type             DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE voucher          DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;

-- GROUP 3: Tables that only had created_at - remove updated_at
ALTER TABLE ai_action_log          DROP COLUMN IF EXISTS updated_at;
ALTER TABLE ai_conversations       DROP COLUMN IF EXISTS updated_at;
ALTER TABLE ai_sessions            DROP COLUMN IF EXISTS updated_at;
ALTER TABLE api_usage_logs         DROP COLUMN IF EXISTS updated_at;
ALTER TABLE api_webhook_deliveries DROP COLUMN IF EXISTS updated_at;
ALTER TABLE audit_logs             DROP COLUMN IF EXISTS updated_at;
ALTER TABLE billing                DROP COLUMN IF EXISTS updated_at;
ALTER TABLE journal_entries        DROP COLUMN IF EXISTS updated_at;
ALTER TABLE password_resets        DROP COLUMN IF EXISTS updated_at;
ALTER TABLE remember_tokens        DROP COLUMN IF EXISTS updated_at;

SELECT 'Phase 2c: Rollback complete - timestamp columns removed' AS result;
