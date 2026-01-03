#!/bin/bash
# ============================================================================
# Database Rollback Script - Use if Migration Fails
# ============================================================================
# Purpose: Restore database from pre-migration backup
# Usage: ./003_rollback_migration.sh [backup_file]
# Date: 2026-01-03
# ============================================================================

set -e  # Exit on any error

# Configuration - Update these values for your environment
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-f2coth_iacc}"
BACKUP_DIR="../backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${RED}============================================${NC}"
echo -e "${RED}  DATABASE ROLLBACK SCRIPT                 ${NC}"
echo -e "${RED}============================================${NC}"
echo -e "${YELLOW}WARNING: This will OVERWRITE the current database!${NC}"
echo ""

# Determine which backup to use
if [ -n "$1" ]; then
    BACKUP_FILE="$1"
else
    # Use the latest pre-migration backup
    BACKUP_FILE="${BACKUP_DIR}/latest_pre_migration.sql"
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}ERROR: Backup file not found: ${BACKUP_FILE}${NC}"
    echo ""
    echo "Available backups in ${BACKUP_DIR}:"
    ls -la "$BACKUP_DIR"/*.sql 2>/dev/null || echo "No SQL backups found"
    echo ""
    echo "Usage: $0 [path_to_backup_file]"
    exit 1
fi

# Confirmation prompt
echo -e "Backup file to restore: ${BLUE}${BACKUP_FILE}${NC}"
echo -e "Target database: ${BLUE}${DB_NAME}${NC}"
echo ""
read -p "Are you sure you want to rollback? This will DELETE all current data! (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Rollback cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${GREEN}[1/4]${NC} Checking database connection..."

# Test database connection
if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Cannot connect to database. Please check credentials.${NC}"
    exit 1
fi

echo -e "${GREEN}[2/4]${NC} Creating safety backup before rollback..."

# Create a backup of current state before rollback (just in case)
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SAFETY_BACKUP="${BACKUP_DIR}/pre_rollback_safety_${TIMESTAMP}.sql"

if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" \
        --single-transaction \
        "$DB_NAME" > "$SAFETY_BACKUP"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction \
        "$DB_NAME" > "$SAFETY_BACKUP"
fi

echo -e "  Safety backup created: ${SAFETY_BACKUP}"

echo -e "${GREEN}[3/4]${NC} Restoring database from backup..."

# Disable foreign key checks during restore
if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=0;"
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$BACKUP_FILE"
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=1;"
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=0;"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_FILE"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=1;"
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Rollback failed!${NC}"
    echo -e "You can try manually restoring from: ${SAFETY_BACKUP}"
    exit 1
fi

echo -e "${GREEN}[4/4]${NC} Verifying database restoration..."

# Quick verification - count tables
if [ -z "$DB_PASS" ]; then
    TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null)
else
    TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null)
fi

echo -e "\n${GREEN}============================================${NC}"
echo -e "${GREEN}  ROLLBACK COMPLETED SUCCESSFULLY!         ${NC}"
echo -e "${GREEN}============================================${NC}"
echo -e "  Restored from: ${BACKUP_FILE}"
echo -e "  Tables in database: ${TABLE_COUNT}"
echo -e "  Safety backup: ${SAFETY_BACKUP}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Note: If you need to undo the rollback, restore from:${NC}"
echo -e "${BLUE}${SAFETY_BACKUP}${NC}"
