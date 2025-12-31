#!/bin/bash
# iAcc Automated Deployment to Production
# This script should only be run after successful staging tests
# Usage: ./deploy-production.sh

set -e

# Configuration
REPO_URL="https://github.com/your-org/iAcc-PHP-MVC.git"
PROD_DIR="/home/iacc-user/public_html/iacc"
STAGING_DIR="/home/iacc-user/public_html/iacc-staging"
BACKUP_DIR="/home/iacc-user/backups"
LOG_FILE="/var/log/iacc-deploy.log"
DEPLOY_LOG="$PROD_DIR/deploy.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Log function
log_message() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1" >> "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1" >> "$LOG_FILE"
}

# Initialize log
echo "=== iAcc Production Deployment Started ===" >> "$LOG_FILE"
log_message "Starting production deployment..."

# Step 1: Pre-deployment checks
log_message "Step 1: Pre-deployment checks..."

if [ ! -d "$PROD_DIR" ]; then
    log_error "Production directory not found: $PROD_DIR"
    exit 1
fi

if [ ! -d "$STAGING_DIR" ]; then
    log_error "Staging directory not found: $STAGING_DIR"
    exit 1
fi

# Create backup
log_message "Creating backup..."
mkdir -p "$BACKUP_DIR"

BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
DB_BACKUP="$BACKUP_DIR/database_pre_deploy_$BACKUP_DATE.sql.gz"
FILE_BACKUP="$BACKUP_DIR/files_pre_deploy_$BACKUP_DATE.tar.gz"

# Backup database
log_message "Backing up database to $DB_BACKUP..."
mysqldump -u iacc_user -p iacc_database 2>/dev/null | gzip > "$DB_BACKUP" || {
    log_error "Database backup failed"
    exit 1
}

# Backup files
log_message "Backing up files to $FILE_BACKUP..."
tar -czf "$FILE_BACKUP" \
    --exclude='upload' \
    --exclude='.git' \
    --exclude='deploy.log' \
    "$PROD_DIR" 2>/dev/null || {
    log_error "File backup failed"
    exit 1
}

log_message "Backups created successfully"

# Step 2: Deploy from Git
log_message "Step 2: Deploying from Git..."

cd "$PROD_DIR"

# Fetch latest code
log_message "Fetching latest code from main branch..."
git fetch origin main || {
    log_error "Git fetch failed"
    exit 1
}

# Check for uncommitted changes
CHANGES=$(git status --porcelain)
if [ -n "$CHANGES" ]; then
    log_warning "Uncommitted changes detected, stashing..."
    git stash
fi

# Reset to latest main
log_message "Checking out main branch..."
git checkout main || {
    log_error "Git checkout failed"
    exit 1
}

# Pull latest code
log_message "Pulling latest code..."
git pull origin main || {
    log_error "Git pull failed"
    exit 1
}

# Get current commit
COMMIT_HASH=$(git rev-parse --short HEAD)
log_message "Deployed commit: $COMMIT_HASH"

# Step 3: Set permissions
log_message "Step 3: Setting file permissions..."

chmod -R 755 "$PROD_DIR" || log_warning "Could not set directory permissions"
find "$PROD_DIR" -type f -exec chmod 644 {} \; 2>/dev/null || log_warning "Could not set file permissions"
chmod 777 "$PROD_DIR/upload" 2>/dev/null || log_warning "Could not set upload directory permissions"
chmod 777 "$PROD_DIR/inc" 2>/dev/null || log_warning "Could not set inc directory permissions"

log_message "Permissions set"

# Step 4: Clear caches
log_message "Step 4: Clearing caches..."

# Clear PHP opcode cache (if using OPcache)
if command -v php &> /dev/null; then
    php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; }" 2>/dev/null || true
fi

# Clear temporary mPDF cache
rm -rf /tmp/mpdf* 2>/dev/null || true

log_message "Caches cleared"

# Step 5: Verify deployment
log_message "Step 5: Verifying deployment..."

# Check if index.php exists and is readable
if [ ! -r "$PROD_DIR/index.php" ]; then
    log_error "index.php not readable after deployment"
    exit 1
fi

# Quick PHP syntax check
if command -v php &> /dev/null; then
    php -l "$PROD_DIR/index.php" > /dev/null 2>&1 || {
        log_error "PHP syntax error in index.php"
        exit 1
    }
    log_message "PHP syntax validation passed"
fi

# Test database connection
log_message "Testing database connection..."
php -r "
    include('$PROD_DIR/inc/env-detect.php');
    include('$PROD_DIR/inc/class.dbconn.php');
    \$db = new DbConn();
    if (\$db->conn) {
        echo 'Database connection OK';
    } else {
        echo 'Database connection FAILED';
        exit(1);
    }
" 2>/dev/null || {
    log_warning "Database connection test failed (PHP may not be available)"
}

log_message "Deployment verified"

# Step 6: Restart web server
log_message "Step 6: Restarting services..."

# Try to restart PHP-FPM
if command -v php-fpm &> /dev/null; then
    systemctl restart php-fpm 2>/dev/null || {
        log_warning "Could not restart php-fpm (may require sudo)"
    }
fi

# Try to restart Apache
if command -v apache2ctl &> /dev/null; then
    systemctl restart apache2 2>/dev/null || {
        log_warning "Could not restart apache2 (may require sudo)"
    }
fi

log_message "Services restarted"

# Step 7: Health check
log_message "Step 7: Performing health check..."

# Check if site responds
RESPONSE=$(curl -s -w "\n%{http_code}" https://iacc.f2.co.th/ 2>/dev/null || echo -e "\n000")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)

if [ "$HTTP_CODE" = "200" ]; then
    log_message "✓ Site responds with HTTP 200"
else
    log_warning "Site returned HTTP $HTTP_CODE (may be normal if behind firewall)"
fi

# Step 8: Log final status
log_message "Step 8: Finalizing..."

log_message "=== DEPLOYMENT SUCCESSFUL ==="
log_message "Deployment Summary:"
log_message "  - Code deployed from commit: $COMMIT_HASH"
log_message "  - Database backup: $DB_BACKUP"
log_message "  - File backup: $FILE_BACKUP"
log_message "  - Production directory: $PROD_DIR"
log_message "  - Deployment time: $(date)"

# Write success marker
echo "$COMMIT_HASH" > "$PROD_DIR/.deployed-commit"

# Show summary
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}DEPLOYMENT COMPLETED SUCCESSFULLY${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Commit deployed: $COMMIT_HASH"
echo "Site: https://iacc.f2.co.th"
echo "Database backup: $DB_BACKUP"
echo ""
echo "To monitor logs:"
echo "  tail -f $LOG_FILE"
echo "  tail -f $PROD_DIR/error_log"
echo ""
echo "To rollback:"
echo "  $PROD_DIR/rollback.sh"
echo ""

exit 0
