#!/bin/bash
# ============================================================================
# Docker Database Rollback Script
# ============================================================================
# Purpose: Restore database from pre-migration backup in Docker environment
# Usage: ./docker_rollback.sh [backup_file]
# Date: 2026-01-03
# ============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

CONTAINER_NAME="iacc_mysql"
DB_USER="root"
DB_PASS="root"
DB_NAME="iacc"
BACKUP_DIR="../backups"

echo -e "${RED}============================================${NC}"
echo -e "${RED}  DATABASE ROLLBACK SCRIPT (Docker)        ${NC}"
echo -e "${RED}============================================${NC}"
echo -e "${YELLOW}WARNING: This will OVERWRITE the current database!${NC}"
echo ""

# Determine which backup to use
if [ -n "$1" ]; then
    BACKUP_FILE="$1"
else
    BACKUP_FILE="${BACKUP_DIR}/pre_migration_backup_20260103_223657.sql"
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}ERROR: Backup file not found: ${BACKUP_FILE}${NC}"
    echo ""
    echo "Available backups:"
    ls -la "$BACKUP_DIR"/*.sql 2>/dev/null || echo "No SQL backups found"
    echo ""
    echo "Usage: $0 [path_to_backup_file]"
    exit 1
fi

echo -e "Backup file to restore: ${BLUE}${BACKUP_FILE}${NC}"
echo -e "Target database: ${BLUE}${DB_NAME}${NC}"
echo ""
read -p "Are you sure you want to rollback? This will DELETE all current data! (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Rollback cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${GREEN}[1/3]${NC} Creating safety backup before rollback..."

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SAFETY_BACKUP="${BACKUP_DIR}/pre_rollback_safety_${TIMESTAMP}.sql"

docker exec ${CONTAINER_NAME} mysqldump -u${DB_USER} -p${DB_PASS} \
    --single-transaction ${DB_NAME} > "$SAFETY_BACKUP" 2>/dev/null

echo -e "  Safety backup created: ${SAFETY_BACKUP}"

echo -e "${GREEN}[2/3]${NC} Restoring database from backup..."

docker exec -i ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} \
    -e "SET FOREIGN_KEY_CHECKS=0;" ${DB_NAME} 2>/dev/null

docker exec -i ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} \
    ${DB_NAME} < "$BACKUP_FILE" 2>/dev/null

docker exec -i ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} \
    -e "SET FOREIGN_KEY_CHECKS=1;" ${DB_NAME} 2>/dev/null

echo -e "${GREEN}[3/3]${NC} Verifying database restoration..."

TABLE_COUNT=$(docker exec ${CONTAINER_NAME} mysql -u${DB_USER} -p${DB_PASS} -N \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null)

echo -e "\n${GREEN}============================================${NC}"
echo -e "${GREEN}  ROLLBACK COMPLETED SUCCESSFULLY!         ${NC}"
echo -e "${GREEN}============================================${NC}"
echo -e "  Restored from: ${BACKUP_FILE}"
echo -e "  Tables in database: ${TABLE_COUNT}"
echo -e "  Safety backup: ${SAFETY_BACKUP}"
echo -e "${GREEN}============================================${NC}"
