<?php
namespace App\Controllers;

use App\Models\Registration;

/**
 * RegistrationController — Public self-registration flow
 * 
 * Flow: register form → submit → send verification email → 
 *       click email link → verify → create company+user+trial → auto-login
 */
class RegistrationController extends BaseController
{
    private Registration $registration;

    public function __construct()
    {
        parent::__construct();
        $this->registration = new Registration();
    }

    /**
     * GET: Show registration form
     */
    public function showForm(): void
    {
        // Already logged in? Go to dashboard
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('dashboard');
            return;
        }
        include __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * POST: Handle registration form submission
     * Validates input, creates verification token, sends email
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('register');
            return;
        }

        // CSRF check
        if (!csrf_verify()) {
            $this->redirectWithError('register', 'Invalid form submission. Please try again.');
            return;
        }

        // Rate limit registration attempts (10 per hour per IP)
        $rateLimited = $this->checkRegistrationRateLimit();
        if ($rateLimited) {
            $this->redirectWithError('register', 'Too many registration attempts. Please try again later.');
            return;
        }

        // Validate inputs
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $companyName = trim($_POST['company_name'] ?? '');
        $agreeTerms = isset($_POST['agree_terms']);

        $errors = $this->validateRegistration($email, $name, $password, $passwordConfirm, $agreeTerms);
        if (!empty($errors)) {
            $this->redirectWithError('register', implode(' ', $errors));
            return;
        }

        // Check if email already registered
        if ($this->registration->emailExists($email)) {
            $this->redirectWithError('register', 'This email is already registered. Please sign in instead.');
            return;
        }

        // Create verification token
        $passHash = password_hash_secure($password);
        $payload = [
            'name'         => $name,
            'password_hash' => $passHash,
            'company_name' => $companyName ?: $name,
        ];
        $token = $this->registration->createVerification($email, $payload);

        // Send verification email
        $this->sendVerificationEmail($email, $name, $token);

        // Redirect to success page
        $_SESSION['reg_email'] = $email;
        $this->redirect('register_sent');
    }

    /**
     * GET: Show "check your email" page after registration
     */
    public function sent(): void
    {
        $email = $_SESSION['reg_email'] ?? '';
        if (empty($email)) {
            $this->redirect('register');
            return;
        }
        include __DIR__ . '/../Views/auth/register-sent.php';
    }

    /**
     * GET: Verify email token and create account
     */
    public function verify(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            include __DIR__ . '/../Views/auth/register-error.php';
            return;
        }

        // Verify token
        $verification = $this->registration->verifyToken($token);
        if (!$verification) {
            $errorMessage = 'This verification link is invalid or has expired. Please register again.';
            include __DIR__ . '/../Views/auth/register-error.php';
            return;
        }

        // Check email isn't taken (race condition guard)
        if ($this->registration->emailExists($verification['email'])) {
            $this->registration->markVerified($verification['id']);
            $errorMessage = 'This email is already registered. Please sign in.';
            include __DIR__ . '/../Views/auth/register-error.php';
            return;
        }

        // Create account (company + user + trial)
        try {
            $account = $this->registration->createAccount(
                $verification['email'],
                $verification['payload']['password_hash'],
                $verification['payload']['name']
            );
            $this->registration->markVerified($verification['id']);
        } catch (\Throwable $e) {
            error_log("Registration failed: " . $e->getMessage());
            $errorMessage = 'An error occurred creating your account. Please try again.';
            include __DIR__ . '/../Views/auth/register-error.php';
            return;
        }

        // Auto-login the new user
        $this->autoLogin($account['user_id'], $verification['email'], $account['company_id'], $verification['payload']['name']);

        // Redirect to onboarding
        $this->redirect('onboarding');
    }

    /**
     * GET: Show onboarding wizard for new users
     */
    public function onboarding(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('register');
            return;
        }
        include __DIR__ . '/../Views/auth/onboarding.php';
    }

    /**
     * POST: Complete onboarding (save company details)
     */
    public function completeOnboarding(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
            $this->redirect('dashboard');
            return;
        }

        $this->verifyCsrf();

        $companyId = intval($_SESSION['com_id']);
        $updates = [];

        // Collect optional onboarding data
        if (!empty($_POST['company_name'])) {
            $updates['name_en'] = sql_escape($_POST['company_name']);
        }
        if (!empty($_POST['phone'])) {
            $updates['phone'] = sql_escape($_POST['phone']);
        }
        if (!empty($_POST['address'])) {
            $updates['address'] = sql_escape($_POST['address']);
        }
        if (!empty($_POST['tax_id'])) {
            $updates['tax'] = sql_escape($_POST['tax_id']);
        }

        $updates['onboarding_completed'] = 1;
        $updates['updated_at'] = date('Y-m-d H:i:s');

        if ($companyId > 0) {
            $this->hard->updateSafe('company', $updates, ['id' => $companyId]);
        }

        $this->redirect('dashboard');
    }

    /**
     * GET: Show plan comparison page
     */
    public function plans(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('register');
            return;
        }
        include __DIR__ . '/../Views/auth/plans.php';
    }

    // ========== Private Helpers ==========

    private function validateRegistration(string $email, string $name, string $password, string $passwordConfirm, bool $agreeTerms): array
    {
        $errors = [];

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Please enter your full name (at least 2 characters).';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$agreeTerms) {
            $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
        }

        return $errors;
    }

    private function checkRegistrationRateLimit(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'reg_' . md5($ip);
        $window = 3600; // 1 hour
        $maxAttempts = 10;

        // Use session-based rate limiting (simple, no external deps)
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset' => time() + $window];
        }

        if (time() > $_SESSION[$key]['reset']) {
            $_SESSION[$key] = ['count' => 0, 'reset' => time() + $window];
        }

        $_SESSION[$key]['count']++;
        return $_SESSION[$key]['count'] > $maxAttempts;
    }

    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        $baseUrl = $this->getBaseUrl();
        $verifyUrl = $baseUrl . '/index.php?page=register_verify&token=' . urlencode($token);

        $subject = 'Verify your iACC account';
        $htmlBody = $this->buildVerificationEmailHtml($name, $verifyUrl);

        // Use MailHog in dev, real SMTP in production
        $smtpHost = getenv('SMTP_HOST') ?: 'iacc_mailhog_server';
        $smtpPort = intval(getenv('SMTP_PORT') ?: 1025);

        $headers = [
            'From: iACC <noreply@iacc.app>',
            'Reply-To: noreply@iacc.app',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        // Try SMTP via fsockopen (works with MailHog, no extensions needed)
        $sent = $this->sendSmtp($smtpHost, $smtpPort, 'noreply@iacc.app', $email, $subject, $htmlBody);

        if (!$sent) {
            // Fallback to PHP mail()
            @mail($email, $subject, $htmlBody, implode("\r\n", $headers));
        }

        error_log("Verification email sent to {$email} (token: " . substr($token, 0, 8) . "...)");
    }

    private function sendSmtp(string $host, int $port, string $from, string $to, string $subject, string $body): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$socket) {
            error_log("SMTP connection failed: {$errstr} ({$errno})");
            return false;
        }

        $this->smtpRead($socket);
        $this->smtpWrite($socket, "EHLO iacc.app\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "MAIL FROM:<{$from}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
        $this->smtpRead($socket);
        $this->smtpWrite($socket, "DATA\r\n");
        $this->smtpRead($socket);

        $message = "From: iACC <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $body . "\r\n";
        $message .= ".\r\n";

        $this->smtpWrite($socket, $message);
        $response = $this->smtpRead($socket);
        $this->smtpWrite($socket, "QUIT\r\n");
        fclose($socket);

        return strpos($response, '250') !== false;
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
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    }

    private function buildVerificationEmailHtml(string $name, string $verifyUrl): string
    {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $escapedUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: 'Inter', Arial, sans-serif; background: #f5f5f5; padding: 40px 0;">
<div style="max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <div style="background: linear-gradient(135deg, #8e44ad, #6c3483); padding: 30px; text-align: center;">
        <h1 style="color: white; margin: 0; font-size: 28px;">iACC</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0;">Accounting Management System</p>
    </div>
    <div style="padding: 30px;">
        <h2 style="color: #2c3e50; margin-top: 0;">Welcome, {$escapedName}!</h2>
        <p style="color: #666; line-height: 1.6;">Thank you for registering with iACC. Please click the button below to verify your email address and activate your <strong>14-day free trial</strong>.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$escapedUrl}" style="display: inline-block; padding: 14px 40px; background: linear-gradient(135deg, #8e44ad, #6c3483); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Verify Email Address</a>
        </div>
        <p style="color: #999; font-size: 13px;">This link expires in 24 hours. If you didn't create this account, you can safely ignore this email.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">If the button doesn't work, copy and paste this URL into your browser:<br>
        <a href="{$escapedUrl}" style="color: #8e44ad; word-break: break-all;">{$escapedUrl}</a></p>
    </div>
</div>
</body>
</html>
HTML;
    }

    private function autoLogin(int $userId, string $email, int $companyId, string $name): void
    {
        // Set session variables (same as authorize.php login flow)
        $_SESSION['user_email'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_level'] = 0; // Regular user
        $_SESSION['lang'] = 0; // English
        $_SESSION['com_id'] = $companyId;
        $_SESSION['com_name'] = $name;

        // Load RBAC permissions
        if (function_exists('rbac_load_permissions')) {
            rbac_load_permissions($this->conn, $userId);
            rbac_load_roles($this->conn, $userId);
        }

        // Security: regenerate session
        session_regenerate_id(true);
        if (function_exists('csrf_regenerate')) {
            csrf_regenerate();
        }

        // Audit log
        if (function_exists('audit_log')) {
            audit_log($this->conn, 'self_registration', 'authorize', $userId, $email);
        }
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    private function redirectWithError(string $page, string $error): void
    {
        $_SESSION['reg_error'] = $error;
        $_SESSION['reg_old'] = [
            'email' => $_POST['email'] ?? '',
            'name' => $_POST['name'] ?? '',
            'company_name' => $_POST['company_name'] ?? '',
        ];
        $this->redirect($page);
    }
}
