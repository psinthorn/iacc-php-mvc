<?php

/**
 * AI Agent Runner
 * Runs any team agent using the Claude API with the correct system prompt.
 *
 * Usage:
 *   $runner = new AgentRunner();
 *   $response = $runner->run('pm', 'Write a spec for CSV bulk import');
 *   echo $response;
 */
class AgentRunner
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $promptDir;

    private array $agents = [
        'pm'        => ['file' => 'agent-pm.md',        'model' => 'claude-opus-4-6'],
        'backend'   => ['file' => 'agent-backend.md',   'model' => 'claude-sonnet-4-6'],
        'frontend'  => ['file' => 'agent-frontend.md',  'model' => 'claude-sonnet-4-6'],
        'qa'        => ['file' => 'agent-qa.md',         'model' => 'claude-sonnet-4-6'],
        'devops'    => ['file' => 'agent-devops.md',     'model' => 'claude-sonnet-4-6'],
        'designer'  => ['file' => 'agent-designer.md',  'model' => 'claude-opus-4-6'],
        'marketing' => ['file' => 'agent-marketing.md', 'model' => 'claude-opus-4-6'],
        'support'   => ['file' => 'agent-support.md',   'model' => 'claude-haiku-4-5-20251001'],
    ];

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
        $this->promptDir = __DIR__ . '/prompts/';
    }

    public function run(string $agentRole, string $userMessage, int $maxTokens = 4096): string
    {
        if (!isset($this->agents[$agentRole])) {
            throw new \InvalidArgumentException("Unknown agent role: {$agentRole}. Available: " . implode(', ', array_keys($this->agents)));
        }

        $agent = $this->agents[$agentRole];
        $systemPrompt = $this->loadPrompt($agent['file']);

        $payload = [
            'model'      => $agent['model'],
            'max_tokens' => $maxTokens,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        return $this->callApi($payload);
    }

    public function runWithHistory(string $agentRole, array $messages, int $maxTokens = 4096): string
    {
        if (!isset($this->agents[$agentRole])) {
            throw new \InvalidArgumentException("Unknown agent role: {$agentRole}");
        }

        $agent = $this->agents[$agentRole];
        $systemPrompt = $this->loadPrompt($agent['file']);

        $payload = [
            'model'      => $agent['model'],
            'max_tokens' => $maxTokens,
            'system'     => $systemPrompt,
            'messages'   => $messages,
        ];

        return $this->callApi($payload);
    }

    public function getAvailableAgents(): array
    {
        return array_keys($this->agents);
    }

    private function loadPrompt(string $filename): string
    {
        $path = $this->promptDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Prompt file not found: {$path}");
        }
        $content = file_get_contents($path);
        // Strip YAML frontmatter (--- ... ---)
        $content = preg_replace('/^---[\s\S]+?---\n/', '', $content);
        return trim($content);
    }

    private function callApi(array $payload): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("ANTHROPIC_API_KEY is not set.");
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("cURL error: {$curlError}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $error = $data['error']['message'] ?? $response;
            throw new \RuntimeException("API error ({$httpCode}): {$error}");
        }

        return $data['content'][0]['text'] ?? '';
    }
}
