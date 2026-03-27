<?php
namespace App\Models;

/**
 * Subscription Model
 * 
 * Manages api_subscriptions table.
 * One subscription per company — tracks plan, limits, and expiry.
 */
class Subscription extends BaseModel
{
    protected string $table = 'api_subscriptions';
    protected bool $useCompanyFilter = false;

    /** Plan configuration defaults */
    const PLANS = [
        'trial' => [
            'bookings_limit' => 50,
            'keys_limit'     => 1,
            'channels'       => 'website',
            'ai_providers'   => 'ollama',
            'duration_days'  => 14,
        ],
        'starter' => [
            'bookings_limit' => 500,
            'keys_limit'     => 3,
            'channels'       => 'website,email',
            'ai_providers'   => 'ollama,openai',
            'duration_days'  => 30,
        ],
        'professional' => [
            'bookings_limit' => 5000,
            'keys_limit'     => 10,
            'channels'       => 'website,email,line,facebook,manual',
            'ai_providers'   => 'ollama,openai,claude,gemini',
            'duration_days'  => 30,
        ],
        'enterprise' => [
            'bookings_limit' => 999999,
            'keys_limit'     => 999,
            'channels'       => 'website,email,line,facebook,manual',
            'ai_providers'   => 'ollama,openai,claude,gemini',
            'duration_days'  => 365,
        ],
    ];

    /**
     * Get subscription by company ID
     */
    public function getByCompanyId(int $companyId): ?array
    {
        $id = \sql_int($companyId);
        $sql = "SELECT * FROM `{$this->table}` WHERE `company_id` = '$id'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Create a trial subscription for a company
     */
    public function createTrial(int $companyId): int
    {
        $plan = self::PLANS['trial'];
        $now = date('Y-m-d');
        $trialEnd = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));

        $data = [
            'company_id'     => $companyId,
            'plan'           => 'trial',
            'status'         => 'active',
            'bookings_limit' => $plan['bookings_limit'],
            'keys_limit'     => $plan['keys_limit'],
            'channels'       => $plan['channels'],
            'ai_providers'   => $plan['ai_providers'],
            'trial_start'    => $now,
            'trial_end'      => $trialEnd,
            'enabled'        => 1,
        ];

        return $this->hard->insertSafe($this->table, $data);
    }

    /**
     * Upgrade/change subscription plan
     */
    public function changePlan(int $subscriptionId, string $plan): bool
    {
        if (!isset(self::PLANS[$plan])) {
            return false;
        }

        $config = self::PLANS[$plan];
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$config['duration_days']} days"));

        $data = [
            'plan'           => $plan,
            'status'         => 'active',
            'bookings_limit' => $config['bookings_limit'],
            'keys_limit'     => $config['keys_limit'],
            'channels'       => $config['channels'],
            'ai_providers'   => $config['ai_providers'],
            'started_at'     => $now,
            'expires_at'     => $expiresAt,
        ];

        $where = ['id' => $subscriptionId];
        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Check if subscription is active and not expired
     */
    public function isActive(array $subscription): bool
    {
        if ($subscription['status'] !== 'active' || !$subscription['enabled']) {
            return false;
        }

        // Check trial expiry
        if ($subscription['plan'] === 'trial' && $subscription['trial_end']) {
            return strtotime($subscription['trial_end']) >= strtotime(date('Y-m-d'));
        }

        // Check paid plan expiry
        if ($subscription['expires_at']) {
            return strtotime($subscription['expires_at']) >= time();
        }

        return true;
    }

    /**
     * Get bookings used this month for a company
     */
    public function getMonthlyUsage(int $companyId): int
    {
        $id = \sql_int($companyId);
        $monthStart = date('Y-m-01');
        $sql = "SELECT COUNT(*) as cnt FROM `booking_requests` 
                WHERE `company_id` = '$id' 
                AND `created_at` >= '$monthStart'
                AND `status` != 'failed'";
        $result = mysqli_query($this->conn, $sql);
        return $result ? intval(mysqli_fetch_assoc($result)['cnt']) : 0;
    }

    /**
     * Check if company has quota remaining
     */
    public function hasQuota(int $companyId, array $subscription): bool
    {
        $used = $this->getMonthlyUsage($companyId);
        return $used < $subscription['bookings_limit'];
    }

    /**
     * Check if channel is allowed for this subscription
     */
    public function isChannelAllowed(array $subscription, string $channel): bool
    {
        $allowed = explode(',', $subscription['channels']);
        return in_array($channel, $allowed);
    }

    /**
     * Get all subscriptions with company info (admin view)
     */
    public function getAllWithCompany(string $search = '', int $page = 1, int $perPage = 15): array
    {
        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = "AND (c.name_en LIKE '%$escaped%' OR c.name_th LIKE '%$escaped%' OR s.plan LIKE '%$escaped%')";
        }

        // Count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` s 
                     JOIN `company` c ON s.company_id = c.id 
                     WHERE 1=1 $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);

        // Fetch
        $sql = "SELECT s.*, c.name_en, c.name_th, c.email,
                (SELECT COUNT(*) FROM api_keys k WHERE k.subscription_id = s.id AND k.is_active = 1) as active_keys,
                (SELECT COUNT(*) FROM booking_requests b WHERE b.company_id = s.company_id AND b.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as monthly_bookings
                FROM `{$this->table}` s 
                JOIN `company` c ON s.company_id = c.id 
                WHERE 1=1 $searchCond
                ORDER BY s.created_at DESC 
                LIMIT {$pagination['offset']}, $perPage";
        
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'pagination' => $pagination,
        ];
    }

    /**
     * Toggle enabled/disabled (Super Admin only)
     */
    public function toggleEnabled(int $id): bool
    {
        $sql = "UPDATE `{$this->table}` SET `enabled` = NOT `enabled` WHERE `id` = '" . \sql_int($id) . "'";
        return (bool)mysqli_query($this->conn, $sql);
    }
}
