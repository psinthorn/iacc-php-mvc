<?php
namespace App\Controllers;

use App\Models\ApiKey;
use App\Models\ApiInvoice;
use App\Models\ApiUsageLog;
use App\Models\ChannelOrder;
use App\Models\Subscription;
use App\Models\Webhook;

/**
 * AdminApiController — Admin panel for managing Sales Channel API
 * 
 * Admin (level >= 2):
 *   - View all subscriptions
 *   - Enable/disable subscriptions
 *   - Change plans
 * 
 * Any logged-in user (level >= 0):
 *   - View own subscription
 *   - Manage own API keys
 *   - View own orders & usage logs
 */
class AdminApiController extends BaseController
{
    private Subscription $subscriptionModel;
    private ApiKey $apiKeyModel;
    private ApiInvoice $invoiceModel;
    private ChannelOrder $orderModel;
    private ApiUsageLog $usageLogModel;
    private Webhook $webhookModel;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new Subscription();
        $this->apiKeyModel = new ApiKey();
        $this->invoiceModel = new ApiInvoice();
        $this->orderModel = new ChannelOrder();
        $this->usageLogModel = new ApiUsageLog();
        $this->webhookModel = new Webhook();
    }

    /**
     * Subscription management page (Super Admin)
     */
    public function subscriptions(): void
    {
        $this->requireLevel(2);

        $search = $this->inputStr('search');
        $page = $this->inputInt('p', 1);
        $result = $this->subscriptionModel->getAllWithCompany($search, $page);

        $this->render('api/subscriptions', [
            'subscriptions' => $result['items'],
            'total'         => $result['total'],
            'pagination'    => $result['pagination'],
            'search'        => $search,
            'title'         => 'API Subscriptions',
        ]);
    }

    /**
     * Toggle subscription enabled/disabled (Super Admin)
     */
    public function toggleSubscription(): void
    {
        $this->requireLevel(2);
        $this->verifyCsrf();

        $id = $this->inputInt('subscription_id');
        if ($id > 0) {
            $this->subscriptionModel->toggleEnabled($id);
        }

        $this->redirect('api_subscriptions');
    }

    /**
     * Change subscription plan (Super Admin)
     */
    public function changePlan(): void
    {
        $this->requireLevel(2);
        $this->verifyCsrf();

        $id = $this->inputInt('subscription_id');
        $plan = $this->inputStr('plan');

        if ($id > 0 && in_array($plan, ['trial', 'starter', 'professional', 'enterprise'])) {
            $this->subscriptionModel->changePlan($id, $plan);
        }

        $this->redirect('api_subscriptions');
    }

    /**
     * Override trial/subscription expiry date (Super Admin only, AJAX POST)
     * Accepts: subscription_id, new_date (YYYY-MM-DD), note (optional)
     */
    public function extendTrial(): void
    {
        header('Content-Type: application/json');
        $this->requireLevel(3);
        $this->verifyCsrf();

        $id      = $this->inputInt('subscription_id');
        $newDate = trim($this->inputStr('new_date'));
        $note    = trim($this->inputStr('note'));

        if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
            echo json_encode(['success' => false, 'message' => 'Invalid subscription ID or date']);
            exit;
        }

        $date = mysqli_real_escape_string($this->conn, $newDate);
        $note = mysqli_real_escape_string($this->conn, $note);
        $adminId = intval($this->user['id'] ?? 0);

        // Determine which column to update based on current plan
        $res = mysqli_query($this->conn, "SELECT plan FROM api_subscriptions WHERE id = $id LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Subscription not found']);
            exit;
        }

        if ($row['plan'] === 'trial') {
            $ok = mysqli_query($this->conn,
                "UPDATE api_subscriptions
                 SET trial_end = '$date', trial_locked_at = NULL, status = 'active', enabled = 1,
                     updated_at = NOW()
                 WHERE id = $id"
            );
        } else {
            $ok = mysqli_query($this->conn,
                "UPDATE api_subscriptions
                 SET expires_at = '$date 23:59:59', status = 'active', enabled = 1,
                     updated_at = NOW()
                 WHERE id = $id"
            );
        }

        // Audit log
        if ($ok && function_exists('audit_log')) {
            audit_log($this->conn, 'extend_trial', 'api_subscriptions', $adminId,
                "sub_id=$id new_date=$date note=$note");
        }

        echo json_encode([
            'success'  => (bool) $ok,
            'message'  => $ok ? "Expiry updated to $newDate" : 'DB update failed',
            'new_date' => $newDate,
        ]);
        exit;
    }

    /**
     * API Keys management page (Company Admin)
     */
    public function keys(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        // Get or create subscription
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        
        $keys = [];
        if ($subscription) {
            $keys = $this->apiKeyModel->getByCompanyId($companyId);
        }

        $this->render('api/keys', [
            'subscription' => $subscription,
            'keys'         => $keys,
            'title'        => 'API Keys',
        ]);
    }

    /**
     * Activate trial / create subscription
     */
    public function activateTrial(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        // Check if already has subscription
        $existing = $this->subscriptionModel->getByCompanyId($companyId);
        if ($existing) {
            $this->redirect('api_keys');
            return;
        }

        // Create trial subscription
        $subId = $this->subscriptionModel->createTrial($companyId);
        if ($subId) {
            // Auto-create first API key
            $this->apiKeyModel->createKey($companyId, $subId, 'Default');
        }

        $this->redirect('api_keys');
    }

    /**
     * Generate a new API key
     */
    public function createKey(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        if (!$subscription) {
            $this->redirect('api_keys');
            return;
        }

        // Check key limit
        $activeKeys = $this->apiKeyModel->countActiveKeys($subscription['id']);
        if ($activeKeys >= $subscription['keys_limit']) {
            // At limit — redirect with error
            $this->redirect('api_keys', ['error' => 'key_limit']);
            return;
        }

        $name = $this->inputStr('key_name', 'Key ' . ($activeKeys + 1));
        $this->apiKeyModel->createKey($companyId, intval($subscription['id']), $name);

        $this->redirect('api_keys');
    }

    /**
     * Revoke an API key
     */
    public function revokeKey(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        if ($id > 0) {
            $this->apiKeyModel->revoke($id);
        }

        $this->redirect('api_keys');
    }

    /**
     * Orders list page
     */
    public function orders(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $filters = [
            'status'    => $this->inputStr('status'),
            'channel'   => $this->inputStr('channel'),
            'date_from' => $this->inputStr('date_from'),
            'date_to'   => $this->inputStr('date_to'),
            'search'    => $this->inputStr('search'),
        ];
        $page = $this->inputInt('p', 1);

        $result = $this->orderModel->getForCompany($companyId, $filters, $page);
        $stats = $this->orderModel->getStats($companyId);
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);

        $this->render('api/orders', [
            'orders'     => $result['items'],
            'total'        => $result['total'],
            'pagination'   => $result['pagination'],
            'filters'      => $filters,
            'stats'        => $stats,
            'subscription' => $subscription,
            'title'        => 'Channel Orders',
        ]);
    }

    /**
     * Usage logs page
     */
    public function usageLogs(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $page = $this->inputInt('p', 1);
        $result = $this->usageLogModel->getForCompany($companyId, $page);
        $daily = $this->usageLogModel->getDailySummary($companyId);
        $channels = $this->usageLogModel->getChannelBreakdown($companyId);

        $this->render('api/usage-logs', [
            'logs'       => $result['items'],
            'total'      => $result['total'],
            'pagination' => $result['pagination'],
            'daily'      => $daily,
            'channels'   => $channels,
            'title'      => 'API Usage Logs',
        ]);
    }

    /**
     * API Dashboard overview
     */
    public function dashboard(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $stats = $this->orderModel->getStats($companyId);
        $recentOrders = $this->orderModel->getRecent($companyId, 10);
        $dailyUsage = $this->usageLogModel->getDailySummary($companyId, 7);
        $usage = $subscription ? $this->subscriptionModel->getMonthlyUsage($companyId) : 0;
        $webhookCount = $this->webhookModel->countForCompany($companyId);
        $quotaPercent = ($subscription && intval($subscription['orders_limit']) > 0)
            ? min(100, round(($usage / intval($subscription['orders_limit'])) * 100))
            : 0;

        $this->render('api/dashboard', [
            'subscription'   => $subscription,
            'stats'          => $stats,
            'recentOrders' => $recentOrders,
            'dailyUsage'     => $dailyUsage,
            'monthlyUsage'   => $usage,
            'webhookCount'   => $webhookCount,
            'quotaPercent'   => $quotaPercent,
            'title'          => 'Sales Channel Dashboard',
        ]);
    }

    /**
     * Webhook management page
     */
    public function webhooks(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $webhooks = $this->webhookModel->getByCompanyId($companyId);
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);

        $this->render('api/webhooks', [
            'webhooks'     => $webhooks,
            'subscription' => $subscription,
            'title'        => 'Webhook Management',
        ]);
    }

    /**
     * Create a webhook (form submission)
     */
    public function createWebhook(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        $url = $this->inputStr('webhook_url');
        $events = $_POST['events'] ?? [];
        
        if (!empty($url) && is_array($events) && !empty($events)) {
            $this->webhookModel->createWebhook($companyId, $url, $events);
        }

        $this->redirect('api_webhooks');
    }

    /**
     * Toggle webhook active/inactive
     */
    public function toggleWebhook(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();

        $id = $this->inputInt('webhook_id');
        if ($id > 0) {
            $companyId = $this->getCompanyId();
            $webhook = $this->webhookModel->findForCompany($id, $companyId);
            if ($webhook) {
                $this->webhookModel->toggleActive($id);
            }
        }

        $this->redirect('api_webhooks');
    }

    /**
     * Delete a webhook
     */
    public function deleteAdminWebhook(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();

        $id = $this->inputInt('webhook_id');
        if ($id > 0) {
            $companyId = $this->getCompanyId();
            $webhook = $this->webhookModel->findForCompany($id, $companyId);
            if ($webhook) {
                $this->webhookModel->deleteWebhook($id);
            }
        }

        $this->redirect('api_webhooks');
    }

    /**
     * Rotate an API key (generates new credentials with grace period)
     */
    public function rotateKey(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        if ($id > 0) {
            $result = $this->apiKeyModel->rotateKey($id, 24);
            if ($result) {
                $_SESSION['rotated_key'] = $result;
            }
        }

        $this->redirect('api_keys');
    }

    /**
     * Update order status (approve/reject/cancel from admin panel)
     */
    public function updateOrderStatus(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        $id = $this->inputInt('id');
        $action = $this->inputStr('action');
        $adminNotes = $this->inputStr('admin_notes');

        $order = $this->orderModel->findForCompany($id, $companyId);
        if (!$order) {
            $this->redirect('api_orders');
            return;
        }

        $allowedTransitions = [
            'pending'    => ['approve', 'reject', 'cancel'],
            'processing' => ['cancel'],
            'completed'  => ['cancel'],
            'failed'     => ['retry', 'cancel'],
        ];

        $currentStatus = $order['status'];
        $allowed = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($action, $allowed)) {
            $this->redirect('api_order_detail', ['id' => $id, 'error' => 'invalid_action']);
            return;
        }

        switch ($action) {
            case 'approve':
                // Process the order through ChannelService
                require_once __DIR__ . '/../Services/ChannelService.php';
                $service = new \App\Services\ChannelService();
                // Build auth data for the service
                $subscription = $this->subscriptionModel->getByCompanyId($companyId);
                $authData = ['company_id' => $companyId, 'plan' => $subscription['plan'] ?? 'trial'];
                $result = $service->processOrder($id, $order, $authData);
                if ($result['success'] && !empty($adminNotes)) {
                    $existingNotes = $order['notes'] ? $order['notes'] . "\n" : '';
                    $this->orderModel->updateFields($id, [
                        'notes' => $existingNotes . '[Admin] ' . $adminNotes,
                    ]);
                }
                break;

            case 'reject':
                $extra = ['error_message' => 'Rejected by admin' . ($adminNotes ? ": $adminNotes" : '')];
                if (!empty($adminNotes)) {
                    $existingNotes = $order['notes'] ? $order['notes'] . "\n" : '';
                    $extra['notes'] = $existingNotes . '[Admin] ' . $adminNotes;
                }
                $this->orderModel->updateStatus($id, 'failed', $extra);
                break;

            case 'cancel':
                $extra = [];
                if (!empty($adminNotes)) {
                    $existingNotes = $order['notes'] ? $order['notes'] . "\n" : '';
                    $extra['notes'] = $existingNotes . '[Admin] ' . $adminNotes;
                }
                $this->orderModel->updateStatus($id, 'cancelled', $extra);
                break;

            case 'retry':
                // Re-process a failed order
                require_once __DIR__ . '/../Services/ChannelService.php';
                $service = new \App\Services\ChannelService();
                $subscription = $this->subscriptionModel->getByCompanyId($companyId);
                $authData = ['company_id' => $companyId, 'plan' => $subscription['plan'] ?? 'trial'];
                // Reset status to pending first
                $this->orderModel->updateStatus($id, 'pending', ['error_message' => '']);
                $order['status'] = 'pending'; // update local copy
                $result = $service->processOrder($id, $order, $authData);
                if ($result['success'] && !empty($adminNotes)) {
                    $existingNotes = $order['notes'] ? $order['notes'] . "\n" : '';
                    $this->orderModel->updateFields($id, [
                        'notes' => $existingNotes . '[Admin Retry] ' . $adminNotes,
                    ]);
                }
                break;
        }

        $this->redirect('api_order_detail', ['id' => $id]);
    }

    /**
     * Order detail page
     */
    public function orderDetail(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();
        $id = $this->inputInt('id');

        $order = $this->orderModel->findForCompany($id, $companyId);
        if (!$order) {
            $this->redirect('api_orders');
            return;
        }

        $this->render('api/order-detail', [
            'order' => $order,
            'title'   => 'Order #' . $id,
        ]);
    }

    /**
     * Export orders to CSV
     */
    public function exportOrders(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $filters = [
            'status'    => $this->inputStr('status'),
            'channel'   => $this->inputStr('channel'),
            'date_from' => $this->inputStr('date_from'),
            'date_to'   => $this->inputStr('date_to'),
            'search'    => $this->inputStr('search'),
        ];

        // Fetch ALL orders (no pagination limit)
        $result = $this->orderModel->getForCompany($companyId, $filters, 1, 999999);
        $orders = $result['items'];

        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="channel-orders-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($out, ['ID', 'Guest Name', 'Email', 'Phone', 'Channel', 'Status', 'Room Type', 'Check-in', 'Check-out', 'Guests', 'Amount', 'Currency', 'PO ID', 'Notes', 'Created']);

        foreach ($orders as $o) {
            fputcsv($out, [
                $o['id'],
                $o['guest_name'],
                $o['guest_email'] ?? '',
                $o['guest_phone'] ?? '',
                $o['channel'],
                $o['status'],
                $o['room_type'] ?? '',
                $o['check_in'] ?? '',
                $o['check_out'] ?? '',
                $o['guests'] ?? '',
                $o['total_amount'],
                $o['currency'] ?? 'THB',
                $o['linked_po_id'] ?? '',
                $o['notes'] ?? '',
                $o['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Webhook delivery log page
     */
    public function webhookDeliveries(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();
        $webhookId = $this->inputInt('id');

        // Verify webhook belongs to this company
        $webhook = $this->webhookModel->findForCompany($webhookId, $companyId);
        if (!$webhook) {
            $this->redirect('api_webhooks');
            return;
        }

        $deliveries = $this->webhookModel->getDeliveries($webhookId, 50);

        $this->render('api/webhook-deliveries', [
            'webhook'    => $webhook,
            'deliveries' => $deliveries,
            'title'      => 'Webhook Deliveries — #' . $webhookId,
        ]);
    }

    /**
     * Plan upgrade page with comparison table
     */
    public function upgradePlan(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $plans = Subscription::PLANS;

        $this->render('api/upgrade', [
            'subscription' => $subscription,
            'plans'        => $plans,
            'title'        => 'Upgrade Plan',
        ]);
    }

    /**
     * Request plan upgrade (form submission)
     */
    public function requestUpgrade(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        if (!$subscription) {
            $this->redirect('api_dashboard');
            return;
        }

        $newPlan = $this->inputStr('plan');
        $validPlans = ['starter', 'professional', 'enterprise'];
        if (!in_array($newPlan, $validPlans)) {
            $this->redirect('api_upgrade', ['error' => 'invalid_plan']);
            return;
        }

        // Check that the new plan is an upgrade
        $planOrder = ['trial' => 0, 'starter' => 1, 'professional' => 2, 'enterprise' => 3];
        $currentLevel = $planOrder[$subscription['plan']] ?? 0;
        $newLevel = $planOrder[$newPlan] ?? 0;

        if ($newLevel <= $currentLevel) {
            $this->redirect('api_upgrade', ['error' => 'not_upgrade']);
            return;
        }

        // Self-service upgrade: change plan immediately
        $this->subscriptionModel->changePlan(intval($subscription['id']), $newPlan);

        // Send notification email
        $this->sendUpgradeEmail($companyId, $subscription['plan'], $newPlan);

        $this->redirect('api_dashboard', ['upgraded' => $newPlan]);
    }

    /**
     * API invoices page
     */
    public function invoices(): void
    {
        $this->requireLevel(0);
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $this->invoiceModel->ensureMonthlyInvoice($companyId, $subscription);
        $invoices = $this->invoiceModel->getByCompanyId($companyId, 24);

        $this->render('api/invoices', [
            'subscription' => $subscription,
            'invoices'     => $invoices,
            'title'        => 'API Invoices',
        ]);
    }

    /**
     * Generate current invoice now (manual trigger)
     */
    public function generateInvoice(): void
    {
        $this->requireLevel(0);
        $this->verifyCsrf();
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $this->invoiceModel->ensureMonthlyInvoice($companyId, $subscription, true);

        $this->redirect('api_invoices', ['generated' => 1]);
    }

    /**
     * API Documentation page (public-facing)
     */
    public function docs(): void
    {
        $this->requireLevel(0);
        $this->render('api/docs', [
            'title' => 'API Documentation',
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function requireLevel(int $minLevel): void
    {
        if ($this->user['level'] < $minLevel) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied. Required level: ' . $minLevel);
        }
    }

    /**
     * Send email notification for order events
     */
    public function sendOrderEmail(int $companyId, array $order, string $event): void
    {
        // Get company email
        $cid = \sql_int($companyId);
        $sql = "SELECT email, name_en FROM company WHERE id = '$cid' LIMIT 1";
        $result = mysqli_query($this->orderModel->getConnection(), $sql);
        if (!$result) return;
        $company = mysqli_fetch_assoc($result);
        if (!$company || empty($company['email'])) return;

        $to = $company['email'];
        $statusColors = ['pending' => '⏳', 'completed' => '✅', 'failed' => '❌', 'cancelled' => '🚫'];
        $icon = $statusColors[$order['status']] ?? '📋';

        switch ($event) {
            case 'order.created':
                $subject = "New Channel Order #{$order['id']} — {$order['guest_name']}";
                $body = "A new order has been received via the {$order['channel']} channel.\n\n";
                break;
            case 'order.completed':
                $subject = "Order #{$order['id']} Completed — {$order['guest_name']}";
                $body = "An order has been successfully processed.\n\n";
                break;
            case 'order.failed':
                $subject = "⚠️ Order #{$order['id']} Failed — {$order['guest_name']}";
                $body = "An order has failed processing. Please review.\n\n";
                break;
            default:
                $subject = "Order #{$order['id']} Updated — {$order['guest_name']}";
                $body = "An order status has been updated.\n\n";
        }

        $body .= "Order Details:\n";
        $body .= "—————————————————\n";
        $body .= "Order ID:    #{$order['id']}\n";
        $body .= "Guest:       {$order['guest_name']}\n";
        $body .= "Channel:     {$order['channel']}\n";
        $body .= "Status:      {$icon} {$order['status']}\n";
        $body .= "Amount:      ฿" . number_format(floatval($order['total_amount']), 2) . "\n";
        if (!empty($order['check_in'])) $body .= "Check-in:    {$order['check_in']}\n";
        if (!empty($order['check_out'])) $body .= "Check-out:   {$order['check_out']}\n";
        if (!empty($order['room_type'])) $body .= "Room:        {$order['room_type']}\n";
        $body .= "—————————————————\n\n";
        $body .= "View order: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/index.php?page=api_order_detail&id={$order['id']}\n";
        $body .= "\nThis is an automated notification from iACC Sales Channel API.\n";

        $headers = "From: iACC Notifications <noreply@iacc.local>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($to, $subject, $body, $headers);
    }

    /**
     * Send upgrade notification email
     */
    private function sendUpgradeEmail(int $companyId, string $oldPlan, string $newPlan): void
    {
        $cid = \sql_int($companyId);
        $sql = "SELECT email, name_en FROM company WHERE id = '$cid' LIMIT 1";
        $result = mysqli_query($this->orderModel->getConnection(), $sql);
        if (!$result) return;
        $company = mysqli_fetch_assoc($result);
        if (!$company || empty($company['email'])) return;

        $planConfig = Subscription::PLANS[$newPlan] ?? [];
        $subject = "🎉 Plan Upgraded to " . ucfirst($newPlan);
        $body = "Hi {$company['name_en']},\n\n";
        $body .= "Your Sales Channel API plan has been upgraded from " . ucfirst($oldPlan) . " to " . ucfirst($newPlan) . "!\n\n";
        $body .= "New Plan Details:\n";
        $body .= "—————————————————\n";
        $body .= "Plan:          " . ucfirst($newPlan) . "\n";
        $body .= "Orders/month:  " . number_format($planConfig['orders_limit'] ?? 0) . "\n";
        $body .= "API Keys:      " . ($planConfig['keys_limit'] ?? 0) . "\n";
        $body .= "Channels:      " . ($planConfig['channels'] ?? '') . "\n";
        $body .= "Duration:      " . ($planConfig['duration_days'] ?? 0) . " days\n";
        $body .= "—————————————————\n\n";
        $body .= "Your new limits are effective immediately.\n\n";
        $body .= "Thank you for choosing iACC!\n";

        $headers = "From: iACC Notifications <noreply@iacc.local>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        @mail($company['email'], $subject, $body, $headers);
    }

}
