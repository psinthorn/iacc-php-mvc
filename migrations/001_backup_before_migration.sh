#!/bin/bash
# ============================================================================
# Database Backup Script - Run BEFORE Migration
# ============================================================================
# Purpose: Create a full backup of the database before running migrations
# Usage: ./001_backup_before_migration.sh
# Date: 2026-01-03
# ============================================================================

set -e  # Exit on any error

# Configuration - Update these values for your environment
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-f2coth_iacc}"
BACKUP_DIR="../backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/pre_migration_backup_${TIMESTAMP}.sql"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}============================================${NC}"
echo -e "${YELLOW}  Database Backup Script                   ${NC}"
echo -e "${YELLOW}============================================${NC}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "\n${GREEN}[1/4]${NC} Checking database connection..."

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

echo -e "${GREEN}[2/4]${NC} Connection successful. Starting backup..."

# Create full database backup with structure and data
echo -e "${GREEN}[3/4]${NC} Creating backup: ${BACKUP_FILE}"

if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --complete-insert \
        --extended-insert \
        "$DB_NAME" > "$BACKUP_FILE"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --complete-insert \
        --extended-insert \
        "$DB_NAME" > "$BACKUP_FILE"
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Backup failed!${NC}"
    exit 1
fi

# Get file size
BACKUP_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')

echo -e "${GREEN}[4/4]${NC} Backup completed successfully!"
echo -e "\n${YELLOW}============================================${NC}"
echo -e "  Backup Details:"
echo -e "  - File: ${BACKUP_FILE}"
echo -e "  - Size: ${BACKUP_SIZE}"
echo -e "  - Database: ${DB_NAME}"
echo -e "  - Host: ${DB_HOST}"
echo -e "${YELLOW}============================================${NC}"

# Create a symlink to the latest backup for easy rollback
ln -sf "$(basename "$BACKUP_FILE")" "${BACKUP_DIR}/latest_pre_migration.sql"

echo -e "\n${GREEN}✓ Backup complete. You can now safely run the migration.${NC}"
echo -e "${YELLOW}To rollback, run: ./003_rollback_migration.sh${NC}\n"
