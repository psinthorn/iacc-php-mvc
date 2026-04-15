-- =========================================================
-- Migration 004: AI Conversations & Action Log
-- Created: 2026-01-04
-- Description: Tables for AI chatbot conversation history
--              and action audit logging
-- =========================================================

-- AI Conversation History
-- Stores all chat messages between users and AI assistant
CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    role ENUM('user', 'assistant', 'system', 'tool') NOT NULL,
    content TEXT NOT NULL,
    tool_calls JSON NULL COMMENT 'Tool calls made by assistant',
    tool_results JSON NULL COMMENT 'Results from tool execution',
    tokens_used INT DEFAULT 0,
    model VARCHAR(50) DEFAULT 'llama3.1:8b',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session (session_id),
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Action Audit Log
-- Tracks all database modifications made through AI agent
CREATE TABLE IF NOT EXISTS ai_action_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    conversation_id INT NULL COMMENT 'Reference to ai_conversations.id',
    action_type VARCHAR(50) NOT NULL COMMENT 'Tool/action name',
    action_params JSON NOT NULL COMMENT 'Parameters passed to the action',
    affected_table VARCHAR(50) NULL COMMENT 'Database table modified',
    affected_id INT NULL COMMENT 'Primary key of affected record',
    previous_value JSON NULL COMMENT 'Value before modification (for rollback)',
    new_value JSON NULL COMMENT 'Value after modification',
    status ENUM('pending', 'confirmed', 'executed', 'cancelled', 'failed') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL COMMENT 'Error details if failed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    executed_at TIMESTAMP NULL,
    
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_status (status),
    INDEX idx_action_type (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Chat Sessions
-- Tracks chat sessions for better conversation management
CREATE TABLE IF NOT EXISTS ai_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NULL COMMENT 'Auto-generated session title',
    message_count INT DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Sample data for testing (optional, comment out in production)
-- =========================================================
-- INSERT INTO ai_sessions (session_id, company_id, user_id, title) 
-- VALUES ('test-session-001', 95, 1, 'Test Conversation');
