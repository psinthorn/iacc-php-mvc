---
name: legacy-migration
description: 'Migrate legacy procedural PHP code to modern MVC architecture in iACC. USE FOR: converting legacy pages to controllers, refactoring procedural code to OOP, migrating from legacy $args queries to prepared statements, deprecating old code safely, bridge patterns between legacy and MVC. Use when: moving pages from legacy/ to app/, refactoring core-function.php switch cases, converting string-based queries to prepared statements, creating backward-compatible wrappers.'
argument-hint: 'Describe the legacy code to migrate or what needs refactoring'
---

# Legacy → MVC Migration

## When to Use

- Converting a legacy page (e.g., `legacy/po-make.php`) to MVC
- Refactoring `core-function.php` switch cases into controllers
- Migrating `$args`-based queries to prepared statements
- Creating compatibility wrappers during incremental migration
- Deprecating old code paths without breaking existing functionality

## Architecture (Current State)

```
Legacy (procedural)           Modern (MVC)
─────────────────            ─────────────
legacy/po-make.php           app/Controllers/POController.php
legacy/category.php          app/Controllers/CategoryController.php
legacy/dashboard.php         app/Controllers/DashboardController.php
core-function.php            app/Controllers/*Controller.php
inc/class.hard.php           app/Models/*Model.php

Entry Points:
  index.php?page=X → routes.php → Controller::method()  (MVC)
  legacy/*.php      → direct file include                (Legacy)
  core-function.php → POST handler with switch($method)  (Legacy)
```

## Migration Checklist

### Step 1: Identify the Legacy Page

```
Source files to check:
├── legacy/<name>.php          (procedural page)
├── core-function.php          (form handler, switch cases)
├── inc/<name>.php             (include files)
└── template/<name>.php        (view templates)
```

### Step 2: Create MVC Equivalents

| Legacy | MVC Equivalent | Notes |
|--------|---------------|-------|
| `legacy/category.php` | `app/Controllers/CategoryController.php` | Business logic |
| SQL in page | `app/Models/Category.php` | Database abstraction |
| HTML in page | `app/Views/category/index.php` | Presentation only |
| `core-function.php` case | Controller POST method | Form handling |

### Step 3: Migrate Queries

**Legacy pattern** (string concatenation, vulnerable):
```php
// core-function.php
$args['table'] = "brand";
$args['value'] = "'" . $_POST['name'] . "'";
$har->insertDb($args);
```

**Modern pattern** (prepared statements, secure):
```php
// app/Models/Brand.php
public function create(string $name, int $companyId): int
{
    return $this->hard->insertSafeMax('brand', [
        'name_en' => $name,
        'company_id' => $companyId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}
```

### Step 4: Create Controller

```php
// app/Controllers/BrandController.php
namespace App\Controllers;

class BrandController extends BaseController
{
    public function index()
    {
        $model = new \App\Models\Brand();
        $brands = $model->getAll();
        $this->render('brand/index', ['brands' => $brands]);
    }

    public function store()
    {
        $this->verifyCsrf();
        $name = trim($this->input('name_en'));
        
        if (empty($name)) {
            $this->redirectWithError('brand_form', 'Name is required');
            return;
        }

        $model = new \App\Models\Brand();
        $model->create($name, $_SESSION['com_id']);
        $this->redirect('brand_list', 'Brand created successfully');
    }
}
```

### Step 5: Register Routes

```php
// app/Config/routes.php
'brand_list'   => ['BrandController', 'index'],
'brand_form'   => ['BrandController', 'form'],
'brand_store'  => ['BrandController', 'store'],
'brand_view'   => ['BrandController', 'view'],
'brand_delete' => ['BrandController', 'delete'],
```

**Route type flags** (third array element):
- `'standalone'` — Page has own `<html><head><body>` and calls `exit;` (devtools, PDFs, AI settings)
- `'public'` — No authentication required
- _(none)_ — Normal page rendered inside admin layout via `$this->render()`

**CRITICAL**: Legacy standalone pages that use `chdir()` + `require_once("inc/...")` MUST have their route flagged as `'standalone'`. Without it, the view runs inside the admin HTML shell and relative paths break.

**Path depth after migration**: Views move from root (`legacy/test-crud.php`) to 3 levels deep (`app/Views/devtools/test-crud.php`). Update `chdir()` from `__DIR__` to `__DIR__ . "/../../.."` (3 levels to reach project root).

### Step 6: Keep Legacy Working (Transition Period)

During migration, both old and new code may coexist:

```php
// legacy/category.php — add deprecation notice at top
// @deprecated Use index.php?page=category_list instead
// This file is kept for backward compatibility during migration

// Redirect if the MVC route exists
if (file_exists(__DIR__ . '/app/Controllers/CategoryController.php')) {
    header('Location: index.php?page=category_list');
    exit;
}

// ... original legacy code below (fallback) ...
```

## Common Migration Patterns

### Pattern A: core-function.php Switch Case → Controller

**Before** (in core-function.php):
```php
case 'A':
    if ($form == "brand") {
        $args['table'] = "brand";
        $args['columns'] = "id, name_en, name_th, company_id";
        $id = $har->Maxid("brand");
        $args['value'] = "$id, '$name_en', '$name_th', '$com_id'";
        $har->insertDbMax($args);
    }
    break;
```

**After** (in BrandController):
```php
public function store()
{
    $this->verifyCsrf();
    $model = new \App\Models\Brand();
    $id = $model->create(
        $this->input('name_en'),
        $this->input('name_th'),
        $_SESSION['com_id']
    );
    $this->redirect('brand_list', 'Created successfully');
}
```

### Pattern B: Inline SQL → Model Method

**Before**:
```php
$result = mysqli_query($conn, "SELECT * FROM brand WHERE company_id = $com_id ORDER BY name_en");
while ($row = mysqli_fetch_assoc($result)) { ... }
```

**After**:
```php
// Model
public function getAll(): array
{
    $cf = \CompanyFilter::getInstance();
    return $this->hard->selectActiveSafe('brand', 
        ['company_id' => $cf->getSafeCompanyId()]);
}
```

### Pattern C: Mixed HTML + PHP → View + Controller

**Before** (legacy page with embedded HTML):
```php
<?php
$result = mysqli_query($conn, "SELECT * FROM brand...");
?>
<table>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr><td><?= $row['name_en'] ?></td></tr>
<?php endwhile; ?>
</table>
```

**After** (separated):
```php
// Controller
public function index() {
    $model = new \App\Models\Brand();
    $this->render('brand/index', ['brands' => $model->getAll()]);
}

// View (app/Views/brand/index.php)
<table>
<?php foreach ($brands as $brand): ?>
    <tr><td><?= htmlspecialchars($brand['name_en'], ENT_QUOTES, 'UTF-8') ?></td></tr>
<?php endforeach; ?>
</table>
```

### Pattern D: Session Auth Check → Controller Middleware

**Before**:
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

**After**: Routes without `'public'` flag automatically require auth (handled by `index.php` dispatcher). No manual check needed in controllers.

## Files Reference

| Legacy File | Purpose | Migration Status |
|-------------|---------|-----------------|
| `core-function.php` | Main CRUD handler (all forms) | Partially migrated |
| `legacy/po-make.php` | PO creation form | Legacy |
| `legacy/category.php` | Category management | Has MVC controller |
| `legacy/dashboard.php` | Dashboard page | Has MVC controller |
| `inc/class.hard.php` | DB abstraction | Active (both legacy + safe methods) |
| `inc/class.company_filter.php` | Multi-tenant filtering | Active |
| `inc/class.paypal.php` | PayPal integration | Legacy |

### Pattern E: Standalone Page → Normal Route (Admin Layout)

Convert standalone pages that have their own `<html>` shell to normal routes rendered inside the admin layout (sidebar + navbar).

**When to do this**: When standalone pages should feel integrated — devtools, admin settings, AI config panels.

**Step 1: Strip the HTML shell from the view**

```php
// BEFORE (standalone — own HTML shell)
<?php session_start(); require_once("inc/sys.configs.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Settings</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .form-group input { height: 44px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- page content -->
    </div>
</body>
</html>

// AFTER (normal — rendered inside admin layout)
<style>
    /* MUST scope CSS under page wrapper to prevent leaking into sidebar/navbar */
    .ai-settings-page .form-group input { height: 44px; }
</style>
<div class="col-lg-12">
    <div class="ai-settings-page">
        <!-- page content -->
    </div>
</div>
```

**Step 2: Remove redundant PHP headers from the view**

Remove these from the view — `index.php` already handles them:
- `session_start()` — session already started
- `require_once("inc/sys.configs.php")` — already loaded
- `require_once("inc/csrf.php")` — already loaded
- Access control checks — handled by route auth

**Step 3: Update the route**

```php
// Remove 'standalone' flag
// BEFORE:
'ai_settings' => ['AiSettingsController', 'index', 'standalone'],
// AFTER:
'ai_settings' => ['AiSettingsController', 'index'],
```

**Step 4: Update the controller**

```php
// BEFORE (standalone)
public function index()
{
    $config = $GLOBALS['config'] ?? null;
    include __DIR__ . '/../Views/ai/settings.php';
    exit;
}

// AFTER (normal — use render())
public function index()
{
    $this->render('ai/settings', ['pageTitle' => 'AI Settings']);
}
```

**Step 5: Separate AJAX endpoints**

If the standalone page had mixed concerns (HTML page + JSON API responding to POST), split them:

```php
// routes.php — the HTML page is normal, the API endpoint stays standalone
'ai_settings'     => ['AiSettingsController', 'index'],               // Normal → admin layout
'ai_settings_api' => ['AiSettingsController', 'api', 'standalone'],   // Standalone → JSON API
```

**Step 6: Scope CSS to avoid leakage**

Standalone pages often use generic CSS selectors that break the admin layout when converted:

| Selector | Problem | Fix |
|----------|---------|-----|
| `.form-group` | Overrides sidebar forms | `.my-page .form-group` |
| `.card` | Changes admin dashboard cards | `.my-page .card` |
| `.alert` | Affects layout flash messages | `.my-page .alert` |
| `.btn` | Changes sidebar nav buttons | `.my-page .btn-custom` |

**Checklist for standalone → normal conversion:**
- [ ] Removed `<!DOCTYPE>`, `<html>`, `<head>`, `<body>` tags
- [ ] Removed `session_start()` and redundant `require_once`
- [ ] Wrapped content in `<div class="col-lg-12">` → page wrapper
- [ ] Scoped ALL CSS under `.page-name-wrapper` prefix
- [ ] No `overflow: hidden` on containers with dropdowns
- [ ] Separated AJAX/JSON endpoints to standalone routes
- [ ] POST handlers use PRG (Post-Redirect-Get) pattern
- [ ] Updated route: removed `'standalone'` flag
- [ ] Updated controller: `include+exit` → `$this->render()`
- [ ] Tested page loads inside admin layout without CSS conflicts

## Safety Rules

1. **Never delete legacy files** until MVC replacement is fully tested
2. **Keep backward compatibility** — old URLs should redirect to new ones
3. **Migrate one page at a time** — don't attempt bulk migration
4. **Test both paths** during transition (legacy endpoint + MVC endpoint)
5. **Use `@deprecated` annotations** on legacy code slated for removal
6. **Run existing E2E tests** after each migration to catch regressions
