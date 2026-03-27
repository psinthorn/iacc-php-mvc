<?php
namespace App\Models;

/**
 * ApiKey Model
 * 
 * Manages api_keys table.
 * Handles key generation, validation, and lookup.
 */
class ApiKey extends BaseModel
{
    protected string $table = 'api_keys';
    protected bool $useCompanyFilter = false;

    /**
     * Generate a cryptographically secure random key
     */
    public static function generateKey(string $prefix = 'iACC'): string
    {
        return $prefix . '_' . bin2hex(random_bytes(24));
    }

    /**
     * Generate a cryptographically secure secret
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a new API key for a company
     */
    public function createKey(int $companyId, int $subscriptionId, string $name = 'Default'): ?array
    {
        $apiKey = self::generateKey();
        $apiSecret = self::generateSecret();

        $data = [
            'company_id'      => $companyId,
            'subscription_id' => $subscriptionId,
            'key_name'        => $name,
            'api_key'         => $apiKey,
            'api_secret'      => $apiSecret,
            'is_active'       => 1,
        ];

        $id = $this->hard->insertSafe($this->table, $data);
        if ($id) {
            return [
                'id'         => $id,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
                'key_name'   => $name,
            ];
        }
        return null;
    }

    /**
     * Authenticate an API request using key + secret
     * Returns the key record with subscription + company info, or null
     */
    public function authenticate(string $apiKey, string $apiSecret): ?array
    {
        $key = \sql_escape($apiKey);
        $secret = \sql_escape($apiSecret);

        $sql = "SELECT k.*, s.plan, s.status as sub_status, s.bookings_limit, s.keys_limit,
                       s.channels, s.ai_providers, s.trial_end, s.expires_at, s.enabled,
                       c.name_en as company_name
                FROM `{$this->table}` k
                JOIN `api_subscriptions` s ON k.subscription_id = s.id
                JOIN `company` c ON k.company_id = c.id
                WHERE k.api_key = '$key' 
                AND k.api_secret = '$secret'
                AND k.is_active = 1";

        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            // Update last_used_at
            $this->touchLastUsed($row['id']);

            return $row;
        }
        return null;
    }

    /**
     * Update last_used_at timestamp
     */
    private function touchLastUsed(int $id): void
    {
        $sql = "UPDATE `{$this->table}` SET `last_used_at` = NOW() WHERE `id` = '" . \sql_int($id) . "'";
        mysqli_query($this->conn, $sql);
    }

    /**
     * Get all keys for a company
     */
    public function getByCompanyId(int $companyId): array
    {
        $id = \sql_int($companyId);
        $sql = "SELECT k.*, s.plan 
                FROM `{$this->table}` k 
                JOIN `api_subscriptions` s ON k.subscription_id = s.id 
                WHERE k.company_id = '$id' 
                ORDER BY k.created_at DESC";
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
     * Get all keys for a subscription
     */
    public function getBySubscriptionId(int $subscriptionId): array
    {
        $id = \sql_int($subscriptionId);
        $sql = "SELECT * FROM `{$this->table}` WHERE `subscription_id` = '$id' ORDER BY `created_at` DESC";
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
     * Count active keys for a subscription
     */
    public function countActiveKeys(int $subscriptionId): int
    {
        $id = \sql_int($subscriptionId);
        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}` WHERE `subscription_id` = '$id' AND `is_active` = 1";
        $result = mysqli_query($this->conn, $sql);
        return $result ? intval(mysqli_fetch_assoc($result)['cnt']) : 0;
    }

    /**
     * Revoke (deactivate) a key
     */
    public function revoke(int $id): bool
    {
        $where = ['id' => $id];
        return $this->hard->updateSafe($this->table, ['is_active' => 0], $where);
    }

    /**
     * Reactivate a key
     */
    public function activate(int $id): bool
    {
        $where = ['id' => $id];
        return $this->hard->updateSafe($this->table, ['is_active' => 1], $where);
    }

    /**
     * Mask secret for display (show first 8 and last 4 chars)
     */
    public static function maskSecret(string $secret): string
    {
        if (strlen($secret) <= 12) return '****';
        return substr($secret, 0, 8) . '...' . substr($secret, -4);
    }
}
