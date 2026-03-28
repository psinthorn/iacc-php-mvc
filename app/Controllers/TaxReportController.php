<?php
namespace App\Controllers;

use App\Models\TaxReport;

/**
 * TaxReportController — Thai Tax Report Management
 * 
 * Handles generation, viewing, and export of Thai tax reports:
 *   - ภ.พ.30 (PP30) — Monthly VAT Return
 *   - ภ.ง.ด.3 (PND3) — Withholding Tax (individual)
 *   - ภ.ง.ด.53 (PND53) — Withholding Tax (company)
 * 
 * Routes:
 *   tax_reports     → index()     — Dashboard with annual summary
 *   tax_report_pp30 → pp30()      — Generate/view PP30 report
 *   tax_report_wht  → wht()       — Generate/view WHT report (PND3/PND53)
 *   tax_report_save → save()      — Save generated report
 *   tax_report_export → export()  — Export report as CSV/PDF
 * 
 * @package App\Controllers
 * @version 1.0.0 — Q2 2026
 */
class TaxReportController extends BaseController
{
    private TaxReport $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new TaxReport();
    }

    /**
     * Tax report dashboard — annual summary with monthly breakdown
     */
    public function index(): void
    {
        $companyId = $this->companyFilter->getSafeCompanyId();
        $year = intval($_GET['year'] ?? date('Y'));
        
        $summary = $this->model->getAnnualSummary($companyId, $year);
        $savedReports = $this->model->getSavedReports($companyId, $year);
        
        $this->render('tax/dashboard', compact('summary', 'savedReports', 'year'));
    }

    /**
     * Generate PP30 (ภ.พ.30) — Monthly VAT Return
     */
    public function pp30(): void
    {
        $companyId = $this->companyFilter->getSafeCompanyId();
        $year = intval($_GET['year'] ?? date('Y'));
        $month = intval($_GET['month'] ?? date('n'));
        
        $report = $this->model->generatePP30($companyId, $year, $month);
        
        $this->render('tax/pp30', compact('report', 'year', 'month'));
    }

    /**
     * Generate WHT report (ภ.ง.ด.3 / ภ.ง.ด.53)
     */
    public function wht(): void
    {
        $companyId = $this->companyFilter->getSafeCompanyId();
        $year = intval($_GET['year'] ?? date('Y'));
        $month = intval($_GET['month'] ?? date('n'));
        $type = ($_GET['type'] ?? 'pnd53');
        
        if (!in_array($type, ['pnd3', 'pnd53'])) $type = 'pnd53';
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $report = $this->model->getWithholdingTax($companyId, $startDate, $endDate, $type);
        $report['tax_year'] = $year;
        $report['tax_month'] = $month;
        
        $this->render('tax/wht', compact('report', 'year', 'month', 'type'));
    }

    /**
     * Save a generated report to database
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=tax_reports');
            exit;
        }
        
        $this->verifyCsrf();
        
        $companyId = $this->companyFilter->getSafeCompanyId();
        $reportType = $_POST['report_type'] ?? '';
        $year = intval($_POST['year'] ?? 0);
        $month = intval($_POST['month'] ?? 0);
        
        // Regenerate the report data
        $reportData = [];
        if ($reportType === 'PP30') {
            $reportData = $this->model->generatePP30($companyId, $year, $month);
        } elseif (in_array($reportType, ['PND3', 'PND53'])) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
            $reportData = $this->model->getWithholdingTax($companyId, $startDate, $endDate, strtolower($reportType));
        }
        
        if (!empty($reportData)) {
            $reportData['company_id'] = $companyId;
            $reportData['tax_year'] = $year;
            $reportData['tax_month'] = $month;
            $this->model->saveReport($reportData);
            
            header('Location: index.php?page=tax_reports&year=' . $year . '&saved=1');
        } else {
            header('Location: index.php?page=tax_reports&error=invalid_report');
        }
        exit;
    }

    /**
     * Export report as CSV
     */
    public function export(): void
    {
        $companyId = $this->companyFilter->getSafeCompanyId();
        $reportType = $_GET['type'] ?? 'pp30';
        $year = intval($_GET['year'] ?? date('Y'));
        $month = intval($_GET['month'] ?? date('n'));
        $format = $_GET['format'] ?? 'csv';
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $filename = "tax-report-{$reportType}-{$year}-{$month}";
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            
            $output = fopen('php://output', 'w');
            
            if ($reportType === 'pp30') {
                $report = $this->model->generatePP30($companyId, $year, $month);
                
                fputcsv($output, ['ภ.พ.30 — Monthly VAT Return']);
                fputcsv($output, ['Period', $startDate . ' to ' . $endDate]);
                fputcsv($output, []);
                
                // Output VAT section
                fputcsv($output, ['=== Output VAT (ภาษีขาย) ===']);
                fputcsv($output, ['Invoice No', 'Tax Invoice No', 'Date', 'Customer', 'Tax ID', 'Base Amount', 'VAT Amount']);
                foreach ($report['output_vat']['items'] as $item) {
                    fputcsv($output, [
                        $item['invoice_no'], $item['tax_invoice_no'], $item['tax_invoice_date'],
                        $item['customer_name'], $item['customer_tax_id'],
                        $item['base_amount'], $item['vat_amount']
                    ]);
                }
                fputcsv($output, ['', '', '', '', 'Total', $report['output_vat']['total_base'], $report['output_vat']['total_vat']]);
                
                fputcsv($output, []);
                
                // Input VAT section
                fputcsv($output, ['=== Input VAT (ภาษีซื้อ) ===']);
                fputcsv($output, ['PO No', '', 'Date', 'Vendor', 'Tax ID', 'Base Amount', 'VAT Amount']);
                foreach ($report['input_vat']['items'] as $item) {
                    fputcsv($output, [
                        $item['po_number'], '', $item['po_date'],
                        $item['vendor_name'], $item['vendor_tax_id'],
                        $item['base_amount'], $item['vat_amount']
                    ]);
                }
                fputcsv($output, ['', '', '', '', 'Total', $report['input_vat']['total_base'], $report['input_vat']['total_vat']]);
                
                fputcsv($output, []);
                fputcsv($output, ['Net VAT', $report['net_vat']]);
                fputcsv($output, ['VAT Payable', $report['vat_payable']]);
                fputcsv($output, ['VAT Refundable', $report['vat_refundable']]);
            }
            
            fclose($output);
            exit;
        }
        
        // JSON format
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        
        if ($reportType === 'pp30') {
            echo json_encode($this->model->generatePP30($companyId, $year, $month), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($this->model->getWithholdingTax($companyId, $startDate, $endDate, $reportType), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
