<?php
namespace App\Controllers;

use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Booking;
use App\Models\Subscription;

/**
 * AdminApiController — Admin panel for managing Booking API
 * 
 * Super Admin (level >= 9):
 *   - View all subscriptions
 *   - Enable/disable subscriptions
 *   - Change plans
 * 
 * Company Admin (level >= 5):
 *   - View own subscription
 *   - Manage own API keys
 *   - View own bookings & usage logs
 */
class AdminApiController extends BaseController
{
    private Subscription $subscriptionModel;
    private ApiKey $apiKeyModel;
    private Booking $bookingModel;
    private ApiUsageLog $usageLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new Subscription();
        $this->apiKeyModel = new ApiKey();
        $this->bookingModel = new Booking();
        $this->usageLogModel = new ApiUsageLog();
    }

    /**
     * Subscription management page (Super Admin)
     */
    public function subscriptions(): void
    {
        $this->requireLevel(9);

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
        $this->requireLevel(9);
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
        $this->requireLevel(9);
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
        $this->requireLevel(5);
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
        $this->requireLevel(5);
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
        $this->requireLevel(5);
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
        $this->requireLevel(5);
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        if ($id > 0) {
            $this->apiKeyModel->revoke($id);
        }

        $this->redirect('api_keys');
    }

    /**
     * Bookings list page
     */
    public function bookings(): void
    {
        $this->requireLevel(5);
        $companyId = $this->getCompanyId();

        $filters = [
            'status'    => $this->inputStr('status'),
            'channel'   => $this->inputStr('channel'),
            'date_from' => $this->inputStr('date_from'),
            'date_to'   => $this->inputStr('date_to'),
            'search'    => $this->inputStr('search'),
        ];
        $page = $this->inputInt('p', 1);

        $result = $this->bookingModel->getForCompany($companyId, $filters, $page);
        $stats = $this->bookingModel->getStats($companyId);
        $subscription = $this->subscriptionModel->getByCompanyId($companyId);

        $this->render('api/bookings', [
            'bookings'     => $result['items'],
            'total'        => $result['total'],
            'pagination'   => $result['pagination'],
            'filters'      => $filters,
            'stats'        => $stats,
            'subscription' => $subscription,
            'title'        => 'API Bookings',
        ]);
    }

    /**
     * Usage logs page
     */
    public function usageLogs(): void
    {
        $this->requireLevel(5);
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
        $this->requireLevel(5);
        $companyId = $this->getCompanyId();

        $subscription = $this->subscriptionModel->getByCompanyId($companyId);
        $stats = $this->bookingModel->getStats($companyId);
        $recentBookings = $this->bookingModel->getRecent($companyId, 10);
        $dailyUsage = $this->usageLogModel->getDailySummary($companyId, 7);
        $usage = $subscription ? $this->subscriptionModel->getMonthlyUsage($companyId) : 0;

        $this->render('api/dashboard', [
            'subscription'   => $subscription,
            'stats'          => $stats,
            'recentBookings' => $recentBookings,
            'dailyUsage'     => $dailyUsage,
            'monthlyUsage'   => $usage,
            'title'          => 'Booking API Dashboard',
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
