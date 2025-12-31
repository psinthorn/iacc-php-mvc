# Phase 3 Step 5: Naming Conventions Standardization - Analysis Document

**Date**: December 31, 2025  
**Status**: Analysis in Progress

## Current Database State Analysis

### All 31 Tables in iacc Database

1. **authorize** - Inconsistent: `usr_id` (abbr), `usr_name` (abbr), `usr_pass` (abbr)
2. **band** - DEPRECATED (renamed to `brand` in Phase 2)
3. **billing** - Inconsistent: `bil_id` (abbr), `inv_id` (abbr)
4. **board** - Inconsistent: `board_id`, `board_group_id`, `board_name`, `board_detail`, `board_no`, `board_status`
5. **board1** - DEPRECATED: Numbered table, should use snake_case
6. **board2** - DEPRECATED: Numbered table, should use snake_case
7. **board_group** - Good: `board_group_date` (could be simplified)
8. **brand** - Good: Standard table name
9. **category** - Good: Standard table name
10. **company** - Good: Standard table name
11. **company_addr** - Good: snake_case
12. **company_credit** - Good: snake_case
13. **deliver** - Good: Standard table name
14. **gen_serial** - Good: snake_case
15. **iv** - BAD: Too short, unclear meaning
16. **keep_log** - Good: snake_case
17. **map_type_to_brand** - Good: snake_case, descriptive
18. **model** - Good: Standard table name
19. **pay** - Good: Standard table name
20. **payment** - Good: Standard table name
21. **po** - BAD: Abbreviation (Purchase Order), should be `purchase_order`
22. **pr** - BAD: Abbreviation (Purchase Request), should be `purchase_request`
23. **product** - Good: Standard table name
24. **receipt** - Good: Standard table name
25. **receive** - Good: Standard table name
26. **sendoutitem** - BAD: camelCase, should be `send_out_item`
27. **store** - Good: Standard table name
28. **store_sale** - Good: snake_case
29. **tmp_product** - Good: snake_case, indicates temporary
30. **type** - BAD: Too generic, context unclear
31. **user** - Good: Standard table name
32. **voucher** - Good: Standard table name

### Column Naming Issues Identified

#### Abbreviation Problems
- `usr_id`, `usr_name`, `usr_pass` (authorize table)
- `bil_id`, `inv_id` (billing table)
- `ven_id` (band/brand table)

#### Table Name Issues
- `po` → should be `purchase_order`
- `pr` → should be `purchase_request`
- `iv` → unclear, possibly `invoice` or need clarification
- `type` → too generic, should be `product_type`
- `sendoutitem` → should be `send_out_item`
- `band` → already renamed to `brand` (Phase 2)
- `board1`, `board2` → should be `board_reply` and `board_comment`?
- `board_group` → good, but columns could be simplified

### Naming Convention Standard to Apply

#### Table Naming Rules
- **Format**: `snake_case` (lowercase, underscores for word separation)
- **Singularity**: Use singular nouns (`user` not `users`)
- **Clarity**: Full names, avoid abbreviations
- **Domain**: No generic names like `type`, `status`

#### Column Naming Rules
- **Format**: `snake_case` (lowercase, underscores)
- **ID Columns**: `id` for primary key, `table_name_id` for foreign keys
- **Boolean Columns**: Prefix with `is_` (e.g., `is_active`, `is_deleted`)
- **Status Columns**: Use `status` or `state` with enum values
- **Timestamps**: `created_at`, `updated_at`, `deleted_at`
- **Generic Columns**: Avoid abbreviations (use `description` not `desc`)

#### Key Mappings Required

| Current Name | New Name | Reason |
|--------------|----------|--------|
| `po` | `purchase_order` | Expand abbreviation |
| `pr` | `purchase_request` | Expand abbreviation |
| `iv` | `invoice` | Clarify abbreviation |
| `type` | `product_type` | Reduce ambiguity |
| `sendoutitem` | `send_out_item` | Apply snake_case |
| `board1` | `board_reply` | Clarify purpose |
| `board2` | `board_comment` | Clarify purpose |
| `usr_id` | `user_id` | Expand abbreviation |
| `usr_name` | `user_name` | Expand abbreviation |
| `usr_pass` | `user_password` | Expand abbreviation |
| `bil_id` | `billing_id` | Expand abbreviation |
| `inv_id` | `invoice_id` | Clarify abbreviation |
| `ven_id` | `vendor_id` | Expand abbreviation |

### Implementation Strategy

#### Phase 1: Table Renames (Critical Path)
1. Rename `po` → `purchase_order`
2. Rename `pr` → `purchase_request`
3. Rename `iv` → `invoice`
4. Rename `type` → `product_type`
5. Rename `sendoutitem` → `send_out_item`
6. Rename `board1` → `board_reply`
7. Rename `board2` → `board_comment`

#### Phase 2: Column Renames (High Impact)
1. authorize: `usr_id` → `user_id`, `usr_name` → `user_name`, `usr_pass` → `user_password`
2. billing: `bil_id` → `billing_id`, `inv_id` → `invoice_id`
3. brand/band: `ven_id` → `vendor_id`

#### Phase 3: Update Foreign Keys
- Update all FK references to renamed tables
- Update all FK column names

#### Phase 4: PHP Code Updates
- Search and replace all table references
- Update SQL queries
- Update PHP model references
- Update core-function.php references

### Testing Strategy

1. **Pre-Migration Backup**: Full database dump
2. **Syntax Validation**: Test all ALTER TABLE statements
3. **Data Integrity**: Verify row counts before/after
4. **FK Validation**: Check all foreign key constraints
5. **Trigger Validation**: Ensure all 18 audit triggers still work
6. **PHP Testing**: Test all application features
7. **Rollback Plan**: Keep original SQL for emergency revert

---

## Next Steps

1. Create detailed renaming migration script
2. Execute database changes
3. Update all PHP files
4. Test complete application flow
5. Create completion report
6. Update README and commit

