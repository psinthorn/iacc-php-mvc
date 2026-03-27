<?php
namespace App\Models;

/**
 * Webhook Model
 * 
 * Manages api_webhooks table.
 * Handles registration, delivery, and failure tracking for webhook endpoints.
 * 
 * Events: order.created, order.completed, order.failed, order.cancelled, order.updated
 */
class Webhook extends BaseModel
{
    protected string $table = 'api_webhooks';
    protected bool $useCompanyFilter = false;

    /** Auto-disable after this many consecutive failures */
    const MAX_FAILURES = 10;

    /** Webhook delivery timeout in seconds */
    const TIMEOUT = 5;

    /**
     * Create a new webhook
     */
    public function createWebhook(int $companyId, string $url, array $events = []): ?array
    {
        if (empty($events)) {
            $events = ['order.created', 'order.completed', 'order.failed', 'order.cancelled'];
        }

        $secret = bin2hex(random_bytes(32));

        $data = [
            'company_id'    => $companyId,
            'url'           => $url,
            'secret'        => $secret,
            'events'        => implode(',', $events),
            'is_active'     => 1,
            'failure_count' => 0,
        ];

        $id = $this->hard->insertSafe($this->table, $data);
        if ($id) {
            return [
                'id'     => $id,
                'url'    => $url,
                'secret' => $secret,
                'events' => $events,
            ];
        }
        return null;
    }

    /**
     * Get all webhooks for a company
     */
    public function getByCompanyId(int $companyId): array
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT * FROM `{$this->table}` WHERE `company_id` = '$cid' ORDER BY `created_at` DESC";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Get active webhooks for a company that listen for a specific event
     */
    public function getActiveForEvent(int $companyId, string $event): array
    {
        $cid = \sql_int($companyId);
        $evt = \sql_escape($event);
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE `company_id` = '$cid' 
                AND `is_active` = 1 
                AND FIND_IN_SET('$evt', `events`) > 0";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Get a single webhook by ID with ownership check
     */
    public function findForCompany(int $id, int $companyId): ?array
    {
        $wid = \sql_int($id);
        $cid = \sql_int($companyId);
        $sql = "SELECT * FROM `{$this->table}` WHERE `id` = '$wid' AND `company_id` = '$cid'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Update webhook
     */
    public function updateWebhook(int $id, array $data): bool
    {
        $where = ['id' => $id];
        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(int $id): bool
    {
        return $this->hard->deleteSafe($this->table, ['id' => $id]);
    }

    /**
     * Toggle webhook active/inactive
     */
    public function toggleActive(int $id): bool
    {
        $wid = \sql_int($id);
        $sql = "UPDATE `{$this->table}` SET `is_active` = NOT `is_active`, `failure_count` = 0 WHERE `id` = '$wid'";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Fire webhook for an event — delivers to all active webhooks for the company
     * 
     * @param int    $companyId  Company that owns the webhooks
     * @param string $event      Event name (e.g. order.created)
     * @param array  $payload    Data to send
     * @return array Results of all deliveries
     */
    public function fireEvent(int $companyId, string $event, array $payload): array
    {
        $webhooks = $this->getActiveForEvent($companyId, $event);
        $results = [];

        foreach ($webhooks as $webhook) {
            $results[] = $this->deliver($webhook, $event, $payload);
        }

        return $results;
    }

    /**
     * Deliver a webhook payload to a single endpoint
     */
    private function deliver(array $webhook, string $event, array $payload): array
    {
        $body = json_encode([
            'event'     => $event,
            'timestamp' => date('c'),
            'data'      => $payload,
        ], JSON_UNESCAPED_UNICODE);

        // Generate HMAC-SHA256 signature
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
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Webhook-Event: ' . $event,
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

        // Log the delivery
        $this->logDelivery($webhook['id'], $event, $body, $responseCode, $responseBody, $durationMs, $success, $error);

        // Update webhook status
        $this->updateDeliveryStatus($webhook['id'], $success, $responseCode, $error);

        return [
            'webhook_id'    => intval($webhook['id']),
            'url'           => $webhook['url'],
            'success'       => $success,
            'status_code'   => $responseCode,
            'duration_ms'   => $durationMs,
            'error'         => $error,
        ];
    }

    /**
     * Log a webhook delivery attempt
     */
    private function logDelivery(int $webhookId, string $event, string $payload, ?int $responseCode, ?string $responseBody, int $durationMs, bool $success, ?string $error): void
    {
        $data = [
            'webhook_id'    => $webhookId,
            'event'         => $event,
            'payload'       => strlen($payload) > 5000 ? substr($payload, 0, 5000) : $payload,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? (strlen($responseBody) > 2000 ? substr($responseBody, 0, 2000) : $responseBody) : null,
            'duration_ms'   => $durationMs,
            'success'       => $success ? 1 : 0,
            'error'         => $error,
        ];
        $this->hard->insertSafe('api_webhook_deliveries', $data);
    }

    /**
     * Update webhook after delivery attempt (failure tracking + auto-disable)
     */
    private function updateDeliveryStatus(int $id, bool $success, ?int $statusCode, ?string $error): void
    {
        $wid = \sql_int($id);
        if ($success) {
            $sql = "UPDATE `{$this->table}` SET 
                    `failure_count` = 0, 
                    `last_triggered` = NOW(), 
                    `last_status` = '$statusCode',
                    `last_error` = NULL 
                    WHERE `id` = '$wid'";
        } else {
            $escapedError = $error ? \sql_escape($error) : 'Unknown error';
            $sql = "UPDATE `{$this->table}` SET 
                    `failure_count` = `failure_count` + 1, 
                    `last_triggered` = NOW(), 
                    `last_status` = " . ($statusCode ? "'$statusCode'" : "NULL") . ",
                    `last_error` = '$escapedError'
                    WHERE `id` = '$wid'";
            mysqli_query($this->conn, $sql);

            // Auto-disable after MAX_FAILURES consecutive failures
            $sql = "UPDATE `{$this->table}` SET `is_active` = 0 
                    WHERE `id` = '$wid' AND `failure_count` >= " . self::MAX_FAILURES;
        }
        mysqli_query($this->conn, $sql);
    }

    /**
     * Get recent deliveries for a webhook (for admin UI)
     */
    public function getDeliveries(int $webhookId, int $limit = 20): array
    {
        $wid = \sql_int($webhookId);
        $sql = "SELECT * FROM `api_webhook_deliveries` 
                WHERE `webhook_id` = '$wid' 
                ORDER BY `created_at` DESC 
                LIMIT $limit";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Count webhooks for a company
     */
    public function countForCompany(int $companyId): int
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE `company_id` = '$cid' AND `is_active` = 1";
        $result = mysqli_query($this->conn, $sql);
        return $result ? intval(mysqli_fetch_assoc($result)['cnt']) : 0;
    }
}
