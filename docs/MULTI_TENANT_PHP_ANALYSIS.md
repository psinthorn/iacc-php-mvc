# Multi-Tenant PHP Files Analysis

This document lists all PHP files that need company_id filtering after migration.

## Summary

| Category | Files Count | Status |
|----------|-------------|--------|
| Master Data (brand, category, type, model) | 8 | Needs Update |
| Transaction (po, iv, product, pay) | 15+ | Needs Update |
| Configuration (payment_methods, payment_gateway) | 4 | Needs Update |
| Voucher/Receipt | 8 | Needs Update |

## Detailed File List

### 1. Master Data Files

#### Brand
| File | Lines | Query Pattern |
|------|-------|---------------|
| [vou-print.php](vou-print.php#L40) | 40 | `SELECT logo FROM brand WHERE id = ...` |
| [voc-make.php](voc-make.php#L237) | 237, 320, 376 | `SELECT brand_name,id FROM brand` |
| [sptinv.php](sptinv.php#L21) | 21 | `SELECT logo FROM brand` |
| [taxiv.php](taxiv.php#L72) | 72 | `SELECT logo FROM brand` |
| [rep-print.php](rep-print.php#L40) | 40 | `SELECT logo FROM brand` |
| [brand-list.php](brand-list.php) | multiple | All brand queries |
| [brand.php](brand.php) | multiple | Brand CRUD |

#### Category
| File | Lines | Query Pattern |
|------|-------|---------------|
| [type.php](type.php#L33) | 33 | `SELECT cat_name,id FROM category` |
| [category-list.php](category-list.php) | multiple | All category queries |
| [category.php](category.php) | multiple | Category CRUD |

#### Type
| File | Lines | Query Pattern |
|------|-------|---------------|
| [voc-make.php](voc-make.php#L137) | 137, 312, 368 | `SELECT name,id FROM type` |
| [type-list.php](type-list.php#L43) | 43 | `SELECT type.id... FROM type` |
| [type.php](type.php#L19) | 19, 51, 55 | Type queries |

#### Model
| File | Lines | Query Pattern |
|------|-------|---------------|
| [voc-make.php](voc-make.php#L332) | 332, 388 | `SELECT model_name,id FROM model` |
| [model.php](model.php) | multiple | Model CRUD |
| [modal_molist.php](modal_molist.php) | multiple | Model list modal |

### 2. Transaction Files

#### PO (Purchase Orders)
| File | Lines | Query Pattern |
|------|-------|---------------|
| [po-list.php](po-list.php#L52) | 52, 59 | `SELECT COUNT(*) FROM po` |
| [po-edit.php](po-edit.php#L182) | 182 | `SELECT ... FROM po` |
| [po-view.php](po-view.php#L70) | 70, 81 | Product queries for PO |
| [po-deliv.php](po-deliv.php#L94) | 94, 117 | Delivery queries |
| [po-make.php](po-make.php) | multiple | PO creation |
| [qa-list.php](qa-list.php#L66) | 66, 108 | QA PO queries |
| [deliv-list.php](deliv-list.php#L68) | 68, 97 | Delivery list |

#### IV (Invoices)
| File | Lines | Query Pattern |
|------|-------|---------------|
| [inv.php](inv.php#L55) | 55, 65 | `SELECT ... FROM po/iv` |
| [dashboard.php](dashboard.php#L196) | 196 | `SELECT COUNT(*) FROM iv` |

#### Product
| File | Lines | Query Pattern |
|------|-------|---------------|
| [voc-make.php](voc-make.php#L360) | 360 | `SELECT ... FROM product` |
| [taxiv-m.php](taxiv-m.php#L81) | 81, 103 | Product queries |
| [sptinv.php](sptinv.php#L75) | 75, 96 | Product queries |
| [po-view.php](po-view.php#L81) | 81 | Product queries |
| [po-edit.php](po-edit.php#L289) | 289 | `SELECT * FROM product` |
| [po-deliv.php](po-deliv.php#L94) | 94 | Product queries |
| [model_mail.php](model_mail.php#L33) | 33, 68, 103 | Product queries |
| [product-list.php](product-list.php#L41) | 41 | Product aggregation |
| [rec.php](rec.php#L109) | 109, 110 | Product queries |
| [rep-make.php](rep-make.php#L570) | 570 | Product for receipt |

#### Pay (Payments)
| File | Lines | Query Pattern |
|------|-------|---------------|
| [dashboard.php](dashboard.php#L136) | 136, 145, 180 | `SELECT SUM(volumn) FROM pay` |
| [invoice-payments.php](invoice-payments.php#L99) | 99, 185 | Pay subqueries |
| [invoice-payments-export.php](invoice-payments-export.php#L60) | 60 | Pay export |
| [payment-list.php](payment-list.php) | multiple | Payment list |
| [payment.php](payment.php) | multiple | Payment CRUD |

### 3. Configuration Files

#### Payment Methods
| File | Lines | Query Pattern |
|------|-------|---------------|
| [payment-method-list.php](payment-method-list.php) | multiple | Payment method queries |
| [payment-method.php](payment-method.php) | multiple | Payment method CRUD |

#### Payment Gateway Config
| File | Lines | Query Pattern |
|------|-------|---------------|
| [payment-gateway-config.php](payment-gateway-config.php#L35) | 35, 71, 174 | `SELECT ... FROM payment_gateway_config` |
| [iacc/payment-gateway-config.php](iacc/payment-gateway-config.php#L61) | 61 | Gateway config |
| [iacc/inc/class.paypal.php](iacc/inc/class.paypal.php#L46) | 46 | PayPal config |
| [iacc/inc/class.stripe.php](iacc/inc/class.stripe.php#L45) | 45 | Stripe config |

### 4. Voucher/Receipt Files

#### Voucher
| File | Lines | Query Pattern |
|------|-------|---------------|
| [vou-print.php](vou-print.php#L19) | 19 | `SELECT * FROM voucher` |
| [vou-list.php](vou-list.php#L106) | 106, 208 | `SELECT ... FROM voucher` |
| [voc-view.php](voc-view.php#L17) | 17 | `SELECT * FROM voucher` |
| [voc-make.php](voc-make.php#L174) | 174 | `SELECT * FROM voucher` |

#### Receipt
| File | Lines | Query Pattern |
|------|-------|---------------|
| [rep-view.php](rep-view.php#L17) | 17 | `SELECT * FROM receipt` |
| [rep-make.php](rep-make.php#L286) | 286 | `SELECT * FROM receipt` |
| [rep-print.php](rep-print.php#L19) | 19 | `SELECT * FROM receipt` |
| [rep-list.php](rep-list.php#L35) | 35-38, 287 | `SELECT ... FROM receipt` |

### 5. Dashboard & Reports

| File | Lines | Query Pattern |
|------|-------|---------------|
| [dashboard.php](dashboard.php#L154) | 154, 188 | PO counts |
| [report.php](report.php#L6) | 6+ | Report queries |
| [report-export.php](report-export.php#L26) | 26+ | Export queries |

## Implementation Pattern

### For SELECT Queries

```php
// Before
$query = mysqli_query($db->conn, "SELECT * FROM brand WHERE id = '...'");

// After
require_once 'inc/class.company_filter.php';
$companyFilter = CompanyFilter::getInstance();
$query = mysqli_query($db->conn, "SELECT * FROM brand WHERE id = '...' " . $companyFilter->andCompanyFilter('brand'));
```

### For INSERT Queries

```php
// Before
$sql = "INSERT INTO brand (brand_name, ven_id) VALUES ('...', '...')";

// After
require_once 'inc/class.company_filter.php';
$companyId = CompanyFilter::getInstance()->getCompanyIdForInsert();
$sql = "INSERT INTO brand (brand_name, ven_id, company_id) VALUES ('...', '...', $companyId)";
```

### For List Pages

```php
// Before
$query = mysqli_query($db->conn, "SELECT * FROM category WHERE 1=1 $search_cond");

// After
require_once 'inc/class.company_filter.php';
$companyFilter = CompanyFilter::getInstance();
$query = mysqli_query($db->conn, "SELECT * FROM category WHERE 1=1 " . $companyFilter->andCompanyFilter() . " $search_cond");
```

## Priority Order for Updates

### High Priority (Core Business Logic)
1. brand-list.php, brand.php
2. category-list.php, category.php
3. type-list.php, type.php
4. model.php, modal_molist.php
5. po-list.php, po-make.php, po-edit.php, po-view.php
6. payment-method-list.php, payment-method.php
7. payment-gateway-config.php

### Medium Priority (Transactions)
8. inv.php, inv-m.php
9. invoice-payments.php, invoice-payments-export.php
10. vou-list.php, voc-make.php, voc-view.php, vou-print.php
11. rep-list.php, rep-make.php, rep-view.php, rep-print.php
12. deliv-list.php, deliv-make.php, deliv-edit.php, deliv-view.php

### Lower Priority (Supporting Files)
13. dashboard.php
14. report.php, report-export.php
15. product-list.php
16. All iacc/ public-facing files
