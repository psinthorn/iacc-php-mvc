<?php
/**
 * iACC Template — API Client
 * Handles all communication with the iACC Sales Channel API.
 * Uses the existing /api.php/v1/* endpoints.
 */

class IaccApiClient
{
    private string $apiUrl;
    private string $apiKey;
    private string $apiSecret;
    private ?string $lastError = null;

    public function __construct(string $apiUrl, string $apiKey, string $apiSecret)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Test API connection — calls GET /api.php/v1 (root info endpoint)
     */
    public function testConnection(): array
    {
        return $this->request('GET', '/api.php/v1');
    }

    /**
     * Get subscription info
     */
    public function getSubscription(): array
    {
        return $this->request('GET', '/api.php/v1/subscription');
    }

    /**
     * Fetch all products (models) for the authenticated company
     */
    public function getProducts(?int $categoryId = null): array
    {
        $query = $categoryId ? "?category_id=$categoryId" : '';
        return $this->request('GET', '/api.php/v1/products' . $query);
    }

    /**
     * Fetch all categories with types
     */
    public function getCategories(): array
    {
        return $this->request('GET', '/api.php/v1/categories');
    }

    /**
     * Create an order (booking) — goes through the full pipeline:
     * channel_orders → Customer → PR → PO → Product
     */
    public function createOrder(array $data): array
    {
        return $this->request('POST', '/api.php/v1/orders', $data);
    }

    /**
     * Get order status
     */
    public function getOrder(int $orderId): array
    {
        return $this->request('GET', "/api.php/v1/orders/$orderId");
    }

    /**
     * List orders
     */
    public function listOrders(int $page = 1, int $perPage = 15): array
    {
        return $this->request('GET', "/api.php/v1/orders?page=$page&per_page=$perPage");
    }

    /**
     * Make HTTP request to iACC API
     */
    private function request(string $method, string $endpoint, ?array $body = null): array
    {
        $url = $this->apiUrl . $endpoint;
        $this->lastError = null;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'X-API-Secret: ' . $this->apiSecret,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->lastError = "Connection failed: $curlError";
            return ['success' => false, 'error' => ['message' => $this->lastError]];
        }

        $data = json_decode($response, true);
        if (!$data) {
            $this->lastError = "Invalid response (HTTP $httpCode)";
            return ['success' => false, 'error' => ['message' => $this->lastError]];
        }

        if (!($data['success'] ?? false)) {
            $this->lastError = $data['error']['message'] ?? 'Unknown API error';
        }

        return $data;
    }
}
