# iAcc-PHP-MVC System Summary
**Last Updated:** January 2, 2026

---

## 1. Project Overview

**iAcc** is a PHP-based accounting/invoicing system for managing:
- Purchase Orders (PO)
- Invoices
- Quotations
- Tax Invoices
- Deliveries
- Products, Categories, Brands
- Companies/Vendors

---

## 2. Technology Stack

| Component | Version | Details |
|-----------|---------|---------|
| PHP | 7.4.33 | FPM in Docker |
| MySQL | 5.7 | Database: `iacc` |
| Web Server | Nginx | Via Docker |
| PDF Library | mPDF 5.7 | Located in `/MPDF/` |
| Frontend | Bootstrap 3.3.7 | jQuery 1.10.2 |
| Container | Docker Compose | See `docker-compose.yml` |

### Docker Services
- **nginx**: Web server on port 80
- **php-fpm**: PHP processor
- **mysql**: Database on port 3306

---

## 3. Directory Structure

```
/Volumes/Data/Projects/iAcc-PHP-MVC/
├── docker-compose.yml      # Docker configuration
├── index.php               # Main router
├── iacc/                   # Main application files
│   ├── inc/                # Includes
│   │   ├── class.dbconn.php    # Database connection
│   │   ├── sys.configs.php     # System configs
│   │   ├── security.php        # Security functions (NEW)
│   │   └── pdf-template.php    # Shared PDF template (NEW)
│   ├── inv.php             # Invoice PDF generator
│   ├── exp.php             # Quotation PDF generator
│   ├── taxiv.php           # Tax Invoice PDF generator
│   ├── po-*.php            # Purchase order files
│   ├── deliv-*.php         # Delivery files
│   ├── company*.php        # Company management
│   ├── product-list.php    # Product listing
│   └── ...
├── MPDF/                   # mPDF library
├── backups/                # Backup files
├── migrations/             # SQL migration files
├── php-source/             # Original PHP source backup
└── logs/                   # Log files
```

---

## 4. Database Configuration

**Connection:** `iacc/inc/class.dbconn.php`

```php
$host = "mysql";      // Docker service name
$user = "root";
$pass = "iacc";
$db   = "iacc";
```

### Key Tables
- `company` - Customer/Vendor info
- `product` - Products
- `category` - Product categories
- `brand` - Product brands
- `invno` - Invoices
- `inv_desc` - Invoice line items
- `payment_methods` - Bank/payment info (NEW)

---

## 5. Recent Changes (January 2, 2026)

### 5.1 PDF Template System
Created a shared PDF template for consistent styling across documents:

**File:** `iacc/inc/pdf-template.php`

Functions available:
- `getPdfStyles()` - Common CSS styles
- `getPdfHeader()` - Centered header with logo
- `getPdfTitle()` - Document title section
- `getPdfInfoSection()` - Customer/vendor info
- `getPdfItemsTable()` - Items table
- `getPdfSummarySection()` - Bank info + totals
- `getPdfAmountWords()` - Amount in words
- `getPdfTerms()` - Terms section
- `getPdfSignatures()` - Signature section
- `generatePdfHtml()` - Complete HTML generator
- `outputPdf()` - PDF output via mPDF

### 5.2 Redesigned PDFs
- **inv.php** - Invoice (fully redesigned with inline CSS)
- **exp.php** - Quotation (uses shared template)
- **taxiv.php** - Tax Invoice (uses shared template)

Design features:
- Centered header with logo
- Modern minimal styling
- Payment info section with bank details
- Clean signature section

### 5.3 Security Enhancements
**File:** `iacc/inc/security.php`

Functions:
- `csrf_token()` / `verify_csrf_token()` - CSRF protection
- `e()` - XSS escaping (htmlspecialchars wrapper)
- `sanitize_input()` - Input sanitization
- `validate_int()` / `validate_email()` - Validation

### 5.4 Database Changes
**New Table:** `payment_methods`

```sql
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    com_id INT NOT NULL,              -- FK to company
    method_type ENUM('bank_transfer', 'credit_card', 'qr_code', 'cash', 'check', 'other'),
    bank_name VARCHAR(100),
    account_name VARCHAR(200),
    account_number VARCHAR(50),
    branch VARCHAR(100),
    swift_code VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    FOREIGN KEY (com_id) REFERENCES company(id)
);
```

Migration file: `migrations/2026_01_02_create_payment_methods_table.sql`

---

## 6. Key Files Reference

### PDF Generation
| File | Purpose | Template |
|------|---------|----------|
| `iacc/inv.php` | Invoice PDF | Inline CSS |
| `iacc/exp.php` | Quotation PDF | Shared template |
| `iacc/taxiv.php` | Tax Invoice PDF | Shared template |
| `iacc/inc/pdf-template.php` | Template functions | - |

### Core Files
| File | Purpose |
|------|---------|
| `iacc/inc/class.dbconn.php` | MySQLi connection |
| `iacc/inc/sys.configs.php` | System settings |
| `iacc/inc/security.php` | Security functions |
| `iacc/core-function.php` | Core business logic |
| `iacc/authorize.php` | Authentication |
| `iacc/menu.php` | Navigation menu |

---

## 7. Testing URLs

| Document | URL |
|----------|-----|
| Invoice | http://localhost/inv.php?id=1923 |
| Quotation | http://localhost/exp.php?id={id} |
| Tax Invoice | http://localhost/taxiv.php?id={id} |
| Dashboard | http://localhost/iacc/dashboard.php |

---

## 8. Git Status

**Current Branch:** `main`
**Remote:** `origin` → `github.com:psinthorn/iacc-php-mvc.git`

### Branches
- `main` - Production branch
- `pdf-template` - Merged into main

### Recent Commits
```
4f57e47 Merge pdf-template branch into main
1389519 Fix signature section styling in PDF templates
56a97cf Create shared PDF template for Invoice, Quotation, Tax Invoice
ff8c932 Invoice redesign with payment_methods
```

---

## 9. Known Issues / TODO

### Potential Improvements
1. **Refactor inv.php** - Currently uses inline CSS, could use shared template
2. **Add more payment methods** - Populate `payment_methods` table with real bank info
3. **MySQLi Migration** - Some files still use deprecated `mysql_*` functions
4. **Error Handling** - Add better error handling for PDF generation

### Files That May Need Attention
- Files with `.backup` extension contain original versions
- `php-source/` contains backup of all original PHP files

---

## 10. Quick Start

### Start Docker
```bash
cd /Volumes/Data/Projects/iAcc-PHP-MVC
docker-compose up -d
```

### Access Application
- Main: http://localhost/
- iAcc: http://localhost/iacc/

### Database Access
```bash
docker exec -it iacc-mysql mysql -u root -piacc iacc
```

### View Logs
```bash
docker-compose logs -f php-fpm
```

---

## 11. Company Info for PDFs

The PDF templates pull vendor info from the `company` table. Key fields:
- `name_en` - English name
- `name` - Thai name
- `adressen` - English address
- `adress` - Thai address
- `logo` - Logo filename (stored in `/images/`)

---

## 12. Session Notes

### What Was Completed
1. ✅ Restored application from php-source backup
2. ✅ Implemented security improvements
3. ✅ Created index.php router
4. ✅ Redesigned invoice PDF (modern minimal style)
5. ✅ Created payment_methods table
6. ✅ Created shared PDF template system
7. ✅ Refactored exp.php and taxiv.php to use shared template
8. ✅ Fixed signature section layout

### Ready for Next Session
- All changes committed and pushed to `origin/main`
- Application is running and functional
- PDF templates are working

---

*This summary was generated for continuity between development sessions.*
