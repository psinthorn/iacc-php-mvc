# Tour Operator Contract Management v2 — QA Checklist

**Purpose:** Manual smoke + regression testing before merging to `develop` and `main`.

Use this when promoting `feature/tour-operator-contract-v2` to staging or production.

---

## Pre-flight (Code & Schema)

- [ ] All 12 phase commits present on the feature branch (`git log --oneline | head -15`)
- [ ] `php -l` clean for every file under `app/Models/`, `app/Controllers/`, `app/Services/`, `app/Views/tour-agent/`, `app/Views/agent-portal/`, `app/Views/admin/`, `cron.php`, `api.php`
- [ ] All routes resolve — `php -r "$r=require 'app/Config/routes.php'; foreach(['tour_contract_list','agent_portal_dashboard','tour_doc_list','super_admin_tour'] as $k) echo isset($r[$k])?'ok':'MISS', PHP_EOL;"`
- [ ] All 5 new tables exist: `tour_operator_agents`, `tour_contract_agents`, `tour_operator_agent_products`, `tour_contract_sync_log`, `tour_operator_documents`
- [ ] All 4 column additions present on `agent_contracts` (`is_operator_level`), `contract_rate` (`season_name`, `season_start`, `season_end`, `priority`), `company_modules` (`default_contract_id`)
- [ ] Migration 016 promoted V1 contracts: `SELECT COUNT(*) FROM agent_contracts WHERE is_operator_level=1` returns the expected count

---

## Phase 2 — Operator Contract CRUD

- [ ] Sidebar shows **Tour Operator → Contracts** for users in companies with `tour_operator` module
- [ ] `tour_contract_list` page renders KPI cards (total / active / draft) + contract grid
- [ ] **New Contract** button opens the tabbed form
- [ ] Save with required `contract_name` succeeds; saving without it shows browser validation
- [ ] After saving, the 3 extra tabs (Seasons & Rates, Agents, Sync Status) become active
- [ ] Edit existing contract loads existing values in all tabs
- [ ] **Clone** button prompts for new name and creates a copy with rates
- [ ] **Set Default** flips the star icon and unsets the previous default
- [ ] **Delete** is hidden on the default contract; delete on a non-default removes rates + assignments + products

## Phase 3 — Sync Engine

- [ ] After saving a contract, `tour_contract_sync_log` gets a new row with `triggered_by='operator'` (only if there are assigned agents)
- [ ] Adding a product type to a contract → next sync adds those models for every assigned agent
- [ ] Removing a product type → next sync sets `is_active=0` for those models (does NOT delete)
- [ ] **Resync Now** button on the Sync tab triggers a sync log entry with `action='resync'`
- [ ] Manually unassigning an agent removes their products from `tour_operator_agent_products` (DELETE, with log entry `action='contract_unassigned'`)
- [ ] cron `?task=sync_all_contracts` is idempotent — running twice doesn't add duplicate rows

## Phase 4 — Agent Registration

- [ ] Sidebar shows **Tour Operator → Agent Registrations**
- [ ] `tour_agent_reg_list` shows status filter cards with counts
- [ ] Clicking a status card filters the list
- [ ] **View** opens detail page with profile + side actions
- [ ] **Approve** with no default contract → status flips to approved, no contract assigned
- [ ] **Approve** with a default contract → status flips, agent appears on that contract's Agents tab, sync triggered
- [ ] Approval sends an email (verify in MailHog at http://localhost:8025 in dev)
- [ ] **Reject** with reason → status flips, reason appended to notes
- [ ] **Suspend** → approved agent loses portal access (sidebar item disappears next page load)
- [ ] **Reactivate** → suspended agent regains portal access

## Phase 5 — Agent Portal

- [ ] User whose company has `status='approved'` in `tour_operator_agents` sees **Agent Portal** in sidebar
- [ ] User without approved status does NOT see the portal item
- [ ] Visiting `agent_portal_dashboard` directly when not approved → redirects to dashboard with `msg=no_operator_access`
- [ ] Dashboard renders 4 KPI cards + operators list + recent bookings
- [ ] **Products** page groups by operator, filterable by operator dropdown, shows resync button
- [ ] **Resync** button triggers sync from agent side, products refresh
- [ ] **Contracts** page shows assigned contracts with operator name + period + rate count
- [ ] Clicking a contract opens detail with rates grouped by season
- [ ] **Bookings** page shows agent's bookings (via `agent_id` column on `tour_bookings`)
- [ ] Portal nav stays consistent across all 5 pages with active highlight

## Phase 6 — Documents

- [ ] Sidebar shows **Tour Operator → Documents** (operator side)
- [ ] Upload form rejects files > 10MB (returns `msg=too_large`)
- [ ] Upload rejects disallowed mime types (e.g. `.exe` returns `msg=bad_type`)
- [ ] Allowed types upload to `uploads/operator-documents/{operator_id}/` with timestamped filename
- [ ] Document table shows category badge, visibility, file size, download count
- [ ] Visibility = "Contract Only" requires picking a contract from the dropdown
- [ ] **Delete** soft-deletes (sets `deleted_at`, file remains on disk)
- [ ] Agent portal sidebar shows **Documents** if agent is approved
- [ ] Agent sees only documents they're allowed to: `all_agents` for any operator that approved them, `contract` for contracts they're assigned to, never `operator_only`
- [ ] Agent download endpoint streams the file and increments `download_count`
- [ ] Direct hit on `agent_portal_doc_download&id=X` for a doc the agent can't access → redirect to portal with `msg=not_found` (no 500 error)

## Phase 7 — REST API

- [ ] `GET /api.php/v1/` (no auth) returns endpoint catalog including all 6 tour endpoints
- [ ] `GET /api.php/v1/tour-contracts` without API key → 401 with `error.code=AUTH_MISSING`
- [ ] `GET /api.php/v1/tour-contracts` with valid key → array of contracts owned by key's company
- [ ] `GET /api.php/v1/tour-contracts/{id}/rates` → seasons array with all rate fields
- [ ] `GET /api.php/v1/tour-products?agent_id=N` filters to that agent
- [ ] `POST /api.php/v1/tour-pricing` with valid body → returns season-resolved unit_prices + totals
- [ ] `POST /api.php/v1/tour-pricing` missing `contract_id` → 422 with `VALIDATION_ERROR`
- [ ] `POST /api.php/v1/tour-pricing` with travel_date that hits a season → response `season` field is non-null
- [ ] `POST /api.php/v1/tour-pricing` with travel_date outside any season → response `season` is null (uses base rate)
- [ ] `POST /api.php/v1/tour-contracts/{id}/resync` writes a sync log with `triggered_by='api'`

## Phase 8 — Reporting

- [ ] Sidebar shows **Tour Operator → Contract Reports**
- [ ] Daily section shows 5 KPI cards
- [ ] Weekly section shows 5 KPI cards + Top 5 Agents table (only if data exists)
- [ ] Monthly section shows 4 KPI cards with up/down delta vs prev month + Top 10 Products table
- [ ] **Email Now** for any period sends to the operator's primary admin email (verify in MailHog)
- [ ] Without admin email configured → returns `msg=no_email`
- [ ] `cron.php?task=daily_reports&token=BAD` → 403
- [ ] `cron.php?task=daily_reports&token=GOOD` → text/plain log of pass/fail per operator
- [ ] `cron.php?task=unknown` → 400 with valid task list

## Phase 9 — Super Admin Dashboard

- [ ] User with `user_level >= 2` sees **Admin → Tour Operator Platform** in sidebar
- [ ] Non-super-admin visiting `super_admin_tour` directly → redirects to dashboard
- [ ] Page shows 6 platform KPI cards
- [ ] **Operators table** shows every operator with `tour_operator` module enabled
- [ ] **Pending Approvals** table shows pending registrations across all operators
- [ ] **Recent Sync Activity** shows last 20 entries from `tour_contract_sync_log`
- [ ] Cron snippet displays the production URL (uses `$_SERVER['HTTP_HOST']`)
- [ ] **Send Daily/Weekly/Monthly** buttons trigger bulk dispatch and return with `msg=sent`
- [ ] Dashboard's super admin Quick Actions shows **Tour Platform** button → links to `super_admin_tour`

## Phase 10 — Data Migration

- [ ] After running migration 014: `tour_operator_agents` count == `tour_agent_profiles` count (where deleted_at IS NULL)
- [ ] After running migration 014: `tour_contract_agents` has one row per existing `agent_contracts` with non-null `agent_company_id`
- [ ] After running migration 016: every contract in 014's data has `is_operator_level=1`
- [ ] After running 016 + cron `sync_all_contracts`: `tour_operator_agent_products` count > 0
- [ ] Rollback `UPDATE agent_contracts SET is_operator_level=0` returns the system to the V1-only state without errors

---

## Cross-Cutting Checks

### Multi-tenant isolation
- [ ] Operator A cannot see operator B's contracts (verify by setting `$_SESSION['com_id']` to a different operator)
- [ ] Agent X cannot see agent Y's portal data (Cross-tenant SQL filter on `agent_company_id`)
- [ ] API key for operator A can never receive contracts owned by operator B

### CSRF
- [ ] All POST forms include `<?= csrf_field() ?>`
- [ ] Submitting any POST without a valid CSRF token → request fails (verifyCsrf throws)

### XSS
- [ ] Every `<?= $variable ?>` outside a script/CSS context uses `htmlspecialchars()`
- [ ] Document title with HTML in it (`<img onerror=alert(1)>`) is escaped on display

### File security
- [ ] Uploaded file with `.php` extension is rejected by mime check
- [ ] Direct URL hit on `uploads/operator-documents/{op_id}/file.pdf` → only works if the web server doesn't list directory; agent download MUST go through `agent_portal_doc_download` endpoint

### Mobile
- [ ] All new pages render correctly on mobile (320px width); `dash-grid` collapses to single column at 768px
- [ ] Tabbed contract form is usable on mobile (tabs wrap)

### Accessibility
- [ ] All form inputs have `<label>` elements
- [ ] All buttons have either visible text or `title=""` attribute
- [ ] Status badges have sufficient color contrast

---

## Sign-off

- [ ] **Developer:** code complete, all unit smoke tests pass — _____________ (date)
- [ ] **QA:** manual checklist complete — _____________ (date)
- [ ] **Product:** acceptance criteria met — _____________ (date)
- [ ] **Approved for `develop` merge:** _____________
- [ ] **Approved for `main` merge (production):** _____________

---

## Backout Plan

If a critical bug is found after deployment to production:

1. **Soft revert** (preferred): rebuild the V1 sidebar items and remove V2 sidebar items via `app/Views/layouts/sidebar.php`. Tables stay in place. Users go back to using `agent_contract_list` (V1 routes).
2. **Hard revert**: `git revert <merge_commit>` to undo the merge. Database tables remain (they're additive); only the V1 → V2 promotion needs reversal: `UPDATE agent_contracts SET is_operator_level = 0;`
3. **Nuclear option**: drop the 5 new tables (`DROP TABLE tour_operator_agents, tour_contract_agents, tour_operator_agent_products, tour_contract_sync_log, tour_operator_documents`) — only do this if migrations corrupted production data and the new tables are unrecoverable.
