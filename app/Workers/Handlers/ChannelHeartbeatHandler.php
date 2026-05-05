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
     */
    private function probe(string $channel, int $companyId): array
    {
        switch ($channel) {
            case 'sales_channel_api':  return $this->probeSalesChannelApi();
            case 'line_oa':            return $this->probeLineOa($companyId);
            case 'outbound_webhook':   return $this->probeOutboundWebhook($companyId);
            case 'email_smtp':         return $this->probeEmailSmtp($companyId);
            default:                   return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }
    }

    /** Self-loopback probe to /?page=health. */
    private function probeSalesChannelApi(): array
    {
        $started = microtime(true);
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
    }

    /**
     * LINE OA — call /v2/bot/info with the company's channel access token.
     * Lightweight read-only API; no message sent, no rate limit risk.
     */
    private function probeLineOa(int $companyId): array
    {
        global $db;
        $sql = "SELECT channel_access_token, channel_id
                  FROM line_oa_config
                 WHERE company_id = $companyId AND is_active = 1 AND deleted_at IS NULL
                 LIMIT 1";
        $res = mysqli_query($db->conn, $sql);
        $cfg = $res ? mysqli_fetch_assoc($res) : null;
        if (!$cfg || empty($cfg['channel_access_token'])) {
            return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }

        $started = microtime(true);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Authorization: Bearer " . trim($cfg['channel_access_token']) . "\r\n",
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents('https://api.line.me/v2/bot/info', false, $ctx);
        $ms = intval((microtime(true) - $started) * 1000);
        $code = $this->httpStatusFromHeaders($http_response_header ?? []);

        if ($body !== false && $code >= 200 && $code < 300) {
            return ['status' => ChannelHealthLog::STATUS_SUCCESS, 'response_ms' => $ms,
                    'channel_ref' => $cfg['channel_id'] ?: 'line_oa'];
        }
        $err = $code > 0 ? "HTTP $code from LINE API" : 'LINE API unreachable';
        return ['status' => ChannelHealthLog::STATUS_FAILURE, 'response_ms' => $ms,
                'error' => $err, 'channel_ref' => $cfg['channel_id'] ?: 'line_oa'];
    }

    /**
     * Outbound webhook — passive prober. Reads delivery telemetry over the
     * last 30 minutes from api_webhook_deliveries and computes success rate.
     * Avoids firing test pings (which could be noisy / unsafe to subscribers).
     *
     * Logic:
     *   - No webhooks registered → not_configured
     *   - Active webhooks but zero deliveries in 30 min → not_configured (no signal)
     *   - Deliveries exist:
     *       success_rate >= 50% → success
     *       success_rate <  50% → failure
     */
    private function probeOutboundWebhook(int $companyId): array
    {
        global $db;
        $started = microtime(true);

        $countSql = "SELECT COUNT(*) AS c FROM api_webhooks
                      WHERE company_id = $companyId AND is_active = 1";
        $r = mysqli_query($db->conn, $countSql);
        $cfgRow = $r ? mysqli_fetch_assoc($r) : null;
        if (!$cfgRow || intval($cfgRow['c']) === 0) {
            return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }

        $statsSql = "SELECT
                        SUM(success)                            AS ok_count,
                        COUNT(*)                                AS total,
                        SUBSTRING_INDEX(GROUP_CONCAT(error ORDER BY created_at DESC), ',', 1) AS last_error
                      FROM api_webhook_deliveries d
                      JOIN api_webhooks w ON w.id = d.webhook_id
                     WHERE w.company_id = $companyId
                       AND d.created_at >= NOW() - INTERVAL 30 MINUTE";
        $r = mysqli_query($db->conn, $statsSql);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        $ms = intval((microtime(true) - $started) * 1000);

        if (!$row || intval($row['total']) === 0) {
            return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED, 'response_ms' => $ms];
        }
        $okCount = intval($row['ok_count']);
        $total   = intval($row['total']);
        $rate    = $total > 0 ? ($okCount / $total) : 0;
        if ($rate >= 0.5) {
            return ['status' => ChannelHealthLog::STATUS_SUCCESS, 'response_ms' => $ms,
                    'channel_ref' => "$okCount/$total ok last 30min"];
        }
        return ['status' => ChannelHealthLog::STATUS_FAILURE, 'response_ms' => $ms,
                'error' => "Only $okCount/$total succeeded; latest: " . substr((string)$row['last_error'], 0, 200),
                'channel_ref' => "$okCount/$total"];
    }

    /**
     * Email SMTP — open a TCP connection to the configured SMTP host:port.
     * Cheapest possible probe: doesn't EHLO, doesn't authenticate, doesn't send.
     * If the host is reachable on the port within 3s, count as healthy.
     */
    private function probeEmailSmtp(int $companyId): array
    {
        global $db;
        try {
            $svc = new \App\Services\EmailService($db->conn, $companyId);
            $cfg = $svc->loadConfig();
        } catch (\Throwable $e) {
            return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }
        $host = trim($cfg['smtp_host'] ?? '');
        $port = intval($cfg['smtp_port'] ?? 0);
        if ($host === '' || $port <= 0) {
            return ['status' => ChannelHealthLog::STATUS_NOT_CONFIGURED];
        }

        $started = microtime(true);
        $errno = 0; $errstr = '';
        $sock = @fsockopen($host, $port, $errno, $errstr, 3);
        $ms = intval((microtime(true) - $started) * 1000);
        if ($sock) {
            fclose($sock);
            return ['status' => ChannelHealthLog::STATUS_SUCCESS, 'response_ms' => $ms,
                    'channel_ref' => "$host:$port"];
        }
        return ['status' => ChannelHealthLog::STATUS_FAILURE, 'response_ms' => $ms,
                'error' => "TCP connect failed: $errstr (errno=$errno)",
                'channel_ref' => "$host:$port"];
    }

    /** Parse the numeric HTTP status code from $http_response_header. */
    private function httpStatusFromHeaders(array $headers): int
    {
        foreach ($headers as $h) {
            if (preg_match('~^HTTP/\S+\s+(\d+)~', $h, $m)) return intval($m[1]);
        }
        return 0;
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
     * Sends bilingual (TH/EN stacked) email to all admins (level >= 2) of the company.
     */
    private function sendAlertEmail(\mysqli $conn, int $companyId, string $channel, string $event, ?string $error): void
    {
        try {
            $admins = $this->lookupAdminEmails($conn, $companyId);
            if (empty($admins)) {
                error_log("[ChannelHeartbeatHandler::sendAlertEmail] no admin emails for company=$companyId");
                return;
            }
            $channelName = $this->channelDisplayName($channel);
            $subject = $event === 'opened'
                ? "[iACC] Alert: $channelName channel is down"
                : "[iACC] Resolved: $channelName channel is back online";
            $html = $this->renderAlertHtml($event, $channelName, $error);
            $text = $this->renderAlertText($event, $channelName, $error);

            $svc = new \App\Services\EmailService($conn, $companyId);
            // EmailService::send accepts a single recipient string or array
            $svc->send($admins, $subject, $html, $text);

            error_log(sprintf(
                '[ChannelHeartbeatHandler::sendAlertEmail] sent company=%d channel=%s event=%s recipients=%d',
                $companyId, $channel, $event, count($admins)
            ));
        } catch (\Throwable $e) {
            // Email failures must NOT abort the heartbeat — alert state is in DB,
            // dashboard surfaces it regardless of email delivery.
            error_log('[ChannelHeartbeatHandler::sendAlertEmail FAIL] ' . $e->getMessage());
        }
    }

    /** Resolve admin emails for a company. Returns array of email strings. */
    private function lookupAdminEmails(\mysqli $conn, int $companyId): array
    {
        $sql = "SELECT DISTINCT email
                  FROM authorize
                 WHERE company_id = $companyId
                   AND email IS NOT NULL
                   AND email != ''
                   AND (locked_until IS NULL OR locked_until < NOW())
                   AND (level >= 2 OR is_admin = 1)
                 LIMIT 10";
        $res = @mysqli_query($conn, $sql);
        if (!$res) {
            // authorize table may have a different "admin" indicator; fall back simpler
            $sql = "SELECT DISTINCT email FROM authorize
                     WHERE company_id = $companyId AND email IS NOT NULL AND email != '' LIMIT 10";
            $res = mysqli_query($conn, $sql);
        }
        $out = [];
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) $out[] = $r['email'];
        }
        return $out;
    }

    /** Bilingual display name for a channel (used in email subject + body). */
    private function channelDisplayName(string $channel): string
    {
        $map = [
            'line_oa'           => 'LINE OA',
            'sales_channel_api' => 'Sales Channel API',
            'outbound_webhook'  => 'Outbound Webhook',
            'email_smtp'        => 'Email SMTP',
        ];
        return $map[$channel] ?? $channel;
    }

    private function renderAlertHtml(string $event, string $channelName, ?string $error): string
    {
        $errPreview = htmlspecialchars((string)($error ?? '—'));
        if ($event === 'opened') {
            $titleEn = "Alert: $channelName channel is down";
            $titleTh = "แจ้งเตือน: ช่องทาง $channelName ขัดข้อง";
            $bodyEn  = "The $channelName channel has failed 5 consecutive heartbeats and is now flagged as down.";
            $bodyTh  = "ช่องทาง $channelName ตรวจพบความล้มเหลวต่อเนื่อง 5 ครั้ง จึงถูกตั้งสถานะเป็นขัดข้อง";
        } else {
            $titleEn = "Resolved: $channelName channel is back online";
            $titleTh = "กลับมาใช้งานได้: ช่องทาง $channelName";
            $bodyEn  = "The $channelName channel has recovered and is responding normally.";
            $bodyTh  = "ช่องทาง $channelName กลับมาตอบสนองตามปกติแล้ว";
        }
        return "<!doctype html><html><body style=\"font-family:Arial,sans-serif;color:#1f2937;\">"
             . "<h2 style=\"color:" . ($event === 'opened' ? '#991b1b' : '#065f46') . ";\">$titleEn</h2>"
             . "<p>$bodyEn</p>"
             . "<p style=\"font-family:monospace;background:#f9fafb;padding:8px;border-left:3px solid #ef4444;\">$errPreview</p>"
             . "<hr style=\"border:0;border-top:1px solid #e5e7eb;margin:20px 0;\">"
             . "<h3 style=\"color:" . ($event === 'opened' ? '#991b1b' : '#065f46') . ";\">$titleTh</h3>"
             . "<p>$bodyTh</p>"
             . "<p style=\"color:#6b7280;font-size:12px;margin-top:24px;\">— iACC Channel Health Monitor</p>"
             . "</body></html>";
    }

    private function renderAlertText(string $event, string $channelName, ?string $error): string
    {
        $err = (string)($error ?? '—');
        if ($event === 'opened') {
            return "[Alert] $channelName channel is down\n\n"
                 . "5 consecutive heartbeats failed.\n"
                 . "Latest error: $err\n\n"
                 . "[แจ้งเตือน] ช่องทาง $channelName ขัดข้อง\n"
                 . "ตรวจพบความล้มเหลวต่อเนื่อง 5 ครั้ง\n"
                 . "ข้อผิดพลาดล่าสุด: $err\n\n"
                 . "— iACC Channel Health Monitor";
        }
        return "[Resolved] $channelName channel is back online\n\n"
             . "Channel is responding normally again.\n\n"
             . "[กลับมาใช้งานได้] ช่องทาง $channelName\n"
             . "ช่องทางกลับมาตอบสนองตามปกติแล้ว\n\n"
             . "— iACC Channel Health Monitor";
    }
}
