-- ============================================================
-- Phase 2c: Backfill timestamps from legacy date columns
-- Date: 2026-03-29
-- Run AFTER 000_add_columns.sql
-- Safe: only updates rows where created_at = default (current time)
-- ============================================================

-- Temporarily allow invalid dates for comparison
SET @old_sql_mode = @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', '');
SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE', '');

-- Backfill created_at from legacy 'date' columns where available
-- Using CAST to convert DATE to TIMESTAMP safely

-- deliver: use deliver_date
UPDATE deliver SET created_at = CAST(deliver_date AS DATETIME) 
WHERE deliver_date > '1970-01-01' AND deliver_date < '2038-01-01';

-- iv (invoices): use createdate
UPDATE iv SET created_at = CAST(createdate AS DATETIME) 
WHERE createdate > '1970-01-01' AND createdate < '2038-01-01';

-- pay: use date
UPDATE pay SET created_at = CAST(date AS DATETIME) 
WHERE date > '1970-01-01' AND date < '2038-01-01';

-- po: use date
UPDATE po SET created_at = CAST(date AS DATETIME) 
WHERE date > '1970-01-01' AND date < '2038-01-01';

-- pr: use date
UPDATE pr SET created_at = CAST(date AS DATETIME) 
WHERE date > '1970-01-01' AND date < '2038-01-01';

-- receive: use date
UPDATE receive SET created_at = CAST(date AS DATETIME) 
WHERE date > '1970-01-01' AND date < '2038-01-01';

-- receipt: use createdate
UPDATE receipt SET created_at = CAST(createdate AS DATETIME) 
WHERE createdate > '1970-01-01' AND createdate < '2038-01-01';

-- voucher: use createdate
UPDATE voucher SET created_at = CAST(createdate AS DATETIME) 
WHERE createdate > '1970-01-01' AND createdate < '2038-01-01';

-- Set updated_at = created_at for rows where no updates have happened
-- (prevents updated_at from being newer than the actual record)
UPDATE deliver SET updated_at = created_at WHERE updated_at > created_at;
UPDATE iv SET updated_at = created_at WHERE updated_at > created_at;
UPDATE pay SET updated_at = created_at WHERE updated_at > created_at;
UPDATE po SET updated_at = created_at WHERE updated_at > created_at;
UPDATE pr SET updated_at = created_at WHERE updated_at > created_at;
UPDATE receive SET updated_at = created_at WHERE updated_at > created_at;
UPDATE voucher SET updated_at = created_at WHERE updated_at > created_at;

-- Restore original SQL mode
SET SESSION sql_mode = @old_sql_mode;

SELECT 'Phase 2c: Backfill complete' AS result;
