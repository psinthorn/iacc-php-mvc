<?php
/**
 * Ollama API Client
 * 
 * PHP wrapper for communicating with Ollama LLM API
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-04
 */

class OllamaClient
{
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private array $options;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array from ai/config.php
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'base_url' => 'http://ollama:11434',
            'model' => 'llama3.2:3b',
            'timeout' => 120,
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'top_p' => 0.9,
            'top_k' => 40,
            'num_ctx' => 4096,
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->model = $config['model'];
        $this->timeout = $config['timeout'];
        $this->options = [
            'temperature' => $config['temperature'],
            'num_predict' => $config['max_tokens'],
            'top_p' => $config['top_p'],
            'top_k' => $config['top_k'],
            'num_ctx' => $config['num_ctx'],
        ];
    }
    
    /**
     * Generate a completion from Ollama
     * 
     * @param string $prompt The prompt to send
     * @param string|null $system Optional system prompt
     * @param array $options Additional options to override defaults
     * @return array Response with 'success', 'response', 'error' keys
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => array_merge($this->options, $options),
        ];
        
        if ($system) {
            $payload['system'] = $system;
        }
        
        return $this->request('/api/generate', $payload);
    }
    
    /**
     * Chat completion with message history
     * 
     * @param array $messages Array of messages [{role: 'user'|'assistant'|'system', content: '...'}]
     * @param array $tools Optional tool definitions for function calling
     * @param array $options Additional options
     * @return array Response with conversation
     */
    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'options' => array_merge($this->options, $options),
        ];
        
        // Add tools if provided (for function calling)
        if (!empty($tools)) {
            $payload['tools'] = $this->formatToolsForOllama($tools);
        }
        
        return $this->request('/api/chat', $payload);
    }
    
    /**
     * Format tools array for Ollama's expected format
     * 
     * @param array $tools Tool definitions
     * @return array Formatted tools
     */
    private function formatToolsForOllama(array $tools): array
    {
        $formatted = [];
        
        foreach ($tools as $tool) {
            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $this->formatParameters($tool['parameters'] ?? []),
                        'required' => $this->getRequiredParams($tool['parameters'] ?? []),
                    ],
                ],
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format parameters for Ollama tool definition
     */
    private function formatParameters(array $params): array
    {
        $properties = [];
        
        foreach ($params as $name => $config) {
            $prop = [
                'type' => $config['type'] ?? 'string',
                'description' => $config['description'] ?? '',
            ];
            
            if (isset($config['enum'])) {
                $prop['enum'] = $config['enum'];
            }
            
            $properties[$name] = $prop;
        }
        
        return $properties;
    }
    
    /**
     * Get required parameter names
     */
    private function getRequiredParams(array $params): array
    {
        $required = [];
        
        foreach ($params as $name => $config) {
            if (!empty($config['required'])) {
                $required[] = $name;
            }
        }
        
        return $required;
    }
    
    /**
     * Check if Ollama is available
     * 
     * @return array Status with 'available' boolean
     */
    public function checkHealth(): array
    {
        $result = $this->request('/api/tags', [], 'GET');
        
        return [
            'available' => $result['success'],
            'models' => $result['success'] ? ($result['response']['models'] ?? []) : [],
            'error' => $result['error'] ?? null,
        ];
    }
    
    /**
     * List available models
     * 
     * @return array List of models
     */
    public function listModels(): array
    {
        $result = $this->request('/api/tags', [], 'GET');
        
        if (!$result['success']) {
            return [];
        }
        
        return array_map(function($model) {
            return [
                'name' => $model['name'],
                'size' => $model['size'] ?? 0,
                'modified' => $model['modified_at'] ?? null,
            ];
        }, $result['response']['models'] ?? []);
    }
    
    /**
     * Pull a model from Ollama registry
     * 
     * @param string $modelName Model to pull
     * @return array Result
     */
    public function pullModel(string $modelName): array
    {
        return $this->request('/api/pull', [
            'name' => $modelName,
            'stream' => false,
        ]);
    }
    
    /**
     * Make HTTP request to Ollama API
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request payload
     * @param string $method HTTP method
     * @return array Response
     */
    private function request(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);
        
        // Handle cURL errors
        if ($errno) {
            return [
                'success' => false,
                'error' => "cURL error ($errno): $error",
                'http_code' => 0,
            ];
        }
        
        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorBody = json_decode($response, true);
            return [
                'success' => false,
                'error' => $errorBody['error'] ?? "HTTP error: $httpCode",
                'http_code' => $httpCode,
            ];
        }
        
        // Parse response
        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from Ollama',
                'raw_response' => $response,
            ];
        }
        
        return [
            'success' => true,
            'response' => $decoded,
            'http_code' => $httpCode,
        ];
    }
    
    /**
     * Extract text response from chat/generate result
     * 
     * @param array $result Result from chat() or generate()
     * @return string|null The text response
     */
    public function getResponseText(array $result): ?string
    {
        if (!$result['success']) {
            return null;
        }
        
        // Chat endpoint
        if (isset($result['response']['message']['content'])) {
            return $result['response']['message']['content'];
        }
        
        // Generate endpoint
        if (isset($result['response']['response'])) {
            return $result['response']['response'];
        }
        
        return null;
    }
    
    /**
     * Extract tool calls from chat result
     * 
     * @param array $result Result from chat()
     * @return array Tool calls array
     */
    public function getToolCalls(array $result): array
    {
        if (!$result['success']) {
            return [];
        }
        
        return $result['response']['message']['tool_calls'] ?? [];
    }
    
    /**
     * Check if response contains tool calls
     * 
     * @param array $result Result from chat()
     * @return bool
     */
    public function hasToolCalls(array $result): bool
    {
        return !empty($this->getToolCalls($result));
    }
    
    /**
     * Get usage statistics from response
     * 
     * @param array $result Result from chat() or generate()
     * @return array Token usage info
     */
    public function getUsage(array $result): array
    {
        if (!$result['success']) {
            return ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }
        
        $response = $result['response'];
        
        return [
            'prompt_tokens' => $response['prompt_eval_count'] ?? 0,
            'completion_tokens' => $response['eval_count'] ?? 0,
            'total_tokens' => ($response['prompt_eval_count'] ?? 0) + ($response['eval_count'] ?? 0),
        ];
    }
    
    /**
     * Get current model name
     * 
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }
    
    /**
     * Set model to use
     * 
     * @param string $model Model name
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
}
