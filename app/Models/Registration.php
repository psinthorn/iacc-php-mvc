<?php
namespace App\Models;

/**
 * Registration Model — Self-registration and email verification
 * 
 * Handles: email verification tokens, user+company creation,
 * trial activation for self-registered users.
 */
class Registration extends BaseModel
{
    protected string $table = 'email_verifications';

    /**
     * Create a verification token for a new registration
     * 
     * @param string $email User email
     * @param array  $payload Registration data (name, password_hash, company_name)
     * @return string The verification token
     */
    public function createVerification(string $email, array $payload): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $payloadJson = json_encode($payload);

        // Invalidate any previous tokens for this email
        $stmt = $this->conn->prepare("UPDATE email_verifications SET expires_at = NOW() WHERE email = ? AND verified_at IS NULL");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();

        // Create new token
        $stmt = $this->conn->prepare(
            "INSERT INTO email_verifications (email, token, payload, expires_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $email, $token, $payloadJson, $expiresAt);
        $stmt->execute();
        $stmt->close();

        return $token;
    }

    /**
     * Verify a token and return the registration payload
     * 
     * @param string $token Verification token
     * @return array|null Payload data or null if invalid/expired
     */
    public function verifyToken(string $token): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, email, payload, expires_at FROM email_verifications 
             WHERE token = ? AND verified_at IS NULL AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        return [
            'id'      => (int) $row['id'],
            'email'   => $row['email'],
            'payload' => json_decode($row['payload'], true),
        ];
    }

    /**
     * Mark a verification token as used
     */
    public function markVerified(int $verificationId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE email_verifications SET verified_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param('i', $verificationId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Check if an email is already registered
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM authorize WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Check if a pending verification exists for this email
     */
    public function hasPendingVerification(string $email): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM email_verifications WHERE email = ? AND verified_at IS NULL AND expires_at > NOW()"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Create company + user + trial subscription in a transaction
     * 
     * @param string $email    User email
     * @param string $passHash Bcrypt password hash
     * @param string $name     User/company display name
     * @return array ['company_id' => int, 'user_id' => int, 'subscription_id' => int]
     */
    public function createAccount(string $email, string $passHash, string $name): array
    {
        $this->conn->begin_transaction();

        try {
            // 1. Create company
            $companyName = $name ?: explode('@', $email)[0];
            $now = date('Y-m-d H:i:s');

            $stmt = $this->conn->prepare(
                "INSERT INTO company (name_en, name_th, email, customer, vender, registered_via, created_at, updated_at) 
                 VALUES (?, ?, ?, 0, 0, 'self', ?, ?)"
            );
            $stmt->bind_param('sssss', $companyName, $companyName, $email, $now, $now);
            $stmt->execute();
            $companyId = $this->conn->insert_id;
            $stmt->close();

            // 2. Create user (level 0 = regular user, locked to company)
            $lang = 0; // English default
            $level = 0;
            $migrated = 1;
            $registeredVia = 'self';
            $stmt = $this->conn->prepare(
                "INSERT INTO authorize (email, password, level, company_id, lang, password_migrated, registered_via, email_verified_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('ssiiiis', $email, $passHash, $level, $companyId, $lang, $migrated, $registeredVia);
            $stmt->execute();
            $userId = $this->conn->insert_id;
            $stmt->close();

            // 3. Create trial subscription (14 days, 50 orders)
            $subscription = new Subscription();
            $subscriptionId = $subscription->createTrial($companyId);

            $this->conn->commit();

            return [
                'company_id'      => $companyId,
                'user_id'         => $userId,
                'subscription_id' => $subscriptionId,
            ];
        } catch (\Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Clean up expired, unverified tokens (housekeeping)
     */
    public function cleanExpired(): int
    {
        $result = $this->conn->query(
            "DELETE FROM email_verifications WHERE verified_at IS NULL AND expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        return $this->conn->affected_rows;
    }
}
