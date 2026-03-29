<?php
namespace App\Controllers;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\ChannelOrder;
use App\Models\Subscription;
use App\Models\Webhook;
use App\Services\ChannelService;

/**
 * ChannelApiController — REST API endpoint for channel orders
 * 
 * Called from api.php (not index.php).
 * All responses are JSON. No session/cookie auth — uses API key + secret.
 * 
 * Endpoints:
 *   POST   /api/v1/orders          — Create a new order
 *   GET    /api/v1/orders/:id      — Get order status
 *   GET    /api/v1/orders          — List orders
 *   DELETE /api/v1/orders/:id      — Cancel an order
 *   GET    /api/v1/subscription      — Get subscription info & usage
 */
class ChannelApiController
{
    private \mysqli $conn;
    private ApiKey $apiKeyModel;
    private ChannelOrder $orderModel;
    private Subscription $subscriptionModel;
    private ApiUsageLog $usageLog;
    private Webhook $webhookModel;
    private ?array $authData = null;
    private float $startTime;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->apiKeyModel = new ApiKey();
        $this->orderModel = new ChannelOrder();
        $this->subscriptionModel = new Subscription();
        $this->usageLog = new ApiUsageLog();
        $this->webhookModel = new Webhook();
        $this->startTime = microtime(true);
    }

    /**
     * Main router — called from api.php
     */
    public function handleRequest(string $method, string $path, ?int $resourceId = null, ?string $subAction = null): void
    {
        try {
            // Authenticate
            $this->authData = $this->authenticate();

            // Rate limiting — check requests per minute
            $this->checkRateLimit();

            // Route to handler
            switch (true) {
                case $method === 'POST' && $path === 'orders' && $resourceId && $subAction === 'retry':
                    $this->retryOrder($resourceId);
                    break;

                case $method === 'POST' && $path === 'orders':
                    $this->createOrder();
                    break;

                case $method === 'GET' && $path === 'orders' && $resourceId:
                    $this->getOrder($resourceId);
                    break;

                case $method === 'GET' && $path === 'orders':
                    $this->listOrders();
                    break;

                case $method === 'PUT' && $path === 'orders' && $resourceId:
                    $this->updateOrder($resourceId);
                    break;

                case $method === 'DELETE' && $path === 'orders' && $resourceId:
                    $this->cancelOrder($resourceId);
                    break;

                case $method === 'GET' && $path === 'subscription':
                    $this->getSubscription();
                    break;

                // Product catalog endpoints
                case $method === 'GET' && $path === 'products':
                    $this->listProducts();
                    break;

                case $method === 'GET' && $path === 'categories':
                    $this->listCategories();
                    break;

                // Webhook endpoints
                case $method === 'POST' && $path === 'webhooks':
                    $this->registerWebhook();
                    break;

                case $method === 'GET' && $path === 'webhooks':
                    $this->listWebhooks();
                    break;

                case $method === 'DELETE' && $path === 'webhooks' && $resourceId:
                    $this->deleteWebhook($resourceId);
                    break;

                case $method === 'PUT' && $path === 'webhooks' && $resourceId:
                    $this->updateWebhookEndpoint($resourceId);
                    break;

                case $method === 'POST' && $path === 'webhooks' && $resourceId && $subAction === 'test':
                    $this->testWebhook($resourceId);
                    break;

                default:
                    $this->jsonError('Not Found', 404, 'ENDPOINT_NOT_FOUND');
            }
        } catch (\Exception $e) {
            $this->jsonError($e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    // =========================================================
    // Endpoints
    // =========================================================

    /**
     * POST /api/v1/orders — Create a new order
     */
    private function createOrder(): void
    {
        $input = $this->getJsonInput();
        $companyId = intval($this->authData['company_id']);

        // Idempotency check
        $idempotencyKey = $_SERVER['HTTP_X_IDEMPOTENCY_KEY'] ?? null;
        if ($idempotencyKey) {
            $existing = $this->orderModel->findByIdempotencyKey($companyId, $idempotencyKey);
            if ($existing) {
                $this->logRequest('POST /api/v1/orders', $existing['channel'], 200, $input);
                $this->jsonSuccess($this->formatOrderResponse($existing), 200, 'Duplicate request — returning existing order');
                return;
            }
        }

        // Validate required fields
        $errors = $this->validateOrderInput($input);
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 422, 'VALIDATION_ERROR', $errors);
            return;
        }

        // Check subscription quota
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        if (!$this->subscriptionModel->hasQuota($companyId, $subscription)) {
            $this->jsonError(
                'Monthly order quota exceeded. Current limit: ' . $subscription['orders_limit'],
                429, 'QUOTA_EXCEEDED'
            );
            return;
        }

        // Check channel allowed
        $channel = $input['channel'] ?? 'website';
        if (!$this->subscriptionModel->isChannelAllowed($subscription, $channel)) {
            $this->jsonError(
                "Channel '$channel' is not available on your plan. Allowed: {$subscription['channels']}",
                403, 'CHANNEL_NOT_ALLOWED'
            );
            return;
        }

        // Create order record
        $orderData = [
            'company_id'  => $companyId,
            'api_key_id'  => intval($this->authData['id']),
            'channel'     => $channel,
            'status'      => 'pending',
            'guest_name'  => $input['guest_name'],
            'guest_email' => $input['guest_email'] ?? null,
            'guest_phone' => $input['guest_phone'] ?? null,
            'check_in'    => $input['check_in'] ?? null,
            'check_out'   => $input['check_out'] ?? null,
            'room_type'   => $input['room_type'] ?? null,
            'guests'      => intval($input['guests'] ?? 1),
            'total_amount' => floatval($input['total_amount'] ?? 0),
            'currency'    => $input['currency'] ?? 'THB',
            'notes'       => $input['notes'] ?? null,
            'raw_data'    => json_encode($input),
            'idempotency_key' => $idempotencyKey,
        ];

        $orderId = $this->orderModel->createOrder($orderData);
        if (!$orderId) {
            $this->jsonError('Failed to create order', 500, 'CREATE_FAILED');
            return;
        }

        $this->fireWebhook('order.created', [
            'order_id' => $orderId,
            'guest_name' => $orderData['guest_name'],
            'channel' => $orderData['channel'],
            'status' => 'pending',
        ]);

        // Process order (create Company → PR → PO → Products)
        $service = new ChannelService();
        $result = $service->processOrder($orderId, $orderData, $this->authData);

        if ($result['success']) {
            $this->logRequest('POST /api/v1/orders', $channel, 201, $input, $result['data']);
            $this->fireWebhook('order.completed', $result['data']);
            $this->jsonSuccess($result['data'], 201, 'Order created and processed successfully');
        } else {
            $this->logRequest('POST /api/v1/orders', $channel, 500, $input, ['error' => $result['error']]);
            $this->fireWebhook('order.failed', ['order_id' => $orderId, 'error' => $result['error']]);
            // Order was created but processing failed — return order ID for retry
            $this->jsonSuccess([
                'order_id' => $orderId,
                'status'     => 'failed',
                'error'      => $result['error'],
            ], 202, 'Order created but processing failed. You can retry later.');
        }
    }

    /**
     * GET /api/v1/orders/:id — Get order status
     */
    private function getOrder(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $order = $this->orderModel->findForCompany($id, $companyId);

        if (!$order) {
            $this->jsonError('Order not found', 404, 'NOT_FOUND');
            return;
        }

        $this->logRequest("GET /api/v1/orders/$id", $order['channel'], 200);
        $this->jsonSuccess($this->formatOrderResponse($order));
    }

    /**
     * GET /api/v1/orders — List orders with filters
     */
    private function listOrders(): void
    {
        $companyId = intval($this->authData['company_id']);
        
        $filters = [
            'status'    => $_GET['status'] ?? '',
            'channel'   => $_GET['channel'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
            'search'    => $_GET['search'] ?? '',
        ];
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(100, max(1, intval($_GET['per_page'] ?? 15)));

        $result = $this->orderModel->getForCompany($companyId, $filters, $page, $perPage);

        $this->logRequest('GET /api/v1/orders', '', 200);
        $this->jsonSuccess([
            'orders'   => array_map([$this, 'formatOrderResponse'], $result['items']),
            'total'      => $result['total'],
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => $result['pagination']['total_pages'] ?? ceil($result['total'] / $perPage),
        ]);
    }

    /**
     * DELETE /api/v1/orders/:id — Cancel an order
     */
    private function cancelOrder(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $order = $this->orderModel->findForCompany($id, $companyId);

        if (!$order) {
            $this->jsonError('Order not found', 404, 'NOT_FOUND');
            return;
        }

        if ($order['status'] === 'cancelled') {
            $this->jsonError('Order is already cancelled', 409, 'ALREADY_CANCELLED');
            return;
        }

        $this->orderModel->updateStatus($id, 'cancelled');
        
        $this->logRequest("DELETE /api/v1/orders/$id", $order['channel'], 200);
        $this->fireWebhook('order.cancelled', ['order_id' => $id, 'guest_name' => $order['guest_name']]);
        $this->jsonSuccess(['order_id' => $id, 'status' => 'cancelled'], 200, 'Order cancelled');
    }

    /**
     * PUT /api/v1/orders/:id — Update an order
     * Only pending or processing orders can be updated.
     */
    private function updateOrder(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $order = $this->orderModel->findForCompany($id, $companyId);

        if (!$order) {
            $this->jsonError('Order not found', 404, 'NOT_FOUND');
            return;
        }

        if (!in_array($order['status'], ['pending', 'processing'])) {
            $this->jsonError('Only pending or processing orders can be updated', 409, 'INVALID_STATUS');
            return;
        }

        $input = $this->getJsonInput();

        // Validate any provided fields
        $errors = [];
        if (!empty($input['guest_email']) && !filter_var($input['guest_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'guest_email is not a valid email address';
        }
        if (!empty($input['check_in']) && !$this->isValidDate($input['check_in'])) {
            $errors[] = 'check_in must be a valid date (YYYY-MM-DD)';
        }
        if (!empty($input['check_out']) && !$this->isValidDate($input['check_out'])) {
            $errors[] = 'check_out must be a valid date (YYYY-MM-DD)';
        }
        $checkIn = $input['check_in'] ?? $order['check_in'];
        $checkOut = $input['check_out'] ?? $order['check_out'];
        if ($checkIn && $checkOut && strtotime($checkOut) <= strtotime($checkIn)) {
            $errors[] = 'check_out must be after check_in';
        }
        if (isset($input['total_amount']) && (!is_numeric($input['total_amount']) || $input['total_amount'] < 0)) {
            $errors[] = 'total_amount must be a positive number';
        }
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 422, 'VALIDATION_ERROR', $errors);
            return;
        }

        // Allowlisted fields for update
        $allowedFields = ['guest_name', 'guest_email', 'guest_phone', 'check_in', 'check_out',
                          'room_type', 'guests', 'total_amount', 'currency', 'notes'];
        $updates = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $updates[$field] = $input[$field];
            }
        }

        if (empty($updates)) {
            $this->jsonError('No valid fields to update', 400, 'NO_CHANGES');
            return;
        }

        // Update via model (safe prepared statements)
        $updates['updated_at'] = date('Y-m-d H:i:s');
        $this->orderModel->updateFields($id, $updates);

        // Return updated order
        $updated = $this->orderModel->findForCompany($id, $companyId);
        $this->logRequest("PUT /api/v1/orders/$id", $updated['channel'], 200);
        $this->fireWebhook('order.updated', $this->formatOrderResponse($updated));
        $this->jsonSuccess(['order' => $this->formatOrderResponse($updated)], 200, 'Order updated');
    }

    /**
     * POST /api/v1/orders/:id/retry — Retry processing a failed order
     */
    private function retryOrder(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $order = $this->orderModel->findForCompany($id, $companyId);

        if (!$order) {
            $this->jsonError('Order not found', 404, 'NOT_FOUND');
            return;
        }

        if ($order['status'] !== 'failed') {
            $this->jsonError('Only failed orders can be retried', 409, 'INVALID_STATUS');
            return;
        }

        // Reset to pending and re-process
        $this->orderModel->updateStatus($id, 'pending');

        $service = new ChannelService();
        $result = $service->processOrder($id, $order, $this->authData);

        // Re-fetch after processing
        $updated = $this->orderModel->findForCompany($id, $companyId);
        $statusCode = ($updated['status'] === 'completed') ? 200 : 207;

        $this->logRequest("POST /api/v1/orders/$id/retry", $order['channel'], $statusCode);
        $eventName = ($updated['status'] === 'completed') ? 'order.completed' : 'order.failed';
        $this->fireWebhook($eventName, $this->formatOrderResponse($updated));
        $this->jsonSuccess([
            'order'          => $this->formatOrderResponse($updated),
            'processing_result' => $result,
        ], $statusCode, $updated['status'] === 'completed' ? 'Order retried successfully' : 'Order retry attempted');
    }

    /**
     * GET /api/v1/subscription — Get subscription info & usage stats
     */
    private function getSubscription(): void
    {
        $companyId = intval($this->authData['company_id']);
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $usage = $this->subscriptionModel->getMonthlyUsage($companyId);
        $stats = $this->orderModel->getStats($companyId);

        $this->logRequest('GET /api/v1/subscription', '', 200);
        $this->jsonSuccess([
            'plan'            => $subscription['plan'],
            'status'          => $subscription['status'],
            'orders_limit'  => intval($subscription['orders_limit']),
            'orders_used'   => $usage,
            'orders_remaining' => max(0, intval($subscription['orders_limit']) - $usage),
            'channels'        => explode(',', $subscription['channels']),
            'ai_providers'    => explode(',', $subscription['ai_providers']),
            'keys_limit'      => intval($subscription['keys_limit']),
            'trial_end'       => $subscription['trial_end'],
            'expires_at'      => $subscription['expires_at'],
            'stats'           => $stats,
        ]);
    }

    // =========================================================
    // Product Catalog Endpoints
    // =========================================================

    /**
     * GET /api/v1/products — List all products (models) for the authenticated company
     * Query params: ?category_id=X&type_id=X
     */
    private function listProducts(): void
    {
        $companyId = intval($this->authData['company_id']);

        $sql = "SELECT m.id, m.model_name, m.price, m.des as description,
                       t.id as type_id, t.name as type_name,
                       c.id as category_id, c.cat_name as category_name,
                       b.id as brand_id, b.brand_name
                FROM model m
                LEFT JOIN type t ON m.type_id = t.id
                LEFT JOIN category c ON t.cat_id = c.id
                LEFT JOIN brand b ON m.brand_id = b.id
                WHERE m.company_id = ? AND m.deleted_at IS NULL
                ORDER BY c.cat_name, t.name, m.model_name";

        // Optional filters
        $params = [$companyId];
        $types = 'i';

        if (!empty($_GET['category_id'])) {
            $sql = str_replace('ORDER BY', 'AND c.id = ? ORDER BY', $sql);
            $params[] = intval($_GET['category_id']);
            $types .= 'i';
        }
        if (!empty($_GET['type_id'])) {
            $sql = str_replace('ORDER BY', 'AND t.id = ? ORDER BY', $sql);
            $params[] = intval($_GET['type_id']);
            $types .= 'i';
        }

        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = [
                'id'            => intval($row['id']),
                'name'          => $row['model_name'],
                'price'         => floatval($row['price']),
                'description'   => $row['description'],
                'type_id'       => intval($row['type_id']),
                'type_name'     => $row['type_name'],
                'category_id'   => intval($row['category_id']),
                'category_name' => $row['category_name'],
                'brand_id'      => intval($row['brand_id']),
                'brand_name'    => $row['brand_name'],
            ];
        }
        mysqli_stmt_close($stmt);

        $this->logRequest('GET /api/v1/products', '', 200);
        $this->jsonSuccess([
            'products' => $products,
            'total'    => count($products),
        ]);
    }

    /**
     * GET /api/v1/categories — List all categories with types for the authenticated company
     */
    private function listCategories(): void
    {
        $companyId = intval($this->authData['company_id']);

        // Get categories
        $sql = "SELECT c.id, c.cat_name as name, c.des as description
                FROM category c
                WHERE c.company_id = ? AND c.deleted_at IS NULL
                ORDER BY c.cat_name";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $catResult = mysqli_stmt_get_result($stmt);

        $categories = [];
        while ($row = mysqli_fetch_assoc($catResult)) {
            $categories[] = [
                'id'          => intval($row['id']),
                'name'        => $row['name'],
                'description' => $row['description'],
                'types'       => [],
            ];
        }
        mysqli_stmt_close($stmt);

        // Get types for each category
        $sql = "SELECT t.id, t.name, t.des as description, t.cat_id,
                       (SELECT COUNT(*) FROM model m WHERE m.type_id = t.id AND m.company_id = ? AND m.deleted_at IS NULL) as product_count
                FROM type t
                WHERE t.company_id = ? AND t.deleted_at IS NULL
                ORDER BY t.name";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $companyId, $companyId);
        mysqli_stmt_execute($stmt);
        $typeResult = mysqli_stmt_get_result($stmt);

        $typesByCategory = [];
        while ($row = mysqli_fetch_assoc($typeResult)) {
            $catId = intval($row['cat_id']);
            $typesByCategory[$catId][] = [
                'id'            => intval($row['id']),
                'name'          => $row['name'],
                'description'   => $row['description'],
                'product_count' => intval($row['product_count']),
            ];
        }
        mysqli_stmt_close($stmt);

        // Merge types into categories
        foreach ($categories as &$cat) {
            $cat['types'] = $typesByCategory[$cat['id']] ?? [];
        }

        $this->logRequest('GET /api/v1/categories', '', 200);
        $this->jsonSuccess([
            'categories' => $categories,
            'total'      => count($categories),
        ]);
    }

    // =========================================================
    // Authentication
    // =========================================================

    /**
     * Authenticate via X-API-Key + X-API-Secret headers
     */
    private function authenticate(): array
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $apiSecret = $_SERVER['HTTP_X_API_SECRET'] ?? '';

        if (empty($apiKey) || empty($apiSecret)) {
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 401);
            $this->jsonError('Missing API credentials. Send X-API-Key and X-API-Secret headers.', 401, 'AUTH_MISSING');
            exit;
        }

        $authData = $this->apiKeyModel->authenticate($apiKey, $apiSecret);
        if (!$authData) {
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 401);
            $this->jsonError('Invalid API credentials', 401, 'AUTH_INVALID');
            exit;
        }

        // Check subscription is active and enabled
        if ($authData['sub_status'] !== 'active' || !$authData['enabled']) {
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 403);
            $this->jsonError('API subscription is not active', 403, 'SUBSCRIPTION_INACTIVE');
            exit;
        }

        // Check subscription expiry (trial_end or expires_at from JOIN)
        $now = date('Y-m-d');
        if (!empty($authData['trial_end']) && $now > $authData['trial_end']) {
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 403);
            $this->jsonError('API trial has expired', 403, 'SUBSCRIPTION_EXPIRED');
            exit;
        }
        if (!empty($authData['expires_at']) && $now > substr($authData['expires_at'], 0, 10)) {
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 403);
            $this->jsonError('API subscription has expired', 403, 'SUBSCRIPTION_EXPIRED');
            exit;
        }

        return $authData;
    }

    /**
     * Check rate limiting — requests per minute based on plan
     * trial=30, starter=60, professional=120, enterprise=300
     * Test keys (key_name contains "Test") get 10x limit for E2E testing
     */
    private function checkRateLimit(): void
    {
        $planLimits = [
            'trial'        => 30,
            'starter'      => 60,
            'professional' => 120,
            'enterprise'   => 300,
        ];

        $plan = $this->authData['plan'] ?? 'trial';
        $limit = $planLimits[$plan] ?? 30;
        $keyId = intval($this->authData['id']);

        // Test/E2E keys get 10x rate limit to allow repeated test runs
        $keyName = $this->authData['key_name'] ?? '';
        if (stripos($keyName, 'test') !== false) {
            $limit *= 10;
        }

        $recentCount = $this->usageLog->countRecentRequests($keyId, 60);
        if ($recentCount >= $limit) {
            $retryAfter = 60;
            header("Retry-After: $retryAfter");
            header("X-RateLimit-Limit: $limit");
            header("X-RateLimit-Remaining: 0");
            $this->logRequest($_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? ''), '', 429);
            $this->jsonError("Rate limit exceeded. Maximum $limit requests per minute for $plan plan.", 429, 'RATE_LIMIT_EXCEEDED');
            exit;
        }

        // Set rate limit headers on all responses
        header("X-RateLimit-Limit: $limit");
        header("X-RateLimit-Remaining: " . max(0, $limit - $recentCount - 1));
    }

    // =========================================================
    // Webhook Endpoints
    // =========================================================

    /**
     * POST /api/v1/webhooks — Register a new webhook
     */
    private function registerWebhook(): void
    {
        $input = $this->getJsonInput();
        $companyId = intval($this->authData['company_id']);

        if (empty($input['url']) || !filter_var($input['url'], FILTER_VALIDATE_URL)) {
            $this->jsonError('A valid HTTPS URL is required', 422, 'VALIDATION_ERROR');
            return;
        }

        // Must be HTTPS in production
        if (strpos($input['url'], 'https://') !== 0 && strpos($input['url'], 'http://localhost') !== 0 && strpos($input['url'], 'http://127.0.0.1') !== 0) {
            $this->jsonError('Webhook URL must use HTTPS', 422, 'VALIDATION_ERROR');
            return;
        }

        // Limit webhooks per company
        $count = $this->webhookModel->countForCompany($companyId);
        if ($count >= 5) {
            $this->jsonError('Maximum 5 webhooks per company', 409, 'WEBHOOK_LIMIT');
            return;
        }

        $validEvents = ['order.created', 'order.completed', 'order.failed', 'order.cancelled', 'order.updated'];
        $events = $input['events'] ?? $validEvents;
        if (!is_array($events)) {
            $this->jsonError('events must be an array', 422, 'VALIDATION_ERROR');
            return;
        }
        $events = array_intersect($events, $validEvents);
        if (empty($events)) {
            $this->jsonError('At least one valid event is required: ' . implode(', ', $validEvents), 422, 'VALIDATION_ERROR');
            return;
        }

        $result = $this->webhookModel->createWebhook($companyId, $input['url'], $events);
        if (!$result) {
            $this->jsonError('Failed to create webhook', 500, 'CREATE_FAILED');
            return;
        }

        $this->logRequest('POST /api/v1/webhooks', '', 201);
        $this->jsonSuccess([
            'webhook_id' => $result['id'],
            'url'        => $result['url'],
            'secret'     => $result['secret'],
            'events'     => $result['events'],
            'note'       => 'Save the secret — it will not be shown again. Use it to verify webhook signatures (HMAC-SHA256).',
        ], 201, 'Webhook registered');
    }

    /**
     * GET /api/v1/webhooks — List all webhooks (with pagination)
     */
    private function listWebhooks(): void
    {
        $companyId = intval($this->authData['company_id']);
        $webhooks = $this->webhookModel->getByCompanyId($companyId);

        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, max(1, intval($_GET['per_page'] ?? 20)));
        $total = count($webhooks);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($webhooks, $offset, $perPage);

        $formatted = array_map(function ($w) {
            return [
                'id'            => intval($w['id']),
                'url'           => $w['url'],
                'events'        => explode(',', $w['events']),
                'is_active'     => (bool)$w['is_active'],
                'failure_count' => intval($w['failure_count']),
                'last_triggered' => $w['last_triggered'],
                'last_status'   => $w['last_status'] ? intval($w['last_status']) : null,
                'created_at'    => $w['created_at'],
            ];
        }, $paged);

        $this->logRequest('GET /api/v1/webhooks', '', 200);
        $this->jsonSuccess([
            'webhooks'   => $formatted,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]);
    }

    /**
     * DELETE /api/v1/webhooks/:id — Delete a webhook
     */
    private function deleteWebhook(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $webhook = $this->webhookModel->findForCompany($id, $companyId);

        if (!$webhook) {
            $this->jsonError('Webhook not found', 404, 'NOT_FOUND');
            return;
        }

        $this->webhookModel->deleteWebhook($id);
        $this->logRequest("DELETE /api/v1/webhooks/$id", '', 200);
        $this->jsonSuccess(['webhook_id' => $id], 200, 'Webhook deleted');
    }

    /**
     * PUT /api/v1/webhooks/:id — Update a webhook's URL or events
     */
    private function updateWebhookEndpoint(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $webhook = $this->webhookModel->findForCompany($id, $companyId);

        if (!$webhook) {
            $this->jsonError('Webhook not found', 404, 'NOT_FOUND');
            return;
        }

        $input = $this->getJsonInput();
        $updateData = [];

        // Update URL if provided
        if (isset($input['url'])) {
            if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
                $this->jsonError('A valid URL is required', 422, 'VALIDATION_ERROR');
                return;
            }
            if (strpos($input['url'], 'https://') !== 0 && strpos($input['url'], 'http://localhost') !== 0 && strpos($input['url'], 'http://127.0.0.1') !== 0) {
                $this->jsonError('Webhook URL must use HTTPS', 422, 'VALIDATION_ERROR');
                return;
            }
            $updateData['url'] = $input['url'];
        }

        // Update events if provided
        if (isset($input['events'])) {
            if (!is_array($input['events'])) {
                $this->jsonError('events must be an array', 422, 'VALIDATION_ERROR');
                return;
            }
            $validEvents = ['order.created', 'order.completed', 'order.failed', 'order.cancelled', 'order.updated'];
            $events = array_intersect($input['events'], $validEvents);
            if (empty($events)) {
                $this->jsonError('At least one valid event is required: ' . implode(', ', $validEvents), 422, 'VALIDATION_ERROR');
                return;
            }
            $updateData['events'] = implode(',', $events);
        }

        if (empty($updateData)) {
            $this->jsonError('No valid fields to update. Provide url and/or events.', 422, 'VALIDATION_ERROR');
            return;
        }

        $this->webhookModel->updateWebhook($id, $updateData);

        $updated = $this->webhookModel->findForCompany($id, $companyId);

        $this->logRequest("PUT /api/v1/webhooks/$id", '', 200);
        $this->jsonSuccess([
            'id'        => intval($updated['id']),
            'url'       => $updated['url'],
            'events'    => explode(',', $updated['events']),
            'is_active' => (bool)$updated['is_active'],
        ], 200, 'Webhook updated');
    }

    /**
     * POST /api/v1/webhooks/:id/test — Send a test ping to a webhook
     */
    private function testWebhook(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $webhook = $this->webhookModel->findForCompany($id, $companyId);

        if (!$webhook) {
            $this->jsonError('Webhook not found', 404, 'NOT_FOUND');
            return;
        }

        if (!$webhook['is_active']) {
            $this->jsonError('Webhook is disabled. Enable it before testing.', 409, 'WEBHOOK_DISABLED');
            return;
        }

        // Build test payload
        $testPayload = [
            'event'     => 'webhook.test',
            'timestamp' => date('c'),
            'data'      => [
                'message'    => 'This is a test ping from iACC Sales Channel API',
                'webhook_id' => intval($webhook['id']),
                'test'       => true,
            ],
        ];

        $body = json_encode($testPayload, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $webhook['secret']);

        $startTime = microtime(true);
        $error = null;
        $responseCode = null;
        $responseBody = null;

        try {
            $ch = curl_init($webhook['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Webhook-Event: webhook.test',
                    'X-Webhook-Signature: sha256=' . $signature,
                    'X-Webhook-Id: ' . $webhook['id'],
                    'User-Agent: iACC-Webhook/1.0',
                ],
            ]);

            $responseBody = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
            }

            curl_close($ch);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $durationMs = intval((microtime(true) - $startTime) * 1000);
        $success = $responseCode >= 200 && $responseCode < 300 && !$error;

        $this->logRequest("POST /api/v1/webhooks/$id/test", '', 200);
        $this->jsonSuccess([
            'webhook_id'  => intval($webhook['id']),
            'url'         => $webhook['url'],
            'test_result' => [
                'success'     => $success,
                'status_code' => $responseCode,
                'duration_ms' => $durationMs,
                'error'       => $error,
            ],
        ], 200, $success ? 'Webhook test successful' : 'Webhook test failed');
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->jsonError('Invalid JSON in request body', 400, 'INVALID_JSON');
            exit;
        }
        return $data;
    }

    private function validateOrderInput(array $input): array
    {
        $errors = [];

        if (empty($input['guest_name'])) {
            $errors[] = 'guest_name is required';
        }

        if (!empty($input['guest_email']) && !filter_var($input['guest_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'guest_email is not a valid email address';
        }

        if (!empty($input['check_in']) && !$this->isValidDate($input['check_in'])) {
            $errors[] = 'check_in must be a valid date (YYYY-MM-DD)';
        }

        if (!empty($input['check_out']) && !$this->isValidDate($input['check_out'])) {
            $errors[] = 'check_out must be a valid date (YYYY-MM-DD)';
        }

        if (!empty($input['check_in']) && !empty($input['check_out'])) {
            if (strtotime($input['check_out']) <= strtotime($input['check_in'])) {
                $errors[] = 'check_out must be after check_in';
            }
        }

        if (isset($input['total_amount']) && (!is_numeric($input['total_amount']) || $input['total_amount'] < 0)) {
            $errors[] = 'total_amount must be a positive number';
        }

        $validChannels = ['website', 'email', 'line', 'facebook', 'manual'];
        if (!empty($input['channel']) && !in_array($input['channel'], $validChannels)) {
            $errors[] = 'channel must be one of: ' . implode(', ', $validChannels);
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function formatOrderResponse(array $order): array
    {
        $formatted = [
            'id'              => intval($order['id']),
            'order_id'      => intval($order['id']),
            'status'          => $order['status'],
            'channel'         => $order['channel'],
            'guest_name'      => $order['guest_name'],
            'guest_email'     => $order['guest_email'],
            'guest_phone'     => $order['guest_phone'],
            'check_in'        => $order['check_in'],
            'check_out'       => $order['check_out'],
            'room_type'       => $order['room_type'],
            'guests'          => intval($order['guests']),
            'total_amount'    => floatval($order['total_amount'] ?? 0),
            'currency'        => $order['currency'],
            'notes'           => $order['notes'],
            'linked_pr_id'    => $order['linked_pr_id'] ? intval($order['linked_pr_id']) : null,
            'linked_po_id'    => $order['linked_po_id'] ? intval($order['linked_po_id']) : null,
            'created_at'      => $order['created_at'],
            'processed_at'    => $order['processed_at'],
        ];
        if (!empty($order['idempotency_key'])) {
            $formatted['idempotency_key'] = $order['idempotency_key'];
        }
        return $formatted;
    }

    /**
     * Fire webhooks for an order event (non-blocking best-effort)
     */
    private function fireWebhook(string $event, array $payload): void
    {
        if (!$this->authData) return;
        $companyId = intval($this->authData['company_id']);
        try {
            $this->webhookModel->fireEvent($companyId, $event, $payload);
        } catch (\Exception $e) {
            // Webhook delivery is best-effort — never fail the API response
            error_log("Webhook fire failed for event $event: " . $e->getMessage());
        }
    }

    private function logRequest(string $endpoint, string $channel, int $statusCode, ?array $request = null, ?array $response = null): void
    {
        $elapsed = intval((microtime(true) - $this->startTime) * 1000);

        $this->usageLog->logRequest([
            'company_id'    => $this->authData ? intval($this->authData['company_id']) : null,
            'api_key_id'    => $this->authData ? intval($this->authData['id']) : null,
            'endpoint'      => $endpoint,
            'channel'       => $channel ?: 'website',
            'status_code'   => $statusCode,
            'request_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'request_body'  => $request ? json_encode($request) : null,
            'response_body' => $response ? json_encode($response) : null,
            'processing_ms' => $elapsed,
        ]);
    }

    private function jsonSuccess($data, int $status = 200, string $message = 'Success'): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $status = 400, string $code = 'ERROR', array $details = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
        if (!empty($details)) {
            $response['error']['details'] = $details;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
