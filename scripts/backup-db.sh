#!/bin/bash
# ============================================================================
# iAcc Database Backup Script
# Usage: ./scripts/backup-db.sh [daily|weekly|manual]
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DAY_OF_WEEK=$(date +%u)

# Configuration
MYSQL_CONTAINER="iacc_mysql"
MYSQL_USER="root"
MYSQL_PASSWORD="root"
MYSQL_DATABASE="iacc"
RETENTION_DAYS=30
RETENTION_WEEKLY=12  # weeks

# Backup type (default: manual)
BACKUP_TYPE="${1:-manual}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "🗄️  iAcc Database Backup"
echo "========================"
echo "📅 Date: $(date)"
echo "📁 Type: $BACKUP_TYPE"
echo ""

# Generate backup filename
case $BACKUP_TYPE in
    daily)
        BACKUP_FILE="iacc_daily_${DATE}.sql.gz"
        ;;
    weekly)
        BACKUP_FILE="iacc_weekly_$(date +%Y_week%W).sql.gz"
        ;;
    *)
        BACKUP_FILE="iacc_backup_${DATE}.sql.gz"
        ;;
esac

BACKUP_PATH="$BACKUP_DIR/$BACKUP_FILE"

# Check if MySQL container is running
if ! docker ps --format '{{.Names}}' | grep -q "^${MYSQL_CONTAINER}$"; then
    echo "❌ Error: MySQL container '$MYSQL_CONTAINER' is not running"
    exit 1
fi

# Perform backup
echo "📦 Creating backup: $BACKUP_FILE"
docker exec $MYSQL_CONTAINER mysqldump \
    -u$MYSQL_USER \
    -p$MYSQL_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    --add-drop-table \
    $MYSQL_DATABASE 2>/dev/null | gzip > "$BACKUP_PATH"

# Check if backup was created successfully
if [ -f "$BACKUP_PATH" ] && [ -s "$BACKUP_PATH" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_PATH" | cut -f1)
    echo "✅ Backup created successfully: $BACKUP_SIZE"
    echo "📍 Location: $BACKUP_PATH"
else
    echo "❌ Backup failed!"
    rm -f "$BACKUP_PATH"
    exit 1
fi

# Cleanup old backups
echo ""
echo "🧹 Cleaning up old backups..."

# Remove daily backups older than retention days
find "$BACKUP_DIR" -name "iacc_daily_*.sql.gz" -mtime +$RETENTION_DAYS -delete 2>/dev/null && \
    echo "   Removed daily backups older than $RETENTION_DAYS days" || true

# Remove weekly backups older than retention weeks
find "$BACKUP_DIR" -name "iacc_weekly_*.sql.gz" -mtime +$((RETENTION_WEEKLY * 7)) -delete 2>/dev/null && \
    echo "   Removed weekly backups older than $RETENTION_WEEKLY weeks" || true

# List recent backups
echo ""
echo "📋 Recent backups:"
ls -lh "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -5 || echo "   No backups found"

echo ""
echo "✅ Backup complete!"
