<?php
namespace App\Controllers;

use App\Models\ContractReport;

/**
 * SuperAdminTourController — Platform-wide tour operator overview
 *
 * Routes:
 *   super_admin_tour          → overview()    — platform stats, operator/agent lists, sync log
 *   super_admin_tour_send_all → sendAllNow()  — POST: trigger digest emails to all operators
 *
 * All routes require user_level >= 2.
 */
class SuperAdminTourController extends BaseController
{
    private ContractReport $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new ContractReport();
    }

    private function guardSuperAdmin(): bool
    {
        $level = intval($_SESSION['user_level'] ?? 0);
        if ($level < 2) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    /**
     * Platform-wide overview (super admin only)
     */
    public function overview(): void
    {
        if (!$this->guardSuperAdmin()) return;

        $platform = $this->reportModel->platformDaily();
        $operators = $this->getOperatorsList();
        $recentSync = $this->getRecentSyncLog(20);
        $pendingApprovals = $this->getPendingApprovals(10);
        $cronUrl = $this->buildCronUrl();
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/admin/super-tour-dashboard.php';
    }

    /**
     * POST: trigger today's digest email for all operators
     */
    public function sendAllNow(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('super_admin_tour');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardSuperAdmin()) return;

        $period = $this->inputStr('period', 'daily');
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';

        $reportController = new ContractReportController();
        $sent = 0;
        $failed = 0;

        global $db;
        $sql = "SELECT DISTINCT cm.company_id
                FROM company_modules cm
                WHERE cm.module_key = 'tour_operator' AND cm.is_enabled = 1";
        $res = mysqli_query($db->conn, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $comId = intval($row['company_id']);
                $email = $this->getCompanyAdminEmail($comId);
                if (!$email) continue;
                if ($reportController->sendDigest($comId, $email, $period)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        $this->redirect('super_admin_tour', ['msg' => 'sent', 'sent' => $sent, 'failed' => $failed]);
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function getOperatorsList(): array
    {
        global $db;
        $sql = "SELECT c.id, c.name_en,
                       COUNT(DISTINCT oa.agent_company_id) AS agent_count,
                       COUNT(DISTINCT ac.id) AS contract_count,
                       (SELECT COUNT(*) FROM tour_bookings WHERE company_id = c.id AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS bookings_30d
                FROM company c
                INNER JOIN company_modules cm ON cm.company_id = c.id AND cm.module_key = 'tour_operator' AND cm.is_enabled = 1
                LEFT JOIN tour_operator_agents oa ON oa.operator_company_id = c.id AND oa.status = 'approved' AND oa.deleted_at IS NULL
                LEFT JOIN agent_contracts ac ON ac.company_id = c.id AND ac.is_operator_level = 1
                GROUP BY c.id
                ORDER BY agent_count DESC, c.name_en";
        $res = mysqli_query($db->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    private function getRecentSyncLog(int $limit = 20): array
    {
        global $db;
        $limit = intval($limit);
        $sql = "SELECT l.*, c.name_en AS operator_name, ac.contract_name, ag.name_en AS agent_name
                FROM tour_contract_sync_log l
                LEFT JOIN company c ON l.company_id = c.id
                LEFT JOIN agent_contracts ac ON l.contract_id = ac.id
                LEFT JOIN company ag ON l.agent_company_id = ag.id
                ORDER BY l.created_at DESC
                LIMIT $limit";
        $res = mysqli_query($db->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    private function getPendingApprovals(int $limit = 10): array
    {
        global $db;
        $limit = intval($limit);
        $sql = "SELECT oa.*, op.name_en AS operator_name, ag.name_en AS agent_name, ag.email AS agent_email
                FROM tour_operator_agents oa
                LEFT JOIN company op ON oa.operator_company_id = op.id
                LEFT JOIN company ag ON oa.agent_company_id = ag.id
                WHERE oa.status = 'pending' AND oa.deleted_at IS NULL
                ORDER BY oa.created_at DESC
                LIMIT $limit";
        $res = mysqli_query($db->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    private function getCompanyAdminEmail(int $comId): ?string
    {
        global $db;
        $sql = "SELECT email FROM user WHERE company_id = " . intval($comId) . " AND user_level >= 1 AND email != '' ORDER BY user_level DESC LIMIT 1";
        $res = mysqli_query($db->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['email'] ?? null;
    }

    private function buildCronUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/cron.php';
    }
}
