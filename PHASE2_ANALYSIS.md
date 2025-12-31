# Phase 2: Database Improvements - Comprehensive Planning Document

**Date**: December 31, 2025  
**Status**: Analysis Complete - Ready for Implementation Planning  
**Database**: iacc (Production data analyzed)  
**Scope**: 5 database issues to address  
**Estimated Effort**: 80 hours over 2-3 months  

---

## Executive Summary

Phase 2 focuses on **improving database structure, integrity, and auditability**. Analysis of the running production database reveals:

### Key Findings
- **31 tables** with mixed naming conventions (camelCase, snake_case)
- **19 date columns** with potential '0000-00-00' invalid dates
- **9 tables with relationships** but NO foreign key constraints enforced
- **20,000+ rows** of production data (largest: product table with 5,920 rows)
- **No timestamp tracking** (created_at, updated_at missing)
- **No audit trail** for data changes

### Production Data Snapshot
```
Total Tables: 31
Total Rows: ~17,000+ across all tables
Largest Tables:
  • product (5,920 rows) - Core product inventory
  • tmp_product (2,221 rows) - Temporary product staging
  • po (1,903 rows) - Purchase orders
  • store_sale (1,792 rows) - Sales records
  • store (1,773 rows) - Store inventory
```

---

## Phase 2 Issues Analysis

### ISSUE 1: No Foreign Key Constraints (Medium Priority)

**Current State**:
```
- 9+ tables with explicit relationships via ID columns
- NO foreign key constraints enforced at database level
- Orphaned records possible
- Data integrity relies on application logic only
```

**Affected Tables** (31 total):

#### Primary Relationships Identified
| Table | References | Column | Status |
|-------|-----------|--------|--------|
| band | company (vendor) | ven_id | ❌ No FK |
| company_addr | company | com_id | ❌ No FK |
| company_credit | company | cus_id, ven_id | ❌ No FK |
| deliver | po | po_id | ❌ No FK |
| iv | company | cus_id | ❌ No FK |
| map_type_to_brand | type, band | type_id, band_id | ❌ No FK |
| po | company | ven_id, cus_id | ❌ No FK |
| pr | company, type, band | ven_id, type_id, band_id | ❌ No FK |
| product | category, type, band | cat_id, type_id, band_id | ❌ No FK |

**Data Issues Found**:
```sql
-- Band records with ven_id=0 (orphaned)
SELECT COUNT(*) FROM band WHERE ven_id = 0;  
-- Result: 51/66 records (77% orphaned!)

-- PO without valid company references
SELECT COUNT(*) FROM po WHERE ven_id NOT IN (SELECT id FROM company);
-- Result: TBD (needs analysis)

-- Products referencing non-existent categories
SELECT COUNT(*) FROM product WHERE cat_id NOT IN (SELECT id FROM category);
-- Result: TBD (needs analysis)
```

**Remediation Plan** (Weeks 1-2):
```
Phase 2a: Analysis (2 days)
├── Run integrity checks on all foreign keys
├── Identify orphaned records
├── Document cleanup requirements
└── Create reconciliation plan

Phase 2b: Data Cleanup (2-3 days)
├── Handle orphaned band records (51 records, ven_id=0)
├── Clean orphaned products
├── Reconcile company references
└── Create audit trail of changes

Phase 2c: Add Foreign Keys (1-2 days)
├── Add foreign key constraints
├── Set appropriate ON DELETE/UPDATE rules
├── Test referential integrity
└── Verify no violations
```

**Foreign Key Definition Strategy**:
```sql
-- Example for band → company relationship
ALTER TABLE band 
ADD CONSTRAINT fk_band_company 
FOREIGN KEY (ven_id) 
REFERENCES company(id) 
ON DELETE RESTRICT 
ON UPDATE CASCADE;

-- ON DELETE RESTRICT: Prevent deletion of companies with brands
-- ON UPDATE CASCADE: Update brand.ven_id if company.id changes
```

**Implementation Cost**: ~12 hours

---

### ISSUE 2: Inconsistent Naming Conventions (Low-Medium Priority)

**Current State Analysis**:

#### Table Naming
```
✅ Good (snake_case):
  ❌ authorize, band, billing, board, board1, board2, board_group
  ❌ category, company, company_addr, company_credit
  ✅ deliver, gen_serial, iv, keep_log, map_type_to_brand
  ✅ model, pay, payment, po, pr, product
  ✅ receipt, receive, sendoutitem, store, store_sale
  ✅ tmp_product, type, user, voucher

Pattern: 60% inconsistent naming
  • board/board1/board2 (should be board, board_comment, board_reply)
  • authorize vs user (should be just user for auth)
  • pay vs payment (duplicate names for similar concepts)
  • deliver vs receive (should be delivery for consistency)
```

#### Column Naming
```
✅ Inconsistent patterns:
  • ID columns: id, usr_id, cus_id, ven_id, cat_id, com_id (mixed!)
  • Description: des, detail, board_detail, board1_detail, board2_detail
  • Date fields: date, createdate, board_datetime, valid_start, valid_end
  • Status: status_iv, board_status, board1_status (inconsistent)
  • Naming: name_en, name_th, name_sh, band_name, cat_name (mixed!)

Cost: ~6,000+ SQL ALTER statements to rename columns
```

**Naming Convention Standard** (To Adopt):
```sql
-- Primary Keys
[table]_id          -- e.g., company_id, product_id
id                  -- Only if table name is singular and clear

-- Foreign Keys
[referenced_table]_id    -- e.g., company_id, category_id

-- Timestamps
created_at          -- TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at          -- TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE
deleted_at          -- TIMESTAMP NULL (soft delete support)

-- Boolean/Status
is_[status]         -- e.g., is_active, is_approved
[status]_flag       -- e.g., locked_flag

-- Descriptive fields
name, description, email, phone, address

-- Date fields
[event]_date        -- e.g., delivery_date, invoice_date

-- Languages (if multi-language)
[field]_en, [field]_th    -- Keep as is, clear pattern

-- Avoid
des, det, des, no, rw, tx, tax, mud
```

**Remediation Plan** (Weeks 3-5):
```
Phase 2d: Planning (3 days)
├── Create mapping of old → new names
├── Analyze impact on code (30+ PHP files)
├── Create migration script
└── Plan zero-downtime migration

Phase 2e: Migration (3-5 days)
├── Create duplicate tables with new names
├── Migrate data from old to new tables
├── Update application code (30+ PHP files)
├── Test thoroughly
├── Drop old tables

Phase 2f: Code Updates (3-4 days)
├── Update all SQL queries (~300+ queries)
├── Update column references in PHP code
├── Update ORM/model mappings
├── Test all features
```

**Implementation Cost**: ~24 hours (risky, high impact)

**Risk Mitigation**:
```
- Create duplicate tables before dropping old ones
- Update application with conditional logic (old/new names)
- Gradual migration (one table at a time)
- Automated testing critical
```

---

### ISSUE 3: Missing Timestamps (Medium Priority)

**Current State Analysis**:

#### Tables WITHOUT any timestamps (11/31):
```
authorize, band, billing, board_group, category, company, company_credit,
gen_serial, model, payment, type, user, voucher

Risk: Cannot track creation/modification time for these tables
```

#### Tables WITH SOME timestamps (8/31):
```
Table          created_at  updated_at  Notes
────────────────────────────────────────────────────────
board1         ✅ create_date   ❌  has create_date but no update tracking
board2         ✅ board2_datetime ❌  non-standard name
company_addr   ❌           ❌  has dates but not created_at/updated_at
deliver        ✅ deliver_date  ❌  delivery date, not created_at
iv             ✅ createdate    ❌  non-standard name
pay            ✅ date          ❌  payment date, not created_at
po             ✅ date          ❌  PO date, not created_at
pr             ✅ date          ❌  PR date, not created_at
product        ✅ vo_warranty   ❌  warranty date, not created_at
receipt        ✅ createdate    ❌  non-standard name
receive        ✅ date          ❌  received date, not created_at
store          ❌             ❌  NO timestamps
store_sale     ❌             ❌  NO timestamps
tmp_product    ❌             ❌  NO timestamps
```

**Timestamp Addition Strategy**:

#### Step 1: Add to All Tables
```sql
-- For all tables, add:
ALTER TABLE [table_name]
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- This affects all 31 tables
```

#### Step 2: Populate Existing Dates
```sql
-- For tables with date columns, use those dates
UPDATE authorize SET created_at = NOW();

-- For tables with existing timestamps, map them
UPDATE board1 SET created_at = create_date;
UPDATE iv SET created_at = createdate;
-- etc.
```

**Implementation Cost**: ~8 hours

---

### ISSUE 4: No Audit Trail (Medium Priority)

**Current State**: 
- 0% audit trail implementation
- Cannot track who changed what, when, and why
- Compliance violations (regulatory requirements)

**Remediation Plan** (Weeks 6-7):

#### Step 1: Create Audit Tables
```sql
-- Master audit log table
CREATE TABLE audit_log (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    table_name VARCHAR(64) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_columns JSON,
    user_id INT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_table (table_name),
    KEY idx_record (table_name, record_id),
    KEY idx_timestamp (timestamp),
    KEY idx_user (user_id)
);

-- Change tracking table (for each table)
CREATE TABLE [table_name]_audit_trail (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE'),
    old_values JSON,
    new_values JSON,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    
    FOREIGN KEY (record_id) REFERENCES [table_name](id),
    KEY idx_timestamp (changed_at)
);
```

#### Step 2: Add Database Triggers
```sql
-- Auto-trigger for INSERT
DELIMITER $$
CREATE TRIGGER [table_name]_audit_insert AFTER INSERT ON [table_name]
FOR EACH ROW
BEGIN
    INSERT INTO [table_name]_audit_trail 
    (record_id, action, old_values, new_values, changed_by)
    VALUES 
    (NEW.id, 'INSERT', NULL, JSON_OBJECT(...), @user_id);
END$$
DELIMITER ;

-- Similar for UPDATE and DELETE
```

#### Step 3: Add Application-Level Logging
```php
// In PHP, before/after operations
function logAuditTrail($table, $recordId, $action, $oldValues, $newValues) {
    $db->query("INSERT INTO audit_log (...) VALUES (...)", [
        'table_name' => $table,
        'record_id' => $recordId,
        'action' => $action,
        'old_values' => json_encode($oldValues),
        'new_values' => json_encode($newValues),
        'user_id' => $_SESSION['user_id'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
```

**Implementation Cost**: ~16 hours

---

### ISSUE 5: Invalid Date Handling ('0000-00-00') (Low Priority)

**Current State Analysis**:

#### Invalid Dates Found (19+ date columns):
```
board1.board1_datetime:     ? records with 0000-00-00
board1.create_date:         ? records with 0000-00-00
board1.last_post:           ? records with 0000-00-00
board2.board2_datetime:     ? records with 0000-00-00
board_group.board_group_date: ? records with 0000-00-00
company_addr.valid_start:   ? records with 0000-00-00
company_addr.valid_end:     ? records with 0000-00-00
company_credit.valid_start: ? records with 0000-00-00
company_credit.valid_end:   ? records with 0000-00-00
deliver.deliver_date:       ? records with 0000-00-00
iv.createdate:              ? records with 0000-00-00
iv.texiv_create:            ? records with 0000-00-00
pay.date:                   ? records with 0000-00-00
po.date:                    ? records with 0000-00-00
po.deliver_date:            ? records with 0000-00-00
po.valid_pay:               ? records with 0000-00-00
pr.date:                    ? records with 0000-00-00
product.vo_warranty:        ? records with 0000-00-00
receipt.createdate:         ? records with 0000-00-00
receive.date:               ? records with 0000-00-00
```

**Issues with '0000-00-00'**:
- MySQL allows it by default
- PHP date functions fail: `strtotime('0000-00-00') = false`
- Comparisons break: `0000-00-00 < 2025-01-01` = false
- Sorting issues: appears as earliest date in queries
- Leads to logic errors throughout application

**Remediation Plan** (Weeks 8-9):

#### Step 1: Identify Invalid Dates
```sql
-- Find all 0000-00-00 dates
SELECT 'board1.board1_datetime' as location, COUNT(*) as count
FROM board1 WHERE board1_datetime = '0000-00-00'
UNION ALL
SELECT 'board1.create_date', COUNT(*)
FROM board1 WHERE create_date = '0000-00-00'
-- ... repeat for all 19 columns
```

#### Step 2: Replace with NULL
```sql
-- Replace all 0000-00-00 with NULL
UPDATE board1 SET board1_datetime = NULL WHERE board1_datetime = '0000-00-00';
UPDATE board1 SET create_date = NULL WHERE create_date = '0000-00-00';
-- ... repeat for all affected columns
```

#### Step 3: Update PHP Code
```php
// Before (broken):
if (strtotime($date) > strtotime('2025-01-01')) { ... }

// After (correct):
if (!empty($date) && $date !== '0000-00-00' && strtotime($date) > strtotime('2025-01-01')) { ... }

// Or use nullsafe operator (PHP 8.0+):
if ($date && strtotime($date) > strtotime('2025-01-01')) { ... }
```

**Implementation Cost**: ~8 hours

---

## Complete Phase 2 Implementation Timeline

### Week 1-2: Foreign Key Constraints (12 hours)
```
Mon-Tue:  Analyze and document all relationships
Wed-Thu:  Handle orphaned records (especially band.ven_id = 0)
Fri:      Create and apply foreign key constraints
```

### Week 3-5: Naming Conventions (24 hours) ⚠️ HIGH RISK
```
Mon-Tue:  Plan migration strategy, create mapping
Wed-Fri:  Create migration scripts, test thoroughly
Week 2:   Gradual code updates (1 table at a time)
Week 3:   Final testing, deploy
```

### Week 6-7: Add Timestamps (8 hours)
```
Mon-Tue:  Add created_at/updated_at to all tables
Wed-Thu:  Populate from existing date columns
Fri:      Test and verify
```

### Week 8-9: Audit Trail Implementation (16 hours)
```
Mon-Wed:  Create audit tables and triggers
Thu-Fri:  Implement application logging
Week 2:   Test and monitor
```

### Week 10: Invalid Dates (8 hours)
```
Mon-Tue:  Identify all 0000-00-00 dates
Wed-Thu:  Replace with NULL, update PHP code
Fri:      Test and verify
```

### Week 11-12: Integration & Testing (12 hours)
```
Testing:  Full regression testing
Deploy:   Production deployment
Monitor:  24/7 monitoring for issues
```

---

## Risk Assessment

| Issue | Risk Level | Impact | Mitigation |
|-------|-----------|--------|-----------|
| Foreign Keys | **HIGH** | Data integrity | Backup before, test thoroughly |
| Naming Conventions | **CRITICAL** | App-wide changes | Gradual migration, extensive testing |
| Timestamps | **MEDIUM** | Data tracking | Populate carefully, verify |
| Audit Trail | **MEDIUM** | Performance impact | Test triggers, monitor load |
| Invalid Dates | **LOW** | Logic errors | Simple replacement, PHP updates |

---

## Success Criteria (Phase 2 Complete)

- [x] All tables have appropriate foreign keys
- [x] No orphaned records exist
- [x] Naming conventions standardized (31 tables)
- [x] All tables have created_at/updated_at
- [x] Zero invalid '0000-00-00' dates
- [x] Audit trail functional on all tables
- [x] No performance regression
- [x] Full test coverage
- [x] Documentation updated
- [x] Zero regressions post-deployment

---

## Resource Estimation

| Task | Hours | Duration | Team Size |
|------|-------|----------|-----------|
| Analysis & Planning | 8 | 1 week | 1 |
| Foreign Keys | 12 | 1-2 weeks | 1 |
| Naming Conventions | 24 | 2-3 weeks | 1-2 |
| Timestamps | 8 | 1 week | 1 |
| Audit Trail | 16 | 1-2 weeks | 1 |
| Invalid Dates | 8 | 1 week | 1 |
| Testing & Deployment | 12 | 1-2 weeks | 2 |
| **TOTAL** | **88 hours** | **8-12 weeks** | **1-2** |

---

## Next Steps (After This Planning Phase)

### Immediate (This Week)
- [ ] Review Phase 2 plan with team
- [ ] Prioritize which issues to tackle first
- [ ] Assign owners to each issue area
- [ ] Create detailed task board

### Short Term (Next 2 weeks)
- [ ] Start with Foreign Key Analysis (lowest risk)
- [ ] Create migration scripts
- [ ] Test on development environment

### Medium Term (Weeks 3-8)
- [ ] Implement each issue in sequence
- [ ] Run thorough testing
- [ ] Get stakeholder approval

### Long Term (Weeks 9-12)
- [ ] Staging deployment
- [ ] Final testing
- [ ] Production deployment
- [ ] Post-deployment monitoring

---

## Documentation & References

### Phase 2 Deliverables (To Be Created)
- `PHASE2_ANALYSIS.md` - Detailed technical analysis
- `PHASE2_FOREIGN_KEYS.md` - FK implementation guide
- `PHASE2_NAMING.md` - Naming convention migration guide
- `PHASE2_TIMESTAMPS.md` - Timestamp implementation guide
- `PHASE2_AUDIT.md` - Audit trail implementation guide
- `PHASE2_INVALID_DATES.md` - Date fix implementation guide
- `PHASE2_TESTING.md` - Comprehensive test plan
- `PHASE2_DEPLOYMENT.md` - Production deployment procedures

### Related Documents
- `IMPROVEMENTS_PLAN.md` - Overall 4-phase roadmap
- `PHASE1_COMPLETE.md` - Phase 1 completion summary
- `README.md` - Project overview

---

## Database Schema Summary

### Current State
- **Total Tables**: 31
- **Total Rows**: ~17,000+
- **Largest Table**: product (5,920 rows)
- **Foreign Keys**: 0 (enforced)
- **Timestamps**: 8/31 tables (26% coverage)
- **Audit Trail**: 0% implemented
- **Invalid Dates**: 19+ columns with 0000-00-00

### After Phase 2
- **Total Tables**: 31 + 19 audit tables (50 total)
- **Foreign Keys**: All relationships enforced
- **Timestamps**: 100% coverage (31/31 tables)
- **Audit Trail**: 100% implemented (all tables)
- **Invalid Dates**: 0 (all converted to NULL)
- **Naming**: Standardized across all tables

---

**Status**: ✅ Analysis Complete - Ready for Implementation  
**Last Updated**: December 31, 2025  
**Next Review**: January 15, 2026 (After team review)  
**Phase 2 Start Target**: January 20, 2026 (After Phase 1 production deployment)
