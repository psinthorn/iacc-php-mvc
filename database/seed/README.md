# database/seed — split SQL files for clean re-seeding

Generated from [`../migrations/f2coth_iacc_saas_01052026.sql`](../migrations/f2coth_iacc_saas_01052026.sql) (staging snapshot, May 1 2026) by [`tmp/split-dump.sh`](../../tmp/split-dump.sh).

## Why split?

The single phpMyAdmin dump conflates schema, reference data, tenant data, auth, module data, and system logs. Splitting lets us:

- **Fresh schema, no data** for unit tests → `01-schema.sql` only
- **New tenant onboarding** → `01-schema.sql` + `02-seed-reference.sql` (no `03-tenants` so nothing is pre-seeded)
- **Local dev clone of production shape** → all 6 files, in order

## File layout

| File | Tables | INSERT batches | What's in it |
|---|---:|---:|---|
| `01-schema.sql` | 84 CREATE + 182 ALTER | 0 | All DDL: structure, indexes, FKs, AUTO_INCREMENTs |
| `02-seed-reference.sql` | 14 | 16 | Static lookups: currencies, payment_method, type, brand, model, category, agent_contract_types, chart_of_accounts, etc. |
| `03-seed-tenants.sql` | 5 | 7 | Tenant core: company, company_addr, company_modules, contract_rate, agent_contracts (213 companies) |
| `04-seed-auth.sql` | 7 | 8 | authorize, user, user_roles, roles, permissions, role_permissions, login_attempts, password_resets (9 admins) |
| `05-seed-modules.sql` | 19 | 75 | Operational: billing, payment, product, store, voucher, expenses, channel_orders, etc. |
| `06-seed-system.sql` | 10 | 15 | System: _migration_log, audit_logs, ai_*, api_*, tmp_* |

**Totals**: 84 tables, 121 INSERT batches (matches the source dump exactly — verified by re-import into `iacc_test`).

## Import order — the dependency chain matters

```
01-schema       (no FK violations — all tables created up front)
  └─ 02-reference   (lookup data first, no deps on tenants/auth)
       └─ 03-tenants   (companies must exist before module data references them)
            └─ 04-auth     (authorize.company_id may reference company.com_id)
                 └─ 05-modules    (most operational data references company_id + auth)
                      └─ 06-system   (audit_logs / api_usage_logs reference earlier rows)
```

Each file disables `FOREIGN_KEY_CHECKS` at the top and re-enables at the bottom — so order is enforced by referential integrity, not by file ordering. But following the chain above keeps things tidy.

## Usage — docker exec recipe

```bash
# Full local clone of staging (drops + recreates iacc — destructive)
docker exec iacc_mysql mysql -uroot -proot -e \
  "DROP DATABASE IF EXISTS iacc; CREATE DATABASE iacc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

for f in 01-schema 02-seed-reference 03-seed-tenants 04-seed-auth 05-seed-modules 06-seed-system; do
  echo "→ $f.sql"
  docker exec -i iacc_mysql mysql -uroot -proot iacc \
    < database/seed/$f.sql
done

# Re-apply v6.1 task_queue migration (not in staging dump)
docker exec -i iacc_mysql mysql -uroot -proot iacc \
  < database/migrations/2026_05_01_task_queue.sql

# Reset a known admin password for local dev (do NOT run in production)
HASH=$(docker exec iacc_php php -r "echo password_hash('iacc-dev-2026', PASSWORD_BCRYPT);")
docker exec iacc_mysql mysql -uroot -proot iacc -e \
  "UPDATE authorize SET password='$HASH', password_migrated=1, locked_until=NULL, failed_attempts=0, email_verified_at=NOW() WHERE email='psinthorn@gmail.com';"
```

## Schema-only re-seed (e.g. for unit tests)

```bash
docker exec -i iacc_mysql mysql -uroot -proot iacc_test < database/seed/01-schema.sql
docker exec -i iacc_mysql mysql -uroot -proot iacc_test < database/seed/02-seed-reference.sql
# Skip 03-tenants → DB has structure + lookups only, ready for test fixtures
```

## Regenerating from a newer staging dump

When staging has new data worth pulling locally:

```bash
# 1) Drop the new dump into database/migrations/
cp ~/Downloads/f2coth_iacc_saas_NEWDATE.sql database/migrations/

# 2) Edit tmp/split-dump.sh — update SRC=... line at top

# 3) Re-run the splitter
bash tmp/split-dump.sh
```

If new tables appear that don't fit any of the existing 5 categories, edit the `classify()` awk function in [`tmp/split-dump.sh`](../../tmp/split-dump.sh) to route them.

## Sensitive-data warning

These files contain:
- Real company names, addresses, phone numbers (213 rows in `company`)
- Real admin email addresses (9 rows in `authorize`) with bcrypt-hashed passwords
- Possibly historical financial data in `05-seed-modules.sql`

**Do not commit these files to a public repo.** Either:
1. Add `database/seed/*.sql` to `.gitignore` and keep the splitter script in-repo so anyone with the source dump can regenerate them, OR
2. Sanitize the seed files (replace real emails/names) before committing

The companion source dump at [`../migrations/f2coth_iacc_saas_01052026.sql`](../migrations/f2coth_iacc_saas_01052026.sql) has the same sensitivity — handle accordingly.
