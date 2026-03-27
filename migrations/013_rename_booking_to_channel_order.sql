-- Migration: Rename booking_requests → channel_orders, bookings_limit → orders_limit
-- Part of the Booking → ChannelOrder naming convention refactor
-- Date: 2026-03-28

-- 1. Rename the main orders table
RENAME TABLE `booking_requests` TO `channel_orders`;

-- 2. Rename the quota column in subscriptions
ALTER TABLE `api_subscriptions` CHANGE `bookings_limit` `orders_limit` INT(11) NOT NULL DEFAULT 50;

-- 3. Update webhook event names in existing webhooks (booking.* → order.*)
UPDATE `api_webhooks` SET `events` = REPLACE(`events`, 'booking.', 'order.') WHERE `events` LIKE '%booking.%';

-- 4. Update event names in webhook delivery logs
UPDATE `api_webhook_deliveries` SET `event` = REPLACE(`event`, 'booking.', 'order.') WHERE `event` LIKE 'booking.%';
