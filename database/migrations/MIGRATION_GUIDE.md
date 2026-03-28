# Database Migration Guide

## Q2 2026: Payment Gateway & Multi-Currency

### Migration File
- `q2_2026_payment_gateway.sql`

### What It Creates/Modifies

| # | Type | Object | Description |
|---|------|--------|-------------|
| 1 | CREATE TABLE | `currencies` | ISO 4217 currency master (10 seeded) |
| 2 | CREATE TABLE | `exchange_rates` | Daily exchange rate cache |
| 3 | CREATE TABLE | `tax_reports` | Saved tax reports (PP30, PND3, PND53) |
| 4 | ALTER TABLE | `pay` | Add `wht_rate`, `wht_amount`, `wht_type` columns |
| 5 | ALTER TABLE | `company` | Add `default_currency` column |
| 6 | ALTER TABLE | `iv` | Add `currency_code`, `exchange_rate` columns |
| 7 | INSERT | `payment_methods` | Add PromptPay for active companies |
| 8 | ALTER TABLE | `payment_log` | Add `exchange_rate`, `slip_image` columns |
| 9 | INSERT | `payment_method` | Seed PromptPay gateway config |

### Prerequisites
These tables must already exist (they do in all known database snapshots):
- `pay`, `company`, `iv` (core tables)
- `payment_methods` (plural — per-company payment methods)
- `payment_method` (singular — gateway-level config)
- `payment_gateway_config`
- `payment_log`

### Safety Features
- ✅ `CREATE TABLE IF NOT EXISTS` — safe to re-run
- ✅ `INFORMATION_SCHEMA` checks before ALTER — won't add duplicate columns
- ✅ `INSERT IGNORE` / `NOT EXISTS` — won't create duplicate rows
- ✅ Uses `DATABASE()` — works on any database name (iacc, iacc_dev, etc.)

---

## How to Run

### On Dev Environment (dev.iacc.f2.co.th / cPanel)

```bash
# Option 1: Via cPanel phpMyAdmin
# 1. Go to phpMyAdmin → Select the database
# 2. Click "Import" tab
# 3. Upload q2_2026_payment_gateway.sql
# 4. Click "Go"

# Option 2: Via SSH (if available)
mysql -u<username> -p <database_name> < q2_2026_payment_gateway.sql

# Option 3: Via cPanel Terminal
cd ~/path/to/migrations
mysql -u<username> -p <database_name> < q2_2026_payment_gateway.sql
```

### On Docker Local

```bash
docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/q2_2026_payment_gateway.sql
```

### Verification Queries

After running the migration, verify with:

```sql
-- Check new tables exist
SHOW TABLES LIKE 'currencies';
SHOW TABLES LIKE 'exchange_rates';
SHOW TABLES LIKE 'tax_reports';

-- Check currencies seeded
SELECT COUNT(*) AS currency_count FROM currencies;  -- Should be 10

-- Check new columns on pay table
SHOW COLUMNS FROM pay LIKE 'wht_%';  -- Should show 3 columns

-- Check new columns on company table
SHOW COLUMNS FROM company LIKE 'default_currency';  -- Should show 1 column

-- Check new columns on iv table
SHOW COLUMNS FROM iv LIKE 'currency%';  -- currency_code
SHOW COLUMNS FROM iv LIKE 'exchange%';  -- exchange_rate

-- Check PromptPay added
SELECT * FROM payment_method WHERE code = 'promptpay';
```

---

## Rollback (if needed)

```sql
-- WARNING: Only run these if you need to undo the migration
-- This will DELETE data. Make a backup first!

DROP TABLE IF EXISTS `tax_reports`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `currencies`;

ALTER TABLE `pay` DROP COLUMN IF EXISTS `wht_rate`, DROP COLUMN IF EXISTS `wht_amount`, DROP COLUMN IF EXISTS `wht_type`;
ALTER TABLE `company` DROP COLUMN IF EXISTS `default_currency`;
ALTER TABLE `iv` DROP COLUMN IF EXISTS `currency_code`, DROP COLUMN IF EXISTS `exchange_rate`;
ALTER TABLE `payment_log` DROP COLUMN IF EXISTS `exchange_rate`, DROP COLUMN IF EXISTS `slip_image`;
DELETE FROM `payment_gateway_config` WHERE `config_key` LIKE 'promptpay_%';
DELETE FROM `payment_method` WHERE `code` = 'promptpay';
DELETE FROM `payment_methods` WHERE `method_name` = 'PromptPay';
```
