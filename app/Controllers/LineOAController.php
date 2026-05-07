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
    private \App\Models\LineMessaging $msgModel;

    public function __construct()
    {
        parent::__construct();
        $this->lineModel = new \App\Models\LineOA();
        $this->msgModel  = new \App\Models\LineMessaging();
    }

    // ====================================================================
    // v6.3 — Health Probe
    // ====================================================================

    /**
     * AJAX endpoint: re-probe LINE channel and return JSON status.
     * Also persists the latest result to line_oa_config for the dashboard cache.
     */
    public function probeConnection(): void
    {
        header('Content-Type: application/json');
        $companyId = (int)$this->user['com_id'];
        $config = $this->lineModel->getConfig($companyId);

        if (!$config || empty($config['channel_access_token'])) {
            echo json_encode(['status' => 'unknown', 'error' => 'No credentials configured']);
            return;
        }

        $service = new \App\Services\LineService(
            (string)$config['channel_access_token'],
            (string)$config['channel_secret']
        );
        $probe = $service->probeChannel();
        $this->lineModel->updateProbeResult($companyId, $probe);

        echo json_encode([
            'status'       => $probe['status'],
            'display_name' => $probe['display_name'] ?? null,
            'picture_url'  => $probe['picture_url']  ?? null,
            'basic_id'     => $probe['basic_id']     ?? null,
            'error'        => $probe['error']        ?? null,
            'probed_at'    => date('c'),
        ]);
    }

    // ====================================================================
    // v6.3 — Templates
    // ====================================================================

    public function templates(): void
    {
        $companyId = (int)$this->user['com_id'];
        $templates = $this->msgModel->getTemplates($companyId);
        $this->render('line-oa/templates-index', ['templates' => $templates]);
    }

    public function templateEdit(): void
    {
        $companyId = (int)$this->user['com_id'];
        $id = (int)($_GET['id'] ?? 0);
        $template = $id ? $this->msgModel->getTemplate($id, $companyId) : null;

        if ($id && !$template) {
            $_SESSION['flash_error'] = 'Template not found.';
            $this->redirect('line_templates');
            return;
        }

        $this->render('line-oa/template-edit', ['template' => $template]);
    }

    public function templateSave(): void
    {
        $this->verifyCsrf();
        $companyId = (int)$this->user['com_id'];
        $userId    = (int)($this->user['id'] ?? 0);

        $action = $_POST['action'] ?? 'save';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) $this->msgModel->deleteTemplate($id, $companyId);
            $_SESSION['flash_success'] = 'Template deleted.';
            $this->redirect('line_templates');
            return;
        }

        $data = [
            'id'             => (int)($_POST['id'] ?? 0),
            'name'           => trim($_POST['name'] ?? ''),
            'template_type'  => $_POST['template_type'] ?? 'custom',
            'message_type'   => $_POST['message_type'] ?? 'flex',
            'alt_text'       => trim($_POST['alt_text'] ?? ''),
            'content_th'     => $_POST['content_th'] ?? null,
            'content_en'     => $_POST['content_en'] ?? null,
            'variables_json' => $_POST['variables_json'] ?? null,
            'is_active'      => (int)($_POST['is_active'] ?? 1),
        ];
        if ($data['name'] === '') {
            $_SESSION['flash_error'] = 'Template name is required.';
            $this->redirect('line_template_edit' . ($data['id'] ? '&id=' . $data['id'] : ''));
            return;
        }

        $this->msgModel->saveTemplate($companyId, $data, $userId);
        $_SESSION['flash_success'] = 'Template saved.';
        $this->redirect('line_templates');
    }

    // ====================================================================
    // v6.3 — Broadcasts
    // ====================================================================

    public function broadcasts(): void
    {
        $companyId = (int)$this->user['com_id'];
        $broadcasts = $this->msgModel->getBroadcasts($companyId);
        $config = $this->lineModel->getConfig($companyId);
        $quotaUsed = $this->msgModel->getMonthlyBroadcastUsage($companyId);
        $quotaLimit = (int)($config['broadcast_quota_monthly'] ?? 500);
        $this->render('line-oa/broadcasts-index', [
            'broadcasts'  => $broadcasts,
            'quota_used'  => $quotaUsed,
            'quota_limit' => $quotaLimit,
        ]);
    }

    public function broadcastCompose(): void
    {
        $companyId = (int)$this->user['com_id'];
        $id = (int)($_GET['id'] ?? 0);
        $broadcast = $id ? $this->msgModel->getBroadcast($id, $companyId) : null;
        if ($id && !$broadcast) {
            $_SESSION['flash_error'] = 'Broadcast not found.';
            $this->redirect('line_broadcasts');
            return;
        }
        // sent broadcasts = read-only
        if ($broadcast && in_array($broadcast['status'], ['sent','sending','partial'])) {
            $this->render('line-oa/broadcast-view', ['broadcast' => $broadcast]);
            return;
        }
        $templates = $this->msgModel->getTemplates($companyId, true);
        $tags      = $this->msgModel->getTags($companyId);
        $this->render('line-oa/broadcast-compose', [
            'broadcast' => $broadcast,
            'templates' => $templates,
            'tags'      => $tags,
        ]);
    }

    /**
     * AJAX: return audience count for the given filter.
     */
    public function broadcastAudienceCount(): void
    {
        header('Content-Type: application/json');
        $companyId = (int)$this->user['com_id'];
        $type   = $_GET['audience_type'] ?? 'all';
        $filter = json_decode($_GET['filter'] ?? '{}', true) ?: [];
        $count  = $this->msgModel->audienceCount($companyId, $type, $filter);
        echo json_encode(['count' => $count]);
    }

    public function broadcastSave(): void
    {
        $this->verifyCsrf();
        $companyId = (int)$this->user['com_id'];
        $userId    = (int)($this->user['id'] ?? 0);
        $action    = $_POST['action'] ?? 'save_draft';

        $filter = $_POST['audience_filter'] ?? [];
        $data = [
            'id'                   => (int)($_POST['id'] ?? 0),
            'name'                 => trim($_POST['name'] ?? ''),
            'audience_type'        => $_POST['audience_type'] ?? 'all',
            'audience_filter_json' => json_encode($filter),
            'message_kind'         => $_POST['message_kind'] ?? 'text',
            'template_id'          => !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null,
            'text_content_th'      => $_POST['text_content_th'] ?? null,
            'text_content_en'      => $_POST['text_content_en'] ?? null,
            'flex_content_th'      => $_POST['flex_content_th'] ?? null,
            'flex_content_en'      => $_POST['flex_content_en'] ?? null,
            'alt_text'             => trim($_POST['alt_text'] ?? ''),
            'scheduled_at'         => trim($_POST['scheduled_at'] ?? '') ?: null,
            'status'               => 'draft',
        ];
        if ($data['name'] === '') {
            $_SESSION['flash_error'] = 'Name is required.';
            $this->redirect('line_broadcast_compose');
            return;
        }

        // Enforce monthly broadcast quota — block send_now/schedule when used + audience > limit.
        // Drafts are always allowed (no messages sent yet).
        if ($action !== 'save_draft') {
            $config     = $this->lineModel->getConfig($companyId);
            $quotaLimit = (int)($config['broadcast_quota_monthly'] ?? 500);
            $quotaUsed  = $this->msgModel->getMonthlyBroadcastUsage($companyId);
            $audienceN  = $this->msgModel->audienceCount($companyId, $data['audience_type'], $filter);
            if (($quotaUsed + $audienceN) > $quotaLimit) {
                $_SESSION['flash_error'] = sprintf(
                    'Quota exceeded: %d used this month + %d this send would exceed %d limit. Save as draft or reduce audience.',
                    $quotaUsed, $audienceN, $quotaLimit
                );
                $this->redirect('line_broadcast_compose');
                return;
            }
        }

        if ($action === 'send_now') {
            $data['status'] = 'sending';
        } elseif ($action === 'schedule') {
            if (!$data['scheduled_at']) {
                $_SESSION['flash_error'] = 'Scheduled time is required.';
                $this->redirect('line_broadcast_compose');
                return;
            }
            $data['status'] = 'scheduled';
        }

        $id = $this->msgModel->saveBroadcast($companyId, $data, $userId);

        if ($action === 'send_now') {
            $this->msgModel->materializeRecipients($id, $companyId);
            $result = $this->dispatchBroadcast($id, $companyId);
            $_SESSION['flash_success'] = 'Broadcast sent: ' . ($result['sent'] ?? 0) .
                                         ' delivered, ' . ($result['failed'] ?? 0) . ' failed.';
        } elseif ($action === 'schedule') {
            $this->msgModel->materializeRecipients($id, $companyId);
            $_SESSION['flash_success'] = 'Broadcast scheduled.';
        } else {
            $_SESSION['flash_success'] = 'Draft saved.';
        }
        $this->redirect('line_broadcasts');
    }

    /**
     * Materialize-then-multicast loop. Idempotent: only picks up rows still in 'pending'.
     * Used both by send-now and the cron worker.
     */
    public function dispatchBroadcast(int $broadcastId, int $companyId): array
    {
        $b = $this->msgModel->getBroadcast($broadcastId, $companyId);
        if (!$b) return ['sent' => 0, 'failed' => 0, 'error' => 'Broadcast not found'];

        $config = $this->lineModel->getConfig($companyId);
        if (!$config || empty($config['channel_access_token'])) {
            $this->msgModel->updateBroadcastStatus($broadcastId, $companyId, 'failed',
                ['last_error' => 'No LINE credentials']);
            return ['sent' => 0, 'failed' => 0, 'error' => 'No credentials'];
        }

        $this->msgModel->updateBroadcastStatus($broadcastId, $companyId, 'sending', ['mark_started' => true]);

        $service = new \App\Services\LineService(
            (string)$config['channel_access_token'],
            (string)$config['channel_secret']
        );

        $messages = $this->buildBroadcastMessages($b, $companyId);
        if (empty($messages)) {
            $this->msgModel->updateBroadcastStatus($broadcastId, $companyId, 'failed',
                ['last_error' => 'Empty message payload']);
            return ['sent' => 0, 'failed' => 0, 'error' => 'Empty message'];
        }

        $totalSent = 0;
        $totalFailed = 0;
        $lastError = null;

        // Drain pending recipients in chunks of 500 (LINE multicast limit)
        while (true) {
            $chunk = $this->msgModel->getPendingRecipientChunk($broadcastId, $companyId, 500);
            if (empty($chunk)) break;

            $userIds = array_column($chunk, 'line_user_id');
            $rowIds  = array_column($chunk, 'recipient_row_id');
            $resp = $service->multicast($userIds, $messages);

            if ($resp['success'] ?? false) {
                $this->msgModel->markRecipientsSent($rowIds, $companyId);
                $totalSent += count($rowIds);
            } else {
                $err = $resp['message'] ?? ($resp['error'] ?? 'Unknown error');
                $this->msgModel->markRecipientsFailed($rowIds, $companyId, $err);
                $totalFailed += count($rowIds);
                $lastError = $err;
                // Stop on auth errors — no point retrying
                if (in_array((int)($resp['http_code'] ?? 0), [401, 403])) break;
            }
        }

        $finalStatus = $totalFailed === 0 ? 'sent' : ($totalSent > 0 ? 'partial' : 'failed');
        $this->msgModel->updateBroadcastStatus($broadcastId, $companyId, $finalStatus, [
            'sent_count'     => $totalSent,
            'failed_count'   => $totalFailed,
            'last_error'     => $lastError,
            'mark_completed' => true,
        ]);

        return ['sent' => $totalSent, 'failed' => $totalFailed, 'error' => $lastError];
    }

    /**
     * Pick the right message payload (text vs template vs custom flex) and language.
     * For broadcasts we don't know per-recipient language, so we use the company default.
     */
    private function buildBroadcastMessages(array $broadcast, int $companyId): array
    {
        $isThai = (($_SESSION['lang'] ?? '0') === '1');

        switch ($broadcast['message_kind']) {
            case 'template':
                $tpl = $this->msgModel->getTemplate((int)$broadcast['template_id'], $companyId);
                if (!$tpl) return [];
                $rendered = $this->msgModel->renderTemplate($tpl, [], $isThai);
                if ($rendered['type'] === 'text') {
                    return [['type' => 'text', 'text' => $rendered['text']]];
                }
                return [['type' => 'flex', 'altText' => $rendered['alt_text'] ?: 'Message', 'contents' => $rendered['contents']]];

            case 'custom_flex':
                $flexJson = $isThai
                    ? ($broadcast['flex_content_th'] ?? $broadcast['flex_content_en'])
                    : ($broadcast['flex_content_en'] ?? $broadcast['flex_content_th']);
                $contents = json_decode((string)$flexJson, true);
                if (!$contents) return [];
                return [['type' => 'flex', 'altText' => $broadcast['alt_text'] ?: 'Message', 'contents' => $contents]];

            case 'text':
            default:
                $text = $isThai
                    ? ($broadcast['text_content_th'] ?? $broadcast['text_content_en'])
                    : ($broadcast['text_content_en'] ?? $broadcast['text_content_th']);
                if (empty($text)) return [];
                return [['type' => 'text', 'text' => (string)$text]];
        }
    }

    /**
     * Cron entrypoint: process all due scheduled broadcasts across tenants.
     * Called from cron.php only — never via web routing.
     */
    public function processDueBroadcasts(): array
    {
        $due = $this->msgModel->findDueBroadcasts(20);
        $report = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'broadcasts' => []];
        foreach ($due as $b) {
            $companyId = (int)$b['company_id'];
            $result = $this->dispatchBroadcast((int)$b['id'], $companyId);
            $report['processed']++;
            $report['sent']    += $result['sent']   ?? 0;
            $report['failed']  += $result['failed'] ?? 0;
            $report['broadcasts'][] = ['id' => $b['id'], 'company_id' => $companyId] + $result;
        }
        return $report;
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
        $dailyMessages = $this->lineModel->getDailyMessageStats($companyId);

        $this->render('line-oa/dashboard', [
            'stats' => $stats,
            'config' => $config,
            'recentOrders' => $recentOrders,
            'recentMessages' => $recentMessages,
            'dailyMessages' => $dailyMessages
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
                $this->redirect('line_dashboard');
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
        $this->redirect('line_settings');
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
            $this->redirect('line_orders');
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
            $this->redirect('line_orders');
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
        $this->redirect('index.php?page=line_order_detail&id=' . $orderId);
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
        $this->redirect('index.php?page=line_order_detail&id=' . $orderId);
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
     * v6.3 #120 — Update a LINE user's user_type from the Users page dropdown.
     * Required so admins can promote a user to 'agent' before binding.
     */
    public function updateUserType(): void
    {
        if ((int)($this->user['level'] ?? 0) < 2) {
            http_response_code(403);
            die('Admin access required');
        }
        $this->verifyCsrf();
        $companyId    = (int)$this->user['com_id'];
        $lineUserDbId = (int)($_POST['line_user_id'] ?? 0);
        $userType     = $_POST['user_type'] ?? '';

        if ($lineUserDbId <= 0) {
            $_SESSION['flash_error'] = 'Missing LINE user.';
        } elseif (!in_array($userType, ['customer', 'agent'], true)) {
            $_SESSION['flash_error'] = 'Invalid user type.';
        } elseif ($this->lineModel->updateUserType($companyId, $lineUserDbId, $userType)) {
            $_SESSION['flash_success'] = 'User type updated.';
        } else {
            $_SESSION['flash_error'] = 'Could not update — verify the user belongs to this company.';
        }
        $this->redirect('line_users');
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
            $this->redirect('line_auto_replies');
            return;
        }

        $this->lineModel->saveAutoReply($companyId, $data);
        $_SESSION['flash_success'] = 'Auto-reply rule saved.';
        $this->redirect('line_auto_replies');
    }

    private function deleteAutoReply(int $companyId): void
    {
        $replyId = intval($_POST['reply_id'] ?? 0);
        $this->lineModel->deleteAutoReply($replyId, $companyId);
        $_SESSION['flash_success'] = 'Auto-reply rule deleted.';
        $this->redirect('line_auto_replies');
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
            $this->redirect('line_send_message');
            return;
        }

        $config = $this->lineModel->getConfig($companyId);
        if (!$config || !$config['is_active']) {
            $_SESSION['flash_error'] = 'LINE OA is not configured or inactive.';
            $this->redirect('line_send_message');
            return;
        }

        $lineUser = $this->lineModel->getLineUserById($lineUserDbId);
        if (!$lineUser) {
            $_SESSION['flash_error'] = 'LINE user not found.';
            $this->redirect('line_send_message');
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

        $this->redirect('line_send_message');
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
