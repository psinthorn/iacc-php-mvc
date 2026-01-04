# iACC - Accounting Management System

**Version**: 3.6  
**Status**: Production Ready (SaaS Ready)  
**Last Updated**: January 4, 2026  
**Project Size**: 175 MB  
**Design Philosophy**: Mobile-First Responsive

---

## 📋 Changelog

### v3.6 (January 4, 2026)
- **Application Footer** 📄:
  - Added global footer component (`inc/footer.php`)
  - Shows copyright, developer credit, and auto-versioning
  - Version and last updated date auto-read from README.md
  - Developer credit: "Developed by F2 Co.,Ltd." with link to www.f2.co.th
  - Responsive design for mobile devices

- **Developer Tools UI Improvements** 🎨:
  - Changed background from dark gradient to white for better readability
  - Moved padding from body to container for cleaner layout
  - Updated disabled Docker tools message with light theme

### v3.5 (January 4, 2026)
- **Developer Tools Dashboard** 🛠️:
  - New consolidated Developer Tools section for Super Admin users
  - Added "Developer Tools" menu in sidebar navigation
  - Quick access panel on main dashboard
  - Access restricted to Super Admin (user_level >= 2)

- **Developer Tools Pages**:
  - `test-crud.php` - Database CRUD operations testing
  - `debug-session.php` - PHP session data viewer
  - `debug-invoice.php` - Invoice access permissions debugger
  - `docker-test.php` - Docker socket connectivity tester
  - `test-containers.php` - Container data duplicate checker
  - `api-lang-debug.php` - Language/localization debugger

- **Modern UI/UX Redesign for Developer Tools** 🎨:
  - New shared style system (`inc/dev-tools-style.php`)
  - Dark gradient backgrounds with modern aesthetics
  - Stats cards with icons and color-coded values
  - Responsive data tables with hover effects
  - Status badges (success/warning/danger/info)
  - Code blocks with syntax highlighting
  - Info boxes for tips and warnings
  - Consistent Inter font family
  - Helper functions: `check_dev_tools_access()`, `get_dev_tools_header()`, `format_json_html()`

- **Routing Updates**:
  - Added 6 new routes in `index.php` for developer tools
  - Routes: test_crud, debug_session, debug_invoice, docker_test, test_containers, api_lang_debug

### v3.4 (January 4, 2026)
- **SaaS Multi-Tenant Security Fixes** 🔒:
  - Fixed data leakage in delivery forms (`deliv-make.php`, `deliv-edit.php`)
    - Type/Brand dropdowns now filtered by company_id
  - Fixed payment.php to verify company ownership before displaying records
  - Fixed product-list.php aggregation to filter by company
  - Fixed modal_molist.php product existence check with company filter
  - Fixed core-function.php store/inventory queries with company isolation
  - Secured test-crud.php (Super Admin only, company-filtered CRUD)
  - All master data now properly isolated per tenant/company

### v3.3 (January 4, 2026)
- **Master Data CRUD Fully Functional** ✅:
  - Fixed all INSERT operations for Category, Type, Brand, Model
  - MySQL strict mode compatibility (NULL for auto-increment IDs)
  - Added `deleted_at` column support for soft deletes
  - Fixed map_type_to_brand insert with CSRF token exclusion
  - All CRUD operations now working with company filtering

- **Professional UX/UI Redesign** 🎨:
  - Complete CSS rewrite with CSS variables design system
  - Modern color palette: Indigo primary (#4f46e5), Emerald success (#10b981)
  - 48px form controls with 2px borders and focus glow
  - Custom select dropdown arrows with SVG
  - Gradient buttons with hover transformations
  - SlideDown animation for inline forms
  - Responsive breakpoints for mobile devices
  - System font stack for cross-platform consistency

- **Brand Management Improvements**:
  - Own company as default vendor selection
  - Vendor dropdown shows "(Own Company)" label
  - Logo preview for existing images

- **Database Fixes**:
  - Category: id, company_id, cat_name, des, deleted_at
  - Type: id, company_id, name, des, cat_id, deleted_at
  - Brand: id, company_id, brand_name, des, logo, ven_id, deleted_at
  - Model: id, company_id, type_id, brand_id, model_name, des, price, deleted_at

### v3.2 (January 4, 2026)
- **Master Data UI Redesign** 🎨:
  - Completely redesigned CRUD pages for Category, Brand, Product, Model, Company
  - Modern card-based stats display with icons
  - Inline create/edit forms with smooth animations
  - Search with icon and filter dropdowns
  - Pagination with page info
  - Empty state illustrations
  - New `css/master-data.css` stylesheet (480+ lines)

- **Master Data Guide Documentation** 📚:
  - New `master-data-guide.php` - Interactive documentation page
  - Industry examples: Travel Agency, Electronics, Retail, Food & Beverage
  - Visual hierarchy diagrams (Category → Brand → Product → Model)
  - Step-by-step setup guide for travel agencies
  - Invoicing workflow explanation
  - Best practices and FAQ section
  - Guide button added to all master data pages

- **Icon Positioning Fixes**:
  - Fixed stat card icons using absolute positioning
  - Fixed search box icon placement inside form
  - Fixed action button icon alignment with inline-flex

### v3.1 (January 4, 2026)
- **Multi-Tenant Architecture** 🏢:
  - Complete company-based data isolation
  - `company_id` column added to 17 tables (brand, category, type, model, map_type_to_brand, payment_method, payment_gateway_config, po, iv, product, deliver, pay, pr, voucher, receipt, store, sendoutitem, receive)
  - New `CompanyFilter` helper class (`inc/class.company_filter.php`)
  - Session-based company filtering via `$_SESSION['com_id']`
  - Default company: F2 Co.,Ltd (company_id = 95)

- **Master Data Company Isolation**:
  - Brand management filtered by company
  - Category management filtered by company
  - Type management filtered by company
  - Model management filtered by company
  - Payment method management filtered by company

- **Transaction Files Updated**:
  - `makeoptionindex.php` - AJAX brand/model lookups filter by company
  - `makeoption.php` - Type/brand cascading selects filter by company
  - `voc-make.php` - Voucher creation filters master data by company
  - `po-edit.php` - PO edit filters master data by company
  - `rep-make.php` - Receipt creation filters master data by company
  - `model.php` - Brand lookup filters by company
  - `modal_molist.php` - Model details filter by company
  - `payment-gateway-config.php` - Full CRUD with company_id

- **Database Migrations**:
  - `migrations/010_add_company_id_multi_tenant.sql` - Add company_id columns
  - `migrations/011_fix_master_data_relationships.sql` - Fix data types, add foreign keys
  - `product.model` changed from VARCHAR(30) to INT (foreign key)
  - `product.quantity` and `product.pack_quantity` changed from VARCHAR to DECIMAL
  - Composite indexes for multi-tenant query optimization

- **Data Integrity Improvements**:
  - Foreign key constraints for type→category, type→company
  - Foreign key constraints for brand→company, category→company
  - Cleaned orphaned model records (9 records fixed)
  - Validated all product→model relationships

### v3.0 (January 4, 2026)
- **Docker Container Monitoring** 🐳:
  - New `admin-containers.php` - Container monitoring dashboard
  - Real-time container status (running/stopped)
  - CPU and memory usage statistics
  - Container logs viewer with modal popup
  - Start/Stop/Restart container actions (development only)
  - Modern card-based UI with status badges

- **Development vs Production Mode**:
  - **Development** (`docker-compose.yml`):
    - Direct Docker socket access for full control
    - Container management enabled (start/stop/restart)
    - Environment badge shows "Development" mode
  - **Production** (`docker-compose.prod.yml`):
    - Docker Socket Proxy (`tecnativa/docker-socket-proxy`)
    - Read-only access - blocks all POST requests
    - Container management disabled for security
    - Environment badge shows "Production (Read-only)"

- **Audit Log Redesign** 📋:
  - Modern timeline-style view
  - Color-coded action icons (CREATE/UPDATE/DELETE)
  - Stats cards showing action counts
  - Expandable details for old/new values
  - Fixed column mismatch (table_name, record_id)
  - Uniform 44px filter input heights

- **UI Improvements**:
  - Added 24px padding between top navbar and page content
  - Updated `css.php` and `css/sb-admin.css` for consistent spacing

- **Security Improvements**:
  - Docker socket proxy for production environments
  - Blocks write operations in production mode
  - Read-only container monitoring for production

### v2.9 (January 3, 2026)
- **User Account Pages** 👤:
  - `profile.php` - Personal information and password management
    - View/edit name and phone
    - Change password with secure validation
    - Avatar with user initials
    - Account info display (role, company, language)
  - `settings.php` - User preferences and settings
    - Language selection (English/Thai)
    - Notification preferences (email, invoice alerts, payment reminders)
    - Display settings (records per page, date format, compact view)
    - Security section with password change link
  - `help.php` - Help center and support
    - Searchable FAQ accordion
    - Quick link cards
    - Documentation section
    - Contact information (email, phone, live chat)
    - Version info display

- **Multi-Language Support** 🌐:
  - New language file system (`inc/lang/en.php`, `inc/lang/th.php`)
  - Landing page fully translated (EN/TH)
  - Language switcher in landing page navbar
  - Session-based language persistence
  - Sarabun font for Thai text

- **UI/UX Improvements**:
  - Improved dropdown styling with 48px height
  - Better font sizing and padding
  - Toggle switches for settings
  - Card-based layout for user pages
  - Purple gradient headers

- **Database Updates**:
  - Added `name` column to authorize table
  - Added `phone` column to authorize table

### v2.8 (January 3, 2026)
- **Landing Page** 🚀:
  - New public-facing landing page (`landing.php`)
  - Hero section with dashboard preview
  - 6 feature cards showcasing system capabilities
  - 3-tier pricing section (Free, Pro, Enterprise)
  - Call-to-action sections
  - Footer with social links and navigation

- **Modern Login Page**:
  - Complete redesign with split-panel layout
  - Branding panel (left) with feature highlights
  - Form panel (right) with modern styling
  - Password visibility toggle
  - Remember me functionality
  - Loading state animations
  - Responsive design (branding hides on mobile)

- **Top Navbar Component**:
  - New fixed top navigation bar (`inc/top-navbar.php`)
  - Purple gradient background
  - iACC logo branding
  - Company badge display
  - Language switcher (EN/TH)
  - User dropdown with avatar and initials
  - Profile, Settings, Help, Sign Out menu items
  - Mobile sidebar toggle

- **Sidebar Improvements**:
  - Light gray background (#f8f9fa) for better readability
  - Purple accent colors for active/hover states
  - Proper text colors for light theme
  - Subtle border separation

- **Authentication Flow**:
  - First-time visitors redirect to landing page
  - Landing page links to login
  - Post-login redirect to dashboard

- **Branding Updates**:
  - Consistent iACC branding throughout
  - Purple primary color scheme (#8e44ad)
  - Inter font family for modern typography

### v2.7 (January 3, 2026)
- **Payment Gateway Integration** 💳:
  - PayPal API integration (Sandbox & Live modes)
  - Stripe API integration (Test & Live modes)
  - `payment-gateway-config.php` for admin configuration
  - Test Connection functionality with real API validation
  - Webhook handler for payment notifications (`payment-webhook.php`)
  - Invoice checkout flow (`inv-checkout.php`)
  - Payment success/cancel handlers

- **Payment Method Management**:
  - New `payment_method` database table
  - CRUD operations (`payment-method.php`, `payment-method-list.php`)
  - Support for both standard and gateway payment methods
  - Bilingual support (English/Thai names)
  - Custom icons for each payment method
  - Sort order management
  - `payment-method-helper.php` utility functions

- **UI/UX Improvements**:
  - Upgraded Font Awesome from 4.0.3 to 4.7.0 (CDN)
  - Fixed FA5-only icons to FA4 equivalents (dashboard)
  - Custom dropdown styling with purple arrow indicators
  - Mode indicator badges (TEST MODE/LIVE)
  - Focus animations and hover effects
  - Responsive design improvements for mobile

- **User Management Enhancements**:
  - User list grouped by role (Super Admin/Admin/User)
  - Color-coded sections (red/blue/green gradients)
  - Role descriptions and user counts per section

- **Thai Font/Encoding Fixes**:
  - Fixed UTF-8 encoding for Thai text display
  - Added UTF-8 headers in `index.php`
  - Re-encoded payment method Thai names in database

- **New View Pages**:
  - `rep-view.php` - Professional receipt view
  - `voc-view.php` - Professional voucher view
  - Linked invoice data support
  - Summary calculations with VAT/discount

### v2.6 (January 3, 2026)
- **Mobile-First Design Priority** 🎯:
  - All new development follows mobile-first design principles
  - Base styles target mobile devices, enhanced for larger screens
  - Touch-friendly action buttons (44px minimum touch target)
  - Responsive tables convert to cards on mobile
  
- **Pagination System**:
  - New `inc/pagination.php` reusable component
  - Mobile-responsive pagination controls
  - Per-page selector (10, 20, 50, 100 records)
  - Record count display
  
- **Default Date Filters**:
  - Month-to-Date (MTD) as default view
  - Quick preset buttons: MTD, YTD, 30 Days, Last Month, All
  - Reduces initial data load for better performance
  
- **Responsive CSS Framework**:
  - New `css/mobile-first.css` with breakpoints:
    - Mobile: < 576px (base)
    - Small tablets: >= 576px
    - Tablets: >= 768px
    - Desktops: >= 992px
    - Large desktops: >= 1200px
  - Card-style table rows on mobile
  - Status badges with color coding
  - Empty state placeholders
  
- **Updated List Pages**:
  - `po-list.php`: Full mobile-first redesign with pagination
  - `compl-list.php`: Full mobile-first redesign with pagination
  - Summary cards showing counts
  - Section headers with direction indicators
  - Touch-friendly action buttons
  
- **Search/Filter on All List Pages**:
  - `compl-list2.php`, `mo-list.php`, `vou-list.php`
  - `payment-list.php`, `type-list.php`, `brand-list.php`
  - `category-list.php`, `user-list.php`
  - Removed old TableFilter JavaScript library

### v2.5 (January 3, 2026)
- **Invoice Payment Tracking**:
  - New `invoice-payments.php` page with payment status tracking
  - Summary cards: Total Invoices, Fully Paid, Partial, Unpaid
  - Outstanding amount calculation
  - Status filters (Paid/Partial/Unpaid)
  - Progress bars showing payment percentage
- **PO List Enhancements**:
  - Search box for PO#, Name, Customer
  - Date range filters (From/To)
  - Direction indicators (↑ Out / ↓ In)
- **Export Functionality**:
  - Excel/CSV export for Business Report
  - Excel/CSV export for Invoice Payment Tracking
  - Print button for all reports
  - UTF-8 BOM for Excel compatibility with Thai text
- **Audit Logging System**:
  - New `audit_log` database table
  - Tracks: login, logout, login_failed, create, update, delete, view, export
  - `audit-log.php` viewer for Super Admin
  - Filters by user, action, entity, date range
  - Detailed change history with old/new values
  - IP address and user agent logging
- **Bug Fixes**:
  - Fixed 9 files with undefined `$users->checkSecurity()` error
  - All PHP files now compatible with PHP 7.4+

### v2.4 (January 3, 2026)
- **MySQLi Migration**:
  - Migrated 37+ PHP files from deprecated `mysql_*` to `mysqli_*` functions
  - Full PHP 7.4+ compatibility
  - Fixed all database queries to use `mysqli_query($db->conn, ...)`
- **Dashboard Enhancements**:
  - Simplified invoice tables (Invoice #, Description, Date, Amount, Actions)
  - Added clickable View and PDF buttons for invoices
  - Fixed invoice table joins (`iv.tex = po.id`)
  - Added Business Summary Report section with period filters
  - Top 10 customers by invoice count
  - Font Awesome 4 icon compatibility fixes
  - Removed redundant "This Month" component
- **Report Page Improvements**:
  - Period filters (Today, 7 Days, 30 Days, This Year, All Time)
  - Column sorting (click any header to sort ascending/descending)
  - Admin global view when no company selected
  - Shows aggregated data across all customers for admins
- **Bug Fixes**:
  - Fixed `po-list.php` undefined `$users` variable
  - Fixed report page showing no data for admin users
  - Fixed duplicate code causing PHP parse errors

### v2.3 (January 2, 2026)
- **Dashboard Overhaul**:
  - Role-based dashboard views (Admin Panel + User Dashboard)
  - Admin/Super Admin: Always see Admin Panel with system stats
  - Admin with company selected: See Admin Panel + Company-specific data
  - Normal User: See only their company's data
  - Company selection grid for quick switching
  - Fixed all dashboard queries to filter by company correctly
  - Direction indicators on invoices (↓ received, ↑ sent)
- **User Management**:
  - New `user-list.php` for Super Admin to manage users
  - Company assignment for normal users (`authorize.company_id`)
  - Role management (User/Admin/Super Admin)
  - Password reset and account unlock features
- **Company Filtering**:
  - All dashboard data filtered by selected company
  - Invoice queries fixed (`iv.id = pr.id` join)
  - Business partner filtering on company list
- **Routing Improvements**:
  - Company switching handled in `index.php` with proper redirects
  - Dashboard menu item added to sidebar
  - Role-based menu visibility

### v2.2 (January 2, 2026)
- **Renamed**: `band` table → `brand` (with files `brand.php`, `brand-list.php`)
- **Renamed**: Authorize table columns (`usr_id`→`id`, `usr_name`→`email`, `usr_pass`→`password`)
- **Added**: Account lockout after 10 failed attempts (30 min)
- **Added**: Password reset flow (`forgot-password.php`, `reset-password.php`)
- **Added**: Remember Me with secure cookies (30 days)
- **Added**: Role-based access control (User/Admin/Super Admin)
- **Fixed**: `authorize.id` PRIMARY KEY with AUTO_INCREMENT
- **Fixed**: `authorize.level` changed from VARCHAR(1) to INT
- **Fixed**: `brand_name` column size (varchar 20 → 100)
- **New Tables**: `password_resets`, `remember_tokens`

### v2.1 (January 2, 2026)
- SQL Injection prevention on 49+ files
- Prepared statements in HardClass
- bcrypt password hashing with MD5 auto-migration
- Rate limiting (5 attempts/15 min)
- CSRF protection
- Session security hardening
- Soft delete support (16 tables)
- DevOps scripts (backup, restore, PHPStan)

---

## 🚀 Quick Start

### Start Docker Services
```bash
docker compose up -d
```

### Access Application
| URL | Description |
|-----|-------------|
| http://localhost/login.php | Login Page |
| http://localhost/index.php | Main Application |
| http://localhost:8083 | phpMyAdmin |

### Stop Services
```bash
docker compose down
```

---

## 📊 Technology Stack

| Component | Version | Status |
|-----------|---------|--------|
| PHP | 7.4.33 FPM | ✅ Running |
| MySQL | 5.7 | ✅ Running |
| Nginx | Alpine | ✅ Running |
| mPDF | 5.7 | ✅ Working |
| Bootstrap | 3.x / 5.3.3 | ✅ Active |
| jQuery | 1.10.2 / 3.7.1 | ✅ Active |
| Font Awesome | 4.7.0 (CDN) | ✅ Active |

---

## 💳 Payment Gateway Configuration (v2.7)

### Supported Gateways

| Gateway | Modes | Features |
|---------|-------|----------|
| PayPal | Sandbox / Live | OAuth2, Webhooks, Returns |
| Stripe | Test / Live | API Keys, Webhooks, Multi-currency |

### Setup Instructions

**PayPal:**
1. Go to [PayPal Developer](https://developer.paypal.com)
2. Create/Login to your account
3. Dashboard → My Apps & Credentials
4. Create new app or use existing
5. Copy Client ID and Secret
6. Configure webhooks for notifications

**Stripe:**
1. Go to [Stripe Dashboard](https://dashboard.stripe.com)
2. Create/Login to your account
3. Developers → API Keys
4. Copy Publishable and Secret keys
5. Set up webhooks under Developers → Webhooks

### Configuration Page
- URL: `index.php?page=payment_gateway_config`
- Access: Super Admin only (level >= 2)
- Features: Save config, Test Connection, Webhook URLs

---

## 🎫 Dashboard (v2.3)

### Role-Based Views

| User Type | Company Selected | Dashboard View |
|-----------|-----------------|----------------|
| Admin/Super Admin | ❌ No | Admin Panel only + Company selection |
| Admin/Super Admin | ✅ Yes | Admin Panel + User Dashboard |
| Normal User | ✅ Always | User Dashboard only |

### Admin Panel Features
- **System Stats**: Total users, companies, locked accounts, failed logins
- **User Breakdown**: Count by role (User/Admin/Super Admin)
- **Quick Actions**: Manage Users, Companies, Reports
- **Company Selection Grid**: 8 most active companies for quick access

### User Dashboard Features
- **KPIs**: Sales today, month sales, pending orders, total orders
- **Invoice Stats**: Invoices and Tax Invoices this month
- **Tables**:
  - Recent Payments (with payment method)
  - Active Purchase Orders
  - Recent Invoices (with counterparty & direction)
  - Tax Invoices Issued

### Company Switching (Admin Only)
```
?page=remote&select_company=ID  → Select a company
?page=remote&clear=1            → Clear selection (back to Admin Panel)
?page=remote&id=ID              → Toggle company (legacy)
```

---

## 👤 User Management (v2.3)

### User Roles
| Level | Role | Permissions |
|-------|------|-------------|
| 0 | User | View own company data only |
| 1 | Admin | View all data, switch companies |
| 2 | Super Admin | All admin + manage users |

### Session Variables
```php
$_SESSION['user_id']     // User ID
$_SESSION['user_email']  // Email address
$_SESSION['user_level']  // 0=User, 1=Admin, 2=Super Admin
$_SESSION['com_id']      // Selected company ID (0 = global)
$_SESSION['com_name']    // Selected company name
```

### Role Helper Methods (class.dbconn.php)
```php
$db->getUserLevel();        // Get current user level
$db->hasLevel(1);           // Check if user has at least level 1
$db->requireLevel(2);       // Require Super Admin, redirect if not
$db->isAdmin();             // Check if Admin or Super Admin
$db->isSuperAdmin();        // Check if Super Admin
```

---

## �🔒 Security Features (v2.1)

### SQL Injection Prevention
All 49+ database files secured with input sanitization:
```php
$id = sql_int($_REQUEST['id']);
$name = sql_escape($_REQUEST['name']);
```

### Prepared Statements
New safe methods in `HardClass`:
```php
$hard->insertSafe('table', ['name' => $value]);
$hard->updateSafe('table', ['name' => $value], ['id' => $id]);
$hard->selectSafe('table', ['id' => $id]);
```

### Password Security
- **Automatic migration** from MD5 to bcrypt on first login
- Uses `password_hash()` with cost factor 12
- Backward compatible with existing MD5 passwords

### Rate Limiting
- **5 login attempts** per 15 minutes per IP
- Login attempts tracked in `login_attempts` table
- User feedback on remaining attempts

### CSRF Protection
- Token-based CSRF protection on login
- Functions: `csrf_token()`, `csrf_field()`, `csrf_verify()`

### Session Security
- HttpOnly cookies
- Strict mode enabled
- SameSite protection
- Session regeneration on login

### Soft Delete
16 tables support soft delete for audit trails:
```php
$hard->softDelete('company', ['id' => $id]);
$hard->restore('company', ['id' => $id]);
$hard->selectActiveSafe('company', []);
```

---

## 🛠️ DevOps Tools

### Database Backup
```bash
# Manual backup
./scripts/backup-db.sh manual

# Daily backup (for cron)
./scripts/backup-db.sh daily

# Weekly backup
./scripts/backup-db.sh weekly
```

### Database Restore
```bash
./scripts/restore-db.sh backups/iacc_backup_20260102.sql.gz
```

### Static Analysis (PHPStan)
```bash
./scripts/phpstan.sh inc iacc
```

---

## 📂 Project Structure

```
iAcc-PHP-MVC/ (172 MB)
│
├── *.php (71 files)              # Main application files
│   ├── login.php                 # Login page
│   ├── authorize.php             # Authentication
│   ├── index.php                 # Main router/dashboard
│   ├── dashboard.php             # Dashboard content
│   │
│   ├── company-*.php             # Company management
│   ├── po-*.php                  # Purchase orders
│   ├── inv*.php                  # Invoices
│   ├── deliv-*.php               # Deliveries
│   ├── payment-*.php             # Payments
│   ├── rep-*.php                 # Reports
│   └── ...
│
├── inc/                          # Core includes
│   ├── sys.configs.php           # Database config
│   ├── class.dbconn.php          # Database connection
│   ├── class.hard.php            # Helper functions
│   ├── security.php              # Security functions
│   ├── pdf-template.php          # PDF template
│   ├── string-th.xml             # Thai language
│   └── string-us.xml             # English language
│
├── MPDF/                         # PDF generation library
├── PHPMailer/                    # Email library
├── TableFilter/                  # Table filtering JS
│
├── css/                          # Stylesheets
├── js/                           # JavaScript
├── fonts/                        # Font files
├── font-awesome/                 # Icon fonts
├── images/                       # Image assets
│
├── file/                         # User uploads (87 MB)
├── upload/                       # Upload folder
│
├── docs/                         # Documentation (83 files)
├── backups/                      # SQL backups
├── logs/                         # Application logs
├── migrations/                   # SQL migrations
│
├── docker/                       # Docker configuration
│   ├── nginx/default.conf        # Nginx config
│   └── mysql/my.cnf              # MySQL config
│
├── scripts/                      # Utility scripts
│   ├── backup-db.sh              # Database backup
│   ├── restore-db.sh             # Database restore
│   └── phpstan.sh                # Static analysis
│
├── docker-compose.yml            # Docker orchestration
├── Dockerfile                    # PHP-FPM image
├── phpstan.neon                  # PHPStan config
├── .env                          # Environment variables
└── .htaccess                     # Apache config
```

---

## 🔧 Configuration

### Database
**File**: `inc/sys.configs.php`
```php
$config["dbhost"] = "mysql";
$config["dbuser"] = "root";
$config["dbpass"] = "root";
$config["dbname"] = "iacc";
```

### Environment Variables
**File**: `.env`
```
DB_HOST=mysql
DB_NAME=iacc
DB_USER=root
DB_PASSWORD=root
```

---

## 📦 Docker Services

| Service | Container | Port |
|---------|-----------|------|
| PHP-FPM | iacc_php | 9000 |
| Nginx | iacc_nginx | 80, 443 |
| MySQL | iacc_mysql | 3306 |
| phpMyAdmin | iacc_phpmyadmin | 8083 |
| MailHog | iacc_mailhog_server | 1025, 8025 |

### Docker Commands
```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f

# Restart services
docker compose restart

# Stop all services
docker compose down

# Access MySQL CLI
docker exec -it iacc_mysql mysql -uroot -proot iacc
```

---

## 🗃️ Database Schema

### Core Tables
| Table | Description |
|-------|-------------|
| `authorize` | User authentication (id, email, password, level) |
| `company` | Companies/vendors/customers |
| `brand` | Product brands |
| `category` | Product categories |
| `type` | Product types |
| `product` | Products catalog |
| `po` | Purchase orders |
| `deliv` | Deliveries |
| `payment` | Payments |
| `credit` | Credits |

### Security Tables
| Table | Description |
|-------|-------------|
| `login_attempts` | Rate limiting tracker |
| `password_resets` | Password reset tokens |
| `remember_tokens` | Persistent login tokens |

---

## ✅ Core Features

- **Company Management** - Vendors, suppliers, customers
- **Product Catalog** - Brands, categories, types, products
- **Purchase Orders** - Create, edit, view, deliver
- **Invoicing** - Invoice generation with PDF export
- **Tax Invoices** - Thai tax invoice support
- **Quotations** - Quote generation with PDF export
- **Payments** - Payment recording and tracking
- **Deliveries** - Delivery tracking and management
- **Reports** - Business reporting
- **Multi-language** - Thai and English support

---

## 📄 PDF Generation

Uses mPDF 5.7 for generating:
- Invoices (`inv.php`)
- Tax Invoices (`taxiv.php`)
- Quotations (`exp.php`)

Template: `inc/pdf-template.php`

---

## 🔐 Authentication System (v2.1)

### Login Flow
| Page | Purpose |
|------|---------|
| `login.php` | Login form with CSRF protection |
| `authorize.php` | Authentication handler |
| `forgot-password.php` | Password reset request |
| `reset-password.php` | Set new password with token |

### User Roles
| Level | Role | Description |
|-------|------|-------------|
| 0 | User | Standard access |
| 1 | Admin | Administrative access |
| 2 | Super Admin | Full system access |

### Security Features
- **Password Hashing**: bcrypt (cost=12) with auto-migration from MD5
- **Rate Limiting**: 5 attempts per 15 minutes per IP
- **Account Lockout**: 10 failed attempts → 30 minute lock
- **CSRF Protection**: Token validation on all forms
- **Remember Me**: Secure cookie-based persistent login (30 days)
- **Password Reset**: Token-based email reset (1 hour expiry)

### Role-Based Access Control
```php
// In inc/class.dbconn.php
$db->getUserLevel();              // Get current user's level
$db->hasLevel(1);                 // Check if user has admin level
$db->requireLevel(2);             // Require super admin (redirects if not)
$db->isSuperAdmin();              // Check if super admin
$db->isAdmin();                   // Check if admin or higher
```

### Session Variables
| Variable | Description |
|----------|-------------|
| `$_SESSION['user_id']` | User ID (int) |
| `$_SESSION['user_email']` | User email |
| `$_SESSION['user_level']` | User role level |
| `$_SESSION['lang']` | Language preference |

### Default Users
| Email | Role |
|-------|------|
| adminx@f2.co.th | Super Admin |

---

## 📝 Development Notes

### File Naming Convention
- `*-list.php` - List/table views
- `*-make.php` - Create forms
- `*-edit.php` - Edit forms
- `*-view.php` - Detail views

### Database
- MySQL 5.7 with utf8mb4 charset
- Database name: `iacc`
- Backups in `backups/` folder

---

## 📚 Documentation

All documentation files are in `docs/` folder (83 markdown files).

---

## 🗂️ Backup

### Create Backup
```bash
./scripts/backup-db.sh manual
```

### Restore Backup
```bash
./scripts/restore-db.sh backups/iacc_backup_YYYYMMDD.sql.gz
```

### SQL Backups Location
```
backups/
```

### Backup Retention
- Daily backups: 30 days
- Weekly backups: 12 weeks

---

## 🔧 Validation Functions

Available in `inc/security.php`:
```php
validate_required(['name', 'email']);  // Check required fields
validate_email($email);                 // Email format
validate_phone($phone);                 // Thai phone format
validate_date($date);                   // Date format (d-m-Y)
validate_range($val, 0, 100);          // Numeric range
validate_tax_id($taxId);               // Thai 13-digit tax ID
validate_file_upload($file, $options); // File upload validation
```

---

## 📜 License

Proprietary - Internal Use Only
