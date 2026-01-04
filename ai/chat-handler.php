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

// Allow CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include required files
require_once __DIR__ . '/../inc/class.dbconn.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ollama-client.php';
require_once __DIR__ . '/agent-tools.php';
require_once __DIR__ . '/agent-executor.php';

/**
 * Main Chat Handler Class
 */
class ChatHandler
{
    private $db;
    private array $config;
    private OllamaClient $ollama;
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
        
        // Initialize Ollama client
        $this->ollama = new OllamaClient($this->config['ollama']);
        
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
        
        // Get available tools
        $tools = getAgentTools();
        
        // Send to Ollama
        $result = $this->ollama->chat($messages, $tools);
        
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
        
        return str_replace(array_keys($replacements), array_values($replacements), $prompt);
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
        $message = $this->ollama->getResponseText($result);
        $toolCalls = $this->ollama->getToolCalls($result);
        
        $toolResults = [];
        $requiresConfirmation = false;
        $confirmationId = null;
        
        // Execute tool calls
        foreach ($toolCalls as $call) {
            $toolName = $call['function']['name'] ?? '';
            $params = [];
            
            // Parse arguments
            if (isset($call['function']['arguments'])) {
                if (is_string($call['function']['arguments'])) {
                    $params = json_decode($call['function']['arguments'], true) ?? [];
                } else {
                    $params = $call['function']['arguments'];
                }
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
            
            // If tool was executed, append result to message
            if (!empty($toolResult['success']) && isset($toolResult['result'])) {
                $message .= "\n\n" . $this->formatToolResult($toolName, $toolResult['result']);
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
     * Format tool result for display
     */
    private function formatToolResult(string $toolName, array $result): string
    {
        // The AI will usually format its own response, but we can help
        return '';
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
        $ollamaHealth = $this->ollama->checkHealth();
        
        $this->sendSuccess([
            'status' => 'ok',
            'ollama' => $ollamaHealth,
            'model' => $this->ollama->getModel(),
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
        $limit = min($limit, $maxHistory);
        
        $sql = "SELECT role, content, tool_calls, tool_results, created_at
                FROM ai_conversations
                WHERE session_id = ? AND company_id = ?
                ORDER BY created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->sessionId, $this->companyId, $limit]);
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
            $this->ollama->getModel(),
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
        $ollama = new OllamaClient();
        $health = $ollama->checkHealth();
        echo json_encode([
            'success' => true,
            'status' => 'ok',
            'ollama' => $health,
            'model' => $ollama->getModel(),
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
