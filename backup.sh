#!/bin/bash

#
# iACC Database Backup Script
# 
# Usage: ./backup.sh [database_name] [backup_dir]
# Example: ./backup.sh iacc /var/backups/iacc
#

DATABASE=${1:-iacc}
BACKUP_DIR=${2:-/var/backups/iacc}
RETENTION_DAYS=${3:-30}

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

# Create timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_${DATABASE}_${TIMESTAMP}.sql.gz"

# Get MySQL credentials from environment or default
MYSQL_HOST=${MYSQL_HOST:-localhost}
MYSQL_USER=${MYSQL_USER:-root}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-root}

echo "[INFO] Starting backup of database: $DATABASE"
echo "[INFO] Backup file: $BACKUP_FILE"

# Create backup
if mysqldump \
    --host="$MYSQL_HOST" \
    --user="$MYSQL_USER" \
    --password="$MYSQL_PASSWORD" \
    --single-transaction \
    --lock-tables=false \
    "$DATABASE" | gzip > "$BACKUP_FILE"; then
    
    FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "[SUCCESS] Backup completed: $FILE_SIZE"
    echo "[INFO] Backup file: $BACKUP_FILE"
    
    # Remove old backups (older than retention days)
    echo "[INFO] Cleaning up old backups (retention: $RETENTION_DAYS days)"
    find "$BACKUP_DIR" -name "backup_${DATABASE}_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
    
    echo "[SUCCESS] Backup script completed"
else
    echo "[ERROR] Backup failed"
    exit 1
fi
