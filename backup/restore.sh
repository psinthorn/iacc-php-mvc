#!/bin/bash
# iAcc Restore from Backup Script
# Restores database and files from backup
# Usage: ./restore.sh database_20231231_120000.sql.gz

set -e

BACKUP_DIR="/home/iacc-user/backups"
BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: ./restore.sh <backup_file>"
    echo "Available backups:"
    ls -lh "$BACKUP_DIR"/database_*.sql.gz
    exit 1
fi

# Database configuration
DB_HOST="localhost"
DB_USER="iacc_user"
DB_PASS="your_password_here"  # Update this
DB_NAME="iacc_database"

echo "WARNING: This will overwrite your current database!"
echo "Backup file: $BACKUP_FILE"
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled"
    exit 0
fi

echo "Restoring database from $BACKUP_FILE..."
zcat "$BACKUP_DIR/$BACKUP_FILE" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"

echo "✓ Database restore completed"
echo "Restore log: $BACKUP_DIR/restore_$(date +%Y%m%d_%H%M%S).log"
