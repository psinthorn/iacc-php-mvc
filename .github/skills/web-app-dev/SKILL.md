---
name: web-app-dev
description: 'Web application development for iACC PHP MVC project. USE FOR: creating controllers, models, views, routes, migrations, API endpoints, form handling, PDF templates, CRUD operations, database queries, MVC refactoring, adding new modules/pages. Use when: building features, adding pages, creating forms, writing SQL, designing REST APIs, fixing MVC routing, generating PDF views, implementing RBAC permissions.'
argument-hint: 'Describe the feature, page, or module you want to build'
---

# Web Application Development — iACC PHP MVC

## When to Use

- Building new features, modules, or pages
- Creating/editing Controllers, Models, Views
- Adding routes to the routing system
- Writing database migrations
- Building REST API endpoints
- Creating forms with CSRF protection
- Generating PDF templates
- Implementing RBAC permission checks
- Adding CRUD operations

## Architecture Overview

```
app/
├── Config/routes.php         # All route definitions
├── Controllers/              # Request handlers (extend BaseController)
├── Models/                   # Business logic & DB access (extend BaseModel)
├── Services/                 # Business services (ChannelService, etc.)
└── Views/                    # Blade-style PHP templates by module
```

### Request Flow

```
Browser → index.php → routes.php → Controller → Model → View
API      → api.php  → Controller → Service    → JSON Response
```

## Procedures

### 1. Create a New Module (Controller + Model + Views + Routes)

**Step 1: Create the Controller** in `app/Controllers/`

```php
<?php
namespace App\Controllers;

class NewModuleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();  // Enforce login
    }

    public function index()
    {
        // Use company filter for multi-tenant
        $companyFilter = $this->getCompanyFilter();
        $model = new \App\Models\NewModule();
        $data = $model->getAll($companyFilter);
        $this->render('new-module/list', ['items' => $data]);
    }

    public function create()
    {
        if ($this->isPost()) {
            $this->validateCsrf();
            // Handle form submission
            $model = new \App\Models\NewModule();
            $model->create($_POST);
            $this->redirect('?page=new_module');
        }
        $this->render('new-module/form');
    }
}
```

**Step 2: Create the Model** in `app/Models/`

```php
<?php
namespace App\Models;

class NewModule extends BaseModel
{
    protected $table = 'new_module';

    public function getAll($companyFilter = '')
    {
        $where = $companyFilter ? "WHERE company_id = " . intval($companyFilter) : '';
        $sql = "SELECT * FROM {$this->table} {$where} AND deleted_at IS NULL ORDER BY id DESC";
        return $this->query($sql);
    }

    public function create($data)
    {
        // Always use prepared statements or sql_escape()
        $sql = "INSERT INTO {$this->table} (name, company_id, created_at)
                VALUES ('" . sql_escape($data['name']) . "', " . intval($data['company_id']) . ", NOW())";
        return $this->execute($sql);
    }
}
```

**Step 3: Create Views** in `app/Views/new-module/`

See [View Templates Reference](./references/view-templates.md)

**Step 4: Register Routes** in `app/Config/routes.php`

```php
// Route types:
'new_module'        => ['NewModuleController', 'index'],               // Normal — rendered inside admin layout
'new_module_create' => ['NewModuleController', 'create'],              // Normal
'new_module_store'  => ['NewModuleController', 'store'],               // POST handler (early dispatch + exit)
'new_module_print'  => ['NewModuleController', 'print', 'standalone'], // Own HTML shell — NOT wrapped in layout
'health'            => ['HealthController', 'index', 'public'],        // No auth required
```

**Route type rules:**
| Type | Third Param | When to Use |
|------|-------------|-------------|
| Normal | _(none)_ | Controller uses `$this->render('view')` — rendered inside admin sidebar+header layout |
| Standalone | `'standalone'` | Controller uses `include $file; exit;` — page has its own `<html><head><body>` (devtools, PDFs, AI settings) |
| Public | `'public'` | No authentication required (health check, landing pages) |

**CRITICAL**: If a controller method includes a file and calls `exit;`, the route **MUST** have `'standalone'`. Without it, the view runs inside the admin layout where `chdir()` and relative paths break.

### Path Depth Rules for Views

Views in `app/Views/` are **3 levels deep** from project root `/var/www/html/`:

```
/var/www/html/                          ← project root
└── app/Views/devtools/test-crud.php    ← 3 levels deep
└── app/Views/ai/settings.php           ← 3 levels deep
```

- `chdir()` to project root: `chdir(__DIR__ . "/../../..");` — NOT `"/../.."`
- `__DIR__` to `inc/`: `__DIR__ . '/../../../inc/file.php'` — NOT `'/../../inc/'`
- `__DIR__` to `ai/`: `__DIR__ . '/../../../ai/file.php'` — NOT `'/../../ai/'`

### `require_once` Scoping Gotcha for Standalone Views

When `index.php` already loaded `inc/sys.configs.php`, a standalone view's `require_once("inc/sys.configs.php")` is a **no-op**. Variables like `$config` exist only in the global scope, not in the controller method's scope where `include` runs.

**Fix**: In the controller, expose globals before including the view:
```php
// In controller method:
$config = $GLOBALS['config'] ?? null;
include __DIR__ . '/../Views/module/standalone-view.php';
exit;

// Or use a helper:
private function includeStandalone(string $viewFile): void
{
    $config = $GLOBALS['config'] ?? null;
    $db = $GLOBALS['db'] ?? null;
    include $viewFile;
    exit;
}
```

**Step 5: Create Migration** in `database/migrations/`

```sql
-- Migration: XXX_new_module.sql
CREATE TABLE IF NOT EXISTS new_module (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES company(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Add Menu Item

Edit `app/Views/layouts/sidebar.php` and add:

```php
<li class="nav-item">
    <a class="nav-link" href="?page=new_module">
        <i class="fas fa-icon-name"></i>
        <span><?= $lang['new_module'] ?? 'New Module' ?></span>
    </a>
</li>
```

### 3. Add RBAC Permission Check

```php
// In controller method
require_permission('manage_new_module');
// or check inline
if (!has_permission('view_new_module')) {
    $this->redirect('?page=dashboard');
}
```

### 4. AJAX Endpoints: Separate Standalone Routes

When a **normal** route page needs to call an AJAX/JSON API endpoint (e.g., for live search, save-without-reload), the API endpoint **MUST** be a separate `'standalone'` route.

**Why**: Normal routes render inside the admin HTML shell. If a controller returns JSON inside a normal route, the response gets wrapped in `<html><head>...` from the layout — breaking the JSON.

```php
// routes.php
'ai_settings'     => ['AiSettingsController', 'index'],               // Normal — HTML page inside layout
'ai_settings_api' => ['AiSettingsController', 'api', 'standalone'],    // Standalone — returns JSON only

// DevTools example
'debug_session'     => ['DevToolsController', 'debugSession'],              // Normal — HTML page
'debug_session_api' => ['DevToolsController', 'debugSessionApi', 'standalone'], // Standalone — JSON API for AJAX
```

**Pattern**: Name the AJAX route `{page}_api` and keep it in the same controller.

### 5. CSRF Token in AJAX POST Calls

Controllers that call `$this->verifyCsrf()` require the CSRF token on **all** POST requests, including AJAX `fetch()` calls.

```javascript
// In a view inside admin layout — csrf_token() is available via inc/csrf.php
fetch('index.php?page=ai_settings_api', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'param=value&csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
})
```

**Common mistake**: Forgetting `csrf_token` in AJAX calls → controller returns 403/redirect instead of JSON.

### 6. CSS Scoping in Admin Layout Views

When a view renders inside the admin layout (normal route), its CSS selectors can **leak** into the sidebar, navbar, and other layout elements.

**Problem**: Generic selectors like `.form-group`, `.alert`, `.card`, `.btn` override the admin layout styles.

**Solution**: Wrap all view content in a page-specific class and scope all CSS under it:

```php
<!-- View: app/Views/ai/settings.php -->
<style>
    /* GOOD — scoped under page wrapper */
    .ai-settings-page .provider-card { border: 2px solid #e0e0e0; }
    .ai-settings-page .form-group input { height: 44px; }
    
    /* BAD — leaks into layout */
    .provider-card { border: 2px solid #e0e0e0; }  /* affects sidebar cards too! */
    .form-group input { height: 44px; }              /* changes all forms! */
</style>

<div class="ai-settings-page">
    <!-- All page content here -->
</div>
```

**CSS Gotcha — `overflow: hidden` clips dropdowns**:

If a parent container has `overflow: hidden`, absolutely-positioned children (dropdowns, search results, popovers) get clipped. Use `overflow: visible` on containers that have dropdown children, or move the dropdown to a portal/outside the clipped container.

### 7. Shared Navigation Partial

For groups of related admin pages (e.g., AI admin, devtools), create a shared navigation partial:

```php
// app/Views/ai/_nav.php
<?php
$currentPage = $currentPage ?? '';
$pages = [
    ['page' => 'ai_settings',       'icon' => 'fa-cogs',     'label' => 'Settings'],
    ['page' => 'ai_chat_history',   'icon' => 'fa-comments', 'label' => 'Chat History'],
    // ... more pages
];
?>
<div class="ai-nav-bar" style="margin-bottom: 20px; ...">
    <?php foreach ($pages as $p): ?>
    <a href="index.php?page=<?= $p['page'] ?>" 
       class="btn btn-sm <?= $currentPage === $p['page'] ? 'btn-primary' : 'btn-default' ?>">
        <i class="fa <?= $p['icon'] ?>"></i> <?= $p['label'] ?>
    </a>
    <?php endforeach; ?>
</div>
```

**Usage in each page view:**
```php
<?php $currentPage = 'ai_settings'; ?>
<?php include __DIR__ . '/_nav.php'; ?>
```

**Convention**: Prefix partial files with `_` (e.g., `_nav.php`, `_filters.php`).

### 8. POST Handlers in Normal Routes (PRG Pattern)

In normal routes, `index.php` dispatches POST handlers **before** rendering the HTML shell. The POST handler runs, then `exit;` is called before any HTML output.

**This means**: POST handlers in normal routes MUST use **Post-Redirect-Get (PRG)** — they cannot render a view directly because the HTML shell hasn't started yet.

```php
// GOOD — PRG pattern for normal route POST handler
public function store()
{
    $this->verifyCsrf();
    // ... process form ...
    $this->redirect('?page=items_list');  // redirect, then exit
}

// BAD — trying to render from POST handler in normal route
public function store()
{
    $this->verifyCsrf();
    // ... process form ...
    $this->render('items/success');  // Won't work — HTML shell not started yet!
}
```

## Critical Rules

1. **Multi-tenant isolation**: Always filter by `company_id` from `$_SESSION['com_id']`
2. **CSRF on all forms**: Use `csrf_field()` in forms, `$this->validateCsrf()` in controllers
3. **CSRF on AJAX POSTs**: Include `csrf_token` parameter in all `fetch()` POST bodies
4. **SQL injection prevention**: Use `sql_escape()` or prepared statements — NEVER raw `$_REQUEST` in queries
5. **Isolated $args arrays**: Use separate arrays per DB operation (see [DB Patterns](./references/db-patterns.md))
6. **Soft delete**: Use `deleted_at` column, filter with `WHERE deleted_at IS NULL`
7. **Security headers**: BaseController handles these automatically
8. **Rate limiting**: Login uses 5 attempts/15 min; API uses 60/min
9. **CSS scoping**: Views in normal routes MUST scope CSS under a page-specific wrapper class
10. **AJAX endpoints**: JSON-returning endpoints MUST be `'standalone'` routes — never normal

## Testing

```bash
# Run all tests
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php
docker exec iacc_php php /var/www/html/tests/test-api-phase3.php
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php

# PHP syntax check
php -l app/Controllers/NewController.php
```

## Deployment

- **Staging**: Push to `develop` → GitHub Actions auto-deploys to dev.iacc.f2.co.th
- **Production**: Push to `main` → GitHub Actions auto-deploys to iacc.f2.co.th
