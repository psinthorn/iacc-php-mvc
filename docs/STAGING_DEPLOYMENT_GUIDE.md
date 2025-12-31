# iAcc Staging Environment Configuration Guide

## Overview
This document describes the staging environment setup for testing before production deployment.

## Staging Domain
- **Production:** https://iacc.f2.co.th
- **Staging:** https://iacc-staging.f2.co.th (to be created)
- **Local Docker:** localhost:8080

## Step 1: Backup Current Production (CRITICAL)

Execute before ANY changes:
```bash
cd /home/iacc-user/backups
./backup.sh
```

Verify backups created:
```bash
ls -lh /home/iacc-user/backups/
```

## Step 2: Create Staging Subdomain on cPanel

### 2.1 Via cPanel UI:
1. Login to cPanel
2. Navigate to Addon Domains
3. Create new domain: `iacc-staging.f2.co.th`
4. Directory: `/home/iacc-user/public_html/iacc-staging`
5. Enable SSL (Let's Encrypt)

### 2.2 Via Terminal (SSH):
```bash
# Create staging directory
mkdir -p /home/iacc-user/public_html/iacc-staging

# Copy production files to staging
cp -r /home/iacc-user/public_html/iacc/* /home/iacc-user/public_html/iacc-staging/

# Set permissions
chmod -R 755 /home/iacc-user/public_html/iacc-staging
chmod -R 777 /home/iacc-user/public_html/iacc-staging/upload
chmod 777 /home/iacc-user/public_html/iacc-staging/inc
```

## Step 3: Create Staging Database

### 3.1 Clone Production Database:
```bash
# Backup production database
mysqldump -u iacc_user -p iacc_database > /tmp/iacc_production.sql

# Create new staging database
mysql -u iacc_user -p -e "CREATE DATABASE iacc_staging;"

# Import data
mysql -u iacc_user -p iacc_staging < /tmp/iacc_production.sql

# Cleanup
rm /tmp/iacc_production.sql
```

### 3.2 Verify Staging Database:
```bash
mysql -u iacc_user -p -e "SHOW DATABASES LIKE 'iacc%';"
mysql -u iacc_user -p iacc_staging -e "SHOW TABLES LIMIT 5;"
```

## Step 4: Configure Staging Application

### 4.1 Update Database Connection (/iacc-staging/inc/sys.configs.php):
```php
<?php
// Staging Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'iacc_user');
define('DB_PASS', 'your_password_here');
define('DB_NAME', 'iacc_staging');  // Note: _staging suffix

// Environment Detection
define('ENVIRONMENT', 'staging');

// Logging (more verbose in staging)
define('DEBUG_MODE', true);
define('LOG_QUERIES', true);
?>
```

### 4.2 Update Configuration File (/iacc-staging/index.php):
Add at beginning to detect staging:
```php
<?php
if (!defined('ENVIRONMENT')) {
    // Detect staging vs production
    if (strpos($_SERVER['HTTP_HOST'], 'staging') !== false) {
        define('ENVIRONMENT', 'staging');
    } else {
        define('ENVIRONMENT', 'production');
    }
}

// Rest of index.php code...
?>
```

## Step 5: Upgrade PHP and MySQL

### 5.1 PHP Upgrade to 8.3:

**On cPanel (via EasyApache):**
1. Login to WHM
2. Go to Home > Software > EasyApache 4
3. Select PHP 8.3
4. Build and deploy
5. Verify: `php -v` (should show 8.3.x)

**Or via SSH (if available):**
```bash
# Check current PHP version
php -v

# If using alternate PHP versions via cPanel:
# List available versions
ls /opt/php* 2>/dev/null | grep -E "bin/php$"
```

### 5.2 MySQL Upgrade to 8.0:

**On cPanel (via WHM):**
1. Login to WHM
2. Go to Home > Software > MySQL Upgrade
3. Select MySQL 8.0
4. Verify compatibility: `mysql --version`

**Critical: Back up before upgrading:**
```bash
mysqldump --all-databases > /backup/full_backup_before_upgrade.sql
```

### 5.3 Verify Upgrade Success:

```bash
# Check PHP version
php -v
# Should output: PHP 8.3.x

# Check MySQL version
mysql --version
# Should output: mysql Ver 8.0.x

# Test PHP extensions
php -m | grep -E "mysqli|pdo"
# Should show both mysqli and pdo_mysql
```

## Step 6: Test Staging Environment

### 6.1 Access Staging:
- Open browser: https://iacc-staging.f2.co.th
- Verify SSL certificate is valid
- Check for any PHP errors in logs

### 6.2 Comprehensive Testing Checklist:

**Database Operations:**
- [ ] Login page works (database connection verified)
- [ ] Create test record in each module
- [ ] Edit existing record
- [ ] Delete test record
- [ ] Verify database transactions complete

**PDF Generation (Critical):**
- [ ] Generate Tax Invoice PDF
- [ ] Generate Invoice PDF
- [ ] Generate Delivery Note PDF
- [ ] Generate Voucher PDF
- [ ] Verify all logos display correctly in PDFs
- [ ] Check PDF has no error messages

**File Operations:**
- [ ] Upload company logo
- [ ] Upload document file
- [ ] Download/view uploaded files
- [ ] Test file permissions

**All Modules:**
- [ ] Band/Category Management
- [ ] Company Management
- [ ] Credit Management
- [ ] Delivery Management
- [ ] Purchase Orders
- [ ] Invoices
- [ ] Reports

### 6.3 Performance Testing:

```bash
# Check load time
curl -w "\nTotal time: %{time_total}s\n" -o /dev/null -s https://iacc-staging.f2.co.th/

# Check PHP error logs
tail -f /home/iacc-user/public_html/iacc-staging/error_log

# Monitor database performance
mysql -u iacc_user -p iacc_staging -e "SHOW PROCESSLIST;"
```

### 6.4 Security Testing:

```bash
# Test file permissions
ls -la /home/iacc-user/public_html/iacc-staging/inc/
# Should show -rwxr-xr-x for PHP files, not -rwxrwxrwx

# Test upload directory permissions
ls -la /home/iacc-user/public_html/iacc-staging/upload/
# Should be drwxrwxr-x (777)

# Verify .git is not accessible
curl https://iacc-staging.f2.co.th/.git/config
# Should return 404 or Forbidden
```

## Step 7: Test Deployment Automation (Optional)

### 7.1 Deploy to Staging via GitHub:

```bash
# Setup webhook for staging repository
# GitHub Settings > Webhooks
# Payload URL: https://iacc-staging.f2.co.th/deploy-staging.php
# Events: Push events
```

### 7.2 Create Staging Deployment Script:

```bash
#!/bin/bash
# /public_html/iacc-staging/deploy-staging.php wrapper

cd /home/iacc-user/public_html/iacc-staging
git pull origin main
chmod -R 755 .
chmod -R 777 upload/
chmod 777 inc/
service php-fpm restart 2>/dev/null || true
```

## Step 8: Production Deployment

Once staging testing passes:

### 8.1 Final Production Backup:
```bash
/home/iacc-user/backups/backup.sh
```

### 8.2 Deploy to Production:
```bash
cd /home/iacc-user/public_html/iacc
git pull origin main
chmod -R 755 .
chmod -R 777 upload/
chmod 777 inc/
service php-fpm restart 2>/dev/null || true
```

### 8.3 Verify Production:
- [ ] https://iacc.f2.co.th loads without errors
- [ ] Test login and basic operations
- [ ] Verify latest changes are live
- [ ] Monitor error logs for issues

## Troubleshooting

### PHP Version Not Updating:
```bash
# Check which PHP version Apache/PHP-FPM is using
php -i | grep -i "php version"

# Restart web server
service apache2 restart
# Or for nginx + PHP-FPM:
service php-fpm restart
```

### Database Connection Errors:
```bash
# Test MySQL connection
mysql -u iacc_user -p iacc_staging -e "SELECT 1;"

# Check for connection in logs
grep -i "error" /var/log/mysql/error.log | tail -20
```

### Logo Not Displaying in Staging PDFs:
```bash
# Check file permissions
ls -la /home/iacc-user/public_html/iacc-staging/upload/

# Verify logos exist
find /home/iacc-user/public_html/iacc-staging/upload -name "*logo*" -o -name "*.jpg" -o -name "*.png"

# Check mPDF temp directory
ls -la /tmp/mpdf*
chmod 777 /tmp/mpdf* 2>/dev/null
```

### Git Deployment Not Working:
```bash
# Check deployment log
tail -f /home/iacc-user/public_html/iacc-staging/deploy.log

# Verify git permissions
ls -la /home/iacc-user/public_html/iacc-staging/.git/

# Test webhook manually
curl -X POST https://iacc-staging.f2.co.th/deploy.php \
  -H "Content-Type: application/json" \
  -d '{"ref":"refs/heads/main"}'
```

## Rollback Procedure

If staging or production deployment fails:

```bash
# Stop application
touch /home/iacc-user/public_html/iacc/maintenance.html

# Restore from backup
cd /home/iacc-user/backups
./restore.sh database_YYYYMMDD_HHMMSS.sql.gz

# Restore files
tar -xzf /home/iacc-user/backups/app_YYYYMMDD_HHMMSS.tar.gz -C /home/iacc-user/public_html/

# Restart services
service php-fpm restart

# Remove maintenance page
rm /home/iacc-user/public_html/iacc/maintenance.html
```

## Monitoring Post-Deployment

### Key Metrics to Monitor:
1. **PHP Error Logs:**
   ```bash
   tail -f /home/iacc-user/public_html/iacc/error_log
   ```

2. **MySQL Slow Queries:**
   ```bash
   tail -f /var/log/mysql/slow.log
   ```

3. **Web Server Access:**
   ```bash
   tail -f /var/log/apache2/access_log
   # Or nginx:
   tail -f /var/log/nginx/access.log
   ```

4. **Application Performance:**
   - Monitor PDF generation times
   - Monitor database query performance
   - Check file upload speeds

---

## Automation Checklist

- [ ] Backup script tested and working
- [ ] Staging subdomain created and accessible
- [ ] Database cloned to staging
- [ ] PHP 8.3 upgraded
- [ ] MySQL 8.0 upgraded
- [ ] All tests passed on staging
- [ ] Git webhooks configured
- [ ] Deployment scripts ready
- [ ] Rollback procedure documented and tested
- [ ] Team notified of upgrade schedule
