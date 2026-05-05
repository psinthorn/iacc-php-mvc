# iACC - Accounting Management System

**Version**: 6.2-inventory-sync-worker  
**Status**: Production Ready  
**Last Updated**: May 5, 2026  
**Architecture**: MVC (Model-View-Controller) + REST API  
**PHP**: 8.2+ | **MySQL**: 5.7 | **Nginx**: Alpine

## 🚀 Deployment Status

[![Deploy to Production](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-production.yml/badge.svg)](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-production.yml)
[![Deploy to Staging](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-staging.yml/badge.svg)](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-staging.yml)

| Environment | URL | Branch | Deploy |
|-------------|-----|--------|--------|
| **Production** | [iacc.f2.co.th](https://iacc.f2.co.th) | `main` | cPanel FTP via GitHub Actions |
| **Staging** | [dev.iacc.f2.co.th](https://dev.iacc.f2.co.th) | `develop` | cPanel FTP via GitHub Actions |
| **Development** | [localhost](http://localhost) | `feature/*` | Docker Compose |

---

## 📊 Project Stats

| Metric | Count |
|--------|-------|
| **Controllers** | 38 (+1 Q3) |
| **Models** | 32 (+2 Q3) |
| **Views** | 117 (+2 reports) |
| **Services** | 4 (ChannelService, PromptPayService, CurrencyService, CompanySeeder) |
| **MVC Routes** | 175 (+2 reports) |
| **Legacy Routes** | 0 |
| **Test Cases** | 192 (42 E2E + 20 API + 126 MVC + 4 Split Invoice) |
| **Active Root Files** | 12 |
| **Archived Legacy Files** | 95 |

---

## 🏗️ Architecture

### MVC + API Structure

```
app/
├── Config/
│   └── routes.php                 # 150 MVC routes (public, standalone, normal)
├── Controllers/ (37)
│   ├── BaseController.php         # Base with auth, DB, CSRF
│   ├── ChannelApiController.php   # Sales Channel REST API
│   ├── AdminApiController.php     # API admin panel
│   ├── DashboardController.php
│   ├── CompanyController.php
│   ├── PurchaseOrderController.php
│   ├── InvoiceController.php
│   ├── TaxReportController.php    # Thai tax reports (PP30/WHT)
│   ├── CurrencyController.php     # Multi-currency management
│   ├── SlipReviewController.php   # Payment slip review workflow
│   ├── InvoicePaymentController.php # PromptPay checkout flow
│   ├── PaymentGatewayController.php # Gateway configuration
│   ├── ExpenseController.php      # Expense tracking & categories
│   ├── HealthController.php       # System health endpoint
│   ├── AuthController.php         # Login/logout/forgot password
│   └── ...
├── Models/ (30)
│   ├── BaseModel.php              # Base with DB helpers
│   ├── Currency.php               # Currency CRUD & exchange rates
│   ├── TaxReport.php              # VAT/WHT report generation
│   ├── SlipReview.php             # Slip approval/rejection
│   ├── InvoicePayment.php         # Invoice payment processing
│   ├── Expense.php                # Expense CRUD & reporting
│   ├── ExpenseCategory.php        # Expense category management
│   ├── ApiKey.php                 # API key management
│   ├── ChannelOrder.php           # Channel order processing
│   └── ...
├── Services/ (4)
│   ├── ChannelService.php         # Business logic for channel API
│   ├── PromptPayService.php       # QR code generation & payment
│   ├── CurrencyService.php        # Exchange rates (BOT API)
│   └── CompanySeeder.php          # Default master data for new companies
└── Views/ (109)
    ├── expense/                   # 6 expense views (list, form, view, categories, summary, project-report)
    ├── report/                    # 2 report views (hub, ar-aging)
    ├── api/                       # 11 API admin panel views
    ├── tax/                       # 3 tax report views (dashboard, PP30, WHT)
    ├── currency/                  # 2 currency views (list, rates)
    ├── slip-review/               # 1 slip review admin view
    ├── invoice-payment/           # 3 checkout flow views
    ├── payment-gateway/           # 2 gateway config views
    ├── auth/                      # Login, forgot/reset password
    ├── dashboard/                 # Dashboard views
    ├── company/                   # Company management
    ├── po/                        # Purchase orders
    ├── invoice/                   # Invoicing
    ├── pdf/                       # PDF templates
    ├── layouts/                   # head, sidebar, scripts
    └── ...
```

### Request Flow

```
Browser → index.php → routes.php → Controller → Model → View
                                        ↓
                                   BaseController
                                   (auth, DB, CSRF)

API Client → api.php → ChannelApiController → ChannelService → JSON Response
                              ↓
                         API Key Auth
                     Rate Limiting (60/min)
                        Idempotency
```

### Active Root PHP Files (15)

| Category | Files |
|----------|-------|
| **Core** | `index.php`, `login.php`, `api.php` |
| **Public Pages** | `landing.php`, `about.php`, `contact.php`, `roadmap.php`, `blog.php`, `press.php`, `careers.php` |
| **Developer Pages** | `api-docs.php`, `template-demo.php`, `template-howto.php` |
| **Legal** | `privacy.php`, `terms.php` |

> All admin/business pages routed through `index.php` via MVC controllers. Legacy files archived to `legacy/` (95 files).

---

## 🔌 Sales Channel API

Full REST API for external integrations (OTA, PMS, channel managers).

### API Features (5 Phases)

| Phase | Feature | Status |
|-------|---------|--------|
| 1 | REST API — CRUD, API key auth, endpoints | ✅ Complete |
| 2 | Rate limiting (60/min), key rotation, idempotency | ✅ Complete |
| 3 | Webhooks, API docs page, order detail view | ✅ Complete |
| 4 | Order management UI — approve/reject/cancel/retry | ✅ Complete |
| 5 | Export (CSV/JSON), notifications, webhook API, invoices | ✅ Complete |

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api.php/orders` | List orders (paginated, filterable) |
| `GET` | `/api.php/orders/{id}` | Get order details |
| `POST` | `/api.php/orders` | Create new order |
| `PUT` | `/api.php/orders/{id}` | Update order |
| `PUT` | `/api.php/orders/{id}/retry` | Retry failed order |
| `DELETE` | `/api.php/orders/{id}` | Cancel order |
| `GET` | `/api.php/webhooks` | List webhooks |
| `POST` | `/api.php/webhooks` | Create webhook |
| `DELETE` | `/api.php/webhooks/{id}` | Delete webhook |

### API Security

- **Authentication**: API key via `X-API-Key` header
- **Rate Limiting**: 60 requests/minute per key
- **Idempotency**: `Idempotency-Key` header prevents duplicate orders
- **Key Rotation**: Seamless key rotation with grace period
- **Webhook Signing**: HMAC-SHA256 payload signatures

### Admin Panel

| Page | Route | Description |
|------|-------|-------------|
| API Keys | `?page=api_keys` | Manage API keys (create/revoke/rotate) |
| Orders | `?page=api_orders` | View & manage channel orders |
| Webhooks | `?page=api_webhooks` | Configure webhook endpoints |
| Usage Logs | `?page=api_usage` | Monitor API usage & rate limits |
| Settings | `?page=api_settings` | API configuration & plans |
| Docs | `?page=api_docs` | Interactive API documentation |
| Export | `?page=api_export` | Export orders (CSV/JSON) |

---

## 🌐 Website Templates

Self-hosted website templates powered by iACC Sales Channel API.

### Tour Company Demo Template

A complete tour booking website that syncs products from iACC and accepts bookings via API.

| File | Purpose |
|------|---------|
| `index.php` | Public homepage — product grid, category filter, booking modal |
| `setup.php` | 3-step setup wizard — API credentials, product sync, site config + admin password |
| `admin-login.php` | Admin login page — bcrypt authentication, session-based |
| `admin.php` | Admin panel — 4 tabs: Products, API Settings, Sync, Bookings |
| `sync.php` | Quick product sync endpoint |
| `book.php` | Booking handler — creates orders via iACC API |
| `config.php` | Auto-generated configuration (API keys, admin hash, theme) |
| `includes/api-client.php` | iACC API client (products, categories, orders, subscription) |
| `includes/database.php` | SQLite database layer (products, categories, bookings, sync) |

### Admin Panel Features

| Tab | Description |
|-----|-------------|
| **Products** | Toggle enable/disable per product, filter by category/status |
| **API Settings** | Update API URL/Key/Secret, test connection, view plan info |
| **Sync** | Pull latest products from iACC API, preserves active/inactive states |
| **Bookings** | View recent bookings with guest details, order status |

### Admin Authentication

| Setting | Value |
|---------|-------|
| **Default Username** | `admin` |
| **Default Password** | `admin123` |
| **Password Storage** | bcrypt hash in `config.php` |
| **Session Key** | `template_admin_logged_in` |
| **Change Password** | Re-run Setup Wizard → Step 3 (Admin Login section) |

### Developer Pages (Public)

| Page | Route | Description |
|------|-------|-------------|
| API Documentation | `api-docs.php` | Full REST API reference with examples |
| Template Setup Demo | `template-demo.php` | 6-step visual walkthrough of setup process |
| Hosting Guide | `template-howto.php` | cPanel/VPS/Docker installation guide |

---

## 📂 Project Structure

```
iAcc-PHP-MVC/
│
├── app/                          # MVC application layer
│   ├── Config/routes.php         # Route definitions (150 MVC, 0 legacy)
│   ├── Controllers/ (38)         # Request handlers
│   ├── Models/ (32)              # Business logic & data access
│   ├── Services/ (3)             # Business services
│   └── Views/ (114)              # View templates organized by module
│
├── inc/                          # Core includes
│   ├── sys.configs.php           # Database & app config
│   ├── class.dbconn.php          # Database connection (5s timeout)
│   ├── class.hard.php            # Database abstraction layer
│   ├── security.php              # Auth, CSRF, RBAC, rate limiting
│   ├── error-handler.php         # Error handling
│   ├── pagination.php            # Pagination helper
│   ├── pdf-template.php          # Shared PDF template
│   ├── lang/en.php, th.php       # Language files
│   ├── string-th.xml             # Thai language strings (515 keys)
│   └── string-us.xml             # English language strings (515 keys)
│
├── ai/                           # AI chatbot system
│   ├── ai-engine.php             # Core AI engine
│   ├── ai-tools.php              # 29 AI tools
│   ├── ai-language.php           # Thai/English detection
│   └── chat-stream.php           # SSE streaming endpoint
│
├── vendor/                       # Composer dependencies (PSR-4 autoloading)
│   ├── mpdf/                     # mPDF 8.x (PDF generation)
│   └── phpmailer/                # PHPMailer 6.x (email)
│
├── css/ js/ fonts/ images/       # Frontend assets
│
├── docker/                       # Docker configuration
│   ├── nginx/default.conf        # Nginx config (security rules)
│   └── mysql/my.cnf              # MySQL config
│
├── tests/                        # Test suites (188 total)
│   ├── test-e2e-crud.php         # 42 E2E integration tests
│   ├── test-api-phase3.php       # 20 Sales Channel API tests
│   └── test-mvc-comprehensive.php # 126 comprehensive MVC tests
│
├── templates/                    # Self-hosted website templates
│   └── tour-company-demo/        # Tour booking template (API-powered)
│       ├── admin.php             # Admin panel (4 tabs, auth-protected)
│       ├── admin-login.php       # Admin login (bcrypt, session)
│       ├── setup.php             # 3-step setup wizard
│       ├── index.php             # Public homepage
│       ├── book.php              # Booking handler
│       ├── sync.php              # Product sync
│       └── includes/             # API client, SQLite database
│
├── legacy/                       # Archived pre-MVC files (95 files)
├── backups/                      # SQL backup files
├── logs/                         # Application logs
│
├── docs/
│   └── phase2/                   # Phase 2 DB hardening plans (7 docs)
│
├── migrations/                    # 20 SQL migrations (001-020) organized by phase
│   ├── scripts/                   # Migration runner scripts
│   └── README.md                  # Migration index & documentation
│
├── .github/
│   ├── copilot-instructions.md   # AI assistant context
│   ├── skills/                   # 6 Copilot skill definitions
│   └── workflows/
│       ├── deploy-production.yml # 4-job CI/CD pipeline
│       ├── deploy-staging.yml    # Staging deploy (with Composer)
│       └── deploy-docker-digitalocean.yml
│
├── docker-compose.yml            # Development environment
├── docker-compose.prod.yml       # Production Docker config
├── Dockerfile                    # PHP-FPM 8.2 image
├── composer.json                 # PSR-4 autoloading + dependencies
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
| **Rate Limiting** | 5 attempts / 15 min per IP (login), 60/min (API) |
| **Account Lockout** | 10 failed attempts → 30 min lock |
| **SQL Injection Prevention** | Prepared statements + `real_escape_string` |
| **Session Security** | HttpOnly, Strict, SameSite=Lax, Secure (HTTPS) |
| **Remember Me** | Secure tokens, 30-day expiry |
| **Password Reset** | Token-based email reset (1 hour expiry) |
| **API Key Auth** | SHA-256 hashed keys with rate limiting |

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

- **Self-Registration** — Email verification, auto company creation, trial activation, default data seeding
- **Company Management** — Vendors, suppliers, customers with soft delete
- **Product Catalog** — Brands, categories, types, models with pricing
- **Purchase Workflow** — PR → PO → Delivery → Invoice → Payment → Tax Invoice
- **Invoicing** — Invoice generation with PDF export, split invoices (material/labour WHT separation)
- **Tax Invoices** — Thai tax invoice support (VAT 7%)
- **Quotations** — Quote generation with PDF export
- **Payments** — Payment recording, gateway integration, tracking
- **Deliveries** — Delivery tracking with receipt confirmation
- **Reports** — Reports Hub, AR Aging analysis, business reporting with CSV/JSON export
- **Expense Tracking** — Expense CRUD with categories, VAT/WHT, approval workflow, monthly summary
- **Sales Channel API** — REST API for OTA/PMS/channel manager integrations
- **Payment Gateway** — PromptPay QR, slip upload & admin review workflow
- **Multi-Currency** — 10 currencies, BOT exchange rates, toggle activation
- **Thai Tax Reports** — PP30 (VAT Return), ภ.ง.ด.3/53 (WHT), CSV export, save/file
- **Multi-language** — Thai and English support (515 translation keys, XML-based)
- **Help System** — In-app user manual, master data guide, developer summary (bilingual)
- **AI Chatbot** — 29 tools, OpenAI/Ollama, Thai/English, streaming
- **Dashboard** — KPI cards, Chart.js charts (revenue/expenses, payment status, order status), company selector

---

## 📦 Docker Services

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| PHP-FPM 8.2 | iacc_php | 9000 | Application server |
| Nginx | iacc_nginx | 80, 443 | Web server |
| MySQL 5.7 | iacc_mysql | 3306 | Database |
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

# Run E2E tests
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php

# Run API tests
docker exec iacc_php php /var/www/html/tests/test-api-phase3.php

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

### Staging Deployment (GitHub Actions)

Triggered on push to `develop`:

```
PHP Syntax Check → Composer Install → Build Artifact → FTP Deploy
```

| Step | Details |
|------|---------|
| **Syntax Check** | Core files + all Controllers, Models, Services, Views |
| **Composer** | `composer install --no-dev --optimize-autoloader` |
| **Config** | Generate `sys.configs.php` from GitHub Secrets |
| **Deploy** | FTP to cPanel staging via `SamKirkland/FTP-Deploy-Action` |

### Production Deployment (GitHub Actions)

4-job pipeline triggered on push to `main`:

```
Lint → Build → Deploy → Health Check
```

| Job | Actions |
|-----|---------|
| **Lint** | PHP 8.2 syntax check on all MVC files. Route audit fails if any legacy routes exist. |
| **Build** | Composer install, generate `version.json`, create deploy artifact, generate production `sys.configs.php` |
| **Deploy** | FTP deploy to cPanel production |
| **Health Check** | Verify `login.php` and `health` endpoint respond |

### Required GitHub Secrets

| Secret | Purpose |
|--------|---------|
| `DB_HOST` / `DB_HOST_STAGING` | Database host |
| `DB_USERNAME` / `DB_USERNAME_STAGING` | Database username |
| `DB_PASSWORD` / `DB_PASSWORD_STAGING` | Database password |
| `DB_NAME` / `DB_NAME_STAGING` | Database name |
| `FTP_SERVER` | cPanel FTP server |
| `FTP_USERNAME` / `FTP_USERNAME_STAGING` | FTP username |
| `FTP_PASSWORD` / `FTP_PASSWORD_STAGING` | FTP password |

---

## 🔧 Configuration

### Database

**File**: `inc/sys.configs.php` (auto-generated in CI from GitHub Secrets)

```php
$config["hostname"] = getenv('DB_HOST') ?: "mysql";
$config["username"] = getenv('DB_USER') ?: "root";
$config["password"] = getenv('DB_PASS') ?: "root";
$config["dbname"]   = getenv('DB_NAME') ?: "iacc";
```

### Connection Resilience

- **Timeout**: 5-second connection timeout (prevents page hang on unreachable DB)
- **Fast-path landing**: Anonymous visitors see landing page without DB connection
- **Error handling**: Custom error handler logs to `logs/error.log`

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

### API Tables

| Table | Description |
|-------|-------------|
| `api_keys` | API key management (hashed, rate limits, plans) |
| `channel_orders` | Orders from external channels |
| `channel_order_items` | Line items for channel orders |
| `webhooks` | Webhook endpoint configurations |
| `webhook_deliveries` | Webhook delivery logs |
| `api_usage_logs` | API request logs (auth, rate limiting) |
| `api_invoices` | Invoices generated from API orders |
| `api_notifications` | API notification queue |
| `idempotency_keys` | Idempotency key storage |

### Payment & Financial Tables (Q2 2026)

| Table | Description |
|-------|-------------|
| `currencies` | Supported currencies (THB, USD, EUR, etc.) |
| `exchange_rates` | Historical exchange rates (BOT API) |
| `tax_reports` | Saved tax reports (PP30, PND3, PND53) |
| `payment_gateway_config` | Gateway settings per company |
| `payment_log` | Payment transaction logs |
| `payment_method` | Payment method registry (PromptPay, etc.) |

### Expense Tables (Q3 2026)

| Table | Description |
|-------|-------------|
| `expense_categories` | Expense category definitions (10 seeded per company, bilingual EN/TH) |
| `expenses` | Expense records with VAT/WHT, approval workflow, receipt upload |
| `chart_of_accounts` | Chart of accounts (20 seeded per company, Thai accounting standard) |

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

| `email_verifications` | Self-registration email verification tokens |
| `api_subscriptions` | SaaS subscription plans & trials (14-day free) |

### Registration & Onboarding

| Table | Description |
|-------|-------------|
| `email_verifications` | Pending registrations with verification tokens |
| `api_subscriptions` | Trial/paid subscription tracking |

### Optimization

- **Foreign Keys**: 13 constraints on critical tables
- **Indexes**: 40+ custom indexes for query performance
- **Soft Delete**: 16 tables with `deleted_at` column
- **Timestamps**: All 59 tables with `created_at` and `updated_at`

---

## 🧪 Testing

```bash
# Run all tests from inside PHP container
docker exec iacc_php php /var/www/html/tests/test-e2e-crud.php
docker exec iacc_php php /var/www/html/tests/test-api-phase3.php
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php
```

| Suite | Tests | Coverage |
|-------|-------|----------|
| **E2E CRUD** | 42 | Company, Category, Type, Brand, Model, PR→PO workflow, Payment, HardClass |
| **Sales Channel API** | 20 | API auth, CRUD orders, rate limiting, webhooks, key rotation, idempotency |
| **MVC Comprehensive** | 126 | Routes, Controllers, Models, Views, Config, Security, RBAC |
| **Split Invoice** | 4 | Split PDF logic, labour/material detection, quotation reference |
| **Total** | **192** | **All passing ✅** |

---

## �️ Planned Roadmap (v6.0 – v6.7)

| # | Version | Feature | Quarter | GitHub Status | Dependencies |
|---|---------|---------|---------|---------------|--------------|
| 1 | **v6.0** | Self-Registration → Trial → Payment | Q4 2026 | [milestone open](https://github.com/psinthorn/iacc-php-mvc/milestone/12) — 4 closed / 5 open | None |
| 2 | **v6.1** | Task Queue & Background Worker Infrastructure | Q2 2026 | ✅ **shipped 2026-05-04** ([milestone v6.1](https://github.com/psinthorn/iacc-php-mvc/milestone/14)) — 4 issues closed via [PR #94](https://github.com/psinthorn/iacc-php-mvc/pull/94) | v6.0 |
| 3 | **v6.2** | AI-Powered Sales Channel Automation | Q1 2027 | 🚧 **in progress** — 2 / 6 shipped ([milestone v6.2](https://github.com/psinthorn/iacc-php-mvc/milestone/15)). #83 Channel Health + #82 Inventory Sync live 2026-05-05 ([PR #100](https://github.com/psinthorn/iacc-php-mvc/pull/100), [PR #105](https://github.com/psinthorn/iacc-php-mvc/pull/105)) | v6.1 + Sales Channel API |
| 4 | **v6.3** | Agent Automation Workers | Q1 2027 | [milestone open](https://github.com/psinthorn/iacc-php-mvc/milestone/16) — 8 skeleton issues filed | v6.1 |
| 5 | **v6.4** | AI Document Processing (OCR) | Q2 2027 | _no milestone yet — text-only roadmap_ | v6.1 |
| 6 | **v6.5** | Conversational BI & Smart Insights | Q2 2027 | _no milestone yet — text-only roadmap_ | Existing AI (29 tools) |
| 7 | **v6.6** | Native Sales Channel Connectors | Q3 2027 | _no milestone yet — text-only roadmap_ | v6.2 |
| 8 | **v6.7** | Multi-LLM Router & AI Platform Strategy | Q3 2027 | _no milestone yet — text-only roadmap_ | v6.5 |

> **Roadmap-vs-issues policy:** v6.0 – v6.3 are **active milestones** with traceable issues. v6.4 – v6.7 are **directional intent** — they get a milestone + issues only when sized into a quarter. Tracker agent reconciles this section against `gh api .../milestones` on every merge to `main` (see [`ai/prompts/agent-tracker.md`](ai/prompts/agent-tracker.md) Version Sync section).

### v6.0 — Self-Registration → Trial → Payment

📋 **GitHub:** [milestone v6.0](https://github.com/psinthorn/iacc-php-mvc/milestone/?q=v6.0) · **Shipped:** [#36](https://github.com/psinthorn/iacc-php-mvc/issues/36) · [#53](https://github.com/psinthorn/iacc-php-mvc/issues/53) · [#54](https://github.com/psinthorn/iacc-php-mvc/issues/54) · [#55](https://github.com/psinthorn/iacc-php-mvc/issues/55) · **Open:** [#32](https://github.com/psinthorn/iacc-php-mvc/issues/32) (mobile app) · [#33](https://github.com/psinthorn/iacc-php-mvc/issues/33) (push notifs) · [#34](https://github.com/psinthorn/iacc-php-mvc/issues/34) (bank reconciliation) · [#35](https://github.com/psinthorn/iacc-php-mvc/issues/35) (e-commerce integrations) · [#37](https://github.com/psinthorn/iacc-php-mvc/issues/37) (OCR receipt scanning)

- Public signup form with email verification (MailHog in dev) — _shipped #53_
- Auto company/user creation on registration — _shipped #53_
- 14-day trial with 50 order limit (website channel only) — _shipped #54_
- Plan comparison page with upgrade flow — _shipped #55_
- PromptPay/bank transfer payment integration — _shipped #55_
- Public Developer API + docs portal — _shipped #36_
- New user onboarding wizard — _open (no issue yet — file before next sprint)_

### v6.1 — Task Queue & Background Worker Infrastructure ✅ shipped 2026-05-04

📋 **GitHub:** [milestone v6.1](https://github.com/psinthorn/iacc-php-mvc/milestone/14) — all 4 issues closed via [PR #94](https://github.com/psinthorn/iacc-php-mvc/pull/94)

- [#75](https://github.com/psinthorn/iacc-php-mvc/issues/75) `task_queue` + `task_results` database schema — _shipped_
- [#76](https://github.com/psinthorn/iacc-php-mvc/issues/76) `worker.php` cron script with poll/lock/retry — _shipped_
- [#77](https://github.com/psinthorn/iacc-php-mvc/issues/77) Dead-letter queue + priority lanes — _shipped_
- [#78](https://github.com/psinthorn/iacc-php-mvc/issues/78) Admin queue dashboard (view, retry, clear) — _shipped_

**Hotfix shipped in same release:** [PR #93](https://github.com/psinthorn/iacc-php-mvc/pull/93) — pin MySQL session timezone to `+07:00` so `NOW()` agrees with PHP `date()` regardless of cPanel host's default. Forward-only.

**Operational notes:** Cron contract is `cron.php?task=run_worker&token=$CRON_TOKEN_PRODUCTION` every minute. cPanel job uses `/usr/bin/curl` (NOT `/usr/local/bin/curl` — host-specific path). Worker processes ≤ 1 task per tick, ceiling ≈ 1,440/day per server.

### v6.2 — AI-Powered Sales Channel Automation 🚧 2 of 6 shipped

📋 **GitHub:** [milestone v6.2](https://github.com/psinthorn/iacc-php-mvc/milestone/15) · sprint-1 in progress per [Option B](https://github.com/psinthorn/iacc-php-mvc/pull/100) (LINE-only MVP for AI features; #81 + #84 deferred to v6.3)

- [#79](https://github.com/psinthorn/iacc-php-mvc/issues/79) **AI Order Parser** — LINE/Facebook/email → structured `channel_orders` — _next sprint_
- [#80](https://github.com/psinthorn/iacc-php-mvc/issues/80) **Smart Order Router** — classify orders by channel + content — _next sprint_
- [#81](https://github.com/psinthorn/iacc-php-mvc/issues/81) **AI Price Optimizer** — dynamic pricing per channel (weekend/season/margin) — _deferred to v6.3_
- [#82](https://github.com/psinthorn/iacc-php-mvc/issues/82) **Inventory Sync Worker** — iACC products ↔ external channels — _shipped 2026-05-05 ([PR #105](https://github.com/psinthorn/iacc-php-mvc/pull/105))_
- [#83](https://github.com/psinthorn/iacc-php-mvc/issues/83) **Channel Health Monitor** — API health checks + webhook delivery alerts — _shipped 2026-05-05 ([PR #100](https://github.com/psinthorn/iacc-php-mvc/pull/100))_
- [#84](https://github.com/psinthorn/iacc-php-mvc/issues/84) **AI Response Generator** — auto-reply with catalog + availability — _deferred to v6.3_

**#83 — what shipped:** Periodic heartbeat (every minute via v6.1 task queue) probing 4 channels per tenant — LINE OA (`/v2/bot/info`), Sales Channel API (loopback), outbound webhook (passive telemetry from `api_webhook_deliveries`), email SMTP (TCP probe). Alerts open after 5 consecutive failures, auto-resolve on first success, bilingual TH/EN email notifications to admins (capped at 2 per downtime episode). Admin dashboard at `/?page=channel_health` with status grid, 24h response chart (Chart.js), open-alerts panel, last-100 timeline. New tables: `channel_health_log` (BIGINT PK, 30-day retention) + `channel_alerts` (state machine).

**#82 — what shipped:** Real-time outbound inventory broadcast. Whenever `tour_allotments` changes via the model layer (create / book / release / set-capacity / close / reopen — 6 hook points), enqueues a `sync_inventory_change` task on the v6.1 task queue. Handler loads the row, resolves the final event type (auto-upgrades `updated` → `depleted` when booked >= total, → `closed` when soft-deleted or `is_closed=1`), and dispatches HMAC-signed POSTs via existing `Webhook::fireEvent()` chain. Event types: `allotment.created` / `allotment.updated` / `allotment.depleted` / `allotment.closed` / `allotment.snapshot`. Admin "Send snapshot" button on the webhooks admin page enqueues backfill events for all active allotments — useful when a partner subscribes mid-month and needs to seed their inventory cache. **Zero new tables, zero migrations** — reuses existing `api_webhooks` + `api_webhook_deliveries`.

### v6.3 — Agent Automation Workers

📋 **GitHub:** [milestone v6.3](https://github.com/psinthorn/iacc-php-mvc/milestone/16) — 8 skeleton issues filed

- [#85](https://github.com/psinthorn/iacc-php-mvc/issues/85) Overdue invoice reminders (daily 9am)
- [#86](https://github.com/psinthorn/iacc-php-mvc/issues/86) Trial expiry notifier (3/1/0 days before expiry)
- [#87](https://github.com/psinthorn/iacc-php-mvc/issues/87) Auto subscription renewal/suspension
- [#88](https://github.com/psinthorn/iacc-php-mvc/issues/88) Weekly AR Aging alert to admin
- [#89](https://github.com/psinthorn/iacc-php-mvc/issues/89) Monthly auto-generated reports (P&L, Revenue) as PDF
- [#90](https://github.com/psinthorn/iacc-php-mvc/issues/90) Webhook retry worker with exponential backoff
- [#91](https://github.com/psinthorn/iacc-php-mvc/issues/91) BOT exchange rate updater (daily)
- [#92](https://github.com/psinthorn/iacc-php-mvc/issues/92) Data cleanup worker (weekly — old task_results, expired sessions, orphaned uploads)

### v6.4 — AI Document Processing (OCR)

📋 **GitHub:** _no milestone yet_ — directional roadmap only. Create milestone before issue tracking.

- Receipt photo upload → AI extracts vendor, amount, date, category → auto-create expense
- Invoice email parser → AI parses PDF/image → creates expense or PO draft
- Contract analyzer → extracts key terms, dates, obligations
- Thai + English language support for document parsing

### v6.5 — Conversational BI & Smart Insights

📋 **GitHub:** _no milestone yet_ — directional roadmap only.

- Chart generation from AI chat ("Show revenue trend this year" → inline Chart.js)
- Predictive cash flow: AI forecasts 30/60/90 day cash position
- Transaction anomaly detection (amount outliers, duplicates, missing receipts)
- Smart automation suggestions based on user behavior patterns
- Natural language → SQL query execution

### v6.6 — Native Sales Channel Connectors

📋 **GitHub:** _no milestone yet_ — directional roadmap only.

- LINE Official Account (Messaging API — receive bookings, AI auto-reply, push status updates)
- Facebook Messenger (Graph API — inquiry bot, auto-quote generation)
- Shopee Open Platform (product sync, order import, inventory update, price sync)
- Lazada Open Platform (unified e-commerce adapter with Shopee)
- TikTok Shop API (product listing, order fulfillment sync)
- Instagram Shopping (Graph API — catalog sync, DM inquiry handling)
- WhatsApp Business (Cloud API — order confirmation, delivery notifications)

### v6.7 — Multi-LLM Router & AI Platform Strategy

📋 **GitHub:** _no milestone yet_ — directional roadmap only.

- Smart Model Selector: route tasks to optimal provider (Ollama for simple, OpenAI for OCR, Claude for analysis)
- Cost Optimizer: track token usage per provider → auto-route to cheapest model meeting quality threshold
- Failover Chain: Ollama → OpenAI → Claude for high availability
- AI Usage Dashboard: per-company usage, cost breakdown, model performance metrics

---

## �📋 Changelog

### v5.12-tour-booking-suite (April 29, 2026) — Tour Booking Payment + Bulk Actions + AI Agent Team + Email/KPI/CSV

**Milestone:** [v5.12 — Tour Booking Suite](https://github.com/psinthorn/iacc-php-mvc/milestone/13) — 9 issues, all shipped (closed)

**Core suite (#44–#49):**

- **Tour Booking Payment System** ([#44](https://github.com/psinthorn/iacc-php-mvc/issues/44)): record payment, slip approve/reject, refund flow with full status state machine (pending → paid → refunded / no_show)
- **Customer Payment Links + Gateway Toggle** ([#45](https://github.com/psinthorn/iacc-php-mvc/issues/45)): public payment URLs (signed-token) + per-company gateway on/off
- **Reusable Multi-Row Bulk Selection** ([#46](https://github.com/psinthorn/iacc-php-mvc/issues/46)): shared component for all list pages (select-all, range-shift, count badge)
- **6 Bulk Actions for Tour Bookings** ([#47](https://github.com/psinthorn/iacc-php-mvc/issues/47)): Confirm, Mark Payment, Vouchers, Invoices, CSV export, Delete (capped at 500/batch)
- **AI Agent Team — 9 specialised agents** ([#48](https://github.com/psinthorn/iacc-php-mvc/issues/48)): PM, Backend, Frontend, QA, DevOps, Designer, Marketing, Support, Tracker — orchestrated via [`CLAUDE.md`](CLAUDE.md). Cleaner agent ([#74](https://github.com/psinthorn/iacc-php-mvc/issues/74)) added next minor.
- **Dynamic Page Titles** ([#49](https://github.com/psinthorn/iacc-php-mvc/issues/49)): all 125 view files now show contextual `<title>` tags

**Operational tooling (#50–#52)** — verified shipped in PM audit on 2026-04-30:

- **Email SMTP delivery** ([#50](https://github.com/psinthorn/iacc-php-mvc/issues/50)): per-tenant SMTP config in [`smtp_settings`](database/migrations/email_smtp_settings.sql) table + [`SmtpSettingsController`](app/Controllers/SmtpSettingsController.php) UI + [`EmailService::sendVoucher/sendInvoiceNotification`](app/Services/EmailService.php) wired into [`BulkActionController`](app/Controllers/BulkActionController.php#L288) (STARTTLS + SSL + PHP `mail()` fallback chain)
- **Tour Booking KPI dashboard** ([#51](https://github.com/psinthorn/iacc-php-mvc/issues/51)): Revenue (฿) · Total Pax · Total Bookings cards + Top Agents table at [`/index.php?page=tour_report`](app/Views/tour-report/index.php#L112-L152), powered by `TourBooking::getKpiByRange()`
- **Bulk CSV booking import** ([#52](https://github.com/psinthorn/iacc-php-mvc/issues/52)): 3-step wizard ([upload](app/Views/tour-booking/csv-import.php) → [preview](app/Views/tour-booking/csv-preview.php) → [done](app/Views/tour-booking/csv-import-done.php)) at [`TourBookingController::csvImport/csvPreview`](app/Controllers/TourBookingController.php#L639)

> **Note:** #50, #51, #52 were originally tracked as deferred from v5.12 → v5.13. PM audit on 2026-04-30 found all three already shipped in v5.12 work; v5.13 milestone closed empty as a result.

**Files changed:** 200+ across `app/Controllers/Tour*.php`, `app/Models/TourBooking*.php`, `app/Views/tour-booking/`, `app/Views/tour-report/`, `app/Views/smtp-settings/`, `app/Services/EmailService.php`, `ai/prompts/`, `database/migrations/2026_04_*`, `database/migrations/email_smtp_settings.sql` | **PRs merged:** #57, #66, e67a1a5

### v5.11-demo-data (April 2, 2026) — Demo Data & Company Seeder

**CompanySeeder Service** — Auto-seeds default master data when a new company registers:

- **10 Expense Categories**: Office Rent, Utilities, Office Supplies, Travel & Transport, Salary & Wages, Marketing & Advertising, Professional Fees, Equipment & Maintenance, Insurance, Miscellaneous — all bilingual (EN/TH) with icons and colors
- **4 Payment Methods**: Cash, Bank Transfer, Credit Card, Cheque — bilingual with Font Awesome icons
- **20 Chart of Accounts**: Standard Thai accounting structure — Assets (5), Liabilities (4), Equity (2), Revenue (3), Expenses (6) — bilingual EN/TH
- **Integration**: Auto-called during self-registration (Registration::createAccount) so new companies start with useful data instead of empty screens
- **Help Docs Updated**: Master Data Guide now includes "Pre-loaded Default Data" section with full reference tables; User Manual updated with "Good news!" callout in Initial Setup chapter

**Demo Seed** (`database/seeds/demo_3_companies.sql`):

- **3 Demo Companies**: Alpha Tech Solutions (IT), Beta Supply Co. (hardware), Gamma Design Studio (creative agency)
- **7 User Accounts**: Admin + staff accounts per company (password: `demo1234`)
- **Cross-Company Data**: 6 vendor/customer cross-references, 8 PRs, 6 POs, 13 products, 3 invoices, 1 receipt
- **Financial Data**: 23 expense categories, 16 expenses (with VAT/WHT), 34 chart of accounts, 4 journal vouchers with 8 journal entries
- **Idempotent**: Safe to re-run — cleanup block at top removes previous demo data

**LINE OA Sales Channel Integration** (feature/line-oa-sales-channel):

- **SalesAgentService**: AI-powered sales agent for LINE OA conversations
- **LineChannelAdapter**: LINE Messaging API adapter for webhook processing
- **AI Settings UI**: Admin page for configuring AI providers and models
- **Migration 008**: LINE OA channel and AI agent database tables

### v5.10-i18n-complete (March 30, 2026) — Complete Multi-Language Support

**i18n Translation System** — Expanded XML translation files from ~110 to 515 keys covering all application views:

- **515 Translation Keys**: Complete EN/TH translations for all modules — payments (40+ keys), billing, receipts, vouchers, accounting, expenses, AI tools, sales channel API, user settings, admin panel, reports
- **Hardcoded String Removal**: Replaced hardcoded English text in user/list.php, dashboard/index.php, company views, and sidebar dropdown with `$xml->` translation references
- **Language Switching**: Sidebar EN/TH buttons → session-based language toggle → XML file loader → all views render in selected language
- **Two i18n Systems**: XML-based (`string-us.xml` / `string-th.xml`) for main app, PHP array-based (`inc/lang/en.php` / `inc/lang/th.php`) for public landing pages

**Infrastructure Cleanup**:
- Organized 20 migration files into sequential numbering (001-020) grouped by phase
- Moved migration shell scripts to `migrations/scripts/` subdirectory
- Aligned MySQL version to 5.7 across all environments (docker-compose, docs)

### v5.9-split-invoice-wht (March 30, 2026) — Split Invoice & WHT Separation

**Split Invoice System** — Quotations with labour charges automatically split into separate material and labour invoices for WHT (withholding tax) separation:

- **Automatic Split Detection**: Quotations with `activelabour=1 AND valuelabour > 0` on any product trigger invoice splitting
- **Material Invoice**: Contains all products with equipment prices, no labour columns
- **Labour Invoice**: Contains products with labour rates only, "Labour Rate" column header
- **Split Group UI**: Collapsible group rows on invoice list with quotation reference (QO-prefix) and expandable sub-rows showing actual invoice numbers (INV-prefix)
- **PDF Support**: Split-aware PDF generation — correct titles ("INVOICE (LABOUR)" / "INVOICE (MATERIALS)"), conditional columns, original quotation reference
- **VOID Watermark**: Voided invoices display diagonal "VOID" watermark at 15% opacity on PDF
- **Void Invoice Fix**: Fixed void function that was targeting wrong PO when multiple POs share the same PR (now uses PO id directly)
- **Void Redirect**: Void action redirects back to invoice list instead of tax invoice list
- **Split Group Subtotals**: Corrected subtotal calculation for split siblings to prevent double-counting labour values

**Database Migration** (`migrations/015_split_invoice_wht.sql`):
- Added `split_group_id` INT column to `po` table
- Added `split_type` ENUM('full','material','labour') to `po` table
- Index on `split_group_id` for group queries

**Technical Details**:
- Modified: InvoiceController (split type detection, void/complete fix), Invoice model (void by PO id, split subtotals), invoice list/view/PDF views
- 4 new split invoice tests (test-pdf-invoice-split.php)
- Split logic in Delivery model's `createSplitInvoices()` method

### v5.8-phase2-timestamps (March 29, 2026) — Phase 2 Database Hardening

**Timestamps Migration** — All 59 database tables now have `created_at` and `updated_at` columns:

- **Full Coverage**: Added `created_at` and `updated_at` to all 41 tables that were missing them (16 needed both, 15 needed created_at, 10 needed updated_at)
- **Backfill**: 5,962 rows backfilled from legacy date columns (deliver.deliver_date, iv.createdate, pay.date, po.date, pr.date, receive.date, receipt.createdate, voucher.createdate)
- **Idempotent Migration**: Stored procedure `add_column_if_not_exists` with BINARY comparison for cross-collation compatibility (utf8_general_ci ↔ utf8_unicode_ci)
- **Date Bounds**: Handles MySQL edge cases — `0000-00-00` dates, TIMESTAMP range limits (1970–2038)
- **Rollback Script**: Full rollback with `DROP COLUMN IF EXISTS` for all added columns

**Infrastructure Cleanup**:

- Moved 7 Phase 2 planning docs from root to `docs/phase2/`
- Branch cleanup: 25 → 4 branches (main, develop, 2 unmerged feature branches)
- Added 6 Copilot skill definitions in `.github/skills/`
- Migration files in `migrations/phase2_timestamps/` (000_add_columns.sql, 010_backfill.sql, rollback/)

### v5.7-api-templates (March 29, 2026) — Website Templates & Admin Panel

**Website Templates** — Self-hosted booking websites powered by iACC Sales Channel API:

- **Tour Company Demo**: Full-featured tour booking website with product grid, category filters, booking modal, responsive design
- **Setup Wizard**: 3-step guided setup — API credentials, product sync, site configuration with admin password
- **Admin Panel**: 4-tab management interface — Products (toggle on/off), API Settings (update/test), Sync (pull from API), Bookings (view orders)
- **Admin Authentication**: bcrypt login system with session-based auth, customizable username/password via Setup Wizard
- **Admin Bar**: Quick-access toolbar on website with Admin Panel, Sync, Settings, Login/Logout links
- **API Integration**: Full iACC API client for products, categories, orders, and subscription management
- **Local SQLite Cache**: Products and categories cached locally for fast page loads, synced on demand
- **Developer Pages**: API Documentation (api-docs.php), Template Setup Demo (template-demo.php), Hosting Guide (template-howto.php)
- **Landing Page**: Templates section, Developer footer column with links to all developer pages

**Technical Details**:
- 9 new template files (admin.php, admin-login.php, setup.php, index.php, book.php, sync.php, config.php, api-client.php, database.php)
- 3 new public pages (api-docs.php, template-demo.php, template-howto.php)
- API endpoints added: GET /products, GET /categories
- Admin auth: bcrypt password hash stored in config.php, session guard on admin.php
- SQLite with is_active column, product toggle, booking tracking, migration support

### v5.6-journal-module (March 29, 2026) — Journal Voucher System

**Journal Module** — Double-entry journal voucher system:

- **Journal Voucher CRUD**: Create, view, list journal entries with auto-generated JV numbers
- **Double-Entry Bookkeeping**: Debit/credit entry pairs with balance validation
- **Chart of Accounts**: Integration with existing chart_of_accounts table
- **Voucher Types**: payment, receipt, journal classification on existing voucher system

### v5.5-dashboard-reports (March 29, 2026) — Dashboard Charts & Reports Hub

**Dashboard Charts** — Interactive Chart.js visualizations on the user dashboard:

- **Revenue vs Expenses**: Bar + line combo chart showing 12-month revenue and expense trends with ฿ formatting
- **Payment Status**: Doughnut chart showing paid/partial/unpaid invoice distribution
- **Order Status**: Doughnut chart showing pending/completed order distribution
- **Chart.js 4.4.7**: CDN-loaded, responsive, with custom tooltips and legends

**Reports Hub** — Centralized reports navigation with card-based UI:

- **Reports Center**: Card grid linking to all report modules (Financial, Expense, Tax, Data Exports)
- **AR Aging Report**: 5-bucket aging analysis (0-30, 31-60, 61-90, 91-120, 120+ days) with customer detail tables
- **Sidebar Menu**: Reports upgraded from single link to submenu with 4 items

**Technical Details**:
- 5 new model methods (getMonthlyRevenue, getMonthlyExpenses, getPaymentStatusDistribution, getOrderStatusDistribution, getArAging)
- 2 new controller methods (ReportController::hub, ReportController::arAging)
- 2 new views (report/hub.php, report/ar-aging.php)
- 2 new routes (175 total MVC routes)
- Dashboard model: fills missing months with zero values, company-filtered queries
- AR Aging: calculates outstanding = total - paid per invoice, groups by aging bucket

### v5.4-expense-module (March 28, 2026) — Q3 Expense Tracking

**Expense Module Foundation** — Complete expense tracking with approval workflow:

- **Expense CRUD**: Create, edit, view, delete expenses with auto-generated number (EXP-YYYYMM-XXXX)
- **Expense Categories**: 10 seeded categories (bilingual EN/TH) — Office Rent, Utilities, Travel, Salary, etc.
- **VAT/WHT Calculator**: Live JavaScript calculation with preview on expense form
- **Approval Workflow**: Draft → Pending → Approved → Paid (with reject/cancel actions)
- **Monthly Summary**: Category breakdown with colored bars, 12-month chart, status breakdown
- **Category Management**: Card grid with color-coded icons, toggle active, modal add/edit
- **Receipt Upload**: File attachment support (JPG/PNG/PDF)
- **Vendor/Project Tracking**: Autocomplete with linkage to PO/PR

**Technical Details**:
- 1 new controller (ExpenseController — 13 methods), 2 new models, 6 new views
- 13 new routes (173 total MVC routes)
- 2 new database tables with FK constraint
- Migration: `database/migrations/004_expense_tables.sql`
- Sidebar: Expenses menu with 5 sub-items (List, New, Categories, Summary, Project Costs)
- Project cost report with drill-down detail and progress bars
- CSV/JSON export (standalone route) with full filter support

### v5.3-payment-gateway (March 28, 2026) — Q2 Payment & Tax

**Payment Gateway & Multi-Currency** — Complete payment infrastructure:

- **PromptPay Integration**: QR code generation, slip upload, admin review workflow (approve/reject with reason)
- **Multi-Currency**: 10 currencies (THB, USD, EUR, GBP, JPY, CNY, SGD, MYR, KRW, AUD), Bank of Thailand API exchange rates, toggle activation
- **Thai Tax Reports**: PP30 (ภ.พ.30) monthly VAT return, ภ.ง.ด.3/53 WHT reports, annual dashboard, CSV/JSON export, save & file
- **Payment Gateway Config**: Admin panel for gateway settings per company
- **Slip Review**: Admin workflow for reviewing payment slips (approve/reject with audit trail)
- **Modern UI**: All Q2 views use master-data.css design system (gradient headers, stats cards, card grids, client-side search)

**Technical Details**:
- 5 new controllers, 4 new models, 2 new services, 11 new views
- 21 new routes (160 total MVC routes)
- 6 new database tables + 4 table alterations
- Migration: `database/migrations/q2_2026_payment_gateway.sql`

### v5.2-ui-modernization (March 28, 2026) — MVC View Upgrade

**Legacy Modern UI** — Upgraded all 18 MVC views to consistent legacy modern UX/UI design:

- **PO Module** (4 views): List with filters/pagination/date presets, detail view with info-cards, edit form with dynamic products, delivery form with serial numbers
- **Delivery Module** (4 views): List with DN OUT/IN tables and sendouts, detail with receive forms, standalone create form, edit form
- **PR Module** (1 view): Detail view with info-cards, products table, create quotation action
- **Receipt Module** (3 views): List with stats/filters, create/edit with source type selector (manual/quotation/invoice), detail with summary
- **Voucher Module** (3 views): List with stats, create/edit with vendor info and products, detail with summary
- **Payment Module** (1 view): Inline add/edit with search, indigo theme
- **Billing Module** (2 views): Invoice list with billing status, create with multi-invoice checkbox selector

**Design System** — Consistent patterns across all modules:

- Color-coded gradients per module (PO green, Delivery purple, Receipt green, Voucher red, Payment indigo, Billing violet)
- Inter font, card-based layouts, glass-effect header buttons, status-badge pills
- Responsive grid layouts with mobile breakpoints
- Dynamic product forms with add/remove JavaScript
- All forms use MVC routes with CSRF protection
- 42/42 E2E tests passing

### v5.1-channel-api (March 27, 2026) — Sales Channel API + Staging

**Sales Channel API** — Complete REST API for external integrations:

- **Phase 1**: REST API foundation — CRUD endpoints, API key authentication, admin panel
- **Phase 2**: Rate limiting (60/min), API key rotation, idempotency keys
- **Phase 3**: Webhooks with HMAC-SHA256 signing, interactive API docs, order detail view
- **Phase 4**: Order management UI — approve/reject/cancel/retry actions
- **Phase 5**: Export (CSV/JSON), notifications, webhook management API, invoice generation

**Staging Environment** — Full CI/CD to dev.iacc.f2.co.th:

- Added composer install to staging workflow (fixes autoloader for MVC)
- Fast-path landing page — anonymous visitors skip DB connection
- 5-second DB connection timeout (prevents page hangs)
- Logout fix — updated URLs for MVC file relocation
- CI workflow syntax checks updated for MVC file structure

### v5.0-mvc (March 26, 2026) — Full MVC Migration

**Architecture Overhaul** — Complete migration from monolithic PHP to MVC pattern:

- **Phase 1**: Upgrade PHP 8.2, Composer, mPDF 8.x, PHPMailer 6.x
- **Phase 2**: MVC foundation — BaseController, BaseModel, routing system
- **Phase 3**: Business logic migration (Invoice, PR, PO, Delivery, Payment)
- **Phase 4**: Admin features (Dashboard, Users, Reports, Audit Log)
- **Phase 5**: Advanced features (Payment Gateway, Invoice Payment, AI Admin)
- **Phase 6**: 100% migration — all remaining routes converted, zero legacy

**Post-Migration**:
- 126 comprehensive MVC tests (168 total at the time)
- Security hardening: CSRF on all forms, security headers, rate limiting
- Root PHP cleanup: archived 85 to 95 legacy files, 24 to 12 active remain
- CI/CD pipeline: 4-job deploy (Lint, Build, Deploy, Health Check)

### v4.11 (February 6, 2026)
- Critical: PO Edit products disappearing (shared $args array fix)
- Docker environment stability, 42 integration tests added

### v4.10 (January 20, 2026)
- PO View, Delivery, Invoice, Tax Invoice PDF fixes

### v4.9 (January 19, 2026)
- Company management improvements, dashboard company selector, soft delete

### v4.0 to v4.8 (January 2026)
- AI chatbot (29 tools), RBAC system, delivery workflow, UI modernization
- Docker environment, security suite (bcrypt, CSRF, rate limiting)
- Database optimization (40+ indexes, 13 foreign keys, soft delete)

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
