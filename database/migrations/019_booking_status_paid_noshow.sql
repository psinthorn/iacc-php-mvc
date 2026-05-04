-- ============================================================================
-- Migration 019: Add 'paid' and 'no_show' to tour_bookings.status enum
-- Date: 2026-04-30
--
-- New status flow:
--   draft → confirmed → paid → completed
--                              ↓
--                            no_show (customer didn't arrive)
--   any → cancelled
--
-- Allotment counting: confirmed, paid, completed, no_show all count
--   (no_show: the seat WAS reserved, just unused — too late to resell)
-- Cancelled and draft do NOT count toward allotment caps.
-- ============================================================================

ALTER TABLE `tour_bookings`
    MODIFY COLUMN `status`
    ENUM('draft','confirmed','paid','completed','no_show','cancelled')
    NOT NULL DEFAULT 'draft';

-- ROLLBACK (only if no rows actually use the new statuses):
--   ALTER TABLE tour_bookings MODIFY COLUMN status
--     ENUM('draft','confirmed','completed','cancelled') NOT NULL DEFAULT 'draft';
