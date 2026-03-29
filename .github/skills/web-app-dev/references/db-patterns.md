# Database Patterns Reference

## Critical: Isolated $args Arrays

The root cause of many legacy bugs is shared `$args` variable. Always use isolated arrays:

```php
// GOOD - isolated arrays per operation
$argsPO = array();
$argsPO['table'] = "po";
$argsPO['columns'] = "col1, col2, col3";
$argsPO['value'] = "...";
$har->insertDbMax($argsPO);

$argsProduct = array();  // Fresh array!
$argsProduct['table'] = "product";
$argsProduct['value'] = "...";
$har->insertDB($argsProduct);
```

```php
// BAD - causes state leakage
$args['table'] = "po";
$args['columns'] = "col1, col2, col3";
$har->insertDbMax($args);
$args['table'] = "product";  // columns still has PO columns!
$har->insertDB($args);       // ERROR: column count mismatch
```

## SQL Escape & Injection Prevention

```php
// Always escape user input
$name = sql_escape($_REQUEST['name']);
$id = sql_int($_REQUEST['id']);  // or intval()

// For numeric fields, use type casting
$price = floatval($_REQUEST['price']);
$quantity = intval($_REQUEST['quantity']);
```

## Multi-Tenant Queries

```php
// Always filter by company_id
$company_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

// In SELECT
$sql = "SELECT * FROM table_name WHERE company_id = {$company_id} AND deleted_at IS NULL";

// In INSERT
$sql = "INSERT INTO table_name (name, company_id) VALUES ('{$name}', {$company_id})";
```

## Soft Delete Pattern

```php
// Delete (soft)
$sql = "UPDATE table_name SET deleted_at = NOW() WHERE id = {$id} AND company_id = {$company_id}";

// Always filter deleted records
$sql = "SELECT * FROM table_name WHERE deleted_at IS NULL AND company_id = {$company_id}";
```

## Auto-Increment INSERT

```php
// Use NULL for auto-increment columns, NOT empty string
$sql = "INSERT INTO table_name (id, name) VALUES (NULL, '{$name}')";
```

## Migration Conventions

- File naming: `NNN_description.sql` (e.g., `006_my_feature.sql`)
- Location: `database/migrations/`
- Always include: `IF NOT EXISTS`, `DEFAULT CHARSET=utf8mb4`
- Always add: `created_at`, `updated_at`, `deleted_at` columns
- Always add: `company_id` with index for multi-tenant
