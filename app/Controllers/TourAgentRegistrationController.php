<?php
namespace App\Controllers;

use App\Models\TourOperatorAgent;
use App\Models\AgentContract;
use App\Services\ContractSyncService;
use App\Services\EmailService;

/**
 * TourAgentRegistrationController — Agent registration & approval workflow
 *
 * Routes:
 *   tour_agent_reg_list      → list()      — List pending/approved/etc. agents
 *   tour_agent_reg_view      → view()      — View single registration
 *   tour_agent_reg_approve   → approve()   — POST: approve + assign default contract
 *   tour_agent_reg_reject    → reject()    — POST: reject with reason
 *   tour_agent_reg_suspend   → suspend()   — POST: suspend agent
 *   tour_agent_reg_reactivate → reactivate() — POST: reactivate suspended
 *   tour_agent_reg_invite    → invite()    — POST: send invitation
 *   tour_agent_reg_register  → publicRegister() — Public agent self-registration
 */
class TourAgentRegistrationController extends BaseController
{
    private TourOperatorAgent $model;
    private AgentContract $contractModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new TourOperatorAgent();
        $this->contractModel = new AgentContract();
    }

    private function guardModule(): bool
    {
        if (!isModuleEnabled($this->getCompanyId(), 'tour_operator')) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * List agents (operator-side) with status filter
     */
    public function list(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $status = $this->inputStr('status', '');
        $statusFilter = in_array($status, ['pending', 'approved', 'suspended', 'rejected'], true) ? $status : null;

        $agents = $this->model->listForOperator($comId, $statusFilter);
        $counts = $this->model->getStatusCounts($comId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/tour-agent/registration-list.php';
    }

    /**
     * View a single registration with approval form
     */
    public function view(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');

        $registration = $this->model->getRegistration($id, $comId);
        if (!$registration) {
            $this->redirect('tour_agent_reg_list', ['msg' => 'not_found']);
            return;
        }

        $contracts = $this->contractModel->getOperatorContracts($comId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/tour-agent/registration-view.php';
    }

    /**
     * POST: approve a pending registration
     */
    public function approve(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_reg_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');
        $defaultContractId = $this->inputInt('default_contract_id') ?: null;
        $approvedBy = intval($_SESSION['user_id'] ?? 0);

        $registration = $this->model->getRegistration($id, $comId);
        if (!$registration) {
            $this->redirect('tour_agent_reg_list', ['msg' => 'not_found']);
            return;
        }

        $ok = $this->model->approve($id, $comId, $approvedBy, $defaultContractId);

        // If a default contract was assigned, also add to tour_contract_agents and trigger sync
        if ($ok && $defaultContractId) {
            $this->contractModel->assignAgent($defaultContractId, (int)$registration['agent_company_id'], $comId, $approvedBy);
            $sync = new ContractSyncService();
            $sync->syncContractToAgents($defaultContractId, $comId, 'operator');
        }

        // Send approval email (best-effort)
        if ($ok) {
            $this->sendApprovalEmail($registration);
        }

        $this->redirect('tour_agent_reg_view', ['id' => $id, 'msg' => $ok ? 'approved' : 'error']);
    }

    /**
     * POST: reject a pending registration
     */
    public function reject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_reg_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');
        $reason = $this->inputStr('reason') ?: null;

        $ok = $this->model->reject($id, $comId, $reason);
        $this->redirect('tour_agent_reg_list', ['msg' => $ok ? 'rejected' : 'error', 'status' => 'rejected']);
    }

    /**
     * POST: suspend an approved agent
     */
    public function suspend(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_reg_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');
        $reason = $this->inputStr('reason') ?: null;

        $ok = $this->model->suspend($id, $comId, $reason);
        $this->redirect('tour_agent_reg_view', ['id' => $id, 'msg' => $ok ? 'suspended' : 'error']);
    }

    /**
     * POST: reactivate a suspended agent
     */
    public function reactivate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_reg_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');

        $ok = $this->model->reactivate($id, $comId);
        $this->redirect('tour_agent_reg_view', ['id' => $id, 'msg' => $ok ? 'reactivated' : 'error']);
    }

    /**
     * POST: invite an existing company as an agent (creates pending invitation token)
     */
    public function invite(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_agent_reg_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $agentComId = $this->inputInt('agent_company_id');
        $notes = $this->inputStr('notes') ?: null;

        if (!$agentComId) {
            $this->redirect('tour_agent_reg_list', ['msg' => 'error']);
            return;
        }

        $invitation = $this->model->createInvitation($comId, $agentComId, $notes);

        // Send invitation email (best-effort)
        if (!empty($invitation['token'])) {
            $this->sendInvitationEmail($comId, $agentComId, $invitation['token']);
        }

        $this->redirect('tour_agent_reg_list', ['msg' => 'invited', 'status' => 'pending']);
    }

    // ─── Helper: send notification emails ─────────────────────

    private function sendApprovalEmail(array $registration): void
    {
        $email = $registration['agent_email'] ?? '';
        if (!$email) return;

        $name = $registration['agent_name_en'] ?: $registration['agent_name_th'] ?: 'Agent';
        $operatorName = $this->getOperatorName();
        $portalUrl = $this->buildAbsoluteUrl('agent_portal_dashboard');

        $subject = "Your agent account has been approved by $operatorName";
        $html = "<h2>Welcome aboard, $name</h2>"
              . "<p>Your registration as an agent for <strong>$operatorName</strong> has been approved.</p>"
              . "<p>You can now access the agent portal to view contracts, products, and bookings:</p>"
              . "<p><a href=\"$portalUrl\" style=\"background:#0d9488;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;\">Open Agent Portal</a></p>";

        try {
            $svc = new EmailService(null, $this->getCompanyId());
            $svc->send($email, $subject, $html);
        } catch (\Throwable $e) {
            // best-effort; log only
            error_log('TourAgentReg approval email failed: ' . $e->getMessage());
        }
    }

    private function sendInvitationEmail(int $operatorComId, int $agentComId, string $token): void
    {
        $sql = "SELECT email, name_en FROM company WHERE id = " . intval($agentComId) . " LIMIT 1";
        $res = mysqli_query($this->model->getConnection(), $sql);
        $agent = $res ? mysqli_fetch_assoc($res) : null;
        $email = $agent['email'] ?? '';
        if (!$email) return;

        $name = $agent['name_en'] ?? 'Agent';
        $operatorName = $this->getOperatorName();
        $acceptUrl = $this->buildAbsoluteUrl('tour_agent_reg_accept', ['token' => $token]);

        $subject = "Invitation to become an agent for $operatorName";
        $html = "<h2>Hi $name</h2>"
              . "<p><strong>$operatorName</strong> has invited you to join as an authorized agent.</p>"
              . "<p>Click the link below to accept the invitation (expires in 14 days):</p>"
              . "<p><a href=\"$acceptUrl\" style=\"background:#0d9488;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;\">Accept Invitation</a></p>";

        try {
            $svc = new EmailService(null, $operatorComId);
            $svc->send($email, $subject, $html);
        } catch (\Throwable $e) {
            error_log('TourAgentReg invitation email failed: ' . $e->getMessage());
        }
    }

    private function getOperatorName(): string
    {
        $comId = $this->getCompanyId();
        $sql = "SELECT name_en FROM company WHERE id = " . intval($comId) . " LIMIT 1";
        $res = mysqli_query($this->model->getConnection(), $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['name_en'] ?? 'Tour Operator';
    }

    private function buildAbsoluteUrl(string $page, array $params = []): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = '/index.php?page=' . $page;
        foreach ($params as $k => $v) {
            $path .= '&' . urlencode($k) . '=' . urlencode((string)$v);
        }
        return $scheme . '://' . $host . $path;
    }
}
