# iACC - Accounting Management System

**Version**: 5.3-payment-gateway  
**Status**: Production Ready  
**Last Updated**: March 28, 2026  
**Architecture**: MVC (Model-View-Controller) + REST API  
**PHP**: 8.2+ | **MySQL**: 8.0 | **Nginx**: Alpine

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
| **Controllers** | 37 (+3 Q2) |
| **Models** | 30 (+2 Q2) |
| **Views** | 109 (+11 Q2) |
| **Services** | 3 (ChannelService, PromptPayService, CurrencyService) |
| **MVC Routes** | 160 (+21 Q2) |
| **Legacy Routes** | 0 |
| **Test Cases** | 188 (42 E2E + 20 API + 126 MVC) |
| **Active Root Files** | 12 |
| **Archived Legacy Files** | 95 |

---

## 🏗️ Architecture

### MVC + API Structure

```
app/
├── Config/
│   └── routes.php                 # 139 MVC routes (public, standalone, normal)
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
│   ├── HealthController.php       # System health endpoint
│   ├── AuthController.php         # Login/logout/forgot password
│   └── ...
├── Models/ (30)
│   ├── BaseModel.php              # Base with DB helpers
│   ├── Currency.php               # Currency CRUD & exchange rates
│   ├── TaxReport.php              # VAT/WHT report generation
│   ├── SlipReview.php             # Slip approval/rejection
│   ├── InvoicePayment.php         # Invoice payment processing
│   ├── ApiKey.php                 # API key management
│   ├── ChannelOrder.php           # Channel order processing
│   └── ...
├── Services/ (3)
│   ├── ChannelService.php         # Business logic for channel API
│   ├── PromptPayService.php       # QR code generation & payment
│   └── CurrencyService.php        # Exchange rates (BOT API)
└── Views/ (109)
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

### Active Root PHP Files (12)

| Category | Files |
|----------|-------|
| **Core** | `index.php`, `login.php`, `api.php` |
| **Public Pages** | `landing.php`, `about.php`, `contact.php`, `roadmap.php`, `blog.php`, `press.php`, `careers.php` |
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

## 📂 Project Structure

```
iAcc-PHP-MVC/
│
├── app/                          # MVC application layer
│   ├── Config/routes.php         # Route definitions (139 MVC, 0 legacy)
│   ├── Controllers/ (34)         # Request handlers
│   ├── Models/ (28)              # Business logic & data access
│   ├── Services/ (1)             # Business services (ChannelService)
│   └── Views/ (98)               # View templates organized by module
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
│   ├── string-th.xml             # Thai language strings
│   └── string-us.xml             # English language strings
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
├── legacy/                       # Archived pre-MVC files (95 files)
├── backups/                      # SQL backup files
├── logs/                         # Application logs
│
├── .github/
│   ├── copilot-instructions.md   # AI assistant context
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

- **Company Management** — Vendors, suppliers, customers with soft delete
- **Product Catalog** — Brands, categories, types, models with pricing
- **Purchase Workflow** — PR → PO → Delivery → Invoice → Payment → Tax Invoice
- **Invoicing** — Invoice generation with PDF export
- **Tax Invoices** — Thai tax invoice support (VAT 7%)
- **Quotations** — Quote generation with PDF export
- **Payments** — Payment recording, gateway integration, tracking
- **Deliveries** — Delivery tracking with receipt confirmation
- **Reports** — Business reporting with CSV/JSON export
- **Sales Channel API** — REST API for OTA/PMS/channel manager integrations
- **Payment Gateway** — PromptPay QR, slip upload & admin review workflow
- **Multi-Currency** — 10 currencies, BOT exchange rates, toggle activation
- **Thai Tax Reports** — PP30 (VAT Return), ภ.ง.ด.3/53 (WHT), CSV export, save/file
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
docker exec iacc_php php /var/www/html/tests/test-api-phase3.php
docker exec iacc_php php /var/www/html/tests/test-mvc-comprehensive.php
```

| Suite | Tests | Coverage |
|-------|-------|----------|
| **E2E CRUD** | 42 | Company, Category, Type, Brand, Model, PR→PO workflow, Payment, HardClass |
| **Sales Channel API** | 20 | API auth, CRUD orders, rate limiting, webhooks, key rotation, idempotency |
| **MVC Comprehensive** | 126 | Routes, Controllers, Models, Views, Config, Security, RBAC |
| **Total** | **188** | **All passing ✅** |

---

## 📋 Changelog

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
