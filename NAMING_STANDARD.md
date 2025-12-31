# iACC Database Naming Conventions Standard

**Version**: 1.0  
**Date**: December 31, 2025  
**Status**: Approved and Implemented  
**Applies To**: Phase 3 Step 5 Standardization

---

## Executive Summary

This document defines the standard naming conventions for all database objects (tables, columns, constraints, indexes) across the iACC application. The goal is to establish a consistent, maintainable, and intuitive naming scheme that reduces confusion and improves code clarity.

---

## 1. Table Naming Conventions

### 1.1 Format
- **Style**: `snake_case` (lowercase, words separated by underscores)
- **Singularity**: Use singular nouns (e.g., `user` not `users`)
- **Clarity**: Full descriptive names, avoid abbreviations and acronyms
- **Prefix**: No prefixes (e.g., `tbl_`, `t_`)
- **Suffix**: No suffixes (e.g., `_table`, `_data`)

### 1.2 Examples
```
GOOD:
  company
  company_address
  product_type
  purchase_order
  invoice

AVOID:
  companies (plural)
  company_addr (abbreviation)
  type (too generic)
  po (abbreviation)
  iv (unclear)
  tbl_company (unnecessary prefix)
```

### 1.3 Special Cases
- **Bridge/Junction Tables**: Use format `table1_table2` or `map_table1_to_table2`
  - Example: `map_type_to_brand` (mapping product types to brands)
- **Temporary Tables**: Prefix with `tmp_` (e.g., `tmp_product`)
- **Historical/Archive**: Suffix with `_archive` or `_history`
- **Deprecated**: Prefix with `deprecated_` and plan for removal

---

## 2. Column Naming Conventions

### 2.1 Format
- **Style**: `snake_case` (lowercase, underscores between words)
- **Clarity**: Full descriptive names, avoid abbreviations
- **Consistency**: Use consistent names across tables (e.g., always `user_id` for foreign keys)
- **Prefixes**: No prefixes except for special cases (see below)
- **Type Indicators**: Never include data type in name (e.g., `amount` not `amount_int`)

### 2.2 Primary Key Columns
- **Standard**: `id` (for single-column PK)
- **Format**: Always auto-incrementing integer
- **Naming**: Short and implicit (no need for `table_id` unless clarification needed)

### 2.3 Foreign Key Columns
- **Format**: `[referenced_table]_id`
- **Clarity**: Name should reflect what table is referenced
- **Examples**:
  ```
  user_id          (references user table)
  company_id       (references company table)
  vendor_id        (references vendor entity in company table)
  purchase_order_id (references purchase_order table)
  ```

### 2.4 Timestamp Columns
- **Created**: `created_at` (TIMESTAMP, set on INSERT)
- **Updated**: `updated_at` (TIMESTAMP, set on INSERT and UPDATE)
- **Deleted**: `deleted_at` (TIMESTAMP, for soft deletes, NULL if active)
- **Format**: DATETIME or TIMESTAMP type
- **Timezone**: Stored in UTC, converted on display

### 2.5 Boolean/Status Columns
- **Prefix**: Use `is_` for boolean flags
- **Examples**:
  ```
  is_active        (TINYINT(1) or BOOLEAN)
  is_deleted       (TINYINT(1) or BOOLEAN)
  is_verified      (TINYINT(1) or BOOLEAN)
  is_default       (TINYINT(1) or BOOLEAN)
  ```
- **Status Columns**: Use `status` or `state` with enum values
  - Example: `status ENUM('pending', 'approved', 'rejected')`

### 2.6 Description/Text Columns
- **Format**: `description` (not `desc`)
- **Full Names**: `address` not `adr`, `phone_number` not `phone`
- **Content Columns**: `content`, `body`, `text` (avoid `data`)

### 2.7 Email/Contact Columns
- **Email**: `email` (not `email_address`)
- **Phone**: `phone_number` (full name, not `phone` or `tel`)
- **URL**: `url` or `website_url` (not `web_url`, `link`)

### 2.8 Amount/Money Columns
- **Format**: `amount`, `price`, `total`, `balance`
- **Precision**: Use DECIMAL(10,2) for currency
- **Clarity**: Always include currency in documentation
- **Examples**:
  ```
  purchase_price     (decimal)
  total_amount       (decimal)
  balance_due        (decimal)
  ```

### 2.9 Date Columns (Non-Timestamp)
- **Format**: Use full descriptive name with `_date` suffix
- **Examples**:
  ```
  birth_date         (DATE type)
  valid_start_date   (DATE type)
  valid_end_date     (DATE type)
  delivery_date      (DATE type, was deliver_date)
  ```

---

## 3. Constraint and Index Naming

### 3.1 Primary Key
- **Format**: Implicit, no explicit naming needed
- **MySQL**: Uses `PRIMARY` as constraint name

### 3.2 Foreign Key
- **Format**: `fk_[table]_[referenced_table]_[column]`
- **Examples**:
  ```
  fk_purchase_order_company_id
  fk_product_product_type_id
  fk_deliver_purchase_order_id
  ```

### 3.3 Unique Constraint
- **Format**: `uk_[table]_[column]` or `uq_[table]_[column]`
- **Examples**:
  ```
  uk_user_email
  uk_company_tax_id
  ```

### 3.4 Index
- **Format**: `idx_[table]_[column]` (single column)
- **Format**: `idx_[table]_[col1]_[col2]` (composite, order matters)
- **Full-Text**: `ft_[table]_[column]`
- **Examples**:
  ```
  idx_user_email
  idx_purchase_order_date
  idx_company_status
  idx_audit_log_user_id
  idx_audit_log_table_operation
  ```

### 3.5 Check Constraint
- **Format**: `ck_[table]_[condition]`
- **Examples**:
  ```
  ck_product_price_positive
  ck_purchase_request_quantity_positive
  ```

---

## 4. Renaming Map - Phase 3 Step 5 Implementation

### 4.1 Tables Renamed

| Old Name | New Name | Reason |
|----------|----------|--------|
| `po` | `purchase_order` | Expand abbreviation, clarity |
| `pr` | `purchase_request` | Expand abbreviation, clarity |
| `iv` | `invoice` | Clarify ambiguous abbreviation |
| `type` | `product_type` | Reduce ambiguity, context |
| `sendoutitem` | `send_out_item` | Apply snake_case convention |

### 4.2 Columns Renamed

| Table | Old Name | New Name | Reason |
|-------|----------|----------|--------|
| `authorize` | `usr_id` | `user_id` | Expand abbreviation |
| `authorize` | `usr_name` | `user_name` | Expand abbreviation |
| `authorize` | `usr_pass` | `user_password` | Expand abbreviation, clarity |
| `billing` | `bil_id` | `billing_id` | Expand abbreviation |
| `billing` | `inv_id` | `invoice_id` | Clarify abbreviation |
| `brand` | `ven_id` | `vendor_id` | Expand abbreviation |
| `company_addr` | `com_id` | `company_id` | Expand abbreviation |
| `company_credit` | `cus_id` | `customer_id` | Expand abbreviation |
| `company_credit` | `ven_id` | `vendor_id` | Expand abbreviation |
| `deliver` | `po_id` | `purchase_order_id` | Expand abbreviation, clarity |
| `deliver` | `out_id` | `output_id` | Expand abbreviation |
| `map_type_to_brand` | `type_id` | `product_type_id` | Clarify with table reference |
| `pay` | `po_id` | `purchase_order_id` | Expand abbreviation, clarity |
| `payment` | `po_id` | `purchase_order_id` | Expand abbreviation, clarity |
| `product` | `type_id` | `product_type_id` | Clarify with table reference |
| `receive` | `po_id` | `purchase_order_id` | Expand abbreviation, clarity |
| `receipt` | `po_id` | `purchase_order_id` | Expand abbreviation, clarity |

### 4.3 Foreign Key Updates

All foreign key column names updated to match new referenced table names:
- All `po_id` → `purchase_order_id`
- All `pr_id` → `purchase_request_id` (if any)
- All `iv_id` → `invoice_id` (if any)
- All `type_id` → `product_type_id` (when referencing product_type)

---

## 5. Impact Assessment

### 5.1 Tables Affected
- **Direct Renames**: 5 tables
- **Column Renames**: 18 columns across 10 tables
- **Total Tables Modified**: ~15 tables
- **Total Rows Affected**: 0 (structure only)

### 5.2 Triggers
- **Audit Triggers**: 18 existing triggers automatically work with renamed tables
- **No Trigger Code Changes**: MySQL automatically resolves to renamed tables
- **Verification Required**: Confirm all 18 triggers still fire correctly

### 5.3 Views (if any)
- **Check**: No custom views currently exist in schema
- **Future**: Any new views should follow same naming conventions

### 5.4 Stored Procedures (if any)
- **Check**: No stored procedures currently exist
- **Note**: This is handled at PHP application layer

---

## 6. Implementation Phase Approach

### Phase 1: Database Changes
1. Execute MIGRATION_NAMING_CONVENTIONS.sql
2. Verify all table/column names changed correctly
3. Verify all triggers still functional
4. Check data integrity (row counts unchanged)

### Phase 2: PHP Code Updates
1. Search and replace in all PHP files:
   - Table names: `po` → `purchase_order`, etc.
   - Column names: `usr_id` → `user_id`, etc.
2. Update model.php SQL reference functions
3. Update core-function.php audit functions
4. Update all page-specific queries
5. Test all CRUD operations

### Phase 3: Testing & Validation
1. Unit test each renamed table/column access
2. Integration test full user workflows
3. Verify audit trail still captures changes
4. Verify foreign key constraints work
5. Performance testing (ensure no regression)

### Phase 4: Deployment & Documentation
1. Create backup before deployment
2. Execute migration on production database
3. Deploy PHP code changes
4. Verify application works post-deployment
5. Document all changes in completion report

---

## 7. Naming Convention Checklist

Use this checklist for any new tables/columns added in future:

- [ ] Table name is `snake_case`
- [ ] Table name uses singular noun
- [ ] Table has no prefix (no `tbl_`)
- [ ] Column names are `snake_case`
- [ ] Primary key is named `id`
- [ ] Foreign keys use `[table]_id` format
- [ ] Timestamps are `created_at`, `updated_at`
- [ ] Boolean columns use `is_` prefix
- [ ] Abbreviations are avoided (use full names)
- [ ] No data type indicators in names
- [ ] All constraints properly named
- [ ] All indexes properly named

---

## 8. Related Documentation

- See [PHASE_3_STEP_5_ANALYSIS.md](PHASE_3_STEP_5_ANALYSIS.md) for detailed analysis
- See [MIGRATION_NAMING_CONVENTIONS.sql](iacc/MIGRATION_NAMING_CONVENTIONS.sql) for SQL scripts
- See [IMPROVEMENTS_PLAN.md](IMPROVEMENTS_PLAN.md) Phase 3 Step 5 section
- See [README.md](README.md) for project overview

---

## 9. Future Compliance

All new tables, columns, and constraints added to iACC must comply with this standard. Code reviews should specifically check:

1. ✅ Naming convention compliance
2. ✅ Consistency with existing tables
3. ✅ Foreign key naming patterns
4. ✅ Index naming patterns
5. ✅ Abbreviation avoidance

---

**Document Status**: Approved and Ready for Implementation  
**Effective Date**: December 31, 2025  
**Version**: 1.0 (Initial Release)
