<?php
namespace App\Controllers;

use App\Models\UserAccount;

/**
 * UserAccountController - Profile & Settings
 * Replaces: profile.php, settings.php
 */
class UserAccountController extends BaseController
{
    private UserAccount $account;

    public function __construct()
    {
        parent::__construct();
        $this->account = new UserAccount();
    }

    /* ---- Profile page ---- */
    public function profile(): void
    {
        $userId = $this->getUserId();
        $user   = $this->account->findUser($userId);
        $message     = '';
        $messageType = '';
        $this->render('account/profile', compact('user', 'message', 'messageType'));
    }

    /* ---- Settings page ---- */
    public function settings(): void
    {
        $userId = $this->getUserId();
        $user   = $this->account->findUser($userId);
        $message     = '';
        $messageType = '';

        if (isset($_GET['updated']) && $_GET['updated'] == '1') {
            $xml = $this->getXml();
            $message     = isset($xml->settings_saved) ? (string)$xml->settings_saved : 'Settings saved successfully.';
            $messageType = 'success';
        }
        $this->render('account/settings', compact('user', 'message', 'messageType'));
    }

    /* ---- Store (POST) ---- */
    public function store(): void
    {
        $this->verifyCsrf();
        $action = $this->input('action', '');
        $userId = $this->getUserId();

        switch ($action) {
            case 'update_profile':
                $name  = trim($this->input('name', ''));
                $phone = trim($this->input('phone', ''));
                if ($this->account->updateProfile($userId, $name, $phone)) {
                    $_SESSION['user_name'] = $name;
                }
                $this->redirect('index.php?page=profile');
                break;

            case 'change_password':
                $cur  = $this->input('current_password', '');
                $new  = $this->input('new_password', '');
                $conf = $this->input('confirm_password', '');

                if (empty($cur) || empty($new) || empty($conf)) {
                    $this->redirect('index.php?page=profile&err=empty');
                    return;
                }
                if ($new !== $conf) {
                    $this->redirect('index.php?page=profile&err=mismatch');
                    return;
                }
                if (strlen($new) < 6) {
                    $this->redirect('index.php?page=profile&err=short');
                    return;
                }
                $user = $this->account->findUser($userId);
                $this->account->changePassword($userId, $cur, $new, $user['password']);
                $this->redirect('index.php?page=profile');
                break;

            case 'update_language':
                $lang = intval($this->input('lang', '0'));
                if ($this->account->updateLanguage($userId, $lang)) {
                    $_SESSION['lang'] = $lang;
                }
                $this->redirect('index.php?page=settings&updated=1');
                break;

            case 'update_notifications':
                // Placeholder — no DB column yet
                $this->redirect('index.php?page=settings');
                break;

            default:
                $this->redirect('index.php?page=profile');
        }
    }

    /* ---- Helpers ---- */
    private function getUserId(): int
    {
        return intval($_SESSION['user_id'] ?? 0);
    }

    private function getXml()
    {
        global $xml;
        return $xml;
    }
}
