---
name: feature-workflow
description: 'End-to-end feature implementation workflow for iACC. USE FOR: building new features, adding modules, full-stack implementation checklists, feature branch workflow, migration + model + controller + views + routes + tests pipeline. Use when: implementing a new feature from scratch, following the complete build pipeline, creating feature branches, wiring all MVC layers together.'
argument-hint: 'Describe the feature to implement end-to-end'
---

# Feature Implementation Workflow

## When to Use

- Building a new feature from scratch (e.g., new module, new user flow)
- Need a systematic checklist to avoid missing steps
- Wiring all MVC layers (migration → model → controller → views → routes → tests)

## Implementation Order

Follow these steps in order. Each step depends on the previous.

### 1. Create Feature Branch

```bash
git checkout main && git pull origin main
git checkout -b feature/v<version>-<feature-name>
```

Branch naming: `feature/v6.0-self-registration`, `feature/v6.1-payment-gateway`

### 2. Write Database Migration

Create in **both** locations:
- `database/migrations/NNN_<name>.sql` — Simple version with `IF NOT EXISTS`
- `migrations/NNN_<name>.sql` — MySQL 5.7 compatible with stored procedure

**MySQL 5.7 Idempotent Column Pattern** (no `ADD COLUMN IF NOT EXISTS`):

```sql
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_my_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = 'my_table'
        AND column_name = 'new_column'
    ) THEN
        ALTER TABLE my_table ADD COLUMN new_column VARCHAR(255) DEFAULT NULL AFTER existing_column;
    END IF;
END //
DELIMITER ;

CALL add_my_columns();
DROP PROCEDURE IF EXISTS add_my_columns;
```

### 3. Build the Model

Location: `app/Models/<Name>.php`

```php
<?php
namespace App\Models;

class MyFeature extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        // $this->conn is available from BaseModel via global $db
    }

    public function createSomething(string $param): int
    {
        $stmt = $this->conn->prepare("INSERT INTO my_table (col) VALUES (?)");
        $stmt->bind_param('s', $param);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
}
```

Key patterns:
- Use **prepared statements** for all queries (never string interpolation)
- Use **transactions** for multi-table operations (`$this->conn->begin_transaction()`)
- Use **isolated arrays** per DB operation when using HardClass (never reuse `$args`)

### 4. Build the Controller

Location: `app/Controllers/<Name>Controller.php`

```php
<?php
namespace App\Controllers;

class MyController extends BaseController
{
    public function index()
    {
        $this->verifyCsrf();        // POST routes
        $model = new \App\Models\MyFeature();
        $data = $model->getAll();
        $this->render('my/index', ['items' => $data]);
    }
}
```

Security checklist:
- `$this->verifyCsrf()` on all POST handlers
- Rate limiting for sensitive actions (login, register, password reset)
- `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` for all output
- Validate/sanitize all `$_POST`/`$_GET` input at the boundary

### 5. Build the Views

Location: `app/Views/<feature>/<action>.php`

Design conventions:
- Match existing styles (split-panel for auth pages, card layout for dashboards)
- Include CSRF token in all forms: `<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">`
- Use Font Awesome 4.7 icons, Bootstrap 5.3 grid, Inter font
- All output escaped: `<?= htmlspecialchars($var, ENT_QUOTES, 'UTF-8') ?>`

### 6. Wire Routes

Location: `app/Config/routes.php`

```php
// Route types:
'page_name' => ['Controller', 'method'],           // Normal (auth required)
'page_name' => ['Controller', 'method', 'public'],  // Public (no auth)
'page_name' => ['Controller', 'method', 'standalone'], // Auth, no layout wrapper
```

Place routes in the appropriate section with a comment header.

### 7. Update Cross-Links

Add navigation links from existing pages:
- Login page → registration link
- Dashboard → new feature link
- Sidebar menu → new section

### 8. Run Migration

```bash
# Copy and execute
docker cp migrations/NNN_name.sql iacc_php:/var/www/html/migrations/
docker exec iacc_mysql mysql -uroot -proot iacc < migrations/NNN_name.sql

# Verify
docker exec iacc_mysql mysql -uroot -proot iacc -e "DESCRIBE new_table"
docker exec iacc_mysql mysql -uroot -proot iacc -e "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='iacc' AND table_name='my_table' AND column_name='new_column'"
```

If DELIMITER-based SQL fails via pipe, run ALTER statements directly.

### 9. PHP Syntax Validation

```bash
docker exec iacc_php bash -c "
php -l /var/www/html/app/Models/MyFeature.php && \
php -l /var/www/html/app/Controllers/MyController.php && \
php -l /var/www/html/app/Views/my/index.php && \
php -l /var/www/html/app/Config/routes.php
"
```

### 10. Write E2E Tests

Location: `tests/test-<feature>.php`

Follow the standard test pattern:
- Setup: `session_start()`, require configs, create `$db` + `$har`
- Use `test($name, $condition, $details)` helper
- Test categories: schema exists, CRUD operations, model methods, route config, HTTP page loads
- Always clean up test data at the end
- Use `try/catch` for expected exceptions (MySQL runs in exception mode)

```bash
docker exec iacc_php php /var/www/html/tests/test-my-feature.php
```

### 11. Commit and Push

```bash
git add <all-new-and-modified-files>
git commit -m "feat: v6.x description

- Model: what it does
- Controller: what it handles
- Views: what pages were created
- Migration: what schema changes
- Tests: N tests (all passing)"

git push origin feature/v<version>-<feature-name>
```

## Checklist Summary

| # | Step | Verify |
|---|------|--------|
| 1 | Feature branch | `git branch` shows correct branch |
| 2 | Migration SQL | Files in both `database/migrations/` and `migrations/` |
| 3 | Model | Prepared statements, transactions for multi-table |
| 4 | Controller | CSRF, rate limiting, input validation |
| 5 | Views | Escaped output, CSRF tokens, responsive design |
| 6 | Routes | Added to `routes.php` with correct type |
| 7 | Cross-links | Existing pages link to new feature |
| 8 | Migration run | Schema verified in MySQL |
| 9 | Syntax check | `php -l` all new files |
| 10 | Tests | All passing, test data cleaned up |
| 11 | Commit | Descriptive message, pushed to remote |
