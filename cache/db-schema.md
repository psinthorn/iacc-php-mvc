# iACC Database Schema
Database: iacc
Discovered: 2026-03-30 00:40:41

## Other

### `_migration_log` (2 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(10) unsigned | PRI | NO |
| migration_name | varchar(255) | UNI | NO |
| executed_at | timestamp | - | NO |
| status | enum('success','failed','rolled_back') | - | YES |
| notes | text | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `api_invoices` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | bigint(20) | PRI | NO |
| company_id | int(11) | MUL | NO |
| subscription_id | int(11) | - | NO |
| invoice_number | varchar(50) | UNI | NO |
| plan | enum('trial','starter','professional','enterprise') | - | NO |
| period_start | date | - | NO |
| period_end | date | MUL | NO |
| orders_limit | int(11) | - | NO |
| orders_used | int(11) | - | NO |
| overage_orders | int(11) | - | NO |
| base_amount | decimal(12,2) | - | NO |
| overage_amount | decimal(12,2) | - | NO |
| total_amount | decimal(12,2) | - | NO |
| currency | varchar(10) | - | NO |
| status | enum('issued','paid','overdue','cancelled') | MUL | NO |
| issued_at | datetime | - | YES |
| due_at | datetime | - | YES |
| paid_at | datetime | - | YES |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

### `api_keys` (2 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | NO |
| subscription_id | int(11) | MUL | NO |
| key_name | varchar(100) | - | NO |
| api_key | varchar(64) | UNI | NO |
| api_secret | varchar(64) | - | NO |
| previous_key | varchar(64) | - | YES |
| previous_secret | varchar(64) | - | YES |
| grace_expires_at | datetime | - | YES |
| is_active | tinyint(1) | - | NO |
| last_used_at | datetime | - | YES |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

**Relationships:**
- `company_id` → `company.id`
- `subscription_id` → `api_subscriptions.id`

### `api_subscriptions` (2 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | UNI | NO |
| plan | enum('trial','starter','professional','enterprise') | MUL | NO |
| status | enum('active','expired','cancelled','suspended') | MUL | NO |
| orders_limit | int(11) | - | NO |
| keys_limit | int(11) | - | NO |
| channels | varchar(255) | - | NO |
| ai_providers | varchar(255) | - | NO |
| trial_start | date | - | YES |
| trial_end | date | - | YES |
| started_at | datetime | - | YES |
| expires_at | datetime | - | YES |
| enabled | tinyint(1) | - | NO |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

**Relationships:**
- `company_id` → `company.id`

### `api_usage_logs` (91 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | bigint(20) | PRI | NO |
| company_id | int(11) | MUL | YES |
| api_key_id | int(11) | MUL | YES |
| endpoint | varchar(100) | - | NO |
| channel | varchar(20) | MUL | YES |
| status_code | int(3) | - | NO |
| request_ip | varchar(45) | - | YES |
| request_body | text | - | YES |
| response_body | text | - | YES |
| processing_ms | int(11) | - | YES |
| created_at | datetime | - | NO |
| updated_at | timestamp | - | NO |

### `api_webhook_deliveries` (130 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | bigint(20) | PRI | NO |
| webhook_id | int(11) | MUL | NO |
| event | varchar(50) | - | NO |
| payload | text | - | NO |
| response_code | int(3) | - | YES |
| response_body | text | - | YES |
| duration_ms | int(11) | - | YES |
| success | tinyint(1) | - | NO |
| error | text | - | YES |
| created_at | datetime | MUL | NO |
| updated_at | timestamp | - | NO |

### `api_webhooks` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | NO |
| url | varchar(500) | - | NO |
| secret | varchar(64) | - | NO |
| events | varchar(255) | - | NO |
| is_active | tinyint(1) | MUL | NO |
| failure_count | int(11) | - | NO |
| last_triggered | datetime | - | YES |
| last_status | int(3) | - | YES |
| last_error | text | - | YES |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

### `audit_logs` (91 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| user_id | int(11) | MUL | YES |
| action | varchar(100) | MUL | NO |
| table_name | varchar(50) | MUL | YES |
| record_id | int(11) | - | YES |
| old_values | json | - | YES |
| new_values | json | - | YES |
| ip_address | varchar(45) | - | YES |
| user_agent | varchar(255) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `authorize` (10 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| email | varchar(100) | UNI | NO |
| name | varchar(100) | - | YES |
| phone | varchar(20) | - | YES |
| password | varchar(255) | - | NO |
| level | int(11) | - | NO |
| company_id | int(11) | MUL | YES |
| lang | int(11) | - | NO |
| password_migrated | tinyint(1) | - | YES |
| locked_until | datetime | - | YES |
| failed_attempts | int(11) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `billing` (3 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| bil_id | int(11) | PRI | NO |
| des | mediumtext | - | YES |
| inv_id | int(11) | - | NO |
| customer_id | int(11) | - | YES |
| price | decimal(15,2) | - | YES |
| created_at | datetime | - | YES |
| updated_at | timestamp | - | NO |

### `billing_items` (7 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| bil_id | int(11) | MUL | NO |
| inv_id | int(11) | MUL | NO |
| amount | decimal(15,2) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `channel_orders` (16 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | bigint(20) | PRI | NO |
| company_id | int(11) | MUL | NO |
| api_key_id | int(11) | - | YES |
| channel | varchar(20) | MUL | NO |
| status | enum('pending','processing','completed','failed','cancelled') | - | NO |
| guest_name | varchar(255) | - | NO |
| guest_email | varchar(255) | - | YES |
| guest_phone | varchar(50) | - | YES |
| check_in | date | MUL | YES |
| check_out | date | - | YES |
| room_type | varchar(100) | - | YES |
| guests | int(11) | - | YES |
| total_amount | decimal(12,2) | - | YES |
| currency | varchar(3) | - | NO |
| notes | text | - | YES |
| raw_data | json | - | YES |
| idempotency_key | varchar(64) | - | YES |
| linked_company_id | int(11) | - | YES |
| linked_pr_id | int(11) | - | YES |
| linked_po_id | int(11) | - | YES |
| ai_parsed | tinyint(1) | - | NO |
| ai_provider | varchar(20) | - | YES |
| ai_confidence | decimal(5,2) | - | YES |
| error_message | text | - | YES |
| processed_at | datetime | - | YES |
| created_at | datetime | MUL | NO |
| updated_at | datetime | - | NO |

**Relationships:**
- `company_id` → `company.id`

### `chart_of_accounts` (84 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | MUL | NO |
| account_code | varchar(20) | MUL | NO |
| account_name | varchar(150) | - | NO |
| account_name_th | varchar(150) | - | YES |
| account_type | enum('asset','liability','equity','revenue','expense') | MUL | NO |
| parent_id | int(11) | MUL | YES |
| level | tinyint(1) | - | NO |
| is_active | tinyint(1) | - | NO |
| description | varchar(255) | - | YES |
| normal_balance | enum('debit','credit') | - | NO |
| created_at | datetime | - | YES |
| updated_at | datetime | - | YES |
| deleted_at | datetime | - | YES |

### `company_addr` (353 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | - | NO |
| adr_tax | mediumtext | - | NO |
| city_tax | varchar(30) | - | NO |
| district_tax | varchar(30) | - | NO |
| province_tax | varchar(30) | - | NO |
| zip_tax | varchar(10) | - | NO |
| adr_bil | mediumtext | - | NO |
| city_bil | varchar(30) | - | NO |
| district_bil | varchar(30) | - | NO |
| province_bil | varchar(30) | - | NO |
| zip_bil | varchar(10) | - | NO |
| valid_start | date | - | NO |
| valid_end | date | - | NO |
| deleted_at | datetime | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `company_credit` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| cus_id | int(11) | - | NO |
| ven_id | int(11) | - | NO |
| limit_credit | varchar(10) | - | NO |
| limit_day | varchar(4) | - | NO |
| valid_start | date | - | NO |
| valid_end | date | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `currencies` (10 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| code | varchar(3) | UNI | NO |
| name | varchar(100) | - | NO |
| name_th | varchar(100) | - | YES |
| symbol | varchar(10) | - | NO |
| decimal_places | tinyint(1) | - | NO |
| symbol_position | enum('before','after') | - | NO |
| is_active | tinyint(1) | - | NO |
| sort_order | int(11) | - | NO |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

### `exchange_rates` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| from_currency | varchar(3) | MUL | NO |
| to_currency | varchar(3) | - | NO |
| rate | decimal(16,6) | - | NO |
| rate_date | date | MUL | NO |
| source | varchar(50) | - | NO |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

### `expense_categories` (10 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | MUL | NO |
| name | varchar(100) | - | NO |
| name_th | varchar(100) | - | YES |
| code | varchar(20) | - | YES |
| icon | varchar(50) | - | YES |
| color | varchar(7) | - | YES |
| description | text | - | YES |
| is_active | tinyint(1) | MUL | NO |
| sort_order | int(11) | - | NO |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |
| deleted_at | datetime | - | YES |

### `expenses` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | MUL | NO |
| expense_number | varchar(30) | UNI | YES |
| category_id | int(11) | MUL | YES |
| title | varchar(255) | - | NO |
| description | text | - | YES |
| amount | decimal(15,2) | - | NO |
| vat_rate | decimal(5,2) | - | YES |
| vat_amount | decimal(15,2) | - | YES |
| wht_rate | decimal(5,2) | - | YES |
| wht_amount | decimal(15,2) | - | YES |
| net_amount | decimal(15,2) | - | NO |
| currency_code | varchar(3) | - | YES |
| exchange_rate | decimal(16,6) | - | YES |
| expense_date | date | MUL | NO |
| due_date | date | - | YES |
| paid_date | date | - | YES |
| payment_method | varchar(50) | - | YES |
| reference_no | varchar(100) | - | YES |
| vendor_name | varchar(255) | MUL | YES |
| vendor_tax_id | varchar(20) | - | YES |
| po_id | int(11) | MUL | YES |
| pr_id | int(11) | MUL | YES |
| project_name | varchar(255) | MUL | YES |
| receipt_file | varchar(255) | - | YES |
| status | enum('draft','pending','approved','paid','rejected','cancelled') | MUL | NO |
| approved_by | int(11) | - | YES |
| approved_at | datetime | - | YES |
| created_by | int(11) | - | YES |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |
| deleted_at | datetime | - | YES |

**Relationships:**
- `category_id` → `expense_categories.id`

### `gen_serial` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `journal_entries` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| journal_voucher_id | int(11) | MUL | NO |
| account_id | int(11) | MUL | NO |
| description | varchar(255) | - | YES |
| debit | decimal(15,2) | - | NO |
| credit | decimal(15,2) | - | NO |
| sort_order | int(11) | - | NO |
| created_at | datetime | - | YES |
| updated_at | timestamp | - | NO |

**Relationships:**
- `account_id` → `chart_of_accounts.id`
- `journal_voucher_id` → `journal_vouchers.id`

### `journal_vouchers` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | MUL | NO |
| jv_number | varchar(20) | MUL | NO |
| voucher_type | enum('general','payment','receipt','adjustment','opening','closing') | MUL | NO |
| transaction_date | date | MUL | NO |
| description | text | - | YES |
| reference | varchar(100) | - | YES |
| reference_type | enum('po','invoice','receipt','voucher','expense','other') | MUL | YES |
| reference_id | int(11) | - | YES |
| total_debit | decimal(15,2) | - | NO |
| total_credit | decimal(15,2) | - | NO |
| status | enum('draft','posted','cancelled') | MUL | NO |
| posted_at | datetime | - | YES |
| posted_by | int(11) | - | YES |
| cancelled_at | datetime | - | YES |
| cancelled_by | int(11) | - | YES |
| cancel_reason | varchar(255) | - | YES |
| created_by | int(11) | - | YES |
| created_at | datetime | - | YES |
| updated_at | datetime | - | YES |
| deleted_at | datetime | - | YES |

### `keep_log` (85 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| log_data | text | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `login_attempts` (87 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| ip_address | varchar(45) | MUL | NO |
| username | varchar(50) | MUL | YES |
| attempted_at | datetime | - | YES |
| successful | tinyint(1) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `map_type_to_brand` (277 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| type_id | int(11) | MUL | NO |
| brand_id | int(11) | MUL | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `brand_id` → `brand.id`
- `type_id` → `type.id`

### `model` (390 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| type_id | int(11) | MUL | NO |
| brand_id | int(11) | MUL | NO |
| model_name | varchar(100) | - | NO |
| des | mediumtext | - | NO |
| price | double | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `brand_id` → `brand.id`
- `type_id` → `type.id`

### `password_resets` (1 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| email | varchar(100) | MUL | NO |
| token | varchar(64) | MUL | NO |
| created_at | datetime | - | YES |
| expires_at | datetime | MUL | NO |
| used | tinyint(1) | - | YES |
| updated_at | timestamp | - | NO |

### `payment` (9 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| payment_name | varchar(100) | - | NO |
| payment_des | varchar(100) | - | NO |
| com_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `payment_gateway_config` (14 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| payment_method_id | int(11) | MUL | NO |
| config_key | varchar(100) | - | NO |
| config_value | text | - | YES |
| is_encrypted | tinyint(1) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `payment_method_id` → `payment_method.id`

### `payment_log` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| gateway | varchar(50) | MUL | NO |
| order_id | varchar(100) | MUL | NO |
| reference_id | varchar(100) | MUL | YES |
| amount | decimal(12,2) | - | NO |
| currency | varchar(3) | - | YES |
| exchange_rate | decimal(16,6) | - | YES |
| status | varchar(50) | MUL | YES |
| request_data | text | - | YES |
| response_data | text | - | YES |
| created_at | datetime | - | YES |
| updated_at | datetime | - | YES |
| slip_image | varchar(255) | - | YES |

### `payment_methods` (171 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| com_id | int(11) | MUL | NO |
| method_type | enum('bank','gateway','qrcode','cash','other') | MUL | NO |
| method_name | varchar(100) | - | NO |
| account_name | varchar(150) | - | YES |
| account_number | varchar(50) | - | YES |
| branch | varchar(100) | - | YES |
| gateway_id | varchar(100) | - | YES |
| qr_image | varchar(200) | - | YES |
| is_active | tinyint(1) | MUL | NO |
| is_default | tinyint(1) | - | NO |
| sort_order | int(11) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `com_id` → `company.id`

### `permissions` (8 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| key | varchar(255) | UNI | NO |
| name | varchar(255) | - | NO |
| description | text | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `receipt` (7 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(50) | - | NO |
| phone | varchar(50) | - | NO |
| email | varchar(50) | - | NO |
| createdate | date | - | NO |
| created_at | timestamp | - | NO |
| updated_at | datetime | - | YES |
| description | mediumtext | - | NO |
| payment_method | varchar(50) | - | YES |
| payment_ref | varchar(100) | - | YES |
| payment_date | date | - | YES |
| status | enum('draft','confirmed','cancelled') | - | YES |
| invoice_id | int(11) | - | YES |
| quotation_id | int(11) | - | YES |
| source_type | enum('quotation','invoice','manual') | - | YES |
| vender | int(11) | - | NO |
| rep_no | int(11) | - | NO |
| rep_rw | varchar(10) | - | NO |
| brand | int(11) | - | NO |
| subtotal | decimal(12,2) | - | YES |
| after_discount | decimal(12,2) | - | YES |
| vat_amount | decimal(12,2) | - | YES |
| total_amount | decimal(12,2) | - | YES |
| vat | int(11) | - | NO |
| dis | int(11) | - | NO |
| include_vat | tinyint(1) | - | NO |
| deleted_at | datetime | MUL | YES |
| payment_source | enum('manual','paypal','stripe') | - | YES |
| payment_transaction_id | varchar(100) | - | YES |

### `remember_tokens` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| user_id | int(11) | MUL | NO |
| token_hash | varchar(64) | MUL | NO |
| expires_at | datetime | MUL | NO |
| created_at | datetime | - | YES |
| updated_at | timestamp | - | NO |

### `role_permissions` (29 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| role_id | int(11) | MUL | NO |
| permission_id | int(11) | MUL | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `permission_id` → `permissions.id`
- `role_id` → `roles.id`

### `roles` (6 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| name | varchar(255) | UNI | NO |
| description | text | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `sendoutitem` (8 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| ven_id | int(11) | - | NO |
| cus_id | int(11) | - | NO |
| tmp | varchar(100) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `store` (1820 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| pro_id | int(11) | - | NO |
| s_n | mediumtext | - | NO |
| no | int(11) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `store_sale` (1837 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| st_id | int(11) | - | NO |
| warranty | date | - | NO |
| sale | varchar(2) | - | NO |
| own_id | int(11) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `tax_reports` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| com_id | int(11) | MUL | NO |
| report_type | enum('PP30','PND3','PND53') | - | NO |
| tax_year | int(4) | MUL | NO |
| tax_month | int(2) | - | NO |
| output_vat | decimal(15,2) | - | NO |
| input_vat | decimal(15,2) | - | NO |
| net_vat | decimal(15,2) | - | NO |
| total_wht | decimal(15,2) | - | NO |
| report_data | json | - | YES |
| status | enum('draft','submitted','filed') | - | NO |
| notes | text | - | YES |
| created_by | int(11) | - | YES |
| created_at | datetime | - | NO |
| updated_at | datetime | - | NO |

### `tmp_product` (2308 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| pr_id | int(11) | - | NO |
| type | int(11) | - | NO |
| quantity | int(11) | - | NO |
| price | varchar(10) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `user` (4 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| usr_id | int(11) | PRI | NO |
| name | varchar(30) | - | NO |
| surname | varchar(30) | - | NO |
| com_id | int(11) | - | NO |
| email | varchar(30) | - | NO |
| phone | varchar(30) | - | NO |
| fax | varchar(30) | - | NO |
| mobile | varchar(30) | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `user_roles` (10 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| user_id | int(11) | MUL | NO |
| role_id | int(11) | MUL | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `role_id` → `roles.id`

### `voucher` (16 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(100) | - | NO |
| phone | varchar(20) | - | NO |
| email | varchar(40) | - | NO |
| createdate | date | - | NO |
| description | mediumtext | - | NO |
| payment_method | varchar(50) | - | YES |
| status | enum('draft','confirmed','cancelled') | - | YES |
| invoice_id | int(11) | - | YES |
| vender | int(11) | - | NO |
| vou_no | int(11) | - | NO |
| vou_rw | varchar(11) | - | NO |
| brand | int(11) | - | NO |
| vat | int(11) | - | NO |
| discount | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

## AI System

### `ai_action_log` (69 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | NO |
| user_id | int(11) | MUL | NO |
| session_id | varchar(64) | MUL | NO |
| conversation_id | int(11) | - | YES |
| action_type | varchar(50) | MUL | NO |
| action_params | json | - | NO |
| affected_table | varchar(50) | - | YES |
| affected_id | int(11) | - | YES |
| previous_value | json | - | YES |
| new_value | json | - | YES |
| result | json | - | YES |
| status | enum('pending','confirmed','executed','cancelled','failed') | MUL | NO |
| error_message | text | - | YES |
| created_at | timestamp | MUL | NO |
| confirmed_at | timestamp | - | YES |
| executed_at | timestamp | - | YES |
| updated_at | timestamp | - | NO |

### `ai_conversations` (61 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | NO |
| user_id | int(11) | - | NO |
| session_id | varchar(64) | MUL | NO |
| role | enum('user','assistant','system','tool') | - | NO |
| content | text | - | NO |
| tool_calls | json | - | YES |
| tool_results | json | - | YES |
| tokens_used | int(11) | - | YES |
| model | varchar(50) | - | YES |
| created_at | timestamp | MUL | NO |
| updated_at | timestamp | - | NO |

### `ai_sessions` (25 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| session_id | varchar(64) | UNI | NO |
| company_id | int(11) | MUL | NO |
| user_id | int(11) | - | NO |
| title | varchar(255) | - | YES |
| message_count | int(11) | - | YES |
| last_activity | timestamp | MUL | NO |
| created_at | timestamp | - | NO |
| closed_at | timestamp | - | YES |
| updated_at | timestamp | - | NO |

## Master Data

### `brand` (70 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| brand_name | varchar(100) | - | NO |
| des | mediumtext | - | NO |
| logo | varchar(100) | - | NO |
| ven_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `company_id` → `company.id`

### `category` (56 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| cat_name | varchar(30) | - | NO |
| des | mediumtext | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `company_id` → `company.id`

### `payment_method` (7 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| code | varchar(50) | UNI | NO |
| name | varchar(100) | - | NO |
| name_th | varchar(100) | - | YES |
| icon | varchar(50) | - | YES |
| description | text | - | YES |
| is_gateway | tinyint(1) | - | YES |
| is_active | tinyint(1) | - | YES |
| sort_order | int(11) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `type` (413 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(50) | - | NO |
| des | mediumtext | - | NO |
| cat_id | int(11) | MUL | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

**Relationships:**
- `cat_id` → `category.id`
- `company_id` → `company.id`

## Companies & Contacts

### `company` (172 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| name_en | varchar(100) | MUL | NO |
| name_th | varchar(100) | MUL | NO |
| name_sh | varchar(30) | - | NO |
| contact | varchar(50) | - | NO |
| email | varchar(50) | - | NO |
| default_currency | varchar(3) | - | NO |
| phone | varchar(100) | - | NO |
| fax | varchar(100) | - | NO |
| tax | varchar(20) | - | NO |
| customer | int(1) | - | NO |
| vender | int(1) | - | NO |
| logo | varchar(100) | - | NO |
| term | mediumtext | - | NO |
| deleted_at | datetime | MUL | YES |
| company_id | int(11) | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

## Core Business

### `deliver` (769 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | MUL | NO |
| deliver_date | date | - | NO |
| out_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `iv` (723 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | - | NO |
| company_id | int(11) | MUL | YES |
| tex | int(15) | PRI | NO |
| cus_id | int(11) | - | NO |
| createdate | date | - | NO |
| taxrw | varchar(8) | - | NO |
| texiv | int(11) | - | NO |
| texiv_rw | int(11) | - | NO |
| texiv_create | date | - | NO |
| status_iv | int(11) | - | NO |
| countmailinv | int(11) | - | NO |
| countmailtax | int(11) | - | NO |
| deleted_at | datetime | - | YES |
| payment_status | enum('pending','partial','paid') | - | YES |
| payment_gateway | varchar(50) | - | YES |
| payment_order_id | varchar(100) | - | YES |
| paid_amount | decimal(12,2) | - | YES |
| paid_date | datetime | - | YES |
| currency_code | varchar(3) | - | YES |
| exchange_rate | decimal(16,6) | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `pay` (512 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | MUL | NO |
| method | int(11) | - | NO |
| value | varchar(50) | - | NO |
| volumn | double | - | NO |
| date | date | MUL | NO |
| deleted_at | datetime | MUL | YES |
| wht_rate | decimal(5,2) | - | YES |
| wht_amount | decimal(15,2) | - | YES |
| wht_type | enum('PND3','PND53') | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `po` (2051 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id_new | varchar(11) | - | YES |
| name | varchar(255) | - | NO |
| ref | int(11) | MUL | NO |
| tax | varchar(15) | - | NO |
| date | date | MUL | NO |
| valid_pay | date | - | NO |
| deliver_date | date | - | NO |
| pic | varchar(50) | - | NO |
| po_ref | varchar(100) | - | YES |
| dis | float | - | NO |
| bandven | int(11) | - | NO |
| vat | double | - | NO |
| over | int(11) | - | NO |
| deleted_at | datetime | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `pr` (1146 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(255) | - | NO |
| des | mediumtext | - | NO |
| usr_id | int(11) | - | NO |
| cus_id | int(11) | MUL | NO |
| ven_id | int(11) | MUL | NO |
| date | date | MUL | NO |
| status | varchar(1) | MUL | NO |
| cancel | int(11) | - | NO |
| mailcount | int(11) | - | NO |
| payby | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `product` (6152 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| pro_id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | MUL | NO |
| price | double | - | NO |
| discount | double | - | NO |
| ban_id | int(11) | MUL | NO |
| model | int(11) | MUL | YES |
| type | int(11) | MUL | NO |
| quantity | decimal(10,2) | - | NO |
| pack_quantity | decimal(10,2) | - | NO |
| so_id | int(11) | - | NO |
| des | mediumtext | - | NO |
| activelabour | int(11) | - | NO |
| valuelabour | double | - | NO |
| vo_id | int(11) | - | NO |
| vo_warranty | date | - | NO |
| re_id | int(11) | - | NO |
| deleted_at | datetime | - | YES |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

### `receive` (738 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| rec_id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | - | NO |
| deliver_id | int(11) | - | NO |
| date | date | - | NO |
| created_at | timestamp | - | NO |
| updated_at | timestamp | - | NO |

