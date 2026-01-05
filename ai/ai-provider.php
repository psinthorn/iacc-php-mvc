<?php
/**
 * AI Provider Wrapper
 * 
 * Unified interface for Ollama and OpenAI APIs
 * Automatically selects based on configuration
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

require_once __DIR__ . '/ollama-client.php';
require_once __DIR__ . '/openai-client.php';

class AIProvider
{
    private $client;
    private string $provider;
    private array $config;
    
    // Settings file path
    const SETTINGS_FILE = __DIR__ . '/../cache/ai-settings.json';
    
    /**
     * Constructor - automatically selects provider based on settings
     * 
     * @param array $overrideConfig Optional config to override saved settings
     */
    public function __construct(array $overrideConfig = [])
    {
        $settings = self::getSettings();
        $this->config = array_merge($settings, $overrideConfig);
        $this->provider = $this->config['provider'] ?? 'openai';
        
        $this->initClient();
    }
    
    /**
     * Initialize the appropriate client
     */
    private function initClient(): void
    {
        if ($this->provider === 'ollama') {
            $this->client = new OllamaClient([
                'base_url' => $this->config['ollama_url'] ?? 'http://ollama:11434',
                'model' => $this->config['ollama_model'] ?? 'llama3.2:3b',
                'timeout' => $this->config['ollama_timeout'] ?? 120,
                'temperature' => $this->config['temperature'] ?? 0.7,
                'max_tokens' => $this->config['max_tokens'] ?? 2048,
            ]);
        } else {
            $this->client = new OpenAIClient([
                'api_key' => $this->config['openai_api_key'] ?? '',
                'model' => $this->config['openai_model'] ?? 'gpt-4o-mini',
                'timeout' => $this->config['openai_timeout'] ?? 60,
                'temperature' => $this->config['temperature'] ?? 0.7,
                'max_tokens' => $this->config['max_tokens'] ?? 2048,
            ]);
        }
    }
    
    /**
     * Get current provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
    
    /**
     * Get current model name
     */
    public function getModel(): string
    {
        $settings = self::getSettings();
        if ($this->provider === 'openai') {
            return $settings['openai_model'] ?? 'gpt-4o-mini';
        }
        return $settings['ollama_model'] ?? 'llama3.2:3b';
    }
    
    /**
     * Get the underlying client
     */
    public function getClient()
    {
        return $this->client;
    }
    
    /**
     * Generate a completion
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        return $this->client->generate($prompt, $system, $options);
    }
    
    /**
     * Chat completion with message history
     */
    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        return $this->client->chat($messages, $tools, $options);
    }
    
    /**
     * Check if provider is available
     */
    public function checkHealth(): array
    {
        $result = $this->client->checkHealth();
        $result['provider'] = $this->provider;
        return $result;
    }
    
    /**
     * List available models
     */
    public function listModels(): array
    {
        return $this->client->listModels();
    }
    
    // =========================================================
    // Static Settings Management
    // =========================================================
    
    /**
     * Get saved settings
     */
    public static function getSettings(): array
    {
        $defaults = [
            'provider' => 'openai',  // Default to OpenAI (Ollama off by default)
            'ollama_enabled' => false,
            'ollama_url' => 'http://ollama:11434',
            'ollama_model' => 'llama3.2:3b',
            'ollama_timeout' => 120,
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'openai_timeout' => 60,
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ];
        
        if (file_exists(self::SETTINGS_FILE)) {
            $saved = json_decode(file_get_contents(self::SETTINGS_FILE), true);
            if (is_array($saved)) {
                return array_merge($defaults, $saved);
            }
        }
        
        return $defaults;
    }
    
    /**
     * Save settings
     */
    public static function saveSettings(array $settings): bool
    {
        $dir = dirname(self::SETTINGS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Merge with existing settings
        $current = self::getSettings();
        $merged = array_merge($current, $settings);
        
        return file_put_contents(self::SETTINGS_FILE, json_encode($merged, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Test a specific provider configuration
     */
    public static function testProvider(string $provider, array $config): array
    {
        if ($provider === 'ollama') {
            $client = new OllamaClient([
                'base_url' => $config['ollama_url'] ?? 'http://ollama:11434',
                'model' => $config['ollama_model'] ?? 'llama3.2:3b',
                'timeout' => 10,
            ]);
        } else {
            $client = new OpenAIClient([
                'api_key' => $config['openai_api_key'] ?? '',
                'model' => $config['openai_model'] ?? 'gpt-4o-mini',
                'timeout' => 10,
            ]);
        }
        
        return $client->checkHealth();
    }
    
    /**
     * Quick test with a simple prompt
     */
    public function quickTest(): array
    {
        $start = microtime(true);
        $result = $this->generate('Say "Hello, I am working!" in exactly those words.', null, [
            'max_tokens' => 50,
            'num_predict' => 50,
        ]);
        $elapsed = round(microtime(true) - $start, 2);
        
        $result['elapsed_time'] = $elapsed;
        $result['provider'] = $this->provider;
        
        return $result;
    }
}
