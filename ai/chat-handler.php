<?php
/**
 * AI Chat Handler
 * 
 * Main API endpoint for AI chatbot conversations
 * Handles user messages, tool execution, and response generation
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-04
 */

// Change to root directory for proper includes
chdir(__DIR__ . '/..');

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Allow CORS with credentials (use specific origin, not wildcard)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Handle ping/status BEFORE auth check (these are public endpoints for the widget)
$action = $_GET['action'] ?? '';
if ($action === 'ping' || $action === 'status') {
    require_once __DIR__ . '/ai-provider.php';
    
    $settings = AIProvider::getSettings();
    $provider = $settings['provider'] ?? 'openai';
    
    $providerInfo = [
        'name' => $provider,
        'available' => false,
        'model' => '',
        'display_name' => '',
    ];
    
    if ($provider === 'openai') {
        $providerInfo['model'] = $settings['openai_model'] ?? 'gpt-4o-mini';
        $providerInfo['available'] = !empty($settings['openai_api_key']);
        $providerInfo['display_name'] = 'OpenAI';
    } else {
        $providerInfo['model'] = $settings['ollama_model'] ?? 'llama3.2:3b';
        $providerInfo['display_name'] = 'Ollama';
        if ($settings['ollama_enabled'] ?? false) {
            require_once __DIR__ . '/ollama-client.php';
            try {
                $ollama = new OllamaClient(['base_url' => $settings['ollama_url'] ?? 'http://ollama:11434']);
                $health = $ollama->checkHealth();
                $providerInfo['available'] = $health['available'] ?? false;
            } catch (Exception $e) {
                $providerInfo['available'] = false;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'provider' => $providerInfo,
        'settings' => [
            'temperature' => $settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? 2048,
        ],
        'timestamp' => date('c'),
    ]);
    exit;
}

// Include required files
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ollama-client.php';
require_once __DIR__ . '/openai-client.php';
require_once __DIR__ . '/ai-provider.php';
require_once __DIR__ . '/agent-tools.php';
require_once __DIR__ . '/agent-executor.php';

/**
 * Main Chat Handler Class
 */
class ChatHandler
{
    private $db;
    private array $config;
    private $aiProvider;
    private AgentExecutor $executor;
    private int $companyId;
    private int $userId;
    private string $sessionId;
    
    public function __construct()
    {
        // Load config
        $this->config = require __DIR__ . '/config.php';
        
        // Initialize database
        $this->initDatabase();
        
        // Check authentication
        $this->checkAuth();
        
        // Initialize AI Provider (uses settings from cache/ai-settings.json)
        $this->aiProvider = new AIProvider();
        
        // Initialize session
        $this->sessionId = $this->getOrCreateSession();
        
        // Initialize executor
        $this->executor = new AgentExecutor(
            $this->db,
            $this->companyId,
            $this->userId,
            $this->sessionId,
            $this->config['agent']
        );
    }
    
    /**
     * Initialize database connection
     */
    private function initDatabase(): void
    {
        try {
            $this->db = new PDO(
                'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
                'root',
                'root',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $this->sendError('Database connection failed', 500);
        }
    }
    
    /**
     * Check user authentication
     */
    private function checkAuth(): void
    {
        // Check if user is logged in
        if (empty($_SESSION['user_id'])) {
            $this->sendError('Authentication required', 401);
        }
        
        $this->userId = (int) $_SESSION['user_id'];
        $this->companyId = (int) ($_SESSION['com_id'] ?? 95);
        
        // Check minimum user level
        $userLevel = (int) ($_SESSION['user_level'] ?? 0);
        $minLevel = $this->config['security']['min_user_level'] ?? 0;
        
        if ($userLevel < $minLevel) {
            $this->sendError('Insufficient permissions', 403);
        }
    }
    
    /**
     * Get or create chat session
     */
    private function getOrCreateSession(): string
    {
        // Check for existing session in request
        $input = $this->getInput();
        $sessionId = $input['session_id'] ?? null;
        
        if ($sessionId) {
            // Verify session belongs to user
            $sql = "SELECT session_id FROM ai_sessions 
                    WHERE session_id = ? AND company_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $this->companyId, $this->userId]);
            
            if ($stmt->fetch()) {
                return $sessionId;
            }
        }
        
        // Create new session
        $sessionId = 'chat_' . bin2hex(random_bytes(16));
        
        $sql = "INSERT INTO ai_sessions (session_id, company_id, user_id, created_at)
                VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $this->companyId, $this->userId]);
        
        return $sessionId;
    }
    
    /**
     * Handle incoming request
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'chat';
        
        switch ($action) {
            case 'chat':
                $this->handleChat();
                break;
                
            case 'confirm':
                $this->handleConfirm();
                break;
                
            case 'cancel':
                $this->handleCancel();
                break;
                
            case 'history':
                $this->handleHistory();
                break;
                
            case 'health':
                $this->handleHealth();
                break;
                
            case 'sessions':
                $this->handleSessions();
                break;
                
            default:
                $this->sendError('Unknown action', 400);
        }
    }
    
    /**
     * Handle chat message
     */
    private function handleChat(): void
    {
        $input = $this->getInput();
        $message = trim($input['message'] ?? '');
        
        if (empty($message)) {
            $this->sendError('Message is required', 400);
        }
        
        // Save user message
        $this->saveMessage('user', $message);
        
        // Get conversation history
        $history = $this->getConversationHistory();
        
        // Build messages array for Ollama
        $messages = $this->buildMessages($history, $message);
        
        // Get all available tools (business + schema discovery)
        $tools = getAllTools();
        
        // Send to AI provider (OpenAI or Ollama based on settings)
        $result = $this->aiProvider->chat($messages, $tools);
        
        if (!$result['success']) {
            $this->sendError('AI service error: ' . ($result['error'] ?? 'Unknown'), 500);
        }
        
        // Process response
        $response = $this->processResponse($result);
        
        // Save assistant message
        $this->saveMessage('assistant', $response['message'], $response['tool_calls'] ?? null);
        
        // Update session
        $this->updateSession();
        
        $this->sendSuccess([
            'session_id' => $this->sessionId,
            'message' => $response['message'],
            'tool_calls' => $response['tool_calls'] ?? [],
            'tool_results' => $response['tool_results'] ?? [],
            'requires_confirmation' => $response['requires_confirmation'] ?? false,
            'confirmation_id' => $response['confirmation_id'] ?? null,
        ]);
    }
    
    /**
     * Build messages array for Ollama
     */
    private function buildMessages(array $history, string $currentMessage): array
    {
        $messages = [];
        
        // Add system prompt
        $systemPrompt = $this->getSystemPrompt();
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];
        
        // Add conversation history
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $currentMessage,
        ];
        
        return $messages;
    }
    
    /**
     * Get system prompt with context
     */
    private function getSystemPrompt(): string
    {
        $promptFile = $this->config['prompts']['system_prompt_file'] ?? '';
        
        if (file_exists($promptFile)) {
            $prompt = file_get_contents($promptFile);
        } else {
            $prompt = $this->getDefaultSystemPrompt();
        }
        
        // Replace placeholders
        $companyName = $_SESSION['company_name'] ?? 'Company';
        $userName = $_SESSION['user_name'] ?? 'User';
        $userLevel = $_SESSION['user_level'] ?? 0;
        
        $replacements = [
            '{company_id}' => $this->companyId,
            '{company_name}' => $companyName,
            '{user_name}' => $userName,
            '{user_level}' => $userLevel,
            '{current_date}' => date('Y-m-d'),
            '{current_time}' => date('H:i:s'),
        ];
        
        $prompt = str_replace(array_keys($replacements), array_values($replacements), $prompt);
        
        // Append cached database schema if available
        $schemaContext = $this->getSchemaContext();
        if ($schemaContext) {
            $prompt .= "\n\n" . $schemaContext;
        }
        
        return $prompt;
    }
    
    /**
     * Get cached database schema context
     */
    private function getSchemaContext(): string
    {
        require_once __DIR__ . '/schema-discovery.php';
        
        $cached = SchemaDiscovery::loadCompactSchema();
        if ($cached) {
            return "DATABASE SCHEMA REFERENCE:\n" . $cached;
        }
        
        // Minimal schema if cache not available
        return <<<SCHEMA
DATABASE SCHEMA QUICK REFERENCE:
- iv: Invoices (tex→po.id, taxrw=invoice#, status_iv, payment_status)
- po: Purchase Orders (id, ref→pr.id, name, date, status)
- pr: Projects (id, cus_id→company, ven_id→company)
- product: Line items (po_id, price, quantity)
- pay: Payments (po_id, volumn=amount)
- company: Companies (id, name_en, name_sh)

Query Pattern: iv → po → pr → company, with product/pay joins for totals
Use list_database_tables or describe_table tools for more details.
SCHEMA;
    }
    
    /**
     * Get default system prompt
     */
    private function getDefaultSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant for iACC Accounting Management System. You help users manage invoices, purchase orders, payments, and customer data.

CAPABILITIES:
- Search and view invoices, POs, payments, customers
- Mark invoices as paid
- Update order statuses
- Generate summaries and reports
- Add notes to records

RULES:
1. Always verify user intent before making changes
2. For any database modification, provide a summary and ask for confirmation
3. Always filter data by the user's company (multi-tenant system)
4. Format currency as Thai Baht (฿) with thousands separators
5. Format dates as DD/MM/YYYY for display
6. Be concise but informative
7. If unsure, ask clarifying questions
8. Respond in the same language the user uses (Thai or English)

CURRENT CONTEXT:
- Company: {company_name} (ID: {company_id})
- User: {user_name}
- User Level: {user_level}
- Date: {current_date}
- Time: {current_time}

When you need data, use the available tools. Present results in a clear, formatted way.
PROMPT;
    }
    
    /**
     * Process Ollama response and execute tools
     */
    private function processResponse(array $result): array
    {
        // Get response text and tool calls from result
        $message = $result['response'] ?? '';
        $toolCalls = $result['tool_calls'] ?? [];
        
        $toolResults = [];
        $requiresConfirmation = false;
        $confirmationId = null;
        
        // Execute tool calls
        foreach ($toolCalls as $call) {
            // Handle both OpenAI format (name, arguments) and function format (function.name, function.arguments)
            $toolName = $call['name'] ?? $call['function']['name'] ?? '';
            $params = [];
            
            // Parse arguments - handle both formats
            $arguments = $call['arguments'] ?? $call['function']['arguments'] ?? null;
            if ($arguments !== null) {
                if (is_string($arguments)) {
                    $params = json_decode($arguments, true) ?? [];
                } else {
                    $params = $arguments;
                }
            }
            
            if (empty($toolName)) {
                continue;
            }
            
            // Execute tool
            $toolResult = $this->executor->execute($toolName, $params);
            $toolResults[] = [
                'tool' => $toolName,
                'params' => $params,
                'result' => $toolResult,
            ];
            
            // Check if confirmation needed
            if (!empty($toolResult['requires_confirmation'])) {
                $requiresConfirmation = true;
                $confirmationId = $toolResult['confirmation_id'];
            }
        }
        
        // If we have tool results, send them back to AI for a natural language response
        if (!empty($toolResults) && empty($message)) {
            $message = $this->generateResponseFromToolResults($toolResults);
        } elseif (!empty($toolResults)) {
            // Append formatted results to the existing message
            foreach ($toolResults as $tr) {
                if (!empty($tr['result']['success'])) {
                    $formatted = $this->formatToolResult($tr['tool'], $tr['result']['result'] ?? $tr['result']);
                    if (!empty($formatted)) {
                        $message .= "\n\n" . $formatted;
                    }
                }
            }
        }
        
        return [
            'message' => $message,
            'tool_calls' => $toolCalls,
            'tool_results' => $toolResults,
            'requires_confirmation' => $requiresConfirmation,
            'confirmation_id' => $confirmationId,
        ];
    }
    
    /**
     * Generate a natural language response from tool results by sending back to AI
     */
    private function generateResponseFromToolResults(array $toolResults): string
    {
        $messages = [];
        
        // Add context about what was requested
        $messages[] = [
            'role' => 'system',
            'content' => 'Based on the tool results below, provide a helpful summary response to the user. Format data nicely with bullet points or tables if appropriate. Keep it concise.',
        ];
        
        // Format tool results for AI
        $resultsText = '';
        foreach ($toolResults as $tr) {
            $toolName = $tr['tool'];
            $result = $tr['result'];
            
            if ($result['success'] ?? false) {
                $data = $result['result'] ?? $result;
                $resultsText .= "Tool: {$toolName}\nResult: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            } else {
                $error = $result['error'] ?? 'Unknown error';
                $resultsText .= "Tool: {$toolName}\nError: {$error}\n\n";
            }
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => "Here are the results from the database queries:\n\n" . $resultsText . "\n\nPlease summarize this for the user in a clear, friendly way.",
        ];
        
        // Call AI without tools to get natural language response
        $response = $this->aiProvider->chat($messages, []);
        
        if ($response['success'] ?? false) {
            return $response['response'] ?? $this->formatToolResultsFallback($toolResults);
        }
        
        // Fallback to simple formatting
        return $this->formatToolResultsFallback($toolResults);
    }
    
    /**
     * Fallback formatting when AI call fails
     */
    private function formatToolResultsFallback(array $toolResults): string
    {
        $output = '';
        
        foreach ($toolResults as $tr) {
            $result = $tr['result'];
            
            if ($result['success'] ?? false) {
                $data = $result['result'] ?? $result;
                $output .= $this->formatToolResult($tr['tool'], $data);
            } else {
                $output .= "❌ Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
        
        return $output ?: 'ไม่พบข้อมูล / No data found';
    }
    
    /**
     * Format tool result for display
     */
    private function formatToolResult(string $toolName, $result): string
    {
        if (empty($result)) {
            return '';
        }
        
        // Handle array results
        if (is_array($result)) {
            // Check if it's a list of items
            if (isset($result[0]) && is_array($result[0])) {
                return $this->formatResultsTable($toolName, $result);
            }
            
            // Check for specific result structures
            if (isset($result['data']) && is_array($result['data'])) {
                return $this->formatResultsTable($toolName, $result['data']);
            }
            
            if (isset($result['count'])) {
                return "📊 พบ {$result['count']} รายการ";
            }
            
            if (isset($result['summary'])) {
                return "📊 " . $result['summary'];
            }
            
            // Format as key-value pairs
            $lines = [];
            foreach ($result as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $lines[] = "• **{$key}**: {$value}";
            }
            return implode("\n", $lines);
        }
        
        return (string) $result;
    }
    
    /**
     * Format array results as a simple table
     */
    private function formatResultsTable(string $toolName, array $items): string
    {
        if (empty($items)) {
            return "ไม่พบข้อมูล / No data found";
        }
        
        $count = count($items);
        $output = "📋 **พบ {$count} รายการ**\n\n";
        
        // Show first 10 items
        $shown = array_slice($items, 0, 10);
        
        foreach ($shown as $i => $item) {
            $num = $i + 1;
            
            // Try to create a meaningful summary for each item
            $summary = $this->createItemSummary($item);
            $output .= "{$num}. {$summary}\n";
        }
        
        if ($count > 10) {
            $remaining = $count - 10;
            $output .= "\n... และอีก {$remaining} รายการ";
        }
        
        return $output;
    }
    
    /**
     * Create a summary line for an item
     */
    private function createItemSummary(array $item): string
    {
        $parts = [];
        
        // Priority fields to show
        $priorityFields = [
            'inv_no', 'invoice_no', 'po_no', 'number', 'name', 'company_name', 'customer_name',
            'total', 'amount', 'grand_total', 'status', 'date', 'due_date', 'inv_date'
        ];
        
        foreach ($priorityFields as $field) {
            if (isset($item[$field]) && $item[$field] !== null && $item[$field] !== '') {
                $value = $item[$field];
                
                // Format money
                if (in_array($field, ['total', 'amount', 'grand_total'])) {
                    $value = '฿' . number_format((float)$value, 2);
                }
                
                $parts[] = $value;
                
                if (count($parts) >= 4) {
                    break;
                }
            }
        }
        
        return implode(' | ', $parts) ?: json_encode($item);
    }
    
    /**
     * Handle action confirmation
     */
    private function handleConfirm(): void
    {
        $input = $this->getInput();
        $confirmationId = (int) ($input['confirmation_id'] ?? 0);
        
        if (!$confirmationId) {
            $this->sendError('Confirmation ID required', 400);
        }
        
        $result = $this->executor->confirmAction($confirmationId);
        
        if ($result['success']) {
            // Save confirmation result as assistant message
            $message = "✅ " . ($result['result']['message'] ?? 'Action completed successfully');
            $this->saveMessage('assistant', $message);
            
            $this->sendSuccess([
                'session_id' => $this->sessionId,
                'message' => $message,
                'result' => $result,
            ]);
        } else {
            $this->sendError($result['error'] ?? 'Confirmation failed', 400);
        }
    }
    
    /**
     * Handle action cancellation
     */
    private function handleCancel(): void
    {
        $input = $this->getInput();
        $confirmationId = (int) ($input['confirmation_id'] ?? 0);
        
        if (!$confirmationId) {
            $this->sendError('Confirmation ID required', 400);
        }
        
        $result = $this->executor->cancelAction($confirmationId);
        
        $message = "❌ Action cancelled";
        $this->saveMessage('assistant', $message);
        
        $this->sendSuccess([
            'session_id' => $this->sessionId,
            'message' => $message,
        ]);
    }
    
    /**
     * Handle history request
     */
    private function handleHistory(): void
    {
        $history = $this->getConversationHistory(50);
        
        $this->sendSuccess([
            'session_id' => $this->sessionId,
            'messages' => $history,
        ]);
    }
    
    /**
     * Handle health check
     */
    private function handleHealth(): void
    {
        $settings = AIProvider::getSettings();
        $provider = $settings['provider'] ?? 'openai';
        
        $this->sendSuccess([
            'status' => 'ok',
            'provider' => $provider,
            'model' => $this->aiProvider->getModel(),
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
        ]);
    }
    
    /**
     * Handle sessions list
     */
    private function handleSessions(): void
    {
        $sql = "SELECT session_id, title, message_count, last_activity, created_at
                FROM ai_sessions
                WHERE company_id = ? AND user_id = ?
                ORDER BY last_activity DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->companyId, $this->userId]);
        $sessions = $stmt->fetchAll();
        
        $this->sendSuccess([
            'sessions' => $sessions,
        ]);
    }
    
    /**
     * Get conversation history
     */
    private function getConversationHistory(int $limit = 20): array
    {
        $maxHistory = $this->config['agent']['max_history'] ?? $limit;
        $limit = min((int)$limit, (int)$maxHistory);
        
        $sql = "SELECT role, content, tool_calls, tool_results, created_at
                FROM ai_conversations
                WHERE session_id = ? AND company_id = ?
                ORDER BY created_at DESC
                LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->sessionId, $this->companyId]);
        $messages = $stmt->fetchAll();
        
        // Reverse to get chronological order
        return array_reverse($messages);
    }
    
    /**
     * Save message to database
     */
    private function saveMessage(string $role, string $content, ?array $toolCalls = null): void
    {
        $sql = "INSERT INTO ai_conversations 
                (company_id, user_id, session_id, role, content, tool_calls, model, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->companyId,
            $this->userId,
            $this->sessionId,
            $role,
            $content,
            $toolCalls ? json_encode($toolCalls) : null,
            $this->aiProvider->getModel(),
        ]);
    }
    
    /**
     * Update session activity
     */
    private function updateSession(): void
    {
        $sql = "UPDATE ai_sessions 
                SET message_count = message_count + 1, last_activity = NOW()
                WHERE session_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->sessionId]);
    }
    
    /**
     * Get input data
     */
    private function getInput(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try form data
            return $_POST;
        }
        
        return $data ?? [];
    }
    
    /**
     * Send success response
     */
    private function sendSuccess(array $data): void
    {
        echo json_encode([
            'success' => true,
            'data' => $data,
        ]);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
        ]);
        exit;
    }
}

// Handle public health check (no auth required)
if (($_GET['action'] ?? '') === 'ping') {
    try {
        require_once __DIR__ . '/ai-provider.php';
        $settings = AIProvider::getSettings();
        $provider = $settings['provider'] ?? 'openai';
        
        $providerInfo = [
            'name' => $provider,
            'available' => false,
            'model' => '',
        ];
        
        if ($provider === 'openai') {
            $providerInfo['model'] = $settings['openai_model'] ?? 'gpt-4o-mini';
            $providerInfo['available'] = !empty($settings['openai_api_key']);
            $providerInfo['display_name'] = 'OpenAI';
        } else {
            $providerInfo['model'] = $settings['ollama_model'] ?? 'llama3.2:3b';
            $providerInfo['display_name'] = 'Ollama';
            // Check Ollama availability
            if ($settings['ollama_enabled'] ?? false) {
                $ollama = new OllamaClient(['base_url' => $settings['ollama_url'] ?? 'http://ollama:11434']);
                $health = $ollama->checkHealth();
                $providerInfo['available'] = $health['available'] ?? false;
            }
        }
        
        echo json_encode([
            'success' => true,
            'status' => 'ok',
            'provider' => $providerInfo,
            // Keep ollama for backward compatibility
            'ollama' => ['available' => $providerInfo['available']],
            'model' => $providerInfo['model'],
            'timestamp' => date('c'),
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
    exit;
}

// Handle session debug (for troubleshooting)
if (($_GET['action'] ?? '') === 'debug') {
    echo json_encode([
        'success' => true,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'com_id' => $_SESSION['com_id'] ?? null,
        'user_level' => $_SESSION['user_level'] ?? null,
        'logged_in' => !empty($_SESSION['user_id']),
    ]);
    exit;
}

// Run handler
try {
    $handler = new ChatHandler();
    $handler->handle();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
    ]);
}
