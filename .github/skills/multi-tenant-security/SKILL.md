---
name: multi-tenant-security
description: 'Multi-tenant data isolation and company filtering for iACC. USE FOR: company-based data filtering, query construction with company_id, preventing cross-company data leakage, record ownership validation, multi-company access, INSERT company assignment, filtered vs global tables. Use when: writing queries that access company data, building list pages, validating record access, creating new records with company_id.'
argument-hint: 'Describe the multi-tenant query or filtering need'
---

# Multi-Tenant Security (CompanyFilter)

## When to Use

- Writing any query that accesses company-scoped data
- Building list/detail pages that filter by company
- Validating a user can access a specific record
- Creating records that need `company_id` assignment
- Building queries with JOINs across filtered tables

## Architecture

```
Session: $_SESSION['com_id'] = 7
    ↓
CompanyFilter::getInstance()  (singleton, loads session)
    ↓
Query Helper Methods:
  whereCompanyFilter()  → "WHERE company_id = 7"
  andCompanyFilter()    → "AND company_id = 7"
  getFilterForPrepared()→ ['column'=>'company_id', 'value'=>7, 'type'=>'i']
```

**File**: `inc/class.company_filter.php`

## Table Classification

### Filtered Tables (19 — always add company_id)

`brand`, `category`, `type`, `model`, `map_type_to_brand`, `payment_methods`, `payment_gateway_config`, `po`, `iv`, `product`, `deliver`, `pay`, `pr`, `voucher`, `receipt`, `store`, `sendoutitem`, `receive`, `audit_log`

### Global Tables (6 — no company filter)

`company`, `company_addr`, `company_credit`, `authorize`, `countries`, `_migration_log`

**Rule**: If the table has a `company_id` column AND is in the filtered list, ALWAYS filter. Never return unfiltered data from filtered tables.

## Procedures

### 1. Basic Query Patterns

```php
$cf = CompanyFilter::getInstance();

// SELECT with WHERE (start of conditions)
$sql = "SELECT * FROM brand " . $cf->whereCompanyFilter('brand');
// → "SELECT * FROM brand WHERE brand.company_id = 7"

// SELECT with AND (add to existing WHERE)
$sql = "SELECT * FROM brand WHERE status = 1 " . $cf->andCompanyFilter('brand');
// → "SELECT * FROM brand WHERE status = 1 AND brand.company_id = 7"

// SELECT without table alias
$sql = "SELECT * FROM category " . $cf->whereCompanyFilter();
// → "SELECT * FROM category WHERE company_id = 7"

// SELECT with soft delete + company filter
$sql = "SELECT * FROM brand WHERE deleted_at IS NULL " . $cf->andCompanyFilter();
```

### 2. JOIN Queries

```php
$cf = CompanyFilter::getInstance();

// JOIN with alias — filter on main table
$sql = "SELECT p.*, c.name_en AS category_name
        FROM product p
        LEFT JOIN category c ON p.category_id = c.id
        WHERE p.status = 1 " . $cf->andCompanyFilter('p');

// JOIN with both tables filtered
$sql = "SELECT po.*, pr.pr_number
        FROM po
        INNER JOIN pr ON po.pr_id = pr.id " 
        . $cf->andCompanyFilter('pr')
        . " " . $cf->whereCompanyFilter('po');
```

### 3. INSERT with Company ID

```php
$cf = CompanyFilter::getInstance();
$companyId = $cf->getCompanyIdForInsert(); // Throws if no company selected

// Using HardClass safe methods
$har->insertSafe('brand', [
    'name_en' => $name,
    'company_id' => $companyId,
    'created_at' => date('Y-m-d H:i:s')
]);

// Using prepared statements directly
$stmt = $conn->prepare("INSERT INTO brand (name_en, company_id) VALUES (?, ?)");
$stmt->bind_param('si', $name, $companyId);
$stmt->execute();
```

### 4. Prepared Statement Integration

```php
$cf = CompanyFilter::getInstance();
$filter = $cf->getFilterForPrepared();
// Returns: ['column' => 'company_id', 'value' => 7, 'type' => 'i']

// Use with prepared statements
$stmt = $conn->prepare("SELECT * FROM brand WHERE name_en = ? AND company_id = ?");
$stmt->bind_param('s' . $filter['type'], $name, $filter['value']);

// Or use addToConditions for query builders
$conditions = ['status = ?'];
$params = [1];
$cf->addToConditions($conditions, $params);
// conditions: ['status = ?', 'company_id = ?']
// params: [1, 7]
```

### 5. Record Ownership Validation

**Always validate before UPDATE/DELETE operations:**

```php
$cf = CompanyFilter::getInstance();

// Before updating a record
if (!$cf->validateRecordOwnership($conn, 'brand', $brandId)) {
    // Record doesn't belong to this company — deny access
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Safe to proceed with update
$har->updateSafe('brand', ['name_en' => $newName], ['id' => $brandId]);
```

### 6. In BaseModel (Automatic Filtering)

```php
// BaseModel automatically applies company filter when useCompanyFilter = true
class BrandModel extends BaseModel {
    protected $useCompanyFilter = true; // Default: true
    
    public function getAll() {
        // Company filter automatically applied
        return $this->hard->selectActiveSafe('brand', 
            ['company_id' => $this->getCompanyId()]);
    }
}

// For global data (no company filter)
class CountryModel extends BaseModel {
    protected $useCompanyFilter = false; // Disable for global tables
}
```

### 7. Multi-Company Access

```php
$cf = CompanyFilter::getInstance();
$companyIds = $cf->getAccessibleCompanyIds($conn);

// IN clause for multiple companies
$sql = "SELECT * FROM po WHERE status = 'active' " 
     . $cf->inCompanyFilter($companyIds, 'po');
// → "AND po.company_id IN (7, 12, 15)"
```

## Security Rules

1. **NEVER skip company filter** on filtered tables — even for "read-only" queries
2. **ALWAYS validate ownership** before UPDATE or DELETE
3. **ALWAYS assign company_id** on INSERT to filtered tables
4. **Use `intval()`** — CompanyFilter does this internally, but double-check raw queries
5. **Test isolation** — create data in company A, verify company B cannot see it
6. **No company = no filter** — when `com_id` is null, `whereCompanyFilter()` returns `WHERE 1=1` (shows all). This is intentional for super-admin views only.

## Helper Functions (Global)

For backward compatibility, these global functions wrap CompanyFilter:

```php
$comId = getCompanyId();           // Get current company ID
$where = whereCompanyFilter();      // "WHERE company_id = 7"
$and = andCompanyFilter('po');      // "AND po.company_id = 7"
$or = orCompanyFilter();            // "OR company_id = 7"
$safe = getSafeCompanyId(0);        // 7 or default 0
$filter = company_filter('t', 'company_id');  // "AND t.company_id = 7"
```
