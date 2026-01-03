# SQL Query & Database Optimization Analysis

## Overview

After successfully migrating the database to InnoDB and standardizing character sets, this document analyzes what SQL query improvements are needed to fully leverage the new database structure.

---

## Current State Analysis

### ✅ Already Good (Security Functions Exist)

The codebase has security functions in `/inc/security.php`:
- `sql_int()` - Sanitizes integer inputs
- `sql_escape()` - Escapes strings for SQL
- `sql_string()` - Sanitizes string inputs
- `e()` - XSS prevention for output
- `csrf_token()` / `csrf_verify()` - CSRF protection

### ⚠️ Issues Found

| Issue | Severity | Count | Files Affected |
|-------|----------|-------|----------------|
| Direct `$_GET/$_POST` in SQL queries | 🔴 Critical | 15+ | modal_molist.php, makeoptionindex.php, inv-m.php, exp-m.php |
| Missing prepared statements | 🟠 High | 50+ | Most PHP files |
| No transaction usage | 🟡 Medium | 10+ | Core transaction files |
| Missing foreign key constraints | 🟡 Medium | 20+ | All related tables |
| Inefficient N+1 queries | 🟡 Medium | 5+ | List pages |

---

## Priority 1: Fix SQL Injection Vulnerabilities

### Files with Direct SQL Injection Risk

1. **modal_molist.php** (Line 9)
```php
// VULNERABLE:
$query=mysqli_query($db->conn, "select ... where model.id='".$_REQUEST[p_id]."'");

// SHOULD BE:
$p_id = sql_int($_REQUEST['p_id']);
$query=mysqli_query($db->conn, "select ... where model.id='".$p_id."'");
```

2. **makeoptionindex.php** (Lines 10, 22, 36)
```php
// VULNERABLE:
"where type_id='".$_GET['value']."'"

// SHOULD BE:
$type_id = sql_int($_GET['value']);
"where type_id='".$type_id."'"
```

3. **inv-m.php** (Lines 11, 15, 75, 96)
```php
// VULNERABLE:
"where po.id='".$_POST[id]."'"

// SHOULD BE:
$po_id = sql_int($_POST['id']);
"where po.id='".$po_id."'"
```

4. **exp-m.php** (Lines 10, 67, 88)
```php
// Same pattern as inv-m.php
```

---

## Priority 2: Implement Prepared Statements

### Current Pattern (Vulnerable)
```php
$id = sql_int($_REQUEST['id']);
$query = mysqli_query($db->conn, "SELECT * FROM po WHERE id='".$id."'");
```

### Recommended Pattern (Secure)
```php
$stmt = mysqli_prepare($db->conn, "SELECT * FROM po WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $_REQUEST['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

### Helper Class to Create
```php
// inc/class.database.php
class Database {
    private $conn;
    
    public function prepare($sql) {
        return mysqli_prepare($this->conn, $sql);
    }
    
    public function query($sql, $params = [], $types = '') {
        if (empty($params)) {
            return mysqli_query($this->conn, $sql);
        }
        
        $stmt = mysqli_prepare($this->conn, $sql);
        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    }
    
    public function fetchOne($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        return mysqli_fetch_assoc($result);
    }
    
    public function fetchAll($sql, $params = [], $types = '') {
        $result = $this->query($sql, $params, $types);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
```

---

## Priority 3: Add Transaction Support

Now that all tables use InnoDB, we can use transactions for data integrity.

### Files That Need Transactions

| File | Operations | Why Needed |
|------|------------|------------|
| `core-function.php` | Insert/Update multiple tables | Atomic operations |
| `po-make.php` | Create PO + Products | Data consistency |
| `payment.php` | Record payment + Update status | Financial integrity |
| `deliv-make.php` | Create delivery + Update stock | Inventory accuracy |

### Example Implementation
```php
// Before (no transaction)
mysqli_query($db->conn, "INSERT INTO po ...");
mysqli_query($db->conn, "INSERT INTO product ...");
mysqli_query($db->conn, "UPDATE pr SET status='2' ...");

// After (with transaction)
mysqli_begin_transaction($db->conn);
try {
    mysqli_query($db->conn, "INSERT INTO po ...");
    mysqli_query($db->conn, "INSERT INTO product ...");
    mysqli_query($db->conn, "UPDATE pr SET status='2' ...");
    mysqli_commit($db->conn);
} catch (Exception $e) {
    mysqli_rollback($db->conn);
    throw $e;
}
```

---

## Priority 4: Add Foreign Key Constraints

Now that all tables are InnoDB, we can add foreign keys for referential integrity.

### Recommended Foreign Keys

```sql
-- Product to PO
ALTER TABLE product 
ADD CONSTRAINT fk_product_po 
FOREIGN KEY (po_id) REFERENCES po(id) ON DELETE CASCADE;

-- Product to Type
ALTER TABLE product 
ADD CONSTRAINT fk_product_type 
FOREIGN KEY (type) REFERENCES type(id) ON DELETE RESTRICT;

-- Product to Brand
ALTER TABLE product 
ADD CONSTRAINT fk_product_brand 
FOREIGN KEY (ban_id) REFERENCES brand(id) ON DELETE RESTRICT;

-- Pay to PO
ALTER TABLE pay 
ADD CONSTRAINT fk_pay_po 
FOREIGN KEY (po_id) REFERENCES po(id) ON DELETE CASCADE;

-- Deliver to PO
ALTER TABLE deliver 
ADD CONSTRAINT fk_deliver_po 
FOREIGN KEY (po_id) REFERENCES po(id) ON DELETE CASCADE;

-- PR relationships
ALTER TABLE pr 
ADD CONSTRAINT fk_pr_customer 
FOREIGN KEY (cus_id) REFERENCES company(id) ON DELETE RESTRICT,
ADD CONSTRAINT fk_pr_vendor 
FOREIGN KEY (ven_id) REFERENCES company(id) ON DELETE RESTRICT;

-- Model to Brand
ALTER TABLE model 
ADD CONSTRAINT fk_model_brand 
FOREIGN KEY (brand_id) REFERENCES brand(id) ON DELETE CASCADE;

-- Model to Type
ALTER TABLE model 
ADD CONSTRAINT fk_model_type 
FOREIGN KEY (type_id) REFERENCES type(id) ON DELETE CASCADE;
```

---

## Priority 5: Optimize Slow Queries

### Current N+1 Query Problem

```php
// SLOW: N+1 queries
$pos = mysqli_query($db->conn, "SELECT * FROM po");
while ($po = mysqli_fetch_array($pos)) {
    $customer = mysqli_query($db->conn, "SELECT * FROM company WHERE id='".$po['cus_id']."'");
    $products = mysqli_query($db->conn, "SELECT * FROM product WHERE po_id='".$po['id']."'");
}
```

### Optimized with JOIN
```php
// FAST: Single query with JOIN
$query = "SELECT po.*, 
          c1.name_en as customer_name, 
          c2.name_en as vendor_name,
          COUNT(p.pro_id) as product_count,
          SUM(p.price * p.quantity) as total_value
          FROM po 
          LEFT JOIN company c1 ON po.cus_id = c1.id
          LEFT JOIN company c2 ON po.ven_id = c2.id
          LEFT JOIN product p ON po.id = p.po_id
          WHERE po.deleted_at IS NULL
          GROUP BY po.id
          ORDER BY po.date DESC";
```

---

## Implementation Phases

### Phase 1: Security Fixes (1-2 days) 🔴 CRITICAL
1. Fix all direct `$_GET/$_POST` SQL injection vulnerabilities
2. Add `sql_int()` and `sql_escape()` to all user inputs
3. Files: modal_molist.php, makeoptionindex.php, inv-m.php, exp-m.php

### Phase 2: Prepared Statements (3-5 days) 🟠 HIGH
1. Create Database helper class with prepared statement support
2. Migrate core files to use prepared statements
3. Files: core-function.php, all *-make.php, all *-list.php

### Phase 3: Transaction Support (2-3 days) 🟡 MEDIUM
1. Add transactions to multi-table operations
2. Create transaction helper methods
3. Files: core-function.php, payment.php, po-make.php

### Phase 4: Foreign Keys (1 day) 🟡 MEDIUM
1. Clean orphan data
2. Add foreign key constraints
3. Test cascade behaviors

### Phase 5: Query Optimization (2-3 days) 🟢 LOW
1. Replace N+1 queries with JOINs
2. Add pagination for large result sets
3. Implement query caching where appropriate

---

## Quick Wins (Can Do Now)

These files have SQL injection and can be fixed immediately:

1. `modal_molist.php` - 2 vulnerabilities
2. `makeoptionindex.php` - 4 vulnerabilities  
3. `model.php` - 1 vulnerability
4. `inv-m.php` - 4 vulnerabilities
5. `exp-m.php` - 3 vulnerabilities

---

## Metrics to Track

After implementing fixes:
- [ ] 0 SQL injection vulnerabilities
- [ ] 100% queries using prepared statements or sql_int/sql_escape
- [ ] All multi-table operations use transactions
- [ ] Foreign key constraints on all relationships
- [ ] Average query time < 100ms

---

## Next Steps

1. **Immediate**: Fix SQL injection in Priority 1 files
2. **This Week**: Create Database helper class with prepared statements
3. **Next Week**: Add transactions to core operations
4. **After Testing**: Add foreign key constraints

Would you like me to start with any specific phase?
