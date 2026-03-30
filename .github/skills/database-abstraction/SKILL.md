---
name: database-abstraction
description: 'HardClass database abstraction layer for iACC. USE FOR: database queries, CRUD operations, prepared statements, soft delete, record restore, legacy vs modern query methods, transaction handling, $args reuse bug prevention. Use when: writing database queries, choosing between insertSafe and insertDbMax, handling soft deletes, using transactions, debugging query errors.'
argument-hint: 'Describe the database operation or query pattern needed'
---

# Database Abstraction (HardClass)

## When to Use

- Writing any database query (CRUD operations)
- Choosing between safe (new) vs legacy (old) methods
- Implementing soft delete / restore
- Wrapping multi-table operations in transactions
- Debugging "column count mismatch" or query errors

## Method Reference

### Modern Safe Methods (use for ALL new code)

| Method | Purpose | Returns |
|--------|---------|---------|
| `insertSafe($table, $data)` | INSERT with prepared statements | `int` insert ID or `false` |
| `insertSafeMax($table, $data)` | INSERT with auto-generated Maxid | `int` ID or `false` |
| `updateSafe($table, $data, $where)` | UPDATE with prepared statements | `bool` |
| `deleteSafe($table, $where)` | Hard DELETE with prepared statements | `bool` |
| `selectSafe($table, $where, $columns)` | SELECT multiple rows | `array` of rows or `false` |
| `selectOneSafe($table, $where, $columns)` | SELECT single row | `array` row or `null` |
| `softDelete($table, $where)` | Set `deleted_at = NOW()` | `bool` |
| `restore($table, $where)` | Set `deleted_at = NULL` | `bool` |
| `selectActiveSafe($table, $where, $columns)` | SELECT where `deleted_at IS NULL` | `array` |
| `selectDeletedSafe($table, $where, $columns)` | SELECT where `deleted_at IS NOT NULL` | `array` |
| `forceDelete($table, $where)` | Permanent delete (only soft-deleted records) | `bool` |

### Legacy Methods (maintain existing code only)

| Method | Replacement | Risk |
|--------|-------------|------|
| `insertDbMax($args)` | `insertSafeMax()` | `$args` reuse bug, no prepared statements |
| `insertDb($args)` | `insertSafe()` | Same risks |
| `updateDb($args)` | `updateSafe()` | String concatenation SQL injection risk |
| `deleteDb($args)` | `deleteSafe()` | String concatenation |
| `Maxid($table)` | Still used | Returns `MAX(id) + 1` |

## Procedures

### 1. Basic CRUD with Safe Methods

```php
$har = new HardClass();
$har->setConnection($db->conn);

// CREATE
$id = $har->insertSafe('brand', [
    'name_en' => $name,
    'company_id' => $_SESSION['com_id'],
    'created_at' => date('Y-m-d H:i:s')
]);

// CREATE with Maxid (legacy tables without AUTO_INCREMENT)
$id = $har->insertSafeMax('category', [
    'name_en' => $name,
    'company_id' => $_SESSION['com_id']
]);

// READ (multiple rows)
$rows = $har->selectSafe('brand', ['company_id' => $_SESSION['com_id']]);

// READ (single row)
$row = $har->selectOneSafe('brand', ['id' => $id]);

// UPDATE
$har->updateSafe('brand', ['name_en' => $newName], ['id' => $id]);

// DELETE (soft)
$har->softDelete('brand', ['id' => $id]);

// DELETE (hard — only if already soft-deleted)
$har->forceDelete('brand', ['id' => $id]);
```

### 2. Transaction Pattern (Multi-Table Operations)

```php
$conn = $db->conn;
$conn->begin_transaction();

try {
    // Step 1: Create parent record
    $companyId = $har->insertSafeMax('company', [
        'com_name' => $name,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Step 2: Create child record
    $userId = $har->insertSafeMax('authorize', [
        'email' => $email,
        'password' => $hash,
        'company_id' => $companyId
    ]);

    // Step 3: Create related record
    $har->insertSafe('api_subscriptions', [
        'company_id' => $companyId,
        'plan' => 'trial'
    ]);

    $conn->commit();
} catch (\Exception $e) {
    $conn->rollback();
    throw $e;
}
```

### 3. Soft Delete Lifecycle

```php
// Tables need: ALTER TABLE brand ADD deleted_at DATETIME NULL DEFAULT NULL;

// 1. Soft delete (hide from normal queries)
$har->softDelete('brand', ['id' => $id]);

// 2. Query active records only (skips soft-deleted)
$active = $har->selectActiveSafe('brand', ['company_id' => $comId]);

// 3. Query deleted records (for recovery UI)
$deleted = $har->selectDeletedSafe('brand', ['company_id' => $comId]);

// 4. Restore a soft-deleted record
$har->restore('brand', ['id' => $id]);

// 5. Permanently remove (only works on already-soft-deleted records)
$har->forceDelete('brand', ['id' => $id]);
```

### 4. Type Inference for Prepared Statements

HardClass automatically infers `mysqli` bind types via `getTypes()`:

| PHP Type | MySQL Bind | Example |
|----------|------------|---------|
| `int` | `i` | `42` |
| `float/double` | `d` | `3.14` |
| `string` (everything else) | `s` | `'hello'`, `null` (cast to string) |

**Important**: If you pass a numeric string like `'42'`, it binds as `s` (string). Cast to `(int)` if you need integer binding.

## Critical Bug: $args Array Reuse

The #1 source of bugs in this codebase. Legacy methods share the `$args` array, causing column/value mismatch.

```php
// BAD — columns leak between operations
$args['table'] = "po";
$args['columns'] = "col1, col2, col3";
$args['value'] = "'v1', 'v2', 'v3'";
$har->insertDbMax($args);

$args['table'] = "product";        // columns still has PO columns!
$args['value'] = "'p1', 'p2'";
$har->insertDb($args);             // ERROR: column count mismatch

// GOOD — isolated arrays
$argsPO = ['table' => 'po', 'columns' => 'col1, col2', 'value' => "'v1', 'v2'"];
$har->insertDbMax($argsPO);

$argsProduct = ['table' => 'product', 'columns' => 'col1', 'value' => "'p1'"];
$har->insertDb($argsProduct);

// BEST — use safe methods (no $args at all)
$har->insertSafe('po', ['col1' => 'v1', 'col2' => 'v2']);
$har->insertSafe('product', ['col1' => 'p1']);
```

## Connection Setup

```php
// Standard setup
$db = new DbConn($config);
$har = new HardClass();
$har->setConnection($db->conn);

// In Models (BaseModel handles connection)
class MyModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        // $this->conn available from BaseModel
        // $this->hard available (HardClass instance)
    }
}

// Connection fallback chain: explicit setConnection() → global $db → global $users
```
