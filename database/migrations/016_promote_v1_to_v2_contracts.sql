-- ============================================================================
-- Migration 016: Promote V1 contracts to V2 operator-level contracts
-- Date: 2026-04-28
--
-- The Phase 1 migration (014) created junction rows in tour_contract_agents
-- for every existing v1 agent_contract, but kept is_operator_level = 0 for
-- backward compatibility.
--
-- This migration flips the flag so the existing contracts show in the
-- new V2 operator contract list.
--
-- AFTER RUNNING:
--   Trigger sync to populate tour_operator_agent_products:
--     curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--
-- ROLLBACK:
--   UPDATE agent_contracts SET is_operator_level = 0 WHERE is_operator_level = 1;
-- ============================================================================

-- Promote all v1 contracts (those with junction rows) to v2 operator-level.
-- Idempotent: only updates rows that have a junction record AND are still v1.
UPDATE `agent_contracts` ac
INNER JOIN (
    SELECT DISTINCT contract_id
    FROM `tour_contract_agents`
) ca ON ac.id = ca.contract_id
SET ac.is_operator_level = 1
WHERE ac.is_operator_level = 0
  AND ac.deleted_at IS NULL;

-- Pick each company's existing default contract as the company_modules.default_contract_id
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

-- Verification queries (run manually after migration):
--   SELECT COUNT(*) AS promoted FROM agent_contracts WHERE is_operator_level = 1;
--   SELECT COUNT(*) AS with_default FROM company_modules WHERE module_key = 'tour_operator' AND default_contract_id IS NOT NULL;
