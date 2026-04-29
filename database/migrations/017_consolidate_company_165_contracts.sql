-- ============================================================================
-- Migration 017: Consolidate Company 165's 46 V1-promoted contracts to 2
-- Date: 2026-04-29
--
-- BEFORE:
--   46 "Default Contract" rows for company 165 (one per agent, leftover from V1
--   per-agent model). All identical: same period, same types, no rates.
--
-- AFTER:
--   1. "Default Contract"      — net rate, full year, all 45 agents assigned,
--                                rates pre-filled from model.price
--   2. "Season Rate Contract"  — full year with 2 seasons (High/Green),
--                                rates pre-filled from model.price for both,
--                                no agents assigned (operator picks later)
--
-- SAFETY:
--   - Scoped strictly to company_id = 165
--   - HARD DELETE the 45 redundant contracts (per user request)
--   - Booking history is safe: the only 2 bookings with contract_rate_id
--     reference orphaned ID 0 (no real rate row at risk)
--   - Wrapped in transaction; rollback on any failure
--
-- ROLLBACK: cannot be undone. Take a DB backup first.
-- ============================================================================

-- ─── STEP 0: Schema fix — drop FK that blocks v2 "all agents" rates ───
-- v2 rates use agent_company_id = 0 to mean "applies to every agent assigned
-- to the contract". The existing FK to company.id makes that impossible.
-- Phase 2's saveSeasonRates() already uses 0; this completes that design.
-- DDL outside the transaction (auto-commits in MySQL).
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contract_rate'
      AND CONSTRAINT_NAME = 'fk_cr_agent'
);
SET @sql := IF(@fk_exists > 0, 'ALTER TABLE contract_rate DROP FOREIGN KEY fk_cr_agent', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

START TRANSACTION;

-- ─── STEP 1: Identify canonical contract (lowest ID) ───
SET @canonical_id := (
    SELECT MIN(id) FROM agent_contracts
    WHERE company_id = 165 AND is_operator_level = 1 AND deleted_at IS NULL
);

-- ─── STEP 2: Move all agent assignments to the canonical contract ───
-- INSERT IGNORE handles the case where canonical already had one agent
INSERT IGNORE INTO tour_contract_agents (contract_id, agent_company_id, company_id, assigned_at, assigned_by)
SELECT @canonical_id, tca.agent_company_id, tca.company_id, tca.assigned_at, tca.assigned_by
FROM tour_contract_agents tca
INNER JOIN agent_contracts ac ON tca.contract_id = ac.id
WHERE ac.company_id = 165
  AND ac.is_operator_level = 1
  AND ac.id != @canonical_id;

-- ─── STEP 3: Update canonical contract metadata ───
UPDATE agent_contracts
SET contract_name = 'Default Contract',
    is_default = 1,
    valid_from = '2026-01-01',
    valid_to = '2026-12-31',
    notes = CONCAT(IFNULL(notes,''), '\n[017] Consolidated from 46 v1-style per-agent contracts on ', NOW())
WHERE id = @canonical_id;

-- Mark this as the company's default contract
UPDATE company_modules
SET default_contract_id = @canonical_id
WHERE company_id = 165 AND module_key = 'tour_operator';

-- ─── STEP 4: Pre-fill default-rate rows for canonical contract ───
-- One contract_rate row per model in the selected types, adult_default = model.price
INSERT INTO contract_rate
    (contract_id, company_id, agent_company_id, model_id, rate_type,
     adult_default, child_default,
     adult_thai, adult_foreigner, child_thai, child_foreigner,
     entrance_adult_default, entrance_child_default,
     entrance_adult_thai, entrance_adult_foreigner, entrance_child_thai, entrance_child_foreigner,
     currency, valid_from, valid_to)
SELECT @canonical_id, 165, 0, m.id, 'net_rate',
       m.price, ROUND(m.price * 0.5, 2),  -- child default: half of adult as a sane starting point
       0, 0, 0, 0,
       0, 0,
       0, 0, 0, 0,
       'THB', '2026-01-01', '2026-12-31'
FROM model m
INNER JOIN agent_contract_types act ON act.type_id = m.type_id
WHERE act.contract_id = @canonical_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165
  AND NOT EXISTS (
      SELECT 1 FROM contract_rate cr
      WHERE cr.contract_id = @canonical_id
        AND cr.model_id = m.id
        AND cr.deleted_at IS NULL
  );

-- ─── STEP 5: Capture redundant contract IDs for cleanup ───
CREATE TEMPORARY TABLE _redundant_contracts (id INT PRIMARY KEY);
INSERT INTO _redundant_contracts (id)
SELECT id FROM agent_contracts
WHERE company_id = 165 AND is_operator_level = 1 AND id != @canonical_id;

-- ─── STEP 6: HARD DELETE redundant contracts and their dependents ───
-- Order matters (FK constraints): children first, then parent.
DELETE FROM tour_operator_agent_products WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM tour_contract_agents          WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM agent_contract_types          WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM contract_rate                 WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM tour_contract_sync_log        WHERE contract_id IN (SELECT id FROM _redundant_contracts);
DELETE FROM agent_contracts               WHERE id          IN (SELECT id FROM _redundant_contracts);

DROP TEMPORARY TABLE _redundant_contracts;

-- ─── STEP 7: Create new "Season Rate Contract" ───
INSERT INTO agent_contracts
    (company_id, agent_company_id, contract_number, contract_name, status,
     valid_from, valid_to, payment_terms, credit_days, deposit_pct,
     conditions, notes, is_default, is_operator_level)
VALUES
    (165, NULL,
     CONCAT('CTR-2026-S', LPAD((SELECT IFNULL(MAX(id),0)+1 FROM agent_contracts AS ac), 4, '0')),
     'Season Rate Contract', 'active',
     '2026-01-01', '2026-12-31', '', 0, 0,
     '', '[017] Auto-created with High/Green seasons. Assign agents and review rates.',
     0, 1);

SET @season_id := LAST_INSERT_ID();

-- Copy the same product types
INSERT INTO agent_contract_types (contract_id, type_id, company_id)
SELECT @season_id, type_id, company_id
FROM agent_contract_types
WHERE contract_id = @canonical_id;

-- ─── STEP 8: Pre-fill rates for Season Rate Contract — TWO seasons per model ───
-- High Season (1 Nov - 30 Apr) - High season often commands a price premium; we
-- seed at base price and let the operator adjust.
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
WHERE act.contract_id = @season_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165;

-- Green/Low Season (1 May - 31 Oct)
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
WHERE act.contract_id = @season_id
  AND m.deleted_at IS NULL
  AND m.company_id = 165;

COMMIT;

-- ─── POST-MIGRATION ───
-- Trigger product re-sync so tour_operator_agent_products reflects the consolidation:
--   curl -s "https://yourdomain.com/cron.php?task=sync_all_contracts&token=YOUR_SECRET"
--
-- Verification:
--   SELECT id, contract_name, is_default, is_operator_level FROM agent_contracts WHERE company_id = 165;
--   SELECT contract_id, COUNT(*) AS rates FROM contract_rate WHERE company_id = 165 GROUP BY contract_id;
--   SELECT contract_id, COUNT(*) AS agents FROM tour_contract_agents WHERE company_id = 165 GROUP BY contract_id;
