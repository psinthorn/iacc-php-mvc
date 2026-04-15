-- Migration: Add result column to ai_action_log table
-- Date: 2026-01-05
-- Description: Stores the result of executed AI actions

ALTER TABLE ai_action_log ADD COLUMN result JSON NULL AFTER new_value;
