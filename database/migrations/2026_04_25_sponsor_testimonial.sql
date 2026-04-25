-- =============================================================================
-- Migration: 2026_04_25_sponsor_testimonial.sql
-- Description: Adopter/Sponsor tagging + testimonial for landing page
-- Tables: api_subscriptions (add 4 columns)
-- MySQL 5.7 compatible, idempotent
-- =============================================================================

-- sponsor_type: NULL = normal, 'adopter' or 'sponsor'
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `api_subscriptions` ADD COLUMN `sponsor_type` ENUM(''adopter'',''sponsor'') NULL DEFAULT NULL COMMENT ''NULL=normal, adopter=project adopter, sponsor=financial sponsor'' AFTER `enabled`',
    'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_subscriptions' AND COLUMN_NAME = 'sponsor_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- show_on_landing: show this company on landing page testimonials
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `api_subscriptions` ADD COLUMN `show_on_landing` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = show on public landing page'' AFTER `sponsor_type`',
    'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_subscriptions' AND COLUMN_NAME = 'show_on_landing');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- testimonial: quote to display on landing page
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `api_subscriptions` ADD COLUMN `testimonial` TEXT NULL COMMENT ''Public testimonial quote'' AFTER `show_on_landing`',
    'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_subscriptions' AND COLUMN_NAME = 'testimonial');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- testimonial_contact: name/title shown under the quote
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `api_subscriptions` ADD COLUMN `testimonial_contact` VARCHAR(255) NULL COMMENT ''Name & title shown under testimonial'' AFTER `testimonial`',
    'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_subscriptions' AND COLUMN_NAME = 'testimonial_contact');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
