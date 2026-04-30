-- ============================================================================
-- Migration 015: Tour Operator Documents
-- Date: 2026-04-28
--
-- Operator-uploaded documents shared with agents (PDFs, brochures, T&Cs, etc.)
-- Visibility: 'all_agents' = visible to all approved agents
--             'contract'   = visible only to agents on a specific contract
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tour_operator_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `operator_company_id` INT(11) NOT NULL COMMENT 'FK company.id — owner',
    `contract_id` INT(11) DEFAULT NULL COMMENT 'FK agent_contracts.id — NULL if visible to all',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Original filename',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Server path under uploads/',
    `file_size` INT(11) NOT NULL DEFAULT 0 COMMENT 'Bytes',
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `category` ENUM('contract','brochure','terms','rate_sheet','other') NOT NULL DEFAULT 'other',
    `visibility` ENUM('all_agents','contract','operator_only') NOT NULL DEFAULT 'all_agents',
    `download_count` INT(11) NOT NULL DEFAULT 0,
    `uploaded_by` INT(11) DEFAULT NULL COMMENT 'FK user.usr_id',
    `deleted_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_operator` (`operator_company_id`, `deleted_at`),
    KEY `idx_contract` (`contract_id`),
    KEY `idx_visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Documents shared by tour operators with their agents';
