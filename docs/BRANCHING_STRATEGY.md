# iACC - Branching Strategy & Deployment Pipeline
# ================================================
# Standard GitFlow-based branching model for team development

## 🌳 Branch Naming Convention

| Branch | Purpose | Deploys To | Auto Deploy |
|--------|---------|------------|-------------|
| `main` | Production-ready code | **production** (iacc.f2.co.th) | ✅ Yes |
| `develop` | Development/staging | **staging** (dev.iacc.f2.co.th) | ✅ Yes |
| `feature/*` | New features | - | ❌ No |
| `hotfix/*` | Emergency fixes | - | ❌ No |
| `release/*` | Release preparation | - | ❌ No |

## 🔄 Workflow

```
feature/xxx ──┐
              ├──► develop ──► release/x.x ──► main
hotfix/xxx  ──┘                                  │
     ▲                                           │
     └───────────────────────────────────────────┘
```

## 📦 Environments

### Production (main branch)
- **URL**: https://iacc.f2.co.th
- **Server**: cPanel production
- **Auto-deploy**: On push/merge to `main`
- **Database**: Production database

### Staging/Development (develop branch)  
- **URL**: https://dev.iacc.f2.co.th (optional subdomain)
- **Server**: cPanel staging folder
- **Auto-deploy**: On push/merge to `develop`
- **Database**: Staging database (copy of production)

## 🏷️ Version Tagging

Format: `v{MAJOR}.{MINOR}.{PATCH}`

Examples:
- `v4.8.0` - New minor release
- `v4.8.1` - Bug fix
- `v5.0.0` - Major version

## 📋 Branch Naming Examples

```bash
# Features
feature/billing-export
feature/ai-enhancement
feature/user-permissions

# Bug fixes (from develop)
bugfix/invoice-calculation
bugfix/login-redirect

# Hotfixes (from main - urgent production fixes)
hotfix/security-patch
hotfix/payment-error

# Releases
release/4.9.0
release/5.0.0
```

## 🚀 Deployment Flow

### 1. Development Work
```bash
# Create feature branch from develop
git checkout develop
git pull origin develop
git checkout -b feature/my-feature

# Work on feature...
git add .
git commit -m "feat: add new feature"
git push origin feature/my-feature

# Create Pull Request to develop
```

### 2. Merge to Develop (Staging Deploy)
```bash
# After PR approval, merge to develop
# GitHub Actions automatically deploys to staging
```

### 3. Release to Production
```bash
# Create release branch
git checkout develop
git checkout -b release/4.9.0

# Final testing and version bump
# Merge to main via Pull Request

# GitHub Actions automatically deploys to production
# Tag the release
git tag v4.9.0
git push origin v4.9.0
```

### 4. Hotfix (Emergency)
```bash
# Create hotfix from main
git checkout main
git checkout -b hotfix/critical-bug

# Fix the issue
git commit -m "fix: critical bug"

# Merge to main AND develop
# Create PR to main (deploys to production)
# Create PR to develop (sync the fix)
```

## 🔐 Protected Branches

| Branch | Protection Rules |
|--------|------------------|
| `main` | Require PR, require review, no force push |
| `develop` | Require PR, no force push |

## 📝 Commit Message Convention

Format: `type: description`

Types:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation
- `style:` - Formatting, no code change
- `refactor:` - Code refactoring
- `test:` - Adding tests
- `chore:` - Maintenance tasks

Examples:
```
feat: add billing note delete function
fix: correct invoice calculation
docs: update deployment guide
refactor: improve database queries
```
