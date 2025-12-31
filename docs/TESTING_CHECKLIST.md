# iAcc Application - Comprehensive Testing Checklist

## Testing Environment
- **URL to Test:** https://iacc-staging.f2.co.th (or production URL)
- **Browser:** Chrome/Firefox (latest version)
- **Date:** _______________
- **Tester:** _______________
- **Version:** PHP 8.3 / MySQL 8.0

---

## Section 1: System & Infrastructure

### 1.1 Server Configuration
- [ ] PHP version is 8.3.x
  ```
  Command: php -v
  Expected: PHP 8.3.x or later
  ```
- [ ] MySQL version is 8.0.x or later
  ```
  Command: mysql --version
  Expected: mysql Ver 8.0.x or later
  ```
- [ ] PHP extensions installed: mysqli, pdo_mysql, gd, zip, curl
  ```
  Command: php -m | grep -E "mysqli|pdo|gd"
  Expected: All should be present
  ```
- [ ] SSL/TLS certificate valid
  ```
  Access https://iacc-staging.f2.co.th and verify no certificate warnings
  ```

### 1.2 File Permissions
- [ ] Application directory readable: `ls -la /public_html/iacc/` shows `drwxr-xr-x`
- [ ] Upload directory writable: `ls -la /public_html/iacc/upload/` shows `drwxrwxr-x`
- [ ] Config files readable: `ls -la /public_html/iacc/inc/` shows `-rw-r--r--`
- [ ] No .git directory publicly accessible: `curl https://domain/.git/config` returns 404

### 1.3 Database Connection
- [ ] MySQL is running: `mysql -u user -p -e "SELECT 1;"`
- [ ] Test database exists: `mysql -u user -p -e "SHOW DATABASES LIKE 'iacc%';"`
- [ ] All required tables present
  ```
  Expected tables: companies, deliveries, invoices, products, users, etc.
  ```

---

## Section 2: Authentication & Session Management

### 2.1 Login Functionality
- [ ] Login page loads without errors: https://domain/login.php
- [ ] Valid credentials allow login
  - Username: test_user
  - Password: test_password
- [ ] Invalid credentials show error message
- [ ] Session created after login (check cookies)
- [ ] Can access protected pages after login
- [ ] Session expires after inactivity (timeout)
- [ ] Logout clears session and redirects to login

### 2.2 User Roles & Permissions
- [ ] Admin user can access all modules
- [ ] Regular user access restricted to assigned modules
- [ ] Users cannot access other user's private data
- [ ] Permission-based menu items show/hide correctly

---

## Section 3: Core Module Testing

### 3.1 Company Management
- [ ] Company List page loads
  - [ ] All companies display correctly
  - [ ] Pagination works (if applicable)
  - [ ] Search filters work
  - [ ] Sort by name/id/date works
- [ ] Create Company
  - [ ] Form displays all fields
  - [ ] Can enter company name, address, phone
  - [ ] File upload for logo works
  - [ ] Submit creates new record in database
  - [ ] Success message displays
- [ ] Edit Company
  - [ ] Existing data populates in form
  - [ ] Can modify all fields
  - [ ] Can update logo
  - [ ] Changes save to database
- [ ] Delete Company
  - [ ] Confirmation dialog appears
  - [ ] Record deleted from database
  - [ ] List updates to remove deleted item

### 3.2 Product/Category Management
- [ ] Category List displays all categories
- [ ] Create Category works correctly
- [ ] Edit Category updates database
- [ ] Delete Category removes record
- [ ] Product List shows all products
- [ ] Products linked to correct categories
- [ ] Create Product works with category selection
- [ ] Product search filters work

### 3.3 User Management
- [ ] User List displays all users
- [ ] Create User allows setting username/password/role
- [ ] Edit User can modify all fields
- [ ] Delete User removes user account
- [ ] User roles (admin/user) assignments work

### 3.4 Delivery Management
- [ ] Delivery List displays all deliveries
- [ ] Create Delivery with date, items, status
- [ ] Edit Delivery updates record
- [ ] Status changes (pending/completed) save correctly
- [ ] Delivery dates display in correct format

### 3.5 Invoice Management
- [ ] Invoice List displays all invoices
- [ ] Create Invoice with line items and calculations
- [ ] Edit Invoice updates amounts and items
- [ ] Delete Invoice removes record
- [ ] Invoice numbers auto-increment correctly
- [ ] Tax calculations are correct (if applicable)

### 3.6 Purchase Orders
- [ ] PO List displays with correct status
- [ ] Create PO with items and quantities
- [ ] Edit PO updates all fields
- [ ] PO totals calculate correctly
- [ ] Approval workflow works (if applicable)

---

## Section 4: PDF Generation (CRITICAL)

### 4.1 Tax Invoice PDF
- [ ] Generate PDF button works
- [ ] PDF downloads without errors
- [ ] PDF opens in PDF reader without corruption
- [ ] **Logo displays in top-left corner** ✓ (CRITICAL)
- [ ] Company details show correctly
- [ ] Invoice items and line totals display
- [ ] Grand total calculated correctly
- [ ] No error messages in PDF output
- [ ] Date/time stamp present

### 4.2 Invoice PDF (Mobile)
- [ ] Generate PDF works
- [ ] PDF renders correctly
- [ ] **Logo displays** ✓
- [ ] All data present and readable
- [ ] Format suitable for mobile/print

### 4.3 Delivery Note PDF
- [ ] Generate PDF works
- [ ] **Logo displays** ✓
- [ ] Delivery date and items shown
- [ ] Signature lines present (if applicable)
- [ ] PDF is printable

### 4.4 Receipt/Voucher PDF
- [ ] Generate PDF works
- [ ] **Logo displays** ✓
- [ ] Payment details shown
- [ ] Amount and date correct
- [ ] PDF is clean and printable

### 4.5 Reports PDF
- [ ] Generate monthly report PDF
- [ ] Generate yearly report PDF
- [ ] **Logos display** ✓
- [ ] Calculations are correct
- [ ] Summary tables display properly

---

## Section 5: File Operations

### 5.1 File Uploads
- [ ] Upload company logo (JPG)
  - [ ] File size validation works
  - [ ] File type validation works (image only)
  - [ ] File saved to correct directory: `/public_html/iacc/upload/`
  - [ ] File is readable from web
- [ ] Upload document file (PDF/DOC)
  - [ ] File accepted and saved
  - [ ] File accessible for download
- [ ] Upload profile picture
  - [ ] Image displayed in correct location
  - [ ] Thumbnail generated (if applicable)

### 5.2 File Downloads
- [ ] Download uploaded files
  - [ ] File downloads with correct name
  - [ ] File content is intact
  - [ ] Download headers correct (Content-Type)
- [ ] Download PDF reports
  - [ ] PDF downloads successfully
  - [ ] File size is reasonable
- [ ] Download CSV exports
  - [ ] CSV file opens in Excel
  - [ ] Data is correctly formatted

### 5.3 File Permissions
- [ ] Cannot directly access files by typing URL
- [ ] Symlinks don't expose system files
- [ ] Directory listing disabled (403 Forbidden)

---

## Section 6: Database Operations

### 6.1 Data Integrity
- [ ] Create operation inserts correct data
  ```sql
  SELECT * FROM companies WHERE id = [new_id];
  Verify all fields match form submission
  ```
- [ ] Update operation modifies all fields
  ```sql
  SELECT * FROM companies WHERE id = [updated_id];
  Verify changes are reflected
  ```
- [ ] Delete operation removes record completely
  ```sql
  SELECT COUNT(*) FROM companies;
  Verify count decreased by 1
  ```

### 6.2 Data Types & Validation
- [ ] Numeric fields reject text input
- [ ] Date fields accept correct format (YYYY-MM-DD)
- [ ] Email fields require @ symbol
- [ ] Phone numbers accept numeric input
- [ ] Required fields cannot be left empty

### 6.3 Data Relationships
- [ ] Foreign key relationships maintained
- [ ] Cannot delete parent record with active children
- [ ] Cascade delete works correctly (if enabled)
- [ ] Junction tables (many-to-many) update correctly

### 6.4 Search & Filtering
- [ ] Search by company name returns correct results
- [ ] Filter by date range works
- [ ] Filter by status/category works
- [ ] Multiple filters can be applied together
- [ ] Clear filters button resets all

---

## Section 7: Performance & Load Testing

### 7.1 Page Load Times
- [ ] Home page: < 2 seconds
- [ ] List pages: < 3 seconds
- [ ] PDF generation: < 10 seconds
- [ ] Database queries: < 1 second

### 7.2 Concurrent Users
- [ ] 5 simultaneous users: No database locks
- [ ] 10 simultaneous users: No timeouts
- [ ] 20 simultaneous PDF generations: System stable

### 7.3 Memory & CPU
- [ ] PHP memory usage < 256MB per request
- [ ] CPU usage < 80% during normal operations
- [ ] No memory leaks after 1 hour of operation

### 7.4 Database Performance
- [ ] Large list queries (1000+ records) load in < 5 seconds
- [ ] No slow queries in MySQL log
- [ ] Query cache functioning (if enabled)

---

## Section 8: Security Testing

### 8.1 Input Validation
- [ ] SQL Injection attempted: `' OR '1'='1` - BLOCKED ✓
- [ ] XSS attempted: `<script>alert('xss')</script>` - ESCAPED ✓
- [ ] File upload: `.php` file upload - BLOCKED ✓
- [ ] Directory traversal: `../../../etc/passwd` - BLOCKED ✓

### 8.2 Authentication Security
- [ ] Passwords hashed in database (not plain text)
- [ ] Passwords minimum 8 characters required
- [ ] Session tokens are random and unique
- [ ] Brute force attempts throttled (after 5 attempts)

### 8.3 Data Privacy
- [ ] Cannot access other user's invoices via URL manipulation
- [ ] Cannot view deleted records via direct query
- [ ] Sensitive data (passwords) not logged
- [ ] File permissions prevent unauthorized access

### 8.4 HTTPS/SSL
- [ ] Entire site uses HTTPS (no mixed content)
- [ ] Certificate is valid and not expired
- [ ] No SSL errors in browser console
- [ ] Secure cookies configured (HTTPOnly, Secure flags)

---

## Section 9: Error Handling

### 9.1 User-Visible Errors
- [ ] Form validation errors display clearly
- [ ] Database errors show friendly message (not raw SQL)
- [ ] File upload errors are descriptive
- [ ] 404 errors show appropriate page

### 9.2 PHP Error Logs
- [ ] No PHP Fatal errors in error_log
- [ ] No PHP Deprecated warnings (PHP 8.3 compatible)
- [ ] No PHP Notice/Warning level issues
- [ ] Error logs not exposed to public

### 9.3 Database Error Handling
- [ ] Connection failures handled gracefully
- [ ] Query errors logged but not displayed
- [ ] Deadlocks resolved with retry logic
- [ ] Out of disk space handled appropriately

### 9.4 Graceful Degradation
- [ ] If PDF library fails, error is logged
- [ ] If email fails, operation continues
- [ ] If file upload fails, user is notified

---

## Section 10: PHP 8.3 Compatibility

### 10.1 Deprecated Features
- [ ] No `each()` function usage
  ```bash
  grep -r "each(" /public_html/iacc/
  Expected: No results
  ```
- [ ] No deprecated array syntax `{$var}`
  ```bash
  grep -r '{\$' /public_html/iacc/
  Expected: No results
  ```
- [ ] No mysql_* functions used
  ```bash
  grep -r "mysql_" /public_html/iacc/
  Expected: No results (except wrapper functions)
  ```
- [ ] No deprecated string offset syntax
  ```
  Old: $string{0}
  New: $string[0]
  ```

### 10.2 Type System Enhancements (If Applicable)
- [ ] Type hints properly declared
- [ ] Return types specified where applicable
- [ ] No type mismatch errors in PHP error log

---

## Section 11: MySQL 8.0 Compatibility

### 11.1 Connection & Queries
- [ ] mysqli connection works without errors
- [ ] Queries execute without deprecated warnings
- [ ] Character encoding set to UTF-8 (utf8mb4)
- [ ] Timezone settings work correctly

### 11.2 Data Type Compatibility
- [ ] No issues with UNSIGNED integers
- [ ] DATETIME fields work correctly
- [ ] TEXT/LONGTEXT fields display properly
- [ ] JSON columns handled correctly (if used)

---

## Section 12: Deployment Verification

### 12.1 Code Deployment
- [ ] Latest code from main branch deployed
- [ ] .env file has correct database credentials
- [ ] Configuration files have production values
- [ ] No debug code in production (DEBUG_MODE = false)

### 12.2 File System
- [ ] All necessary files deployed
- [ ] Permissions set correctly (755 for folders, 644 for files)
- [ ] Upload directory exists and is writable
- [ ] Temporary directories exist (for PDF generation)

### 12.3 Database Schema
- [ ] All tables created with correct structure
- [ ] Indexes created for performance
- [ ] Foreign keys configured
- [ ] Initial data populated (if applicable)

---

## Section 13: Backwards Compatibility

### 13.1 Legacy Code Support
- [ ] Old mysql_query() wrapper functions work
- [ ] Existing database connections work
- [ ] Old file paths still accessible
- [ ] Deprecated functions mapped to new ones

### 13.2 Data Migration
- [ ] Old data accessible from new version
- [ ] No data loss during upgrade
- [ ] Data types converted correctly
- [ ] Custom data preserved

---

## Test Results Summary

### Critical Items (Must Pass)
- [ ] PDF generation works
- [ ] **Logos display in all PDFs**
- [ ] Database operations succeed
- [ ] User login works
- [ ] No PHP 8.3 deprecation warnings
- [ ] No MySQL compatibility issues

### High Priority (Should Pass)
- [ ] All module functionality works
- [ ] File uploads/downloads work
- [ ] Security validations pass
- [ ] Reports generate correctly

### Medium Priority (Nice to Have)
- [ ] Performance meets benchmarks
- [ ] Load testing successful
- [ ] Old API still works

---

## Sign-Off

**Tested By:** _____________________ **Date:** _____________

**Overall Result:** 
- [ ] ✓ PASS - All tests passed, ready for production
- [ ] ⚠ CONDITIONAL PASS - Minor issues, documented below
- [ ] ✗ FAIL - Critical issues found, do not deploy

**Issues Found:**
```
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________
```

**Recommendations:**
```
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
```

**Approved for Production:** _________________ **Date:** _________

---

**Notes:**
- Save screenshots of critical test results (logos in PDFs, etc.)
- Document any deviations from standard behavior
- Keep this checklist with deployment records
