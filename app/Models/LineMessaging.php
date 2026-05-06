<?php
namespace App\Models;

/**
 * LineMessaging — v6.3 rich messaging operations
 *
 * Tables:
 * - line_message_templates: bilingual Flex/text templates per company
 * - line_broadcasts: campaign records
 * - line_broadcast_recipients: per-user delivery rows
 * - line_user_tags + line_user_tag_map: segmentation
 *
 * All queries are prepared statements and filter by company_id.
 */
class LineMessaging extends BaseModel
{
    // ====================================================================
    // Templates
    // ====================================================================

    public function getTemplates(int $companyId, bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM line_message_templates WHERE company_id = ? AND deleted_at IS NULL";
        if ($activeOnly) $sql .= " AND is_active = 1";
        $sql .= " ORDER BY template_type, name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getTemplate(int $id, int $companyId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_message_templates WHERE id = ? AND company_id = ? AND deleted_at IS NULL"
        );
        $stmt->bind_param('ii', $id, $companyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function saveTemplate(int $companyId, array $data, ?int $userId = null): int
    {
        $id           = (int)($data['id'] ?? 0);
        $name         = trim($data['name'] ?? '');
        $type         = $data['template_type'] ?? 'custom';
        $messageType  = $data['message_type'] ?? 'flex';
        $altText      = $data['alt_text'] ?? null;
        $contentTh    = $data['content_th'] ?? null;
        $contentEn    = $data['content_en'] ?? null;
        $variables    = $data['variables_json'] ?? null;
        $isActive     = (int)($data['is_active'] ?? 1);

        if ($id > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE line_message_templates
                 SET name=?, template_type=?, message_type=?, alt_text=?,
                     content_th=?, content_en=?, variables_json=?, is_active=?
                 WHERE id=? AND company_id=? AND deleted_at IS NULL"
            );
            $stmt->bind_param('sssssssiii',
                $name, $type, $messageType, $altText,
                $contentTh, $contentEn, $variables, $isActive,
                $id, $companyId
            );
            $stmt->execute();
            $stmt->close();
            return $id;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO line_message_templates
             (company_id, name, template_type, message_type, alt_text,
              content_th, content_en, variables_json, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('isssssssii',
            $companyId, $name, $type, $messageType, $altText,
            $contentTh, $contentEn, $variables, $isActive, $userId
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function deleteTemplate(int $id, int $companyId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE line_message_templates SET deleted_at = NOW() WHERE id = ? AND company_id = ?"
        );
        $stmt->bind_param('ii', $id, $companyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Render a template by substituting {variable} tokens with the supplied vars.
     * Returns ['alt_text' => ..., 'contents' => array (Flex JSON decoded) | 'text' => string]
     */
    public function renderTemplate(array $template, array $vars, bool $isThai): array
    {
        $body = $isThai
            ? ($template['content_th'] ?? $template['content_en'] ?? '')
            : ($template['content_en'] ?? $template['content_th'] ?? '');

        // Token replace — keep it simple and safe
        $rendered = $body;
        foreach ($vars as $k => $v) {
            $rendered = str_replace('{' . $k . '}', (string)$v, $rendered);
        }

        $altText = $template['alt_text'] ?? '';
        foreach ($vars as $k => $v) {
            $altText = str_replace('{' . $k . '}', (string)$v, $altText);
        }

        if (($template['message_type'] ?? 'flex') === 'text') {
            return ['type' => 'text', 'text' => $rendered, 'alt_text' => $altText];
        }

        $decoded = json_decode($rendered, true);
        return [
            'type'     => 'flex',
            'alt_text' => $altText,
            'contents' => $decoded ?: [],
        ];
    }

    // ====================================================================
    // Tags
    // ====================================================================

    public function getTags(int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT t.*, COUNT(m.id) AS user_count
             FROM line_user_tags t
             LEFT JOIN line_user_tag_map m ON m.tag_id = t.id
             WHERE t.company_id = ? AND t.deleted_at IS NULL
             GROUP BY t.id
             ORDER BY t.name"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function saveTag(int $companyId, string $name, string $color = '#3498db', ?string $description = null): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO line_user_tags (company_id, name, color, description) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isss', $companyId, $name, $color, $description);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function tagUser(int $companyId, int $lineUserId, int $tagId): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO line_user_tag_map (company_id, line_user_id, tag_id) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iii', $companyId, $lineUserId, $tagId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function untagUser(int $companyId, int $lineUserId, int $tagId): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM line_user_tag_map WHERE company_id = ? AND line_user_id = ? AND tag_id = ?"
        );
        $stmt->bind_param('iii', $companyId, $lineUserId, $tagId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ====================================================================
    // Audience resolution
    // ====================================================================

    /**
     * Resolve an audience filter into a list of line_users.id rows.
     * Used for both preview-count and actual recipient materialization.
     *
     * $audienceType in: all | tag | language | has_booked | last_active
     * $filter examples:
     *   ['tag_id' => 5]
     *   ['language' => 'th']
     *   ['days' => 30]   (last_active = interacted in last N days)
     */
    public function resolveAudience(int $companyId, string $audienceType, array $filter = [], int $limit = 100000): array
    {
        $base = "SELECT DISTINCT u.id, u.line_user_id
                 FROM line_users u
                 WHERE u.company_id = ?
                   AND u.deleted_at IS NULL
                   AND u.is_blocked = 0";

        $sql    = $base;
        $types  = 'i';
        $params = [$companyId];

        switch ($audienceType) {
            case 'tag':
                $tagId = (int)($filter['tag_id'] ?? 0);
                $sql .= " AND EXISTS (
                            SELECT 1 FROM line_user_tag_map m
                            WHERE m.line_user_id = u.id AND m.tag_id = ?
                          )";
                $types .= 'i';
                $params[] = $tagId;
                break;

            case 'language':
                // We don't track language per user yet — proxy via last inbound message charset.
                // For now, skip language filter and return all (TODO post-v6.3).
                break;

            case 'has_booked':
                $sql .= " AND EXISTS (
                            SELECT 1 FROM line_orders o
                            WHERE o.line_user_id = u.id AND o.deleted_at IS NULL
                              AND o.status IN ('confirmed','processing','completed')
                          )";
                break;

            case 'last_active':
                $days = max(1, (int)($filter['days'] ?? 30));
                $sql .= " AND u.last_interaction_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $types .= 'i';
                $params[] = $days;
                break;

            case 'all':
            default:
                // no extra filter
                break;
        }

        $sql .= " LIMIT ?";
        $types .= 'i';
        $params[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function audienceCount(int $companyId, string $audienceType, array $filter = []): int
    {
        return count($this->resolveAudience($companyId, $audienceType, $filter));
    }

    // ====================================================================
    // Broadcasts
    // ====================================================================

    public function getBroadcasts(int $companyId, int $limit = 50): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_broadcasts
             WHERE company_id = ? AND deleted_at IS NULL
             ORDER BY COALESCE(scheduled_at, created_at) DESC
             LIMIT ?"
        );
        $stmt->bind_param('ii', $companyId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getBroadcast(int $id, int $companyId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_broadcasts WHERE id = ? AND company_id = ? AND deleted_at IS NULL"
        );
        $stmt->bind_param('ii', $id, $companyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function saveBroadcast(int $companyId, array $data, ?int $userId = null): int
    {
        $id              = (int)($data['id'] ?? 0);
        $name            = trim($data['name'] ?? '');
        $audienceType    = $data['audience_type'] ?? 'all';
        $audienceFilter  = $data['audience_filter_json'] ?? null;
        $messageKind     = $data['message_kind'] ?? 'text';
        $templateId      = !empty($data['template_id']) ? (int)$data['template_id'] : null;
        $textTh          = $data['text_content_th'] ?? null;
        $textEn          = $data['text_content_en'] ?? null;
        $flexTh          = $data['flex_content_th'] ?? null;
        $flexEn          = $data['flex_content_en'] ?? null;
        $altText         = $data['alt_text'] ?? null;
        $scheduledAt     = $data['scheduled_at'] ?? null;
        $status          = $data['status'] ?? 'draft';

        if ($id > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE line_broadcasts
                 SET name=?, status=?, audience_type=?, audience_filter_json=?,
                     message_kind=?, template_id=?, text_content_th=?, text_content_en=?,
                     flex_content_th=?, flex_content_en=?, alt_text=?, scheduled_at=?
                 WHERE id=? AND company_id=? AND deleted_at IS NULL"
            );
            $stmt->bind_param('sssssissssssii',
                $name, $status, $audienceType, $audienceFilter,
                $messageKind, $templateId, $textTh, $textEn,
                $flexTh, $flexEn, $altText, $scheduledAt,
                $id, $companyId
            );
            $stmt->execute();
            $stmt->close();
            return $id;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO line_broadcasts
             (company_id, name, status, audience_type, audience_filter_json,
              message_kind, template_id, text_content_th, text_content_en,
              flex_content_th, flex_content_en, alt_text, scheduled_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // 14 params: company_id(i), name(s), status(s), audience_type(s),
        // audience_filter(s), message_kind(s), template_id(i),
        // text_th(s), text_en(s), flex_th(s), flex_en(s), alt_text(s),
        // scheduled_at(s), created_by(i)
        $stmt->bind_param(
            'issssisssssssi',
            $companyId, $name, $status, $audienceType, $audienceFilter,
            $messageKind, $templateId,
            $textTh, $textEn, $flexTh, $flexEn,
            $altText, $scheduledAt, $userId
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    /**
     * Materialize recipients for a broadcast (one row per line_user) from the audience filter.
     * Returns count of rows inserted.
     */
    public function materializeRecipients(int $broadcastId, int $companyId): int
    {
        $b = $this->getBroadcast($broadcastId, $companyId);
        if (!$b) return 0;

        $filter = json_decode($b['audience_filter_json'] ?? '[]', true) ?: [];
        $audience = $this->resolveAudience($companyId, $b['audience_type'], $filter);

        $count = 0;
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO line_broadcast_recipients (company_id, broadcast_id, line_user_id) VALUES (?, ?, ?)"
        );
        foreach ($audience as $u) {
            $uid = (int)$u['id'];
            $stmt->bind_param('iii', $companyId, $broadcastId, $uid);
            if ($stmt->execute()) $count++;
        }
        $stmt->close();

        $this->conn->query(
            "UPDATE line_broadcasts SET recipient_count = $count WHERE id = $broadcastId AND company_id = $companyId"
        );

        return $count;
    }

    /**
     * Pull a chunk of pending recipients for a broadcast.
     * Returns rows with line_user_id (LINE userId string) for the multicast call.
     */
    public function getPendingRecipientChunk(int $broadcastId, int $companyId, int $chunkSize = 500): array
    {
        $stmt = $this->conn->prepare(
            "SELECT r.id AS recipient_row_id, r.line_user_id AS db_user_id, u.line_user_id
             FROM line_broadcast_recipients r
             JOIN line_users u ON u.id = r.line_user_id
             WHERE r.broadcast_id = ? AND r.company_id = ? AND r.status = 'pending'
             LIMIT ?"
        );
        $stmt->bind_param('iii', $broadcastId, $companyId, $chunkSize);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function markRecipientsSent(array $recipientRowIds, int $companyId): void
    {
        if (empty($recipientRowIds)) return;
        $ids = implode(',', array_map('intval', $recipientRowIds));
        $companyId = (int)$companyId;
        $this->conn->query(
            "UPDATE line_broadcast_recipients
             SET status='sent', sent_at=NOW()
             WHERE id IN ($ids) AND company_id = $companyId"
        );
    }

    public function markRecipientsFailed(array $recipientRowIds, int $companyId, string $error): void
    {
        if (empty($recipientRowIds)) return;
        $ids = implode(',', array_map('intval', $recipientRowIds));
        $companyId = (int)$companyId;
        $stmt = $this->conn->prepare(
            "UPDATE line_broadcast_recipients
             SET status='failed', error_message=?
             WHERE id IN ($ids) AND company_id = ?"
        );
        $stmt->bind_param('si', $error, $companyId);
        $stmt->execute();
        $stmt->close();
    }

    public function updateBroadcastStatus(int $broadcastId, int $companyId, string $status, array $extra = []): bool
    {
        $sets = ['status = ?'];
        $types = 's';
        $params = [$status];

        if (array_key_exists('sent_count', $extra)) {
            $sets[] = 'sent_count = ?';
            $types .= 'i';
            $params[] = (int)$extra['sent_count'];
        }
        if (array_key_exists('failed_count', $extra)) {
            $sets[] = 'failed_count = ?';
            $types .= 'i';
            $params[] = (int)$extra['failed_count'];
        }
        if (array_key_exists('last_error', $extra)) {
            $sets[] = 'last_error = ?';
            $types .= 's';
            $params[] = $extra['last_error'];
        }
        if (!empty($extra['mark_started'])) {
            $sets[] = 'sent_started_at = NOW()';
        }
        if (!empty($extra['mark_completed'])) {
            $sets[] = 'sent_completed_at = NOW()';
        }

        $sql = "UPDATE line_broadcasts SET " . implode(', ', $sets) .
               " WHERE id = ? AND company_id = ?";
        $types .= 'ii';
        $params[] = $broadcastId;
        $params[] = $companyId;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Find broadcasts due to be sent (status=scheduled AND scheduled_at <= NOW()).
     * Used by the cron worker. Cross-tenant — caller must respect.
     */
    public function findDueBroadcasts(int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM line_broadcasts
             WHERE status = 'scheduled'
               AND scheduled_at IS NOT NULL
               AND scheduled_at <= NOW()
               AND deleted_at IS NULL
             ORDER BY scheduled_at ASC
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Sum sent_count across this month's broadcasts for quota tracking.
     */
    public function getMonthlyBroadcastUsage(int $companyId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(sent_count),0) AS used
             FROM line_broadcasts
             WHERE company_id = ?
               AND deleted_at IS NULL
               AND DATE_FORMAT(COALESCE(sent_completed_at, created_at), '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['used'] ?? 0);
    }
}
