# Phase 2e: Invalid Date Remediation & Application Hardening Guide

**Status**: Ready for implementation  
**Last Updated**: December 31, 2025  
**Owner**: Database Reliability Team  
**Duration**: ~8 engineer-hours (Week 10 of Phase 2 plan)  
**Dependencies**: Timestamp columns provisioned (see `PHASE2_TIMESTAMPS.md`).

---

## 1. Objectives

- Purge `0000-00-00` and other invalid sentinel dates from all 19+ affected columns.
- Replace legacy date defaults with `NULL` and enforce strict column definitions going forward.
- Harden PHP date handling to gracefully manage `NULL`/empty values without warnings.
- Produce verification evidence for auditors and downstream analytics consumers.

---

## 2. Impacted Columns (from `PHASE2_ANALYSIS.md`)

| Table | Column | Notes |
| --- | --- | --- |
| `board1` | `board1_datetime`, `create_date`, `last_post` | Forum module dates |
| `board2` | `board2_datetime` | Replies |
| `board_group` | `board_group_date` | Discussion group |
| `company_addr` | `valid_start`, `valid_end` | Contract dates |
| `company_credit` | `valid_start`, `valid_end` | Credit period |
| `deliver` | `deliver_date` | Logistics |
| `iv` | `createdate`, `texiv_create` | Invoice creation fields |
| `pay` | `date` | Payment record |
| `po` | `date`, `deliver_date`, `valid_pay` | Purchase order lifecycle |
| `pr` | `date` | Purchase request |
| `product` | `vo_warranty` | Warranty expiration |
| `receipt` | `createdate` | Receipt creation |
| `receive` | `date` | Receiving log |

> 📌 Update this table if new columns are discovered during profiling.

---

## 3. Remediation Workflow

### Step 1: Baseline Snapshot

1. Export counts of invalid dates per column to `docs/audit/invalid-dates/findings-YYYYMMDD.csv`:

   ```sql
   SELECT 'po.date' AS column_name, COUNT(*) AS invalid_rows
   FROM po WHERE date = '0000-00-00'
   UNION ALL
   SELECT 'po.deliver_date', COUNT(*)
   FROM po WHERE deliver_date = '0000-00-00'
   -- repeat for remaining columns
   ```

2. Capture max/min valid dates to detect outliers (e.g., < 1990 or > 2100).

### Step 2: Data Cleanup Scripts

1. Replace sentinel dates with `NULL`:

   ```sql
   UPDATE po SET deliver_date = NULL WHERE deliver_date = '0000-00-00';
   ```

2. Normalize other invalid formats (e.g., `'1900-01-00'`, `'2019-13-01'`) via `STR_TO_DATE` or manual review.
3. For mandatory fields, backfill using best available data:
   - Use related timestamps (e.g., `created_at`) where appropriate.
   - For warranty dates, compute from purchase date + warranty months when stored.
4. Log every decision in `docs/audit/invalid-dates/cleanup-log.md` (include SQL snippet, row counts, justification).

### Step 3: Schema Enforcement

1. Alter columns to reject invalid defaults:

   ```sql
   ALTER TABLE po
     MODIFY COLUMN date DATE NULL DEFAULT NULL,
     MODIFY COLUMN deliver_date DATE NULL DEFAULT NULL,
     MODIFY COLUMN valid_pay DATE NULL DEFAULT NULL;
   ```

2. For columns that must be mandatory, set `NOT NULL` with sensible defaults plus application validation.
3. Enable `sql_mode=NO_ZERO_DATE,NO_ZERO_IN_DATE,STRICT_TRANS_TABLES` in MySQL config (or per-session via migration script) to prevent regressions.

### Step 4: Application Hardening

1. Update PHP helpers to check for empty/invalid dates before invoking `strtotime` or formatting functions:

   ```php
   function formatDateSafe(?string $date, string $format = 'd/m/Y'): string
   {
       if (empty($date) || $date === '0000-00-00') {
           return '';
       }
       $timestamp = strtotime($date);
       return $timestamp ? date($format, $timestamp) : '';
   }
   ```

2. Enforce server-side validation on form submissions to reject zero dates.
3. Adjust client-side date pickers to prevent manual entry of invalid values.
4. Audit reports/exports to ensure they treat `NULL` properly (e.g., display "N/A").

### Step 5: Regression & Monitoring

- Run targeted test cases: PO creation, invoice generation, delivery updates, warranty reporting.
- Monitor PHP logs for residual warnings (`strtotime(): Argument must be a string` etc.).
- Add DB monitoring alert when invalid date count > 0 after cleanup.

---

## 4. Validation Checklist

- [ ] `SELECT COUNT(*) FROM <table> WHERE <date_column> = '0000-00-00';` returns zero for every column listed above.
- [ ] `SELECT @@sql_mode;` includes `NO_ZERO_DATE` and `NO_ZERO_IN_DATE`.
- [ ] Application forms reject invalid dates and display clear error messages.
- [ ] Reports/export files show blank or "N/A" instead of `0000-00-00`.
- [ ] QA sign-off recorded in `docs/audit/invalid-dates/test-report.md`.

---

## 5. Rollback Plan

1. Database: Keep backup `backup/iacc_pre_invalid_dates_YYYYMMDD.sql`. If cleanup introduces issues, restore affected tables only.
2. Application: Feature flag date validation (e.g., `DATE_STRICT_MODE=true`). Disable if blocking issue discovered.
3. Config: Revert `sql_mode` change temporarily if third-party tooling breaks; document exception and notify stakeholders.

---

## 6. Deliverables

- `migrations/phase2_invalid_dates/000_cleanup.sql`
- `migrations/phase2_invalid_dates/010_enforce.sql`
- `migrations/phase2_invalid_dates/rollback/*.sql`
- `docs/audit/invalid-dates/findings-YYYYMMDD.csv`
- `docs/audit/invalid-dates/cleanup-log.md`
- `docs/audit/invalid-dates/test-report.md`
- Updated PHP helper (`formatDateSafe`) and usage references

---

**Next Up**: With data normalized, move to Phase 3 architectural improvements per `IMPROVEMENTS_PLAN.md`, starting with modularizing business logic.
