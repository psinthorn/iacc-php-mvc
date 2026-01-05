# iACC Database Schema
Database: iacc
Discovered: 2026-01-05 10:01:21

## Other

### `_migration_log` (2 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(10) unsigned | PRI | NO |
| migration_name | varchar(255) | UNI | NO |
| executed_at | timestamp | - | NO |
| status | enum('success','failed','rolled_back') | - | YES |
| notes | text | - | YES |

### `audit_logs` (37 rows)
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

### `authorize` (7 rows)
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

### `billing` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| bil_id | int(11) | PRI | NO |
| des | mediumtext | - | NO |
| inv_id | int(11) | - | NO |
| price | varchar(10) | - | NO |

### `company_addr` (343 rows)
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

### `gen_serial` (0 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |

### `login_attempts` (48 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| ip_address | varchar(45) | MUL | NO |
| username | varchar(50) | MUL | YES |
| attempted_at | datetime | - | YES |
| successful | tinyint(1) | - | YES |

### `map_type_to_brand` (265 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| type_id | int(11) | MUL | NO |
| brand_id | int(11) | MUL | NO |

**Relationships:**
- `brand_id` → `brand.id`
- `type_id` → `type.id`

### `model` (359 rows)
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

### `payment` (5 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| payment_name | varchar(100) | - | NO |
| payment_des | varchar(100) | - | NO |
| com_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |

### `payment_gateway_config` (11 rows)
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
| status | varchar(50) | MUL | YES |
| request_data | text | - | YES |
| response_data | text | - | YES |
| created_at | datetime | - | YES |
| updated_at | datetime | - | YES |

### `payment_methods` (1 rows)
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

### `permissions` (7 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| key | varchar(255) | UNI | NO |
| name | varchar(255) | - | NO |
| description | text | - | YES |

### `receipt` (5 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | - | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(50) | - | NO |
| phone | varchar(50) | - | NO |
| email | varchar(50) | - | NO |
| createdate | date | - | NO |
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

### `role_permissions` (7 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| role_id | int(11) | MUL | NO |
| permission_id | int(11) | MUL | NO |

**Relationships:**
- `permission_id` → `permissions.id`
- `role_id` → `roles.id`

### `roles` (5 rows)
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

### `store` (1773 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| pro_id | int(11) | - | NO |
| s_n | mediumtext | - | NO |
| no | int(11) | - | NO |

### `store_sale` (1792 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| st_id | int(11) | - | NO |
| warranty | date | - | NO |
| sale | varchar(2) | - | NO |
| own_id | int(11) | - | NO |

### `tmp_product` (2221 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| pr_id | int(11) | - | NO |
| type | int(11) | - | NO |
| quantity | int(11) | - | NO |
| price | varchar(10) | - | NO |

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

### `user_roles` (4 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| user_id | int(11) | MUL | NO |
| role_id | int(11) | MUL | NO |

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

## AI System

### `ai_action_log` (8 rows)
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

### `ai_conversations` (42 rows)
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

### `ai_sessions` (21 rows)
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

## Master Data

### `brand` (67 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| brand_name | varchar(100) | - | NO |
| des | mediumtext | - | NO |
| logo | varchar(100) | - | NO |
| ven_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |

**Relationships:**
- `company_id` → `company.id`

### `category` (41 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| cat_name | varchar(30) | - | NO |
| des | mediumtext | - | NO |
| deleted_at | datetime | MUL | YES |

**Relationships:**
- `company_id` → `company.id`

### `payment_method` (6 rows)
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

### `type` (390 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(50) | - | NO |
| des | mediumtext | - | NO |
| cat_id | int(11) | MUL | NO |
| deleted_at | datetime | MUL | YES |

**Relationships:**
- `cat_id` → `category.id`
- `company_id` → `company.id`

## Companies & Contacts

### `company` (155 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| name_en | varchar(100) | MUL | NO |
| name_th | varchar(100) | MUL | NO |
| name_sh | varchar(30) | - | NO |
| contact | varchar(50) | - | NO |
| email | varchar(50) | - | NO |
| phone | varchar(100) | - | NO |
| fax | varchar(100) | - | NO |
| tax | varchar(20) | - | NO |
| customer | int(1) | - | NO |
| vender | int(1) | - | NO |
| logo | varchar(100) | - | NO |
| term | mediumtext | - | NO |
| deleted_at | datetime | MUL | YES |
| company_id | int(11) | MUL | YES |

## Core Business

### `deliver` (747 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | MUL | NO |
| deliver_date | date | - | NO |
| out_id | int(11) | - | NO |
| deleted_at | datetime | MUL | YES |

### `iv` (702 rows)
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

### `pay` (494 rows)
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

### `po` (1903 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id_new | varchar(11) | - | YES |
| name | varchar(30) | - | NO |
| ref | int(11) | MUL | NO |
| tax | varchar(15) | - | NO |
| date | date | MUL | NO |
| valid_pay | date | - | NO |
| deliver_date | date | - | NO |
| pic | varchar(50) | - | NO |
| dis | float | - | NO |
| bandven | int(11) | - | NO |
| vat | double | - | NO |
| over | int(11) | - | NO |
| deleted_at | datetime | - | YES |

### `pr` (1036 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| name | varchar(30) | - | NO |
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

### `product` (5920 rows)
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

### `receive` (716 rows)
| Column | Type | Key | Nullable |
|--------|------|-----|----------|
| rec_id | int(11) | PRI | NO |
| company_id | int(11) | MUL | YES |
| po_id | int(11) | - | NO |
| deliver_id | int(11) | - | NO |
| date | date | - | NO |

