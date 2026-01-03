# iAcc Database Data Dictionary

**Version:** 1.0  
**Created:** 2026-01-03  
**Database:** MySQL 5.7.44 (InnoDB)  
**Character Set:** utf8mb4_unicode_ci  

---

## Table of Contents

1. [Naming Convention Standards](#naming-convention-standards)
2. [Table Dictionary](#table-dictionary)
3. [Column Dictionary by Table](#column-dictionary-by-table)
4. [Proposed Naming Changes](#proposed-naming-changes)
5. [Abbreviation Reference](#abbreviation-reference)

---

## Naming Convention Standards

### Recommended Standards (for new development)

| Element | Convention | Example |
|---------|------------|---------|
| Table names | snake_case, plural | `purchase_requests`, `invoices` |
| Column names | snake_case | `created_at`, `customer_id` |
| Primary keys | `id` | `id` |
| Foreign keys | `{table_singular}_id` | `customer_id`, `vendor_id` |
| Boolean columns | `is_` or `has_` prefix | `is_active`, `has_payment` |
| Date columns | `_at` or `_date` suffix | `created_at`, `delivery_date` |
| Junction tables | `{table1}_{table2}` | `user_roles`, `type_brands` |

---

## Table Dictionary

### Core Business Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `pr` | Purchase Requests - Initial purchase request from customer | `purchase_requests` | 🔴 High |
| `po` | Purchase Orders - Confirmed orders with pricing | `purchase_orders` | 🔴 High |
| `iv` | Invoices - Tax invoice records | `invoices` | 🔴 High |
| `pay` | Payments - Payment records for orders | `payments` | 🔴 High |
| `product` | Products - Line items in orders | `order_items` or keep `products` | 🟡 Medium |
| `deliver` | Deliveries - Delivery records | `deliveries` | 🟡 Medium |
| `receive` | Receipts of delivery | `delivery_receipts` | 🟡 Medium |

### Master Data Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `company` | Companies (customers/vendors) | ✅ Keep | - |
| `company_addr` | Company addresses | `company_addresses` | 🟢 Low |
| `company_credit` | Company credit limits | `company_credits` | 🟢 Low |
| `brand` | Product brands | ✅ Keep | - |
| `type` | Product types/categories | `product_types` | 🟡 Medium |
| `category` | Product categories | `product_categories` | 🟡 Medium |
| `model` | Product models | `product_models` | 🟡 Medium |
| `map_type_to_brand` | Type-Brand associations | `type_brands` | 🟢 Low |

### User & Authentication Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `authorize` | User accounts (login) | `users` | 🔴 High |
| `user` | User profiles (legacy) | `user_profiles` | 🟡 Medium |
| `roles` | User roles | ✅ Keep | - |
| `permissions` | System permissions | ✅ Keep | - |
| `user_roles` | User-Role assignments | ✅ Keep | - |
| `role_permissions` | Role-Permission assignments | ✅ Keep | - |
| `login_attempts` | Login attempt tracking | ✅ Keep | - |
| `password_resets` | Password reset tokens | ✅ Keep | - |
| `remember_tokens` | Remember me tokens | ✅ Keep | - |

### Payment Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `payment` | Payment methods (legacy) | Merge with `payment_methods` | 🟡 Medium |
| `payment_method` | Payment methods (new) | ✅ Keep | - |
| `payment_methods` | Vendor payment methods | `vendor_payment_methods` | 🟡 Medium |
| `payment_gateway_config` | Gateway configurations | ✅ Keep | - |
| `payment_log` | Payment transaction logs | `payment_logs` | 🟢 Low |

### Document Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `receipt` | Sales receipts | `sales_receipts` | 🟡 Medium |
| `voucher` | Sales vouchers | `sales_vouchers` | 🟡 Medium |
| `billing` | Billing records | `billings` | 🟢 Low |

### Inventory Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `store` | Stock/inventory items | `inventory` or `stock_items` | 🟡 Medium |
| `store_sale` | Stock sales/transfers | `inventory_movements` | 🟡 Medium |
| `gen_serial` | Serial number generator | `serial_sequences` | 🟢 Low |
| `sendoutitem` | Outbound shipments | `shipments` | 🟡 Medium |

### System Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `audit_log` | Audit trail (singular) | Merge with `audit_logs` | 🟡 Medium |
| `audit_logs` | Audit trail (plural) | ✅ Keep | - |
| `keep_log` | System logs (legacy) | Remove or merge | 🟢 Low |
| `_migration_log` | Migration tracking | ✅ Keep | - |

### Forum/Board Tables (Legacy?)

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `board` | Forum boards | `forum_boards` or Remove | 🟢 Low |
| `board1` | Forum topics | `forum_topics` or Remove | 🟢 Low |
| `board2` | Forum posts | `forum_posts` or Remove | 🟢 Low |
| `board_group` | Forum groups | `forum_groups` or Remove | 🟢 Low |

### Temporary Tables

| Current Name | Description | Proposed Name | Priority |
|--------------|-------------|---------------|----------|
| `tmp_product` | Temporary product data | Remove or `temp_order_items` | 🟢 Low |

---

## Column Dictionary by Table

### `pr` (Purchase Requests)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `name` | varchar(30) | Request name/title | `title` |
| `des` | mediumtext | Description | `description` |
| `usr_id` | int(11) | Created by user | `created_by_user_id` |
| `cus_id` | int(11) | Customer company ID | `customer_id` |
| `ven_id` | int(11) | Vendor company ID | `vendor_id` |
| `date` | date | Request date | `request_date` |
| `status` | varchar(1) | Status code | `status` (consider enum) |
| `cancel` | int(11) | Cancelled flag | `is_cancelled` (boolean) |
| `mailcount` | int(11) | Email sent count | `email_count` |
| `payby` | int(11) | Payment method | `payment_method_id` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `po` (Purchase Orders)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `po_id_new` | varchar(11) | Revised PO reference | `revised_order_id` |
| `name` | varchar(30) | Order name | `title` |
| `ref` | int(11) | PR reference | `purchase_request_id` |
| `tax` | varchar(15) | Tax invoice number | `tax_invoice_number` |
| `date` | date | Order date | `order_date` |
| `valid_pay` | date | Payment due date | `payment_due_date` |
| `deliver_date` | date | Expected delivery | `expected_delivery_date` |
| `pic` | varchar(50) | Attached picture/file | `attachment_file` |
| `dis` | float | Discount percentage | `discount_percent` |
| `bandven` | int(11) | Brand vendor (typo!) | `brand_id` |
| `vat` | double | VAT percentage | `vat_percent` |
| `over` | int(11) | Overhead percentage | `overhead_percent` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `iv` (Invoices)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Sequence number | `sequence_id` |
| `tex` | int(15) | PO reference (PK!) | `purchase_order_id` |
| `cus_id` | int(11) | Customer ID | `customer_id` |
| `createdate` | date | Invoice date | `invoice_date` |
| `taxrw` | varchar(8) | Tax invoice formatted | `tax_invoice_formatted` |
| `texiv` | int(11) | Tax invoice sequence | `tax_invoice_sequence` |
| `texiv_rw` | int(11) | Tax invoice raw | `tax_invoice_number` |
| `texiv_create` | date | Tax invoice date | `tax_invoice_date` |
| `status_iv` | int(11) | Invoice status | `status` |
| `countmailinv` | int(11) | Invoice email count | `invoice_email_count` |
| `countmailtax` | int(11) | Tax email count | `tax_email_count` |
| `payment_status` | enum | Payment status | ✅ Keep |
| `payment_gateway` | varchar(50) | Gateway used | ✅ Keep |
| `payment_order_id` | varchar(100) | Gateway order ID | ✅ Keep |
| `paid_amount` | decimal(12,2) | Amount paid | ✅ Keep |
| `paid_date` | datetime | Payment date | ✅ Keep |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `product` (Order Line Items)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `pro_id` | int(11) | Primary key | `id` |
| `po_id` | int(11) | Purchase order | `purchase_order_id` |
| `price` | double | Unit price | `unit_price` |
| `discount` | double | Discount | `discount_amount` |
| `ban_id` | int(11) | Brand ID | `brand_id` |
| `model` | varchar(30) | Model reference | `model_id` |
| `type` | int(11) | Product type | `product_type_id` |
| `quantity` | varchar(10) | Quantity | `quantity` (change to int) |
| `pack_quantity` | varchar(10) | Pack quantity | `pack_quantity` (change to int) |
| `so_id` | int(11) | Sales order ID | `sales_order_id` |
| `des` | mediumtext | Description | `description` |
| `activelabour` | int(11) | Labour active flag | `has_labour` |
| `valuelabour` | double | Labour cost | `labour_cost` |
| `vo_id` | int(11) | Voucher ID | `voucher_id` |
| `vo_warranty` | date | Warranty date | `warranty_expiry_date` |
| `re_id` | int(11) | Receipt ID | `receipt_id` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `company` (Companies)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `name_en` | varchar(100) | English name | ✅ Keep |
| `name_th` | varchar(100) | Thai name | ✅ Keep |
| `name_sh` | varchar(30) | Short name | `name_short` |
| `contact` | varchar(50) | Contact person | `contact_person` |
| `email` | varchar(50) | Email | ✅ Keep |
| `phone` | varchar(100) | Phone | ✅ Keep |
| `fax` | varchar(100) | Fax | ✅ Keep |
| `tax` | varchar(20) | Tax ID | `tax_id` |
| `customer` | int(1) | Is customer flag | `is_customer` |
| `vender` | int(1) | Is vendor flag (typo!) | `is_vendor` |
| `logo` | varchar(100) | Logo file | `logo_file` |
| `term` | mediumtext | Terms | `payment_terms` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `authorize` (Users)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `email` | varchar(100) | Email (login) | ✅ Keep |
| `name` | varchar(100) | Full name | `full_name` |
| `phone` | varchar(20) | Phone | ✅ Keep |
| `password` | varchar(255) | Password hash | `password_hash` |
| `level` | int(11) | Access level (legacy) | `legacy_level` |
| `company_id` | int(11) | Company association | ✅ Keep |
| `lang` | int(11) | Language preference | `language_id` |
| `password_migrated` | tinyint(1) | Migration flag | ✅ Keep |
| `locked_until` | datetime | Account lock time | ✅ Keep |
| `failed_attempts` | int(11) | Failed login count | ✅ Keep |

### `pay` (Payments)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `po_id` | int(11) | Purchase order | `purchase_order_id` |
| `method` | int(11) | Payment method | `payment_method_id` |
| `value` | varchar(50) | Reference value | `reference_number` |
| `volumn` | double | Amount (typo!) | `amount` |
| `date` | date | Payment date | `payment_date` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

### `deliver` (Deliveries)

| Current Column | Type | Description | Proposed Name |
|----------------|------|-------------|---------------|
| `id` | int(11) | Primary key | ✅ Keep |
| `po_id` | int(11) | Purchase order | `purchase_order_id` |
| `deliver_date` | date | Delivery date | `delivery_date` |
| `out_id` | int(11) | Outbound shipment | `shipment_id` |
| `deleted_at` | datetime | Soft delete | ✅ Keep |

---

## Proposed Naming Changes

### Phase 1: Critical Tables (High Impact)

| Current | Proposed | Affected Files (Est.) |
|---------|----------|----------------------|
| `pr` | `purchase_requests` | 50+ |
| `po` | `purchase_orders` | 60+ |
| `iv` | `invoices` | 40+ |
| `authorize` | `users` | 30+ |

### Phase 2: Important Tables (Medium Impact)

| Current | Proposed | Affected Files (Est.) |
|---------|----------|----------------------|
| `pay` | `payments` | 20+ |
| `type` | `product_types` | 30+ |
| `model` | `product_models` | 25+ |

### Phase 3: Cleanup (Low Impact)

| Action | Tables |
|--------|--------|
| **Merge duplicates** | `audit_log` → `audit_logs`, `payment` → `payment_methods` |
| **Remove unused** | `board`, `board1`, `board2`, `board_group`, `keep_log`, `tmp_product` |
| **Rename for clarity** | `store` → `inventory`, `company_addr` → `company_addresses` |

---

## Abbreviation Reference

### Current Abbreviations Used

| Abbreviation | Full Meaning | Context |
|--------------|--------------|---------|
| `pr` | Purchase Request | Table name |
| `po` | Purchase Order | Table name |
| `iv` | Invoice | Table name |
| `des` | Description | Column name |
| `tex` | Tax Invoice (ID) | Column name |
| `texiv` | Tax Invoice | Column prefix |
| `cus_id` | Customer ID | Column name |
| `ven_id` | Vendor ID | Column name |
| `adr` | Address | Column prefix |
| `bil` | Billing | Column prefix |
| `dis` | Discount | Column name |
| `vat` | Value Added Tax | Column name |
| `rw` | Raw (formatted number) | Column suffix |
| `sh` | Short | Column suffix |
| `com_id` | Company ID | Column name |
| `usr_id` | User ID | Column name |
| `ban_id` | Brand ID | Column name |
| `pro_id` | Product ID | Column name |
| `rec_id` | Receipt ID | Column name |
| `rep_no` | Receipt Number | Column name |
| `vou_no` | Voucher Number | Column name |
| `st_id` | Store ID | Column name |
| `so_id` | Sales Order ID | Column name |
| `vo_id` | Voucher ID | Column name |
| `re_id` | Receipt ID | Column name |

### Typos Found

| Current | Correct | Location |
|---------|---------|----------|
| `bandven` | `brand_vendor` | po.bandven |
| `vender` | `vendor` | company.vender |
| `volumn` | `volume` or `amount` | pay.volumn |
| `createdate` | `created_at` | Multiple tables |

---

## Status Codes Reference

### `pr.status` Values

| Value | Meaning |
|-------|---------|
| `0` | Draft |
| `1` | Pending Quotation |
| `2` | Quotation Received |
| `3` | Delivered |
| `4` | Invoiced |
| `5` | Completed |

### `iv.status_iv` Values

| Value | Meaning |
|-------|---------|
| `0` | Draft |
| `1` | Active |
| `2` | Paid |

---

## Recommendations Summary

### Immediate Actions (No Breaking Changes)
1. ✅ Document all current names (this file)
2. ✅ Use new naming conventions for NEW tables only
3. ✅ Add comments to existing code explaining abbreviations

### Future Migration (Requires Planning)
1. Create database views with new names for gradual migration
2. Update PHP code incrementally
3. Run comprehensive tests before renaming

### Do NOT Rename Yet
- Wait until major version upgrade
- Requires full regression testing
- Plan for 2-4 hours downtime minimum

---

## Appendix: Full Schema Export

Run this command to get updated schema:
```bash
docker exec iacc_mysql mysqldump -uroot -proot --no-data iacc > docs/schema_$(date +%Y%m%d).sql
```
