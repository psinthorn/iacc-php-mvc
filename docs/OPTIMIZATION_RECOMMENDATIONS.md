# iAcc System Optimization Recommendations

**Date:** January 2, 2026  
**Stack:** PHP 7.4 + MySQL 5.7 + Nginx + Docker  
**Priority:** Safe improvements that won't break the system

---

## 📊 Current System Analysis

### Strengths ✅
1. **Docker-based deployment** - Consistent environment
2. **Security helper functions** - CSRF, XSS prevention available in `inc/security.php`
3. **MySQL compatibility layer** - Legacy code works with mysqli
4. **Error logging** - Custom error handler in place
5. **UTF-8 support** - Proper charset configuration
6. **.htaccess security** - Good security headers configured

### Areas for Improvement 🔧

---

## 🔒 PRIORITY 1: SQL Injection Prevention (CRITICAL)

### Current Issue
Many files use raw `$_REQUEST`, `$_GET`, `$_POST` directly in SQL queries:

```php
// VULNERABLE - Direct user input in SQL
$query = mysql_query("SELECT * FROM type WHERE id='".$_REQUEST['id']."'");
```

### Safe Fix (Non-Breaking)
Create a helper function and use it gradually:

**Add to `inc/security.php`:**
```php
/**
 * Safely escape value for SQL queries
 * @param mixed $value The value to escape
 * @return string Escaped value safe for SQL
 */
function sql_escape($value) {
    global $__MYSQL_COMPAT_CONNECTION;
    if ($value === null) return 'NULL';
    if ($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)) {
        return $__MYSQL_COMPAT_CONNECTION->conn->real_escape_string($value);
    }
    return addslashes($value);
}

/**
 * Get integer value safely
 */
function sql_int($value) {
    return intval($value);
}
```

**Then gradually update files:**
```php
// SAFE - Using escape function
$id = sql_int($_REQUEST['id']);
$query = mysql_query("SELECT * FROM type WHERE id='" . $id . "'");
```

### Files to Update (High Priority)
- `core-function.php` - Most database operations
- `model.php` - User input handling
- `po-list.php`, `pr-list.php` - List queries
- All `*-make.php`, `*-edit.php` files

---

## 🔐 PRIORITY 2: Login Security Improvements

### Current Issue in `authorize.php`
```php
// Uses MD5 (weak hashing)
$query = mysqli_query($db->conn, "SELECT ... WHERE usr_pass='" . MD5($_POST['m_pass']) . "'");
```

### Safe Fix (Non-Breaking)
1. **Add CSRF protection to login form**

**Update `login.php`** - Add inside `<form>`:
```php
<?php 
session_start();
require_once("inc/security.php");
?>
<!-- Add after <fieldset> -->
<?= csrf_field() ?>
```

2. **Update `authorize.php`** - Add CSRF verification:
```php
// Add after session_start()
require_once("inc/security.php");

// Before processing login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        exit("<script>alert('Invalid request');history.back();</script>");
    }
}
```

3. **Future: Migrate to password_hash()** (requires database update)
```php
// When ready to migrate:
// 1. Add new column: usr_pass_new VARCHAR(255)
// 2. On successful login with MD5, also store password_hash() version
// 3. Gradually migrate all users
```

---

## 📁 PRIORITY 3: Include Path Security

### Current Issue
Some files might be accessed directly:
```php
// inc/sys.configs.php contains credentials
$config["password"] = "root";
```

### Already Protected ✅
Your `.htaccess` already blocks direct access to sensitive files.

### Additional Protection
**Create `inc/.htaccess`** (if not exists):
```apache
# Block all direct access to inc/ files
Order Deny,Allow
Deny from all
```

---

## ⚡ PRIORITY 4: Performance Optimizations

### 4.1 Database Connection Pooling
**Current:** New connection per request  
**Optimization:** Already singleton pattern, no change needed

### 4.2 Add Query Caching for Static Data

**Create `inc/cache.php`:**
```php
<?php
/**
 * Simple file-based cache for static data
 */
class SimpleCache {
    private static $cache = [];
    
    public static function get($key) {
        return self::$cache[$key] ?? null;
    }
    
    public static function set($key, $value, $ttl = 300) {
        self::$cache[$key] = $value;
    }
    
    public static function remember($key, $callback, $ttl = 300) {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $callback();
        }
        return self::$cache[$key];
    }
}
```

**Usage for dropdown data:**
```php
// Cache category list for the request
$categories = SimpleCache::remember('categories', function() {
    $result = mysql_query("SELECT id, cat_name FROM category ORDER BY cat_name");
    $data = [];
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
});
```

### 4.3 Optimize JavaScript Loading

**Current in `login.php`:**
```html
<script src="js/jquery-1.10.2.js"></script>  <!-- 268KB -->
```

**Optimization:** Use minified version
```html
<script src="js/jquery-1.10.2.min.js"></script>  <!-- 93KB -->
```

**Or download from CDN with fallback:**
```html
<script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
<script>window.jQuery || document.write('<script src="js/jquery-1.10.2.min.js"><\/script>')</script>
```

---

## 🛡️ PRIORITY 5: Session Security

### Current Issue
Sessions use default PHP settings.

### Safe Fix
**Add to `inc/sys.configs.php`:**
```php
// Session security settings (add before any session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
```

---

## 📝 PRIORITY 6: Error Handling Improvements

### Current State
Already have `inc/error-handler.php` - Good!

### Enhancement
**Add to `inc/error-logger.php`:**
```php
<?php
/**
 * Enhanced error logging with rotation
 */
function log_error($message, $level = 'ERROR') {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    error_log($entry, 3, $log_file);
}

function log_query_error($query, $error) {
    log_error("Query Error: $error | Query: $query", 'SQL');
}
```

---

## 🗂️ PRIORITY 7: Code Organization (Low Risk)

### Current Structure
71 PHP files in root directory - manageable but could be organized.

### Suggested Structure (Future)
```
/                       ← Keep current structure
├── pages/              ← Move page files (optional)
│   ├── po-list.php
│   └── ...
├── inc/                ← Keep core includes
├── js/                 ← Keep assets
└── css/
```

### Non-Breaking Approach
Keep current structure but add `pages/` directory for new features.

---

## 📋 IMPLEMENTATION CHECKLIST

### Phase 1: Immediate (No Code Changes)
- [ ] Verify `inc/.htaccess` exists and blocks access
- [ ] Review error logs in `/logs/` directory
- [ ] Check MySQL slow query log

### Phase 2: Quick Wins (1-2 hours)
- [ ] Add `sql_escape()` and `sql_int()` to `inc/security.php`
- [ ] Add session security settings to `inc/sys.configs.php`
- [ ] Add CSRF to login form

### Phase 3: Gradual Updates (Ongoing)
- [ ] Update `core-function.php` to use `sql_escape()`
- [ ] Update high-traffic pages to use escaped queries
- [ ] Add query error logging

### Phase 4: Future Considerations
- [ ] Migrate passwords from MD5 to password_hash()
- [ ] Consider upgrading to PHP 8.x
- [ ] Add automated testing

---

## ⚠️ DO NOT CHANGE

These items work correctly and should not be modified:
1. **mysql_* compatibility layer** - Working, provides backward compatibility
2. **Docker configuration** - Already optimized
3. **Database connection class** - Working with UTF-8
4. **Routing in index.php** - Clean and functional
5. **XML language files** - Working localization system

---

## 🔧 Quick Command Reference

```bash
# Check for SQL injection patterns
grep -r "\$_REQUEST\[" *.php | grep -v "sql_escape\|sql_int"

# Check error logs
docker exec iacc_php cat /var/www/html/logs/app-$(date +%Y-%m-%d).log

# Test database connection
docker exec iacc_mysql mysql -u root -proot -e "SELECT 1" iacc
```

---

**Summary:** Focus on SQL injection prevention first using the helper functions. This is the highest-impact security improvement that can be done gradually without breaking existing functionality.
