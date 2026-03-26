<?php
namespace App\Controllers;

use App\Models\ApiKey;
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
    private ChannelOrder $orderModel;
    private ApiUsageLog $usageLogModel;
    private Webhook $webhookModel;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new Subscription();
        $this->apiKeyModel = new ApiKey();
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

        $this->render('api/dashboard', [
            'subscription'   => $subscription,
            'stats'          => $stats,
            'recentOrders' => $recentOrders,
            'dailyUsage'     => $dailyUsage,
            'monthlyUsage'   => $usage,
            'webhookCount'   => $webhookCount,
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
}
