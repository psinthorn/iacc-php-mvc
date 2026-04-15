# Database Migration Scripts

## Overview

Sequential migration scripts for the iACC database. Run in order (001 → 020).  
**MySQL Version**: 5.7 (matching cPanel production)

## SQL Migrations

### Phase 1: Database Hardening (Jan 3, 2026)
| # | File | Purpose |
|---|------|---------|
| 001 | `001_critical_database_fixes.sql` | MyISAM → InnoDB, charset → utf8mb4, authorize PK fix, indexes |
| 002 | `002_remaining_database_fixes.sql` | Additional table conversions and index fixes |
| 003 | `003_rollback_foundation.sql` | SQL-only rollback for Phase 1 changes |

### Phase 2: RBAC & Auth (Jan 2-4, 2026)
| # | File | Purpose |
|---|------|---------|
| 004 | `004_rbac_setup.sql` | Create roles, permissions, user_roles tables |
| 005 | `005_rbac_schema.sql` | Phase 2 authorization schema |
| 006 | `006_add_company_id_to_authorize.sql` | Add company_id FK to authorize table |

### Phase 3: Business Tables (Jan 2-4, 2026)
| # | File | Purpose |
|---|------|---------|
| 007 | `007_create_payment_methods_table.sql` | Multi-channel payment methods table |
| 008 | `008_create_receipt_table.sql` | Receipt table with quotation support |
| 009 | `009_add_foreign_keys.sql` | FK constraints across all tables |

### Phase 4: Multi-Tenant (Jan 4, 2026)
| # | File | Purpose |
|---|------|---------|
| 010 | `010_add_company_id_multi_tenant.sql` | Add company_id to all business tables |
| 011 | `011_fix_master_data_relationships.sql` | Fix master data FK relationships |
| 012 | `012_add_company_id_to_company.sql` | Company self-reference for multi-tenant |

### Phase 5: AI & Cleanup (Jan 4-5, 2026)
| # | File | Purpose |
|---|------|---------|
| 013 | `013_ai_conversations.sql` | AI chatbot conversation & action log tables |
| 014 | `014_ai_action_log_result.sql` | Add result column to ai_action_log |
| 015 | `015_database_cleanup.sql` | Remove unused tables, merge duplicates |
| 016 | `016_add_soft_delete.sql` | Add deleted_at columns for soft delete |

### Phase 6: API & Sales Channel (Mar 27, 2026)
| # | File | Purpose |
|---|------|---------|
| 017 | `017_api_tables.sql` | Complete API infrastructure tables |
| 018 | `018_rename_booking_to_channel_order.sql` | Rename booking → channel_order |
| 019 | `019_create_api_invoices.sql` | API billing table |

### Phase 7: Split Invoice WHT (Mar 30, 2026)
| # | File | Purpose |
|---|------|---------|
| 020 | `020_split_invoice_wht.sql` | Split invoice for WHT separation |

### Timestamp Migrations (Mar 29, 2026)
| File | Purpose |
|------|---------|
| `phase2_timestamps/000_add_columns.sql` | Add created_at/updated_at to all 59 tables |
| `phase2_timestamps/010_backfill.sql` | Backfill timestamps with existing data |
| `phase2_timestamps/rollback/000_drop_columns.sql` | Rollback timestamp columns |

## Shell Scripts (`scripts/`)

| File | Purpose |
|------|---------|
| `scripts/001_backup_before_migration.sh` | Creates full database backup before migration |
| `scripts/003_rollback_migration.sh` | Restores database from backup if migration fails |
| `scripts/004_run_migration.sh` | Complete migration runner (backup + migrate + verify) |
| `scripts/docker_rollback.sh` | Docker-specific rollback helper |

## Other
| File | Purpose |
|------|---------|
| `phase2_naming/README.md` | Convention guide for future naming migrations |

## Quick Start

### Docker (Development)
```bash
# Run a specific migration
docker exec -i iacc_mysql mysql -uroot -proot iacc < migrations/001_critical_database_fixes.sql

# Run all migrations in order
for f in migrations/0*.sql; do
  echo "Running $f..."
  docker exec -i iacc_mysql mysql -uroot -proot iacc < "$f"
done
```

### cPanel (Production)
```bash
# Via phpMyAdmin: Import tab → Upload file → Go
# Via SSH:
mysql -u<username> -p <database_name> < migrations/001_critical_database_fixes.sql
```

### Automated Runner
```bash
cd migrations/scripts
chmod +x *.sh
export DB_HOST=localhost DB_USER=root DB_PASS=root DB_NAME=iacc
./004_run_migration.sh
```

## Rollback

```bash
# Full rollback from backup
./scripts/003_rollback_migration.sh

# SQL-only rollback (Phase 1 structural changes)
docker exec -i iacc_mysql mysql -uroot -proot iacc < migrations/003_rollback_foundation.sql

# Timestamp rollback
docker exec -i iacc_mysql mysql -uroot -proot iacc < migrations/phase2_timestamps/rollback/000_drop_columns.sql
```

---

**MySQL**: 5.7 (matching cPanel production)  
**Created:** 2026-01-03 | **Updated:** 2026-03-30  
**Version:** 2.0.0
