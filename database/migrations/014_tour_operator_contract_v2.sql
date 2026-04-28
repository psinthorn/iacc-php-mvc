-- ============================================================
-- Migration 014: Tour Operator Contract v2
-- Many-to-many contracts, season rates, agent registration,
-- product sync, audit trail.
-- Date: 2026-04-28
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/014_tour_operator_contract_v2.sql
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- 1. tour_operator_agents — Agent registration & approval
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
-- 2. tour_contract_agents — Many-to-many contract ↔ agent
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
-- 3. tour_operator_agent_products — Synced product catalog
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
-- 4. tour_contract_sync_log — Audit trail
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
-- 5. ALTER contract_rate — Add season support
-- ────────────────────────────────────────────────────────────
ALTER TABLE `contract_rate`
    ADD COLUMN `season_name` VARCHAR(100) DEFAULT NULL
        COMMENT 'e.g. High Season, Low Season, Peak — NULL = base/default rate'
        AFTER `rate_type`,
    ADD COLUMN `season_start` DATE DEFAULT NULL
        COMMENT 'Season period start date'
        AFTER `season_name`,
    ADD COLUMN `season_end` DATE DEFAULT NULL
        COMMENT 'Season period end date'
        AFTER `season_start`,
    ADD COLUMN `priority` INT(11) NOT NULL DEFAULT 0
        COMMENT 'Higher = checked first when seasons overlap'
        AFTER `season_end`;

-- Drop old unique key and add new one including season
ALTER TABLE `contract_rate`
    DROP INDEX `idx_cr_agent_model`,
    ADD UNIQUE KEY `idx_cr_contract_model_season`
        (`contract_id`, `model_id`, `season_name`);

-- ────────────────────────────────────────────────────────────
-- 6. ALTER agent_contracts — Support operator-level contracts
-- ────────────────────────────────────────────────────────────
-- Drop the FK constraint first, then modify column
ALTER TABLE `agent_contracts`
    DROP FOREIGN KEY `fk_ac_agent`;

ALTER TABLE `agent_contracts`
    MODIFY COLUMN `agent_company_id` INT(11) DEFAULT NULL
        COMMENT 'FK company.id — NULL for v2 operator-level contracts',
    ADD COLUMN `is_operator_level` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = v2 operator-level contract (agents via tour_contract_agents)'
        AFTER `is_default`;

-- ────────────────────────────────────────────────────────────
-- 7. ALTER company_modules — Default contract pointer
-- ────────────────────────────────────────────────────────────
ALTER TABLE `company_modules`
    ADD COLUMN `default_contract_id` INT(11) DEFAULT NULL
        COMMENT 'FK agent_contracts.id — default contract for new agents'
        AFTER `valid_to`;

-- ────────────────────────────────────────────────────────────
-- 8. Backfill: set season_name on existing contract_rate rows
-- ────────────────────────────────────────────────────────────
-- Existing rows become the "default" (base) rate — no season period
-- They keep season_name=NULL which means "base rate, always applies"
-- No data change needed — NULL season_name = default fallback

-- ────────────────────────────────────────────────────────────
-- 9. Create tour_operator_agents records from existing tour_agent_profiles
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
-- 10. Create tour_contract_agents records from existing agent_contracts
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
