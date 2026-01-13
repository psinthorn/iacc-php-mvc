# iACC - GitHub Actions CI/CD Setup Guide

## 🎯 Overview

This guide explains how to set up automated deployment using GitHub Actions for the iACC application.

### Deployment Pipeline

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   develop   │────►│   staging   │     │             │
│   branch    │     │   server    │     │             │
└─────────────┘     └─────────────┘     │             │
       │                                 │   cPanel    │
       ▼                                 │   Server    │
┌─────────────┐     ┌─────────────┐     │             │
│    main     │────►│  production │     │             │
│   branch    │     │   server    │     │             │
└─────────────┘     └─────────────┘     └─────────────┘
```

| Branch | Environment | URL | Auto Deploy |
|--------|-------------|-----|-------------|
| `develop` | Staging | dev.iacc.f2.co.th | ✅ On push |
| `main` | Production | iacc.f2.co.th | ✅ On push |

---

## 📋 Setup Steps

### Step 1: Create GitHub Environments

1. Go to your repository on GitHub
2. Click **Settings** → **Environments**
3. Create two environments:

#### Production Environment
- Name: `production`
- Protection rules (recommended):
  - ✅ Required reviewers: Add at least 1 reviewer
  - ✅ Wait timer: 0 minutes (or add delay for safety)
  - Deployment branches: `main` only

#### Staging Environment
- Name: `staging`
- No protection rules needed (auto-deploy for testing)
- Deployment branches: `develop` only

### Step 2: Add Repository Secrets

Go to **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Add the following secrets:

#### For Production (iacc.f2.co.th)
| Secret Name | Value | Description |
|-------------|-------|-------------|
| `FTP_SERVER` | `ftp.f2.co.th` | Your cPanel FTP server |
| `FTP_USERNAME` | `your_ftp_user` | FTP username for production |
| `FTP_PASSWORD` | `your_ftp_password` | FTP password for production |

#### For Staging (dev.iacc.f2.co.th)
| Secret Name | Value | Description |
|-------------|-------|-------------|
| `FTP_USERNAME_STAGING` | `your_staging_ftp_user` | FTP username for staging |
| `FTP_PASSWORD_STAGING` | `your_staging_ftp_password` | FTP password for staging |

### Step 3: Set Up cPanel Subdomains

#### Production Subdomain
1. Login to cPanel
2. Go to **Subdomains** or **Domains**
3. Ensure `iacc.f2.co.th` points to `public_html/` or appropriate folder

#### Staging Subdomain (Optional but Recommended)
1. Create subdomain: `dev.iacc.f2.co.th`
2. Point to: `dev.iacc.f2.co.th/` folder
3. Create the folder if it doesn't exist

### Step 4: Create Staging Database

1. In cPanel → **MySQL Databases**
2. Create database: `username_iacc_dev`
3. Create user: `username_iaccdev`
4. Add user to database with ALL PRIVILEGES
5. Import production database for testing (optional)

### Step 5: Configure Database on Servers

#### Production Server
After first deployment, SSH or use File Manager to edit:
```
public_html/inc/sys.configs.php
```

Update with production database credentials:
```php
$config["hostname"] = "localhost";
$config["username"] = "f2coth_iaccuser";
$config["password"] = "your_production_password";
$config["dbname"]   = "f2coth_iacc";
```

#### Staging Server
Same process for staging:
```
dev.iacc.f2.co.th/inc/sys.configs.php
```

Update with staging database credentials:
```php
$config["hostname"] = "localhost";
$config["username"] = "f2coth_iaccdev";
$config["password"] = "your_staging_password";
$config["dbname"]   = "f2coth_iacc_dev";
```

---

## 🚀 Using the CI/CD Pipeline

### Daily Development Workflow

```bash
# 1. Start new feature from develop
git checkout develop
git pull origin develop
git checkout -b feature/my-new-feature

# 2. Work on your feature
# ... make changes ...
git add .
git commit -m "feat: add new feature"
git push origin feature/my-new-feature

# 3. Create Pull Request to develop
# Go to GitHub → Create PR → Review → Merge

# 4. Automatic staging deployment triggers!
# Check: https://dev.iacc.f2.co.th
```

### Release to Production

```bash
# 1. When staging is tested and ready
# Create Pull Request: develop → main

# 2. Get approval and merge

# 3. Automatic production deployment triggers!
# Check: https://iacc.f2.co.th

# 4. Tag the release
git checkout main
git pull origin main
git tag v4.9.0
git push origin v4.9.0
```

### Emergency Hotfix

```bash
# 1. Create hotfix from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-fix

# 2. Make the fix
git commit -m "fix: critical production bug"
git push origin hotfix/critical-fix

# 3. Create PR to main (urgent)
# Merge immediately after review

# 4. Also merge to develop to sync
git checkout develop
git merge hotfix/critical-fix
git push origin develop
```

---

## 📊 Monitoring Deployments

### Check Deployment Status

1. Go to repository → **Actions** tab
2. See all workflow runs
3. Click on a run to see details

### Workflow Status Badges

Add to README.md:
```markdown
![Production Deploy](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-production.yml/badge.svg)
![Staging Deploy](https://github.com/psinthorn/iacc-php-mvc/actions/workflows/deploy-staging.yml/badge.svg)
```

---

## 🔧 Troubleshooting

### FTP Connection Failed

1. Verify FTP server address
2. Check username/password in secrets
3. Ensure FTP is enabled on cPanel
4. Try using SFTP if available

### Deployment Succeeded but Site Not Updated

1. Clear browser cache
2. Check if correct folder is being deployed to
3. Verify `.htaccess` is correct
4. Check PHP error logs

### Database Connection Error After Deploy

1. Verify `sys.configs.php` has correct credentials
2. Check database user has proper privileges
3. Ensure database name follows cPanel format

---

## 📁 Files Reference

| File | Purpose |
|------|---------|
| `.github/workflows/deploy-production.yml` | Production deployment workflow |
| `.github/workflows/deploy-staging.yml` | Staging deployment workflow |
| `inc/sys.configs.production.php` | Production config template |
| `inc/sys.configs.staging.php` | Staging config template |
| `docs/BRANCHING_STRATEGY.md` | Branch naming conventions |

---

## 🔐 Security Notes

1. **Never commit secrets** - Use GitHub Secrets
2. **Protect main branch** - Require PR reviews
3. **Use separate databases** - Staging ≠ Production
4. **Enable HTTPS** - Both staging and production
5. **Rotate passwords** - Change FTP passwords regularly
