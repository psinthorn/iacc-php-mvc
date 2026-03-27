-- Migration: 002_usage_logs_nullable_fk
-- Date: 2026-03-27
-- Description: Make company_id and api_key_id nullable in api_usage_logs
--              and drop foreign key constraints to allow logging
--              unauthenticated (failed auth) requests.

-- Drop foreign key constraints
ALTER TABLE `api_usage_logs` DROP FOREIGN KEY `fk_log_company`;
ALTER TABLE `api_usage_logs` DROP FOREIGN KEY `fk_log_key`;

-- Make columns nullable (NULL = unauthenticated request)
ALTER TABLE `api_usage_logs` MODIFY `company_id` int(11) DEFAULT NULL;
ALTER TABLE `api_usage_logs` MODIFY `api_key_id` int(11) DEFAULT NULL;

-- Keep indexes for query performance (they already exist)
-- idx_company_date (company_id, created_at)
-- idx_api_key (api_key_id)
