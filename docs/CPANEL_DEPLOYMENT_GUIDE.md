# iACC - cPanel Production Deployment Guide

## 📋 Pre-Deployment Checklist

Before deploying to cPanel, ensure you have:

- [ ] cPanel login credentials
- [ ] FTP/SFTP access or File Manager access
- [ ] MySQL database created in cPanel
- [ ] MySQL user created with full privileges
- [ ] Domain/subdomain configured
- [ ] SSL certificate installed (recommended)

---

## 🚀 Deployment Steps

### Step 1: Prepare Database

1. **Login to cPanel** → MySQL Databases
2. **Create Database**: 
   - Name: `yourusername_iacc`
3. **Create User**:
   - Username: `yourusername_iaccuser`
   - Password: Generate a strong password
4. **Add User to Database** with ALL PRIVILEGES
5. **Note down**:
   - Database name: `yourusername_iacc`
   - Username: `yourusername_iaccuser`
   - Password: `your_password`

### Step 2: Upload Files

#### Option A: Using File Manager (Recommended for small updates)

1. Login to cPanel → File Manager
2. Navigate to `public_html` (or your domain folder)
3. Upload all files from your local project

#### Option B: Using Git (Recommended for version control)

1. Login to cPanel → Terminal (or SSH)
2. Navigate to your domain folder:
   ```bash
   cd ~/public_html
   ```
3. Clone the repository:
   ```bash
   git clone https://github.com/psinthorn/iacc-php-mvc.git .
   ```
4. For updates:
   ```bash
   git pull origin main
   ```

#### Option C: Using FTP/SFTP

1. Use FileZilla or similar FTP client
2. Connect using cPanel FTP credentials
3. Upload all files to `public_html`

### Step 3: Configure Database Connection

1. Navigate to `public_html/inc/`
2. **Rename** `sys.configs.cpanel.php` to `sys.configs.php`:
   ```bash
   mv sys.configs.cpanel.php sys.configs.php
   ```
3. **Edit** `sys.configs.php` and update:
   ```php
   $config["hostname"] = "localhost";
   $config["username"] = "yourusername_iaccuser";
   $config["password"] = "your_database_password";
   $config["dbname"]   = "yourusername_iacc";
   ```

### Step 4: Import Database

1. Login to cPanel → phpMyAdmin
2. Select your database (`yourusername_iacc`)
3. Click **Import** tab
4. Choose file: `iacc_07012026.sql` (or latest SQL file)
5. Click **Go** to import

### Step 5: Configure .htaccess

1. Rename `.htaccess.cpanel` to `.htaccess`:
   ```bash
   mv .htaccess.cpanel .htaccess
   ```
2. If you have SSL, uncomment the HTTPS redirect section

### Step 6: Set Folder Permissions

```bash
chmod 755 public_html
chmod 755 public_html/inc
chmod 755 public_html/logs
chmod 755 public_html/cache
chmod 755 public_html/file
chmod 755 public_html/upload
chmod 644 public_html/inc/sys.configs.php
chmod 644 public_html/.htaccess
```

Or via File Manager:
- `inc/`, `logs/`, `cache/`, `file/`, `upload/` → 755
- `inc/sys.configs.php` → 644

### Step 7: Create Required Directories

If not exist, create:
```bash
mkdir -p logs
mkdir -p cache
mkdir -p backups
touch logs/.htaccess
echo "Deny from all" > logs/.htaccess
```

### Step 8: Test the Application

1. Visit your domain: `https://yourdomain.com`
2. Login with default admin credentials
3. **Change the admin password immediately!**

---

## 🔒 Security Hardening

### 1. Enable HTTPS

In `.htaccess`, uncomment:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Secure Cookies

In `inc/sys.configs.php`, ensure:
```php
ini_set('session.cookie_secure', 1);  // Already enabled
```

### 3. Change Default Credentials

1. Login as admin
2. Go to Settings → Profile
3. Change password

### 4. Configure AI Settings (Optional)

If using AI features:
1. Go to AI Settings
2. Enter your OpenAI API key
3. Test the connection

---

## 📁 Files to Exclude from Upload

Do NOT upload these files/folders to production:

```
.git/
.venv/
docker/
docker-compose.yml
docker-compose.prod.yml
Dockerfile
*.bak
*.backup
*.log
TODAY_WORK_SUMMARY*.txt
phpstan.neon
```

---

## 🔄 Updating Production

### Quick Update (via Git)

```bash
cd ~/public_html
git stash           # Save any local changes
git pull origin main
git stash pop       # Restore local changes (if needed)
```

### Manual Update

1. Backup current files
2. Upload new files via FTP
3. Run database migrations if needed

### Database Migrations

If there are schema changes:
1. Check `migrations/` folder for new files
2. Run SQL scripts in phpMyAdmin in order

---

## 🐛 Troubleshooting

### Error: Database Connection Failed

1. Verify credentials in `sys.configs.php`
2. Check database name format: `cpanelusername_dbname`
3. Ensure user has privileges on the database

### Error: 500 Internal Server Error

1. Check `logs/php_errors.log`
2. Verify PHP version (requires PHP 7.4+)
3. Check `.htaccess` syntax

### Error: Permission Denied

```bash
chmod 755 public_html
chmod -R 755 public_html/logs
chmod -R 755 public_html/cache
```

### Session Issues

1. Check PHP session save path
2. Ensure cookies are enabled
3. Clear browser cache

---

## 📞 Support

For issues, check:
1. Error logs: `logs/php_errors.log`
2. PHP error log in cPanel
3. GitHub Issues: https://github.com/psinthorn/iacc-php-mvc/issues

---

## 📝 Post-Deployment Checklist

- [ ] Application loads correctly
- [ ] Can login with admin account
- [ ] Changed default admin password
- [ ] SSL certificate working (HTTPS)
- [ ] Database operations working
- [ ] File uploads working
- [ ] PDF generation working
- [ ] Email sending working (if configured)
- [ ] AI chatbot working (if configured)
- [ ] Error logging to `logs/` folder
