# PRIORITY 1: cPANEL DEPLOYMENT - QUICK ACTION CHECKLIST
**Status**: Phase Started - Ready for Execution  
**Date**: January 1, 2026  
**Responsibility**: Deployment Team

---

## Current System Status ✅
- Docker environment: **Healthy**
- Database: **Functional** (35 tables, RBAC operational)
- Application: **Running** (core functions working)
- Backup checkpoints: **Created** (2 snapshots available)

---

## PHASE 1: VALIDATION (Today - Jan 1)

### 1.1 System Verification
- [ ] Test application at http://localhost
  - [ ] Home page loads
  - [ ] Login form displays
  - [ ] User can login with test credentials
  - [ ] Dashboard renders post-login

- [ ] Check database integrity
  ```bash
  # Verify table count
  docker exec iacc_mysql mysql -uroot -proot iacc -e "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='iacc';"
  # Should show: 35 tables
  ```

- [ ] Verify RBAC data
  ```bash
  docker exec iacc_mysql mysql -uroot -proot iacc -e "
  SELECT 'Roles' as table_name, COUNT(*) as count FROM roles
  UNION ALL
  SELECT 'Permissions', COUNT(*) FROM permissions
  UNION ALL  
  SELECT 'User Roles', COUNT(*) FROM user_roles
  UNION ALL
  SELECT 'Role Permissions', COUNT(*) FROM role_permissions;
  "
  # Expected: 1, 7, 4, 7 (or similar)
  ```

### 1.2 Review Documentation
- [ ] Read [SYSTEM_TEST_REPORT_20260101.md](SYSTEM_TEST_REPORT_20260101.md)
- [ ] Read [RBAC_IMPLEMENTATION_REPORT.md](RBAC_IMPLEMENTATION_REPORT.md)
- [ ] Review [PROJECT_ROADMAP_2026.md](PROJECT_ROADMAP_2026.md) - Section "Phase 4: Deployment"

### 1.3 Create Recovery Plan
- [ ] Document backup locations
  - `BACKUP_BEFORE_IMPORT_20260101_105745.sql` ← Point A (clean state)
  - `BACKUP_WITH_RBAC_20260101_111500.sql` ← Point B (current state)
- [ ] Create rollback procedure document
- [ ] Test restore from backup (optional - don't do on production)

**Status After Phase 1**: ✅ Green Light to Proceed

---

## PHASE 2: PREPARATION (Jan 1-2)

### 2.1 Hosting Environment Preparation
- [ ] Access cPanel for f2.co.th server
- [ ] Verify PHP version available
  - [ ] Check for PHP 7.4 (current minimum)
  - [ ] Check if PHP 8.3 available (for future upgrade)
- [ ] Verify MySQL version
  - [ ] Check MySQL 5.7 available
  - [ ] Check if MySQL 8.0 available (for future upgrade)
- [ ] Check disk space available
  - Estimate: 200MB minimum (application + database)
- [ ] Verify email capability (for phpMailer)

### 2.2 Database Preparation
- [ ] Export database from Docker
  ```bash
  docker exec iacc_mysql mysqldump -uroot -proot iacc > /path/to/iacc_production_ready.sql
  ```
- [ ] Validate SQL file integrity
  ```bash
  # Check file size and format
  ls -lh iacc_production_ready.sql
  head -20 iacc_production_ready.sql | grep -E "CREATE TABLE|INSERT"
  ```
- [ ] Create import script for cPanel

### 2.3 Application Code Preparation
- [ ] Review iacc/ directory structure
- [ ] Identify config files that need cPanel customization
  - [ ] `iacc/inc/sys.configs.php` - Database credentials
  - [ ] Check for hardcoded paths that won't work on cPanel
  - [ ] Verify relative vs absolute path usage
- [ ] List files that should NOT be deployed (dev only)
  - [ ] Docker files
  - [ ] Test files
  - [ ] .env files (if any)

### 2.4 Security Checklist
- [ ] Change database credentials (if default used)
- [ ] Remove debug mode if enabled
- [ ] Verify file permissions appropriate for cPanel
  - Upload dirs writable: 755
  - Config files readable: 644
- [ ] Remove phpmyadmin or restrict access

**Status After Phase 2**: 📋 Ready for Deployment

---

## PHASE 3: DEPLOYMENT (Jan 2-3)

### 3.1 Pre-Deployment Backup
- [ ] Backup any existing cPanel data (if applicable)
- [ ] Create snapshot of current Docker system
  ```bash
  docker exec iacc_mysql mysqldump -uroot -proot iacc > BACKUP_BEFORE_CPANEL_DEPLOY_$(date +%Y%m%d).sql
  ```

### 3.2 Database Deployment
- [ ] Create new database in cPanel
  - Database name: `iacc` (or similar)
  - Database user: Create dedicated user (not root)
  - Grant: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX
- [ ] Import SQL file
  ```bash
  # Using cPanel phpMyAdmin or command line
  mysql -u[username] -p[password] [database] < iacc_production_ready.sql
  ```
- [ ] Verify import completeness
  - [ ] All 35 tables present
  - [ ] Data integrity check: SELECT COUNT(*) FROM each major table
  - [ ] RBAC tables populated correctly

### 3.3 Application Deployment  
- [ ] Upload iacc/ directory to cPanel public_html or subdirectory
- [ ] Upload other required directories
  - [ ] resources/
  - [ ] src/ (if using new framework code)
  - [ ] storage/ (if needed)
- [ ] Update sys.configs.php with cPanel credentials
  ```php
  $config["hostname"] = "[cpanel_mysql_host]";
  $config["username"] = "[cpanel_mysql_user]";
  $config["password"] = "[cpanel_mysql_password]";
  $config["dbname"]   = "[cpanel_database_name]";
  ```
- [ ] Set file permissions
  ```bash
  chmod 755 upload/ (or wherever files are written)
  chmod 644 *.php
  ```

### 3.4 Testing in Production
- [ ] Test application URL: https://f2.co.th/iacc/ (or domain)
- [ ] Test login functionality
- [ ] Test core modules (PO, Company, Reports)
- [ ] Check error logs for issues
  - [ ] PHP error log in cPanel
  - [ ] MySQL error log
- [ ] Test file uploads if applicable
- [ ] Verify email works if used in application

**Status After Phase 3**: ✅ Live in Production

---

## PHASE 4: VALIDATION (Jan 3-4)

### 4.1 Production Verification
- [ ] All core functions working
- [ ] Users can login and use system
- [ ] No database connection errors
- [ ] File uploads working (if applicable)
- [ ] Email notifications working (if applicable)

### 4.2 Performance Check
- [ ] Page load times reasonable
- [ ] Database queries not timing out
- [ ] No memory exhaustion errors

### 4.3 Documentation Update
- [ ] Update README with production URLs/locations
- [ ] Document cPanel backup procedures
- [ ] Document how to access MySQL on cPanel
- [ ] Create emergency contact procedures

**Status After Phase 4**: 🎉 Ready for Regular Use

---

## PHASE 5: HANDOFF (Jan 4-5)

### 5.1 User Training
- [ ] Brief team on new production URL
- [ ] Verify all users can login
- [ ] Provide documentation on basic operations

### 5.2 Monitoring Setup
- [ ] Check if cPanel provides monitoring/alerts
- [ ] Set up error logging if available
- [ ] Create incident response plan

### 5.3 Next Steps
- [ ] Schedule Phase 1 improvements (tech stack upgrade)
- [ ] Document lessons learned
- [ ] Plan maintenance windows

**Status After Phase 5**: ✅ System Ready for Operations + Improvement Planning

---

## ROLLBACK PROCEDURES

**If deployment fails**, rollback options:

### Option A: Restore Docker (No Production Data Loss)
```bash
# Restore from latest checkpoint
docker exec -i iacc_mysql mysql -uroot -proot iacc < BACKUP_WITH_RBAC_20260101_111500.sql
```
- **Time**: < 5 minutes
- **Data**: Full recovery to current state

### Option B: Restore Previous cPanel Deployment (If Had Existing)
```bash
# Use cPanel backup if available
```
- **Time**: Variable
- **Data**: Depends on backup age

### Option C: Emergency Contact
```
If critical issues:
- Contact f2.co.th hosting support
- Emergency rollback: Use pre-deployment snapshot
```

---

## SIGN-OFF

| Phase | Status | Date | Completed By | Notes |
|-------|--------|------|--------------|-------|
| 1. Validation | ⏳ In Progress | Jan 1 | - | System ready |
| 2. Preparation | ⏳ Pending | Jan 1-2 | - | Awaiting checklist |
| 3. Deployment | ⏳ Pending | Jan 2-3 | - | After prep complete |
| 4. Production Test | ⏳ Pending | Jan 3-4 | - | After deploy |
| 5. Handoff | ⏳ Pending | Jan 4-5 | - | After validation |

---

## QUICK REFERENCE

### Database Credentials (Docker - Current)
```
Host: mysql (Docker DNS) or localhost:3306
User: root
Pass: root
DB: iacc
```

### Database Credentials (cPanel - To Set)
```
Host: localhost (or cpanel host)
User: [CREATE NEW]
Pass: [CREATE NEW - Store securely]
DB: [CREATE NEW]
```

### Important Files
- SQL Export: `iacc_production_ready.sql` (to create)
- Config File: `iacc/inc/sys.configs.php` (needs update)
- Backup Checkpoints: `BACKUP_BEFORE_IMPORT_*` and `BACKUP_WITH_RBAC_*`

### Key Contacts
- Hosting: f2.co.th cPanel support
- Database: MySQL documentation
- Framework: Check existing code comments

---

**Last Updated**: 2026-01-01 11:20 AM  
**Next Review**: After Phase 1 Completion  
**Owner**: Deployment Team
