<?php
namespace App\Controllers;

use App\Services\EmailService;

/**
 * SmtpSettingsController — Per-company SMTP outbound email configuration.
 *
 * Routes:
 *   smtp_settings        GET  — settings form
 *   smtp_settings_save   POST — save settings (redirect back)
 *   smtp_settings_test   POST — AJAX test connection (standalone)
 */
class SmtpSettingsController extends BaseController
{
    // ─── Form (GET + POST save) ────────────────────────────────

    public function index(): void
    {
        if ($this->user['level'] < 2) {
            echo '<div class="alert alert-danger"><i class="fa fa-lock"></i> Access denied. Admin privileges required.</div>';
            return;
        }

        $comId   = intval($this->user['com_id']);
        $message = '';
        $msgType = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
            $this->verifyCsrf();
            $this->saveSettings($comId);
            header('Location: index.php?page=smtp_settings&status=saved');
            exit;
        }

        // Flash message
        if (($_GET['status'] ?? '') === 'saved') {
            $message = 'SMTP settings saved.';
            $msgType = 'success';
        }

        $cfg = $this->loadSettings($comId);
        $this->render('smtp-settings/index', compact('cfg', 'message', 'msgType'));
    }

    // ─── AJAX Test (standalone) ────────────────────────────────

    public function test(): void
    {
        header('Content-Type: application/json');

        if ($this->user['level'] < 2) {
            echo json_encode(['ok' => false, 'message' => 'Access denied']);
            exit;
        }

        $this->verifyCsrf();

        $testTo = trim($_POST['test_to'] ?? '');
        if (empty($testTo) || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'message' => 'Enter a valid recipient email for the test']);
            exit;
        }

        $comId   = intval($this->user['com_id']);
        $emailSvc = new EmailService($this->conn, $comId);

        // Override config with posted values so we can test unsaved settings
        $override = [
            'host'       => trim($_POST['host']       ?? ''),
            'port'       => intval($_POST['port']      ?? 587),
            'encryption' => trim($_POST['encryption']  ?? 'tls'),
            'username'   => trim($_POST['username']    ?? ''),
            'password'   => trim($_POST['password']    ?? ''),
            'from_email' => trim($_POST['from_email']  ?? ''),
            'from_name'  => trim($_POST['from_name']   ?? 'iACC'),
            'is_enabled' => 1,
        ];

        // Inject override if host provided
        if (!empty($override['host'])) {
            $emailSvc->overrideConfig($override);
        }

        $result = $emailSvc->testConnection($testTo);
        echo json_encode($result);
        exit;
    }

    // ─── Private helpers ───────────────────────────────────────

    private function loadSettings(int $comId): array
    {
        $defaults = [
            'host'       => '',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => '',
            'password'   => '',
            'from_email' => '',
            'from_name'  => '',
            'is_enabled' => 1,
        ];

        $result = mysqli_query($this->conn,
            "SELECT * FROM smtp_settings WHERE company_id = $comId LIMIT 1"
        );
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return array_merge($defaults, $row);
        }
        return $defaults;
    }

    private function saveSettings(int $comId): void
    {
        $host      = trim($_POST['host']       ?? '');
        $port      = intval($_POST['port']     ?? 587);
        $enc       = trim($_POST['encryption'] ?? 'tls');
        $user      = trim($_POST['username']   ?? '');
        $pass      = trim($_POST['password']   ?? '');
        $fromEmail = trim($_POST['from_email'] ?? '');
        $fromName  = trim($_POST['from_name']  ?? '');
        $enabled   = isset($_POST['is_enabled']) ? 1 : 0;

        if (!in_array($enc, ['none', 'ssl', 'tls'], true)) $enc = 'tls';
        if ($port < 1 || $port > 65535) $port = 587;

        $host      = mysqli_real_escape_string($this->conn, $host);
        $user      = mysqli_real_escape_string($this->conn, $user);
        $fromEmail = mysqli_real_escape_string($this->conn, $fromEmail);
        $fromName  = mysqli_real_escape_string($this->conn, $fromName);

        // Only update password if a new one was submitted
        $passClause = '';
        if ($pass !== '') {
            $pass = mysqli_real_escape_string($this->conn, $pass);
            $passClause = ", password = '$pass'";
        }

        mysqli_query($this->conn,
            "INSERT INTO smtp_settings (company_id, host, port, encryption, username, password, from_email, from_name, is_enabled)
             VALUES ($comId, '$host', $port, '$enc', '$user', " . ($pass !== '' ? "'$pass'" : "''") . ", '$fromEmail', '$fromName', $enabled)
             ON DUPLICATE KEY UPDATE
               host = '$host', port = $port, encryption = '$enc',
               username = '$user'" . $passClause . ",
               from_email = '$fromEmail', from_name = '$fromName',
               is_enabled = $enabled, updated_at = NOW()"
        );
    }
}
