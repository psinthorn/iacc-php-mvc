-- Add product/model FK and pax lines JSON to tour_booking_items
ALTER TABLE tour_booking_items
  ADD COLUMN product_type_id INT DEFAULT NULL AFTER notes,
  ADD COLUMN model_id INT DEFAULT NULL AFTER product_type_id,
  ADD COLUMN pax_lines_json TEXT DEFAULT NULL AFTER model_id;
