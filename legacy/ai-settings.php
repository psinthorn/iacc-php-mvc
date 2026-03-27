<?php
/**
 * AI Settings Configuration Page
 * Configure AI provider (Ollama/OpenAI) and settings
 * 
 * @package iACC
 * @version 1.0
 * @date 2026-01-05
 */

// Handle AJAX requests BEFORE any session/output (when called directly)
if (isset($_GET['ajax']) || (isset($_POST['action']) && in_array($_POST['action'], ['test_provider', 'quick_test', 'pull_model', 'list_models']))) {
    session_start();
    
    // Suppress any errors from being output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    require_once("inc/sys.configs.php");
    require_once("ai/ai-provider.php");
    
    // Check if logged in (basic auth check)
    if (empty($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'status':
        case 'ping':
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
                    require_once("ai/ollama-client.php");
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
            
        case 'test_provider':
            $provider = $_POST['provider'] ?? 'openai';
            $config = [
                'ollama_url' => $_POST['ollama_url'] ?? '',
                'ollama_model' => $_POST['ollama_model'] ?? '',
                'openai_api_key' => $_POST['openai_api_key'] ?? '',
                'openai_model' => $_POST['openai_model'] ?? '',
            ];
            
            $result = AIProvider::testProvider($provider, $config);
            echo json_encode($result);
            exit;
            
        case 'quick_test':
            try {
                $ai = new AIProvider();
                $result = $ai->quickTest();
                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
            
        case 'pull_model':
            $model = $_POST['model'] ?? '';
            $ollamaUrl = $_POST['ollama_url'] ?? 'http://ollama:11434';
            
            if (empty($model)) {
                echo json_encode(['success' => false, 'error' => 'Model name required']);
                exit;
            }
            
            $ch = curl_init($ollamaUrl . '/api/pull');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['name' => $model, 'stream' => false]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 600,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['success' => false, 'error' => "cURL error: $error"]);
            } elseif ($httpCode >= 400) {
                echo json_encode(['success' => false, 'error' => "HTTP $httpCode: $response"]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Model pulled successfully']);
            }
            exit;
            
        case 'list_models':
            $ollamaUrl = $_POST['ollama_url'] ?? 'http://ollama:11434';
            
            $ch = curl_init($ollamaUrl . '/api/tags');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['success' => false, 'error' => "cURL error: $error"]);
            } elseif ($httpCode >= 400) {
                echo json_encode(['success' => false, 'error' => "HTTP $httpCode"]);
            } else {
                $data = json_decode($response, true);
                echo json_encode(['success' => true, 'models' => $data['models'] ?? []]);
            }
            exit;
    }
    
    // Unknown action
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Regular page load
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("inc/sys.configs.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");
require_once("ai/ai-provider.php");

// Check access
check_dev_tools_access();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'save_settings':
                $settings = [
                    'provider' => $_POST['provider'] ?? 'openai',
                    'ollama_enabled' => isset($_POST['ollama_enabled']),
                    'ollama_url' => $_POST['ollama_url'] ?? 'http://ollama:11434',
                    'ollama_model' => $_POST['ollama_model'] ?? 'llama3.2:3b',
                    'ollama_timeout' => intval($_POST['ollama_timeout'] ?? 120),
                    'openai_api_key' => $_POST['openai_api_key'] ?? '',
                    'openai_model' => $_POST['openai_model'] ?? 'gpt-4o-mini',
                    'openai_timeout' => intval($_POST['openai_timeout'] ?? 60),
                    'temperature' => floatval($_POST['temperature'] ?? 0.7),
                    'max_tokens' => intval($_POST['max_tokens'] ?? 2048),
                ];
                
                if (AIProvider::saveSettings($settings)) {
                    $message = 'Settings saved successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to save settings';
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get current settings
$settings = AIProvider::getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Settings - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <style>
        .provider-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .provider-card.selected {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        .provider-card.disabled {
            opacity: 0.6;
            background: #f8f9fa;
        }
        .provider-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .provider-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .provider-icon.ollama {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
        }
        .provider-icon.openai {
            background: linear-gradient(135deg, #10a37f, #0d8a6a);
            color: white;
        }
        .provider-title {
            flex: 1;
        }
        .provider-title h3 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .provider-title p {
            margin: 0;
            color: #666;
            font-size: 13px;
        }
        .provider-select {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .provider-select input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-height: 48px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #888;
            font-size: 12px;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-badge.online {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.offline {
            background: #f8d7da;
            color: #721c24;
        }
        .status-badge.unknown {
            background: #fff3cd;
            color: #856404;
        }
        .test-result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .test-result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .test-result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .btn-test {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-test:hover {
            background: #5a6268;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 26px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #667eea;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Current Status Indicator */
        .current-status-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .current-status-bar h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }
        .current-status-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .current-provider-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .current-provider-details h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .current-provider-details p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .current-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .current-status-badge.online {
            background: rgba(40, 167, 69, 0.9);
        }
        .current-status-badge.offline {
            background: rgba(220, 53, 69, 0.9);
        }
        .current-status-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .status-actions {
            display: flex;
            gap: 10px;
        }
        .status-actions .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .status-actions .btn:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('AI Settings', 'Configure AI provider and model settings', 'fa-cogs', '#667eea'); ?>
        
        <!-- Current Status Bar -->
        <div class="current-status-bar" id="currentStatusBar">
            <div class="current-status-info">
                <div class="current-provider-icon" id="currentProviderIcon">
                    <?php if ($settings['provider'] === 'openai'): ?>
                        <i class="fa fa-bolt"></i>
                    <?php else: ?>
                        <i class="fa fa-server"></i>
                    <?php endif; ?>
                </div>
                <div class="current-provider-details">
                    <h4>Current AI Provider</h4>
                    <h3 id="currentProviderName">
                        <?php echo $settings['provider'] === 'openai' ? 'OpenAI' : 'Ollama'; ?>
                        <span style="font-weight: 400; font-size: 14px; opacity: 0.9;">
                            • <?php echo $settings['provider'] === 'openai' ? $settings['openai_model'] : $settings['ollama_model']; ?>
                        </span>
                    </h3>
                    <p id="currentProviderDesc">
                        <?php if ($settings['provider'] === 'openai'): ?>
                            Cloud-based AI • Fast response • API key required
                        <?php else: ?>
                            Local AI • Privacy-focused • GPU recommended
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="current-status-badge" id="currentStatusBadge">
                    <span class="dot"></span>
                    <span id="currentStatusText">Checking...</span>
                </span>
                <div class="status-actions">
                    <button type="button" class="btn" onclick="checkCurrentStatus()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                    <button type="button" class="btn" onclick="quickTestAI()">
                        <i class="fa fa-comment"></i> Quick Test
                    </button>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fa fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="settingsForm">
            <input type="hidden" name="action" value="save_settings">
            
            <!-- OpenAI Provider -->
            <div class="provider-card <?php echo $settings['provider'] === 'openai' ? 'selected' : ''; ?>" id="openaiCard">
                <div class="provider-header">
                    <div class="provider-icon openai">
                        <i class="fa fa-bolt"></i>
                    </div>
                    <div class="provider-title">
                        <h3>OpenAI</h3>
                        <p>Fast, reliable cloud-based AI. Requires API key.</p>
                    </div>
                    <div class="provider-select">
                        <input type="radio" name="provider" value="openai" id="providerOpenAI" 
                               <?php echo $settings['provider'] === 'openai' ? 'checked' : ''; ?>
                               onchange="updateProviderUI()">
                        <label for="providerOpenAI" style="margin: 0; font-weight: normal;">Use OpenAI</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="password" name="openai_api_key" id="openaiApiKey"
                               value="<?php echo htmlspecialchars($settings['openai_api_key']); ?>"
                               placeholder="sk-...">
                        <small>Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></small>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <select name="openai_model" id="openaiModel">
                            <option value="gpt-4o-mini" <?php echo $settings['openai_model'] === 'gpt-4o-mini' ? 'selected' : ''; ?>>GPT-4o Mini (Fast, Cheap)</option>
                            <option value="gpt-4o" <?php echo $settings['openai_model'] === 'gpt-4o' ? 'selected' : ''; ?>>GPT-4o (Most Capable)</option>
                            <option value="gpt-4-turbo" <?php echo $settings['openai_model'] === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                            <option value="gpt-3.5-turbo" <?php echo $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo (Legacy)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Timeout (seconds)</label>
                    <input type="number" name="openai_timeout" value="<?php echo $settings['openai_timeout']; ?>" min="10" max="300">
                </div>
                
                <button type="button" class="btn-test" onclick="testProvider('openai')">
                    <i class="fa fa-plug"></i> Test Connection
                </button>
                <div class="test-result" id="openaiTestResult"></div>
            </div>
            
            <!-- Ollama Provider -->
            <div class="provider-card <?php echo $settings['provider'] === 'ollama' ? 'selected' : 'disabled'; ?>" id="ollamaCard">
                <div class="provider-header">
                    <div class="provider-icon ollama">
                        <i class="fa fa-server"></i>
                    </div>
                    <div class="provider-title">
                        <h3>Ollama (Local)</h3>
                        <p>Run AI locally. Free but requires powerful hardware (GPU recommended).</p>
                    </div>
                    <div class="provider-select">
                        <label class="toggle-switch">
                            <input type="checkbox" name="ollama_enabled" id="ollamaEnabled"
                                   <?php echo $settings['ollama_enabled'] ? 'checked' : ''; ?>
                                   onchange="toggleOllama()">
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="margin-left: 10px; color: #666;">Enable</span>
                    </div>
                </div>
                
                <div id="ollamaSettings" style="<?php echo $settings['ollama_enabled'] ? '' : 'display: none;'; ?>">
                    <div style="margin-bottom: 15px;">
                        <input type="radio" name="provider" value="ollama" id="providerOllama"
                               <?php echo $settings['provider'] === 'ollama' ? 'checked' : ''; ?>
                               onchange="updateProviderUI()">
                        <label for="providerOllama" style="font-weight: normal;">Use Ollama as primary provider</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Ollama URL</label>
                        <input type="text" name="ollama_url" id="ollamaUrl"
                               value="<?php echo htmlspecialchars($settings['ollama_url']); ?>"
                               placeholder="http://ollama:11434">
                        <small>Use "http://ollama:11434" for Docker, "http://localhost:11434" for local</small>
                    </div>
                    
                    <!-- Model Selection with Resource Info -->
                    <div class="form-group">
                        <label>Model Selection</label>
                        <select name="ollama_model" id="ollamaModel" onchange="updateModelInfo()" class="form-control">
                            <optgroup label="🚀 Lightweight (< 2GB RAM) - Fast">
                                <option value="qwen2:0.5b" <?php echo $settings['ollama_model'] === 'qwen2:0.5b' ? 'selected' : ''; ?>>Qwen2 0.5B - Fastest (395MB)</option>
                                <option value="tinyllama:1.1b" <?php echo $settings['ollama_model'] === 'tinyllama:1.1b' ? 'selected' : ''; ?>>TinyLlama 1.1B - Good balance (637MB)</option>
                            </optgroup>
                            <optgroup label="⚡ Small (2-4GB RAM) - Balanced">
                                <option value="gemma:2b" <?php echo $settings['ollama_model'] === 'gemma:2b' ? 'selected' : ''; ?>>Gemma 2B - Google (1.7GB)</option>
                                <option value="phi3:mini" <?php echo $settings['ollama_model'] === 'phi3:mini' ? 'selected' : ''; ?>>Phi-3 Mini - Microsoft, Smart (2.3GB)</option>
                                <option value="llama3.2:1b" <?php echo $settings['ollama_model'] === 'llama3.2:1b' ? 'selected' : ''; ?>>Llama 3.2 1B - Meta (1.3GB)</option>
                            </optgroup>
                            <optgroup label="🔥 Medium (4-8GB RAM) - Better Quality">
                                <option value="llama3.2:3b" <?php echo $settings['ollama_model'] === 'llama3.2:3b' ? 'selected' : ''; ?>>Llama 3.2 3B - Meta (2GB)</option>
                                <option value="mistral:7b" <?php echo $settings['ollama_model'] === 'mistral:7b' ? 'selected' : ''; ?>>Mistral 7B - High quality (4.1GB)</option>
                                <option value="codellama:7b" <?php echo $settings['ollama_model'] === 'codellama:7b' ? 'selected' : ''; ?>>CodeLlama 7B - Code focused (3.8GB)</option>
                            </optgroup>
                            <optgroup label="⚠️ Large (8GB+ RAM) - GPU Recommended">
                                <option value="llama3.1:8b" <?php echo $settings['ollama_model'] === 'llama3.1:8b' ? 'selected' : ''; ?>>Llama 3.1 8B - Very capable (4.7GB)</option>
                                <option value="gemma2:9b" <?php echo $settings['ollama_model'] === 'gemma2:9b' ? 'selected' : ''; ?>>Gemma2 9B - Google latest (5.4GB)</option>
                            </optgroup>
                            <optgroup label="Custom">
                                <option value="custom">Custom model name...</option>
                            </optgroup>
                        </select>
                        <input type="text" id="ollamaModelCustom" placeholder="Enter custom model name" 
                               style="display: none; margin-top: 10px;" class="form-control">
                    </div>
                    
                    <!-- Model Info Panel -->
                    <div id="modelInfoPanel" style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div id="modelInfo"></div>
                    </div>
                    
                    <!-- Resource Warning -->
                    <div id="resourceWarning" style="display: none; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <strong><i class="fa fa-exclamation-triangle" style="color: #856404;"></i> High Resource Usage Warning</strong>
                        <p id="warningText" style="margin: 10px 0 0 0; color: #856404;"></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Timeout (seconds)</label>
                        <input type="number" name="ollama_timeout" value="<?php echo $settings['ollama_timeout']; ?>" min="30" max="600">
                        <small>Ollama on CPU can be slow. Increase if you get timeouts.</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn-test" onclick="testProvider('ollama')">
                            <i class="fa fa-plug"></i> Test Connection
                        </button>
                        <button type="button" class="btn-test" onclick="pullModel()" id="pullModelBtn">
                            <i class="fa fa-download"></i> Pull Model
                        </button>
                        <button type="button" class="btn-test" onclick="listInstalledModels()">
                            <i class="fa fa-list"></i> Show Installed
                        </button>
                    </div>
                    <div class="test-result" id="ollamaTestResult"></div>
                </div>
                
                <div id="ollamaDisabledMsg" style="<?php echo $settings['ollama_enabled'] ? 'display: none;' : ''; ?>; color: #888; font-style: italic;">
                    <i class="fa fa-info-circle"></i> Ollama is disabled. Enable to configure local AI.
                </div>
            </div>
            
            <!-- Common Settings -->
            <div class="card">
                <h3 style="margin-top: 0;"><i class="fa fa-sliders"></i> Common Settings</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Temperature</label>
                        <input type="number" name="temperature" value="<?php echo $settings['temperature']; ?>" 
                               min="0" max="2" step="0.1">
                        <small>0 = deterministic, 1 = creative, 2 = very random</small>
                    </div>
                    <div class="form-group">
                        <label>Max Tokens</label>
                        <input type="number" name="max_tokens" value="<?php echo $settings['max_tokens']; ?>" 
                               min="100" max="8000">
                        <small>Maximum response length</small>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Settings
                </button>
                <button type="button" class="btn btn-secondary" onclick="quickTest()">
                    <i class="fa fa-play"></i> Quick Test
                </button>
            </div>
            
            <div class="test-result" id="quickTestResult" style="margin-top: 20px;"></div>
        </form>
    </div>
    
    <script>
    // Model information database
    const modelInfo = {
        'qwen2:0.5b': { size: '395MB', ram: '1GB', cpu: 15, speed: 'Very Fast', quality: '⭐⭐', desc: 'Smallest and fastest. Good for simple tasks.' },
        'tinyllama:1.1b': { size: '637MB', ram: '1.5GB', cpu: 20, speed: 'Fast', quality: '⭐⭐⭐', desc: 'Great balance of speed and quality for basic tasks.' },
        'gemma:2b': { size: '1.7GB', ram: '3GB', cpu: 30, speed: 'Fast', quality: '⭐⭐⭐', desc: 'Google model. Good reasoning ability.' },
        'phi3:mini': { size: '2.3GB', ram: '4GB', cpu: 35, speed: 'Medium', quality: '⭐⭐⭐⭐', desc: 'Microsoft model. Surprisingly smart for its size. Recommended!' },
        'llama3.2:1b': { size: '1.3GB', ram: '2GB', cpu: 25, speed: 'Fast', quality: '⭐⭐⭐', desc: 'Meta latest small model. Good all-rounder.' },
        'llama3.2:3b': { size: '2GB', ram: '4GB', cpu: 40, speed: 'Medium', quality: '⭐⭐⭐⭐', desc: 'Good quality with reasonable speed.' },
        'mistral:7b': { size: '4.1GB', ram: '8GB', cpu: 70, speed: 'Slow', quality: '⭐⭐⭐⭐⭐', desc: 'High quality. Needs good hardware.' },
        'codellama:7b': { size: '3.8GB', ram: '8GB', cpu: 70, speed: 'Slow', quality: '⭐⭐⭐⭐⭐', desc: 'Specialized for code. Great for CRUD testing.' },
        'llama3.1:8b': { size: '4.7GB', ram: '10GB', cpu: 80, speed: 'Very Slow', quality: '⭐⭐⭐⭐⭐', desc: 'Very capable. GPU recommended.' },
        'gemma2:9b': { size: '5.4GB', ram: '12GB', cpu: 85, speed: 'Very Slow', quality: '⭐⭐⭐⭐⭐', desc: 'Google latest. Excellent quality. GPU needed.' },
    };
    
    function updateModelInfo() {
        const select = document.getElementById('ollamaModel');
        const model = select.value;
        const infoPanel = document.getElementById('modelInfoPanel');
        const customInput = document.getElementById('ollamaModelCustom');
        const warning = document.getElementById('resourceWarning');
        
        // Handle custom model
        if (model === 'custom') {
            customInput.style.display = 'block';
            customInput.name = 'ollama_model';
            select.name = '';
            infoPanel.innerHTML = '<i class="fa fa-info-circle"></i> Enter a custom model name. Make sure it\'s installed in Ollama.';
            warning.style.display = 'none';
            return;
        } else {
            customInput.style.display = 'none';
            customInput.name = '';
            select.name = 'ollama_model';
        }
        
        const info = modelInfo[model];
        if (info) {
            infoPanel.innerHTML = `
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div><strong>Size:</strong> ${info.size}</div>
                    <div><strong>RAM:</strong> ${info.ram}</div>
                    <div><strong>Speed:</strong> ${info.speed}</div>
                    <div><strong>Quality:</strong> ${info.quality}</div>
                </div>
                <p style="margin: 10px 0 0 0; color: #666;">${info.desc}</p>
            `;
            
            // Show warning if CPU usage > 60%
            if (info.cpu > 60) {
                warning.style.display = 'block';
                document.getElementById('warningText').innerHTML = `
                    This model may use <strong>${info.cpu}%+ CPU</strong> and <strong>${info.ram}+ RAM</strong> on your machine.<br>
                    <span style="font-size: 12px;">Running on CPU without GPU will be very slow (30-120+ seconds per response). 
                    Consider using a smaller model or OpenAI for better performance.</span>
                `;
            } else {
                warning.style.display = 'none';
            }
        } else {
            infoPanel.innerHTML = '<i class="fa fa-info-circle"></i> Select a model to see resource requirements.';
            warning.style.display = 'none';
        }
    }
    
    async function pullModel() {
        const model = document.getElementById('ollamaModel').value;
        if (model === 'custom') {
            const customModel = document.getElementById('ollamaModelCustom').value;
            if (!customModel) {
                alert('Please enter a model name first');
                return;
            }
        }
        
        const modelName = model === 'custom' ? document.getElementById('ollamaModelCustom').value : model;
        const resultDiv = document.getElementById('ollamaTestResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result';
        resultDiv.innerHTML = `<div class="loading-spinner"></div> Pulling model "${modelName}"... This may take several minutes.`;
        
        try {
            const formData = new FormData();
            formData.append('action', 'pull_model');
            formData.append('model', modelName);
            formData.append('ollama_url', document.getElementById('ollamaUrl').value);
            
            const response = await fetch('/ai-settings.php?ajax=1', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                resultDiv.className = 'test-result success';
                resultDiv.innerHTML = `<i class="fa fa-check-circle"></i> <strong>Model "${modelName}" pulled successfully!</strong>`;
            } else {
                resultDiv.className = 'test-result error';
                resultDiv.innerHTML = `<i class="fa fa-times-circle"></i> <strong>Pull Failed:</strong> ${data.error || 'Unknown error'}`;
            }
        } catch (e) {
            resultDiv.className = 'test-result error';
            resultDiv.innerHTML = `<i class="fa fa-times-circle"></i> <strong>Error:</strong> ${e.message}`;
        }
    }
    
    async function listInstalledModels() {
        const resultDiv = document.getElementById('ollamaTestResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result';
        resultDiv.innerHTML = '<div class="loading-spinner"></div> Fetching installed models...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'list_models');
            formData.append('ollama_url', document.getElementById('ollamaUrl').value);
            
            const response = await fetch('/ai-settings.php?ajax=1', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.models) {
                let html = '<i class="fa fa-check-circle"></i> <strong>Installed Models:</strong><br><ul style="margin: 10px 0; padding-left: 20px;">';
                data.models.forEach(m => {
                    const size = m.size ? (m.size / 1024 / 1024 / 1024).toFixed(1) + ' GB' : 'Unknown';
                    html += `<li><strong>${m.name}</strong> (${size})</li>`;
                });
                html += '</ul>';
                resultDiv.className = 'test-result success';
                resultDiv.innerHTML = html;
            } else {
                resultDiv.className = 'test-result error';
                resultDiv.innerHTML = `<i class="fa fa-times-circle"></i> ${data.error || 'Could not fetch models'}`;
            }
        } catch (e) {
            resultDiv.className = 'test-result error';
            resultDiv.innerHTML = `<i class="fa fa-times-circle"></i> <strong>Error:</strong> ${e.message}`;
        }
    }
    
    // Initialize model info on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateModelInfo();
    });
    
    function updateProviderUI() {
        const provider = document.querySelector('input[name="provider"]:checked').value;
        document.getElementById('openaiCard').classList.toggle('selected', provider === 'openai');
        document.getElementById('ollamaCard').classList.toggle('selected', provider === 'ollama');
    }
    
    function toggleOllama() {
        const enabled = document.getElementById('ollamaEnabled').checked;
        document.getElementById('ollamaSettings').style.display = enabled ? '' : 'none';
        document.getElementById('ollamaDisabledMsg').style.display = enabled ? 'none' : '';
        document.getElementById('ollamaCard').classList.toggle('disabled', !enabled);
        
        // If disabling Ollama and it was selected, switch to OpenAI
        if (!enabled && document.getElementById('providerOllama').checked) {
            document.getElementById('providerOpenAI').checked = true;
            updateProviderUI();
        }
    }
    
    async function testProvider(provider) {
        const resultDiv = document.getElementById(provider + 'TestResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result';
        resultDiv.innerHTML = '<div class="loading-spinner"></div> Testing connection...';
        
        const formData = new FormData();
        formData.append('action', 'test_provider');
        formData.append('provider', provider);
        
        if (provider === 'openai') {
            formData.append('openai_api_key', document.getElementById('openaiApiKey').value);
            formData.append('openai_model', document.getElementById('openaiModel').value);
        } else {
            formData.append('ollama_url', document.getElementById('ollamaUrl').value);
            const model = document.getElementById('ollamaModel').value;
            formData.append('ollama_model', model === 'custom' ? document.getElementById('ollamaModelCustom').value : model);
        }
        
        try {
            const response = await fetch('/ai-settings.php?ajax=1', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();
            console.log('Test provider response:', data);
            
            if (data.available) {
                resultDiv.className = 'test-result success';
                let models = data.models ? data.models.map(m => m.name).join(', ') : 'N/A';
                resultDiv.innerHTML = '<i class="fa fa-check-circle"></i> <strong>Connected!</strong><br>Available models: ' + models;
            } else {
                resultDiv.className = 'test-result error';
                resultDiv.innerHTML = '<i class="fa fa-times-circle"></i> <strong>Connection Failed</strong><br>' + (data.error || 'Unknown error');
            }
        } catch (e) {
            resultDiv.className = 'test-result error';
            resultDiv.innerHTML = '<i class="fa fa-times-circle"></i> <strong>Error:</strong> ' + e.message;
        }
    }
    
    async function quickTest() {
        const resultDiv = document.getElementById('quickTestResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'test-result';
        resultDiv.innerHTML = '<div class="loading-spinner"></div> Running quick test with current settings...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'quick_test');
            
            const response = await fetch('/ai-settings.php?ajax=1', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                resultDiv.className = 'test-result success';
                resultDiv.innerHTML = `
                    <i class="fa fa-check-circle"></i> <strong>Test Passed!</strong><br>
                    <strong>Provider:</strong> ${data.provider}<br>
                    <strong>Response:</strong> ${data.response || 'N/A'}<br>
                    <strong>Time:</strong> ${data.elapsed_time}s
                `;
            } else {
                resultDiv.className = 'test-result error';
                resultDiv.innerHTML = '<i class="fa fa-times-circle"></i> <strong>Test Failed</strong><br>' + (data.error || 'Unknown error');
            }
        } catch (e) {
            resultDiv.className = 'test-result error';
            resultDiv.innerHTML = '<i class="fa fa-times-circle"></i> <strong>Error:</strong> ' + e.message;
        }
    }
    
    // Check current provider status
    async function checkCurrentStatus() {
        const badge = document.getElementById('currentStatusBadge');
        const statusText = document.getElementById('currentStatusText');
        statusText.textContent = 'Checking...';
        badge.className = 'current-status-badge';
        
        try {
            const response = await fetch('/ai-settings.php?ajax=1&action=status', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success && data.provider) {
                const provider = data.provider;
                
                // Update status badge
                if (provider.available) {
                    badge.className = 'current-status-badge online';
                    statusText.textContent = 'Online';
                } else {
                    badge.className = 'current-status-badge offline';
                    statusText.textContent = 'Offline';
                }
                
                // Update provider info
                document.getElementById('currentProviderName').innerHTML = 
                    provider.display_name + 
                    ' <span style="font-weight: 400; font-size: 14px; opacity: 0.9;">• ' + provider.model + '</span>';
                
                // Update icon
                const iconEl = document.getElementById('currentProviderIcon');
                iconEl.innerHTML = provider.name === 'openai' 
                    ? '<i class="fa fa-bolt"></i>' 
                    : '<i class="fa fa-server"></i>';
                
                // Update description
                document.getElementById('currentProviderDesc').textContent = 
                    provider.name === 'openai' 
                        ? 'Cloud-based AI • Fast response • API key required'
                        : 'Local AI • Privacy-focused • GPU recommended';
            }
        } catch (e) {
            badge.className = 'current-status-badge offline';
            statusText.textContent = 'Error';
        }
    }
    
    // Quick test from status bar
    async function quickTestAI() {
        const statusText = document.getElementById('currentStatusText');
        const originalText = statusText.textContent;
        statusText.textContent = 'Testing...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'quick_test');
            
            const response = await fetch('/ai-settings.php?ajax=1', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();
            
            if (data.success) {
                statusText.textContent = 'Working!';
                document.getElementById('currentStatusBadge').className = 'current-status-badge online';
                // Show quick response
                alert('✅ AI Test Passed!\n\nProvider: ' + data.provider + '\nResponse: ' + (data.response || 'OK') + '\nTime: ' + data.elapsed_time + 's');
            } else {
                statusText.textContent = 'Failed';
                document.getElementById('currentStatusBadge').className = 'current-status-badge offline';
                alert('❌ AI Test Failed!\n\n' + (data.error || 'Unknown error'));
            }
        } catch (e) {
            statusText.textContent = 'Error';
            alert('❌ Error: ' + e.message);
        }
        
        // Refresh status after a moment
        setTimeout(checkCurrentStatus, 2000);
    }
    
    // Check status on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateModelInfo();
        checkCurrentStatus();
    });
    </script>
</body>
</html>
