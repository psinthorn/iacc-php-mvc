# Phase 2a: Foreign Key Integrity Implementation Guide

**Status**: Ready for execution  
**Last Updated**: December 31, 2025  
**Owner**: Data Integrity Task Force  
**Duration**: ~12 hours over 1-2 weeks


## 1. Objectives

- Enforce referential integrity across all production datasets.
- Eliminate orphaned records before constraints are added.
- Standardize on `ON DELETE RESTRICT` / `ON UPDATE CASCADE` defaults with table-specific overrides.
- Provide auditable SQL scripts plus validation artifacts for compliance sign-off.

## 2. Prerequisites

- ✅ Phase 1 security deployment completed in production.
- ✅ Fresh database backup (`mysqldump iacc > backup/iacc_pre_fk_YYYYMMDD.sql`).
- ✅ Read/write admin access to MySQL 5.7 instance.
- ✅ Staging environment seeded with production snapshot < 48h old.
- ✅ Query checklist from `PHASE2_ANALYSIS.md` reviewed.

## 3. Workflow Overview

1. **Data Profiling**: Run canned queries to detect orphaned and invalid references.
2. **Cleanup Plan**: Decide per-table remediation (fix, archive, or delete).
3. **Constraint Design**: Approve FK matrix and cascading behaviors.
4. **Implementation**: Execute SQL scripts in staging, then production.
5. **Validation & Monitoring**: Automated verification + 48h observability window.

## 4. Data Profiling Playbook (Week 1)

Run each query on staging first; export CSV results to `/docs/audit/fk/`.

```sql
-- 4.1 Band ↔ Company
SELECT band_id, ven_id FROM band WHERE ven_id NOT IN (SELECT company_id FROM company) OR ven_id = 0;

-- 4.2 Company Address ↔ Company
SELECT addr_id, com_id FROM company_addr WHERE com_id NOT IN (SELECT company_id FROM company);

-- 4.3 Company Credit ↔ Company (customer & vendor)
SELECT credit_id, cus_id, ven_id FROM company_credit
WHERE (cus_id IS NOT NULL AND cus_id NOT IN (SELECT company_id FROM company))
   OR (ven_id IS NOT NULL AND ven_id NOT IN (SELECT company_id FROM company));

-- 4.4 Deliveries ↔ Purchase Orders
SELECT deliver_id, po_id FROM deliver WHERE po_id NOT IN (SELECT po_id FROM po);

-- 4.5 Invoices ↔ Company
SELECT iv_id, cus_id FROM iv WHERE cus_id NOT IN (SELECT company_id FROM company);

-- 4.6 Map Type ↔ {type, band}
SELECT map_id, type_id, band_id FROM map_type_to_brand
WHERE type_id NOT IN (SELECT type_id FROM type)
   OR band_id NOT IN (SELECT band_id FROM band);

-- 4.7 PO ↔ Company
SELECT po_id, ven_id, cus_id FROM po
WHERE ven_id NOT IN (SELECT company_id FROM company)
   OR (cus_id IS NOT NULL AND cus_id NOT IN (SELECT company_id FROM company));

-- 4.8 PR ↔ Company/Type/Band
SELECT pr_id, ven_id, type_id, band_id FROM pr
WHERE ven_id NOT IN (SELECT company_id FROM company)
   OR type_id NOT IN (SELECT type_id FROM type)
   OR band_id NOT IN (SELECT band_id FROM band);

-- 4.9 Product ↔ Category/Type/Band
SELECT product_id, cat_id, type_id, band_id FROM product
WHERE cat_id NOT IN (SELECT category_id FROM category)
   OR type_id NOT IN (SELECT type_id FROM type)
   OR band_id NOT IN (SELECT band_id FROM band);
```

> 📌 **Deliverable**: `docs/audit/fk/findings-YYYYMMDD.xlsx` summarizing counts + remediation notes per table.

## 5. Cleanup Strategy (Week 1)

For each orphaned record category, choose **one** remediation path and document it in `docs/audit/fk/cleanup-log.md`:

- **Fix Reference**: Update FK column with valid `*_id` (preferred when data still relevant).
- **Archive & Remove**: Move orphaned rows into `*_archive` table, then delete from primary table.
- **Soft Delete**: If business rules forbid hard deletes, add `deleted_at` timestamp and skip FK addition until Phase 2 timestamps are live.

**Automation Tips**:

- Wrap multi-table updates in transactions: `START TRANSACTION; ...; COMMIT;`
- Keep row counts before/after cleanup for audit trail.

## 6. Constraint Design Matrix

| Child Table | Parent Table | Columns | Delete Rule | Update Rule | Notes |
| --- | --- | --- | --- | --- | --- |
| `band` | `company` | `band.ven_id → company.company_id` | RESTRICT | CASCADE | 51 records currently `ven_id = 0` must be fixed first. |
| `company_addr` | `company` | `com_id → company_id` | CASCADE | CASCADE | Deleting a company should remove dependent addresses. |
| `company_credit` | `company` | `cus_id`, `ven_id` | RESTRICT | CASCADE | Consider splitting into two constraints. |
| `deliver` | `po` | `po_id → po.po_id` | RESTRICT | CASCADE | Keeps delivery history intact. |
| `iv` | `company` | `cus_id → company_id` | RESTRICT | CASCADE | Invoices cannot exist without a customer. |
| `map_type_to_brand` | `type`, `band` | `type_id`, `band_id` | CASCADE | CASCADE | Acts as bridge table; cascading delete acceptable. |
| `po` | `company` | `ven_id`, `cus_id` | RESTRICT | CASCADE | Prevent orphaned purchase orders. |
| `pr` | `company`, `type`, `band` | `ven_id`, `type_id`, `band_id` | RESTRICT | CASCADE | Multi-parent: add one FK per column. |
| `product` | `category`, `type`, `band` | `cat_id`, `type_id`, `band_id` | RESTRICT | CASCADE | Ensure catalog coherence. |

> ✅ Final matrix must be approved by product + finance stakeholders before execution.

## 7. Implementation Runbook (Week 2)

### 7.1 Script Generation

- Use template per constraint:

```sql
ALTER TABLE <child_table>
ADD CONSTRAINT fk_<child>_<parent>_<column>
FOREIGN KEY (<column>)
REFERENCES <parent_table>(<parent_key>)
ON DELETE <rule>
ON UPDATE <rule>;
```

- Store scripts in `migrations/phase2_fk/001_add_constraints.sql` with one statement per block.
- Include `/*!40014 SET FOREIGN_KEY_CHECKS=0 */;` guard lines only within transactions to avoid global side-effects.

### 7.2 Staging Dry Run

1. Snapshot staging DB.
2. Apply cleanup patches.
3. Run FK script.
4. Execute regression suite (login, PO creation, product CRUD, reporting).
5. Export `SHOW ENGINE INNODB STATUS \G` output for records.

### 7.3 Production Rollout

1. Announce 30-minute maintenance window.
2. Enable read-only banner in app (set `SYSTEM_MAINTENANCE=true`).
3. Backup production DB.
4. Apply cleanup SQL via transaction.
5. Apply FK script.
6. Re-run orphan checks (Section 4). Expect **zero** rows.
7. Disable maintenance mode.

## 8. Validation Checklist

- [ ] `SELECT @@foreign_key_checks;` returns `1` post-deployment.
- [ ] All orphan-detection queries return zero rows.
- [ ] `INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS` shows 15+ entries for new FKs.
- [ ] Application smoke tests (login, PO, PR, invoice, delivery) pass.
- [ ] Error logs (`iacc/logs/php-error.log`) remain clean for 24h.
- [ ] Grafana/PMM dashboards show no spike in InnoDB deadlocks.

## 9. Rollback Plan

1. If constraint creation fails mid-run: `ROLLBACK;` and inspect offending rows via `SHOW ENGINE INNODB STATUS;`.
2. If post-deployment issues arise within 24h: execute `migrations/phase2_fk/rollback/001_drop_constraints.sql` which drops each FK by name.
3. Restore backup only if data corruption detected; otherwise keep cleanup changes for future rerun.

## 10. Deliverables

- `migrations/phase2_fk/001_add_constraints.sql`
- `migrations/phase2_fk/rollback/001_drop_constraints.sql`
- `docs/audit/fk/findings-YYYYMMDD.xlsx`
- `docs/audit/fk/cleanup-log.md`
- Change request ticket + sign-off checklist stored in `docs/audit/fk/CR-###.md`

---

**Next Up**: After FK integrity is live, proceed with Phase 2 naming standardization using the clean relational graph as the source of truth.
