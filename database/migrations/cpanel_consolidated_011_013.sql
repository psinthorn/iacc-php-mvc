-- ============================================================
-- Consolidated Migration: Contract Rate Redesign + Agent Contracts
-- Combines migrations 011, 012, 013 + tour_agent_profiles columns
-- For cPanel import: run via phpMyAdmin or mysql CLI
-- ============================================================

-- ============================================================
-- PART 1: tour_agent_profiles — add contact columns
-- ============================================================
ALTER TABLE `tour_agent_profiles`
  ADD COLUMN `contact_person` VARCHAR(200) DEFAULT NULL AFTER `contact_whatsapp`,
  ADD COLUMN `contact_mobile` VARCHAR(50) DEFAULT NULL AFTER `contact_person`,
  ADD COLUMN `contact_email` VARCHAR(200) DEFAULT NULL AFTER `contact_mobile`,
  ADD COLUMN `website` VARCHAR(500) DEFAULT NULL AFTER `contact_email`;

-- ============================================================
-- PART 2: contract_rate redesign (011)
-- ============================================================

-- Drop FK constraints
ALTER TABLE `contract_rate` DROP FOREIGN KEY `fk_cr_vendor`;
ALTER TABLE `contract_rate` DROP FOREIGN KEY `fk_cr_customer`;

-- Rename customer_id → agent_company_id
ALTER TABLE `contract_rate` CHANGE COLUMN `customer_id` `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent/customer';

-- Add Thai/Foreigner × Adult/Child columns + entrance fees
ALTER TABLE `contract_rate`
  ADD COLUMN `rate_type` ENUM('net_rate','percentage') NOT NULL DEFAULT 'net_rate' COMMENT 'Net rate (THB) or percentage (%)' AFTER `model_id`,
  ADD COLUMN `adult_thai` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `rate_type`,
  ADD COLUMN `adult_foreigner` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `adult_thai`,
  ADD COLUMN `child_thai` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `adult_foreigner`,
  ADD COLUMN `child_foreigner` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `child_thai`,
  ADD COLUMN `entrance_adult_thai` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '0 = no entrance fee' AFTER `child_foreigner`,
  ADD COLUMN `entrance_adult_foreigner` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `entrance_adult_thai`,
  ADD COLUMN `entrance_child_thai` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `entrance_adult_foreigner`,
  ADD COLUMN `entrance_child_foreigner` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `entrance_child_thai`;

-- Migrate old rate_label data → new columns
UPDATE `contract_rate` cr
  INNER JOIN (
    SELECT company_id, agent_company_id, model_id,
      MAX(CASE WHEN rate_label = 'adult' THEN rate_amount ELSE 0 END) AS v_adult,
      MAX(CASE WHEN rate_label = 'child' THEN rate_amount ELSE 0 END) AS v_child
    FROM `contract_rate`
    WHERE rate_label = 'adult'
    GROUP BY company_id, agent_company_id, model_id
  ) pivot ON cr.company_id = pivot.company_id 
         AND cr.agent_company_id = pivot.agent_company_id 
         AND IFNULL(cr.model_id, 0) = IFNULL(pivot.model_id, 0)
SET cr.adult_thai = pivot.v_adult,
    cr.adult_foreigner = pivot.v_adult
WHERE cr.rate_label = 'adult';

UPDATE `contract_rate` cr
  INNER JOIN (
    SELECT company_id, agent_company_id, model_id,
      MAX(CASE WHEN rate_label = 'child' THEN rate_amount ELSE 0 END) AS v_child
    FROM `contract_rate`
    WHERE rate_label = 'child'
    GROUP BY company_id, agent_company_id, model_id
  ) pivot ON cr.company_id = pivot.company_id 
         AND cr.agent_company_id = pivot.agent_company_id 
         AND IFNULL(cr.model_id, 0) = IFNULL(pivot.model_id, 0)
SET cr.child_thai = pivot.v_child,
    cr.child_foreigner = pivot.v_child
WHERE cr.rate_label = 'adult';

-- Remove collapsed rows
DELETE FROM `contract_rate` WHERE rate_label IN ('child', 'full_moon');

-- Drop old columns
ALTER TABLE `contract_rate`
  DROP COLUMN `rate_label`,
  DROP COLUMN `rate_amount`,
  DROP COLUMN `min_quantity`,
  DROP COLUMN `vendor_id`;

-- Re-add FK
ALTER TABLE `contract_rate`
  ADD CONSTRAINT `fk_cr_agent` FOREIGN KEY (`agent_company_id`) REFERENCES `company` (`id`);

-- Unique constraint
ALTER TABLE `contract_rate`
  ADD UNIQUE KEY `idx_cr_agent_model` (`company_id`, `agent_company_id`, `model_id`);

-- ============================================================
-- PART 3: Default fallback fields (012)
-- ============================================================

ALTER TABLE `contract_rate`
  ADD COLUMN `adult_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default adult rate — fallback when Thai/Foreigner is 0' AFTER `rate_type`,
  ADD COLUMN `child_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default child rate — fallback when Thai/Foreigner is 0' AFTER `adult_default`;

ALTER TABLE `contract_rate`
  ADD COLUMN `entrance_adult_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default entrance adult — fallback when Thai/Foreigner is 0' AFTER `child_foreigner`,
  ADD COLUMN `entrance_child_default` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Default entrance child — fallback when Thai/Foreigner is 0' AFTER `entrance_adult_default`;

-- Backfill defaults from Thai values
UPDATE `contract_rate`
SET `adult_default` = `adult_thai`,
    `child_default` = `child_thai`,
    `entrance_adult_default` = `entrance_adult_thai`,
    `entrance_child_default` = `entrance_child_thai`
WHERE `deleted_at` IS NULL;

-- ============================================================
-- PART 4: Agent Contracts module (013)
-- ============================================================

-- Create agent_contracts table
CREATE TABLE IF NOT EXISTS `agent_contracts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `company_id` INT(11) NOT NULL COMMENT 'Tenant FK',
    `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent',
    `contract_number` VARCHAR(50) NOT NULL COMMENT 'Auto: CTR-YYYY-NNNN',
    `contract_name` VARCHAR(200) DEFAULT NULL,
    `status` ENUM('draft','active','expired','cancelled') NOT NULL DEFAULT 'draft',
    `valid_from` DATE DEFAULT NULL,
    `valid_to` DATE DEFAULT NULL,
    `payment_terms` VARCHAR(200) DEFAULT NULL COMMENT 'e.g. Net 30 days',
    `credit_days` INT(11) NOT NULL DEFAULT 0,
    `deposit_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Deposit percentage',
    `conditions` TEXT COMMENT 'Special terms/conditions',
    `notes` TEXT,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = auto-created default contract',
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_contract_number` (`company_id`, `contract_number`),
    KEY `idx_agent` (`company_id`, `agent_company_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_ac_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
    CONSTRAINT `fk_ac_agent` FOREIGN KEY (`agent_company_id`) REFERENCES `company` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create agent_contract_types junction table
CREATE TABLE IF NOT EXISTS `agent_contract_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL COMMENT 'FK agent_contracts.id',
    `type_id` INT(11) NOT NULL COMMENT 'FK type.id (product type)',
    `company_id` INT(11) NOT NULL COMMENT 'Denormalized for tenant queries',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_contract_type` (`contract_id`, `type_id`),
    KEY `idx_company_type` (`company_id`, `type_id`),
    CONSTRAINT `fk_act_contract` FOREIGN KEY (`contract_id`) REFERENCES `agent_contracts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_act_type` FOREIGN KEY (`type_id`) REFERENCES `type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add contract_id to contract_rate
ALTER TABLE `contract_rate`
    ADD COLUMN `contract_id` INT(11) DEFAULT NULL COMMENT 'FK agent_contracts.id' AFTER `id`;

-- Data migration: create default contracts for each existing agent
DELIMITER //
CREATE PROCEDURE migrate_contract_rates()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_company_id INT;
    DECLARE v_agent_id INT;
    DECLARE v_contract_id INT;
    DECLARE v_seq INT DEFAULT 0;
    DECLARE v_year CHAR(4);

    DECLARE cur CURSOR FOR
        SELECT DISTINCT company_id, agent_company_id
        FROM contract_rate
        WHERE deleted_at IS NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SET v_year = YEAR(NOW());

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_company_id, v_agent_id;
        IF done THEN LEAVE read_loop; END IF;

        SET v_seq = v_seq + 1;

        INSERT INTO agent_contracts
            (company_id, agent_company_id, contract_number, contract_name, status,
             valid_from, valid_to, is_default)
        VALUES
            (v_company_id, v_agent_id,
             CONCAT('CTR-', v_year, '-', LPAD(v_seq, 4, '0')),
             'Default Contract', 'active',
             '2026-01-01', '2026-12-31', 1);

        SET v_contract_id = LAST_INSERT_ID();

        INSERT IGNORE INTO agent_contract_types (contract_id, type_id, company_id)
        SELECT DISTINCT v_contract_id, m.type_id, v_company_id
        FROM contract_rate cr
        JOIN model m ON cr.model_id = m.id
        WHERE cr.company_id = v_company_id
          AND cr.agent_company_id = v_agent_id
          AND cr.model_id IS NOT NULL
          AND cr.deleted_at IS NULL;

        INSERT IGNORE INTO agent_contract_types (contract_id, type_id, company_id)
        SELECT DISTINCT v_contract_id, t.id, v_company_id
        FROM type t
        WHERE t.company_id = v_company_id
          AND t.deleted_at IS NULL
          AND t.id NOT IN (
              SELECT act.type_id FROM agent_contract_types act WHERE act.contract_id = v_contract_id
          );

        UPDATE contract_rate
        SET contract_id = v_contract_id
        WHERE company_id = v_company_id
          AND agent_company_id = v_agent_id
          AND deleted_at IS NULL;

    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

CALL migrate_contract_rates();
DROP PROCEDURE IF EXISTS migrate_contract_rates;

-- Add FK + index for contract_id
ALTER TABLE `contract_rate`
    ADD CONSTRAINT `fk_cr_contract` FOREIGN KEY (`contract_id`) REFERENCES `agent_contracts` (`id`);

ALTER TABLE `contract_rate`
    ADD KEY `idx_cr_contract_model` (`contract_id`, `model_id`);
