<?php
namespace App\Controllers;

use App\Models\ChannelHealthLog;
use App\Models\ChannelAlert;

/**
 * ChannelHealthController — admin dashboard for v6.2 #83.
 *
 * Routes (registered in app/Config/routes.php):
 *   channel_health             GET  — main dashboard view
 *   channel_health_test_now    POST — AJAX: enqueue an off-schedule heartbeat for one channel
 *   channel_health_acknowledge POST — AJAX: mark an open alert as acknowledged
 *
 * Multi-tenant: every query filters by $this->user['com_id']. Super-admin
 * cross-tenant view is a deferred enhancement (PM spec out-of-scope).
 *
 * Security:
 *   - level >= 2 (admin) gate on every action
 *   - CSRF token verified on every POST
 *   - "Test now" rate-limited at the controller level (30s per channel per company)
 */
class ChannelHealthController extends BaseController
{
    private const ACCESS_DENIED_HTML =
        '<div class="alert alert-danger m-4"><i class="fa fa-lock"></i> Access denied. Admin privileges required.</div>';

    /** Rate limit window for the manual "Test now" button. */
    private const TEST_NOW_RATE_LIMIT_SECONDS = 30;

    /** GET — main dashboard view. */
    public function index(): void
    {
        if ($this->user['level'] < 2) {
            echo self::ACCESS_DENIED_HTML;
            return;
        }

        $comId = intval($this->user['com_id']);
        if ($comId <= 0) {
            echo '<div class="alert alert-warning m-4">Pick a company first.</div>';
            return;
        }

        $log    = new ChannelHealthLog($this->conn);
        $alerts = new ChannelAlert($this->conn);

        $latestPerChannel = $log->latestPerChannel($comId);
        $statusCards      = $this->buildStatusCards($comId, $latestPerChannel, $log);
        $openAlerts       = $alerts->listOpen($comId);
        $timeline         = $log->recentTimeline($comId, 100);
        $chartSeries      = $log->chartLast24h($comId);

        $flash = [
            'msg'  => $_GET['flash'] ?? '',
            'type' => $_GET['flash_type'] ?? 'success',
        ];

        $this->render('admin/channel-health/index', [
            'statusCards' => $statusCards,
            'openAlerts'  => $openAlerts,
            'timeline'    => $timeline,
            'chartSeries' => $chartSeries,
            'flash'       => $flash,
            'channels'    => ChannelHealthLog::CHANNELS,
        ]);
    }

    /** POST — enqueue off-schedule heartbeat for one channel. */
    public function testNow(): void
    {
        header('Content-Type: application/json');

        if ($this->user['level'] < 2) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'access_denied']);
            return;
        }
        $this->verifyCsrf();

        $comId   = intval($this->user['com_id']);
        $channel = $_POST['channel'] ?? '';
        if (!in_array($channel, ChannelHealthLog::CHANNELS, true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_channel']);
            return;
        }

        // Rate limit: don't let admins spam this. One test_now per channel per
        // company per 30 seconds.
        if ($this->isRateLimited($comId, $channel)) {
            http_response_code(429);
            echo json_encode([
                'ok'    => false,
                'error' => 'rate_limited',
                'retry_after_seconds' => self::TEST_NOW_RATE_LIMIT_SECONDS,
            ]);
            return;
        }

        $payload = json_encode([
            'channels'    => [$channel],
            'triggered_by' => 'admin_test_now',
            'admin_user_id' => intval($this->user['id']),
        ]);
        $payloadEsc = mysqli_real_escape_string($this->conn, $payload);

        $sql = "INSERT INTO task_queue
                  (company_id, task_type, payload, priority, status, scheduled_for, max_attempts)
                VALUES
                  ($comId, 'channel_heartbeat', '$payloadEsc', 1, 'pending', NOW(), 1)";

        if (!mysqli_query($this->conn, $sql)) {
            error_log('[ChannelHealthController::testNow] ' . mysqli_error($this->conn));
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'enqueue_failed']);
            return;
        }
        echo json_encode(['ok' => true, 'task_id' => intval(mysqli_insert_id($this->conn))]);
    }

    /** POST — acknowledge one alert. */
    public function acknowledge(): void
    {
        header('Content-Type: application/json');

        if ($this->user['level'] < 2) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'access_denied']);
            return;
        }
        $this->verifyCsrf();

        $alertId = intval($_POST['alert_id'] ?? 0);
        $comId   = intval($this->user['com_id']);
        if ($alertId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_alert_id']);
            return;
        }

        $alerts = new ChannelAlert($this->conn);
        $alert  = $alerts->find($alertId, $comId);
        if ($alert === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found_or_wrong_tenant']);
            return;
        }

        $ok = $alerts->acknowledge($alertId, intval($this->user['id']));
        echo json_encode(['ok' => $ok]);
    }

    /**
     * Build dashboard status cards by combining latest heartbeat + last 5 evaluation.
     * Returns assoc array: channel_type → ['state'=>..., 'response_ms'=>..., 'ago'=>..., 'error'=>...].
     *
     * State machine matches Designer spec:
     *   healthy        → last 5 all success
     *   degraded       → 1-4 of last 5 failures
     *   down           → all 5 of last 5 failures
     *   not_configured → no credentials / latest row says not_configured
     *   stale          → no row in last 30 minutes (worker may be down)
     */
    private function buildStatusCards(int $comId, array $latestPerChannel, ChannelHealthLog $log): array
    {
        $cards = [];
        foreach (ChannelHealthLog::CHANNELS as $channel) {
            $latest = $latestPerChannel[$channel] ?? null;
            if ($latest === null) {
                $cards[$channel] = ['state' => 'stale', 'response_ms' => null,
                                    'ago_seconds' => null, 'error' => null,
                                    'last_checked_at' => null];
                continue;
            }
            $ago = time() - strtotime($latest['checked_at']);
            if ($ago > 30 * 60) {
                $cards[$channel] = ['state' => 'stale', 'response_ms' => null,
                                    'ago_seconds' => $ago, 'error' => null,
                                    'last_checked_at' => $latest['checked_at']];
                continue;
            }

            if ($latest['status'] === ChannelHealthLog::STATUS_NOT_CONFIGURED) {
                $cards[$channel] = ['state' => 'not_configured', 'response_ms' => null,
                                    'ago_seconds' => $ago, 'error' => null,
                                    'last_checked_at' => $latest['checked_at']];
                continue;
            }

            $recent = $log->recentFor($comId, $channel, 5);
            $failures = 0;
            foreach ($recent as $r) if ($r['status'] === ChannelHealthLog::STATUS_FAILURE) $failures++;

            $state = 'healthy';
            if ($failures >= 5)      $state = 'down';
            elseif ($failures >= 1)  $state = 'degraded';

            $cards[$channel] = [
                'state'           => $state,
                'response_ms'     => isset($latest['response_ms']) ? intval($latest['response_ms']) : null,
                'ago_seconds'     => $ago,
                'error'           => $latest['error_message'] ?? null,
                'last_checked_at' => $latest['checked_at'],
            ];
        }
        return $cards;
    }

    /**
     * Rate-limit check for "Test now" button. Returns true if currently rate-limited.
     */
    private function isRateLimited(int $comId, string $channel): bool
    {
        $channelEsc = mysqli_real_escape_string($this->conn, $channel);
        $window = self::TEST_NOW_RATE_LIMIT_SECONDS;

        $sql = "SELECT COUNT(*) AS c
                  FROM task_queue
                 WHERE company_id = $comId
                   AND task_type = 'channel_heartbeat'
                   AND JSON_EXTRACT(payload, '$.triggered_by') = 'admin_test_now'
                   AND JSON_EXTRACT(payload, '$.channels[0]') = '\"$channelEsc\"'
                   AND created_at >= NOW() - INTERVAL $window SECOND";
        $res = mysqli_query($this->conn, $sql);
        if (!$res) return false; // fail open — never block on a query error
        $row = mysqli_fetch_assoc($res);
        return intval($row['c'] ?? 0) > 0;
    }
}
