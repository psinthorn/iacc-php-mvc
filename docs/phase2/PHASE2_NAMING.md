# Phase 2b: Naming Standardization & Schema Refactor Guide

**Status**: Planning complete, ready for task breakdown  
**Last Updated**: December 31, 2025  
**Owner**: Schema Modernization Squad  
**Duration**: ~24 engineer-hours over 2-3 calendar weeks  
**Dependencies**: Foreign keys from `PHASE2_FOREIGN_KEYS.md` must be live before this effort.

---

## 1. Objectives

- Establish a single, predictable naming convention for every table, column, primary key, and foreign key.
- Avoid brittle one-shot renames by using staged compatibility layers (views/synonyms) during rollout.
- Reduce long-term maintenance cost by aligning database schema names with PHP variables and DTOs.
- Produce automated migration scripts plus a reference catalog for developers, QA, and BI consumers.

## 2. Current Challenges (from `PHASE2_ANALYSIS.md`)

- Mixed case styles (`board`, `board1`, `board_group`, `tmp_product`).
- Duplicate semantics with different names (`pay` vs `payment`, `authorize` vs `user`).
- Inconsistent key suffixes (`id`, `_id`, `ven_id`, `idven`).
- Date columns using multiple naming patterns (`date`, `createdate`, `valid_begin`, `board1_datetime`).
- Status columns with arbitrary prefixes (`status_iv`, `board_status`, `board1_status`).
- Result: 300+ SQL statements and 30+ PHP files require manual search/replace when schema evolves.

---

## 3. Target Naming Standards

| Element | Convention | Examples |
| --- | --- | --- |
| Tables | Singular snake_case nouns; avoid numbering | `purchase_order`, `purchase_order_item`, `product_category` |
| Primary Keys | `<table>_id` INT AUTO_INCREMENT | `company_id`, `product_id` |
| Foreign Keys | `<referenced_table>_id` | `company_id`, `category_id` |
| Junction Tables | `<entity_a>_<entity_b>` alphabetical order | `brand_type`, `product_store` |
| Timestamps | `created_at`, `updated_at`, `deleted_at` (nullable) | Default CURRENT_TIMESTAMP / ON UPDATE |
| Status Flags | `is_active`, `is_locked`, `status_code` (ENUM/text) | Avoid `status_iv` style |
| Monetary Columns | Decimal columns end with `_amount` | `total_amount`, `tax_amount` |
| Boolean Columns | `is_`, `has_`, `can_` prefixes | `is_vatable`, `has_attachment` |
| Language Columns | `<field>_<locale>` | `name_en`, `name_th` |
| Audit Columns | `<action>_by`, `<action>_at` | `approved_by`, `approved_at` |

> 📌 Add any approved exceptions to `docs/standards/naming-exceptions.md` to keep the schema glossary authoritative.

---

## 4. Migration Workflow

### Step 1: Inventory & Mapping (Week 1)

1. Export table/column metadata via `INFORMATION_SCHEMA.COLUMNS` into `docs/schema/iacc_schema_snapshot_YYYYMMDD.csv`.
2. Produce a mapping spreadsheet with columns: `object_type`, `object_name_current`, `object_name_target`, `priority`, `owner`, `status`.
3. Prioritize high-traffic tables (company, product, po, pr, iv, deliver) for early refactor.
4. Secure sign-off from product + reporting stakeholders before any DDL executes.

### Step 2: Controlled Renames (Week 2)

1. For each table, schedule a maintenance window (or use blue/green DB hosts) to run the following pattern:
   - Create new table with desired name/columns.
   - Copy data from old table.
   - Create updatable view or synonym with old name pointing to new table.
   - Deploy code updates that switch to new name.
   - Drop compatibility view when confidence threshold met.
2. Prefer MySQL `RENAME TABLE` only when application downtime is acceptable and table is <5M rows; otherwise use copy + swap.
3. Keep all scripts in `migrations/phase2_naming/` with numbered batches (e.g., `010_company.sql`).
4. Record every change in `docs/schema/naming-migration-log.md` including row counts before/after.

### Step 3: Code Alignment (Week 2-3)

1. Update PHP files (model queries, report builders, stored procedures) using `rg` or `phpcbf` to ensure consistent replacements.
2. Run automated regression tests (login, PO lifecycle, invoicing, reporting) after each table batch.
3. Coordinate with BI/reporting owners to update downstream ETL scripts and dashboards.
4. Communicate release notes to end users if field names surface in UI exports.

### Step 4: Validation

- `SHOW CREATE TABLE <table>` matches new standards.
- `SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='iacc' AND TABLE_NAME LIKE '%1%'` returns zero rows.
- No references to legacy names remain in `iacc/*.php` (grep).
- Performance metrics unchanged (query plans verified via EXPLAIN).
- QA sign-off documented in `docs/schema/naming-test-report.md`.

---

## 5. Risk Mitigation

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Hard-coded table names in legacy PHP | High | Use compatibility views and staged rollouts; run project-wide search before renames |
| BI/ETL breakage | Medium | Provide data dictionary diff; coordinate with analytics team |
| Long transaction locks during copy | Medium | Perform COPY + RENAME in off-peak windows; chunk large tables |
| Rollback complexity | Medium | Keep original tables untouched until validation; have `migrations/phase2_naming/rollback/*.sql` scripts |
| Dual-write drift when two names co-exist | Low | Freeze writes to old table once new table live; enforce via triggers if required |

---

## 6. Deliverables

- `migrations/phase2_naming/` (forward + rollback scripts per table).
- `docs/schema/naming-mapping-YYYYMMDD.xlsx` (authoritative mapping catalog).
- `docs/schema/naming-migration-log.md` (execution journal).
- `docs/schema/naming-test-report.md` (QA evidence).
- Updated `README.md` snippet highlighting new schema conventions.

---

## 7. Suggested Timeline

| Week | Tasks |
| --- | --- |
| Week 1 | Inventory export, mapping spreadsheet, stakeholder approvals |
| Week 2 | Rename high-priority tables (company, product, po, pr) with compatibility views |
| Week 3 | Rename remaining tables, remove temporary views, update docs/test reports |

---

**Next Up**: After naming is standardized, proceed with timestamp adoption (`PHASE2_TIMESTAMPS.md`) to ensure auditing columns match the new schema style.
