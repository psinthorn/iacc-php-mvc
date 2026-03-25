<?php
namespace App\Controllers;

use App\Models\User;

/**
 * UserController - User management (Super Admin only)
 * Replaces: user-list.php
 */
class UserController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(): void
    {
        $this->requireLevel(2);

        $search        = trim($this->input('search', ''));
        $roleFilter    = $this->input('role', '');
        $companyFilter = $this->input('company_id', '');
        $message     = '';
        $messageType = '';

        $users     = $this->userModel->getUsers($search, $roleFilter, $companyFilter);
        $companies = $this->userModel->getCompanies();

        // Group by role
        $usersByRole = [
            2 => ['label'=>'Super Admins','class'=>'danger','icon'=>'fa-shield','users'=>[],'desc'=>'Full system access and configuration'],
            1 => ['label'=>'Admins','class'=>'info','icon'=>'fa-user-secret','users'=>[],'desc'=>'Can access all companies'],
            0 => ['label'=>'Users','class'=>'default','icon'=>'fa-user','users'=>[],'desc'=>'Locked to assigned company'],
        ];
        foreach ($users as $u) {
            $lvl = intval($u['level']);
            $usersByRole[$lvl]['users'][] = $u;
        }

        $this->render('user/list', compact('usersByRole', 'companies', 'search', 'roleFilter', 'companyFilter', 'message', 'messageType'));
    }

    public function store(): void
    {
        $this->requireLevel(2);
        $this->verifyCsrf();

        $action = $this->input('action', '');
        $currentUserId = intval($_SESSION['user_id'] ?? 0);

        switch ($action) {
            case 'add':
                $email     = trim($this->input('email', ''));
                $password  = $this->input('password', '');
                $level     = $this->inputInt('level', 0);
                $companyId = $this->inputInt('company_id', 0) ?: null;

                if ($level == 0 && empty($companyId)) break;
                if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) break;
                if ($this->userModel->emailExists($email)) break;
                $this->userModel->createUser($email, $password, $level, $companyId);
                break;

            case 'update_level':
                $userId = $this->inputInt('user_id', 0);
                if ($userId === $currentUserId) break;
                $this->userModel->updateLevel($userId, $this->inputInt('level', 0));
                break;

            case 'update_company':
                $companyId = $this->inputInt('company_id', 0) ?: null;
                $this->userModel->updateCompany($this->inputInt('user_id', 0), $companyId);
                break;

            case 'reset_password':
                $pw = $this->input('new_password', '');
                if (strlen($pw) >= 6) $this->userModel->resetPassword($this->inputInt('user_id', 0), $pw);
                break;

            case 'unlock':
                $this->userModel->unlockUser($this->inputInt('user_id', 0));
                break;

            case 'delete':
                $userId = $this->inputInt('user_id', 0);
                if ($userId !== $currentUserId) $this->userModel->deleteUser($userId);
                break;
        }

        $this->redirect('index.php?page=user');
    }

    private function requireLevel(int $minLevel): void
    {
        $level = intval($_SESSION['user_level'] ?? 0);
        if ($level < $minLevel) {
            echo '<div class="alert alert-danger">Access denied.</div>';
            exit;
        }
    }
}
