#!/bin/bash
# ============================================================================
# iAcc Database Restore Script
# Usage: ./scripts/restore-db.sh <backup_file>
# ============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/backups"

# Configuration
MYSQL_CONTAINER="iacc_mysql"
MYSQL_USER="root"
MYSQL_PASSWORD="root"
MYSQL_DATABASE="iacc"

# Check arguments
if [ -z "$1" ]; then
    echo "🗄️  iAcc Database Restore"
    echo "========================"
    echo ""
    echo "Usage: $0 <backup_file>"
    echo ""
    echo "Available backups:"
    ls -lh "$BACKUP_DIR"/*.sql.gz 2>/dev/null || echo "   No backups found in $BACKUP_DIR"
    exit 1
fi

BACKUP_FILE="$1"

# Check if file exists (try with and without backup dir prefix)
if [ ! -f "$BACKUP_FILE" ]; then
    if [ -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
        BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"
    else
        echo "❌ Error: Backup file not found: $BACKUP_FILE"
        exit 1
    fi
fi

echo "🗄️  iAcc Database Restore"
echo "========================"
echo "📅 Date: $(date)"
echo "📁 File: $BACKUP_FILE"
echo ""

# Confirm restore
read -p "⚠️  This will OVERWRITE the current database. Continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "❌ Restore cancelled"
    exit 1
fi

# Check if MySQL container is running
if ! docker ps --format '{{.Names}}' | grep -q "^${MYSQL_CONTAINER}$"; then
    echo "❌ Error: MySQL container '$MYSQL_CONTAINER' is not running"
    exit 1
fi

# Create a backup before restore
echo ""
echo "📦 Creating safety backup before restore..."
"$SCRIPT_DIR/backup-db.sh" manual

# Perform restore
echo ""
echo "🔄 Restoring database..."
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" | docker exec -i $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE 2>/dev/null
else
    docker exec -i $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < "$BACKUP_FILE" 2>/dev/null
fi

echo "✅ Database restored successfully!"
echo ""
echo "📊 Verification:"
docker exec $MYSQL_CONTAINER mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE -e "SELECT COUNT(*) as tables FROM information_schema.tables WHERE table_schema='$MYSQL_DATABASE';" 2>/dev/null
