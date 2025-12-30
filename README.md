# Legacy PHP Source Code Backup

**Date Backed Up**: December 4, 2025  
**Project**: iACC - Accounting Management System  
**Status**: Legacy system (being migrated to Next.js + Node.js)

## Directory Structure

```
old-version-backup/
├── php-source/                 # Complete PHP source code backup
│   ├── inc/                    # Core PHP classes and configuration
│   ├── js/                     # JavaScript files (jQuery, Bootstrap, plugins)
│   ├── css/                    # CSS files and stylesheets
│   ├── MPDF/                   # PDF generation library
│   ├── MPDF57-7/              # Additional PDF library
│   ├── PHPMailer/             # Email library
│   ├── TableFilter/           # Data table filtering library
│   ├── upload/                # File uploads directory
│   ├── file/                  # File storage
│   ├── images/                # Image assets
│   ├── font-awesome/          # Font Awesome icons
│   ├── fonts/                 # Font files
│   └── *.php files            # Main application files
│
├── iacc/                       # Original backup (unchanged)
├── index.php                   # Original index file
├── src/                        # Source code directory
└── views/                      # View templates

```

## Main Application Files

### Configuration
- `inc/sys.configs.php` - Database configuration
- `inc/class.dbconn.php` - Database connection class
- `inc/class.hard.php` - Core helper functions
- `inc/class.current.php` - Session and current user management
- `inc/string-th.xml` - Thai language strings
- `inc/string-us.xml` - English language strings

### Authentication & User Management
- `authorize.php` - User authentication/login handling
- `login.php` - Login page
- `remoteuser.php` - Remote user handling

### Company/Vendor Management
- `company.php` - Add/edit company
- `company-list.php` - List companies
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
- MariaDB 10.4.13
- Apache 2.4

## Migration to New Stack

This code is being migrated to:

```
Frontend:  Next.js 14 + TypeScript + Tailwind CSS + shadcn/ui
Backend:   Node.js + Express + TypeScript + Prisma
Database:  PostgreSQL (Neon.tech)
Auth:      JWT + bcrypt + RBAC
```

### Migration Status
- 📋 Database analysis: ✅ Complete
- 🔧 Schema design: 🔄 In Progress
- 🎨 Frontend setup: ⏳ Pending
- ⚙️ Backend setup: ⏳ Pending
- 🔐 Authentication: ⏳ Pending
- 📊 Feature migration: ⏳ Pending

## How to Use This Backup

### 1. Reference Original Code
```bash
cd old-version-backup/php-source/
grep -r "function_name" .  # Find specific functions
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
