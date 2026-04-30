-- ============================================================================
-- Migration 018: Tour Operator Contract Management v2 — COMBINED
-- ============================================================================
-- Single-file consolidation of migrations 014-017 for one-shot deployment to
-- staging or production. Run this INSTEAD of 014, 015, 016, 017 individually.
--
-- Date: 2026-04-30
-- Run via phpMyAdmin → Import, or via CLI:
--   mysql -u USER -p DBNAME < database/migrations/018_tour_contract_v2_combined.sql
--
-- WHAT THIS DOES:
--   PART A — Schema (014):  4 new tables + ALTERs to support v2 contracts
--   PART B — Documents (015): tour_operator_documents table
--   PART C — Promote (016):  flip is_operator_level=1 on existing v1 contracts
--   PART D — Consolidate (017): collapse company 165's 46 placeholder contracts
--                                into 2 (Default + Season Rate). SCOPED to 165.
--
-- IMPORTANT — PART D is dev-data-specific:
--   PART D acts only on company_id = 165 and HARD DELETES 45 contracts that
--   were auto-created placeholders in dev. If your production has a similar
--   pattern for a different company, edit the company_id in PART D before
--   running. To skip PART D entirely, comment out the `\i PART D` block at
--   the bottom (or delete those lines) — Parts A/B/C alone produce a working
--   v2 system; PART D is a one-time cleanup.
--
-- AFTER RUNNING:
--   curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--
-- ROLLBACK STRATEGY:
--   - PART A/B can be reversed by DROPping the new tables + reverting ALTERs
--     (but you'll lose any V2 data). Take a full DB backup first.
--   - PART C: UPDATE agent_contracts SET is_operator_level = 0 WHERE is_operator_level = 1;
--   - PART D: NOT REVERSIBLE — restore from backup if needed.
-- ============================================================================


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART A — Schema for Contract v2 (was migration 014)                      ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

-- ────────────────────────────────────────────────────────────
-- A1. tour_operator_agents — Agent registration & approval
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tour_operator_agents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `operator_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the tour operator',
    `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent',
    `status` ENUM('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending',
    `registered_via` ENUM('self','invitation','manual') NOT NULL DEFAULT 'manual',
    `invitation_token` VARCHAR(64) DEFAULT NULL COMMENT 'For email invitation verification',
    `invitation_expires` DATETIME DEFAULT NULL,
    `default_contract_id` INT(11) DEFAULT NULL COMMENT 'FK agent_contracts.id — assigned on approval',
    `approved_at` DATETIME DEFAULT NULL,
    `approved_by` INT(11) DEFAULT NULL COMMENT 'FK user.usr_id',
    `notes` TEXT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_operator_agent` (`operator_company_id`, `agent_company_id`),
    KEY `idx_status` (`operator_company_id`, `status`),
    KEY `idx_invitation` (`invitation_token`),
    KEY `idx_agent` (`agent_company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Agent registration and approval for tour operators';

-- ────────────────────────────────────────────────────────────
-- A2. tour_contract_agents — Many-to-many contract ↔ agent
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tour_contract_agents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL COMMENT 'FK agent_contracts.id',
    `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent',
    `company_id` INT(11) NOT NULL COMMENT 'FK company.id — denormalized operator tenant',
    `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` INT(11) DEFAULT NULL COMMENT 'FK user.usr_id',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_contract_agent` (`contract_id`, `agent_company_id`),
    KEY `idx_agent_company` (`agent_company_id`, `company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Many-to-many: which agents are assigned to which contracts';

-- ────────────────────────────────────────────────────────────
-- A3. tour_operator_agent_products — Synced product catalog
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tour_operator_agent_products` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `operator_company_id` INT(11) NOT NULL COMMENT 'FK company.id — product owner',
    `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — agent who can sell',
    `contract_id` INT(11) NOT NULL COMMENT 'FK agent_contracts.id — source contract',
    `model_id` INT(11) NOT NULL COMMENT 'FK model.id — the product',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `synced_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last sync timestamp',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_agent_contract_model` (`agent_company_id`, `contract_id`, `model_id`),
    KEY `idx_operator` (`operator_company_id`),
    KEY `idx_agent_active` (`agent_company_id`, `is_active`),
    KEY `idx_contract` (`contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Products synced from contracts to agent catalogs — bridge table, not duplicating model data';

-- ────────────────────────────────────────────────────────────
-- A4. tour_contract_sync_log — Audit trail
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tour_contract_sync_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL COMMENT 'Operator company_id',
    `contract_id` INT(11) NOT NULL,
    `agent_company_id` INT(11) DEFAULT NULL COMMENT 'NULL = all agents',
    `action` ENUM('sync','resync','product_added','product_removed',
                  'rate_updated','contract_assigned','contract_unassigned') NOT NULL,
    `details` TEXT DEFAULT NULL COMMENT 'JSON: additional context',
    `triggered_by` ENUM('auto','operator','agent','api','system') NOT NULL DEFAULT 'auto',
    `products_added` INT(11) NOT NULL DEFAULT 0,
    `products_removed` INT(11) NOT NULL DEFAULT 0,
    `created_by` INT(11) DEFAULT NULL COMMENT 'FK user.usr_id',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_company_contract` (`company_id`, `contract_id`),
    KEY `idx_agent` (`agent_company_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for contract sync operations';

-- ────────────────────────────────────────────────────────────
-- A5. ALTER contract_rate — Add season support
-- ────────────────────────────────────────────────────────────
-- Idempotent: only add columns if they don't exist
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                      AND COLUMN_NAME = 'season_name');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `contract_rate`
        ADD COLUMN `season_name` VARCHAR(100) DEFAULT NULL
            COMMENT ''e.g. High Season, Low Season, Peak — NULL = base/default rate''
            AFTER `rate_type`,
        ADD COLUMN `season_start` DATE DEFAULT NULL
            COMMENT ''Season period start date''
            AFTER `season_name`,
        ADD COLUMN `season_end` DATE DEFAULT NULL
            COMMENT ''Season period end date''
            AFTER `season_start`,
        ADD COLUMN `priority` INT(11) NOT NULL DEFAULT 0
            COMMENT ''Higher = checked first when seasons overlap''
            AFTER `season_end`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop old unique key (if it exists) and add new one including season
SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                      AND INDEX_NAME = 'idx_cr_agent_model');
SET @sql := IF(@idx_exists > 0, 'ALTER TABLE `contract_rate` DROP INDEX `idx_cr_agent_model`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(*) FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                      AND INDEX_NAME = 'idx_cr_contract_model_season');
SET @sql := IF(@idx_exists = 0,
    'ALTER TABLE `contract_rate` ADD UNIQUE KEY `idx_cr_contract_model_season` (`contract_id`, `model_id`, `season_name`)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Drop fk_cr_agent so v2 contracts can use agent_company_id=0 (sentinel for "all agents")
-- Required by ContractSyncService and saveSeasonRates(). See PART D step 0 in original 017.
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_rate'
                     AND CONSTRAINT_NAME = 'fk_cr_agent');
SET @sql := IF(@fk_exists > 0, 'ALTER TABLE contract_rate DROP FOREIGN KEY fk_cr_agent', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A6. ALTER agent_contracts — Support operator-level contracts
-- ────────────────────────────────────────────────────────────
-- Drop the FK constraint first (if exists), then modify column
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_contracts'
                     AND CONSTRAINT_NAME = 'fk_ac_agent');
SET @sql := IF(@fk_exists > 0, 'ALTER TABLE `agent_contracts` DROP FOREIGN KEY `fk_ac_agent`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add is_operator_level column if missing
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_contracts'
                      AND COLUMN_NAME = 'is_operator_level');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `agent_contracts`
        MODIFY COLUMN `agent_company_id` INT(11) DEFAULT NULL
            COMMENT ''FK company.id — NULL for v2 operator-level contracts'',
        ADD COLUMN `is_operator_level` TINYINT(1) NOT NULL DEFAULT 0
            COMMENT ''1 = v2 operator-level contract (agents via tour_contract_agents)''
            AFTER `is_default`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A7. ALTER company_modules — Default contract pointer
-- ────────────────────────────────────────────────────────────
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'company_modules'
                      AND COLUMN_NAME = 'default_contract_id');
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `company_modules`
        ADD COLUMN `default_contract_id` INT(11) DEFAULT NULL
            COMMENT ''FK agent_contracts.id — default contract for new agents''
            AFTER `valid_to`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ────────────────────────────────────────────────────────────
-- A8. Seed tour_operator_agents from existing tour_agent_profiles
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tour_operator_agents`
    (`operator_company_id`, `agent_company_id`, `status`, `registered_via`, `approved_at`)
SELECT
    tap.company_id,
    tap.company_ref_id,
    'approved',
    'manual',
    tap.created_at
FROM `tour_agent_profiles` tap
WHERE tap.deleted_at IS NULL
  AND tap.company_ref_id IS NOT NULL
  AND tap.company_id IS NOT NULL;

-- ────────────────────────────────────────────────────────────
-- A9. Seed tour_contract_agents from existing agent_contracts
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `tour_contract_agents`
    (`contract_id`, `agent_company_id`, `company_id`)
SELECT
    ac.id,
    ac.agent_company_id,
    ac.company_id
FROM `agent_contracts` ac
WHERE ac.deleted_at IS NULL
  AND ac.agent_company_id IS NOT NULL;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART B — Operator Documents (was migration 015)                          ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS `tour_operator_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `operator_company_id` INT(11) NOT NULL COMMENT 'FK company.id — owner',
    `contract_id` INT(11) DEFAULT NULL COMMENT 'FK agent_contracts.id — NULL if visible to all',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Original filename',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Server path under uploads/',
    `file_size` INT(11) NOT NULL DEFAULT 0 COMMENT 'Bytes',
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `category` ENUM('contract','brochure','terms','rate_sheet','other') NOT NULL DEFAULT 'other',
    `visibility` ENUM('all_agents','contract','operator_only') NOT NULL DEFAULT 'all_agents',
    `download_count` INT(11) NOT NULL DEFAULT 0,
    `uploaded_by` INT(11) DEFAULT NULL COMMENT 'FK user.usr_id',
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_operator` (`operator_company_id`, `deleted_at`),
    KEY `idx_contract` (`contract_id`),
    KEY `idx_visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Documents shared by tour operators with their agents';


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART C — Promote V1 → V2 (was migration 016)                             ║
-- ╚══════════════════════════════════════════════════════════════════════════╝
-- Flips is_operator_level=1 on existing v1 contracts (those with junction rows
-- created by PART A9). Idempotent.

UPDATE `agent_contracts` ac
INNER JOIN (
    SELECT DISTINCT contract_id
    FROM `tour_contract_agents`
) ca ON ac.id = ca.contract_id
SET ac.is_operator_level = 1
WHERE ac.is_operator_level = 0
  AND ac.deleted_at IS NULL;

UPDATE `company_modules` cm
INNER JOIN (
    SELECT company_id, MIN(id) AS default_id
    FROM `agent_contracts`
    WHERE is_default = 1
      AND is_operator_level = 1
      AND deleted_at IS NULL
    GROUP BY company_id
) ac ON cm.company_id = ac.company_id
SET cm.default_contract_id = ac.default_id
WHERE cm.module_key = 'tour_operator'
  AND cm.default_contract_id IS NULL;


-- ╔══════════════════════════════════════════════════════════════════════════╗
-- ║ PART D — Consolidate Company 165 (was migration 017)                     ║
-- ║                                                                          ║
-- ║ ⚠️  DEV-DATA-SPECIFIC. Only run if your production has the same          ║
-- ║    pattern (one operator with many duplicate placeholder contracts).     ║
-- ║    To skip, comment out this entire PART D block.                        ║
-- ║    To apply to a different company, change `165` to that company's ID.  ║
-- ╚══════════════════════════════════════════════════════════════════════════╝

-- Guard: only run if company 165 actually has the symptom (>5 v2 contracts)
SET @needs_consolidation := (
    SELECT COUNT(*) FROM agent_contracts
    WHERE company_id = 165 AND is_operator_level = 1 AND deleted_at IS NULL
);

-- If guard passes (>5 contracts), proceed with consolidation
-- NOTE: MySQL doesn't support IF blocks outside stored procedures,
-- so we use SET expressions with conditional INSERT/UPDATE WHERE clauses.
-- All statements below are no-ops if @needs_consolidation <= 5.

START TRANSACTION;

-- D1: Pick canonical contract (lowest id) — only if guard met
SET @canonical_id := IF(@needs_consolidation > 5, (
    SELECT MIN(id) FROM agent_contracts
    WHERE company_id = 165 AND is_operator_level = 1 AND deleted_at IS NULL
), 0);

-- D2: Move all agent assignments to the canonical contract
INSERT IGNORE INTO tour_contract_agents (contract_id, agent_company_id, company_id, assigned_at, assigned_by)
SELECT @canonical_id, tca.agent_company_id, tca.company_id, tca.assigned_at, tca.assigned_by
FROM tour_contract_agents tca
INNER JOIN agent_contracts ac ON tca.contract_id = ac.id
WHERE @canonical_id > 0
  AND ac.company_id = 165
  AND ac.is_operator_level = 1
  AND ac.id != @canonical_id;

-- D3: Update canonical contract metadata
UPDATE agent_contracts
SET contract_name = 'Default Contract',
    is_default = 1,
    valid_from = '2026-01-01',
    valid_to = '2026-12-31',
    notes = CONCAT(IFNULL(notes,''), '\n[018] Consolidated from v1-style per-agent contracts on ', NOW())
WHERE @canonical_id > 0 AND id = @canonical_id;

UPDATE company_modules
SET default_contract_id = @canonical_id
WHERE @canonical_id > 0
  AND company_id = 165 AND module_key = 'tour_operator';

-- D4: Pre-fill default-rate rows for canonical contract
INSERT INTO contract_rate
    (contract_id, company_id, agent_company_id, model_id, rate_type,
     adult_default, child_default,
     adult_thai, adult_foreigner, child_thai, child_foreigner,
     entrance_adult_default, entrance_child_default,
     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
     currency, valid_from, valid_to)
SELECT @canonical_id, 165, 0, m.id, 'net_rate',
       m.price, ROUND(m.price * 0.5, 2),
       0, 0, 0, 0,
       0, 0,
       0, 0, 0, 0,
       'THB', '2026-01-01', '2026-12-31'
FROM model m
INNER JOIN agent_contract_types act ON act.type_id = m.type_id
WHERE @canonical_id > 0
  AND act.contract_id = @canonical_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165
  AND NOT EXISTS (
      SELECT 1 FROM contract_rate cr
      WHERE cr.contract_id = @canonical_id
        AND cr.model_id = m.id
        AND cr.deleted_at IS NULL
  );

-- D5: Capture redundant contract IDs for cleanup (only if guard met)
DROP TEMPORARY TABLE IF EXISTS _redundant_contracts;
CREATE TEMPORARY TABLE _redundant_contracts (id INT PRIMARY KEY);
INSERT INTO _redundant_contracts (id)
SELECT id FROM agent_contracts
WHERE @canonical_id > 0
  AND company_id = 165 AND is_operator_level = 1 AND id != @canonical_id;

-- D6: HARD DELETE redundant contracts and their dependents (children first)
DELETE FROM tour_operator_agent_products WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM tour_contract_agents          WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM agent_contract_types          WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM contract_rate                 WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM tour_contract_sync_log        WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM agent_contracts               WHERE id          IN (SELECT id FROM _redundant_contracts);

DROP TEMPORARY TABLE _redundant_contracts;

-- D7: Create new "Season Rate Contract" (only if consolidation happened)
INSERT INTO agent_contracts
    (company_id, agent_company_id, contract_number, contract_name, status,
     valid_from, valid_to, payment_terms, credit_days, deposit_pct,
     conditions, notes, is_default, is_operator_level)
SELECT 165, NULL,
       CONCAT('CTR-2026-S', LPAD(IFNULL((SELECT MAX(id) FROM agent_contracts AS ac), 0)+1, 4, '0')),
       'Season Rate Contract', 'active',
       '2026-01-01', '2026-12-31', '', 0, 0,
       '', '[018] Auto-created with High/Green seasons. Assign agents and review rates.',
       0, 1
WHERE @canonical_id > 0
  AND NOT EXISTS (
      SELECT 1 FROM agent_contracts
      WHERE company_id = 165 AND contract_name = 'Season Rate Contract' AND deleted_at IS NULL
  );

SET @season_id := IF(@canonical_id > 0, (
    SELECT id FROM agent_contracts
    WHERE company_id = 165 AND contract_name = 'Season Rate Contract' AND deleted_at IS NULL
    ORDER BY id DESC LIMIT 1
), 0);

-- Copy product types to Season Rate Contract
INSERT INTO agent_contract_types (contract_id, type_id, company_id)
SELECT @season_id, type_id, company_id
FROM agent_contract_types
WHERE @season_id > 0
  AND contract_id = @canonical_id
  AND NOT EXISTS (
      SELECT 1 FROM agent_contract_types act2
      WHERE act2.contract_id = @season_id AND act2.type_id = agent_contract_types.type_id
  );

-- D8: Pre-fill High Season rates (1 Nov – 30 Apr)
INSERT INTO contract_rate
    (contract_id, company_id, agent_company_id, model_id, rate_type,
     season_name, season_start, season_end, priority,
     adult_default, child_default,
     adult_thai, adult_foreigner, child_thai, child_foreigner,
     entrance_adult_default, entrance_child_default,
     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
     currency, valid_from, valid_to)
SELECT @season_id, 165, 0, m.id, 'net_rate',
       'High Season', '2026-11-01', '2026-04-30', 10,
       m.price, ROUND(m.price * 0.5, 2),
       0, 0, 0, 0,
       0, 0,
       0, 0, 0, 0,
       'THB', '2026-01-01', '2026-12-31'
FROM model m
INNER JOIN agent_contract_types act ON act.type_id = m.type_id
WHERE @season_id > 0
  AND act.contract_id = @season_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165
  AND NOT EXISTS (
      SELECT 1 FROM contract_rate cr
      WHERE cr.contract_id = @season_id
        AND cr.model_id = m.id
        AND cr.season_name = 'High Season'
        AND cr.deleted_at IS NULL
  );

-- D8: Pre-fill Green/Low Season rates (1 May – 31 Oct)
INSERT INTO contract_rate
    (contract_id, company_id, agent_company_id, model_id, rate_type,
     season_name, season_start, season_end, priority,
     adult_default, child_default,
     adult_thai, adult_foreigner, child_thai, child_foreigner,
     entrance_adult_default, entrance_child_default,
     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
     currency, valid_from, valid_to)
SELECT @season_id, 165, 0, m.id, 'net_rate',
       'Green Season', '2026-05-01', '2026-10-31', 5,
       m.price, ROUND(m.price * 0.5, 2),
       0, 0, 0, 0,
       0, 0,
       0, 0, 0, 0,
       'THB', '2026-01-01', '2026-12-31'
FROM model m
INNER JOIN agent_contract_types act ON act.type_id = m.type_id
WHERE @season_id > 0
  AND act.contract_id = @season_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165
  AND NOT EXISTS (
      SELECT 1 FROM contract_rate cr
      WHERE cr.contract_id = @season_id
        AND cr.model_id = m.id
        AND cr.season_name = 'Green Season'
        AND cr.deleted_at IS NULL
  );

COMMIT;


-- ============================================================================
-- POST-MIGRATION
-- ============================================================================
-- 1. Trigger product re-sync to populate tour_operator_agent_products:
--      curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--
-- 2. Verify:
--      SELECT id, company_id, contract_name, is_default, is_operator_level
--        FROM agent_contracts WHERE is_operator_level = 1 ORDER BY company_id, id;
--      SELECT contract_id, COUNT(*) AS rates
--        FROM contract_rate WHERE deleted_at IS NULL GROUP BY contract_id;
--      SELECT contract_id, COUNT(*) AS agents
--        FROM tour_contract_agents GROUP BY contract_id;
--
-- 3. Login as super admin → Admin → Tour Operator Platform → confirm operators show up.
-- ============================================================================
