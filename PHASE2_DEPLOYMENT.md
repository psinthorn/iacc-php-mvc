# Phase 2: Database Modernization Deployment Playbook

**Status**: Ready for execution  
**Last Updated**: December 31, 2025  
**Owner**: Release Engineering & DBA Team  
**Target Window**: January 20–24, 2026 (after Phase 1 production go-live)  
**Scope**: Foreign keys, naming refactor, timestamps, audit trail, invalid date remediation

---

## 1. Prerequisites

| Area | Requirement | Owner | Status |
| --- | --- | --- | --- |
| Phase 1 | Security hardening live in production | App Team | ✅ |
| Backups | Full logical + physical backup (`mysqldump` + snapshot) < 24h old | DBA | ☐ |
| Migrations | SQL scripts reviewed & signed off (`migrations/phase2_*`) | Tech Lead | ☐ |
| Testing | `PHASE2_TESTING.md` checklist completed with no P1/P2 defects | QA | ☐ |
| Monitoring | Grafana dashboards updated for new tables/metrics | DevOps | ☐ |
| Runbooks | FK, naming, timestamps, audit, invalid-date guides finalized | PM | ✅ |

Deployment cannot start until all prerequisites are checked.

---

## 2. High-Level Timeline

| Day | Activities |
| --- | --- |
| Day 0 (T-1) | Final backups, dry-run replay in staging, Go/No-Go meeting |
| Day 1 (Maintenance Window) | FK enforcement + naming batch 1 |
| Day 2 | Naming batch 2, timestamps rollout |
| Day 3 | Audit trail deployment |
| Day 4 | Invalid date cleanup + schema enforcement |
| Day 5 | Final validation, monitoring, sign-off |

Maintenance window: **02:00–06:00 local time** each day to minimize user impact. Application placed in read-only maintenance mode via `SYSTEM_MAINTENANCE=true` flag.

---

## 3. Detailed Steps

### Step 1: Pre-flight (Day 0)

1. Communicate schedule to stakeholders (email + Slack #iacc-release).
2. Freeze code and DB changes outside Phase 2 scope (change freeze).
3. Export baseline metrics:
   - `SHOW GLOBAL STATUS` snapshot
   - Slow query log marker
   - Application throughput baseline
4. Verify rollback scripts exist for each migration batch.

### Step 2: Execution (Days 1–4)

| Order | Task | Script/Doc | Notes |
| --- | --- | --- | --- |
| 1 | Enable maintenance mode on app servers | `SYSTEM_MAINTENANCE=true` | Displays banner, blocks writes |
| 2 | Run FK cleanup + constraint scripts | `migrations/phase2_fk/001_add_constraints.sql` | Take per-table backups before ALTER |
| 3 | Apply naming batch A (company/product/po/pr) | `migrations/phase2_naming/010_*` | Use views for backward compatibility |
| 4 | Apply naming batch B (remaining tables) | `migrations/phase2_naming/020_*` | Drop compatibility views after tests |
| 5 | Run timestamp column adds + backfill | `migrations/phase2_timestamps/000/010` | Monitor lock times |
| 6 | Deploy audit tables + triggers | `migrations/phase2_audit/000/010` | Enable feature flag `AUDIT_ENABLED` |
| 7 | Execute invalid date cleanup + enforcement | `migrations/phase2_invalid_dates/000/010` | Ensure `sql_mode` updated |
| 8 | Redeploy app code with naming/timestamp/audit helpers | `deploy-production.sh` | Tag release `v2.0.0-phase2` |

### Step 3: Post-flight (Day 5)

1. Disable maintenance mode, gradually allow traffic.
2. Run `PHASE2_TESTING.md` smoke subset in production (read-only queries, non-destructive flows).
3. Monitor logs/dashboards for 48h.
4. Publish release notes + metrics to `DEPLOYMENT_COMPLETION_SUMMARY.md`.

---

## 4. Rollback Strategy

| Scenario | Action |
| --- | --- |
| FK migration failure | `ROLLBACK;` within transaction, fix offending rows, re-run. If critical, drop added constraints using rollback script. |
| Naming causes app failure | Re-enable compatibility views; redeploy prior app version; revert table renames via `RENAME TABLE` or restore from backup. |
| Timestamp rollout causing locks | Pause deployment, revert columns using `rollback/000_drop_columns.sql`, resume during next window. |
| Audit triggers causing performance hit | Disable triggers (`DROP TRIGGER ...`), set `AUDIT_ENABLED=false`, analyze slow queries before retry. |
| Invalid date cleanup removes needed data | Restore specific table from backup using `mysqldump --where` filters. |
| Major incident | Restore full database snapshot, redeploy pre-Phase 2 app package, and declare incident per ops handbook. |

Rollback decision threshold: **Any P1 incident >30 minutes**, or inability to complete migrations within window.

---

## 5. Communication Plan

| Audience | Channel | Message |
| --- | --- | --- |
| Internal stakeholders | Slack #iacc-release + email | Daily status updates, blockers |
| End users | Maintenance banner + email | Notify downtime window 48h prior |
| Exec sponsor | Email summary | Morning + end-of-window updates |
| Support/helpdesk | Ticketing system note | Provide talking points for user inquiries |

Incident updates follow standard Sev2/Sev1 comms cadence (every 30 mins until resolved).

---

## 6. Monitoring & Verification

- **DB Metrics**: InnoDB row lock time, deadlocks, replication lag (if any), `INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS` counts.
- **App Metrics**: Error rate, response time, login success, PO/Invoice transaction counts.
- **Audit Logs**: Growth rate, trigger errors (`SHOW WARNINGS`).
- **Custom Alerts**: Zero-date count >0, missing timestamps, FK violations (via scheduled SQL).

Use Grafana dashboards updated with Phase 2 panels. Assign on-call rotation for DBA + App engineer during rollout.

---

## 7. Deliverables & Evidence

- Updated `DEPLOYMENT_COMPLETION_SUMMARY.md` with:
  - Start/end times per task
  - Scripts executed & checksums
  - Issues encountered + resolutions
- `docs/audit/phase2_deployment_log.md` capturing console output snippets.
- Database metadata exports (`mysqldump --no-data iacc > backup/schemas/post_phase2.sql`).
- Screenshots or exports of monitoring dashboards during rollout.
- Meeting notes from Go/No-Go and wrap-up sessions.

---

## 8. Post-Deployment Tasks

1. Remove temporary compatibility views and deprecated columns.
2. Update developer onboarding docs (`README.md`, `UPGRADE_PHP_MYSQL.md`).
3. Plan Phase 3 kickoff (architecture refactor) using `IMPROVEMENTS_PLAN.md` timelines.
4. Schedule knowledge-sharing session to review lessons learned.

---

**Next Up**: After successful deployment and monitoring, transition to Phase 3 (architecture modernization, API layering) per the master improvements plan.
