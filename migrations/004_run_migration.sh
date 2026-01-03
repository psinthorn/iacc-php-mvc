#!/bin/bash
# ============================================================================
# Migration Runner Script
# ============================================================================
# Purpose: Safely run the database migration with backup and validation
# Usage: ./004_run_migration.sh
# Date: 2026-01-03
# ============================================================================

set -e  # Exit on any error

# Configuration - Update these values for your environment
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-f2coth_iacc}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  Database Migration Runner                ${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# Step 1: Pre-flight checks
echo -e "${GREEN}[Step 1/5]${NC} Running pre-flight checks..."

# Check if MySQL is available
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}ERROR: mysql command not found. Please install MySQL client.${NC}"
    exit 1
fi

# Check database connection
echo -n "  Checking database connection... "
if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" > /dev/null 2>&1
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}FAILED${NC}"
    echo -e "${RED}ERROR: Cannot connect to database.${NC}"
    echo "Please set environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME"
    exit 1
fi
echo -e "${GREEN}OK${NC}"

# Check if migration file exists
echo -n "  Checking migration file... "
if [ ! -f "${SCRIPT_DIR}/002_critical_database_fixes.sql" ]; then
    echo -e "${RED}FAILED${NC}"
    echo -e "${RED}ERROR: Migration file not found.${NC}"
    exit 1
fi
echo -e "${GREEN}OK${NC}"

# Step 2: Confirmation
echo ""
echo -e "${YELLOW}============================================${NC}"
echo -e "${YELLOW}  MIGRATION WILL APPLY THE FOLLOWING:      ${NC}"
echo -e "${YELLOW}============================================${NC}"
echo "  1. Convert all tables to InnoDB engine"
echo "  2. Standardize charset to utf8mb4_unicode_ci"
echo "  3. Fix authorize table primary key"
echo "  4. Add performance indexes"
echo "  5. Clean orphan records"
echo "  6. Create migration log table"
echo ""
echo -e "  Target database: ${BLUE}${DB_NAME}${NC} on ${BLUE}${DB_HOST}${NC}"
echo ""

read -p "Do you want to proceed with the migration? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Migration cancelled.${NC}"
    exit 0
fi

# Step 3: Create backup
echo ""
echo -e "${GREEN}[Step 2/5]${NC} Creating pre-migration backup..."
cd "$SCRIPT_DIR"
chmod +x 001_backup_before_migration.sh
./001_backup_before_migration.sh

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Backup failed! Migration aborted.${NC}"
    exit 1
fi

# Step 4: Run migration
echo ""
echo -e "${GREEN}[Step 3/5]${NC} Running migration..."

if [ -z "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "${SCRIPT_DIR}/002_critical_database_fixes.sql"
else
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "${SCRIPT_DIR}/002_critical_database_fixes.sql"
fi

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Migration failed!${NC}"
    echo -e "${YELLOW}Attempting automatic rollback...${NC}"
    chmod +x 003_rollback_migration.sh
    ./003_rollback_migration.sh
    exit 1
fi

# Step 5: Verification
echo ""
echo -e "${GREEN}[Step 4/5]${NC} Verifying migration..."

# Check if authorize table has new id column
if [ -z "$DB_PASS" ]; then
    HAS_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -N -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='authorize' AND column_name='id';" 2>/dev/null)
    ENGINE_CHECK=$(mysql -h "$DB_HOST" -u "$DB_USER" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND engine='InnoDB';" 2>/dev/null)
else
    HAS_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='$DB_NAME' AND table_name='authorize' AND column_name='id';" 2>/dev/null)
    ENGINE_CHECK=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND engine='InnoDB';" 2>/dev/null)
fi

echo -n "  Authorize table has id column: "
if [ "$HAS_ID" -eq 1 ]; then
    echo -e "${GREEN}YES${NC}"
else
    echo -e "${RED}NO${NC}"
fi

echo -e "  Tables using InnoDB: ${GREEN}${ENGINE_CHECK}${NC}"

# Step 6: Complete
echo ""
echo -e "${GREEN}[Step 5/5]${NC} Migration complete!"

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  MIGRATION COMPLETED SUCCESSFULLY!        ${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Important Notes:${NC}"
echo "  1. Test your application thoroughly"
echo "  2. Check any PHP code that uses 'usr_name' as a key"
echo "  3. Update queries to use 'id' instead of 'usr_name' where appropriate"
echo ""
echo -e "${YELLOW}If you encounter issues:${NC}"
echo "  Run: ./003_rollback_migration.sh"
echo ""
