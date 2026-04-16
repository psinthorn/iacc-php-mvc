-- Add driver and vehicle columns to tour_bookings
-- Date: 2026-04-16

ALTER TABLE tour_bookings
  ADD COLUMN driver_name VARCHAR(100) DEFAULT NULL AFTER remark,
  ADD COLUMN vehicle_no VARCHAR(50) DEFAULT NULL AFTER driver_name;
