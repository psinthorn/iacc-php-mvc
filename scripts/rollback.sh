#!/bin/bash
# iAcc Rollback Script
# Automatically rollback to previous working version
# Usage: ./rollback.sh [backup_date]
# Example: ./rollback.sh 20231215_120000

PROD_DIR="/home/iacc-user/public_html/iacc"
BACKUP_DIR="/home/iacc-user/backups"
LOG_FILE="/var/log/iacc-deploy.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Log function
log_message() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
}

log_message "=== iAcc Rollback Started ==="

# Get backup date from argument or use latest
BACKUP_DATE="${1:-$(ls -1 $BACKUP_DIR/database_pre_deploy_* 2>/dev/null | tail -1 | xargs -n1 basename | sed 's/database_pre_deploy_//; s/.sql.gz//')}"

if [ -z "$BACKUP_DATE" ]; then
    log_error "No backup date specified and no pre-deploy backups found"
    exit 1
fi

echo ""
echo -e "${YELLOW}ROLLBACK WARNING${NC}"
echo "This will restore:"
echo "  Database: $BACKUP_DIR/database_pre_deploy_$BACKUP_DATE.sql.gz"
echo "  Files: $BACKUP_DIR/files_pre_deploy_$BACKUP_DATE.tar.gz"
echo ""
read -p "Continue with rollback? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    log_message "Rollback cancelled"
    exit 0
fi

# Check backups exist
if [ ! -f "$BACKUP_DIR/database_pre_deploy_$BACKUP_DATE.sql.gz" ]; then
    log_error "Database backup not found: $BACKUP_DIR/database_pre_deploy_$BACKUP_DATE.sql.gz"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/files_pre_deploy_$BACKUP_DATE.tar.gz" ]; then
    log_error "File backup not found: $BACKUP_DIR/files_pre_deploy_$BACKUP_DATE.tar.gz"
    exit 1
fi

# Create rollback marker
touch "$PROD_DIR/.rollback-in-progress"

log_message "Starting rollback from $BACKUP_DATE..."

# Step 1: Restore files
log_message "Step 1: Restoring files..."
cd /
tar -xzf "$BACKUP_DIR/files_pre_deploy_$BACKUP_DATE.tar.gz" || {
    log_error "File restore failed"
    rm "$PROD_DIR/.rollback-in-progress"
    exit 1
}
log_message "Files restored"

# Step 2: Restore database
log_message "Step 2: Restoring database..."
zcat "$BACKUP_DIR/database_pre_deploy_$BACKUP_DATE.sql.gz" | mysql -u iacc_user -p iacc_database || {
    log_error "Database restore failed"
    rm "$PROD_DIR/.rollback-in-progress"
    exit 1
}
log_message "Database restored"

# Step 3: Set permissions
log_message "Step 3: Setting file permissions..."
chmod -R 755 "$PROD_DIR"
chmod -R 777 "$PROD_DIR/upload"
chmod 777 "$PROD_DIR/inc"

# Step 4: Clear caches
log_message "Step 4: Clearing caches..."
rm -rf /tmp/mpdf* 2>/dev/null || true

# Step 5: Restart services
log_message "Step 5: Restarting services..."
systemctl restart php-fpm 2>/dev/null || true
systemctl restart apache2 2>/dev/null || true

# Clean up rollback marker
rm "$PROD_DIR/.rollback-in-progress"

log_message "=== ROLLBACK COMPLETED ==="
log_message "System restored to: $BACKUP_DATE"
log_message "Verify site at: https://iacc.f2.co.th"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}ROLLBACK COMPLETED${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "System restored from: $BACKUP_DATE"
echo "Please verify at: https://iacc.f2.co.th"
echo ""

exit 0
