---
name: deployment
description: 'Deploy iACC to production and staging environments. USE FOR: CI/CD pipeline configuration, GitHub Actions workflows, cPanel FTP deployment, Apache .htaccess security, environment configuration, database migrations, deployment packaging, health checks. Use when: deploying to production, configuring staging, setting up CI/CD, fixing deployment issues, managing GitHub secrets, creating deployment packages.'
argument-hint: 'Describe the deployment task or environment to configure'
---

# Deployment — iACC CI/CD & Infrastructure

## When to Use

- Deploying to production or staging
- Configuring CI/CD pipelines
- Setting up server security (.htaccess)
- Running database migrations
- Creating deployment packages
- Fixing deployment failures

## Environments

| Environment     | URL               | Branch      | Method               |
| --------------- | ----------------- | ----------- | -------------------- |
| **Production**  | iacc.f2.co.th     | `main`      | GitHub Actions → FTP |
| **Staging**     | dev.iacc.f2.co.th | `develop`   | GitHub Actions → FTP |
| **Development** | localhost         | `feature/*` | Docker Compose       |

## Procedures

### 1. Production Deployment (Automated)

Push to `main` triggers a 4-job pipeline:

```
Lint → Build → Deploy → Health Check
```

**Lint Job**: PHP 8.2 syntax check on all MVC files + route audit (fails if legacy routes exist).

**Build Job**: Composer install, generate `version.json`, create deployment artifact, generate production `sys.configs.php` from secrets.

**Deploy Job**: FTP deploy to cPanel.

**Health Check**: Verify `login.php` and health endpoint respond.

### 2. Staging Deployment (Automated)

Push to `develop` triggers: Syntax Check → Composer Install → FTP Deploy.

### 3. Manual Deployment Package

```bash
./deploy-cpanel.sh v5.6

# Creates: build/iacc-cpanel-v5.6.zip
# Excludes: .git, docker, tests, legacy, node_modules, .env
# Includes: composer install --no-dev --optimize-autoloader
```

### 4. Required GitHub Secrets

```
FTP_SERVER           → ftps://iacc.f2.co.th
FTP_USER             → cpanel_username
FTP_PASSWORD         → cpanel_password
DB_HOST_STAGING      → staging-db hostname
DB_USERNAME_STAGING  → staging db user
DB_PASSWORD_STAGING  → staging db password
DB_NAME_STAGING      → iacc_staging
DB_HOST_PROD         → production-db hostname
DB_USERNAME_PROD     → production db user
DB_PASSWORD_PROD     → production db password
DB_NAME_PRODUCTION   → iacc
```

### 5. Apache Security (.htaccess.cpanel)

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Block sensitive files
<FilesMatch "\.(sql|log|bak|sh|git|env|json|md|yml)$">
    Deny from all
</FilesMatch>

# Block directories
<DirectoryMatch "^.*(tests|legacy|app|inc|docker|logs|backups|cache|migrations).*$">
    Deny from all
</DirectoryMatch>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set X-Frame-Options "SAMEORIGIN"
Header set Strict-Transport-Security "max-age=31536000"

# PHP settings (production)
php_flag display_errors Off
php_flag log_errors On
php_value memory_limit 256M
```

### 6. Database Migration

```bash
# Run on production server
mysql -u $DB_USER -p $DB_NAME < database/migrations/005_journal_module.sql

# Via Docker (development)
docker exec -i iacc_mysql mysql -uroot -proot iacc < database/migrations/005_journal_module.sql
```

Migration naming: `NNN_description.sql` (sequential numbering).

### 7. Post-Deployment Checklist

```bash
# File permissions
chmod 755 public_html/
chmod 644 public_html/*.php
chmod 770 upload/ file/ logs/

# Verify health endpoint
curl -s "https://iacc.f2.co.th/index.php?page=health" | jq .

# Verify login page loads
curl -s -o /dev/null -w "%{http_code}" "https://iacc.f2.co.th/login.php"
# Expected: 200
```

### 8. Rollback

```bash
# Revert to previous commit
git revert HEAD
git push origin main
# CI/CD will auto-deploy the reverted code

# Database rollback (manual)
mysql -u $DB_USER -p $DB_NAME < backups/pre-migration-backup.sql
```

## Docker Commands (Development)

```bash
docker compose up -d              # Start all services
docker compose down               # Stop all services
docker compose ps                 # Check status
docker logs iacc_php --tail 50    # PHP logs
docker logs iacc_nginx --tail 50  # Nginx logs
docker exec -it iacc_mysql mysql -uroot -proot iacc  # MySQL shell
```

## cPanel Deployment Gotchas

### 1. `global $config;` in Standalone PDF Views
When a PDF view is `include`d from a controller, `$config` from `sys.configs.php` is not in scope. The fallback block creates a new config with `'mysql'` hostname which doesn't exist on cPanel.

**Fix**: Every standalone PDF view that creates its own `DbConn` must have:
```php
global $config;
if (!isset($config)) {
    $config = [ /* fallback */ ];
}
```

**Affected files**: `delivery/print.php`, `receipt/print.php`, `voucher/print.php`, any future PDF views.

### 2. Empty-String Fallbacks (`?:` not `??`)
DB fields may be empty string `''` on cPanel (not NULL). Use `trim($row['field'] ?? '') ?: 'default'` instead of `$row['field'] ?? 'default'`. This caused blank serial numbers where `company.name_sh` was `''`.

### 3. DB Hostname
- Docker: `mysql` (container name)
- cPanel: `localhost` (or env `DB_HOST`)
- `sys.configs.php` falls back to `'mysql'` if no env var set — ensure cPanel has `DB_HOST=localhost` configured

## File Structure

```
.github/workflows/
├── deploy-production.yml    # Main → Production (4-job pipeline)
├── deploy-staging.yml       # Develop → Staging
└── deploy-docker-digitalocean.yml  # Docker deployment option
deploy-cpanel.sh              # Manual packaging script
.htaccess.cpanel              # Apache security config
docker-compose.yml            # Local development
docker-compose.prod.yml       # Production Docker option
```
