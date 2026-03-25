<?php
namespace App\Controllers;

/**
 * AiChatController - AI Chat API endpoint
 * 
 * Wraps the existing ChatHandler class.
 * This is a pure JSON API — no HTML rendering.
 * Handles: chat, confirm, cancel, history, health, sessions, ping, status, debug
 */
class AiChatController extends BaseController
{
    public function index(): void
    {
        // Set JSON response header
        header('Content-Type: application/json');

        // Allow CORS with credentials
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

        $action = $_GET['action'] ?? 'chat';

        // Handle public endpoints that don't need auth (ping/status/debug)
        if (in_array($action, ['ping', 'status'])) {
            $this->handlePing();
            return;
        }

        if ($action === 'debug') {
            $this->handleDebug();
            return;
        }

        // For all other actions, delegate to ChatHandler
        $this->handleChat();
    }

    private function handlePing(): void
    {
        require_once __DIR__ . '/../../ai/ai-provider.php';

        $settings = \AIProvider::getSettings();
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

        echo json_encode([
            'success' => true,
            'status' => 'ok',
            'provider' => $providerInfo,
            'ollama' => ['available' => $providerInfo['available']], // backward compat
            'model' => $providerInfo['model'],
            'timestamp' => date('c'),
        ]);
        exit;
    }

    private function handleDebug(): void
    {
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

    private function handleChat(): void
    {
        // Include ChatHandler dependencies — it manages its own DB, auth, etc.
        $chatHandlerFile = __DIR__ . '/../../ai/chat-handler.php';

        // The chat-handler.php creates ChatHandler and calls handle() at the bottom.
        // We need to include it, which will execute the handler and exit.
        // First, make sure working directory is correct for the handler's includes.
        $originalDir = getcwd();
        chdir(__DIR__ . '/../../');

        require $chatHandlerFile;

        // Restore dir (won't reach here since chat-handler exits, but just in case)
        chdir($originalDir);
    }
}
