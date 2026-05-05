<?php
namespace App\Workers\Handlers;

use App\Models\ChannelHealthLog;
use App\Models\ChannelAlert;

/**
 * ChannelHeartbeatHandler — v6.2 #83.
 *
 * Task type: 'channel_heartbeat'
 * Runs once every 5 minutes (re-enqueues itself on success). Probes each
 * registered channel for the tenant, writes a row to channel_health_log per
 * channel, and evaluates alert state transitions.
 *
 * Payload:
 *   {} — no payload required. The handler iterates all CHANNELS by default.
 *   { "channels": ["line_oa"] } — restrict to specific channels (used by "Test now" button).
 *
 * Result (written to task_results.result_data):
 *   {
 *     "company_id": 123,
 *     "channels_probed": 4,
 *     "results": [{ "channel": "line_oa", "status": "success", "response_ms": 245 }, ...],
 *     "alerts_opened": 1, "alerts_resolved": 0,
 *     "next_scheduled_for": "2026-05-05 01:30:00"
 *   }
 *
 * Invariants:
 *  - Always writes one channel_health_log row per channel probed (even on
 *    not_configured) so the dashboard "stale" detection works.
 *  - Alert email sending is delegated to a helper hook that returns void
 *    (logs but never throws — email failure must not abort the heartbeat).
 *
 * NOT yet implemented (scoped to v6.2.x iteration):
 *  - Real probes for line_oa / sales_channel_api / outbound_webhook /
 *    email_smtp. Current implementation returns 'not_configured' for all
 *    channels except a stubbed 'sales_channel_api' that always succeeds —
 *    enough to wire the pipeline end-to-end. Real prober classes ship next.
 */
class ChannelHeartbeatHandler implements TaskHandler
{
    /** Tighter cap than default — channel APIs misbehave; don't tie up the queue. */
    public static int $maxAttempts = 3;

    /** Re-enqueue interval (matches PM spec AC1). */
    public const INTERVAL_MINUTES = 5;

    public function handle(array $payload, array $context): array
    {
        global $db;
        $conn = $db->conn;

        $companyId = intval($context['company_id']);
        if ($companyId <= 0) {
            // Heartbeat must be tenant-scoped. Caller is responsible for
            // enqueuing per-tenant tasks (the scheduler — v6.2.x follow-up).
            throw new \RuntimeException('ChannelHeartbeatHandler requires company_id in context');
        }

        $log    = new ChannelHealthLog($conn);
        $alerts = new ChannelAlert($conn);

        $channels = isset($payload['channels']) && is_array($payload['channels'])
            ? array_intersect($payload['channels'], ChannelHealthLog::CHANNELS)
            : ChannelHealthLog::CHANNELS;

        $results        = [];
        $alertsOpened   = 0;
        $alertsResolved = 0;

        foreach ($channels as $channel) {
            $probe = $this->probe($channel, $companyId);

            $log->insert([
                'company_id'    => $companyId,
                'channel_type'  => $channel,
                'channel_ref'   => $probe['channel_ref'] ?? null,
                'status'        => $probe['status'],
                'response_ms'   => $probe['response_ms'] ?? null,
                'error_message' => $probe['error'] ?? null,
            ]);

            // Alert state transitions
            $active = $alerts->activeFor($companyId, $channel);
            if ($probe['status'] === ChannelHealthLog::STATUS_FAILURE
                && $active === null
                && $log->hasConsecutiveFailures($companyId, $channel)) {

                $alertId = $alerts->open($companyId, $channel, $probe['channel_ref'] ?? null, $probe['error'] ?? null);
                $this->sendAlertEmail($conn, $companyId, $channel, 'opened', $probe['error'] ?? null);
                $alerts->bumpEmailSentCount($alertId);
                $alertsOpened++;

            } elseif ($probe['status'] === ChannelHealthLog::STATUS_SUCCESS && $active !== null) {

                $alerts->resolve(intval($active['id']));
                $this->sendAlertEmail($conn, $companyId, $channel, 'resolved', null);
                $alerts->bumpEmailSentCount(intval($active['id']));
                $alertsResolved++;
            }

            $results[] = [
                'channel'      => $channel,
                'status'       => $probe['status'],
                'response_ms'  => $probe['response_ms'] ?? null,
                'error'        => $probe['error']       ?? null,
            ];
        }

        // Self-reschedule for the next tick (AC1). Worker writes attempts++,
        // so this enqueue must NOT be tied to the current task row's lifecycle —
        // we simply append a fresh row.
        $nextRunAt = $this->scheduleNext($conn, $companyId);

        return [
            'company_id'         => $companyId,
            'channels_probed'    => count($channels),
            'results'            => $results,
            'alerts_opened'      => $alertsOpened,
            'alerts_resolved'    => $alertsResolved,
            'next_scheduled_for' => $nextRunAt,
        ];
    }

    /**
     * Probe one channel — returns an outcome shape:
     *   ['status' => 'success'|'failure'|'not_configured',
     *    'response_ms' => ?int, 'error' => ?string, 'channel_ref' => ?string]
     *
     * MVP stub: only 'sales_channel_api' returns success; others return
     * 'not_configured'. Replace with real prober classes (Phase 4.5 follow-up).
     */
    private function probe(string $channel, int $companyId): array
    {
        $started = microtime(true);

        switch ($channel) {
            case 'sales_channel_api':
                // Cheap self-check: probe our own /api/health endpoint via loopback.
                // If we can't reach our own API, that's a real platform problem.
                $url = $this->resolveSelfApiHealth();
                $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
                $body = @file_get_contents($url, false, $ctx);
                $ms = intval((microtime(true) - $started) * 1000);
                if ($body === false) {
                    return ['status' => ChannelHealthLog::STATUS_FAILURE, 'response_ms' => $ms,
                            'error' => 'self-api unreachable', 'channel_ref' => $url];
                }
                return ['status' => ChannelHealthLog::STATUS_SUCCESS, 'response_ms' => $ms,
                        'channel_ref' => $url];

            case 'line_oa':
            case 'outbound_webhook':
            case 'email_smtp':
            default:
                // Real probers ship next iteration. Until then, mark as not_configured
                // so the dashboard renders gracefully without false-alarming.
                return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }
    }

    /**
     * Resolve the URL to ping for sales_channel_api self-check.
     * Falls back to localhost if no host header in current request context.
     */
    private function resolveSelfApiHealth(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return "$scheme://$host/?page=health";
    }

    /**
     * Re-enqueue a heartbeat task for this tenant in INTERVAL_MINUTES.
     * Returns the scheduled_for timestamp string for inclusion in the result.
     */
    private function scheduleNext(\mysqli $conn, int $companyId): string
    {
        $payload = json_encode(['scheduled_by' => 'ChannelHeartbeatHandler']);
        $payloadEsc = mysqli_real_escape_string($conn, $payload);

        $sql = "INSERT INTO task_queue
                  (company_id, task_type, payload, priority, status, scheduled_for, max_attempts)
                VALUES
                  ($companyId, 'channel_heartbeat', '$payloadEsc', 5, 'pending',
                   DATE_ADD(NOW(), INTERVAL " . self::INTERVAL_MINUTES . " MINUTE), 3)";

        if (!mysqli_query($conn, $sql)) {
            error_log('[ChannelHeartbeatHandler::scheduleNext] ' . mysqli_error($conn));
            return '';
        }

        $r = mysqli_query($conn, "SELECT DATE_ADD(NOW(), INTERVAL " . self::INTERVAL_MINUTES . " MINUTE) AS t");
        $row = mysqli_fetch_assoc($r);
        return $row['t'] ?? '';
    }

    /**
     * Email alerter — fire-and-forget. Never throws.
     * Real implementation will use App\Services\EmailService; for now, log.
     */
    private function sendAlertEmail(\mysqli $conn, int $companyId, string $channel, string $event, ?string $error): void
    {
        try {
            // TODO Phase 4.5: integrate with App\Services\EmailService for the
            // production email send. For MVP, log the intent — admins still see
            // the alert in the dashboard; email is a nice-to-have for alerting.
            error_log(sprintf(
                '[ChannelHeartbeatHandler::sendAlertEmail] company=%d channel=%s event=%s error=%s',
                $companyId, $channel, $event, $error ?? '-'
            ));
        } catch (\Throwable $e) {
            error_log('[ChannelHeartbeatHandler::sendAlertEmail FAIL] ' . $e->getMessage());
        }
    }
}
