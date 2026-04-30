<?php
namespace App\Controllers;

use App\Models\ContractReport;
use App\Services\EmailService;

/**
 * ContractReportController — Operator reporting dashboard + email digests
 *
 * Routes:
 *   tour_contract_report      → dashboard()      — Combined daily/weekly/monthly view
 *   tour_contract_report_send → sendNow()        — POST: send a digest email manually
 *
 * The cron-friendly runner is at bin/send_contract_reports.php
 */
class ContractReportController extends BaseController
{
    private ContractReport $reportModel;

    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new ContractReport();
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
     * Combined dashboard — daily + weekly + monthly snapshots
     */
    public function dashboard(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $period = $this->inputStr('period', 'weekly');  // daily | weekly | monthly

        $daily   = $this->reportModel->daily($comId);
        $weekly  = $this->reportModel->weekly($comId);
        $monthly = $this->reportModel->monthly($comId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/tour-agent/report-dashboard.php';
    }

    /**
     * POST: trigger a digest email for the operator's own admin email
     */
    public function sendNow(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_contract_report');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $period = $this->inputStr('period', 'daily');

        $email = $this->getCompanyAdminEmail($comId);
        if (!$email) {
            $this->redirect('tour_contract_report', ['msg' => 'no_email']);
            return;
        }

        $sent = $this->sendDigest($comId, $email, $period);
        $this->redirect('tour_contract_report', ['msg' => $sent ? 'sent' : 'send_failed', 'period' => $period]);
    }

    /**
     * Send a digest email (also called from CLI runner)
     */
    public function sendDigest(int $comId, string $toEmail, string $period = 'daily'): bool
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
        $data = $this->reportModel->{$period}($comId);
        $companyName = $this->getCompanyName($comId);

        $subject = match ($period) {
            'daily'   => "[$companyName] Daily Tour Operator Report — " . date('d M Y'),
            'weekly'  => "[$companyName] Weekly Tour Operator Report — " . date('d M Y'),
            'monthly' => "[$companyName] Monthly Tour Operator Report — " . date('M Y'),
        };

        $html = $this->buildEmailHtml($period, $data, $companyName);

        try {
            $svc = new EmailService(null, $comId);
            return $svc->send($toEmail, $subject, $html);
        } catch (\Throwable $e) {
            error_log('ContractReport email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function buildEmailHtml(string $period, array $data, string $companyName): string
    {
        $title = ucfirst($period) . ' Tour Operator Report';
        $when = $period === 'daily'   ? ('for ' . date('d M Y'))
              : ($period === 'weekly' ? ("for {$data['period_start']} → {$data['period_end']}")
              :                          ("for " . date('M Y', strtotime($data['period_start']))));

        $html = "<div style=\"font-family:Arial,sans-serif;max-width:640px;margin:0 auto;\">"
              . "<h1 style=\"color:#0d9488;\">$title</h1>"
              . "<p style=\"color:#64748b;\">$companyName · $when</p>";

        if ($period === 'daily') {
            $html .= "<table cellspacing=\"0\" cellpadding=\"12\" style=\"width:100%;border-collapse:collapse;background:#f8fafc;border-radius:8px;\">"
                  . "<tr><td><strong>New Contracts</strong></td><td align=\"right\">{$data['new_contracts']}</td></tr>"
                  . "<tr><td><strong>New Agents Approved</strong></td><td align=\"right\">{$data['new_agents']}</td></tr>"
                  . "<tr><td><strong>New Registrations</strong></td><td align=\"right\">{$data['new_registrations']}</td></tr>"
                  . "<tr><td><strong>Pending Approvals</strong></td><td align=\"right\">{$data['pending_approvals']}</td></tr>"
                  . "<tr><td><strong>Sync Events</strong></td><td align=\"right\">{$data['sync_events']}</td></tr>"
                  . "<tr><td><strong>New Bookings</strong></td><td align=\"right\">{$data['bookings_today']}</td></tr>"
                  . "</table>";
        } elseif ($period === 'weekly') {
            $rev = number_format($data['revenue'], 2);
            $html .= "<table cellspacing=\"0\" cellpadding=\"12\" style=\"width:100%;border-collapse:collapse;background:#f8fafc;border-radius:8px;\">"
                  . "<tr><td><strong>New Contracts</strong></td><td align=\"right\">{$data['new_contracts']}</td></tr>"
                  . "<tr><td><strong>New Agents</strong></td><td align=\"right\">{$data['new_agents']}</td></tr>"
                  . "<tr><td><strong>Bookings (7 days)</strong></td><td align=\"right\">{$data['bookings']}</td></tr>"
                  . "<tr><td><strong>Revenue (7 days)</strong></td><td align=\"right\">฿$rev</td></tr>"
                  . "<tr><td><strong>Sync Events</strong></td><td align=\"right\">{$data['sync_events']}</td></tr>"
                  . "</table>";
            if (!empty($data['top_agents'])) {
                $html .= "<h3 style=\"color:#0d9488;margin-top:24px;\">Top 5 Agents</h3>"
                      . "<table cellspacing=\"0\" cellpadding=\"10\" style=\"width:100%;border-collapse:collapse;\">";
                foreach ($data['top_agents'] as $i => $a) {
                    $rank = $i + 1;
                    $rev = number_format($a['revenue'], 2);
                    $name = htmlspecialchars($a['agent_name'] ?: 'Agent #' . $a['agent_id']);
                    $html .= "<tr><td>$rank. $name</td><td align=\"right\">{$a['booking_count']} bookings · ฿$rev</td></tr>";
                }
                $html .= "</table>";
            }
        } else {  // monthly
            $rev = number_format($data['current']['revenue'], 2);
            $bookingChg = $data['change_pct']['bookings'] !== null ? sprintf('%+.1f%%', $data['change_pct']['bookings']) : 'n/a';
            $revChg = $data['change_pct']['revenue'] !== null ? sprintf('%+.1f%%', $data['change_pct']['revenue']) : 'n/a';
            $html .= "<table cellspacing=\"0\" cellpadding=\"12\" style=\"width:100%;border-collapse:collapse;background:#f8fafc;border-radius:8px;\">"
                  . "<tr><td><strong>Bookings</strong></td><td align=\"right\">{$data['current']['bookings']} <span style=\"color:#94a3b8;font-size:12px;\">($bookingChg)</span></td></tr>"
                  . "<tr><td><strong>Revenue</strong></td><td align=\"right\">฿$rev <span style=\"color:#94a3b8;font-size:12px;\">($revChg)</span></td></tr>"
                  . "<tr><td><strong>New Contracts</strong></td><td align=\"right\">{$data['new_contracts']}</td></tr>"
                  . "<tr><td><strong>New Agents</strong></td><td align=\"right\">{$data['new_agents']}</td></tr>"
                  . "</table>";
            if (!empty($data['top_products'])) {
                $html .= "<h3 style=\"color:#0d9488;margin-top:24px;\">Top 10 Products</h3>"
                      . "<table cellspacing=\"0\" cellpadding=\"10\" style=\"width:100%;border-collapse:collapse;\">";
                foreach ($data['top_products'] as $i => $p) {
                    $rank = $i + 1;
                    $rev = number_format(floatval($p['revenue']), 2);
                    $name = htmlspecialchars($p['model_name'] ?: 'Product #' . $p['model_id']);
                    $html .= "<tr><td>$rank. $name</td><td align=\"right\">{$p['bookings']} bookings · ฿$rev</td></tr>";
                }
                $html .= "</table>";
            }
        }

        $html .= "<p style=\"margin-top:24px;color:#94a3b8;font-size:12px;\">Generated by iACC. Reply to this email if anything looks off.</p></div>";
        return $html;
    }

    private function getCompanyAdminEmail(int $comId): ?string
    {
        global $db;
        $sql = "SELECT email FROM user WHERE company_id = " . intval($comId) . " AND user_level >= 1 AND email != '' ORDER BY user_level DESC, usr_id ASC LIMIT 1";
        $res = mysqli_query($db->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['email'] ?? null;
    }

    private function getCompanyName(int $comId): string
    {
        global $db;
        $sql = "SELECT name_en FROM company WHERE id = " . intval($comId) . " LIMIT 1";
        $res = mysqli_query($db->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row['name_en'] ?? 'Tour Operator';
    }
}
