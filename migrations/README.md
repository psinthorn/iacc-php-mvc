# Database Migration Scripts

## Overview

These scripts address critical database issues identified in the iAcc-PHP-MVC application:

1. **Mixed storage engines** (MyISAM → InnoDB)
2. **Inconsistent character sets** (latin1/utf8 → utf8mb4)
3. **Problematic primary key design** on `authorize` table
4. **Missing indexes** for performance
5. **No foreign key constraints**

## Files

| File | Purpose |
|------|---------|
| `001_backup_before_migration.sh` | Creates full database backup before migration |
| `002_critical_database_fixes.sql` | Main migration script with all fixes |
| `003_rollback_migration.sh` | Restores database from backup if migration fails |
| `004_run_migration.sh` | Complete migration runner (backup + migrate + verify) |
| `005_rollback_migration.sql` | SQL-only partial rollback (structural changes only) |

## Quick Start

### Option 1: Automated (Recommended)

```bash
# Set your database credentials
export DB_HOST=localhost
export DB_USER=root
export DB_PASS=your_password
export DB_NAME=f2coth_iacc

# Navigate to migrations directory
cd migrations

# Make scripts executable
chmod +x *.sh

# Run the complete migration
./004_run_migration.sh
```

### Option 2: Manual

```bash
# Step 1: Create backup
./001_backup_before_migration.sh

# Step 2: Run migration
mysql -u root -p f2coth_iacc < 002_critical_database_fixes.sql

# If something goes wrong:
./003_rollback_migration.sh
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | localhost | MySQL server hostname |
| `DB_USER` | root | MySQL username |
| `DB_PASS` | (empty) | MySQL password |
| `DB_NAME` | f2coth_iacc | Database name |

## What the Migration Does

### Phase 1: Convert to InnoDB
- Converts all MyISAM tables to InnoDB
- Enables transaction support
- Enables foreign key constraints

### Phase 2: Standardize Character Set
- Converts all tables to `utf8mb4_unicode_ci`
- Fixes Thai character support
- Enables emoji support

### Phase 3: Fix `authorize` Table
- Adds new `id` column (INT UNSIGNED AUTO_INCREMENT)
- Changes primary key from `usr_name` to `id`
- Adds unique constraint on `usr_name`
- Expands `usr_name` from VARCHAR(10) to VARCHAR(50)

### Phase 4: Add Indexes
- Adds indexes on frequently queried columns
- Improves query performance for dates, statuses, foreign keys

### Phase 5: Data Cleanup
- Removes orphan records before adding FK constraints
- Validates data integrity

### Phase 6: Migration Log
- Creates `_migration_log` table to track migrations
- Logs migration success/failure

## Rollback Procedures

### Full Rollback (Recommended)
```bash
./003_rollback_migration.sh
```
This restores the database from the pre-migration backup.

### Partial Rollback (SQL Only)
```bash
mysql -u root -p f2coth_iacc < 005_rollback_migration.sql
```
This reverts structural changes but keeps InnoDB and utf8mb4 (recommended to keep these).

### Manual Rollback from Specific Backup
```bash
./003_rollback_migration.sh /path/to/specific_backup.sql
```

## Post-Migration Checklist

After running the migration, verify:

- [ ] Application login works
- [ ] User creation works
- [ ] All CRUD operations function correctly
- [ ] Thai characters display properly
- [ ] Reports generate correctly
- [ ] No error logs related to database

## PHP Code Changes Required

After migration, update any code that uses `usr_name` as a foreign key:

```php
// Before (using usr_name)
$sql = "SELECT * FROM po WHERE usr_id = '$usr_name'";

// After (using id from authorize table)
$sql = "SELECT p.* FROM po p 
        JOIN authorize a ON p.usr_id = a.id 
        WHERE a.usr_name = '$usr_name'";
```

## Backup Location

Backups are stored in: `../backups/`

Files created:
- `pre_migration_backup_YYYYMMDD_HHMMSS.sql` - Full backup before migration
- `latest_pre_migration.sql` - Symlink to most recent backup
- `pre_rollback_safety_YYYYMMDD_HHMMSS.sql` - Safety backup before rollback

## Troubleshooting

### Error: Cannot connect to database
```bash
# Check MySQL is running
systemctl status mysql

# Verify credentials
mysql -u root -p -e "SELECT 1"
```

### Error: Foreign key constraint fails
```bash
# Check for orphan records
mysql -u root -p f2coth_iacc -e "SELECT * FROM product WHERE po_id NOT IN (SELECT id FROM po) AND po_id != 0"
```

### Error: Table doesn't exist
Some tables in the migration may not exist in your database. Edit the SQL file to comment out those tables.

## Support

If you encounter issues:

1. Check the error message carefully
2. Review the backup files in `../backups/`
3. Run the rollback script
4. Contact the database administrator

---

**Created:** 2026-01-03  
**Author:** Database Migration System  
**Version:** 1.0.0
