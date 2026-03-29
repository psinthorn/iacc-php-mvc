# Phase 2c: Timestamp Adoption & Historical Backfill Guide

**Status**: Ready for execution  
**Last Updated**: December 31, 2025  
**Owner**: Data Integrity Task Force  
**Duration**: ~8 engineer-hours across 1 calendar week  
**Dependencies**: Foreign keys and naming strategy baselined (see `PHASE2_FOREIGN_KEYS.md` and `PHASE2_NAMING.md`).

---

## 1. Objectives

- Ensure every table records `created_at` and `updated_at` (and optional `deleted_at`) values using consistent data types and defaults.
- Preserve historical accuracy by backfilling timestamps from existing legacy columns (`date`, `createdate`, `board_datetime`, etc.).
- Provide safe migration scripts that can run incrementally on large tables without exceeding lock timeouts.
- Deliver verification tooling so QA can confirm timestamp coverage programmatically.

---

## 2. Scope Summary (from `PHASE2_ANALYSIS.md`)

| Category | Count | Notes |
| --- | --- | --- |
| Tables lacking any timestamps | 11 | e.g., `authorize`, `band`, `company`, `type`, `user`, `voucher` |
| Tables with partial/legacy timestamps | 8 | e.g., `po.date`, `board1.create_date`, `product.vo_warranty` |
| Date columns storing `0000-00-00` | 19+ | Must be normalized to `NULL` before migration |

> 📌 Tackle `NULL` cleanup concurrently with this effort to avoid invalid defaults propagating into new columns.

---

## 3. Standard Timestamp Contract

| Column | Type | Default | Description |
| --- | --- | --- | --- |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Row creation timestamp; never updated after insert |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last mutation time; automatically refreshed |
| `deleted_at` *(optional)* | `TIMESTAMP NULL` | `DEFAULT NULL` | Soft-delete marker for tables that need archival semantics |

Implementation rules:

1. Use `DATETIME` only if timezone preservation is required; otherwise `TIMESTAMP` suffices (MySQL 5.7 default timezone = UTC).
2. Add `updated_at` even if table is append-only; future changes may require it.
3. Index combination `(updated_at)` where reporting dashboards rely on recency filters.
4. Add `CHECK (created_at <= updated_at)` constraint when MySQL 8+ becomes available; document as future enhancement.

---

## 4. Migration Workflow

### Step 1: Prep Scripts

- Create master script `migrations/phase2_timestamps/000_add_columns.sql` containing `ALTER TABLE` statements for all tables lacking timestamps.
- For large tables (>1M rows), split into multiple files to control deployment windows.
- Wrap each table block in its own transaction for easier rollback.

Example template:

```sql
ALTER TABLE <table_name>
  ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER <reference_column>,
  ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;
```

### Step 2: Backfill Plan

Create `migrations/phase2_timestamps/010_backfill.sql` using the following rules:

1. **Direct Mapping**: If table already has `create_date`, `createdate`, or `date` representing creation time, copy into `created_at`.

   ```sql
   UPDATE board1 SET created_at = COALESCE(create_date, NOW()) WHERE created_at IS NULL;
   ```

2. **Updated At**: Use `last_update`, `last_post`, or other audit columns if they exist; otherwise default to `created_at`.

   ```sql
   UPDATE board1 SET updated_at = COALESCE(last_post, created_at);
   ```

3. **No Legacy Columns**: Use best-effort inference (e.g., minimum of related child timestamps) or fallback to rollout timestamp.
4. **Soft Deletes**: For tables already using flags (`status = 'void'`), set `deleted_at = NOW()` where flagged.
5. Chunk updates for large tables:

   ```sql
   UPDATE product SET created_at = COALESCE(vo_warranty, NOW())
   WHERE created_at IS NULL
   LIMIT 5000;
   ```



### Step 3: Enforce Non-Null & Defaults

- After backfill, run `SHOW WARNINGS` to ensure no truncated dates.
- Set `ALTER TABLE <table> MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;` etc.
- For ETL tools inserting without timestamps, update stored procedures to omit explicit values so defaults apply.

### Step 4: Application Updates

- Update PHP insert/update queries to exclude timestamp columns (let MySQL manage them) or explicitly set via prepared statements when necessary.
- Add helpers in `iacc/inc/class.hard.php` or equivalent to format timestamps for UI (Thai + English locales).
- Ensure export reports handle `NULL` `deleted_at` gracefully.

---

## 5. Validation Checklist

- [ ] `SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='iacc' AND COLUMN_NAME='created_at';` returns **31 rows** (one per table).
- [ ] Same query for `updated_at` returns **31 rows**; `deleted_at` matches tables flagged for soft deletes.
- [ ] Random sampling: `SELECT created_at, updated_at FROM <table> ORDER BY RAND() LIMIT 10;` shows non-null, chronologically sane values.
- [ ] Application regression: create/edit/delete flows show accurate timestamps in UI/export.
- [ ] ETL/BI verification: dashboards ingest new columns without schema mismatch.
- [ ] Monitoring: check for slow query spikes due to added columns or default conversions.

---

## 6. Rollback Strategy

1. Keep pre-migration backups (`backup/iacc_pre_timestamps_YYYYMMDD.sql`).
2. Provide `migrations/phase2_timestamps/rollback/000_drop_columns.sql` that removes the new columns per table.
3. If only backfill logic fails, re-run the specific block inside a transaction; no need to drop columns.

---

## 7. Deliverables

- `migrations/phase2_timestamps/000_add_columns.sql`
- `migrations/phase2_timestamps/010_backfill.sql`
- `migrations/phase2_timestamps/rollback/000_drop_columns.sql`
- `docs/schema/timestamp-coverage-report.md` (include before/after counts per table)
- QA evidence: screenshots or logs of regression suite + SQL validation queries

---

**Next Up**: With timestamps standardized, proceed to `PHASE2_AUDIT.md` to leverage these columns for comprehensive change tracking.
