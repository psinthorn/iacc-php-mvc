<?php
namespace App\Services;

/**
 * EmailService — Outbound SMTP email delivery for iACC.
 *
 * Supports:
 *  - Per-company SMTP config loaded from `smtp_settings` table
 *  - Fallback to environment variables (SMTP_HOST / SMTP_PORT)
 *  - Fallback to PHP mail() if SMTP fails
 *  - Plain-text + HTML multipart messages
 *  - STARTTLS via stream_socket_enable_crypto (port 587)
 *  - SSL via ssl:// wrapper (port 465)
 *
 * Usage:
 *   $svc = new EmailService($conn, $comId);
 *   $svc->send('customer@example.com', 'Your Voucher', '<html>...</html>');
 */
class EmailService
{
    private ?\mysqli $conn;
    private int $comId;
    private ?array $config = null;  // Lazy-loaded from DB

    public function __construct(?\mysqli $conn = null, int $comId = 0)
    {
        $this->conn  = $conn;
        $this->comId = $comId;
    }

    // ─── Public API ───────────────────────────────────────────

    /**
     * Send an HTML email.
     *
     * @param string|array $to      Single email or ['email'=>'...','name'=>'...']
     * @param string       $subject Email subject
     * @param string       $html    HTML body
     * @param string       $text    Optional plain-text fallback
     * @return bool        True if delivered (or queued), false on failure
     */
    public function send($to, string $subject, string $html, string $text = ''): bool
    {
        $cfg = $this->loadConfig();

        $toEmail = is_array($to) ? ($to['email'] ?? '') : $to;
        $toName  = is_array($to) ? ($to['name']  ?? '') : '';

        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("EmailService: invalid recipient: $toEmail");
            return false;
        }

        $fromEmail = $cfg['from_email'] ?: 'noreply@iacc.app';
        $fromName  = $cfg['from_name']  ?: 'iACC';
        $text      = $text ?: strip_tags($html);

        $body = $this->buildMimeBody($fromEmail, $fromName, $toEmail, $toName, $subject, $html, $text);

        // Try configured SMTP
        if (!empty($cfg['host'])) {
            $sent = $this->deliverSmtp($cfg, $fromEmail, $toEmail, $body);
            if ($sent) {
                error_log("EmailService: sent via SMTP to $toEmail — $subject");
                return true;
            }
            error_log("EmailService: SMTP failed for $toEmail, falling back to mail()");
        }

        // Fallback: PHP mail()
        return $this->deliverMailFunction($fromEmail, $fromName, $toEmail, $subject, $html);
    }

    /**
     * Send a voucher email for a tour booking.
     * Returns ['sent'=>bool, 'error'=>string].
     */
    public function sendVoucher(array $booking, array $contact): array
    {
        $to = [
            'email' => $contact['email'] ?? '',
            'name'  => $contact['contact_name'] ?? '',
        ];

        if (empty($to['email'])) {
            return ['sent' => false, 'error' => 'No email address on file'];
        }

        $subject = 'Your Tour Voucher — ' . ($booking['booking_number'] ?? '');
        $html    = $this->buildVoucherHtml($booking, $contact);

        $sent = $this->send($to, $subject, $html);
        return ['sent' => $sent, 'error' => $sent ? '' : 'Delivery failed'];
    }

    /**
     * Send an invoice notification email for a tour booking.
     */
    public function sendInvoiceNotification(array $booking, array $contact): array
    {
        $to = [
            'email' => $contact['email'] ?? '',
            'name'  => $contact['contact_name'] ?? '',
        ];

        if (empty($to['email'])) {
            return ['sent' => false, 'error' => 'No email address on file'];
        }

        $subject = 'Invoice — Booking ' . ($booking['booking_number'] ?? '');
        $html    = $this->buildInvoiceHtml($booking, $contact);

        $sent = $this->send($to, $subject, $html);
        return ['sent' => $sent, 'error' => $sent ? '' : 'Delivery failed'];
    }

    /**
     * Test the SMTP connection and send a test email.
     * Returns ['ok'=>bool, 'message'=>string].
     */
    public function testConnection(string $testTo): array
    {
        $cfg = $this->loadConfig();
        if (empty($cfg['host'])) {
            return ['ok' => false, 'message' => 'No SMTP host configured'];
        }

        $fromEmail = $cfg['from_email'] ?: 'noreply@iacc.app';
        $fromName  = $cfg['from_name']  ?: 'iACC';
        $html      = '<p>This is a test email from iACC. SMTP is working correctly.</p>';
        $body      = $this->buildMimeBody($fromEmail, $fromName, $testTo, '', 'iACC SMTP Test', $html, 'iACC SMTP test — working.');

        $sent = $this->deliverSmtp($cfg, $fromEmail, $testTo, $body);
        return [
            'ok'      => $sent,
            'message' => $sent ? "Test email sent to $testTo" : "SMTP delivery failed — check host/port/credentials",
        ];
    }

    // ─── Config Loading ───────────────────────────────────────

    /** Inject a config array directly (used by test endpoint before save). */
    public function overrideConfig(array $cfg): void
    {
        $this->config = $cfg;
    }

    public function loadConfig(): array
    {
        if ($this->config !== null) return $this->config;

        // 1. Try DB config for this company
        if ($this->conn && $this->comId > 0) {
            $cid    = intval($this->comId);
            $result = mysqli_query($this->conn,
                "SELECT * FROM smtp_settings WHERE company_id = $cid AND is_enabled = 1 LIMIT 1"
            );
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $this->config = $row;
                return $this->config;
            }
        }

        // 2. Fallback to environment variables (MailHog in dev, real SMTP in prod)
        $this->config = [
            'host'       => getenv('SMTP_HOST') ?: '',
            'port'       => intval(getenv('SMTP_PORT') ?: 587),
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'username'   => getenv('SMTP_USERNAME') ?: '',
            'password'   => getenv('SMTP_PASSWORD') ?: '',
            'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'noreply@iacc.app',
            'from_name'  => getenv('SMTP_FROM_NAME')  ?: 'iACC',
        ];

        return $this->config;
    }

    // ─── SMTP Delivery ────────────────────────────────────────

    private function deliverSmtp(array $cfg, string $from, string $to, string $rawMessage): bool
    {
        $host       = $cfg['host'];
        $port       = intval($cfg['port'] ?? 587);
        $encryption = $cfg['encryption'] ?? 'tls';
        $user       = $cfg['username']   ?? '';
        $pass       = $cfg['password']   ?? '';

        // SSL wrapping (port 465)
        $connectHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;

        $socket = @fsockopen($connectHost, $port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("EmailService SMTP connect failed [{$host}:{$port}]: $errstr ($errno)");
            return false;
        }

        try {
            $this->smtpRead($socket); // 220 greeting

            // EHLO
            $this->smtpWrite($socket, "EHLO iacc.app\r\n");
            $ehlo = $this->smtpRead($socket);

            // STARTTLS (port 587)
            if ($encryption === 'tls' && strpos($ehlo, 'STARTTLS') !== false) {
                $this->smtpWrite($socket, "STARTTLS\r\n");
                $this->smtpRead($socket);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    error_log("EmailService: STARTTLS failed");
                    fclose($socket);
                    return false;
                }
                // Re-EHLO after TLS
                $this->smtpWrite($socket, "EHLO iacc.app\r\n");
                $this->smtpRead($socket);
            }

            // AUTH LOGIN
            if (!empty($user)) {
                $this->smtpWrite($socket, "AUTH LOGIN\r\n");
                $this->smtpRead($socket);
                $this->smtpWrite($socket, base64_encode($user) . "\r\n");
                $this->smtpRead($socket);
                $this->smtpWrite($socket, base64_encode($pass) . "\r\n");
                $authResponse = $this->smtpRead($socket);
                if (strpos($authResponse, '235') === false) {
                    error_log("EmailService: AUTH failed: $authResponse");
                    fclose($socket);
                    return false;
                }
            }

            // Envelope
            $this->smtpWrite($socket, "MAIL FROM:<{$from}>\r\n");
            $this->smtpRead($socket);
            $this->smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
            $rcpt = $this->smtpRead($socket);
            if (strpos($rcpt, '250') === false && strpos($rcpt, '251') === false) {
                error_log("EmailService: RCPT rejected: $rcpt");
                fclose($socket);
                return false;
            }

            // Message
            $this->smtpWrite($socket, "DATA\r\n");
            $this->smtpRead($socket);
            $this->smtpWrite($socket, $rawMessage . "\r\n.\r\n");
            $dataResponse = $this->smtpRead($socket);
            $this->smtpWrite($socket, "QUIT\r\n");
            fclose($socket);

            return strpos($dataResponse, '250') !== false;

        } catch (\Throwable $e) {
            error_log("EmailService SMTP exception: " . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    private function deliverMailFunction(string $fromEmail, string $fromName, string $to, string $subject, string $html): bool
    {
        $headers = implode("\r\n", [
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
        ]);
        return @mail($to, $subject, $html, $headers);
    }

    private function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data);
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $response;
    }

    // ─── MIME Builder ─────────────────────────────────────────

    private function buildMimeBody(
        string $fromEmail, string $fromName,
        string $toEmail,   string $toName,
        string $subject, string $html, string $text
    ): string {
        $boundary = '----=_Part_' . md5(uniqid());
        $toHeader = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

        $msg  = "From: {$fromName} <{$fromEmail}>\r\n";
        $msg .= "To: {$toHeader}\r\n";
        $msg .= "Subject: {$subject}\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $msg .= "\r\n";
        // Plain text part
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $msg .= $text . "\r\n";
        // HTML part
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $msg .= quoted_printable_encode($html) . "\r\n";
        $msg .= "--{$boundary}--\r\n";

        return $msg;
    }

    // ─── Email Templates ──────────────────────────────────────

    private function buildVoucherHtml(array $booking, array $contact): string
    {
        $bNum     = htmlspecialchars($booking['booking_number'] ?? '');
        $date     = !empty($booking['travel_date']) ? date('d M Y', strtotime($booking['travel_date'])) : '—';
        $pax      = intval($booking['total_pax'] ?? 0);
        $amount   = number_format(floatval($booking['total_amount'] ?? 0), 2);
        $custName = htmlspecialchars($contact['contact_name'] ?? 'Valued Customer');
        $company  = htmlspecialchars($booking['company_name'] ?? 'iACC Tour');
        $pickup   = htmlspecialchars($booking['pickup_hotel'] ?? '');
        $pickupT  = !empty($booking['pickup_time']) ? date('H:i', strtotime($booking['pickup_time'])) : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'Sarabun',Arial,sans-serif;background:#f5f5f5;padding:30px 0;margin:0;">
<div style="max-width:520px;margin:0 auto;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
    <div style="background:linear-gradient(135deg,#0d9488,#0f766e);padding:28px 30px;text-align:center;">
        <h1 style="color:white;margin:0;font-size:24px;font-weight:700;">{$company}</h1>
        <p style="color:rgba(255,255,255,0.9);margin:6px 0 0;font-size:14px;">Tour Voucher</p>
    </div>
    <div style="padding:28px 30px;">
        <p style="color:#334155;font-size:15px;">Dear <strong>{$custName}</strong>,</p>
        <p style="color:#64748b;line-height:1.6;">Please find your tour voucher details below. Present this to your guide on the day of your tour.</p>

        <div style="background:#f0fdfa;border:1px solid #ccfbf1;border-radius:10px;padding:20px;margin:20px 0;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="padding:6px 0;color:#94a3b8;width:40%;">Booking Ref</td><td style="font-weight:700;color:#0d9488;">{$bNum}</td></tr>
                <tr><td style="padding:6px 0;color:#94a3b8;">Tour Date</td><td style="font-weight:600;">{$date}</td></tr>
                <tr><td style="padding:6px 0;color:#94a3b8;">Passengers</td><td style="font-weight:600;">{$pax} pax</td></tr>
                {$pickup ? "<tr><td style=\"padding:6px 0;color:#94a3b8;\">Pickup</td><td>{$pickup}" . ($pickupT ? " @ {$pickupT}" : "") . "</td></tr>" : ""}
                <tr><td style="padding:6px 0;color:#94a3b8;">Amount</td><td style="font-weight:700;font-size:16px;">฿{$amount}</td></tr>
            </table>
        </div>

        <p style="color:#94a3b8;font-size:12px;line-height:1.6;">
            If you have any questions, please contact us directly. We look forward to seeing you!
        </p>
    </div>
    <div style="background:#f8fafc;padding:16px 30px;text-align:center;font-size:11px;color:#94a3b8;">
        Powered by iACC Tour Management
    </div>
</div>
</body>
</html>
HTML;
    }

    private function buildInvoiceHtml(array $booking, array $contact): string
    {
        $bNum     = htmlspecialchars($booking['booking_number'] ?? '');
        $date     = !empty($booking['travel_date']) ? date('d M Y', strtotime($booking['travel_date'])) : '—';
        $amount   = number_format(floatval($booking['total_amount'] ?? 0), 2);
        $custName = htmlspecialchars($contact['contact_name'] ?? 'Valued Customer');
        $company  = htmlspecialchars($booking['company_name'] ?? 'iACC Tour');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:'Sarabun',Arial,sans-serif;background:#f5f5f5;padding:30px 0;margin:0;">
<div style="max-width:520px;margin:0 auto;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
    <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:28px 30px;text-align:center;">
        <h1 style="color:white;margin:0;font-size:24px;font-weight:700;">{$company}</h1>
        <p style="color:rgba(255,255,255,0.9);margin:6px 0 0;font-size:14px;">Invoice Notification</p>
    </div>
    <div style="padding:28px 30px;">
        <p style="color:#334155;font-size:15px;">Dear <strong>{$custName}</strong>,</p>
        <p style="color:#64748b;line-height:1.6;">An invoice has been issued for your upcoming tour booking. Please review the details below.</p>

        <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;padding:20px;margin:20px 0;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="padding:6px 0;color:#94a3b8;width:40%;">Booking Ref</td><td style="font-weight:700;color:#6366f1;">{$bNum}</td></tr>
                <tr><td style="padding:6px 0;color:#94a3b8;">Tour Date</td><td style="font-weight:600;">{$date}</td></tr>
                <tr><td style="padding:6px 0;color:#94a3b8;">Amount Due</td><td style="font-weight:700;font-size:16px;color:#ef4444;">฿{$amount}</td></tr>
            </table>
        </div>

        <p style="color:#94a3b8;font-size:12px;line-height:1.6;">
            Please contact us if you have any questions regarding this invoice.
        </p>
    </div>
    <div style="background:#f8fafc;padding:16px 30px;text-align:center;font-size:11px;color:#94a3b8;">
        Powered by iACC Tour Management
    </div>
</div>
</body>
</html>
HTML;
    }
}
