-- ============================================================
-- Migration: Tour Booking Performance Indexes
-- Issue #60 — booking list slow with 500+ bookings
-- Safe to run multiple times (idempotent via INFORMATION_SCHEMA checks)
-- ============================================================

-- 1. Composite index: company_id + deleted_at
--    Covers the hot path: WHERE company_id = X AND deleted_at IS NULL
SET @i = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND INDEX_NAME = 'idx_company_deleted');
SET @s = IF(@i = 0,
    'ALTER TABLE tour_bookings ADD INDEX idx_company_deleted (company_id, deleted_at)',
    'SELECT "idx_company_deleted already exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 2. Composite index: company_id + status + deleted_at
--    Covers status filter queries
SET @i = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND INDEX_NAME = 'idx_company_status');
SET @s = IF(@i = 0,
    'ALTER TABLE tour_bookings ADD INDEX idx_company_status (company_id, status, deleted_at)',
    'SELECT "idx_company_status already exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 3. Composite index: company_id + travel_date
--    Covers date range filter queries
SET @i = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_bookings'
            AND INDEX_NAME = 'idx_company_travel_date');
SET @s = IF(@i = 0,
    'ALTER TABLE tour_bookings ADD INDEX idx_company_travel_date (company_id, travel_date)',
    'SELECT "idx_company_travel_date already exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- 4. Index on tour_booking_contacts.booking_id
--    Used in the EXISTS subquery for contact_name search
SET @i = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tour_booking_contacts'
            AND INDEX_NAME = 'idx_tbc_booking_id');
SET @s = IF(@i = 0,
    'ALTER TABLE tour_booking_contacts ADD INDEX idx_tbc_booking_id (booking_id)',
    'SELECT "idx_tbc_booking_id already exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
