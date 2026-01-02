# Project Cleanup Analysis - iAcc-PHP-MVC

## Current Status
- **Total Size**: 543 MB
- **Commit**: f1accc4 (working version with Dockerfile, MailHog, fixed DB connection)

## Identified Cleanup Opportunities

### 1. **php-source/** - DUPLICATE APPLICATION (190 MB) ⚠️ HIGH PRIORITY
   - Complete copy of iacc/ directory
   - Not referenced in code
   - Safe to delete: YES
   - **Savings: 190 MB**

### 2. **iacc/MPDF57-7/** - OBSOLETE LIBRARY (31 MB) ⚠️ MEDIUM PRIORITY
   - Old version 5.7-7, superseded by iacc/MPDF/
   - No code references
   - Safe to delete: YES
   - **Savings: 31 MB**

### 3. **iacc/file/** - USER UPLOADS (87 MB) ⚠️ REVIEW NEEDED
   - Contains user-uploaded files
   - Consider archiving or migrating to cloud storage
   - Safe to delete: MAYBE (depends on business requirements)
   - **Potential savings: 87 MB**

### 4. **f2coth_iacc.sql** - OLD DATABASE BACKUP (2.2 MB) ✓ SAFE
   - Old SQL backup file
   - Different database name than current (iacc_cms.sql is used)
   - Safe to delete: YES
   - **Savings: 2.2 MB**

### 5. **iacc/upload/** - USER UPLOADS (2.2 MB) ✓ SAFE
   - Active upload directory
   - Keep as-is (lightweight)
   - Safe to delete: NO (active data)

## Total Potential Cleanup: 256 MB (47% reduction)

### Conservative Cleanup (220 MB - 41%):
1. Remove php-source/ (190 MB)
2. Remove MPDF57-7/ (31 MB)
3. Remove f2coth_iacc.sql (2.2 MB)
⚠️ Keep iacc/file/ (need confirmation)

### Aggressive Cleanup (307 MB - 57%):
1. Remove php-source/ (190 MB)
2. Remove MPDF57-7/ (31 MB)
3. Remove f2coth_iacc.sql (2.2 MB)
4. Archive iacc/file/ (87 MB)

## Recommendation
Start with **Conservative Cleanup** (remove obvious duplicates/obsolete files).
Archive iacc/file/ separately if storage is critical.

