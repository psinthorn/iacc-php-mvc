# iACC - Accounting Management System

**Project**: iACC - Accounting Management System (PHP MVC)  
**Status**: Production-ready with Docker support  
**Last Updated**: December 31, 2025  
**Repository**: https://github.com/psinthorn/iacc-php-mvc  
**Git Auth**: SSH ED25519 key

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

## System Architecture (Current - Docker)

```
┌─────────────────────────────────────────┐
│          Web Browser                    │
└────────────────┬────────────────────────┘
                 │ HTTP Requests (Port 80)
┌────────────────▼────────────────────────┐
│      Nginx Web Server (Alpine)          │
│      Reverse Proxy & Static Files       │
└────────────────┬────────────────────────┘
                 │ FastCGI Protocol (9000)
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

> **📋 Comprehensive Improvement Plan Available**: See [IMPROVEMENTS_PLAN.md](IMPROVEMENTS_PLAN.md) for detailed analysis, remediation strategies, implementation roadmap, and resource estimation.

### Critical Security Issues (P1)
- ❌ MD5 password hashing (insecure) - **CRITICAL**
- ❌ No CSRF protection - **CRITICAL**
- ⚠️ SQL injection vulnerable code possible - **HIGH**
- ⚠️ Basic session handling - **HIGH**
- ⚠️ No input validation framework - **HIGH**

**Remediation**: Phase 1 (Months 1-3) - ~120 hours

### Architecture Issues (P2)
- ⚠️ Tightly coupled code - **MEDIUM**
- ⚠️ Mixed business logic and presentation - **MEDIUM**
- ⚠️ No API layer - **MEDIUM**
- ⚠️ No dependency injection - **MEDIUM**
- ⚠️ No unit tests - **MEDIUM**

**Remediation**: Phase 3 (Months 3-8) - ~200 hours

### Database Issues (P2)
- ❌ No foreign key constraints - **MEDIUM**
- ❌ Inconsistent naming conventions - **LOW-MEDIUM**
- ⚠️ Missing timestamps (created_at, updated_at) - **LOW-MEDIUM**
- ⚠️ No audit trail - **LOW-MEDIUM**
- ⚠️ Invalid date handling ('0000-00-00') - **LOW-MEDIUM**

**Remediation**: Phase 2 (Months 2-5) - ~80 hours

### Performance Issues (P3)
- ⚠️ No database indexing strategy documented - **LOW**
- ⚠️ N+1 query problems likely - **LOW**
- ⚠️ No caching layer - **LOW**

**Remediation**: Phase 4 (Months 4-10) - ~60 hours

### Summary
- **Total Issues**: 18 known issues
- **Critical**: 2 | **High**: 3 | **Medium**: 8 | **Low-Medium**: 5
- **Estimated Effort**: 610 hours over 6-12 months
- **Recommended Team**: 2-3 developers
- **Status**: Planning phase complete, ready for implementation

## Docker Configuration (Current)

### Services Overview
All services defined in `docker-compose.yml`:

```yaml
Services:
  - iacc_php (PHP-FPM 7.4) - Internal port 9000
  - iacc_nginx (Nginx Alpine) - Port 80, 443
  - iacc_mysql (MySQL 5.7) - Port 3306
  - iacc_phpmyadmin (PhpMyAdmin) - Port 8083
  - iacc_mailhog_server (MailHog) - Ports 1025, 8025
```

**Commands**:
- Start: `docker compose up -d`
- Stop: `docker compose down`
- View logs: `docker compose logs -f [service]`
- Rebuild: `docker compose build --no-cache && docker compose up -d`

### Container Sizes
- PHP-FPM container: 169 MB (optimized, removed duplicates)
- Total project: 320 MB (41% reduction from original 543 MB)
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

### Git & SSH Authentication

**SSH Key Setup** (if needed on new machine)
```bash
# Generate ED25519 key
ssh-keygen -t ed25519 -C "your-email@example.com"

# Add to GitHub: https://github.com/settings/keys
# Copy your public key
cat ~/.ssh/id_ed25519.pub

# Set remote to SSH (if using HTTPS)
git remote set-url origin git@github.com:psinthorn/iacc-php-mvc.git

# Verify connection
ssh -T git@github.com
```

**Clone with SSH**
```bash
git clone git@github.com:psinthorn/iacc-php-mvc.git
```

**Push/Pull Errors**
```bash
# If you get "Permission denied (publickey)"
# 1. Verify SSH key is added to GitHub settings
# 2. Test SSH connection: ssh -T git@github.com
# 3. Check SSH key permissions: chmod 600 ~/.ssh/id_ed25519

# If you get "HTTP 400 Bad Request"
# 1. Ensure using SSH remote (not HTTPS)
# 2. Verify SSH key is in GitHub settings
# 3. Try: git push origin main -v (for verbose output)
```

### Email Testing with MailHog

All email sent by the application is automatically captured and available at:
- **MailHog Web UI**: http://localhost:8025
- **SMTP Server**: mailhog:1025 (Docker network)
- No email leaves the system in development

## Repository Info

### Size & Cleanup (December 31, 2025)
**Final Size**: 320 MB (41% reduction)
- Original size: 543 MB
- Total cleanup: 223 MB removed
- Docker container: 169 MB (cleaned)

**Cleanup Details**:
- `php-source/` (190 MB) - Duplicate removed
- `iacc/MPDF57-7/` (31 MB) - Obsolete library
- Old backup SQL files (2.2 MB)
- Large SQL files removed from git: 307+ MB
  - `iacc/theiconn_angthong.sql` (249+ MB) - Exceeded GitHub limits
  - `iacc/f2coth_iacc.sql` (20+ MB)
  - Added to `.gitignore` to prevent re-adding

### Git History (December 31, 2025)

| Commit | Message |
|--------|----------|
| `aa5d857` | Remove large SQL files and add to .gitignore |
| `eb0eb98` | Update README with Docker setup instructions |
| `a076720` | Conservative cleanup (223 MB removed) |
| `f1accc4` | Add Dockerfile and docker-compose |

**Current Branch**: main  
**Remote**: git@github.com:psinthorn/iacc-php-mvc.git (SSH)  
**Status**: All commits pushed to GitHub ✅

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

## Recent Updates (December 31, 2025)

✅ **Docker Infrastructure**
- Added Dockerfile with PHP 7.4-FPM
- Configured docker-compose.yml with 5 services
- Nginx reverse proxy (Alpine Linux)
- MySQL 5.7 with persistent data volume
- PhpMyAdmin for database management
- MailHog for email testing in development

✅ **Repository Cleanup**
- Removed 223 MB of duplicate and obsolete files
- Removed 307+ MB of large SQL files from git history
- Optimized container size to 169 MB
- Added comprehensive .gitignore

✅ **Documentation**
- Updated README with Docker quick start
- Added troubleshooting section
- Documented all services and ports
- Added deployment guide (DEPLOYMENT_README.md)

✅ **Git & Deployment**
- SSH ED25519 key authentication configured
- All commits pushed to GitHub successfully
- Remote tracking set up (origin/main)
- Clean git history

---

**Last Updated**: December 31, 2025  
**Status**: Production-ready with Docker support  
**Next Phase**: Frontend and Backend development
