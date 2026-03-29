# Naming Migration Execution Log

Use this log to document every execution step during the Phase 2b naming rollout. Treat it as the single source of truth for auditors and rollback coordination.

| timestamp (UTC) | environment | engineer | script/reference | summary | validation artifacts | status |
| --- | --- | --- | --- | --- | --- | --- |
| _pending_ | staging | _assign_ | migrations/phase2_naming/010_purchase_order.sql | Rename `po` → `purchase_order`, deploy compatibility view, backfill references. | `SHOW CREATE TABLE purchase_order`, regression run `2026-01-03`. | pending |
| _pending_ | staging | _assign_ | migrations/phase2_naming/020_purchase_request.sql | Rename `pr` → `purchase_request`, update PHP queries. | PHPUnit / manual PO-PR workflow check. | pending |
| _pending_ | staging | _assign_ | migrations/phase2_naming/030_invoice.sql | Rename `iv` → `invoice`, verify PDF generation. | Invoice export comparison. | pending |

## Logging Instructions

1. **Before execution**: populate `timestamp`, `environment`, `engineer`, and `script/reference`.
2. **During execution**: capture row counts before/after, transaction durations, and any blockers in `summary`.
3. **After execution**: attach validation evidence (links to Grafana, QA sheets, or console captures) and update `status` to `complete`.
4. **If rollback occurs**: add a new row referencing the rollback script and describe root cause + mitigation.

Keep this file synced with any spreadsheet or ticketing system notes to avoid drift.
