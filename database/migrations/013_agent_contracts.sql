-- ============================================================
-- Migration 013: Agent Contracts Module
-- Creates agent_contracts + agent_contract_types tables,
-- adds contract_id FK to contract_rate, migrates existing data.
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/013_agent_contracts.sql
-- ============================================================

-- 1. Create agent_contracts table
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

-- 2. Create agent_contract_types junction table
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

-- 3. Add contract_id column to contract_rate
ALTER TABLE `contract_rate`
    ADD COLUMN `contract_id` INT(11) DEFAULT NULL COMMENT 'FK agent_contracts.id' AFTER `id`;

-- 4. Data migration: create default contracts for each existing agent
--    Use a stored procedure for MySQL 5.7 compatibility
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

        -- Create a default contract for this agent
        INSERT INTO agent_contracts
            (company_id, agent_company_id, contract_number, contract_name, status,
             valid_from, valid_to, is_default)
        VALUES
            (v_company_id, v_agent_id,
             CONCAT('CTR-', v_year, '-', LPAD(v_seq, 4, '0')),
             'Default Contract', 'active',
             '2026-01-01', '2026-12-31', 1);

        SET v_contract_id = LAST_INSERT_ID();

        -- Link all product types that this agent has rates for
        INSERT IGNORE INTO agent_contract_types (contract_id, type_id, company_id)
        SELECT DISTINCT v_contract_id, m.type_id, v_company_id
        FROM contract_rate cr
        JOIN model m ON cr.model_id = m.id
        WHERE cr.company_id = v_company_id
          AND cr.agent_company_id = v_agent_id
          AND cr.model_id IS NOT NULL
          AND cr.deleted_at IS NULL;

        -- Also add all other types the company has (default contract = all types)
        INSERT IGNORE INTO agent_contract_types (contract_id, type_id, company_id)
        SELECT DISTINCT v_contract_id, t.id, v_company_id
        FROM type t
        WHERE t.company_id = v_company_id
          AND t.deleted_at IS NULL
          AND t.id NOT IN (
              SELECT act.type_id FROM agent_contract_types act WHERE act.contract_id = v_contract_id
          );

        -- Update contract_rate rows to point to this contract
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

-- 5. Add FK constraint for contract_id
ALTER TABLE `contract_rate`
    ADD CONSTRAINT `fk_cr_contract` FOREIGN KEY (`contract_id`) REFERENCES `agent_contracts` (`id`);

-- 6. Add index on contract_id + model_id (for fast lookups within a contract)
ALTER TABLE `contract_rate`
    ADD KEY `idx_cr_contract_model` (`contract_id`, `model_id`);
