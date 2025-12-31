# iACC - Accounting Management System

**Project**: iACC - Accounting Management System (PHP MVC)  
**Status**: Production-ready with Docker support  
**Last Updated**: December 31, 2025

## Docker Quick Start

This project includes complete Docker support for local development and deployment.

### Prerequisites
- Docker & Docker Compose installed
- 5 available ports: 80 (Nginx), 8083 (PhpMyAdmin), 8025 (MailHog), 3306 (MySQL)

### Getting Started

```bash
# 1. Clone/navigate to the project
cd /path/to/iAcc-PHP-MVC

# 2. Start all services (PHP-FPM, MySQL, Nginx, PhpMyAdmin, MailHog)
docker-compose up -d

# 3. Access the application
- Application: http://localhost
- PhpMyAdmin: http://localhost:8083
- MailHog (Email): http://localhost:8025
```

### Services

| Service | Port | Container Name | Purpose |
|---------|------|------------------|---------|
| Nginx | 80, 443 | iacc_nginx | Web server / reverse proxy |
| PHP-FPM | 9000 | iacc_php | PHP application runtime |
| MySQL | 3306 | iacc_mysql | Database server |
| PhpMyAdmin | 8083 | iacc_phpmyadmin | Database management UI |
| MailHog | 1025, 8025 | iacc_mailhog_server | Email testing |

### Database Configuration

- **Host**: mysql (Docker service name)
- **Port**: 3306
- **Database**: iacc
- **User**: root
- **Password**: root
- **Charset**: utf8mb4

**Note**: For production, change the root password in `docker-compose.yml` and `iacc/inc/sys.configs.php`

---

## Directory Structure

```
iacc-php-mvc/
├── iacc/                       # Main application directory (168 MB)
│   ├── inc/                    # Core PHP classes and configuration
│   │   ├── sys.configs.php     # Database and app configuration
│   │   ├── class.dbconn.php    # Database connection class
│   │   ├── class.hard.php      # Helper functions
│   │   ├── class.current.php   # Session/user management
│   │   ├── string-th.xml       # Thai language strings
│   │   └── string-us.xml       # English language strings
│   ├── js/                     # JavaScript (jQuery, Bootstrap)
│   ├── css/                    # Stylesheets
│   ├── MPDF/                   # PDF generation library
│   ├── PHPMailer/              # Email library
│   ├── TableFilter/            # Data table utilities
│   ├── upload/                 # User file uploads
│   ├── file/                   # File storage
│   ├── images/                 # Image assets
│   ├── font-awesome/           # Icon fonts
│   └── *.php files             # Application pages
│
├── docker-compose.yml          # Docker services configuration
├── Dockerfile                  # PHP-FPM container definition
├── README.md                   # This file
└── DEPLOYMENT_README.md        # Deployment guide
```

## Main Application Files

### Core Configuration & Database
- `iacc/inc/sys.configs.php` - Database and application configuration
- `iacc/inc/class.dbconn.php` - Database connection management (MySQLi)
- `iacc/inc/class.hard.php` - Core helper functions and utilities
- `iacc/inc/class.current.php` - Session management and current user info

### Authentication & User Management
- `iacc/authorize.php` - User authentication/login handling
- `iacc/login.php` - Login page interface

### Core Business Operations
- `iacc/company.php` / `iacc/company-list.php` - Company/vendor management
- `iacc/category.php` / `iacc/category-list.php` - Product categories
- `iacc/product-list.php` - Product inventory management
- `iacc/band.php` / `iacc/band-list.php` - Band/department management
- `company-addr.php` - Manage company addresses
- `company-credit.php` - Company credit information

### Product Management
- `band.php` - Brand management
- `band-list.php` - List brands
- `category.php` - Category management
- `category-list.php` - List categories
- `type.php` - Product type management
- `type-list.php` - List product types
- `product-list.php` - List products

### Procurement
- `po-make.php` - Create purchase order
- `po-edit.php` - Edit purchase order
- `po-view.php` - View purchase order
- `po-list.php` - List purchase orders
- `po-deliv.php` - PO delivery tracking
- `deliv-make.php` - Create delivery
- `deliv-edit.php` - Edit delivery
- `deliv-view.php` - View delivery
- `deliv-list.php` - List deliveries

### Invoicing & Payments
- `inv.php` - Invoice management
- `inv-m.php` - Invoice main operations
- `inv-m.php` - Invoice management interface
- `payment.php` - Payment recording
- `payment-list.php` - List payments
- `credit-list.php` - List credits

### Receipts & Vouchers
- `rec.php` - Receipt handling
- `vou-list.php` - List vouchers
- `vou-print.php` - Print voucher
- `vou-make.php` - Create voucher

### Reports
- `rep-list.php` - List reports
- `rep-make.php` - Generate report
- `rep-print.php` - Print report
- `report.php` - Report generation
- `exp.php` - Export functions
- `exp-m.php` - Export management

### Additional Features
- `qa-list.php` - QA/Issues tracking
- `payment-list.php` - Payment listings
- `credit-list.php` - Credit management
- `core-function.php` - Core functions (27KB - main logic)

### UI/Templates
- `index.php` - Main routing file
- `menu.php` - Navigation menu
- `page.php` - Page template
- `css.php` - CSS inclusion
- `script.php` - JavaScript inclusion

### Database
- `model.php` - Data models
- `model_mail.php` - Email model
- `makeoption.php` - Make option generation
- `makeoptionindex.php` - Option index generation

### Miscellaneous
- `fetadr.php` - Fetch address function
- `testtab.php` - Test table
- `error_log` - Error logging
- Various `.html` files - Static template files (blank.html, buttons.html, etc.)

## Database Files

- `iacc.sql` - Main database export (17,208 lines)
- `theiconn_angthong.sql` - Backup export 1
- `theiconn_cms.sql` - Backup export 2

## Libraries & Dependencies

### PDF Generation
- MPDF - Modern PDF library
- MPDF57-7 - Alternative PDF version

### Email
- PHPMailer - Professional email library

### Data Processing
- TableFilter - Advanced data table filtering

### Frontend
- Bootstrap 3 - UI framework
- jQuery 1.10.2 - DOM manipulation
- jqBootstrapValidation - Form validation
- Font Awesome - Icon library

## System Architecture (Legacy)

```
┌─────────────────────────────────────────┐
│          Web Browser                    │
└────────────────┬────────────────────────┘
                 │ HTTP Requests
┌────────────────▼────────────────────────┐
│      Apache Web Server                  │
│      (Port 8089 in Docker)              │
└────────────────┬────────────────────────┘
                 │ PHP Processing
┌────────────────▼────────────────────────┐
│      PHP Application (5.6/7.2)          │
│  ├── index.php (Router)                 │
│  ├── inc/ (Config & Classes)            │
│  └── *.php (Feature files)              │
└────────────────┬────────────────────────┘
                 │ SQL Queries
┌────────────────▼────────────────────────┐
│   MySQL/MariaDB Database                │
│   (iacc.sql schema)                     │
│   (Port 3366 in Docker)                 │
└─────────────────────────────────────────┘
```

## Features Summary

✅ User Authentication (Session-based)  
✅ Company/Vendor Management  
✅ Product Catalog (Brands, Categories, Types)  
✅ Purchase Order Management  
✅ Invoice Management  
✅ Payment Tracking  
✅ Receipt Management  
✅ Voucher Management  
✅ Credit Management  
✅ Reporting & Export  
✅ Multi-language Support (Thai, English)  
✅ Discussion Board/Forum  
✅ User Roles (Basic - 3 levels)  
✅ PDF Generation  
✅ Email Integration  

## Known Issues (Documented for Reference)

### Security Issues
- ❌ MD5 password hashing (insecure)
- ❌ No CSRF protection
- ❌ Basic session handling
- ⚠️ SQL injection vulnerable code possible
- ⚠️ No input validation framework

### Architecture Issues
- ⚠️ Tightly coupled code
- ⚠️ Mixed business logic and presentation
- ⚠️ No API layer
- ⚠️ No dependency injection
- ⚠️ No unit tests

### Database Issues
- ❌ No foreign key constraints
- ❌ Inconsistent naming conventions
- ⚠️ Missing timestamps (created_at, updated_at)
- ⚠️ No audit trail
- ⚠️ Invalid date handling ('0000-00-00')

### Performance Issues
- ⚠️ No database indexing strategy documented
- ⚠️ N+1 query problems likely
- ⚠️ No caching layer

## Docker Configuration (Legacy)

### Docker Compose Setup
```yaml
Services:
  - f2xiacc (PHP Application) - Port 8089
  - db_mysql (MariaDB) - Port 3366
  - phpmyadmin (MySQL Management) - Port 8085
```

**Start Command**: `docker compose up -d`  
**Stop Command**: `docker compose down`

### Environment
- PHP 5.6 or 7.2 (configurable)
## Technology Stack

- **Language**: PHP 7.4
- **Web Server**: Nginx (Alpine Linux)
- **Database**: MySQL 5.7
- **Runtime**: PHP-FPM
- **Libraries**: 
  - MySQLi (Database)
  - MPDF (PDF generation)
  - PHPMailer (Email)
  - jQuery + Bootstrap 3 (Frontend)

## Environment & Deployment

### Development (Docker)
1. All services configured in `docker-compose.yml`
2. Environment variables in docker-compose.yml
3. Volume mounts:
   - `./iacc` → `/var/www/html` (application code)
   - `mysql_data` → `/var/lib/mysql` (database persistence)

### Production Deployment
See [DEPLOYMENT_README.md](DEPLOYMENT_README.md) for production setup instructions.

## Troubleshooting

### Common Issues

**Database Connection Error**
```
Error: mysqli_error(): expects exactly 1 parameter, 0 given
```
- ✅ Fixed in commit f1accc4 (uses `mysqli_connect_error()` for connection phase)

**Containers won't start**
```bash
# Check container logs
docker-compose logs -f [service-name]

# Verify ports are available
lsof -i :80    # Nginx
lsof -i :3306  # MySQL
lsof -i :8083  # PhpMyAdmin
```

**MySQL connection refused**
```bash
# Wait for MySQL to be ready (takes 10-15 seconds)
docker-compose logs mysql

# Restart MySQL service
docker-compose restart mysql
```

### Email Testing with MailHog

All email sent by the application is automatically captured and available at:
- **MailHog Web UI**: http://localhost:8025
- **SMTP Server**: mailhog:1025 (Docker network)
- No email leaves the system in development

## Repository Info

**Size**: 320 MB (after cleanup on Dec 31, 2025)
- Original: 543 MB
- Cleanup removed: 223 MB (41% reduction)
  - `php-source/` duplicate: 190 MB
  - `iacc/MPDF57-7/` obsolete: 31 MB  
  - Old backup SQL: 2.2 MB

**Git History**: 
- Latest commit: f1accc4 (Docker + DB fixes)
- Cleanup commit: a076720 (Removed duplicates)
- Deployment tag: before-cleanup-v1

## Future Migration

This codebase is being migrated to:

```
Frontend:  Next.js 14 + TypeScript + Tailwind CSS
Backend:   Node.js + Express + TypeScript + Prisma
Database:  PostgreSQL
Auth:      JWT + bcrypt + RBAC
```
cat company-list.php       # Review specific features
```

### 2. Extract Logic
Look for:
- Database queries (convert to Prisma)
- Form validation (convert to Zod/Joi)
- Business logic (port to Node.js)

### 3. Understand Data Relationships
Review:
- `core-function.php` - Main business logic
- Database queries in each feature file
- Form structures in `*-make.php` files

### 4. Run Original System (if needed)
```bash
docker compose up -d
# Access at http://localhost:8089
# phpMyAdmin at http://localhost:8085
```

## Important Notes

- ⚠️ **Production Use**: This legacy code should not be used for new features
- 🔒 **Security**: This code has known security vulnerabilities
- 📚 **Documentation**: This backup serves as reference for feature understanding
- 🔄 **Active Development**: New system is being developed in parallel
- 🗄️ **Data**: All database data is preserved in `iacc.sql`

## Contact & Support

For questions about this legacy system:
- Check original feature files for implementation details
- Review database schema for data relationships
- Consult `core-function.php` for business logic

For new system development, refer to project root documentation:
- `MIGRATION_PLAN.md` - Migration strategy and timeline
- `DATABASE_SCHEMA.md` - New schema design
- `/README.md` - Project overview

---

**Last Updated**: December 4, 2025  
**Status**: Legacy system preserved for reference  
**Next Phase**: Frontend and Backend development
