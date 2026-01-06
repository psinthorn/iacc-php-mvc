<?php
/**
 * AI Chat Streaming Handler
 * 
 * Server-Sent Events endpoint for real-time chat streaming
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-06
 */

// Change to root directory for proper includes
chdir(__DIR__ . '/..');

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Allow CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

// Flush output buffer if any
if (ob_get_level()) {
    ob_end_flush();
}

// Include required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai-provider.php';
require_once __DIR__ . '/ai-language.php';
require_once __DIR__ . '/agent-tools.php';
require_once __DIR__ . '/agent-executor.php';

/**
 * Send SSE event
 */
function sendEvent(string $event, $data): void
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

/**
 * Send SSE message
 */
function sendMessage(string $text): void
{
    sendEvent('message', ['text' => $text]);
}

/**
 * Send SSE error
 */
function sendError(string $message): void
{
    sendEvent('error', ['message' => $message]);
    exit;
}

/**
 * Send SSE done
 */
function sendDone(array $data = []): void
{
    sendEvent('done', $data);
    exit;
}

// Check authentication
if (empty($_SESSION['user_id'])) {
    sendError('Authentication required');
}

$userId = (int) $_SESSION['user_id'];
$companyId = (int) ($_SESSION['com_id'] ?? 95);
$userLevel = (int) ($_SESSION['user_level'] ?? 0);

// Get message from query
$message = trim($_GET['message'] ?? '');
$sessionId = $_GET['session_id'] ?? '';

if (empty($message)) {
    sendError('Message is required');
}

// Send thinking indicator
sendEvent('thinking', ['status' => 'processing']);

try {
    // Initialize database
    $db = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root',
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Detect language
    $language = AILanguage::detectLanguage($message);
    sendEvent('language', ['detected' => $language]);
    
    // Build system prompt
    $systemPrompt = AILanguage::getSystemPrompt($language);
    
    // Replace placeholders
    $companyName = $_SESSION['company_name'] ?? 'Company';
    $userName = $_SESSION['user_name'] ?? 'User';
    
    $replacements = [
        '{company_id}' => $companyId,
        '{company_name}' => $companyName,
        '{user_name}' => $userName,
        '{user_level}' => $userLevel,
        '{current_date}' => date('Y-m-d'),
        '{current_time}' => date('H:i:s'),
    ];
    
    $systemPrompt = str_replace(array_keys($replacements), array_values($replacements), $systemPrompt);
    
    // Add schema context
    require_once __DIR__ . '/schema-discovery.php';
    $schemaContext = SchemaDiscovery::loadCompactSchema();
    if ($schemaContext) {
        $systemPrompt .= "\n\nDATABASE SCHEMA REFERENCE:\n" . $schemaContext;
    }
    
    // Build messages
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message],
    ];
    
    // Get tools
    $tools = getAllTools();
    
    // Initialize AI provider
    $aiProvider = new AIProvider();
    
    // Send status update
    sendEvent('status', ['step' => 'calling_ai']);
    
    // Get response from AI
    $result = $aiProvider->chat($messages, $tools);
    
    if (!$result['success']) {
        sendError('AI service error: ' . ($result['error'] ?? 'Unknown'));
    }
    
    // Process tool calls if any
    $toolResults = [];
    $toolCalls = $result['tool_calls'] ?? [];
    
    if (!empty($toolCalls)) {
        sendEvent('tools', ['count' => count($toolCalls)]);
        
        $config = require __DIR__ . '/config.php';
        $executor = new AgentExecutor(
            $db,
            $companyId,
            $userId,
            $sessionId ?: 'stream-' . uniqid(),
            $config['agent'] ?? []
        );
        
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'] ?? '';
            $toolParams = $toolCall['function']['arguments'] ?? [];
            
            if (is_string($toolParams)) {
                $toolParams = json_decode($toolParams, true) ?: [];
            }
            
            sendEvent('tool_call', [
                'tool' => $toolName,
                'params' => $toolParams,
            ]);
            
            // Execute tool
            $toolResult = $executor->execute($toolName, $toolParams);
            $toolResults[] = [
                'name' => $toolName,
                'result' => $toolResult,
            ];
            
            sendEvent('tool_result', [
                'name' => $toolName,
                'success' => $toolResult['success'] ?? false,
            ]);
        }
    }
    
    // Get final response text
    $responseText = $result['response'] ?? '';
    
    // Stream response character by character (simulated)
    // In production, this would use OpenAI's streaming API
    if (strlen($responseText) > 0) {
        sendEvent('status', ['step' => 'streaming_response']);
        
        // Stream in chunks for smoother effect
        $chunkSize = 10; // Characters per chunk
        $chunks = str_split($responseText, $chunkSize);
        
        foreach ($chunks as $chunk) {
                // Send chunk as plain text (not JSON)
                echo "event: chunk\n";
                echo "data: " . $chunk . "\n\n";
                flush();
        'tool_results' => $toolResults,
    ]);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage());
}
