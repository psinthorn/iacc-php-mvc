---
name: api-development
description: 'Build and maintain the iACC Sales Channel REST API. USE FOR: creating API endpoints, API key authentication, rate limiting, idempotency, webhooks, JSON responses, order processing, CORS headers. Use when: adding API routes, implementing webhook delivery, configuring rate limits, building order management, API key rotation, writing API tests.'
argument-hint: 'Describe the API endpoint or feature to build'
---

# API Development — iACC Sales Channel API

## When to Use

- Adding new REST API endpoints
- Implementing authentication & rate limiting
- Building webhook delivery
- Processing channel orders
- Writing API integration tests

## Architecture

```
api.php                              # API entry point (separate from index.php)
app/Controllers/
├── ChannelApiController.php         # Main API controller
└── AdminApiController.php           # API admin panel
app/Models/
├── ApiKey.php                       # API key management
├── ChannelOrder.php                 # Order CRUD
└── ApiSubscription.php              # Subscription plans
app/Services/
└── ChannelService.php               # Order processing business logic
```

### Request Flow

```
API Client → api.php → Parse URL → Auth (API Key) → Rate Limit Check
    → ChannelApiController → ChannelService → Model → JSON Response
```

## Procedures

### 1. Add a New API Endpoint

In `api.php`, add routing:

```php
// URL pattern: /api.php/v1/{resource}[/{id}][/{action}]
$pathParts = array_values(array_filter(explode('/', $_SERVER['PATH_INFO'] ?? '')));
$resource = $pathParts[1] ?? '';
$resourceId = $pathParts[2] ?? null;
$action = $pathParts[3] ?? null;

switch ($resource) {
    case 'orders':
        // existing
        break;
    case 'new_resource':  // Add new resource
        $controller->handleNewResource($resourceId, $action);
        break;
}
```

In the controller:

```php
public function handleNewResource(?string $id, ?string $action) {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $id ? $this->getOne($id) : $this->getList();
            break;
        case 'POST':
            $this->create();
            break;
        case 'PUT':
            $this->update($id);
            break;
        case 'DELETE':
            $this->delete($id);
            break;
    }
}
```

### 2. API Key Authentication

```php
// Request headers required:
// X-API-Key: iACC_abc123...
// X-API-Secret: def456...

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$apiSecret = $_SERVER['HTTP_X_API_SECRET'] ?? '';

// Validate
$auth = $apiKeyModel->authenticate($apiKey, $apiSecret);
if (!$auth) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API credentials']);
    exit;
}

// Key generation (cryptographically secure)
$key = 'iACC_' . bin2hex(random_bytes(24));     // 53 chars
$secret = bin2hex(random_bytes(32));              // 64 chars
```

### 3. Rate Limiting

```php
const RATE_LIMITS = [
    'trial'      => 30,
    'starter'    => 60,
    'pro'        => 120,
    'enterprise' => 300,
];

// Response headers:
header("X-RateLimit-Limit: {$limit}");
header("X-RateLimit-Remaining: {$remaining}");
header("X-RateLimit-Reset: {$resetTime}");

// On limit exceeded: HTTP 429
http_response_code(429);
header("Retry-After: {$waitSeconds}");
```

### 4. Idempotency

```php
// Client sends: X-Idempotency-Key: unique-request-id
$idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;

if ($idempotencyKey) {
    $existing = $orderModel->findByIdempotencyKey($companyId, $idempotencyKey);
    if ($existing) {
        // Return cached response — no duplicate processing
        $this->jsonSuccess($existing, 200, 'Duplicate request');
        return;
    }
}
```

### 5. JSON Response Helpers

```php
// Success response
private function jsonSuccess($data, int $code = 200, string $message = 'Success') {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

// Error response
private function jsonError(string $message, int $code = 400, $details = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'details' => $details
    ]);
}
```

### 6. Order Processing Flow

```php
// ChannelService::processOrder()
// 1. Find or create customer company
// 2. Create PR (Purchase Requisition)
// 3. Create PO (Purchase Order)
// 4. Add product line items
// 5. Link records to channel_orders
// 6. Send webhook notifications
```

### 7. Webhook Delivery

```php
// Events: order.created, order.processing, order.completed, order.failed
$payload = json_encode([
    'event' => 'order.created',
    'timestamp' => time(),
    'data' => $orderData
]);

// HMAC-SHA256 signature
$signature = hash_hmac('sha256', $payload, $webhookSecret);

// Deliver with curl
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Webhook-Signature: ' . $signature,
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
```

## CORS Headers (Required)

```php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-API-Secret, X-Idempotency-Key');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

## Existing Endpoints

| Method   | Endpoint                        | Description             |
| -------- | ------------------------------- | ----------------------- |
| `GET`    | `/api.php/v1/orders`            | List orders (paginated) |
| `GET`    | `/api.php/v1/orders/{id}`       | Get order details       |
| `POST`   | `/api.php/v1/orders`            | Create order            |
| `PUT`    | `/api.php/v1/orders/{id}`       | Update order            |
| `DELETE` | `/api.php/v1/orders/{id}`       | Cancel order            |
| `POST`   | `/api.php/v1/orders/{id}/retry` | Retry failed order      |
| `GET`    | `/api.php/v1/products`          | List products           |
| `GET`    | `/api.php/v1/categories`        | List categories         |
| `GET`    | `/api.php/v1/subscription`      | Subscription info       |
| `POST`   | `/api.php/v1/webhooks`          | Register webhook        |
| `GET`    | `/api.php/v1/webhooks`          | List webhooks           |
| `DELETE` | `/api.php/v1/webhooks/{id}`     | Delete webhook          |
