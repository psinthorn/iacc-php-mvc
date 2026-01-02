# Band → Brand Table Rename Migration

**Purpose**: Rename `band` table to `brand` and update all references  
**Date**: December 31, 2025  
**Status**: In Progress  

## Migration Steps

### 1. Database Migration
- Rename table `band` → `brand`
- Rename column `band_name` → `name`
- Add foreign key to `company` table
- Update all related tables

### 2. PHP Code Updates
- Update SQL queries in all PHP files
- Update variable names (optional but recommended)
- Update form field names

### 3. Testing
- Test all services
- Verify no errors in logs
- Test all related features

---

## Files to Update

### PHP Files Using Band Table:
1. `brand-list.php` - List brands
2. `brand.php` - Brand management
3. `core-function.php` - Core functions
4. `deliv-edit.php` - Delivery editing
5. `deliv-make.php` - Delivery creation
6. `exp-m.php` - Export main
7. `exp.php` - Export
8. `po-make.php` - PO creation (if uses band)
9. `product-list.php` - Product list (if uses band)
10. `model.php` - Model data (if uses band)
11. `inc/sys.configs.php` - Configuration

### SQL Migration Script:
- Rename table
- Rename columns
- Update foreign keys
- Update constraints

---

## Execution Plan

See PHASE2_BRAND_MIGRATION.md for detailed steps.
