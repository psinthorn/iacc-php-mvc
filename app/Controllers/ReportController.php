<?php
namespace App\Controllers;

use App\Models\Report;

/**
 * ReportController - Invoice payments & business summary
 * Replaces: invoice-payments.php, report.php
 */
class ReportController extends BaseController
{
    private Report $report;

    public function __construct()
    {
        parent::__construct();
        $this->report = new Report();
    }

    /* ---- Invoice Payments ---- */
    public function invoicePayments(): void
    {
        require_once __DIR__ . '/../../inc/pagination.php';

        $comId   = $this->getCompanyId();
        $page    = max(1, $this->inputInt('pg', 1));
        $perPage = 20;
        $status  = $this->input('status', '');
        $search  = trim($this->input('search', ''));

        $statusCond = match ($status) {
            'paid'    => ' HAVING paid_amount >= total_amount AND total_amount > 0',
            'partial' => ' HAVING paid_amount > 0 AND paid_amount < total_amount',
            'unpaid'  => ' HAVING (paid_amount IS NULL OR paid_amount = 0) AND total_amount > 0',
            default   => '',
        };

        $summary = $this->report->getInvoicePaymentSummary($comId, $search);
        $total   = $this->report->countInvoicePayments($comId, $search, $statusCond);
        $pagination = paginate($total, $perPage, $page);
        $rows    = $this->report->getInvoicePayments($comId, $search, $statusCond, $perPage, $pagination['offset']);

        $queryParams = $_GET;
        unset($queryParams['pg']);

        $this->render('report/invoice-payments', compact(
            'summary', 'rows', 'pagination', 'status', 'search', 'queryParams'
        ));
    }

    /* ---- Business Summary Report ---- */
    public function summary(): void
    {
        $comId     = $this->getCompanyId();
        $userLevel = intval($_SESSION['user_level'] ?? 0);
        $isAdmin   = $userLevel >= 1;
        $period    = $this->input('period', 'all');
        $sortBy    = $this->input('sort', 'name');
        $sortDir   = $this->input('dir', 'asc');

        $validSorts = ['name','pr','qa','po','iv','tx'];
        if (!in_array($sortBy, $validSorts)) $sortBy = 'name';
        if (!in_array($sortDir, ['asc','desc'])) $sortDir = 'asc';

        $dateFilter = match ($period) {
            'today' => "AND DATE(pr.date) = CURDATE()",
            'week'  => "AND pr.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month' => "AND pr.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'year'  => "AND YEAR(pr.date) = YEAR(CURDATE())",
            default => '',
        };

        $periodLabel = match ($period) {
            'today'=>'Today','week'=>'Last 7 Days','month'=>'Last 30 Days','year'=>'This Year',default=>'All Time'
        };

        if ($comId == 0 && !$isAdmin) {
            $this->render('report/summary', ['noCompany'=>true]);
            return;
        }

        $data = $this->report->getBusinessReport($comId, $dateFilter, $isAdmin);

        // Sort
        usort($data['rows'], function ($a, $b) use ($sortBy, $sortDir) {
            $cmp = $sortBy === 'name' ? strcasecmp($a['name'], $b['name']) : ($a[$sortBy] - $b[$sortBy]);
            return $sortDir === 'asc' ? $cmp : -$cmp;
        });

        $this->render('report/summary', [
            'reportData'  => $data['rows'],
            'totals'      => $data['totals'],
            'period'      => $period,
            'periodLabel' => $periodLabel,
            'sortBy'      => $sortBy,
            'sortDir'     => $sortDir,
            'noCompany'   => false,
        ]);
    }
}
