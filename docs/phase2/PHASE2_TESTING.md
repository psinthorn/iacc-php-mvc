# Phase 2: Database Modernization Test Plan & Checklist

**Status**: Draft ready for execution  
**Last Updated**: December 31, 2025  
**Owner**: QA & Release Engineering  
**Scope**: Foreign keys, naming refactor, timestamps, audit trail, invalid date remediation  
**Test Window**: Weeks 11-12 of Phase 2 roadmap

---

## 1. Test Objectives

1. Validate referential integrity enforcement (no orphaned rows, correct FK behavior).
2. Confirm renamed tables/columns are fully adopted by the application, ETL, and reporting layers.
3. Ensure timestamps populate correctly for inserts/updates across all 31 tables.
4. Verify audit logging captures CRUD operations with accurate metadata.
5. Guarantee all legacy `0000-00-00` dates are removed and code gracefully handles `NULL` dates.

---

## 2. Environments & Data

| Environment | Purpose | Requirements |
| --- | --- | --- |
| Staging | Primary regression | Latest production snapshot (<48h), FK + naming + timestamp migrations applied |
| QA Sandbox | Destructive tests | Optional environment for failure injection, trigger disable/enable |
| Reporting/UAT | BI validation | Updated ETL pipelines pointing to renamed schema |

**Test Data**: Seed accounts for finance, purchasing, warehouse, and admin roles. Include at least 20 POs, 10 PRs, 15 invoices, 10 deliveries, 20 products, and 5 companies with credit terms.

---

## 3. Test Suites

### 3.1 Database Integrity

| ID | Test | Steps | Expected |
| --- | --- | --- | --- |
| DB-FK-01 | Orphan detection | Run queries from `PHASE2_FOREIGN_KEYS.md` | Zero rows returned |
| DB-FK-02 | FK enforcement | Attempt to delete a company referenced by PO | Operation blocked (`Cannot delete or update a parent row`) |
| DB-FK-03 | Cascade updates | Update company ID via script; verify child rows update | All related rows reflect new ID |
| DB-NAME-01 | Legacy references | `grep -R "po." iacc/` for old column names | No stale references |
| DB-NAME-02 | BI schema | Refresh PowerBI/Looker dashboards | No schema mismatch errors |
| DB-TS-01 | Insert timestamps | Create new PO/invoice and query `created_at/updated_at` | Non-null, current timestamps |
| DB-TS-02 | Update timestamps | Edit existing PO; verify `updated_at` > `created_at` | Timestamp updated once |
| DB-AUD-01 | Trigger firing | Insert/update/delete PO; check `audit_log` + `po_audit` | One row per action |
| DB-DATE-01 | Zero date scan | `SELECT COUNT(*) ... = '0000-00-00'` | All zero |

### 3.2 Application Regression

| ID | Feature | Scenario | Expected |
| --- | --- | --- | --- |
| APP-AUTH-01 | Login | Confirm existing users can log in post-schema changes | Success, audit record created |
| APP-PO-01 | PO creation | Create PO with vendor + delivery schedule | PO saved, timestamps populated, audit entry logged |
| APP-PO-02 | PO deletion | Attempt to delete PO with deliveries | Blocked due to FK; UI shows friendly error |
| APP-PR-01 | PR lifecycle | Create PR, convert to PO | Consistent IDs/naming usage |
| APP-INV-01 | Invoice issue | Create invoice tied to PO | `iv` table rows reflect new schema |
| APP-DEL-01 | Delivery update | Edit delivery date; ensure no zero date persists | Date saved, audit entry |
| APP-REP-01 | Reports | Run main financial/stock reports | No SQL errors referencing old names |
| APP-EXPORT-01 | CSV export | Export product list | Column headers reflect new names |
| APP-API-01 | External feeds | Trigger any APIs/integrations | Payload fields match new schema |

### 3.3 Performance & Concurrency

| ID | Test | Description | Success Criteria |
| --- | --- | --- | --- |
| PERF-01 | Bulk import | Load 5k products via existing import tool | Runtime within ±10% of baseline, no FK errors |
| PERF-02 | Audit overhead | Run 100 PO updates via script | CPU/Latency increase <5% |
| PERF-03 | Deadlock watch | Run mixed workload (PO + Invoice + Delivery) | No increase in `LATEST_DETECTED_DEADLOCK` |

---

## 4. Automation & Tooling

- **SQL Scripts**: Store validation queries in `scripts/testing/phase2/validate.sql` for re-use.
- **PHP Smoke Tests**: Extend existing PHPUnit/Codeception suite (if available) or create curl-based scripts under `scripts/testing/phase2/smoke.sh`.
- **Audit Verifier**: Add CLI script to diff `audit_log` entries vs. application actions.
- **CI Integration**: Add Phase 2 test job to pipeline; block deployment if FK or timestamp checks fail.

---

## 5. Entry/Exit Criteria

| Gate | Entry | Exit |
| --- | --- | --- |
| FK Migration | Migrations applied in staging | All DB-FK tests pass |
| Naming Refactor | Compatibility views active | `DB-NAME` suite green; remove views |
| Timestamp Adoption | `created_at/updated_at` columns present | DB-TS tests pass + app flows confirm |
| Audit Trail | Audit tables + triggers deployed | DB-AUD + APP audit checks pass |
| Invalid Dates | Cleanup scripts executed | DB-DATE tests pass, UI validations confirmed |

Release to production only after all suites pass and no P1/P2 defects remain.

---

## 6. Defect Management

- Track issues in Jira project `IACC-DB` with labels `phase2`, `fk`, `naming`, `timestamp`, `audit`, `dates`.
- Severity guidelines:
  - **P1**: Data loss, integrity violation, or blocking production workflows.
  - **P2**: Incorrect audit logs, mismatched schema causing major feature malfunction.
  - **P3**: UI validation gaps, non-critical logging issues.
- Require root-cause analysis for every P1/P2 before closure.

---

## 7. Reporting & Sign-off

- Maintain `docs/testing/phase2_test_run.xlsx` with pass/fail per test ID, tester, date, and notes.
- Capture DB snapshots (`mysqldump --no-data`) before/after final verification for audit compliance.
- Conduct Go/No-Go meeting with engineering, QA, product, and finance; attach testing evidence.
- Store final sign-off in `PHASE2_STATUS.md` and update `README.md` status banner.

---

**Next Up**: After test plan execution, follow `PHASE2_DEPLOYMENT.md` (to be authored) for production rollout and monitoring.
