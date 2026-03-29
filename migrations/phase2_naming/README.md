# Phase 2b Naming Migration Scripts

This folder will hold the forward and rollback scripts for the naming-standardization wave. Follow these conventions when adding files:

1. **Numbered batches**: Use zero-padded increments of 10 to preserve ordering (e.g., `010_purchase_order.sql`, `010_purchase_order_rollback.sql`).
2. **Atomic changes**: Keep each script focused on a single table or related group (table + compatibility view).
3. **Transaction safety**: Wrap rename/copy operations in explicit transactions and include verification queries at the bottom.
4. **Compatibility views**: When renaming a table, add a view or synonym script (`*_compat.sql`) that maintains the legacy name until the PHP layer is updated.
5. **Testing hooks**: Append comments indicating which automated/manual tests must run after applying the script.

> Store execution notes in `docs/schema/naming-migration-log.md` after every run.
