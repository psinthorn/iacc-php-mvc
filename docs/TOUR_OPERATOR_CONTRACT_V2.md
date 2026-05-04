# Tour Operator Contract Management v2 — Technical Document

**Version:** 2.0
**Status:** Released on `feature/tour-operator-contract-v2` branch
**Audience:** Developers, DevOps, technical product managers

---

## Overview

Contract Management v2 replaces the old per-agent contract model with a **many-to-many** system where operators define contracts at the company level and assign multiple agents to each. Pricing is **season-aware**, with priority-based fallback to a base rate. Product catalogs **auto-sync** to assigned agents.

### Key Capabilities

- **Operator-level contracts** with many assigned agents (vs. one-contract-per-agent in v1)
- **Season pricing**: define multiple rate sets per contract (e.g. High Season, Low Season, base rate fallback)
- **Net rate or percentage** rate types per product
- **Agent registration & approval** workflow (pending → approved → suspended → rejected)
- **Auto-sync engine** populates each agent's product catalog from their contracts
- **Agent self-service portal** to view contracts, products, bookings, documents
- **Document sharing** with visibility scopes (all agents / contract-only / internal)
- **REST API** for sales channels and external integrations
- **Reporting** with daily/weekly/monthly digests, including auto-email
- **Super admin** platform-wide overview across all operators

---

## Data Model

### New Tables

#### `tour_operator_agents`
Tracks agent registration & approval per operator.
- Composite unique key on `(operator_company_id, agent_company_id)`
- Status flow: `pending` → `approved` (or `rejected`); approved can be `suspended` and `reactivated`
- Optional `default_contract_id` set when approving — auto-assigns to that contract on approval

#### `tour_contract_agents`
Many-to-many junction between `agent_contracts` and agent companies.
- Created via the contract form's "Agents" tab or by approving with a default contract
- Composite unique key on `(contract_id, agent_company_id)` — idempotent

#### `tour_operator_agent_products`
The synced catalog visible in the agent portal.
- One row per `(operator, agent, contract, model)` tuple
- `is_active` instead of delete — preserves history when products are removed from a contract
- Rebuilt incrementally by `ContractSyncService`

#### `tour_contract_sync_log`
Audit trail. Every sync writes one row with:
- `triggered_by`: `auto` | `operator` | `agent` | `api` | `cron` | `system`
- `products_added` / `products_removed` counters
- Optional JSON `details` (e.g. agent count, model IDs)

### Modified Tables

| Table | Change | Reason |
|---|---|---|
| `agent_contracts` | `agent_company_id` → nullable | V2 contracts have no single agent |
| `agent_contracts` | `is_operator_level` (TINYINT) | Distinguishes V1 (0) from V2 (1) |
| `contract_rate` | `season_name`, `season_start`, `season_end`, `priority` | Season-aware pricing |
| `company_modules` | `default_contract_id` | Each operator's fallback contract |
| `tour_operator_documents` | new table | File sharing |

### Migration Order

```
014_tour_operator_contract_v2.sql      # Schema + data migration (60 agents, 46 contracts)
015_tour_operator_documents.sql        # Documents table
016_promote_v1_to_v2_contracts.sql     # Promote 46 existing contracts to V2
```

After 016 you should run a full sync once: `cron.php?task=sync_all_contracts&token=...` to populate `tour_operator_agent_products`.

---

## Pricing Algorithm

The applicable rate for a `(contract, model, travel_date)` tuple is resolved via:

```sql
SELECT * FROM contract_rate
WHERE contract_id = :cid AND model_id = :mid
  AND deleted_at IS NULL
  AND (
        (season_start <= :travel_date AND season_end >= :travel_date)
     OR (season_name IS NULL)
  )
ORDER BY
  CASE WHEN season_name IS NOT NULL THEN 0 ELSE 1 END,  -- prefer matching season
  priority DESC                                          -- higher priority wins ties
LIMIT 1
```

Once the rate row is found, the per-pax price is selected by nationality:

- `default` → `adult_default` / `child_default`
- `thai` → `adult_thai` if > 0 else `adult_default`
- `foreigner` → `adult_foreigner` if > 0 else `adult_default`

Same logic for entrance fees (`entrance_adult_*`, `entrance_child_*`).

This logic lives in `App\Models\AgentContract::findApplicableRate()` and is exposed via `POST /api.php/v1/tour-pricing`.

---

## Sync Engine

**Class:** `App\Services\ContractSyncService`

**Triggers:**
- After contract create/update (controller `triggerSync()`)
- After agent assign/unassign (controller actions)
- Manual button on contract form (`tour_contract_resync` route)
- Manual button on agent portal Products page (`agent_portal_resync`)
- Cron: `sync_all_contracts` task
- API: `POST /tour-contracts/{id}/resync`

**Algorithm (per agent):**
1. Fetch the contract's selected types (`agent_contract_types`)
2. Resolve type IDs → model IDs (`SELECT id FROM model WHERE type_id IN (...)`)
3. Diff against current sync rows for this `(agent, contract)`
4. Upsert missing rows (sets `is_active=1`, refreshes `synced_at`)
5. Set `is_active=0` on rows whose models are no longer in the contract
6. Write a sync log entry

**Transaction:** the entire batch (across all agents in a contract) is wrapped in `mysqli_begin_transaction`, with rollback on any exception.

---

## Permissions Matrix

| Action | Agent | Operator (admin) | Super Admin |
|---|---|---|---|
| View own portal | ✅ if approved | n/a | n/a |
| Resync own catalog | ✅ | n/a | n/a |
| Create/edit contracts | ❌ | ✅ | ✅ (any operator) |
| Approve agents | ❌ | ✅ | ✅ |
| Upload documents | ❌ | ✅ | ✅ |
| Download documents | ✅ if visibility allows | ✅ own | ✅ all |
| Send report digests | ❌ | ✅ own | ✅ all (bulk) |
| API access | ❌ | ✅ via API key | ✅ |

Module gating: operator routes require `isModuleEnabled($comId, 'tour_operator')`. Agent portal routes require the user's company to have at least one `tour_operator_agents` row with `status='approved'`.

---

## REST API

Base URL: `https://yourdomain.com/api.php/v1`
Auth headers: `X-API-Key`, `X-API-Secret`. The key's company is implicitly the operator scope.

### Endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/tour-contracts` | List operator contracts |
| `GET` | `/tour-contracts/{id}` | Single contract detail |
| `GET` | `/tour-contracts/{id}/rates` | Rates grouped by season |
| `POST` | `/tour-contracts/{id}/resync` | Sync contract products to agents |
| `GET` | `/tour-products?agent_id=N&contract_id=N` | List synced products |
| `POST` | `/tour-pricing` | Calculate price (body: `{contract_id, model_id, travel_date, pax_adult, pax_child, nationality}`) |

### Pricing example

```bash
curl -X POST https://yourdomain.com/api.php/v1/tour-pricing \
  -H "Content-Type: application/json" \
  -H "X-API-Key: iACC_..." \
  -H "X-API-Secret: ..." \
  -d '{
    "contract_id": 42,
    "model_id": 17,
    "travel_date": "2026-08-15",
    "pax_adult": 2,
    "pax_child": 1,
    "nationality": "foreigner"
  }'
```

Response:
```json
{
  "success": true,
  "data": {
    "contract_id": 42,
    "model_id": 17,
    "travel_date": "2026-08-15",
    "season": "High Season",
    "rate_type": "net_rate",
    "unit_prices": {
      "adult": 1500.00, "child": 750.00,
      "entrance_adult": 200.00, "entrance_child": 100.00
    },
    "pax": { "adult": 2, "child": 1, "nationality": "foreigner" },
    "totals": { "service": 3750.00, "entrance": 500.00, "grand": 4250.00 }
  }
}
```

---

## Reporting

### Manual via UI

- Operator: `index.php?page=tour_contract_report` — shows daily + weekly + monthly snapshots with KPI cards, MoM deltas, top 5 agents, top 10 products. "Email Now" buttons send the digest to the operator's primary admin email.
- Super admin: `index.php?page=super_admin_tour` — platform-wide stats and bulk-send buttons that dispatch digests to every operator.

### Cron (cPanel)

Set `$config['cron_token'] = '<random secret>'` in `inc/sys.configs.php`, then add cron jobs:

```cron
# Daily at 8am
0 8 * * * curl -s "https://yourdomain.com/cron.php?task=daily_reports&token=YOUR_SECRET"
# Weekly Mondays 9am
0 9 * * 1 curl -s "https://yourdomain.com/cron.php?task=weekly_reports&token=YOUR_SECRET"
# Monthly 1st at 9am
0 9 1 * * curl -s "https://yourdomain.com/cron.php?task=monthly_reports&token=YOUR_SECRET"
```

The cron returns `text/plain` with a per-operator pass/fail line — useful for cron job log inspection.

---

## Deployment Steps (cPanel)

1. **Backup** the production DB (full dump).
2. **Pull** the `develop` (or `main`) branch via cPanel git deploy.
3. **Run migrations** in order via phpMyAdmin → Import:
   - `database/migrations/014_tour_operator_contract_v2.sql`
   - `database/migrations/015_tour_operator_documents.sql`
   - `database/migrations/016_promote_v1_to_v2_contracts.sql`
4. **Set cron token**: edit `inc/sys.configs.php` and add `$config['cron_token'] = bin2hex(random_bytes(16));`
5. **Trigger one-time sync**: hit `/cron.php?task=sync_all_contracts&token=YOUR_SECRET` once after migration 016.
6. **Configure cron jobs** (see above).
7. **Verify**:
   - Login as super admin → "Tour Operator Platform" menu shows operators
   - Login as a tour operator user → "Contracts" menu shows the V2 list with promoted contracts
   - If a user's company is in `tour_operator_agents` with status approved → "Agent Portal" appears in their sidebar
8. **Set up uploads dir**: ensure `uploads/operator-documents/` is writable by PHP.

---

## Rollback

- **Migration 016** can be reversed with: `UPDATE agent_contracts SET is_operator_level = 0 WHERE is_operator_level = 1;`
- Migrations 014/015 should NOT be rolled back without DB-level coordination — the new tables would lose data. Instead, keep them in place and disable the new UI by removing sidebar links.
- Branch rollback: `git checkout main` reverts to the prior version. The new tables will remain but be unused.

---

## Known Limits / Future Work

- Document storage is local to the cPanel filesystem (`uploads/operator-documents/`). For multi-server, switch to S3 with signed URLs.
- Sync runs synchronously — for very large operators (1000+ agents), consider moving to a job queue.
- Reports query the live tables; for slow operators, materialize daily snapshots into a `tour_report_snapshots` table.
- The agent portal does not yet support agent-initiated booking creation — that flow continues through the operator UI.
