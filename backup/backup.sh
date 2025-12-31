#!/bin/bash
# iAcc Application Backup Script
# Creates comprehensive backups of database and uploaded files
# Run this before any major upgrades or deployments

set -e  # Exit on error

BACKUP_DIR="/home/iacc-user/backups"
DATE=$(date +%Y%m%d_%H%M%S)
LOG_FILE="$BACKUP_DIR/backup_$DATE.log"

# Database configuration
DB_HOST="localhost"
DB_USER="iacc_user"
DB_PASS="your_password_here"  # Update this
DB_NAME="iacc_database"

# Directory to backup
APP_DIR="/home/iacc-user/public_html/iacc"

echo "=== iAcc Backup Started at $(date) ===" > "$LOG_FILE"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# 1. Backup MySQL Database
echo "Backing up MySQL database..." | tee -a "$LOG_FILE"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/database_$DATE.sql.gz"
echo "✓ Database backup completed: database_$DATE.sql.gz" | tee -a "$LOG_FILE"

# 2. Backup Uploaded Files
echo "Backing up uploaded files..." | tee -a "$LOG_FILE"
tar -czf "$BACKUP_DIR/upload_$DATE.tar.gz" -C "$APP_DIR" upload/ 2>/dev/null || echo "Warning: upload directory might not exist" | tee -a "$LOG_FILE"
echo "✓ Upload files backup completed: upload_$DATE.tar.gz" | tee -a "$LOG_FILE"

# 3. Backup Application Files
echo "Backing up application files..." | tee -a "$LOG_FILE"
tar -czf "$BACKUP_DIR/app_$DATE.tar.gz" -C "$APP_DIR/.." iacc/ --exclude='upload' --exclude='.git' 2>/dev/null
echo "✓ Application backup completed: app_$DATE.tar.gz" | tee -a "$LOG_FILE"

# 4. Keep only last 10 backups
echo "Cleaning old backups..." | tee -a "$LOG_FILE"
ls -1tr "$BACKUP_DIR"/database_*.sql.gz | head -n -10 | xargs -r rm
ls -1tr "$BACKUP_DIR"/upload_*.tar.gz | head -n -10 | xargs -r rm
ls -1tr "$BACKUP_DIR"/app_*.tar.gz | head -n -10 | xargs -r rm
echo "✓ Old backups cleaned" | tee -a "$LOG_FILE"

# 5. Backup Summary
echo "" | tee -a "$LOG_FILE"
echo "=== Backup Summary ===" | tee -a "$LOG_FILE"
du -sh "$BACKUP_DIR"/database_$DATE.sql.gz "$BACKUP_DIR"/upload_$DATE.tar.gz "$BACKUP_DIR"/app_$DATE.tar.gz 2>/dev/null | tee -a "$LOG_FILE"
echo "Total backups in directory:" | tee -a "$LOG_FILE"
ls -lh "$BACKUP_DIR"/ | grep -E "database|upload|app" | wc -l | tee -a "$LOG_FILE"

echo "=== Backup Completed at $(date) ===" | tee -a "$LOG_FILE"
echo "Log saved to: $LOG_FILE"
