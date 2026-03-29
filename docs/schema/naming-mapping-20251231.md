# Naming Standardization Mapping (Snapshot: 2025-12-31)

This document captures the authoritative mapping between legacy schema identifiers and their Phase 2b naming targets. Use it as the source for the spreadsheet deliverable (`docs/schema/naming-mapping-YYYYMMDD.xlsx`). Update the Markdown in Git for history, then export to XLSX if stakeholders need spreadsheet formatting.

| object_type | legacy_name | target_name | priority | owner | status | notes |
| --- | --- | --- | --- | --- | --- | --- |
| table | company | company | P0 | Data Team | pending | Remains the canonical entity; only column-level renames required. |
| table | po | purchase_order | P0 | Ops Team | pending | Requires compatibility view `po` → `purchase_order` during rollout. |
| table | pr | purchase_request | P0 | Ops Team | pending | Switch API + PHP references after view deployed. |
| table | iv | invoice | P0 | Billing | pending | Rename table + primary key to `invoice_id`. |
| table | deliver | delivery_note | P1 | Logistics | pending | Consider splitting header/items in Phase 2c. |
| table | map_type_to_brand | brand_type | P1 | Product | pending | Rename columns to `brand_id`, `type_id`. |
| table | company_addr | company_address | P1 | Data Team | pending | Column rename: `com_id` → `company_id`. |
| table | company_credit | company_credit | P1 | Finance | pending | Standardize FK names `customer_company_id`, `vendor_company_id`. |
| column | po.po_id | purchase_order.purchase_order_id | P0 | Ops Team | pending | Auto-inc INT PK. |
| column | po.ven_id | purchase_order.vendor_company_id | P0 | Ops Team | pending | Matches FK naming format. |
| column | po.cus_id | purchase_order.customer_company_id | P0 | Ops Team | pending | Nullable for internal/vendor-only orders. |
| column | pr.pr_id | purchase_request.purchase_request_id | P0 | Ops Team | pending | Primary key rename. |
| column | pr.type_id | purchase_request.product_type_id | P0 | Product | pending | Avoid collision with renamed `type` table. |
| column | iv.iv_id | invoice.invoice_id | P0 | Billing | pending | Rename columns + adjust PDFs. |
| column | iv.cus_id | invoice.customer_company_id | P0 | Billing | pending | Aligns with PO naming. |
| column | deliver.po_id | delivery_note.purchase_order_id | P1 | Logistics | pending | FK rename after PO table swap. |
| column | company_addr.addr_id | company_address.company_address_id | P1 | Data Team | pending | Primary key rename. |
| column | company_addr.com_id | company_address.company_id | P1 | Data Team | pending | Already enforced via FK, only rename needed. |
| column | company_credit.cus_id | company_credit.customer_company_id | P1 | Finance | pending | Update reports referencing `cus_id`. |
| column | company_credit.ven_id | company_credit.vendor_company_id | P1 | Finance | pending | Mirrors PO naming. |
| view | v_po_summary | v_purchase_order_summary | P2 | BI | pending | Update Looker/Metabase dashboards. |
| procedure | sp_report_po | sp_report_purchase_order | P2 | Reporting | pending | Ensure new table names referenced. |

> **Guidance**
>
> - Keep `priority` aligned with rollout waves: P0 (blocking), P1 (within first sprint), P2 (can trail by 1-2 weeks).
> - Set `status` to `pending`, `in-progress`, `blocked`, or `complete`.
> - Capture cross-team dependencies in `notes`.

## Next Actions

1. Once `scripts/export-schema-inventory.sh` generates the CSV snapshot, import it into this table to ensure coverage of every object.
2. Share the Markdown (or exported XLSX) with stakeholders for approval before authoring any DDL.
3. After approval, create per-table migration specs under `migrations/phase2_naming/` following numbering guidelines (e.g., `010_purchase_order.sql`).
