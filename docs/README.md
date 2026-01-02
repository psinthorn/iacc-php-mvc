# iAcc - Accounting Management System

**Project**: iACC - Comprehensive Accounting & Procurement Management  
**Version**: 2.1 (PDF Template Update)  
**Status**: Active Development  
**Last Updated**: January 2, 2026

---

## 🚀 Quick Start

### Start Docker Services
```bash
cd /Volumes/Data/Projects/iAcc-PHP-MVC
docker-compose up -d
```

### Access Application
| URL | Description |
|-----|-------------|
| http://localhost/ | Main entry |
| http://localhost/iacc/ | Application |
| http://localhost/inv.php?id=1923 | Invoice PDF |
| http://localhost/exp.php?id={id} | Quotation PDF |
| http://localhost/taxiv.php?id={id} | Tax Invoice PDF |

### Database Access
```bash
docker exec -it iacc-mysql mysql -u root -piacc iacc
```

---

## 📊 Current System Status

### Technology Stack
| Component | Version | Status |
|-----------|---------|--------|
| PHP | 7.4.33 FPM | ✅ Running |
| MySQL | 5.7 | ✅ Running |
| Nginx | Latest | ✅ Running |
| mPDF | 5.7 | ✅ Working |
| Bootstrap | 3.3.7 | ✅ Active |
| jQuery | 1.10.2 | ✅ Active |

### Docker Services
- **nginx** - Web server (port 80)
- **php-fpm** - PHP processor
- **mysql** - Database (port 3306)

---

## 📂 Project Structure

```
iAcc-PHP-MVC/
├── docker-compose.yml          # Docker configuration
├── index.php                   # Main router
├── SYSTEM_SUMMARY.md           # Detailed system summary
│
├── iacc/                       # Main application
│   ├── inc/                    # Core includes
│   │   ├── class.dbconn.php    # MySQLi connection
│   │   ├── sys.configs.php     # System settings
│   │   ├── security.php        # Security functions (NEW)
│   │   └── pdf-template.php    # Shared PDF template (NEW)
│   │
│   ├── inv.php                 # Invoice PDF generator
│   ├── exp.php                 # Quotation PDF generator
│   ├── taxiv.php               # Tax Invoice PDF generator
│   ├── po-*.php                # Purchase order files
│   ├── deliv-*.php             # Delivery files
│   ├── company*.php            # Company management
│   └── ...
│
├── MPDF/                       # PDF library
├── migrations/                 # SQL migration files
├── backups/                    # Backup files
├── php-source/                 # Original PHP backup
└── logs/                       # Application logs
```

---

## 🆕 Recent Updates (January 2, 2026)

### PDF Template System
Created modern, minimal PDF templates for all documents:

- **Shared Template**: `iacc/inc/pdf-template.php`
- **Invoice**: `iacc/inv.php` (redesigned)
- **Quotation**: `iacc/exp.php` (uses shared template)
- **Tax Invoice**: `iacc/taxiv.php` (uses shared template)

Features:
- Centered header with company logo
- Modern minimal styling
- Payment info section with bank details
- Clean signature section

### Security Enhancements
**File**: `iacc/inc/security.php`

```php
csrf_token()           // Generate CSRF token
verify_csrf_token()    // Validate CSRF token
e($string)             // XSS escape (htmlspecialchars)
sanitize_input()       // Input sanitization
validate_int()         // Integer validation
validate_email()       // Email validation
```

### New Database Table
**Table**: `payment_methods`

```sql
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    com_id INT NOT NULL,
    method_type ENUM('bank_transfer', 'credit_card', 'qr_code', 'cash', 'check', 'other'),
    bank_name VARCHAR(100),
    account_name VARCHAR(200),
    account_number VARCHAR(50),
    branch VARCHAR(100),
    swift_code VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (com_id) REFERENCES company(id)
);
```

---

## 🔧 Database Configuration

**File**: `iacc/inc/class.dbconn.php`

```php
$host = "mysql";      // Docker service name
$user = "root";
$pass = "iacc";
$db   = "iacc";
```

### Key Tables
| Table | Description |
|-------|-------------|
| `company` | Customers/Vendors |
| `product` | Products catalog |
| `category` | Product categories |
| `brand` | Product brands |
| `invno` | Invoices |
| `inv_desc` | Invoice line items |
| `payment_methods` | Bank/payment info |

---

## 📝 Git Information

**Repository**: `github.com:psinthorn/iacc-php-mvc.git`  
**Branch**: `main`

### Recent Commits
```
b0be717 Add system summary for development continuity
4f57e47 Merge pdf-template branch into main
1389519 Fix signature section styling in PDF templates
56a97cf Create shared PDF template for Invoice, Quotation, Tax Invoice
```

---

## 📋 Development Notes

### For Detailed System Information
See [SYSTEM_SUMMARY.md](SYSTEM_SUMMARY.md) for:
- Complete file reference
- Function documentation
- Testing URLs
- Known issues and TODO

### Backup Locations
- `php-source/` - Original PHP files backup
- `backups/` - File backups with timestamps
- `*.backup` files - Individual file backups

---

---

## 📊 PROJECT TIMELINE (7 Weeks)

```
WEEK 1 (Jan 1-7)           PHASE 1: Tech Stack Stabilization
├─ PHP 8.3 upgrade (cPanel EasyApache)
├─ MySQL 8.0 upgrade (cPanel WHM)
├─ 29 test suite execution
└─ Gate 1: All tests passing ✓

WEEKS 2-3 (Jan 8-21)       PHASE 2: Database Production Hardening
├─ Add system columns to 31 tables
├─ Add 25+ foreign key constraints
├─ Create audit log system with triggers
├─ Automate daily backups
└─ Gate 2: Audit system working ✓

WEEK 4 (Jan 22-Feb 4)      PHASE 3: Authentication & Security
├─ Migrate 100% passwords: MD5 → Bcrypt
├─ Implement RBAC (5 roles, 50+ permissions)
├─ Add CSRF token protection
├─ OWASP Top 10 compliance (0 vulnerabilities)
└─ Gate 3: OWASP scan passed ✓

WEEK 5 (Feb 5-18)          PHASE 4: cPanel Deployment
├─ Blue-Green deployment setup
├─ Zero-downtime deployment execution
├─ 24/7 monitoring setup
├─ Automated backups verification
└─ Gate 4: 99.9% uptime achieved ✓

🎉 PROJECT COMPLETE - Ready for Production
```

---

## 📂 PROJECT DIRECTORY STRUCTURE

```
iAcc-PHP-MVC/
├── 📋 PLANNING_COMPLETE_SUMMARY.md      ← Planning overview
├── 📄 EXECUTIVE_SUMMARY.md              ← Team reference
├── 🚀 PROJECT_ROADMAP_2026.md           ← Main detailed plan
├── 📅 IMPLEMENTATION_TIMELINE.md        ← Day-by-day schedule
├── 📌 QUICK_REFERENCE.md                ← Desk reference card
├── 📚 DOCUMENTATION_INDEX.md            ← Master index
├── README.md                            ← This file
├── docker-compose.yml                   ← Development setup
├── .env                                 ← Environment config
│
├── iacc/                                ← Main application
│   ├── inc/                            ← Core classes
│   │   ├── sys.configs.php             ← Database config
│   │   ├── class.dbconn.php            ← DB connection
│   │   ├── class.hard.php              ← Helper functions
│   │   ├── SecurityHelper.php           ← NEW: Security utils
│   │   ├── string-th.xml               ← Thai language
│   │   └── string-us.xml               ← English language
│   │
│   ├── index.php                       ← Main router
│   ├── authorize.php                   ← Authentication
│   ├── login.php                       ← Login page
│   ├── dashboard.php                   ← Main dashboard
│   │
│   ├── company-*.php                   ← Company management
│   ├── po-*.php                        ← Purchase orders
│   ├── inv-*.php                       ← Invoices
│   ├── payment-*.php                   ← Payments
│   ├── deliv-*.php                     ← Deliveries
│   ├── rep-*.php                       ← Reports
│   │
│   ├── MPDF/                           ← PDF library
│   ├── PHPMailer/                      ← Email library
│   ├── upload/                         ← File uploads
│   ├── file/                           ← File storage
│   ├── css/                            ← Stylesheets
│   ├── js/                             ← JavaScript
│   ├── images/                         ← Assets
│   └── core-function.php               ← Business logic
│
├── database/
│   ├── migrations/                     ← Schema migrations
│   └── *.sql                           ← Database dumps
│
├── docs/
│   ├── UPGRADE_PHP_MYSQL.md            ← PHP/MySQL upgrade
│   ├── TESTING_CHECKLIST.md            ← 29 test procedures
│   ├── STAGING_DEPLOYMENT_GUIDE.md     ← Staging setup
│   └── [other reference docs]
│
├── config/
│   ├── app.php                         ← App configuration
│   └── database.php                    ← DB configuration
│
└── [other support files]
```

---

## ✅ FEATURES & CAPABILITIES

### Core Modules
✅ **Company Management** - Vendor/supplier management  
✅ **Product Catalog** - Brands, categories, types, products  
✅ **Purchase Orders** - Create, edit, view, deliver  
✅ **Invoicing** - Invoice creation and management  
✅ **Payments** - Payment recording and tracking  
✅ **Deliveries** - Delivery tracking and management  
✅ **Reports** - Reporting and data export  
✅ **User Management** - Authentication and roles  

### Advanced Features
✅ PDF Generation - Tax invoices, delivery notes, reports  
✅ Email Integration - Notifications and communications  
✅ Multi-language - Thai and English support  
✅ Audit Logging - User activity tracking (to be enhanced)  
✅ File Management - Document uploads and storage  

---

## 🔐 SECURITY ROADMAP

### Current State (To Be Improved)
- Session-based authentication
- MD5 password hashing ⚠️ (insecure)
- Basic CSRF protection needed
- No comprehensive input validation
- No prepared statements everywhere

### Target State (After Phase 3)
- ✅ Bcrypt password hashing (cost 12)
- ✅ RBAC with 5 roles
- ✅ CSRF tokens on all forms
- ✅ Prepared statements (all queries)
- ✅ Input validation framework
- ✅ Security headers (X-Frame-Options, CSP, HSTS)
- ✅ Session timeout (1 hour)
- ✅ OWASP Top 10 compliant

---

## 🗄️ DATABASE INFORMATION

### Current Database
- **Name**: iacc
- **Tables**: 31 tables
- **Engine**: MySQL 5.7 → 8.0 (upgrade planned)
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci

### Database Backups
- `iacc_26122025.sql` - Latest schema (reference)
- `f2coth_iacc.sql` - Production backup
- `theiconn_cms.sql` - Secondary backup
- **Automated Backups**: To be scheduled (Phase 2)
- **Backup Location**: Daily 2 AM (cPanel)

### Schema Improvements (Phase 2)
- ✅ Add system columns: created_at, updated_at, created_by, updated_by
- ✅ Add 25+ foreign key constraints
- ✅ Create audit_log table with triggers
- ✅ Verify data integrity
- ✅ Optimize indexes

---

## 🚀 DEPLOYMENT INFORMATION

### Current: Docker Development
```bash
docker compose up -d
# Application: http://localhost:8089/iacc/
# PhpMyAdmin: http://localhost:8085
# Database: mysql:3306 (host: mysql)
```

### Target: cPanel Production
- **Server**: f2.co.th (cPanel)
- **PHP Version**: 8.3 (from 7.4)
- **MySQL Version**: 8.0 (from 5.7)
- **Deployment**: Blue-Green (zero downtime)
- **SSL/TLS**: HTTPS enabled
- **Monitoring**: 24/7 active
- **Backups**: Automated daily
- **Uptime Target**: 99.9%

---

## 📖 HOW TO PROCEED

### Step 1: Review Documentation (This Week)
1. Read [PLANNING_COMPLETE_SUMMARY.md](PLANNING_COMPLETE_SUMMARY.md) (20 min)
2. Review [PROJECT_ROADMAP_2026.md](PROJECT_ROADMAP_2026.md) (45 min)
3. Check [IMPLEMENTATION_TIMELINE.md](IMPLEMENTATION_TIMELINE.md) (30 min)
4. Keep [QUICK_REFERENCE.md](QUICK_REFERENCE.md) at your desk

### Step 2: Team Preparation (By Dec 31)
1. Assign roles and responsibilities
2. Schedule daily standup meetings
3. Verify cPanel access (WHM)
4. Confirm backup procedures ready

### Step 3: Begin Execution (January 1, 2026)
1. Start Phase 1: Tech Stack Stabilization
2. Follow day-by-day schedule from IMPLEMENTATION_TIMELINE.md
3. Run 29 tests from docs/TESTING_CHECKLIST.md
4. Monitor Go/No-Go gate milestones

### Step 4: After Each Phase
1. Verify all deliverables completed
2. Confirm Go/No-Go gate approval
3. Document any issues and resolutions
4. Update team on progress
5. Proceed to next phase

---

## 📞 KEY CONTACTS & RESOURCES

### Documentation References
- **Overall Plan**: PROJECT_ROADMAP_2026.md
- **Execution Schedule**: IMPLEMENTATION_TIMELINE.md
- **Quick Answers**: QUICK_REFERENCE.md
- **Upgrade Guide**: docs/UPGRADE_PHP_MYSQL.md
- **Testing Guide**: docs/TESTING_CHECKLIST.md
- **Deployment Guide**: DEPLOYMENT_PLAN_STEPS_1-4.md
- **Database Info**: iacc_26122025.sql

### Development Resources
- **App Config**: iacc/inc/sys.configs.php
- **Database Class**: iacc/inc/class.dbconn.php
- **Core Logic**: iacc/core-function.php (27 KB)
- **Email**: iacc/PHPMailer/
- **PDF**: iacc/MPDF/

---

## ⚠️ IMPORTANT REMINDERS

### Before You Implement
- ✅ Read the relevant planning document
- ✅ Review historical documents for context
- ✅ Understand success criteria for your phase
- ✅ Know the timeline and milestones
- ✅ Identify your role and responsibilities
- ✅ Check Go/No-Go gates

### During Implementation
- ✅ Follow the day-by-day schedule
- ✅ Reference historical documents
- ✅ Run all required tests
- ✅ Monitor success metrics
- ✅ Communicate progress daily
- ✅ Escalate blockers immediately
- ✅ Document all changes in git

### After Deployment
- ✅ Monitor logs for 24+ hours
- ✅ Watch performance metrics
- ✅ Verify backups are running
- ✅ Keep monitoring active 24/7
- ✅ Have rollback procedures ready

---

## 📊 SUCCESS METRICS

| Objective | Success Criteria | Phase | Deadline |
|-----------|------------------|-------|----------|
| Tech Stack | PHP 8.3, MySQL 8.0, all tests pass | 1 | Jan 7 |
| Database | Audit system, backups, constraints | 2 | Jan 21 |
| Security | Bcrypt 100%, RBAC 5 roles, OWASP 0 | 3 | Feb 4 |
| Deployment | Zero downtime, 99.9% uptime | 4 | Feb 18 |

---

## 🎯 FINAL NOTES

### What Makes This Plan Strong
- ✅ Comprehensive: All 4 phases fully documented
- ✅ Referenced: Based on existing work and historical documents
- ✅ Realistic: Timelines based on actual complexity
- ✅ Safe: Rollback and contingency procedures included
- ✅ Measurable: Success criteria and metrics defined
- ✅ Actionable: Day-by-day tasks with clear responsibilities
- ✅ Team-friendly: Clear roles, contacts, and processes

### Next Steps
1. Share all 6 planning documents with your team
2. Schedule team kickoff meeting (1-2 hours)
3. Review cPanel access and procedures
4. Confirm backup procedures are ready
