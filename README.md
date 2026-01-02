# iAcc - Accounting Management System

**Version**: 2.1  
**Status**: Production Ready  
**Last Updated**: January 2, 2026  
**Project Size**: 172 MB

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

---

## 🔒 Security Features (v2.1)

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

## 🔐 Authentication

- Session-based authentication
- Login: `login.php`
- Auth handler: `authorize.php`
- Session check in `index.php`
- **Password**: bcrypt with auto-migration from MD5
- **Rate limiting**: 5 attempts per 15 minutes
- **CSRF protection**: Token validation on login

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
