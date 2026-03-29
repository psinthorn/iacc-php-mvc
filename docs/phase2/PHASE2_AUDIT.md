# Phase 2d: Audit Trail & Change Logging Implementation Guide

**Status**: Implementation playbook ready  
**Last Updated**: December 31, 2025  
**Owner**: Compliance & Data Engineering Squad  
**Duration**: ~16 engineer-hours across 2 weeks  
**Dependencies**: Timestamps in place (`PHASE2_TIMESTAMPS.md`) and foreign keys enforced.

---

## 1. Goals

- Capture INSERT/UPDATE/DELETE activity for all critical tables with actor, timestamp, and before/after snapshots.
- Provide a centralized `audit_log` table for cross-table reporting plus per-table audit trails for granular rollback.
- Offer both database-trigger and application-level hooks to cover automated imports and PHP-driven operations.
- Supply operational tooling to search, export, and purge audit records while respecting compliance retention policies.

---

## 2. Architecture Overview

```text
┌───────────────┐      CRUD       ┌───────────────┐
│ PHP Services  │ ─────────────▶ │   MySQL 5.7    │
│ (iacc/*.php)  │                 │  Business DB   │
└──────┬────────┘                 └──────┬────────┘
       │ Application log calls          │ Triggers
       ▼                                ▼
┌───────────────┐      ETL feed    ┌───────────────┐
│ audit_log     │◀────────────────▶│ <table>_audit │
│ (global)      │                  │ per entity    │
└───────────────┘                  └───────────────┘
```

- **Global Log (`audit_log`)**: JSON payload, searchable across all tables.
- **Per-Table Trails (`<table>_audit`)**: Structured columns mirroring parent table, optimized for quick forensic review.
- **Application Hooks**: Helper `AuditService` class invoked around high-level operations (e.g., PO approval) to store business context.

---

## 3. Database Objects

### 3.1 Master Audit Table

```sql
CREATE TABLE audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(64) NOT NULL,
    record_id BIGINT NOT NULL,
    action ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_columns JSON,
    user_id INT NULL,
    username VARCHAR(128) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 Per-Table Audit Trails

Create lightweight audit tables only for entities with compliance requirements (company, product, po, pr, iv, deliver, payment, receipt).

```sql
CREATE TABLE po_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    action ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    snapshot JSON NOT NULL,
    changed_by INT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'TRIGGER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po (po_id),
    CONSTRAINT fk_po_audit_po FOREIGN KEY (po_id) REFERENCES po(po_id)
);
```

> 📌 Keep per-table schema lean; store the entire row inside `snapshot` JSON to reduce ALTER churn.

---

## 4. Trigger Strategy

1. Create AFTER INSERT/UPDATE/DELETE triggers for each audited table.
2. Ensure triggers populate both the per-table audit table and the global `audit_log`.
3. Wrap JSON construction with `JSON_OBJECT()`; avoid string concatenation to reduce SQL injection risk.

Example (Purchase Orders):

```sql
DELIMITER $$
CREATE TRIGGER po_audit_insert AFTER INSERT ON po
FOR EACH ROW
BEGIN
    INSERT INTO po_audit (po_id, action, snapshot, changed_by)
    VALUES (NEW.po_id, 'INSERT', JSON_OBJECT('row', NEW.*), NEW.updated_by);

    INSERT INTO audit_log (table_name, record_id, action, new_values, user_id)
    VALUES ('po', NEW.po_id, 'INSERT', JSON_OBJECT('row', NEW.*), NEW.updated_by);
END$$
DELIMITER ;
```

> ⚠️ MySQL 5.7 cannot use `NEW.*` directly inside JSON_OBJECT. Instead, list columns individually or generate SQL via script to keep in sync.

Trigger checklist:

- Name pattern: `<table>_audit_<operation>`
- Use `BEFORE UPDATE` to capture `OLD` values and `AFTER` to capture `NEW` when necessary.
- Guard against recursion by avoiding writes back to parent table.
- Log `USER()` or session variables to capture DB-level actors (e.g., migrations).

---

## 5. Application-Level Logging

Create `resources/classes/AuditService.php` with methods:

- `logAction($table, $recordId, $action, $oldValues, $newValues, $context = [])`
- `logBulk($table, array $records, $action, $context = [])`

Responsibilities:

- Enrich payload with session user (via `SessionManager`), IP, user-agent, request ID.
- Serialize arrays/objects to JSON with `JSON_THROW_ON_ERROR` to detect encoding issues.
- Provide PSR-3 style hooks for future logging integrations.

Integrate with high-risk flows:

- Authentication events (login success/failure, password reset) → tie into Phase 1 security upgrades.
- Financial approvals (PO, PR, invoice, voucher) → log business reason and workflow status.
- Master data edits (company, product) → include diff summary for easier review.

---

## 6. Deployment Runbook

1. **Prep**: Backup DB; ensure timestamps & FK migrations have landed.
2. **Schema**: Apply master audit table + per-table audit tables via `migrations/phase2_audit/000_create_tables.sql`.
3. **Triggers**: Apply generated trigger scripts (`010_triggers.sql`). Validate with `SHOW TRIGGERS LIKE 'po\_%';`.
4. **App Update**: Deploy `AuditService` + wiring in PHP controllers; feature flag via `AUDIT_ENABLED` env config.
5. **Smoke Test**: Perform CRUD on each audited entity; confirm rows appear in `audit_log` and respective `_audit` table.
6. **Monitoring**: Add Grafana/PMM panels tracking audit table growth, trigger execution time, and errors.

---

## 7. Validation Checklist

- [ ] `SELECT COUNT(*) FROM audit_log;` increases for every CRUD action executed during QA.
- [ ] `SELECT action, COUNT(*) FROM po_audit GROUP BY action;` reflects expected operations.
- [ ] Application log contains `AuditService` entries without errors.
- [ ] Triggers exist for all targeted tables (`SHOW TRIGGERS WHERE Trigger LIKE '%_audit_%';`).
- [ ] Performance impact <5% on write-heavy operations (verify via slow query log).
- [ ] Retention policy documented (e.g., purge data older than 18 months via scheduled job).

---

## 8. Rollback Plan

1. Disable feature flag `AUDIT_ENABLED=false` to stop application logging.
2. Drop triggers via `migrations/phase2_audit/rollback/010_drop_triggers.sql`.
3. Drop audit tables if necessary (`rollback/000_drop_tables.sql`).
4. Retain collected audit data in archival storage if compliance mandates.

---

## 9. Deliverables

- `migrations/phase2_audit/000_create_tables.sql`
- `migrations/phase2_audit/010_triggers.sql`
- `migrations/phase2_audit/rollback/*`
- `resources/classes/AuditService.php` + unit tests
- `docs/audit/audit-log-playbook.md` (operations + retention)
- Grafana/PMM dashboard JSON exports

---

**Next Up**: After audit logging is deployed, run the invalid-date remediation (`PHASE2_INVALID_DATES.md`) to clean remaining legacy values feeding these trails.
