<?php
namespace App\Controllers;

/**
 * AiSettingsController - AI Provider Configuration
 * 
 * Standalone page (own HTML/head/body) for configuring AI providers.
 * Handles both AJAX API endpoints and the settings form page.
 * This is a standalone page that does NOT use the admin layout.
 */
class AiSettingsController extends BaseController
{
    public function index(): void
    {
        // Handle AJAX requests BEFORE any output
        if (isset($_GET['ajax']) || (isset($_POST['action']) && in_array($_POST['action'], ['test_provider', 'quick_test', 'pull_model', 'list_models']))) {
            $this->handleAjax();
            return;
        }

        // Standalone page — require files and render full HTML
        require_once __DIR__ . '/../../ai/ai-provider.php';

        // Check access
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $message = '';
        $messageType = '';

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
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

            if (\AIProvider::saveSettings($settings)) {
                $message = 'Settings saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to save settings';
                $messageType = 'error';
            }
        }

        $settings = \AIProvider::getSettings();

        // Render standalone view (includes own HTML/head/body)
        $viewFile = __DIR__ . '/../../views/ai/settings.php';
        include $viewFile;
        exit;
    }

    private function handleAjax(): void
    {
        error_reporting(0);
        ini_set('display_errors', 0);

        require_once __DIR__ . '/../../inc/sys.configs.php';
        require_once __DIR__ . '/../../ai/ai-provider.php';

        if (empty($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Authentication required']);
        }

        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        switch ($action) {
            case 'status':
            case 'ping':
                $settings = \AIProvider::getSettings();
                $provider = $settings['provider'] ?? 'openai';
                $providerInfo = ['name' => $provider, 'available' => false, 'model' => '', 'display_name' => ''];

                if ($provider === 'openai') {
                    $providerInfo['model'] = $settings['openai_model'] ?? 'gpt-4o-mini';
                    $providerInfo['available'] = !empty($settings['openai_api_key']);
                    $providerInfo['display_name'] = 'OpenAI';
                } else {
                    $providerInfo['model'] = $settings['ollama_model'] ?? 'llama3.2:3b';
                    $providerInfo['display_name'] = 'Ollama';
                    if ($settings['ollama_enabled'] ?? false) {
                        require_once __DIR__ . '/../../ai/ollama-client.php';
                        try {
                            $ollama = new \OllamaClient(['base_url' => $settings['ollama_url'] ?? 'http://ollama:11434']);
                            $health = $ollama->checkHealth();
                            $providerInfo['available'] = $health['available'] ?? false;
                        } catch (\Exception $e) {
                            $providerInfo['available'] = false;
                        }
                    }
                }

                $this->json([
                    'success' => true,
                    'provider' => $providerInfo,
                    'settings' => ['temperature' => $settings['temperature'] ?? 0.7, 'max_tokens' => $settings['max_tokens'] ?? 2048],
                    'timestamp' => date('c'),
                ]);

            case 'test_provider':
                $provider = $_POST['provider'] ?? 'openai';
                $config = [
                    'ollama_url' => $_POST['ollama_url'] ?? '',
                    'ollama_model' => $_POST['ollama_model'] ?? '',
                    'openai_api_key' => $_POST['openai_api_key'] ?? '',
                    'openai_model' => $_POST['openai_model'] ?? '',
                ];
                $this->json(\AIProvider::testProvider($provider, $config));

            case 'quick_test':
                try {
                    $ai = new \AIProvider();
                    $this->json($ai->quickTest());
                } catch (\Exception $e) {
                    $this->json(['success' => false, 'error' => $e->getMessage()]);
                }

            case 'pull_model':
                $model = $_POST['model'] ?? '';
                $ollamaUrl = $_POST['ollama_url'] ?? 'http://ollama:11434';
                if (empty($model)) { $this->json(['success' => false, 'error' => 'Model name required']); }
                $ch = curl_init($ollamaUrl . '/api/pull');
                curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['name' => $model, 'stream' => false]),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 600]);
                $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $error = curl_error($ch); curl_close($ch);
                if ($error) $this->json(['success' => false, 'error' => "cURL error: $error"]);
                elseif ($httpCode >= 400) $this->json(['success' => false, 'error' => "HTTP $httpCode: $response"]);
                else $this->json(['success' => true, 'message' => 'Model pulled successfully']);

            case 'list_models':
                $ollamaUrl = $_POST['ollama_url'] ?? 'http://ollama:11434';
                $ch = curl_init($ollamaUrl . '/api/tags');
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
                $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $error = curl_error($ch); curl_close($ch);
                if ($error) $this->json(['success' => false, 'error' => "cURL error: $error"]);
                elseif ($httpCode >= 400) $this->json(['success' => false, 'error' => "HTTP $httpCode"]);
                else { $data = json_decode($response, true); $this->json(['success' => true, 'models' => $data['models'] ?? []]); }

            default:
                $this->json(['success' => false, 'error' => 'Unknown action']);
        }
    }
}
