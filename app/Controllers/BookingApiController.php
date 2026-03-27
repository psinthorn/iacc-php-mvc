<?php
namespace App\Controllers;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Booking;
use App\Models\Subscription;
use App\Services\BookingService;

/**
 * BookingApiController — REST API endpoint for booking requests
 * 
 * Called from api.php (not index.php).
 * All responses are JSON. No session/cookie auth — uses API key + secret.
 * 
 * Endpoints:
 *   POST   /api/v1/bookings          — Create a new booking
 *   GET    /api/v1/bookings/:id      — Get booking status
 *   GET    /api/v1/bookings          — List bookings
 *   DELETE /api/v1/bookings/:id      — Cancel a booking
 *   GET    /api/v1/subscription      — Get subscription info & usage
 */
class BookingApiController
{
    private \mysqli $conn;
    private ApiKey $apiKeyModel;
    private Booking $bookingModel;
    private Subscription $subscriptionModel;
    private ApiUsageLog $usageLog;
    private ?array $authData = null;
    private float $startTime;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->apiKeyModel = new ApiKey();
        $this->bookingModel = new Booking();
        $this->subscriptionModel = new Subscription();
        $this->usageLog = new ApiUsageLog();
        $this->startTime = microtime(true);
    }

    /**
     * Main router — called from api.php
     */
    public function handleRequest(string $method, string $path, ?int $resourceId = null): void
    {
        try {
            // Authenticate
            $this->authData = $this->authenticate();

            // Route to handler
            switch (true) {
                case $method === 'POST' && $path === 'bookings':
                    $this->createBooking();
                    break;

                case $method === 'GET' && $path === 'bookings' && $resourceId:
                    $this->getBooking($resourceId);
                    break;

                case $method === 'GET' && $path === 'bookings':
                    $this->listBookings();
                    break;

                case $method === 'DELETE' && $path === 'bookings' && $resourceId:
                    $this->cancelBooking($resourceId);
                    break;

                case $method === 'GET' && $path === 'subscription':
                    $this->getSubscription();
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
     * POST /api/v1/bookings — Create a new booking
     */
    private function createBooking(): void
    {
        $input = $this->getJsonInput();
        $companyId = intval($this->authData['company_id']);

        // Validate required fields
        $errors = $this->validateBookingInput($input);
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 422, 'VALIDATION_ERROR', $errors);
            return;
        }

        // Check subscription quota
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        if (!$this->subscriptionModel->hasQuota($companyId, $subscription)) {
            $this->jsonError(
                'Monthly booking quota exceeded. Current limit: ' . $subscription['bookings_limit'],
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

        // Create booking record
        $bookingData = [
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
        ];

        $bookingId = $this->bookingModel->createBooking($bookingData);
        if (!$bookingId) {
            $this->jsonError('Failed to create booking', 500, 'CREATE_FAILED');
            return;
        }

        // Process booking (create Company → PR → PO → Products)
        $service = new BookingService();
        $result = $service->processBooking($bookingId, $bookingData, $this->authData);

        if ($result['success']) {
            $this->logRequest('POST /api/v1/bookings', $channel, 201, $input, $result['data']);
            $this->jsonSuccess($result['data'], 201, 'Booking created and processed successfully');
        } else {
            $this->logRequest('POST /api/v1/bookings', $channel, 500, $input, ['error' => $result['error']]);
            // Booking was created but processing failed — return booking ID for retry
            $this->jsonSuccess([
                'booking_id' => $bookingId,
                'status'     => 'failed',
                'error'      => $result['error'],
            ], 202, 'Booking created but processing failed. You can retry later.');
        }
    }

    /**
     * GET /api/v1/bookings/:id — Get booking status
     */
    private function getBooking(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $booking = $this->bookingModel->findForCompany($id, $companyId);

        if (!$booking) {
            $this->jsonError('Booking not found', 404, 'NOT_FOUND');
            return;
        }

        $this->logRequest("GET /api/v1/bookings/$id", $booking['channel'], 200);
        $this->jsonSuccess($this->formatBookingResponse($booking));
    }

    /**
     * GET /api/v1/bookings — List bookings with filters
     */
    private function listBookings(): void
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

        $result = $this->bookingModel->getForCompany($companyId, $filters, $page, $perPage);

        $this->logRequest('GET /api/v1/bookings', '', 200);
        $this->jsonSuccess([
            'bookings'   => array_map([$this, 'formatBookingResponse'], $result['items']),
            'total'      => $result['total'],
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => $result['pagination']['total_pages'] ?? ceil($result['total'] / $perPage),
        ]);
    }

    /**
     * DELETE /api/v1/bookings/:id — Cancel a booking
     */
    private function cancelBooking(int $id): void
    {
        $companyId = intval($this->authData['company_id']);
        $booking = $this->bookingModel->findForCompany($id, $companyId);

        if (!$booking) {
            $this->jsonError('Booking not found', 404, 'NOT_FOUND');
            return;
        }

        if ($booking['status'] === 'cancelled') {
            $this->jsonError('Booking is already cancelled', 409, 'ALREADY_CANCELLED');
            return;
        }

        $this->bookingModel->updateStatus($id, 'cancelled');
        
        $this->logRequest("DELETE /api/v1/bookings/$id", $booking['channel'], 200);
        $this->jsonSuccess(['booking_id' => $id, 'status' => 'cancelled'], 200, 'Booking cancelled');
    }

    /**
     * GET /api/v1/subscription — Get subscription info & usage stats
     */
    private function getSubscription(): void
    {
        $companyId = intval($this->authData['company_id']);
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $usage = $this->subscriptionModel->getMonthlyUsage($companyId);
        $stats = $this->bookingModel->getStats($companyId);

        $this->logRequest('GET /api/v1/subscription', '', 200);
        $this->jsonSuccess([
            'plan'            => $subscription['plan'],
            'status'          => $subscription['status'],
            'bookings_limit'  => intval($subscription['bookings_limit']),
            'bookings_used'   => $usage,
            'bookings_remaining' => max(0, intval($subscription['bookings_limit']) - $usage),
            'channels'        => explode(',', $subscription['channels']),
            'ai_providers'    => explode(',', $subscription['ai_providers']),
            'keys_limit'      => intval($subscription['keys_limit']),
            'trial_end'       => $subscription['trial_end'],
            'expires_at'      => $subscription['expires_at'],
            'stats'           => $stats,
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

        // Check subscription is active
        if ($authData['sub_status'] !== 'active' || !$authData['enabled']) {
            $this->jsonError('API subscription is not active', 403, 'SUBSCRIPTION_INACTIVE');
            exit;
        }

        // Check subscription expiry
        $sub = $this->subscriptionModel->getByCompanyId(intval($authData['company_id']));
        if (!$this->subscriptionModel->isActive($sub)) {
            $this->jsonError('API subscription has expired', 403, 'SUBSCRIPTION_EXPIRED');
            exit;
        }

        return $authData;
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

    private function validateBookingInput(array $input): array
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

    private function formatBookingResponse(array $booking): array
    {
        return [
            'id'           => intval($booking['id']),
            'status'       => $booking['status'],
            'channel'      => $booking['channel'],
            'guest_name'   => $booking['guest_name'],
            'guest_email'  => $booking['guest_email'],
            'guest_phone'  => $booking['guest_phone'],
            'check_in'     => $booking['check_in'],
            'check_out'    => $booking['check_out'],
            'room_type'    => $booking['room_type'],
            'guests'       => intval($booking['guests']),
            'total_amount' => floatval($booking['total_amount'] ?? 0),
            'currency'     => $booking['currency'],
            'notes'        => $booking['notes'],
            'linked_pr_id' => $booking['linked_pr_id'] ? intval($booking['linked_pr_id']) : null,
            'linked_po_id' => $booking['linked_po_id'] ? intval($booking['linked_po_id']) : null,
            'created_at'   => $booking['created_at'],
            'processed_at' => $booking['processed_at'],
        ];
    }

    private function logRequest(string $endpoint, string $channel, int $statusCode, ?array $request = null, ?array $response = null): void
    {
        if (!$this->authData) return;

        $elapsed = intval((microtime(true) - $this->startTime) * 1000);

        $this->usageLog->logRequest([
            'company_id'    => intval($this->authData['company_id']),
            'api_key_id'    => intval($this->authData['id']),
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
