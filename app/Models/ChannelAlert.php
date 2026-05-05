<?php
namespace App\Models;

/**
 * ChannelAlert — alert state machine for v6.2 #83.
 *
 * Owns the channel_alerts table. State transitions:
 *   open ────► acknowledged ────► resolved (auto when channel comes back)
 *        └────────────────────► resolved (auto when channel comes back)
 *
 * Email is sent only on transitions:
 *   - on initial open  → "Alert: channel X is down"
 *   - on resolved      → "Resolved: channel X is back online"
 * Sustained downtime does NOT re-alert (alert_email_sent_count caps at 2).
 */
class ChannelAlert
{
    public const STATUS_OPEN         = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED     = 'resolved';

    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Find the currently-active alert (open OR acknowledged) for a channel,
     * or null if no active alert exists.
     */
    public function activeFor(int $companyId, string $channelType): ?array
    {
        $channelType = mysqli_real_escape_string($this->conn, $channelType);
        $sql = "SELECT *
                  FROM channel_alerts
                 WHERE company_id = $companyId
                   AND channel_type = '$channelType'
                   AND status IN ('open','acknowledged')
                 ORDER BY opened_at DESC
                 LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) {
            error_log('[ChannelAlert::activeFor] ' . mysqli_error($this->conn));
            return null;
        }
        $row = mysqli_fetch_assoc($res);
        return $row ?: null;
    }

    /**
     * Open a new alert. Caller must verify no active alert exists first
     * (use activeFor() — DON'T duplicate).
     */
    public function open(int $companyId, string $channelType, ?string $channelRef, ?string $lastError): int
    {
        $channelType = mysqli_real_escape_string($this->conn, $channelType);
        $channelRefSql = $channelRef !== null ? "'" . mysqli_real_escape_string($this->conn, $channelRef) . "'" : 'NULL';
        $lastErrorSql  = $lastError !== null
            ? "'" . mysqli_real_escape_string($this->conn, mb_substr($lastError, 0, 1024)) . "'"
            : 'NULL';

        $sql = "INSERT INTO channel_alerts
                  (company_id, channel_type, channel_ref, status, opened_at, last_error)
                VALUES
                  ($companyId, '$channelType', $channelRefSql, 'open', NOW(), $lastErrorSql)";

        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelAlert::open] ' . mysqli_error($this->conn));
            return 0;
        }
        return intval(mysqli_insert_id($this->conn));
    }

    /**
     * Mark alert acknowledged (admin clicked the button).
     * Suppresses repeat alerts within the same downtime episode.
     */
    public function acknowledge(int $alertId, int $userId): bool
    {
        $sql = "UPDATE channel_alerts
                   SET status = 'acknowledged',
                       acknowledged_at = NOW(),
                       acknowledged_by = $userId
                 WHERE id = $alertId
                   AND status = 'open'";
        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelAlert::acknowledge] ' . mysqli_error($this->conn));
            return false;
        }
        return mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Resolve alert (channel came back up). Auto-fired by handler.
     */
    public function resolve(int $alertId): bool
    {
        $sql = "UPDATE channel_alerts
                   SET status = 'resolved',
                       resolved_at = NOW()
                 WHERE id = $alertId
                   AND status IN ('open','acknowledged')";
        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelAlert::resolve] ' . mysqli_error($this->conn));
            return false;
        }
        return mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Increment email-sent counter — used to cap notifications at 2 per episode
     * (1 on open, 1 on resolved). Returns new count.
     */
    public function bumpEmailSentCount(int $alertId): int
    {
        $sql = "UPDATE channel_alerts
                   SET alert_email_sent_count = alert_email_sent_count + 1
                 WHERE id = $alertId";
        mysqli_query($this->conn, $sql);

        $sql2 = "SELECT alert_email_sent_count FROM channel_alerts WHERE id = $alertId";
        $res = mysqli_query($this->conn, $sql2);
        if (!$res) return 0;
        $row = mysqli_fetch_assoc($res);
        return intval($row['alert_email_sent_count'] ?? 0);
    }

    /**
     * Open alerts for the dashboard panel (all channels for one company).
     */
    public function listOpen(int $companyId): array
    {
        $sql = "SELECT *
                  FROM channel_alerts
                 WHERE company_id = $companyId
                   AND status IN ('open','acknowledged')
                 ORDER BY opened_at DESC";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }

    public function find(int $alertId, int $companyId): ?array
    {
        $sql = "SELECT * FROM channel_alerts
                 WHERE id = $alertId AND company_id = $companyId LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return null;
        $row = mysqli_fetch_assoc($res);
        return $row ?: null;
    }
}
