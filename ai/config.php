<?php
/**
 * AI Agent Configuration
 * 
 * Settings for Ollama LLM and agent behavior
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-04
 */

// Prevent direct access
if (!defined('IACC_ROOT')) {
    define('IACC_ROOT', dirname(__DIR__));
}

return [
    // =========================================================
    // Ollama LLM Configuration
    // =========================================================
    'ollama' => [
        // API endpoint (use container name in Docker, localhost otherwise)
        'base_url' => getenv('OLLAMA_URL') ?: 'http://ollama:11434',
        
        // Model to use (pull with: docker exec iacc_ollama ollama pull llama3.2:3b)
        'model' => getenv('OLLAMA_MODEL') ?: 'llama3.2:3b',
        
        // Request timeout in seconds
        'timeout' => 120,
        
        // Generation parameters
        'temperature' => 0.7,      // 0.0 = deterministic, 1.0 = creative
        'max_tokens' => 2048,      // Maximum response length
        'top_p' => 0.9,            // Nucleus sampling
        'top_k' => 40,             // Top-k sampling
        
        // Context window size
        'num_ctx' => 4096,         // Context window (depends on model)
    ],
    
    // =========================================================
    // Agent Behavior Configuration
    // =========================================================
    'agent' => [
        // Require user confirmation before database writes
        'require_confirmation' => true,
        
        // Maximum messages to keep in conversation context
        'max_history' => 20,
        
        // Session timeout in minutes
        'session_timeout' => 60,
        
        // Enable/disable specific capabilities
        'capabilities' => [
            'read' => true,        // Query data
            'create' => true,      // Create new records
            'update' => true,      // Update existing records
            'delete' => false,     // Soft delete only (safety)
        ],
        
        // Tables the agent can access
        'allowed_tables' => [
            'iv',           // Invoices
            'po',           // Purchase Orders
            'pr',           // Quotations
            'pay',          // Payments
            'company',      // Customers/Companies
            'product',      // Products
            'deliver',      // Deliveries
            'voucher',      // Vouchers
            'receipt',      // Receipts
        ],
        
        // Actions that require double confirmation
        'high_risk_actions' => [
            'delete_invoice',
            'delete_po',
            'void_payment',
        ],
    ],
    
    // =========================================================
    // Security Configuration
    // =========================================================
    'security' => [
        // Minimum user level required to use AI assistant
        'min_user_level' => 0,
        
        // Enable audit logging for all AI actions
        'audit_logging' => true,
        
        // Rate limiting (requests per minute per user)
        'rate_limit' => 30,
        
        // IP whitelist (empty = allow all)
        'ip_whitelist' => [],
    ],
    
    // =========================================================
    // UI Configuration
    // =========================================================
    'ui' => [
        // Show AI assistant button
        'enabled' => true,
        
        // Position: 'bottom-right', 'bottom-left', 'top-right', 'top-left'
        'position' => 'bottom-right',
        
        // Theme: 'light', 'dark', 'auto'
        'theme' => 'light',
        
        // Quick actions to show in chat
        'quick_actions' => [
            ['label' => 'Unpaid Invoices', 'prompt' => 'Show me all unpaid invoices'],
            ['label' => 'Today\'s Summary', 'prompt' => 'Give me a summary of today\'s transactions'],
            ['label' => 'Overdue Payments', 'prompt' => 'Which invoices are overdue?'],
        ],
    ],
    
    // =========================================================
    // Prompts Configuration
    // =========================================================
    'prompts' => [
        // System prompt file path
        'system_prompt_file' => IACC_ROOT . '/ai/prompts/system-prompt.txt',
        
        // Language preference
        'default_language' => 'auto',  // 'en', 'th', or 'auto' (detect from user)
    ],
];
