<?php
/**
 * AI-Powered CRUD Testing Tool
 * Uses AI (OpenAI/Ollama) to generate test scenarios, analyze failures, and interactive testing
 * 
 * @package iACC
 * @version 1.1
 * @date 2026-01-05
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");
require_once("ai/ai-provider.php");

// Check access
check_dev_tools_access();

$db = new DbConn($config);
$ai = new AIProvider();
$aiSettings = AIProvider::getSettings();

// Available tables for testing
$availableTables = [
    'brand' => ['id', 'brand_name', 'des', 'logo', 'ven_id'],
    'category' => ['id', 'cat_name', 'des'],
    'type' => ['id', 'type_name', 'des'],
    'company' => ['id', 'name_en', 'name_th', 'name_sh', 'contact', 'email', 'phone', 'fax', 'tax', 'customer', 'vender', 'logo', 'term'],
    'payment_method' => ['id', 'name', 'des', 'status'],
];

// Get table schema info
function getTableSchema($db, $table) {
    $schema = [];
    $result = mysqli_query($db->conn, "DESCRIBE `$table`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $schema[] = $row;
        }
    }
    return $schema;
}

// Execute CRUD operation
function executeCrud($db, $operation, $table, $data = [], $whereId = null) {
    $result = ['success' => false, 'message' => '', 'data' => null, 'sql' => ''];
    
    try {
        switch ($operation) {
            case 'create':
                $columns = array_keys($data);
                $placeholders = array_fill(0, count($data), '?');
                $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                $result['sql'] = $sql;
                
                $stmt = mysqli_prepare($db->conn, $sql);
                if ($stmt) {
                    $types = str_repeat('s', count($data));
                    $values = array_values($data);
                    mysqli_stmt_bind_param($stmt, $types, ...$values);
                    if (mysqli_stmt_execute($stmt)) {
                        $newId = mysqli_insert_id($db->conn);
                        $result['success'] = true;
                        $result['message'] = "Created record with ID: $newId";
                        $result['data'] = ['id' => $newId];
                    } else {
                        $result['message'] = "Insert failed: " . mysqli_error($db->conn);
                    }
                } else {
                    $result['message'] = "Prepare failed: " . mysqli_error($db->conn);
                }
                break;
                
            case 'read':
                $sql = "SELECT * FROM `$table`";
                if ($whereId) {
                    $sql .= " WHERE id = " . intval($whereId);
                }
                $sql .= " LIMIT 10";
                $result['sql'] = $sql;
                
                $queryResult = mysqli_query($db->conn, $sql);
                if ($queryResult) {
                    $rows = [];
                    while ($row = mysqli_fetch_assoc($queryResult)) {
                        $rows[] = $row;
                    }
                    $result['success'] = true;
                    $result['message'] = "Found " . count($rows) . " record(s)";
                    $result['data'] = $rows;
                } else {
                    $result['message'] = "Query failed: " . mysqli_error($db->conn);
                }
                break;
                
            case 'update':
                if (!$whereId) {
                    $result['message'] = "Update requires an ID";
                    break;
                }
                $sets = [];
                foreach ($data as $col => $val) {
                    $sets[] = "`$col` = ?";
                }
                $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ?";
                $result['sql'] = $sql;
                
                $stmt = mysqli_prepare($db->conn, $sql);
                if ($stmt) {
                    $types = str_repeat('s', count($data)) . 'i';
                    $values = array_values($data);
                    $values[] = $whereId;
                    mysqli_stmt_bind_param($stmt, $types, ...$values);
                    if (mysqli_stmt_execute($stmt)) {
                        $affected = mysqli_stmt_affected_rows($stmt);
                        $result['success'] = true;
                        $result['message'] = "Updated $affected record(s)";
                        $result['data'] = ['affected_rows' => $affected];
                    } else {
                        $result['message'] = "Update failed: " . mysqli_error($db->conn);
                    }
                } else {
                    $result['message'] = "Prepare failed: " . mysqli_error($db->conn);
                }
                break;
                
            case 'delete':
                if (!$whereId) {
                    $result['message'] = "Delete requires an ID";
                    break;
                }
                $sql = "DELETE FROM `$table` WHERE id = ?";
                $result['sql'] = $sql;
                
                $stmt = mysqli_prepare($db->conn, $sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $whereId);
                    if (mysqli_stmt_execute($stmt)) {
                        $affected = mysqli_stmt_affected_rows($stmt);
                        $result['success'] = true;
                        $result['message'] = "Deleted $affected record(s)";
                        $result['data'] = ['affected_rows' => $affected];
                    } else {
                        $result['message'] = "Delete failed: " . mysqli_error($db->conn);
                    }
                } else {
                    $result['message'] = "Prepare failed: " . mysqli_error($db->conn);
                }
                break;
        }
    } catch (Exception $e) {
        $result['message'] = "Exception: " . $e->getMessage();
    }
    
    return $result;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    switch ($action) {
        case 'check_ollama':
            // Check if AI provider is available
            $result = $ai->checkHealth();
            if ($result['available']) {
                $response['success'] = true;
                $response['message'] = ucfirst($result['provider']) . ' is connected';
                $response['data'] = $result['models'] ?? [];
                $response['provider'] = $result['provider'];
            } else {
                $response['message'] = 'Cannot connect to ' . ucfirst($ai->getProvider()) . ': ' . ($result['error'] ?? 'Unknown error');
                $response['provider'] = $ai->getProvider();
            }
            break;
            
        case 'generate_scenarios':
            // Generate test scenarios using AI
            $table = $_POST['table'] ?? 'brand';
            $count = intval($_POST['count'] ?? 5);
            $testType = $_POST['test_type'] ?? 'edge_cases';
            
            $schema = getTableSchema($db, $table);
            $schemaText = json_encode($schema, JSON_PRETTY_PRINT);
            
            $prompt = "You are a database testing expert. Generate $count test scenarios for CRUD operations on the '$table' table.

Table Schema:
$schemaText

Test Type: $testType

Generate realistic test data scenarios. For each scenario, provide:
1. Operation (create/read/update/delete)
2. Test data as JSON
3. Expected outcome
4. What edge case or scenario it tests

Return as JSON array:
[
  {
    \"name\": \"Test name\",
    \"operation\": \"create\",
    \"data\": {\"column\": \"value\"},
    \"expected\": \"Description of expected outcome\",
    \"tests\": \"What this scenario tests\"
  }
]

Focus on: " . (function($type) {
    switch ($type) {
        case 'edge_cases': return 'empty strings, special characters, unicode, very long strings, SQL injection attempts (safely), boundary values';
        case 'normal': return 'typical valid data that would be used in production';
        case 'stress': return 'large data volumes, concurrent-like operations, maximum field lengths';
        case 'security': return 'SQL injection patterns, XSS attempts, malformed data, authentication bypass attempts';
        default: return 'general testing scenarios';
    }
})($testType);

            $result = $ai->generate($prompt, "You are a helpful database testing assistant. Always return valid JSON.");
            
            if ($result['success']) {
                $responseText = $result['response'] ?? '';
                // Try to extract JSON from the response
                if (preg_match('/\[.*\]/s', $responseText, $matches)) {
                    $scenarios = json_decode($matches[0], true);
                    if ($scenarios) {
                        $response['success'] = true;
                        $response['data'] = $scenarios;
                        $response['message'] = "Generated $count test scenarios";
                    } else {
                        $response['message'] = 'Could not parse AI response as JSON';
                        $response['data'] = ['raw' => $responseText];
                    }
                } else {
                    $response['message'] = 'No JSON found in AI response';
                    $response['data'] = ['raw' => $responseText];
                }
            } else {
                $response['message'] = 'AI error: ' . ($result['error'] ?? 'Unknown');
            }
            break;
            
        case 'analyze_failure':
            // Analyze a test failure
            $errorMessage = $_POST['error'] ?? '';
            $sql = $_POST['sql'] ?? '';
            $table = $_POST['table'] ?? '';
            $operation = $_POST['operation'] ?? '';
            
            $prompt = "Analyze this database test failure and provide solutions:

Table: $table
Operation: $operation
SQL: $sql
Error: $errorMessage

Please provide:
1. Root cause analysis
2. Possible solutions (ranked by likelihood)
3. How to prevent this in the future
4. Any related issues to check

Be specific and actionable.";

            $result = $ai->generate($prompt, "You are a database debugging expert. Provide clear, actionable solutions.");
            
            if ($result['success']) {
                $response['success'] = true;
                $response['data'] = ['analysis' => $result['response'] ?? ''];
                $response['message'] = 'Analysis complete';
            } else {
                $response['message'] = 'AI error: ' . ($result['error'] ?? 'Unknown');
            }
            break;
            
        case 'execute_test':
            // Execute a CRUD test
            $table = $_POST['table'] ?? '';
            $operation = $_POST['operation'] ?? '';
            $data = json_decode($_POST['data'] ?? '{}', true) ?: [];
            $whereId = $_POST['where_id'] ?? null;
            
            if (!isset($availableTables[$table])) {
                $response['message'] = "Table '$table' is not available for testing";
                break;
            }
            
            $result = executeCrud($db, $operation, $table, $data, $whereId);
            $response = $result;
            break;
            
        case 'natural_language':
            // Process natural language testing request
            $query = $_POST['query'] ?? '';
            
            $tablesInfo = json_encode($availableTables, JSON_PRETTY_PRINT);
            
            $prompt = "You are a CRUD testing assistant. Parse this natural language request and convert it to a test operation.

Available tables and columns:
$tablesInfo

User request: \"$query\"

Return a JSON object with:
{
  \"understood\": true/false,
  \"table\": \"table_name\",
  \"operation\": \"create|read|update|delete\",
  \"data\": {\"column\": \"value\"},
  \"where_id\": null or id number for update/delete,
  \"explanation\": \"What this test will do\"
}

If you cannot understand the request, set understood to false and explain why in the explanation field.";

            $result = $ai->generate($prompt, "You are a helpful testing assistant. Always return valid JSON.");
            
            if ($result['success']) {
                $responseText = $result['response'] ?? '';
                if (preg_match('/\{.*\}/s', $responseText, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if ($parsed) {
                        $response['success'] = true;
                        $response['data'] = $parsed;
                        $response['message'] = 'Request parsed successfully';
                    } else {
                        $response['message'] = 'Could not parse AI response';
                        $response['data'] = ['raw' => $responseText];
                    }
                } else {
                    $response['message'] = 'No JSON found in response';
                    $response['data'] = ['raw' => $responseText];
                }
            } else {
                $response['message'] = 'AI error: ' . ($result['error'] ?? 'Unknown');
            }
            break;
            
        case 'run_full_crud_test':
            // Run a complete CRUD cycle on a table
            $table = $_POST['table'] ?? 'brand';
            $testData = json_decode($_POST['test_data'] ?? '{}', true) ?: [];
            
            if (!isset($availableTables[$table])) {
                $response['message'] = "Table '$table' is not available for testing";
                break;
            }
            
            $results = [];
            $allPassed = true;
            
            // CREATE
            $createResult = executeCrud($db, 'create', $table, $testData);
            $results['create'] = $createResult;
            if (!$createResult['success']) {
                $allPassed = false;
            }
            
            if ($createResult['success'] && isset($createResult['data']['id'])) {
                $newId = $createResult['data']['id'];
                
                // READ
                $readResult = executeCrud($db, 'read', $table, [], $newId);
                $results['read'] = $readResult;
                if (!$readResult['success']) $allPassed = false;
                
                // UPDATE
                $updateData = [];
                foreach ($testData as $key => $val) {
                    $updateData[$key] = $val . '_updated';
                }
                $updateResult = executeCrud($db, 'update', $table, $updateData, $newId);
                $results['update'] = $updateResult;
                if (!$updateResult['success']) $allPassed = false;
                
                // DELETE
                $deleteResult = executeCrud($db, 'delete', $table, [], $newId);
                $results['delete'] = $deleteResult;
                if (!$deleteResult['success']) $allPassed = false;
            }
            
            $response['success'] = $allPassed;
            $response['data'] = $results;
            $response['message'] = $allPassed ? 'All CRUD operations passed!' : 'Some operations failed';
            break;
            
        case 'get_table_schema':
            $table = $_POST['table'] ?? '';
            if (!isset($availableTables[$table])) {
                $response['message'] = "Table '$table' is not available";
                break;
            }
            
            $schema = getTableSchema($db, $table);
            $response['success'] = true;
            $response['data'] = $schema;
            $response['message'] = 'Schema retrieved';
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI CRUD Testing - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <style>
        .ai-chat-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .chat-input-area {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .chat-input-area input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .chat-input-area input:focus {
            outline: none;
            border-color: #667eea;
        }
        .chat-messages {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .chat-message {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            animation: fadeIn 0.3s ease;
        }
        .chat-message.user {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            margin-left: 20%;
        }
        .chat-message.ai {
            background: #e9ecef;
            color: #333;
            margin-right: 20%;
        }
        .chat-message.system {
            background: #fff3cd;
            color: #856404;
            text-align: center;
            font-size: 13px;
        }
        .scenario-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .scenario-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .scenario-card .operation {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .operation.create { background: #d4edda; color: #155724; }
        .operation.read { background: #cce5ff; color: #004085; }
        .operation.update { background: #fff3cd; color: #856404; }
        .operation.delete { background: #f8d7da; color: #721c24; }
        
        .test-result {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .test-result.success { background: #d4edda; border-left: 4px solid #28a745; }
        .test-result.fail { background: #f8d7da; border-left: 4px solid #dc3545; }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .quick-action-btn {
            padding: 8px 16px;
            border: 1px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .quick-action-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .ollama-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ffc107;
        }
        .status-dot.connected { background: #28a745; }
        .status-dot.error { background: #dc3545; }
        
        .tab-container {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        pre.code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('AI-Powered CRUD Testing', 'Use AI (' . ucfirst($ai->getProvider()) . ') to generate tests, analyze failures, and test with natural language', 'fa-robot', '#667eea'); ?>
        
        <!-- AI Status -->
        <div class="ollama-status" id="ollamaStatus">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">Checking AI connection...</span>
            <a href="ai-settings.php" style="margin-left: auto; font-size: 12px;"><i class="fa fa-cog"></i> Settings</a>
        </div>
        
        <!-- Tab Navigation -->
        <div class="tab-container">
            <button class="tab-btn active" data-tab="natural">💬 Natural Language</button>
            <button class="tab-btn" data-tab="generate">🎲 Generate Scenarios</button>
            <button class="tab-btn" data-tab="analyze">🔍 Analyze Failures</button>
            <button class="tab-btn" data-tab="manual">🔧 Manual Test</button>
        </div>
        
        <!-- Tab 1: Natural Language Testing -->
        <div class="tab-content active" id="tab-natural">
            <div class="card">
                <h3><i class="fa fa-comments"></i> Natural Language Testing</h3>
                <p style="color: #666; margin-bottom: 15px;">Describe what you want to test in plain English. AI will interpret and execute the test.</p>
                
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="setQuery('Test creating a brand with special characters like é and 中文')">Special Characters</button>
                    <button class="quick-action-btn" onclick="setQuery('Create and delete a test company')">CRUD Cycle</button>
                    <button class="quick-action-btn" onclick="setQuery('Check if category table can handle empty string')">Empty String</button>
                    <button class="quick-action-btn" onclick="setQuery('Test SQL injection on brand name: \\' OR 1=1 --')">SQL Injection</button>
                    <button class="quick-action-btn" onclick="setQuery('Read all records from type table')">Read All</button>
                </div>
                
                <div class="ai-chat-container">
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message system">
                            <i class="fa fa-info-circle"></i> Ask me to test anything! Example: "Test if brand table handles unicode" or "Run full CRUD on company"
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <input type="text" id="naturalQuery" placeholder="Describe what you want to test..." onkeypress="if(event.key==='Enter')processNaturalLanguage()">
                        <button class="btn btn-primary" onclick="processNaturalLanguage()">
                            <i class="fa fa-paper-plane"></i> Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Generate Scenarios -->
        <div class="tab-content" id="tab-generate">
            <div class="card">
                <h3><i class="fa fa-magic"></i> AI Test Scenario Generator</h3>
                <p style="color: #666; margin-bottom: 15px;">Let AI generate test scenarios for comprehensive CRUD testing.</p>
                
                <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label>Table:</label>
                        <select id="genTable" class="form-control">
                            <?php foreach ($availableTables as $table => $cols): ?>
                            <option value="<?php echo $table; ?>"><?php echo $table; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Test Type:</label>
                        <select id="genType" class="form-control">
                            <option value="edge_cases">Edge Cases</option>
                            <option value="normal">Normal Data</option>
                            <option value="stress">Stress Test</option>
                            <option value="security">Security Test</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Count:</label>
                        <select id="genCount" class="form-control">
                            <option value="3">3 scenarios</option>
                            <option value="5" selected>5 scenarios</option>
                            <option value="10">10 scenarios</option>
                        </select>
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="generateScenarios()" id="genBtn">
                    <i class="fa fa-cogs"></i> Generate Test Scenarios
                </button>
                
                <div id="scenariosContainer" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <!-- Tab 3: Analyze Failures -->
        <div class="tab-content" id="tab-analyze">
            <div class="card">
                <h3><i class="fa fa-bug"></i> Failure Analysis</h3>
                <p style="color: #666; margin-bottom: 15px;">Paste a test failure and let AI diagnose the issue.</p>
                
                <div style="margin-bottom: 15px;">
                    <label>Table (optional):</label>
                    <input type="text" id="failTable" class="form-control" placeholder="e.g., brand">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Operation:</label>
                    <select id="failOperation" class="form-control">
                        <option value="create">CREATE</option>
                        <option value="read">READ</option>
                        <option value="update">UPDATE</option>
                        <option value="delete">DELETE</option>
                    </select>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>SQL Query (optional):</label>
                    <input type="text" id="failSql" class="form-control" placeholder="e.g., INSERT INTO brand...">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Error Message:</label>
                    <textarea id="failError" class="form-control" rows="3" placeholder="Paste the error message here..."></textarea>
                </div>
                
                <button class="btn btn-primary" onclick="analyzeFailure()" id="analyzeBtn">
                    <i class="fa fa-search"></i> Analyze Failure
                </button>
                
                <div id="analysisResult" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <!-- Tab 4: Manual Test -->
        <div class="tab-content" id="tab-manual">
            <div class="card">
                <h3><i class="fa fa-wrench"></i> Manual CRUD Test</h3>
                <p style="color: #666; margin-bottom: 15px;">Manually execute CRUD operations with custom data.</p>
                
                <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label>Table:</label>
                        <select id="manualTable" class="form-control" onchange="loadTableSchema()">
                            <?php foreach ($availableTables as $table => $cols): ?>
                            <option value="<?php echo $table; ?>"><?php echo $table; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Operation:</label>
                        <select id="manualOperation" class="form-control" onchange="updateManualForm()">
                            <option value="create">CREATE</option>
                            <option value="read">READ</option>
                            <option value="update">UPDATE</option>
                            <option value="delete">DELETE</option>
                            <option value="full_crud">Full CRUD Cycle</option>
                        </select>
                    </div>
                </div>
                
                <div id="schemaInfo" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;"></div>
                
                <div style="margin-bottom: 15px;" id="dataInputArea">
                    <label>Test Data (JSON):</label>
                    <textarea id="manualData" class="form-control" rows="4" placeholder='{"column_name": "value"}'></textarea>
                </div>
                
                <div style="margin-bottom: 15px;" id="whereIdArea">
                    <label>Record ID (for update/delete):</label>
                    <input type="number" id="manualWhereId" class="form-control" placeholder="Enter record ID">
                </div>
                
                <button class="btn btn-primary" onclick="executeManualTest()">
                    <i class="fa fa-play"></i> Execute Test
                </button>
                
                <div id="manualResult" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>
    
    <script>
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
    
    // Check AI status on load
    async function checkAIStatus() {
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=check_ollama'
            });
            const data = await response.json();
            
            const dot = document.getElementById('statusDot');
            const text = document.getElementById('statusText');
            
            if (data.success) {
                dot.classList.add('connected');
                const provider = data.provider ? data.provider.charAt(0).toUpperCase() + data.provider.slice(1) : 'AI';
                text.textContent = provider + ' connected - Models: ' + (data.data.map(m => m.name).join(', ') || 'Ready');
            } else {
                dot.classList.add('error');
                text.textContent = 'AI not available: ' + data.message;
            }
        } catch (e) {
            document.getElementById('statusDot').classList.add('error');
            document.getElementById('statusText').textContent = 'Connection error: ' + e.message;
        }
    }
    checkAIStatus();
    
    // Natural Language Processing
    function setQuery(text) {
        document.getElementById('naturalQuery').value = text;
    }
    
    function addChatMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const msg = document.createElement('div');
        msg.className = 'chat-message ' + type;
        msg.innerHTML = text;
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    async function processNaturalLanguage() {
        const query = document.getElementById('naturalQuery').value.trim();
        if (!query) return;
        
        addChatMessage(query, 'user');
        document.getElementById('naturalQuery').value = '';
        addChatMessage('<div class="loading-spinner"></div> Thinking...', 'ai');
        
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=natural_language&query=' + encodeURIComponent(query)
            });
            const data = await response.json();
            
            // Remove loading message
            const messages = document.querySelectorAll('.chat-message.ai');
            messages[messages.length - 1].remove();
            
            if (data.success && data.data.understood) {
                const parsed = data.data;
                let response = `<strong>Understood!</strong><br>
                    <span class="operation ${parsed.operation}">${parsed.operation.toUpperCase()}</span> on <strong>${parsed.table}</strong><br>
                    <em>${parsed.explanation}</em><br><br>`;
                
                if (parsed.data && Object.keys(parsed.data).length > 0) {
                    response += `<strong>Data:</strong><pre class="code-block">${JSON.stringify(parsed.data, null, 2)}</pre>`;
                }
                
                response += `<button class="btn btn-sm btn-success" onclick="executeFromNL('${parsed.table}', '${parsed.operation}', ${JSON.stringify(JSON.stringify(parsed.data || {}))}, ${parsed.where_id || 'null'})">
                    <i class="fa fa-play"></i> Execute This Test
                </button>`;
                
                addChatMessage(response, 'ai');
            } else if (data.data && data.data.raw) {
                addChatMessage(data.data.raw, 'ai');
            } else {
                addChatMessage('Sorry, I couldn\'t understand that. Try: "Test creating a brand with name Test123"', 'ai');
            }
        } catch (e) {
            const messages = document.querySelectorAll('.chat-message.ai');
            if (messages.length) messages[messages.length - 1].remove();
            addChatMessage('Error: ' + e.message, 'system');
        }
    }
    
    async function executeFromNL(table, operation, data, whereId) {
        addChatMessage('<div class="loading-spinner"></div> Executing test...', 'ai');
        
        try {
            let body = `action=execute_test&table=${table}&operation=${operation}&data=${encodeURIComponent(data)}`;
            if (whereId) body += `&where_id=${whereId}`;
            
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body
            });
            const result = await response.json();
            
            const messages = document.querySelectorAll('.chat-message.ai');
            messages[messages.length - 1].remove();
            
            let html = `<div class="test-result ${result.success ? 'success' : 'fail'}">
                <strong>${result.success ? '✅ PASSED' : '❌ FAILED'}</strong><br>
                ${result.message}<br>
                <small>SQL: ${result.sql || 'N/A'}</small>
            </div>`;
            
            if (result.data) {
                html += `<pre class="code-block">${JSON.stringify(result.data, null, 2)}</pre>`;
            }
            
            addChatMessage(html, 'ai');
        } catch (e) {
            addChatMessage('Error executing: ' + e.message, 'system');
        }
    }
    
    // Generate Scenarios
    async function generateScenarios() {
        const btn = document.getElementById('genBtn');
        const container = document.getElementById('scenariosContainer');
        
        btn.disabled = true;
        btn.innerHTML = '<div class="loading-spinner"></div> Generating...';
        container.innerHTML = '';
        
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=generate_scenarios&table=${document.getElementById('genTable').value}&test_type=${document.getElementById('genType').value}&count=${document.getElementById('genCount').value}`
            });
            const data = await response.json();
            
            if (data.success && Array.isArray(data.data)) {
                let html = '';
                data.data.forEach((scenario, i) => {
                    html += `<div class="scenario-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong>${scenario.name || 'Scenario ' + (i+1)}</strong>
                            <span class="operation ${scenario.operation || 'create'}">${(scenario.operation || 'create').toUpperCase()}</span>
                        </div>
                        <p style="color: #666; font-size: 13px;">${scenario.tests || scenario.expected || ''}</p>
                        <pre class="code-block">${JSON.stringify(scenario.data || {}, null, 2)}</pre>
                        <button class="btn btn-sm btn-primary" onclick="runGeneratedScenario('${document.getElementById('genTable').value}', '${scenario.operation || 'create'}', ${JSON.stringify(JSON.stringify(scenario.data || {}))})">
                            <i class="fa fa-play"></i> Run Test
                        </button>
                    </div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div class="test-result fail">Could not generate scenarios: ${data.message}<br><pre>${data.data?.raw || ''}</pre></div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="test-result fail">Error: ${e.message}</div>`;
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-cogs"></i> Generate Test Scenarios';
    }
    
    async function runGeneratedScenario(table, operation, dataJson) {
        const container = document.getElementById('scenariosContainer');
        
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=execute_test&table=${table}&operation=${operation}&data=${encodeURIComponent(dataJson)}`
            });
            const result = await response.json();
            
            alert(result.success ? '✅ Test Passed: ' + result.message : '❌ Test Failed: ' + result.message);
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }
    
    // Analyze Failure
    async function analyzeFailure() {
        const btn = document.getElementById('analyzeBtn');
        const container = document.getElementById('analysisResult');
        
        const error = document.getElementById('failError').value.trim();
        if (!error) {
            alert('Please enter an error message to analyze');
            return;
        }
        
        btn.disabled = true;
        btn.innerHTML = '<div class="loading-spinner"></div> Analyzing...';
        
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=analyze_failure&error=${encodeURIComponent(error)}&table=${document.getElementById('failTable').value}&operation=${document.getElementById('failOperation').value}&sql=${encodeURIComponent(document.getElementById('failSql').value)}`
            });
            const data = await response.json();
            
            if (data.success) {
                container.innerHTML = `<div class="card" style="background: #f8f9fa;">
                    <h4><i class="fa fa-lightbulb-o"></i> AI Analysis</h4>
                    <div style="white-space: pre-wrap;">${data.data.analysis}</div>
                </div>`;
            } else {
                container.innerHTML = `<div class="test-result fail">${data.message}</div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="test-result fail">Error: ${e.message}</div>`;
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-search"></i> Analyze Failure';
    }
    
    // Manual Test
    async function loadTableSchema() {
        const table = document.getElementById('manualTable').value;
        const container = document.getElementById('schemaInfo');
        
        try {
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_table_schema&table=' + table
            });
            const data = await response.json();
            
            if (data.success) {
                let html = '<strong>Table Schema:</strong><br><small>';
                data.data.forEach(col => {
                    html += `<code>${col.Field}</code> (${col.Type}) ${col.Null === 'NO' ? 'NOT NULL' : ''} ${col.Key === 'PRI' ? '🔑' : ''}<br>`;
                });
                html += '</small>';
                container.innerHTML = html;
                
                // Generate sample data
                const sample = {};
                data.data.forEach(col => {
                    if (col.Key !== 'PRI' && col.Extra !== 'auto_increment') {
                        sample[col.Field] = 'test_' + col.Field;
                    }
                });
                document.getElementById('manualData').value = JSON.stringify(sample, null, 2);
            }
        } catch (e) {
            container.innerHTML = 'Error loading schema';
        }
    }
    
    function updateManualForm() {
        const op = document.getElementById('manualOperation').value;
        document.getElementById('whereIdArea').style.display = (op === 'update' || op === 'delete') ? 'block' : 'none';
        document.getElementById('dataInputArea').style.display = (op === 'read' || op === 'delete') ? 'none' : 'block';
    }
    
    async function executeManualTest() {
        const table = document.getElementById('manualTable').value;
        const operation = document.getElementById('manualOperation').value;
        const container = document.getElementById('manualResult');
        
        container.innerHTML = '<div class="loading-spinner"></div> Executing...';
        
        try {
            let body = '';
            if (operation === 'full_crud') {
                body = `action=run_full_crud_test&table=${table}&test_data=${encodeURIComponent(document.getElementById('manualData').value)}`;
            } else {
                body = `action=execute_test&table=${table}&operation=${operation}&data=${encodeURIComponent(document.getElementById('manualData').value)}`;
                const whereId = document.getElementById('manualWhereId').value;
                if (whereId) body += `&where_id=${whereId}`;
            }
            
            const response = await fetch('test-crud-ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body
            });
            const result = await response.json();
            
            if (operation === 'full_crud') {
                let html = `<h4>${result.success ? '✅ All Tests Passed!' : '❌ Some Tests Failed'}</h4>`;
                for (const [op, res] of Object.entries(result.data || {})) {
                    html += `<div class="test-result ${res.success ? 'success' : 'fail'}">
                        <strong>${op.toUpperCase()}:</strong> ${res.message}
                        <small style="display: block; color: #666;">SQL: ${res.sql || 'N/A'}</small>
                    </div>`;
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="test-result ${result.success ? 'success' : 'fail'}">
                        <strong>${result.success ? '✅ PASSED' : '❌ FAILED'}</strong><br>
                        ${result.message}
                    </div>
                    <pre class="code-block">SQL: ${result.sql || 'N/A'}
Result: ${JSON.stringify(result.data, null, 2)}</pre>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="test-result fail">Error: ${e.message}</div>`;
        }
    }
    
    // Initialize
    loadTableSchema();
    updateManualForm();
    </script>
</body>
</html>
