<?php
/**
 * OpenAI API Client
 * 
 * PHP wrapper for communicating with OpenAI API
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-05
 */

class OpenAIClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private array $options;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'api_key' => getenv('OPENAI_API_KEY') ?: '',
            'base_url' => 'https://api.openai.com/v1',
            'model' => 'gpt-4o-mini',
            'timeout' => 60,
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        $this->apiKey = $config['api_key'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->model = $config['model'];
        $this->timeout = $config['timeout'];
        $this->options = [
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
        ];
    }
    
    /**
     * Generate a completion from OpenAI
     * 
     * @param string $prompt The prompt to send
     * @param string|null $system Optional system prompt
     * @param array $options Additional options to override defaults
     * @return array Response with 'success', 'response', 'error' keys
     */
    public function generate(string $prompt, ?string $system = null, array $options = []): array
    {
        $messages = [];
        
        if ($system) {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];
        
        return $this->chat($messages, [], $options);
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
            'temperature' => $options['temperature'] ?? $this->options['temperature'],
            'max_tokens' => $options['max_tokens'] ?? $this->options['max_tokens'],
        ];
        
        // Add tools if provided (for function calling)
        if (!empty($tools)) {
            $payload['tools'] = $this->formatToolsForOpenAI($tools);
            $payload['tool_choice'] = 'auto';
        }
        
        $result = $this->request('/chat/completions', $payload);
        
        if ($result['success']) {
            $choice = $result['data']['choices'][0] ?? null;
            if ($choice) {
                $message = $choice['message'] ?? [];
                
                // Check for tool calls
                if (!empty($message['tool_calls'])) {
                    return [
                        'success' => true,
                        'response' => $message['content'] ?? '',
                        'tool_calls' => array_map(function($tc) {
                            return [
                                'id' => $tc['id'],
                                'name' => $tc['function']['name'],
                                'arguments' => json_decode($tc['function']['arguments'], true),
                            ];
                        }, $message['tool_calls']),
                        'finish_reason' => $choice['finish_reason'] ?? 'stop',
                    ];
                }
                
                return [
                    'success' => true,
                    'response' => $message['content'] ?? '',
                    'finish_reason' => $choice['finish_reason'] ?? 'stop',
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Format tools array for OpenAI's expected format
     */
    private function formatToolsForOpenAI(array $tools): array
    {
        $formatted = [];
        
        foreach ($tools as $tool) {
            $properties = $this->formatParameters($tool['parameters'] ?? []);
            $required = $this->getRequiredParams($tool['parameters'] ?? []);
            
            // OpenAI requires parameters to be an object, not array
            // Use stdClass for empty properties to ensure {} not []
            $parameters = [
                'type' => 'object',
                'properties' => empty($properties) ? new \stdClass() : $properties,
            ];
            
            // Only add required if not empty
            if (!empty($required)) {
                $parameters['required'] = $required;
            }
            
            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $parameters,
                ],
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format parameters for tool definition
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
     * Check if OpenAI API is available
     * 
     * @return array Status with 'available' boolean
     */
    public function checkHealth(): array
    {
        if (empty($this->apiKey)) {
            return [
                'available' => false,
                'models' => [],
                'error' => 'API key not configured',
            ];
        }
        
        $result = $this->request('/models', [], 'GET');
        
        if ($result['success']) {
            $models = array_filter($result['data']['data'] ?? [], function($m) {
                return strpos($m['id'], 'gpt') !== false;
            });
            
            return [
                'available' => true,
                'models' => array_values(array_map(function($m) {
                    return ['name' => $m['id']];
                }, $models)),
                'error' => null,
            ];
        }
        
        return [
            'available' => false,
            'models' => [],
            'error' => $result['error'] ?? 'Unknown error',
        ];
    }
    
    /**
     * List available models
     * 
     * @return array List of models
     */
    public function listModels(): array
    {
        $health = $this->checkHealth();
        return $health['models'] ?? [];
    }
    
    /**
     * Make HTTP request to OpenAI API
     */
    private function request(string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);
        
        if ($errno) {
            return [
                'success' => false,
                'error' => "cURL error ($errno): $error",
            ];
        }
        
        $data = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $data['error']['message'] ?? "HTTP $httpCode error";
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }
        
        return [
            'success' => true,
            'data' => $data,
        ];
    }
}
