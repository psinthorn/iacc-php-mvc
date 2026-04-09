-- Quick Create Module: Add auto_generated flag to document tables
-- This allows tracking which documents were auto-created by the Quick Create module
-- vs manually created through the traditional flow.
-- Note: MySQL 5.7 does not support IF NOT EXISTS for ADD COLUMN.
-- Run once only, or check column existence before running.

ALTER TABLE `pr` ADD COLUMN `auto_generated` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `po` ADD COLUMN `auto_generated` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `deliver` ADD COLUMN `auto_generated` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `iv` ADD COLUMN `auto_generated` TINYINT(1) NOT NULL DEFAULT 0;
