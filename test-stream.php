<?php
/**
 * Test Streaming SSE Endpoint
 * 
 * @date 2026-01-06
 */

// Set up a mock session
$_SESSION['user_id'] = 1;
$_SESSION['com_id'] = 95;
$_SESSION['user_level'] = 1;
$_SESSION['company_name'] = 'Test Company';
$_SESSION['user_name'] = 'Test User';

echo "=== Testing AI Streaming SSE ===\n\n";

// Load required files
chdir(__DIR__);
require_once __DIR__ . '/ai/config.php';
require_once __DIR__ . '/ai/ai-provider.php';
require_once __DIR__ . '/ai/ai-language.php';
require_once __DIR__ . '/ai/agent-tools.php';
require_once __DIR__ . '/ai/agent-executor.php';

// Test 1: Language detection
echo "1. Testing Language Detection:\n";
$testMessages = [
    'Hello, how are you?' => 'en',
    'สวัสดีครับ ขอดูใบแจ้งหนี้' => 'th',
    'แสดงข้อมูลลูกค้า' => 'th',
    'Show me today\'s invoices' => 'en',
];

foreach ($testMessages as $msg => $expected) {
    $detected = AILanguage::detectLanguage($msg);
    $status = $detected === $expected ? '✓' : '✗';
    echo "   $status \"$msg\" => $detected (expected: $expected)\n";
}

// Test 2: Database connection
echo "\n2. Testing Database Connection:\n";
try {
    $db = new PDO(
        'mysql:host=mysql;dbname=iacc;charset=utf8mb4',
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   ✓ Database connected\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: AI Provider
echo "\n3. Testing AI Provider:\n";
try {
    $aiProvider = new AIProvider();
    $status = $aiProvider->checkHealth();
    if ($status['available']) {
        echo "   ✓ AI Provider: " . ($status['display_name'] ?? $status['provider']) . "\n";
        echo "   ✓ Model: " . ($status['model'] ?? 'unknown') . "\n";
    } else {
        echo "   ✗ AI Provider not available\n";
    }
} catch (Exception $e) {
    echo "   ✗ AI Provider error: " . $e->getMessage() . "\n";
}

// Test 4: Simple chat request
echo "\n4. Testing Simple Chat:\n";
try {
    $messages = [
        ['role' => 'system', 'content' => 'You are a helpful assistant. Reply in 2 sentences max.'],
        ['role' => 'user', 'content' => 'Say hello in Thai'],
    ];
    
    $result = $aiProvider->chat($messages, []);
    
    if ($result['success']) {
        echo "   ✓ Chat successful\n";
        echo "   Response: " . substr($result['response'], 0, 100) . "...\n";
    } else {
        echo "   ✗ Chat failed: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Chat error: " . $e->getMessage() . "\n";
}

// Test 5: SSE format
echo "\n5. Testing SSE Event Format:\n";
$events = [
    ['thinking', ['status' => 'processing']],
    ['language', ['detected' => 'th']],
    ['tool_call', ['tool' => 'get_invoices', 'params' => ['status' => 'unpaid']]],
    ['chunk', 'Hello'],
    ['done', ['session_id' => 'test-123', 'message' => 'Complete']],
];

foreach ($events as $event) {
    $name = $event[0];
    $data = $event[1];
    if (is_array($data)) {
        $output = "event: {$name}\ndata: " . json_encode($data) . "\n\n";
    } else {
        $output = "event: {$name}\ndata: {$data}\n\n";
    }
    echo "   ✓ {$name}: " . trim(str_replace("\n", ' | ', $output)) . "\n";
}

echo "\n=== All Tests Complete ===\n";
