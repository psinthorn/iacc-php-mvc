-- ============================================================
-- Migration 011: Redesign contract_rate for dual pricing
-- Thai/Foreigner × Adult/Child + optional entrance fees per model
-- Run: docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/011_contract_rate_redesign.sql
-- ============================================================

-- 1. Drop FK constraints that will be affected
ALTER TABLE `contract_rate` DROP FOREIGN KEY `fk_cr_vendor`;
ALTER TABLE `contract_rate` DROP FOREIGN KEY `fk_cr_customer`;

-- 2. Rename customer_id → agent_company_id
ALTER TABLE `contract_rate` CHANGE COLUMN `customer_id` `agent_company_id` INT(11) NOT NULL COMMENT 'FK company.id — the agent/customer';

-- 3. Add new columns
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

-- 4. Migrate existing data: map old rate_label → new columns
--    Strategy: group by (company_id, agent_company_id, model_id), pivot rate_label values
--    For old data: adult → adult_thai (treat old rates as Thai rates; foreigner = same)
UPDATE `contract_rate` cr
  INNER JOIN (
    SELECT company_id, agent_company_id, model_id,
      MAX(CASE WHEN rate_label = 'adult' THEN rate_amount ELSE 0 END) AS v_adult,
      MAX(CASE WHEN rate_label = 'child' THEN rate_amount ELSE 0 END) AS v_child,
      MAX(CASE WHEN rate_label = 'full_moon' THEN rate_amount ELSE 0 END) AS v_full_moon
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

-- 5. Now collapse: keep only the 'adult' row (which now has all data), delete child/full_moon rows
DELETE FROM `contract_rate` WHERE rate_label IN ('child', 'full_moon');

-- 6. Drop old columns
ALTER TABLE `contract_rate`
  DROP COLUMN `rate_label`,
  DROP COLUMN `rate_amount`,
  DROP COLUMN `min_quantity`,
  DROP COLUMN `vendor_id`;

-- 7. Re-add FK for agent_company_id
ALTER TABLE `contract_rate`
  ADD CONSTRAINT `fk_cr_agent` FOREIGN KEY (`agent_company_id`) REFERENCES `company` (`id`);

-- 8. Add unique constraint (one rate per agent per model)
ALTER TABLE `contract_rate`
  ADD UNIQUE KEY `idx_cr_agent_model` (`company_id`, `agent_company_id`, `model_id`);
