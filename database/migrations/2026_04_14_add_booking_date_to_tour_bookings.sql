-- Add booking_date column to separate booking date from trip/travel date
-- booking_date = when the booking was made
-- travel_date  = when the trip happens

ALTER TABLE tour_bookings ADD COLUMN booking_date DATE NULL AFTER booking_number;

-- Backfill existing records with created_at date
UPDATE tour_bookings SET booking_date = DATE(created_at) WHERE booking_date IS NULL;
