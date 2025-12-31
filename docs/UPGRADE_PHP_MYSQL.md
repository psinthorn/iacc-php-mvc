# cPanel PHP 8.3 & MySQL 8.0 Upgrade Guide

## Pre-Upgrade Checklist

### Step 1: Backup Everything (CRITICAL)

```bash
# Login to cPanel server via SSH
ssh root@f2.co.th

# Create backup directory
mkdir -p /backup/iacc-pre-upgrade-$(date +%Y%m%d)

# Backup database
cd /backup/iacc-pre-upgrade-$(date +%Y%m%d)
mysqldump -u iacc_user -p iacc_database > iacc_database.sql
mysqldump -u iacc_user -p iacc_staging > iacc_staging.sql
gzip *.sql

# Backup application files
tar -czf iacc_files.tar.gz /home/iacc-user/public_html/iacc/
tar -czf iacc_staging_files.tar.gz /home/iacc-user/public_html/iacc-staging/

# Verify backups
ls -lh /backup/iacc-pre-upgrade-$(date +%Y%m%d)/
```

**Backup should contain:**
- ✓ iacc_database.sql.gz (main database)
- ✓ iacc_staging.sql.gz (staging database)
- ✓ iacc_files.tar.gz (~50-100MB)
- ✓ iacc_staging_files.tar.gz (~50-100MB)

---

## Part A: PHP 8.3 Upgrade

### Option A1: Via cPanel EasyApache 4 (Recommended)

**Step 1: Access WHM**
```
1. Login to WHM: https://f2.co.th:2087
2. Username: root
3. Password: [your_password]
```

**Step 2: Navigate to EasyApache**
```
WHM Home > Software > EasyApache 4
Or: https://f2.co.th:2087/scripts/easyapache
```

**Step 3: Review Current Configuration**
- Current PHP Version: Should show current version (7.4, 8.0, etc.)
- Current Apache/Nginx version
- Current Extensions

**Step 4: Change PHP Version**
- Click "Customize" or "Currently Installed Packages"
- Scroll to "PHP" section
- Select version: **8.3.x**
- Ensure these extensions are selected:
  - ✓ bcmath
  - ✓ cURL
  - ✓ GD (for image processing)
  - ✓ gzip
  - ✓ intl
  - ✓ mbstring
  - ✓ mysqli (for database)
  - ✓ pdo_mysql (backup database driver)
  - ✓ xml
  - ✓ zip (for file operations)

**Step 5: Start Build**
- Click "Start 3 of 3"
- Compilation will begin (takes 15-30 minutes)
- **Do not interrupt!** System will restart services

**Step 6: Verify Installation**
```bash
php -v
# Should output: PHP 8.3.x (CLI) ... Zend Engine x.x
```

### Option A2: Via Command Line (If WHM Unavailable)

```bash
# Check current version
php -v

# If using MultiPHP Manager:
/scripts/php_installer --list
/scripts/php_installer --install 8.3

# Or rebuild EasyApache:
/scripts/easyapache --build
```

---

## Part B: MySQL 8.0 Upgrade

### Option B1: Via WHM (Recommended)

**Step 1: Access WHM MySQL Upgrade Tool**
```
WHM Home > Software > MySQL Upgrade
Or: https://f2.co.th:2087/scripts/upcp
```

**Step 2: Check Current Version**
```bash
mysql --version
# Current: mysql Ver 5.7.x
# Target: mysql Ver 8.0.x
```

**Step 3: Create Pre-Upgrade Backup**

**CRITICAL: Do this again before upgrade**
```bash
mysqldump --all-databases | gzip > /backup/mysql_all_$(date +%Y%m%d_%H%M%S).sql.gz
```

**Step 4: Start Upgrade**
- In WHM, select version: **MySQL 8.0.x**
- Click "Start Upgrade"
- System will:
  1. Stop MySQL service
  2. Upgrade binaries
  3. Run mysql_upgrade
  4. Start MySQL service

**Step 5: Monitor Upgrade**
```bash
# Watch the upgrade process
tail -f /var/log/cPanel/mysql_upgrade.log

# Check MySQL is running
systemctl status mysql

# Verify version
mysql --version
```

**Step 6: Verify Database Integrity**
```bash
# Check database
mysql -u iacc_user -p iacc_database -e "CHECK TABLE \`*\` ;"

# Run mysqlcheck
mysqlcheck -u root -p --all-databases

# Test queries
mysql -u iacc_user -p iacc_database -e "SELECT COUNT(*) FROM companies;"
```

### Option B2: Command Line Upgrade

```bash
# Stop MySQL
systemctl stop mysql

# Backup
mysqldump --all-databases | gzip > /backup/mysql_backup.sql.gz

# Check for issues
mysql_upgrade -u root -p

# Start MySQL
systemctl start mysql

# Verify
mysql --version
```

---

## Part C: Application Compatibility Check

### Step 1: Test PHP 8.3 Compatibility

After PHP upgrade, test the application:

```bash
# SSH to server
ssh root@f2.co.th

# Check for PHP errors
tail -100 /usr/local/apache2/logs/error_log | grep -i php

# Test PHP can connect to MySQL
php -r "
\$conn = mysqli_connect('localhost', 'iacc_user', 'password', 'iacc_database');
if (\$conn) {
    echo 'PHP to MySQL: OK';
    mysqli_close(\$conn);
} else {
    echo 'PHP to MySQL: FAILED';
}
"
```

### Step 2: Test Database Connection from Application

```bash
# Create test script
cat > /tmp/test_db.php << 'EOF'
<?php
include('/home/iacc-user/public_html/iacc/inc/env-detect.php');
include('/home/iacc-user/public_html/iacc/inc/class.dbconn.php');

try {
    $db = new DbConn();
    echo "Database Connection: OK\n";
    
    $result = mysqli_query($db->conn, "SELECT DATABASE();");
    echo "Current Database: " . mysqli_fetch_row($result)[0] . "\n";
    
    $result = mysqli_query($db->conn, "SELECT VERSION();");
    echo "MySQL Version: " . mysqli_fetch_row($result)[0] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
EOF

php /tmp/test_db.php
```

### Step 3: Test Application Startup

```bash
# Check for fatal errors on main page
curl -s https://iacc.f2.co.th/ | grep -i "fatal\|error\|warning" | head -20

# Check error log
tail -20 /home/iacc-user/public_html/iacc/error_log
```

---

## Part D: Post-Upgrade Database Optimization

### Step 1: Update MySQL Configuration

Edit `/etc/my.cnf` or `/etc/mysql/my.cnf`:

```ini
[mysqld]
# MySQL 8.0 specific settings
default_authentication_plugin=mysql_native_password
# Or use caching_sha2_password for new installations

# Performance settings for PHP application
max_connections=200
max_allowed_packet=256M
query_cache_type=0  # Query cache removed in MySQL 8.0
innodb_buffer_pool_size=1G  # Adjust based on server RAM

# Character set
character_set_server=utf8mb4
collation_server=utf8mb4_unicode_ci

# Slow query log (for monitoring)
slow_query_log=1
slow_query_log_file=/var/log/mysql/slow.log
long_query_time=2
```

Restart MySQL:
```bash
systemctl restart mysql
```

### Step 2: Verify All Users Can Connect

```bash
# Test main database user
mysql -u iacc_user -p iacc_database -e "SELECT 1;" 

# Test staging database user (if different)
mysql -u iacc_user -p iacc_staging -e "SELECT 1;"

# List all users
mysql -u root -p -e "SELECT user, host FROM mysql.user;"
```

### Step 3: Run Table Optimization

```bash
# Optimize all tables in iacc database
mysqlcheck -u iacc_user -p --optimize iacc_database

# Analyze table statistics
mysqlcheck -u iacc_user -p --analyze iacc_database
```

---

## Part E: Testing on Staging

### Step 1: Deploy Code to Staging

```bash
# Pull latest code
cd /home/iacc-user/public_html/iacc-staging
git pull origin main

# Set permissions
chmod -R 755 .
chmod -R 777 upload/
chmod 777 inc/
```

### Step 2: Run Comprehensive Tests

Access staging: https://iacc-staging.f2.co.th

**Critical Tests:**
- [ ] Login works
- [ ] Database queries execute
- [ ] PDF generation creates files
- [ ] Logos display in PDFs
- [ ] File uploads work
- [ ] All modules functional

**See:** [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md)

### Step 3: Monitor for Errors

```bash
# Watch real-time errors
tail -f /home/iacc-user/public_html/iacc-staging/error_log

# Check PHP deprecation warnings
grep -i "deprecated" /home/iacc-user/public_html/iacc-staging/error_log
# Should see: NO DEPRECATED WARNINGS
```

---

## Part F: Production Deployment

### Step 1: Final Backup

```bash
# Create dated backup
BACKUP_DIR="/backup/iacc-production-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump -u iacc_user -p iacc_database | gzip > "$BACKUP_DIR/database.sql.gz"

# Backup files
tar -czf "$BACKUP_DIR/files.tar.gz" /home/iacc-user/public_html/iacc/

echo "Backup location: $BACKUP_DIR"
```

### Step 2: Deploy to Production

```bash
# Pull latest code
cd /home/iacc-user/public_html/iacc
git pull origin main

# Set correct permissions
chmod -R 755 .
chmod -R 777 upload/
chmod 777 inc/

# Clear any PHP opcode cache
systemctl restart php-fpm  # or php-cgi depending on setup

# Optional: Run database upgrade
mysql -u iacc_user -p iacc_database < /tmp/any_schema_changes.sql
```

### Step 3: Verify Production

```bash
# Check main site
curl -s https://iacc.f2.co.th/ | grep -i "title"

# Check errors
tail -20 /home/iacc-user/public_html/iacc/error_log

# Monitor logs for 5 minutes
watch -n 1 'tail -5 /home/iacc-user/public_html/iacc/error_log'
```

### Step 4: Health Check

Create this script and run:

```bash
#!/bin/bash
echo "=== iAcc Health Check Post-Upgrade ==="
echo ""
echo "1. PHP Version:"
php -v | head -1

echo ""
echo "2. MySQL Version:"
mysql --version

echo ""
echo "3. MySQL Connection Test:"
mysql -u iacc_user -p iacc_database -e "SELECT NOW();"

echo ""
echo "4. Application Test:"
php -r "include('/home/iacc-user/public_html/iacc/index.php');" 2>&1 | head -5

echo ""
echo "5. Latest Errors (last 5):"
tail -5 /home/iacc-user/public_html/iacc/error_log

echo ""
echo "=== Health Check Complete ==="
```

---

## Rollback Procedures

If upgrade causes issues:

### Rollback to Previous PHP

**Via WHM:**
```
WHM > Software > EasyApache
- Select previous PHP version
- Click "Start 3 of 3"
- Wait for rebuild
```

**Via Command Line:**
```bash
/scripts/php_installer --list
/scripts/php_installer --install 7.4  # Or previous version
```

### Rollback to Previous MySQL

```bash
# Stop MySQL
systemctl stop mysql

# Restore from backup
cd /backup/iacc-pre-upgrade-$(date +%Y%m%d)
zcat iacc_database.sql.gz | mysql -u root -p

# Restart MySQL
systemctl start mysql
```

### Rollback Application Files

```bash
cd /backup/iacc-pre-upgrade-$(date +%Y%m%d)
tar -xzf iacc_files.tar.gz -C /
systemctl restart apache2  # or nginx
```

---

## Monitoring After Upgrade

### Daily Checks for First Week

```bash
# Check for errors
grep -i "error\|fatal" /home/iacc-user/public_html/iacc/error_log | wc -l
# Should be: 0 or very few (warnings are OK)

# Check MySQL
mysql -u root -p -e "SHOW PROCESSLIST;" | grep -i Sleep

# Check disk space
df -h /home/iacc-user
# Should have > 20% free space

# Check MySQL size
mysql -u root -p -e "SELECT table_schema, ROUND(SUM(data_length+index_length)/1024/1024,2) AS MB FROM information_schema.tables GROUP BY table_schema ORDER BY MB DESC;"
```

### Weekly Checks

```bash
# Check for slow queries
grep -c "Query_time" /var/log/mysql/slow.log
# Should be < 100 per week

# Check backup status
ls -lh /backup/iacc-* | tail -5

# Database integrity check
mysqlcheck -u root -p --all-databases
```

---

## Version Compatibility Matrix

After upgrade, your stack will be:

| Component | Previous | New | Status |
|-----------|----------|-----|--------|
| PHP | 7.4 | 8.3 | ✅ Compatible |
| MySQL | 5.7 | 8.0 | ✅ Compatible |
| Apache | 2.4 | 2.4 | ✅ No change |
| Nginx | 1.x | 1.x | ✅ No change (if used) |
| mPDF | 5.x | 5.x (modernized) | ✅ Fixed |
| mysqli | ✓ | ✓ | ✅ Primary driver |
| MySQL Native | ✓ | ✓ | ✅ Auth method |

**Application Compatibility:** ✅ GREEN (All code already modernized for PHP 8.3)

---

## Support Contacts

If issues occur:

1. **cPanel Support:** https://support.cpanel.net
2. **MySQL Documentation:** https://dev.mysql.com/doc/refman/8.0/en/
3. **PHP 8.3 Migration:** https://www.php.net/manual/en/migration83.php
4. **Deployment Logs:** `/var/log/messages`

---

## Success Criteria

After complete upgrade:

- ✅ PHP version shows 8.3.x
- ✅ MySQL version shows 8.0.x
- ✅ Zero fatal PHP errors
- ✅ All databases intact
- ✅ Application fully functional
- ✅ PDF generation works
- ✅ Logos display in PDFs
- ✅ File uploads work
- ✅ Users can login
- ✅ All modules respond normally
- ✅ No performance degradation

---

**Estimated Time:** 1-2 hours total
**Risk Level:** LOW (all application code already upgraded)
**Recommended Maintenance Window:** Off-peak hours (2-4 AM)
