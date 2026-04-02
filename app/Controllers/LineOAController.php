<?php
namespace App\Controllers;

/**
 * LineOAController — Admin UI for LINE OA Sales Channel
 * 
 * Pages:
 * - dashboard: Overview stats, recent orders, recent messages
 * - settings: LINE channel configuration
 * - orders: LINE order management
 * - order_detail: Single order view + status update
 * - messages: Message log / conversation view
 * - users: LINE user list
 * - auto_replies: Auto-reply rule management
 * - webhook_log: Raw webhook event log
 * - send_message: Push message to a LINE user
 */
class LineOAController extends BaseController
{
    private \App\Models\LineOA $lineModel;

    public function __construct()
    {
        parent::__construct();
        $this->lineModel = new \App\Models\LineOA();
    }

    /**
     * Dashboard — overview stats
     */
    public function dashboard(): void
    {
        $companyId = $this->user['com_id'];
        $stats = $this->lineModel->getStats($companyId);
        $config = $this->lineModel->getConfig($companyId);
        $recentOrders = $this->lineModel->getOrders($companyId, null, null, 5);
        $recentMessages = $this->lineModel->getMessages($companyId, null, 10);

        $this->render('line-oa/dashboard', [
            'stats' => $stats,
            'config' => $config,
            'recentOrders' => $recentOrders,
            'recentMessages' => $recentMessages
        ]);
    }

    /**
     * Settings — LINE channel configuration
     */
    public function settings(): void
    {
        $companyId = $this->user['com_id'];
        $config = $this->lineModel->getConfig($companyId);

        $this->render('line-oa/settings', [
            'config' => $config
        ]);
    }

    /**
     * Store settings
     */
    public function store(): void
    {
        $this->verifyCsrf();
        $companyId = $this->user['com_id'];
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'save_config':
                $this->saveConfig($companyId);
                break;
            case 'save_auto_reply':
                $this->saveAutoReply($companyId);
                break;
            case 'delete_auto_reply':
                $this->deleteAutoReply($companyId);
                break;
            case 'update_order_status':
                $this->updateOrderStatus($companyId);
                break;
            case 'confirm_payment':
                $this->confirmPayment($companyId);
                break;
            case 'send_message':
                $this->sendMessage($companyId);
                break;
            default:
                $this->redirect('?page=line_dashboard');
        }
    }

    private function saveConfig(int $companyId): void
    {
        $data = [
            'channel_id' => trim($_POST['channel_id'] ?? ''),
            'channel_secret' => trim($_POST['channel_secret'] ?? ''),
            'channel_access_token' => trim($_POST['channel_access_token'] ?? ''),
            'webhook_url' => trim($_POST['webhook_url'] ?? ''),
            'is_active' => intval($_POST['is_active'] ?? 0),
            'greeting_message' => trim($_POST['greeting_message'] ?? ''),
            'auto_reply_enabled' => intval($_POST['auto_reply_enabled'] ?? 0)
        ];

        $this->lineModel->saveConfig($companyId, $data);
        $_SESSION['flash_success'] = 'LINE OA settings saved successfully.';
        $this->redirect('?page=line_settings');
    }

    /**
     * Orders list
     */
    public function orders(): void
    {
        $companyId = $this->user['com_id'];
        $status = $_GET['status'] ?? null;
        $orderType = $_GET['order_type'] ?? null;
        $orders = $this->lineModel->getOrders($companyId, $status, $orderType);

        $this->render('line-oa/orders', [
            'orders' => $orders,
            'currentStatus' => $status,
            'currentType' => $orderType
        ]);
    }

    /**
     * Order detail view
     */
    public function orderDetail(): void
    {
        $companyId = $this->user['com_id'];
        $orderId = intval($_GET['id'] ?? 0);
        $order = $this->lineModel->getOrder($orderId, $companyId);

        if (!$order) {
            $_SESSION['flash_error'] = 'Order not found.';
            $this->redirect('?page=line_orders');
            return;
        }

        // Get messages for this user
        $messages = $this->lineModel->getMessages($companyId, $order['line_user_id'], 20);

        $this->render('line-oa/order-detail', [
            'order' => $order,
            'messages' => $messages
        ]);
    }

    private function updateOrderStatus(int $companyId): void
    {
        $orderId = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];

        if (!in_array($status, $allowed)) {
            $_SESSION['flash_error'] = 'Invalid status.';
            $this->redirect('?page=line_orders');
            return;
        }

        $this->lineModel->updateOrderStatus($orderId, $companyId, $status, $this->user['id']);

        // When confirmed, process into iACC business records (PR → PO → Products)
        if ($status === 'confirmed') {
            $this->processOrderToBusinessRecords($orderId, $companyId);
        }

        // Optionally notify via LINE
        $order = $this->lineModel->getOrder($orderId, $companyId);
        if ($order) {
            $config = $this->lineModel->getConfig($companyId);
            if ($config && $config['is_active']) {
                $lineUser = $this->lineModel->getLineUserById($order['line_user_id']);
                if ($lineUser) {
                    $service = new \App\Services\LineService($config['channel_access_token'], $config['channel_secret']);
                    $statusMsg = "Your order {$order['order_ref']} status updated to: " . strtoupper($status);
                    $service->pushText($lineUser['line_user_id'], $statusMsg);
                }
            }
        }

        $_SESSION['flash_success'] = 'Order status updated.';
        $this->redirect('?page=line_order_detail&id=' . $orderId);
    }

    private function confirmPayment(int $companyId): void
    {
        $orderId = intval($_POST['order_id'] ?? 0);
        $paymentStatus = $_POST['payment_status'] ?? 'confirmed';

        $this->lineModel->updatePaymentStatus($orderId, $companyId, $paymentStatus);

        // Notify customer
        $order = $this->lineModel->getOrder($orderId, $companyId);
        if ($order) {
            $config = $this->lineModel->getConfig($companyId);
            if ($config && $config['is_active']) {
                $lineUser = $this->lineModel->getLineUserById($order['line_user_id']);
                if ($lineUser) {
                    $service = new \App\Services\LineService($config['channel_access_token'], $config['channel_secret']);
                    $msg = $paymentStatus === 'confirmed'
                        ? "Payment confirmed for order {$order['order_ref']}! ✅ Thank you."
                        : "Payment for order {$order['order_ref']} was rejected. Please contact us.";
                    $service->pushText($lineUser['line_user_id'], $msg);
                }
            }
        }

        $_SESSION['flash_success'] = 'Payment status updated.';
        $this->redirect('?page=line_order_detail&id=' . $orderId);
    }

    /**
     * Process LINE order into iACC business records via ChannelService
     * Creates: Customer → PR → PO → Product line items
     */
    private function processOrderToBusinessRecords(int $orderId, int $companyId): void
    {
        $order = $this->lineModel->getOrder($orderId, $companyId);
        if (!$order || !empty($order['linked_po_id'])) {
            return; // Already linked or not found
        }

        try {
            $channelService = new \App\Services\ChannelService();

            // Map LINE order data to ChannelService format
            $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
            $itemDescription = '';
            foreach ($items as $item) {
                $itemDescription .= ($item['name'] ?? 'Item') . ' x' . ($item['qty'] ?? 1) . ', ';
            }
            $itemDescription = rtrim($itemDescription, ', ') ?: ($order['order_type'] === 'booking' ? 'Booking' : 'LINE Order');

            $channelOrder = [
                'guest_name'   => $order['guest_name'] ?: ($order['display_name'] ?? 'LINE Customer'),
                'guest_email'  => $order['guest_email'] ?? '',
                'guest_phone'  => $order['guest_phone'] ?? '',
                'room_type'    => $itemDescription,
                'total_amount' => $order['total_amount'] ?? 0,
                'check_in'     => $order['booking_date'] ?? date('Y-m-d'),
                'check_out'    => $order['booking_date'] ?? date('Y-m-d', strtotime('+1 day')),
                'channel'      => 'line',
                'notes'        => $order['notes'] ?? "LINE Order Ref: {$order['order_ref']}",
            ];

            $authData = ['company_id' => $companyId];

            $result = $channelService->processOrder($orderId, $channelOrder, $authData);

            if ($result['success']) {
                $this->lineModel->linkToBusinessRecords(
                    $orderId, $companyId,
                    $result['data']['pr_id'],
                    $result['data']['po_id']
                );
                $_SESSION['flash_success'] = 'Order confirmed and linked to PR #' . $result['data']['pr_id'] . ' / PO #' . $result['data']['po_id'];
            } else {
                $_SESSION['flash_error'] = 'Order confirmed but failed to create business records: ' . ($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Order confirmed but processing failed: ' . $e->getMessage();
        }
    }

    /**
     * Messages — conversation log
     */
    public function messages(): void
    {
        $companyId = $this->user['com_id'];
        $lineUserId = intval($_GET['user_id'] ?? 0);
        $messages = $this->lineModel->getMessages($companyId, $lineUserId ?: null);

        $this->render('line-oa/messages', [
            'messages' => $messages,
            'selectedUserId' => $lineUserId
        ]);
    }

    /**
     * LINE Users list
     */
    public function users(): void
    {
        $companyId = $this->user['com_id'];
        $userType = $_GET['type'] ?? null;
        $users = $this->lineModel->getLineUsers($companyId, $userType);

        $this->render('line-oa/users', [
            'lineUsers' => $users,
            'currentType' => $userType
        ]);
    }

    /**
     * Auto-reply rules management
     */
    public function autoReplies(): void
    {
        $companyId = $this->user['com_id'];
        $rules = $this->lineModel->getAutoReplies($companyId);

        $this->render('line-oa/auto-replies', [
            'rules' => $rules
        ]);
    }

    private function saveAutoReply(int $companyId): void
    {
        $data = [
            'id' => intval($_POST['reply_id'] ?? 0) ?: null,
            'trigger_keyword' => trim($_POST['trigger_keyword'] ?? ''),
            'match_type' => $_POST['match_type'] ?? 'contains',
            'reply_type' => $_POST['reply_type'] ?? 'text',
            'reply_content' => trim($_POST['reply_content'] ?? ''),
            'is_active' => intval($_POST['is_active'] ?? 1),
            'priority' => intval($_POST['priority'] ?? 0)
        ];

        if (empty($data['trigger_keyword']) || empty($data['reply_content'])) {
            $_SESSION['flash_error'] = 'Keyword and reply content are required.';
            $this->redirect('?page=line_auto_replies');
            return;
        }

        $this->lineModel->saveAutoReply($companyId, $data);
        $_SESSION['flash_success'] = 'Auto-reply rule saved.';
        $this->redirect('?page=line_auto_replies');
    }

    private function deleteAutoReply(int $companyId): void
    {
        $replyId = intval($_POST['reply_id'] ?? 0);
        $this->lineModel->deleteAutoReply($replyId, $companyId);
        $_SESSION['flash_success'] = 'Auto-reply rule deleted.';
        $this->redirect('?page=line_auto_replies');
    }

    /**
     * Webhook event log
     */
    public function webhookLog(): void
    {
        $companyId = $this->user['com_id'];
        $events = $this->lineModel->getWebhookEvents($companyId);

        $this->render('line-oa/webhook-log', [
            'events' => $events
        ]);
    }

    /**
     * Send message form + handler
     */
    public function sendMessagePage(): void
    {
        $companyId = $this->user['com_id'];
        $users = $this->lineModel->getLineUsers($companyId);

        $this->render('line-oa/send-message', [
            'lineUsers' => $users
        ]);
    }

    private function sendMessage(int $companyId): void
    {
        $lineUserDbId = intval($_POST['line_user_id'] ?? 0);
        $messageText = trim($_POST['message'] ?? '');
        $messageType = $_POST['message_type'] ?? 'text';

        if (!$lineUserDbId || empty($messageText)) {
            $_SESSION['flash_error'] = 'User and message are required.';
            $this->redirect('?page=line_send_message');
            return;
        }

        $config = $this->lineModel->getConfig($companyId);
        if (!$config || !$config['is_active']) {
            $_SESSION['flash_error'] = 'LINE OA is not configured or inactive.';
            $this->redirect('?page=line_send_message');
            return;
        }

        $lineUser = $this->lineModel->getLineUserById($lineUserDbId);
        if (!$lineUser) {
            $_SESSION['flash_error'] = 'LINE user not found.';
            $this->redirect('?page=line_send_message');
            return;
        }

        $service = new \App\Services\LineService($config['channel_access_token'], $config['channel_secret']);

        if ($messageType === 'image') {
            $result = $service->pushImage($lineUser['line_user_id'], $messageText);
        } else {
            $result = $service->pushText($lineUser['line_user_id'], $messageText);
        }

        if ($result['success'] ?? false) {
            $this->lineModel->logMessage($companyId, $lineUserDbId, 'outbound', $messageType, null, null, $messageText, null, 'sent');
            $_SESSION['flash_success'] = 'Message sent successfully.';
        } else {
            $this->lineModel->logMessage($companyId, $lineUserDbId, 'outbound', $messageType, null, null, $messageText, null, 'failed');
            $_SESSION['flash_error'] = 'Failed to send message: ' . ($result['message'] ?? 'Unknown error');
        }

        $this->redirect('?page=line_send_message');
    }

    /**
     * CSRF verification helper
     */
    protected function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('CSRF token mismatch');
        }
    }
}
