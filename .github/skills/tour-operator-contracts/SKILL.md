---
name: tour-operator-contracts
description: 'Tour Operator Contract Management v2 — many-to-many operator/agent contracts with season-aware pricing, sync engine, agent portal, and document sharing. USE FOR: building features on the v2 contract system, debugging sync issues, adding new contract attributes, extending agent portal, working with the tour API endpoints, or understanding the data model. Use when: a request mentions tour contracts, agent portal, contract sync, season rates, agent registration/approval, operator documents, or any /tour/* / agent_portal_* / tour_contract_* / tour_agent_reg_* route.'
argument-hint: 'What you want to do (e.g. "add cancellation policy field to contracts", "debug why agent X is missing product Y", "add a new visibility scope to documents")'
---

# Tour Operator Contract Management v2 — Skill

## Mental Model

The system has **two roles** that are both companies in the `company` table:
1. **Operator** — owns products (`model`), creates contracts, approves agents
2. **Agent** — sells operator products to end customers, sees a portal of synced products

A contract is a many-to-many bridge:

```
Operator (company)
   └── creates → agent_contracts (is_operator_level=1)
                    ├── agent_contract_types (which product types covered)
                    ├── contract_rate (rates, with optional season_name + period + priority)
                    └── tour_contract_agents (which agents assigned)
                              └── auto-syncs → tour_operator_agent_products
                                                    └── visible in agent portal
```

## Core Tables (created by migration 014)

| Table | Purpose |
|---|---|
| `tour_operator_agents` | Agent registration & approval (status: pending/approved/suspended/rejected) |
| `tour_contract_agents` | Many-to-many junction (which agents are on which contract) |
| `tour_operator_agent_products` | Sync target — agent's accessible product catalog (does NOT duplicate model data, just links) |
| `tour_contract_sync_log` | Audit trail of every sync operation |

Plus columns added to existing tables:
- `agent_contracts.is_operator_level` (1 = v2 contract, 0 = legacy v1 per-agent contract)
- `agent_contracts.agent_company_id` (now nullable; v2 contracts have it NULL)
- `contract_rate.season_name`, `season_start`, `season_end`, `priority` (season-aware pricing)
- `company_modules.default_contract_id` (each operator's "fallback" contract)

Migration 015 added `tour_operator_documents` for operator → agent file sharing.
Migration 016 promotes legacy v1 contracts to v2 (one-time).

## Season Rate Resolution (priority-based)

When pricing a booking for `(contract, model, travel_date)`:

```sql
SELECT * FROM contract_rate
WHERE contract_id = ? AND model_id = ?
  AND deleted_at IS NULL
  AND (
        (season_start <= travel_date AND season_end >= travel_date)
     OR (season_name IS NULL)  -- base rate fallback
  )
ORDER BY
  CASE WHEN season_name IS NOT NULL THEN 0 ELSE 1 END,  -- prefer season match
  priority DESC                                          -- higher priority wins
LIMIT 1
```

This logic lives in `AgentContract::findApplicableRate()` and is also exposed via the API at `POST /api.php/v1/tour-pricing`.

## Sync Engine (Phase 3)

- Service: `App\Services\ContractSyncService`
- Triggered automatically on: contract save, agent assign/unassign, manual resync button, API call
- Triggered manually via cron: `cron.php?task=sync_all_contracts&token=...`
- Always wraps work in `mysqli_begin_transaction` with rollback on failure
- Logs every sync to `tour_contract_sync_log` (with `triggered_by` source: auto/operator/agent/api/cron/system)

The sync is **incremental**: it diffs current synced products against the contract's current model list, then upserts new ones and deactivates removed ones. It does NOT delete; it sets `is_active = 0` so history is preserved.

## Routes Cheat Sheet

### Operator side (under index.php?page=...)
- `tour_contract_list`, `tour_contract_make`, `tour_contract_store`, `tour_contract_delete` — contract CRUD
- `tour_contract_assign`, `tour_contract_unassign` — manage agents on a contract
- `tour_contract_default`, `tour_contract_clone`, `tour_contract_resync` — utilities
- `tour_agent_reg_list`, `tour_agent_reg_view`, `tour_agent_reg_approve`, etc. — registration workflow
- `tour_doc_list`, `tour_doc_upload`, `tour_doc_delete` — document library
- `tour_contract_report`, `tour_contract_report_send` — daily/weekly/monthly reports

### Agent portal (under index.php?page=...)
- `agent_portal_dashboard`, `agent_portal_products`, `agent_portal_contracts`, `agent_portal_contract`, `agent_portal_bookings`, `agent_portal_documents`
- `agent_portal_resync` (POST), `agent_portal_doc_download`

### Super admin
- `super_admin_tour` — platform overview
- `super_admin_tour_send_all` (POST) — bulk digest emails

### REST API (under api.php/v1/...)
- `GET /tour-contracts`, `GET /tour-contracts/{id}`, `GET /tour-contracts/{id}/rates`
- `POST /tour-contracts/{id}/resync`
- `GET /tour-products` (filter: `?agent_id=N&contract_id=N`)
- `POST /tour-pricing` (body: `{contract_id, model_id, travel_date, pax_adult, pax_child, nationality}`)

Auth: `X-API-Key` + `X-API-Secret` headers (existing auth, scoped to operator's company).

## Common Tasks

### Adding a new contract attribute (e.g., cancellation_policy)

1. Migration: `ALTER TABLE agent_contracts ADD COLUMN cancellation_policy TEXT`
2. Model `AgentContract`: include the column in `getContract`/`createOperatorContract`/save methods
3. View `contract-make-v2.php`: add a form field in the Details tab + populate `value=...`
4. Controller `AgentContractController::contractStore`: read from `$_POST` and pass to model
5. View `contract-list-v2.php`: optional — show as a meta row
6. Agent portal `contract-detail.php`: show in summary if you want agents to see it
7. API `getTourContract` in ChannelApiController: include in response

### Adding a new visibility scope to documents

1. Migration: `ALTER TABLE tour_operator_documents MODIFY COLUMN visibility ENUM(...,'new_scope')`
2. Model `OperatorDocument::listForAgent`: add a JOIN/condition for the new scope
3. Model `OperatorDocument::canAgentAccess`: same
4. View `document-list.php`: add the option to the Visibility select + label
5. Controller `OperatorDocumentController::upload`: pass through if relevant

### Debugging "agent X is missing product Y"

Check in this order:
1. `SELECT * FROM tour_operator_agents WHERE agent_company_id=X AND status='approved'` — is agent approved?
2. `SELECT * FROM tour_contract_agents WHERE agent_company_id=X` — is agent assigned to a contract?
3. `SELECT * FROM agent_contract_types WHERE contract_id=...` — does the contract include Y's type?
4. `SELECT * FROM tour_operator_agent_products WHERE agent_company_id=X AND model_id=Y` — is the sync row there? Is `is_active=1`?
5. `SELECT * FROM tour_contract_sync_log WHERE agent_company_id=X ORDER BY id DESC LIMIT 10` — what was the last sync?
6. If all OK but agent doesn't see it — check the agent portal's `AgentPortal::getProducts` SQL.

### Adding a new sync trigger

Call `(new ContractSyncService())->syncContractToAgents($contractId, $comId, 'your_source_label')` after the action that mutates the contract or its agent assignments. The service handles transactions and audit logging; you don't need to wrap it.

## Backward Compatibility

The v2 system was built side-by-side with the legacy per-agent contract flow:

- **V1 routes** (`agent_contract_list`, `agent_contract_make`) still exist and work for legacy contracts
- **V1 model methods** in `AgentContract` are preserved
- A contract is v1 if `is_operator_level = 0` and has `agent_company_id` set
- A contract is v2 if `is_operator_level = 1` and has `agent_company_id IS NULL`
- Migration 016 promoted all 46 existing v1 contracts to v2 (junction rows already exist from migration 014)

When in doubt: prefer v2 methods for new features. Don't add to the v1 flow.

## Files of Note

```
app/Models/
  AgentContract.php             # Core contract model (v1 + v2 methods)
  ContractSync.php              # Synced products + sync log access
  TourOperatorAgent.php         # Agent registration/approval
  AgentPortal.php               # Read-only data for agent-facing portal
  OperatorDocument.php          # Document sharing
  ContractReport.php            # Daily/weekly/monthly aggregates

app/Services/
  ContractSyncService.php       # The sync engine (always start here for sync logic)

app/Controllers/
  AgentContractController.php           # Operator-side contract CRUD (v1 + v2 actions)
  TourAgentRegistrationController.php   # Approval workflow
  AgentPortalController.php             # Agent-facing portal
  OperatorDocumentController.php        # Document upload + agent download
  ContractReportController.php          # Reports + sendDigest()
  SuperAdminTourController.php          # Platform-wide overview
  ChannelApiController.php              # REST API (tour endpoints near bottom)

app/Views/
  tour-agent/contract-list-v2.php       # Operator contract list
  tour-agent/contract-make-v2.php       # Tabbed form (Details/Seasons & Rates/Agents/Sync)
  tour-agent/registration-list.php      # Agent approvals
  tour-agent/document-list.php          # Doc library
  tour-agent/report-dashboard.php       # Reports
  agent-portal/                         # Agent-facing UI (dashboard, products, etc.)
  admin/super-tour-dashboard.php        # Super admin overview

database/migrations/
  014_tour_operator_contract_v2.sql     # Schema + data migration
  015_tour_operator_documents.sql       # Documents
  016_promote_v1_to_v2_contracts.sql    # One-time V1→V2 promotion

cron.php                                # cPanel-friendly task runner
```

## cPanel Cron Setup

```cron
# Daily contract digest at 8am
0 8 * * * curl -s "https://yourdomain.com/cron.php?task=daily_reports&token=YOUR_SECRET"
# Weekly digest on Mondays at 9am
0 9 * * 1 curl -s "https://yourdomain.com/cron.php?task=weekly_reports&token=YOUR_SECRET"
# Monthly digest on the 1st at 9am
0 9 1 * * curl -s "https://yourdomain.com/cron.php?task=monthly_reports&token=YOUR_SECRET"
```

Set the token in `inc/sys.configs.php` as `$config['cron_token'] = '...'` or as a CRON_TOKEN env var.

## Module Gating

Operator-side routes guard with `isModuleEnabled($comId, 'tour_operator')`.
Agent portal routes guard with `AgentPortal::getOperators($agentComId)` — if empty, the user's company isn't an approved agent for any operator and gets redirected.
