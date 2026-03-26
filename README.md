# iACC - Accounting Management System

**Version**: 5.0-mvc  
**Status**: Production Ready  
**Last Updated**: March 26, 2026  
**Architecture**: MVC (Model-View-Controller)  
**PHP**: 8.2 | **MySQL**: 8.0 | **Nginx**: Alpine

## 🚀 Deployment Status

[![Deploy to Production](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-production.yml/badge.svg)](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-production.yml)
[![Deploy to Staging](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-staging.yml/badge.svg)](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-staging.yml)

| Environment | URL | Branch | Deploy |
|-------------|-----|--------|--------|
| **Production** | [iacc.f2.co.th](https://iacc.f2.co.th) | `main` | cPanel FTP via GitHub Actions |
| **Staging** | [dev.iacc.f2.co.th](https://dev.iacc.f2.co.th) | `develop` | cPanel FTP via GitHub Actions |
| **Development** | [localhost](http://localhost) | `mvc` | Docker Compose |

---

## 📊 Project Stats

| Metric | Count |
|--------|-------|
| **Controllers** | 28 |
| **Models** | 22 |
| **Views** | 67 |
| **MVC Routes** | 96 |
| **Legacy Routes** | 0 |
| **Test Cases** | 168 (42 E2E + 126 comprehensive) |
| **Active Root Files** | 24 |
| **Archived Legacy Files** | 85 |

---

## 🏗️ Architecture

### MVC Structure

```
app/
├── Config/
│   └── routes.php              # All 96 MVC routes
├── Controllers/                # 28 controllers
│   ├── BaseController.php      # Base with auth, DB, CSRF
│   ├── CategoryController.php
│   ├── BrandController.php
│   ├── CompanyController.php
│   ├── InvoiceController.php
│   ├── PurchaseOrderController.php
│   ├── PurchaseRequestController.php
│   ├── DashboardController.php
│   ├── HealthController.php    # System health endpoint
│   └── ...
└── Models/                     # 22 models
    ├── BaseModel.php           # Base with DB helpers
    ├── Category.php
    ├── Company.php
    ├── PurchaseOrder.php
    └── ...

views/                          # 67 view templates
├── category/
├── company/
├── dashboard/
├── invoice/
├── po/
└── ...
```

### Request Flow

```
Browser → index.php → routes.php → Controller → Model → View
                                        ↓
                                   BaseController
                                   (auth, DB, CSRF)
```

### Active Root PHP Files (24)

| Category | Files |
|----------|-------|
| **Core** | `index.php`, `login.php`, `authorize.php`, `menu.php`, `css.php`, `script.php` |
| **Includes** | `core-function.php`, `lang.php`, `makeoptionindex.php`, `master-data-guide.php` |
| **Print/PDF** | `inv.php`, `exp.php`, `taxiv.php`, `rec.php`, `sptinv.php` |
| **Email** | `model_mail.php`, `exp-m.php`, `inv-m.php`, `taxiv-m.php` |
| **Export** | `report-export.php`, `invoice-payments-export.php` |
| **Auth** | `forgot-password.php`, `reset-password.php`, `remoteuser.php` |

---

## 📂 Project Structure

```
iAcc-PHP-MVC/
│
├── app/                          # MVC application layer
│   ├── Config/routes.php         # Route definitions (96 MVC, 0 legacy)
│   ├── Controllers/ (28)         # Request handlers
│   └── Models/ (22)              # Business logic & data access
│
├── views/ (67)                   # View templates organized by module
│
├── inc/                          # Core includes
│   ├── sys.configs.php           # Database & app config
│   ├── class.dbconn.php          # Database connection
│   ├── class.hard.php            # Database abstraction layer
│   ├── security.php              # Auth, CSRF, RBAC, rate limiting
│   ├── error-handler.php         # Error handling
│   ├── pdf-template.php          # Shared PDF template
│   ├── string-th.xml             # Thai language strings
│   └── string-us.xml             # English language strings
│
├── ai/                           # AI chatbot system
│   ├── ai-engine.php             # Core AI engine
│   ├── ai-tools.php              # 29 AI tools
│   ├── ai-language.php           # Thai/English detection
│   └── chat-stream.php           # SSE streaming endpoint
│
├── vendor/                       # Composer dependencies
│   ├── mpdf/                     # mPDF 8.x (PDF generation)
│   └── phpmailer/                # PHPMailer 6.x (email)
│
├── css/ js/ fonts/ images/       # Frontend assets
│
├── docker/                       # Docker configuration
│   ├── nginx/default.conf        # Nginx config (security rules)
│   └── mysql/my.cnf              # MySQL config
│
├── scripts/                      # Utility scripts
│   └── generate-version.sh       # Version.json generator
│
├── tests/                        # Test suites (168 total)
│   ├── test-e2e-crud.php         # 42 E2E integration tests
│   └── test-mvc-comprehensive.php # 126 comprehensive MVC tests
│
├── legacy/                       # Archived pre-MVC files (85 files)
├── backups/                      # SQL backup files
├── logs/                         # Application logs
│
├── .github/
│   ├── copilot-instructions.md   # AI assistant context
│   └── workflows/
│       ├── deploy-production.yml # 4-job CI/CD pipeline
│       ├── deploy-staging.yml    # Staging deployment
│       └── deploy-docker-digitalocean.yml
│
├── docker-compose.yml            # Development environment
├── docker-compose.prod.yml       # Production Docker config
├── Dockerfile                    # PHP-FPM 8.2 image
├── .htaccess                     # Dev Apache config
├── .htaccess.cpanel              # Production Apache config
└── deploy-cpanel.sh              # cPanel deployment packager
```

---

## 🔐 Security

### Authentication & Authorization

| Feature | Implementation |
|---------|----------------|
| **Password Hashing** | bcrypt (cost=12) with MD5 auto-migration |
| **CSRF Protection** | Token validation on all forms |
| **Rate Limiting** | 5 attempts / 15 min per IP |
| **Account Lockout** | 10 failed attempts → 30 min lock |
| **SQL Injection Prevention** | Prepared statements + `real_escape_string` |
| **Session Security** | HttpOnly, Strict, SameSite=Lax, Secure (HTTPS) |
| **Remember Me** | Secure tokens, 30-day expiry |
| **Password Reset** | Token-based email reset (1 hour expiry) |

### User Roles

| Level | Role | Description |
|-------|------|-------------|
| 0 | User | Standard access |
| 1 | Admin | Administrative access |
| 2 | Super Admin | Full system access, bypasses RBAC |

### RBAC System

4 tables (`roles`, `permissions`, `role_permissions`, `user_roles`) with PHP enforcement:

```php
has_permission('manage_users');     // Check permission
has_role('admin');                  // Check role
require_permission('edit_invoice'); // Enforce or redirect
can('manage_settings');             // Hybrid: RBAC + user_level fallback
```

### Infrastructure Security

| Layer | Rules |
|-------|-------|
| **Nginx** | Blocks `tests/`, `legacy/`, `app/`, dotfiles, `.sql`, `.sh`, `.yml`, `.bak` |
| **Apache (.htaccess.cpanel)** | Blocks `tests/`, `legacy/`, `app/`, `inc/`, `docker/`, `logs/`, `backups/`, `cache/`, `migrations/` |
| **PHP** | `display_errors=Off` in production, env-based config, security headers |
| **Headers** | `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy` |

---

## 🏥 Health Endpoint

**URL**: `index.php?page=health`

| Access | Response |
|--------|----------|
| **Public** | `{"status": "ok", "timestamp": "..."}` |
| **Super Admin** | Full system details: PHP, DB, disk, version, routes, errors |

Used by CI/CD pipeline for post-deployment verification.

---

## ✅ Core Features

- **Company Management** — Vendors, suppliers, customers with soft delete
- **Product Catalog** — Brands, categories, types, models with pricing
- **Purchase Workflow** — PR → PO → Delivery → Invoice → Payment → Tax Invoice
- **Invoicing** — Invoice generation with PDF export
- **Tax Invoices** — Thai tax invoice support (VAT 7%)
- **Quotations** — Quote generation with PDF export
- **Payments** — Payment recording, gateway integration, tracking
- **Deliveries** — Delivery tracking with receipt confirmation
- **Reports** — Business reporting with CSV/JSON export
- **Multi-language** — Thai and English support
- **AI Chatbot** — 29 tools, OpenAI/Ollama, Thai/English, streaming
- **Dashboard** — Statistics, charts, company selector

---

## 📦 Docker Services

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| PHP-FPM 8.2 | iacc_php | 9000 | Application server |
| Nginx | iacc_nginx | 80, 443 | Web server |
| MySQL 8.0 | iacc_mysql | 3306 | Database |
| phpMyAdmin | iacc_phpmyadmin | 8083 | DB management |
| Ollama | iacc_ollama | 11434 | AI models |
| MailHog | iacc_mailhog_server | 1025, 8025 | Email testing |

### Quick Start

```bash
# Start development environment
docker compose up -d

# Verify all containers are running
docker compose ps

# View application
open http://localhost

# Run E2E tests (from inside container)
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php

# Run comprehensive MVC tests
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php

# Access MySQL CLI
docker exec -it iacc_mysql mysql -uroot -proot iacc

# Check PHP logs
docker logs iacc_php --tail 50

# Stop everything
docker compose down
```

---

## 🚀 CI/CD Pipeline

### Production Deployment (GitHub Actions)

4-job pipeline triggered on push to `main`:

```
Lint → Build → Deploy → Health Check
```

| Job | Actions |
|-----|---------|
| **Lint** | PHP 8.2 syntax check on all Controllers, Models, Views, core files. Route audit fails if any legacy routes exist. |
| **Build** | Composer install, generate `version.json`, create deploy artifact (excludes tests/legacy/docker/backups), generate production `sys.configs.php` from GitHub secrets |
| **Deploy** | FTP deploy to cPanel via `SamKirkland/FTP-Deploy-Action` |
| **Health Check** | Verify `login.php` and `health` endpoint respond after deploy |

### cPanel Manual Deployment

```bash
# Create deployment package
./deploy-cpanel.sh v5.0

# Result: build/iacc-cpanel-v5.0.zip
# Upload to cPanel → Extract → Configure sys.configs.php
```

### Required GitHub Secrets

| Secret | Purpose |
|--------|---------|
| `DB_HOST` | Production database host |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |
| `DB_NAME` | Database name |
| `FTP_SERVER` | cPanel FTP server |
| `FTP_USERNAME` | FTP username |
| `FTP_PASSWORD` | FTP password |

---

## 🔧 Configuration

### Database

**File**: `inc/sys.configs.php` (auto-generated in CI, template in `inc/sys.configs.cpanel.php`)

```php
$config["hostname"] = getenv('DB_HOST') ?: "mysql";
$config["username"] = getenv('DB_USER') ?: "root";
$config["password"] = getenv('DB_PASS') ?: "root";
$config["dbname"]   = getenv('DB_NAME') ?: "iacc";
```

### Environment Variables

See `.env.example` for all available settings.

---

## 🗃️ Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `authorize` | User authentication (email, password, level) |
| `company` | Companies/vendors/customers |
| `brand` | Product brands |
| `category` | Product categories |
| `type` | Product types |
| `model` | Product models with pricing |
| `product` | Products in orders |
| `pr` | Purchase requisitions |
| `po` | Purchase orders / quotations |
| `iv` | Invoices |
| `deliv` | Deliveries |
| `pay` | Payments |
| `credit` | Credits |

### Security Tables

| Table | Description |
|-------|-------------|
| `login_attempts` | Rate limiting tracker |
| `password_resets` | Password reset tokens |
| `remember_tokens` | Persistent login tokens |
| `roles` | RBAC roles |
| `permissions` | RBAC permissions |
| `role_permissions` | Role-permission mapping |
| `user_roles` | User-role assignments |

### Optimization

- **Foreign Keys**: 13 constraints on critical tables
- **Indexes**: 40+ custom indexes for query performance
- **Soft Delete**: 16 tables with `deleted_at` column
- **Timestamps**: 11 tables with `created_at` column

---

## 🧪 Testing

```bash
# Run all tests from inside PHP container
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php
```

| Suite | Tests | Coverage |
|-------|-------|----------|
| **E2E CRUD** | 42 | Company, Category, Type, Brand, Model, PR→PO workflow, Payment, HardClass |
| **MVC Comprehensive** | 126 | Routes, Controllers, Models, Views, Config, Security, RBAC |
| **Total** | **168** | **All passing ✅** |

---

## 📋 Changelog

### v5.0-mvc (March 26, 2026) — Full MVC Migration

**Architecture Overhaul** — Complete migration from monolithic PHP to MVC pattern:

- **Phase 1**: Upgrade PHP 8.2, Composer, mPDF 8.x, PHPMailer 6.x
- **Phase 2**: MVC foundation — BaseController, BaseModel, routing system
  - Migrated: Category, Brand, Type, Model, PaymentMethod, Company
- **Phase 3**: Business logic migration
  - Invoice, Purchase Request, Payment, Purchase Order, Voucher, Delivery, Receipt, Billing
- **Phase 4**: Admin & user features
  - Dashboard, User Management, Reports, Audit Log, Profile/Settings
- **Phase 5**: Advanced features
  - Payment Gateway, Invoice Payment, AI Admin Panel, AI Core
- **Phase 6**: 100% migration — All remaining 15 routes converted

**Post-Migration** (Tasks 1–6):
- Fix PurchaseRequest date column bug
- Controller audit (no property conflicts)
- 126 comprehensive MVC tests (168 total)
- Security hardening: CSRF on all admin forms, intval/floatval safety, security headers
- Production readiness: env-based config, display_errors off, Nginx security blocks
- Root PHP cleanup: archived 85 legacy files, 24 active remain

**DevOps** (CI/CD & Deployment):
- New 4-job CI/CD pipeline (Lint → Build → Deploy → Health Check)
- HealthController with public/admin system status
- version.json generation (CI + local script)
- .htaccess.cpanel: block tests/, legacy/, app/ directories
- deploy-cpanel.sh: updated excludes, version.json output

### v4.13 (March 24, 2026)
- Fix: PHP 8 compatibility (`each()` → `foreach()`)
- Fix: Hardcoded Docker log paths → relative paths
- Fix: Auto-create logs directory

### v4.12 (March 21, 2026)
- Fix: Receipt/Voucher PDF "data already output" error
- Fix: Non-existent `complain` table JOIN → proper `iv` table

### v4.11 (February 6, 2026)
- Critical: PO Edit products disappearing (shared `$args` array fix)
- Company checkbox handling fix
- Docker environment stability
- 42 integration tests added

### v4.10 (January 20, 2026)
- PO View, Delivery, Invoice, Tax Invoice PDF fixes
- PO Reference field support
- Customer/vendor LEFT JOIN fixes across PDFs

### v4.9 (January 19, 2026)
- Company management improvements
- Company list redesign with filter tabs
- Dashboard company selector
- Soft delete for companies

### v4.8 (January 10, 2026)
- PR form fix (nested HTML structure)
- `insertDbMax` AUTO_INCREMENT fix
- Column width expansion (pr.name, po.name)

### v4.7 (January 9, 2026)
- Developer role & menu access control
- RBAC test page

### v4.6 (January 9, 2026)
- RBAC enforcement complete (has_permission, has_role, require_permission, can)

### v4.5 (January 8, 2026)
- Delivery note workflow complete
- Invoice view redesign
- Payment recording fix
- PDF template typo fixes

### v4.4 (January 7, 2026)
- Critical: Product INSERT fix (numeric type casting)
- PO Edit page redesign
- PO View product description fix

### v4.3–v4.0 (January 5–7, 2026)
- AI chatbot (29 tools, OpenAI/Ollama, Thai/English, streaming)
- RAG enhancement, multi-language support
- UI modernization (Inter font, card layouts, gradients)
- Docker development environment
- Security: bcrypt, CSRF, rate limiting, account lockout, SQL injection prevention

### v3.x (January 4, 2026)
- Soft delete system (16 tables)
- Database optimization (40+ indexes, 13 foreign keys)
- Invoice workflow completion
- Session security improvements

---

## 🗂️ Backup & Recovery

```bash
# Create backup
docker exec iacc_mysql mysqldump -uroot -proot iacc > backup.sql

# Restore backup
docker exec -i iacc_mysql mysql -uroot -proot iacc < backup.sql

# Automated backup script
./backup.sh
```

---

## 📜 License

Proprietary — Internal Use Only
