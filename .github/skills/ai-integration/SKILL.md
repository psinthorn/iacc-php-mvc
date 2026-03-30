---
name: ai-integration
description: 'AI chatbot and LLM integration for iACC. USE FOR: Ollama local AI, OpenAI API, chat streaming, agent tool execution, AI provider configuration, schema discovery, system prompts, chat handler, SSE streaming. Use when: building AI features, configuring LLM providers, adding agent tools, implementing chat interfaces, handling streaming responses, managing AI settings.'
argument-hint: 'Describe the AI feature or integration to build'
---

# AI Integration

## When to Use

- Configuring Ollama or OpenAI providers
- Building chat interfaces with streaming
- Adding new agent tools for the AI to invoke
- Creating system prompts with company context
- Implementing AI-powered features

## Architecture

```
User Message
    ↓
chat-handler.php (entry point, session + auth)
    ↓
AIProvider (selects Ollama or OpenAI)
    ├── OllamaClient (local, free, private)
    └── OpenAIClient (cloud, paid, GPT-4)
    ↓
Response → AgentExecutor (if tool call detected)
    ├── Permission check (user level + tool permissions)
    ├── Confirmation flow (write operations)
    ├── Execute tool method
    └── Audit log to agent_action_log table
    ↓
chat-stream.php (SSE for real-time token delivery)
```

**Key Files**:

| File | Purpose |
|------|---------|
| `ai/ai-provider.php` | Unified provider wrapper (Ollama/OpenAI auto-select) |
| `ai/ollama-client.php` | Ollama API client (local LLM) |
| `ai/openai-client.php` | OpenAI API client (cloud LLM) |
| `ai/chat-handler.php` | Chat session management, message routing |
| `ai/chat-stream.php` | Server-Sent Events (SSE) streaming |
| `ai/agent-executor.php` | Tool execution with permissions + audit |
| `ai/agent-tools.php` | Tool definitions (name, params, permissions) |
| `ai/schema-discovery.php` | Auto-generate DB schema for tool validation |
| `ai/config.php` | AI module configuration |
| `ai/prompts/` | System prompt templates |
| `cache/ai-settings.json` | Persisted provider settings |

## Procedures

### 1. Provider Configuration

Settings stored in `cache/ai-settings.json`:

```json
{
    "provider": "ollama",
    "ollama_url": "http://ollama:11434",
    "ollama_model": "llama3.2:3b",
    "ollama_timeout": 120,
    "openai_api_key": "sk-...",
    "openai_model": "gpt-4o-mini",
    "openai_timeout": 60,
    "temperature": 0.7,
    "max_tokens": 2048
}
```

Provider selection:
- **Ollama**: Free, local, private data stays on-premise. Good for: internal operations, data-sensitive queries. Container: `iacc_ollama` port 11434.
- **OpenAI**: Cloud API, costs per token, higher quality. Good for: complex reasoning, customer-facing features.

### 2. Using AIProvider

```php
require_once 'ai/ai-provider.php';

// Auto-select from settings
$ai = new AIProvider();

// Override provider
$ai = new AIProvider(['provider' => 'openai']);

// Generate response
$result = $ai->generate(
    'Summarize this invoice data...',     // User prompt
    'You are an accounting assistant.',    // System prompt
    ['temperature' => 0.3]                // Options override
);

if ($result['success']) {
    $text = $result['data']['text'];
    $usage = $result['data']['usage'];  // token counts
} else {
    $error = $result['error'];
}

// Check current provider info
echo $ai->getProvider();  // 'ollama' or 'openai'
echo $ai->getModel();     // 'llama3.2:3b' or 'gpt-4o-mini'
```

### 3. Defining Agent Tools

Tools are defined in `ai/agent-tools.php`:

```php
$tools = [
    [
        'name' => 'search_invoices',
        'description' => 'Search invoices by date range or customer',
        'parameters' => [
            'customer_name' => ['type' => 'string', 'required' => false],
            'date_from' => ['type' => 'string', 'format' => 'Y-m-d', 'required' => false],
            'date_to' => ['type' => 'string', 'format' => 'Y-m-d', 'required' => false],
        ],
        'permissions' => ['min_level' => 0],  // All users
        'confirm' => false,                    // Read-only, no confirmation needed
    ],
    [
        'name' => 'create_invoice',
        'description' => 'Create a new invoice from AI conversation',
        'parameters' => [
            'customer_id' => ['type' => 'integer', 'required' => true],
            'items' => ['type' => 'array', 'required' => true],
        ],
        'permissions' => ['min_level' => 0],
        'confirm' => true,  // Write operation, requires user confirmation
    ],
];
```

### 4. Agent Execution Flow

```php
require_once 'ai/agent-executor.php';

$executor = new AgentExecutor(
    $db,                         // Database connection
    $_SESSION['com_id'],         // Company ID (multi-tenant)
    $_SESSION['user_id'],        // User ID
    $chatSessionId,              // Chat session for audit trail
    $config                      // Optional config overrides
);

$result = $executor->execute('search_invoices', [
    'customer_name' => 'Acme Corp',
    'date_from' => '2026-01-01'
], $confirmed = false);

// Result structure:
// Success: ['success' => true, 'tool' => 'search_invoices', 'result' => [...]]
// Error: ['success' => false, 'error' => 'Permission denied']
// Needs confirm: ['success' => false, 'needs_confirmation' => true, 'tool' => '...', 'params' => [...]]
```

Security enforcement:
1. Tool exists in registry → otherwise rejected
2. User level meets `permissions.min_level` → otherwise "Permission denied"
3. Write operations check `confirm` flag → return confirmation prompt
4. All actions logged to `agent_action_log` (tool, params, result, user, company, timestamp)
5. Company ID enforced on all queries inside tools

### 5. Streaming Responses (SSE)

```php
// chat-stream.php sends Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Stream each token as it arrives
function streamToken($token) {
    echo "data: " . json_encode(['token' => $token]) . "\n\n";
    ob_flush();
    flush();
}

// End stream
function streamEnd($fullResponse) {
    echo "data: " . json_encode(['done' => true, 'full' => $fullResponse]) . "\n\n";
    ob_flush();
    flush();
}

// Client-side (JavaScript):
// const source = new EventSource('ai/chat-stream.php?session=' + sessionId);
// source.onmessage = (e) => { const data = JSON.parse(e.data); ... };
```

### 6. Schema Discovery

Auto-generates database schema for AI tool validation:

```php
require_once 'ai/schema-discovery.php';

$discovery = new SchemaDiscovery($conn);

// Full schema (cached to cache/db-schema.json)
$schema = $discovery->discoverDatabaseSchema();

// Compact format for prompts (cache/db-schema-compact.txt)
$compact = $discovery->getCompactSchema();
// Output: "po(id,po_number,date,total,company_id) product(id,name,price,po_id)..."
```

Used in system prompts so the AI understands available tables/columns.

### 7. System Prompts with Context

Templates in `ai/prompts/` inject company-specific context:

```php
$systemPrompt = "You are an AI assistant for {company_name} (ID: {company_id}).
You help with invoicing, quotations, and business operations.
Current user: {user_name} (level: {user_level}).
Available tables: {schema_compact}
Language preference: {lang}

Rules:
- Always filter queries by company_id = {company_id}
- Never show data from other companies
- Confirm before creating or modifying records
- Respond in {lang} language";
```

## Configuration

### Docker (Ollama)

```yaml
# docker-compose.yml
ollama:
    image: ollama/ollama
    container_name: iacc_ollama
    ports:
      - "11434:11434"
    volumes:
      - ollama_models:/root/.ollama
    deploy:
      resources:
        limits:
          memory: 8G
```

### Environment Variables

```bash
# .env or docker environment
OLLAMA_URL=http://ollama:11434     # Internal Docker network
OPENAI_API_KEY=sk-...              # OpenAI API key (prod only)
AI_PROVIDER=ollama                  # Default provider
AI_MAX_TOKENS=2048                  # Response limit
```
