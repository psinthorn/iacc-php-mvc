<?php
namespace App\Models;

/**
 * LineOA Model — Database operations for LINE OA module
 * 
 * Handles all 6 LINE tables:
 * - line_oa_config: Channel configuration per company
 * - line_users: LINE user profiles
 * - line_messages: Message log (in/out)
 * - line_orders: Orders via LINE
 * - line_auto_replies: Auto-reply rules
 * - line_webhook_events: Raw webhook event log
 */
class LineOA extends BaseModel
{
    protected string $table = 'line_oa_config';

    // ========== Config ==========

    public function getConfig(int $companyId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_oa_config WHERE company_id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Persist the latest LINE channel probe result + cached bot info.
     * Called by LineOAController::probeConnection().
     */
    public function updateProbeResult(int $companyId, array $probe): bool
    {
        $name  = $probe['display_name'] ?? null;
        $pic   = $probe['picture_url']  ?? null;
        $basic = $probe['basic_id']     ?? null;
        $stat  = $probe['status']       ?? 'unknown';
        $err   = $probe['error']        ?? null;

        $stmt = $this->conn->prepare(
            "UPDATE line_oa_config
             SET bot_display_name = ?, bot_picture_url = ?, bot_basic_id = ?,
                 last_probe_at = NOW(), last_probe_status = ?, last_probe_error = ?
             WHERE company_id = ? AND deleted_at IS NULL"
        );
        $stmt->bind_param('sssssi', $name, $pic, $basic, $stat, $err, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function saveConfig(int $companyId, array $data): bool
    {
        $existing = $this->getConfig($companyId);

        if ($existing) {
            $stmt = $this->conn->prepare(
                "UPDATE line_oa_config SET channel_id = ?, channel_secret = ?, channel_access_token = ?,
                 webhook_url = ?, is_active = ?, greeting_message = ?, auto_reply_enabled = ?
                 WHERE company_id = ? AND deleted_at IS NULL"
            );
            $stmt->bind_param('ssssissi',
                $data['channel_id'], $data['channel_secret'], $data['channel_access_token'],
                $data['webhook_url'], $data['is_active'], $data['greeting_message'],
                $data['auto_reply_enabled'], $companyId
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO line_oa_config (company_id, channel_id, channel_secret, channel_access_token,
                 webhook_url, is_active, greeting_message, auto_reply_enabled)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issssisi',
                $companyId, $data['channel_id'], $data['channel_secret'], $data['channel_access_token'],
                $data['webhook_url'], $data['is_active'], $data['greeting_message'],
                $data['auto_reply_enabled']
            );
        }

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ========== LINE Users ==========

    public function findOrCreateLineUser(int $companyId, string $lineUserId, string $displayName = '', string $pictureUrl = '', string $userType = 'customer'): int
    {
        // Try to find existing
        $stmt = $this->conn->prepare(
            "SELECT id FROM line_users WHERE company_id = ? AND line_user_id = ? LIMIT 1"
        );
        $stmt->bind_param('is', $companyId, $lineUserId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            // Update last interaction
            $upd = $this->conn->prepare(
                "UPDATE line_users SET display_name = ?, picture_url = ?, last_interaction_at = NOW() WHERE id = ?"
            );
            $upd->bind_param('ssi', $displayName, $pictureUrl, $row['id']);
            $upd->execute();
            $upd->close();
            return (int) $row['id'];
        }

        // Create new
        $stmt = $this->conn->prepare(
            "INSERT INTO line_users (company_id, line_user_id, display_name, picture_url, user_type, last_interaction_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('issss', $companyId, $lineUserId, $displayName, $pictureUrl, $userType);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getLineUsers(int $companyId, ?string $userType = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM line_users WHERE company_id = ? AND deleted_at IS NULL";
        $types = 'i';
        $params = [$companyId];

        if ($userType) {
            $sql .= " AND user_type = ?";
            $types .= 's';
            $params[] = $userType;
        }

        $sql .= " ORDER BY last_interaction_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getLineUserById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM line_users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * v6.3 #120 — List LINE users with user_type='agent', joined to bound iACC user (if any).
     * Used by the agent-bindings admin page.
     */
    public function getAgentBindings(int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.line_user_id, u.display_name, u.picture_url, u.user_type,
                    u.linked_user_id, u.linked_at, u.linked_by,
                    a.email AS linked_email, a.name AS linked_name, a.level AS linked_level
             FROM line_users u
             LEFT JOIN authorize a ON a.id = u.linked_user_id AND a.company_id = u.company_id
             WHERE u.company_id = ? AND u.deleted_at IS NULL
             ORDER BY (u.user_type = 'agent') DESC, u.display_name ASC"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * v6.3 #120 — List iACC users in this company eligible to be bound as agents.
     */
    public function getEligibleIaccUsers(int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, level FROM authorize
             WHERE company_id = ? ORDER BY level DESC, name ASC"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * v6.3 #120 — Bind a LINE user (must be user_type='agent') to an iACC user.
     * Returns true on success, false if either user belongs to a different company
     * or the LINE user is not an agent.
     */
    public function bindAgentToUser(int $companyId, int $lineUserDbId, int $iaccUserId, int $adminId): bool
    {
        // Verify both rows belong to this tenant + the LINE user is an agent
        $check = $this->conn->prepare(
            "SELECT
                (SELECT COUNT(*) FROM line_users WHERE id = ? AND company_id = ? AND user_type = 'agent' AND deleted_at IS NULL) AS lu_ok,
                (SELECT COUNT(*) FROM authorize  WHERE id = ? AND company_id = ?) AS au_ok"
        );
        $check->bind_param('iiii', $lineUserDbId, $companyId, $iaccUserId, $companyId);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        $check->close();
        if (!$row || (int)$row['lu_ok'] !== 1 || (int)$row['au_ok'] !== 1) return false;

        $stmt = $this->conn->prepare(
            "UPDATE line_users
             SET linked_user_id = ?, linked_at = NOW(), linked_by = ?
             WHERE id = ? AND company_id = ?"
        );
        $stmt->bind_param('iiii', $iaccUserId, $adminId, $lineUserDbId, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * v6.3 #120 — Remove an agent binding.
     */
    public function unbindAgent(int $companyId, int $lineUserDbId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE line_users
             SET linked_user_id = NULL, linked_at = NULL, linked_by = NULL
             WHERE id = ? AND company_id = ?"
        );
        $stmt->bind_param('ii', $lineUserDbId, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * v6.3 #120 — Lookup the bound iACC user id for a given LINE userId string.
     * Returns null if the LINE user isn't an agent or isn't bound.
     */
    public function getBoundIaccUserId(int $companyId, string $lineUserIdStr): ?int
    {
        $stmt = $this->conn->prepare(
            "SELECT linked_user_id FROM line_users
             WHERE company_id = ?
               AND line_user_id = ?
               AND user_type = 'agent'
               AND linked_user_id IS NOT NULL
               AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->bind_param('is', $companyId, $lineUserIdStr);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['linked_user_id'] : null;
    }

    public function getLineUserByLineId(int $companyId, string $lineUserId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM line_users WHERE company_id = ? AND line_user_id = ? LIMIT 1");
        $stmt->bind_param('is', $companyId, $lineUserId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // ========== Messages ==========

    public function logMessage(int $companyId, int $lineUserId, string $direction, string $messageType, ?string $messageId, ?string $replyToken, ?string $content, ?string $mediaUrl = null, string $status = 'received'): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO line_messages (company_id, line_user_id, direction, message_type, message_id, reply_token, content, media_url, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisssssss',
            $companyId, $lineUserId, $direction, $messageType,
            $messageId, $replyToken, $content, $mediaUrl, $status
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getMessages(int $companyId, ?int $lineUserId = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT m.*, u.display_name, u.line_user_id as line_uid
                FROM line_messages m
                JOIN line_users u ON m.line_user_id = u.id
                WHERE m.company_id = ?";
        $types = 'i';
        $params = [$companyId];

        if ($lineUserId) {
            $sql .= " AND m.line_user_id = ?";
            $types .= 'i';
            $params[] = $lineUserId;
        }

        $sql .= " ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    // ========== Orders ==========

    public function createOrder(int $companyId, int $lineUserId, array $data): int
    {
        $orderRef = 'LINE-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $orderType = $data['order_type'] ?? 'customer_order';
        $guestName = $data['guest_name'] ?? null;
        $guestPhone = $data['guest_phone'] ?? null;
        $guestEmail = $data['guest_email'] ?? null;
        $itemsJson = isset($data['items']) ? json_encode($data['items']) : null;
        $totalAmount = $data['total_amount'] ?? 0;
        $currency = $data['currency'] ?? 'THB';
        $notes = $data['notes'] ?? null;
        $bookingDate = $data['booking_date'] ?? null;
        $bookingTime = $data['booking_time'] ?? null;

        $stmt = $this->conn->prepare(
            "INSERT INTO line_orders (company_id, line_user_id, order_ref, order_type, guest_name, guest_phone,
             guest_email, items_json, total_amount, currency, notes, booking_date, booking_time)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iissssssdssss',
            $companyId, $lineUserId, $orderRef, $orderType, $guestName, $guestPhone,
            $guestEmail, $itemsJson, $totalAmount, $currency, $notes, $bookingDate, $bookingTime
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getOrders(int $companyId, ?string $status = null, ?string $orderType = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.display_name, u.line_user_id as line_uid
                FROM line_orders o
                JOIN line_users u ON o.line_user_id = u.id
                WHERE o.company_id = ? AND o.deleted_at IS NULL";
        $types = 'i';
        $params = [$companyId];

        if ($status) {
            $sql .= " AND o.status = ?";
            $types .= 's';
            $params[] = $status;
        }
        if ($orderType) {
            $sql .= " AND o.order_type = ?";
            $types .= 's';
            $params[] = $orderType;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getOrder(int $id, int $companyId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT o.*, u.display_name, u.line_user_id as line_uid
             FROM line_orders o
             JOIN line_users u ON o.line_user_id = u.id
             WHERE o.id = ? AND o.company_id = ? AND o.deleted_at IS NULL"
        );
        $stmt->bind_param('ii', $id, $companyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function updateOrderStatus(int $id, int $companyId, string $status, ?int $processedBy = null): bool
    {
        $sql = "UPDATE line_orders SET status = ?";
        $types = 's';
        $params = [$status];

        if ($processedBy) {
            $sql .= ", processed_by = ?, processed_at = NOW()";
            $types .= 'i';
            $params[] = $processedBy;
        }

        $sql .= " WHERE id = ? AND company_id = ?";
        $types .= 'ii';
        $params[] = $id;
        $params[] = $companyId;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function updatePaymentStatus(int $id, int $companyId, string $paymentStatus, ?string $slipUrl = null): bool
    {
        $sql = "UPDATE line_orders SET payment_status = ?";
        $types = 's';
        $params = [$paymentStatus];

        if ($slipUrl) {
            $sql .= ", payment_slip_url = ?";
            $types .= 's';
            $params[] = $slipUrl;
        }

        $sql .= " WHERE id = ? AND company_id = ?";
        $types .= 'ii';
        $params[] = $id;
        $params[] = $companyId;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Link a LINE order to iACC business records (PR + PO)
     */
    public function linkToBusinessRecords(int $id, int $companyId, int $prId, int $poId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE line_orders SET linked_pr_id = ?, linked_po_id = ? WHERE id = ? AND company_id = ?"
        );
        $stmt->bind_param('iiii', $prId, $poId, $id, $companyId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ========== Auto-Reply Rules ==========

    public function getAutoReplies(int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_auto_replies WHERE company_id = ? AND deleted_at IS NULL ORDER BY priority DESC, id ASC"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function saveAutoReply(int $companyId, array $data): int
    {
        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare(
                "UPDATE line_auto_replies SET trigger_keyword = ?, match_type = ?, reply_type = ?, reply_content = ?, is_active = ?, priority = ?
                 WHERE id = ? AND company_id = ?"
            );
            $stmt->bind_param('ssssiiii',
                $data['trigger_keyword'], $data['match_type'], $data['reply_type'],
                $data['reply_content'], $data['is_active'], $data['priority'],
                $data['id'], $companyId
            );
            $stmt->execute();
            $stmt->close();
            return (int) $data['id'];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO line_auto_replies (company_id, trigger_keyword, match_type, reply_type, reply_content, is_active, priority)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issssii',
            $companyId, $data['trigger_keyword'], $data['match_type'],
            $data['reply_type'], $data['reply_content'], $data['is_active'], $data['priority']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function deleteAutoReply(int $id, int $companyId): bool
    {
        $stmt = $this->conn->prepare("UPDATE line_auto_replies SET deleted_at = NOW() WHERE id = ? AND company_id = ?");
        $stmt->bind_param('ii', $id, $companyId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Find matching auto-reply for an incoming message
     */
    public function findMatchingReply(int $companyId, string $messageText): ?array
    {
        $rules = $this->getAutoReplies($companyId);

        foreach ($rules as $rule) {
            if (!$rule['is_active']) continue;

            $keyword = $rule['trigger_keyword'];
            $matched = false;

            switch ($rule['match_type']) {
                case 'exact':
                    $matched = (mb_strtolower($messageText) === mb_strtolower($keyword));
                    break;
                case 'contains':
                    $matched = (mb_stripos($messageText, $keyword) !== false);
                    break;
                case 'regex':
                    $matched = (preg_match('/' . $keyword . '/iu', $messageText) === 1);
                    break;
            }

            if ($matched) {
                return $rule;
            }
        }

        return null;
    }

    // ========== Webhook Events ==========

    public function logWebhookEvent(int $companyId, string $eventType, string $eventJson): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO line_webhook_events (company_id, event_type, event_json) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iss', $companyId, $eventType, $eventJson);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getWebhookEvents(int $companyId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_webhook_events WHERE company_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bind_param('iii', $companyId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    // ========== Dashboard Stats ==========

    public function getStats(int $companyId): array
    {
        $stats = [];

        // Total users
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_users WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['total_users'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Total orders
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_orders WHERE company_id = ? AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['total_orders'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Pending orders
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_orders WHERE company_id = ? AND status = 'pending' AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['pending_orders'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Today messages
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_messages WHERE company_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['today_messages'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Total revenue from completed orders
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM line_orders WHERE company_id = ? AND status = 'completed' AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['total_revenue'] = (float) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Confirmed orders
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_orders WHERE company_id = ? AND status = 'confirmed' AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['confirmed_orders'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Completed orders
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_orders WHERE company_id = ? AND status = 'completed' AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['completed_orders'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Total messages
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_messages WHERE company_id = ?");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['total_messages'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        // Linked orders (with PR/PO)
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM line_orders WHERE company_id = ? AND linked_po_id IS NOT NULL AND deleted_at IS NULL");
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['linked_orders'] = (int) $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        return $stats;
    }

    /**
     * Get daily message counts for the last 7 days
     */
    public function getDailyMessageStats(int $companyId, int $days = 7): array
    {
        $stmt = $this->conn->prepare(
            "SELECT DATE(created_at) as day,
                    SUM(direction = 'inbound') as inbound,
                    SUM(direction = 'outbound') as outbound,
                    COUNT(*) as total
             FROM line_messages
             WHERE company_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );
        $stmt->bind_param('ii', $companyId, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}
