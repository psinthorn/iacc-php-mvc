<?php
namespace App\Models;

/**
 * ChannelHealthLog — heartbeat log row writer + dashboard reader (v6.2 #83).
 *
 * Owns the channel_health_log table. Writes are append-only (no UPDATE / DELETE
 * except the 30-day retention sweep). Reads serve the admin dashboard:
 *   - last N rows for the timeline
 *   - last 5 rows per (company, channel) for the consecutive-failure rule
 *   - aggregated stats for the response-time chart
 */
class ChannelHealthLog
{
    private \mysqli $conn;

    public const STATUS_SUCCESS         = 'success';
    public const STATUS_FAILURE         = 'failure';
    public const STATUS_NOT_CONFIGURED  = 'not_configured';

    /** Channels supported in v6.2 MVP (#83). */
    public const CHANNELS = ['line_oa', 'sales_channel_api', 'outbound_webhook', 'email_smtp'];

    /** Consecutive failures that open an alert (matches PM spec AC3). */
    public const FAILURE_THRESHOLD = 5;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Append one heartbeat row.
     *
     * @param array $row [
     *   'company_id'    => int,
     *   'channel_type'  => string (one of CHANNELS),
     *   'channel_ref'   => ?string,
     *   'status'        => string (one of STATUS_*),
     *   'response_ms'   => ?int,
     *   'error_message' => ?string,
     * ]
     * @return int inserted id (0 on failure — caller checks mysqli_error)
     */
    public function insert(array $row): int
    {
        $companyId   = intval($row['company_id']);
        $channelType = mysqli_real_escape_string($this->conn, $row['channel_type']);
        $channelRef  = isset($row['channel_ref'])  ? "'" . mysqli_real_escape_string($this->conn, $row['channel_ref'])  . "'" : 'NULL';
        $status      = mysqli_real_escape_string($this->conn, $row['status']);
        $respMs      = $row['response_ms'] !== null ? intval($row['response_ms']) : 'NULL';
        $errMsg      = isset($row['error_message']) && $row['error_message'] !== null
            ? "'" . mysqli_real_escape_string($this->conn, mb_substr($row['error_message'], 0, 1024)) . "'"
            : 'NULL';

        $sql = "INSERT INTO channel_health_log
                  (company_id, channel_type, channel_ref, status, response_ms, error_message)
                VALUES
                  ($companyId, '$channelType', $channelRef, '$status', $respMs, $errMsg)";

        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelHealthLog::insert] ' . mysqli_error($this->conn));
            return 0;
        }
        return intval(mysqli_insert_id($this->conn));
    }

    /**
     * Fetch the last N heartbeats for one (company, channel) — used to evaluate
     * whether the channel is down (5 consecutive failures = open alert).
     *
     * Returns rows newest-first.
     */
    public function recentFor(int $companyId, string $channelType, int $limit = 5): array
    {
        $channelType = mysqli_real_escape_string($this->conn, $channelType);
        $limit = max(1, min($limit, 100));

        $sql = "SELECT id, status, response_ms, error_message, checked_at
                  FROM channel_health_log
                 WHERE company_id = $companyId
                   AND channel_type = '$channelType'
                 ORDER BY checked_at DESC, id DESC
                 LIMIT $limit";

        $res = mysqli_query($this->conn, $sql);
        if (!$res) {
            error_log('[ChannelHealthLog::recentFor] ' . mysqli_error($this->conn));
            return [];
        }
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }

    /**
     * Are the last 5 heartbeats all failures? (PM spec AC3 trigger.)
     */
    public function hasConsecutiveFailures(int $companyId, string $channelType, int $threshold = self::FAILURE_THRESHOLD): bool
    {
        $rows = $this->recentFor($companyId, $channelType, $threshold);
        if (count($rows) < $threshold) return false;
        foreach ($rows as $r) {
            if ($r['status'] !== self::STATUS_FAILURE) return false;
        }
        return true;
    }

    /**
     * Latest row per channel for the dashboard status grid.
     * Returns assoc array keyed by channel_type → row (or null if no data).
     */
    public function latestPerChannel(int $companyId): array
    {
        $out = array_fill_keys(self::CHANNELS, null);

        // Subquery picks max(checked_at) per channel; outer select grabs the row.
        // Avoids window functions (MySQL 5.7 compat).
        $sql = "SELECT chl.*
                  FROM channel_health_log chl
                  JOIN (
                      SELECT channel_type, MAX(checked_at) AS max_at
                        FROM channel_health_log
                       WHERE company_id = $companyId
                       GROUP BY channel_type
                  ) latest
                    ON chl.channel_type = latest.channel_type
                   AND chl.checked_at   = latest.max_at
                 WHERE chl.company_id = $companyId";

        $res = mysqli_query($this->conn, $sql);
        if (!$res) {
            error_log('[ChannelHealthLog::latestPerChannel] ' . mysqli_error($this->conn));
            return $out;
        }
        while ($r = mysqli_fetch_assoc($res)) {
            $out[$r['channel_type']] = $r;
        }
        return $out;
    }

    /**
     * Last N rows across all channels for the dashboard timeline.
     */
    public function recentTimeline(int $companyId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $sql = "SELECT id, channel_type, channel_ref, status, response_ms, error_message, checked_at
                  FROM channel_health_log
                 WHERE company_id = $companyId
                 ORDER BY checked_at DESC, id DESC
                 LIMIT $limit";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }

    /**
     * 24h response time series for the chart, bucketed by hour and channel.
     * Returns rows: { channel_type, hour_bucket, avg_ms, max_ms, fail_count }.
     */
    public function chartLast24h(int $companyId): array
    {
        $sql = "SELECT
                    channel_type,
                    DATE_FORMAT(checked_at, '%Y-%m-%d %H:00:00') AS hour_bucket,
                    ROUND(AVG(response_ms))                      AS avg_ms,
                    MAX(response_ms)                             AS max_ms,
                    SUM(CASE WHEN status='failure' THEN 1 ELSE 0 END) AS fail_count
                  FROM channel_health_log
                 WHERE company_id = $companyId
                   AND checked_at >= NOW() - INTERVAL 24 HOUR
                 GROUP BY channel_type, hour_bucket
                 ORDER BY hour_bucket ASC, channel_type ASC";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return [];
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }

    /**
     * 30-day retention cleanup. Returns rows deleted.
     * Called by a daily cron task (placeholder for v6.3 #92).
     */
    public function pruneOlderThan30Days(): int
    {
        $sql = "DELETE FROM channel_health_log
                 WHERE checked_at < NOW() - INTERVAL 30 DAY
                 LIMIT 10000"; // chunk to avoid long lock
        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelHealthLog::prune] ' . mysqli_error($this->conn));
            return 0;
        }
        return mysqli_affected_rows($this->conn);
    }
}
