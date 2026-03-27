<?php
namespace App\Models;

/**
 * TaxReport Model — Thai Tax Report Generation
 * 
 * Generates standard Thai tax reports:
 *   - ภ.พ.30 (PP30) — Monthly VAT Return
 *   - ภ.ง.ด.3 (PND3) — Withholding Tax (individual)
 *   - ภ.ง.ด.53 (PND53) — Withholding Tax (company)
 * 
 * Data sources:
 *   - po/iv tables — Output VAT (sales)
 *   - pr/po tables — Input VAT (purchases)
 *   - pay table — Payment records with WHT
 *   - company table — Vendor/customer tax IDs
 * 
 * @package App\Models
 * @version 1.0.0 — Q2 2026
 */
class TaxReport extends BaseModel
{
    protected string $table = 'tax_reports';

    /**
     * Get Output VAT summary (ภาษีขาย) for a period
     * Output VAT = VAT collected from sales invoices
     * 
     * @param int    $companyId Company ID
     * @param string $startDate Period start (Y-m-d)
     * @param string $endDate   Period end (Y-m-d)
     * @return array ['items' => [...], 'total_base' => float, 'total_vat' => float]
     */
    public function getOutputVAT(int $companyId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    iv.tex AS invoice_no,
                    iv.texiv_rw AS tax_invoice_no,
                    iv.texiv_create AS tax_invoice_date,
                    po.po_name AS customer_name,
                    c.tax_id AS customer_tax_id,
                    po.vat AS vat_rate,
                    SUM(p.price * p.qty) AS subtotal,
                    po.dis AS discount_pct,
                    po.over AS overhead_pct
                FROM iv 
                JOIN po ON iv.po_id = po.po_id
                JOIN product p ON p.po_id = po.po_id
                LEFT JOIN company c ON po.com_id = c.com_id
                WHERE po.com_id_owner = {$companyId}
                AND iv.deleted_at IS NULL
                AND po.deleted_at IS NULL
                AND iv.texiv_create BETWEEN '{$startDate}' AND '{$endDate}'
                AND po.vat > 0
                GROUP BY iv.tex
                ORDER BY iv.texiv_create ASC";
        
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        $totalBase = 0;
        $totalVat = 0;
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subtotal = floatval($row['subtotal']);
                $discount = $subtotal * (floatval($row['discount_pct']) / 100);
                $overhead = ($subtotal - $discount) * (floatval($row['overhead_pct']) / 100);
                $base = $subtotal - $discount + $overhead;
                $vat = $base * (floatval($row['vat_rate']) / 100);
                
                $row['base_amount'] = round($base, 2);
                $row['vat_amount'] = round($vat, 2);
                $row['total_amount'] = round($base + $vat, 2);
                
                $totalBase += $row['base_amount'];
                $totalVat += $row['vat_amount'];
                
                $items[] = $row;
            }
        }
        
        return [
            'items'      => $items,
            'total_base' => round($totalBase, 2),
            'total_vat'  => round($totalVat, 2),
            'total'      => round($totalBase + $totalVat, 2),
            'period'     => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    /**
     * Get Input VAT summary (ภาษีซื้อ) for a period
     * Input VAT = VAT paid on purchases
     * 
     * @param int    $companyId Company ID
     * @param string $startDate Period start
     * @param string $endDate   Period end
     * @return array
     */
    public function getInputVAT(int $companyId, string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    po.po_id,
                    po.po_running AS po_number,
                    po.po_create AS po_date,
                    po.po_name AS vendor_name,
                    c.tax_id AS vendor_tax_id,
                    po.vat AS vat_rate,
                    SUM(p.price * p.qty) AS subtotal,
                    po.dis AS discount_pct,
                    po.over AS overhead_pct
                FROM po
                JOIN product p ON p.po_id = po.po_id
                LEFT JOIN company c ON po.com_id = c.com_id
                WHERE po.com_id_owner = {$companyId}
                AND po.deleted_at IS NULL
                AND po.po_status IN (1, 2)
                AND po.po_create BETWEEN '{$startDate}' AND '{$endDate}'
                AND po.vat > 0
                AND po.po_type = 'po'
                GROUP BY po.po_id
                ORDER BY po.po_create ASC";
        
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        $totalBase = 0;
        $totalVat = 0;
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subtotal = floatval($row['subtotal']);
                $discount = $subtotal * (floatval($row['discount_pct']) / 100);
                $overhead = ($subtotal - $discount) * (floatval($row['overhead_pct']) / 100);
                $base = $subtotal - $discount + $overhead;
                $vat = $base * (floatval($row['vat_rate']) / 100);
                
                $row['base_amount'] = round($base, 2);
                $row['vat_amount'] = round($vat, 2);
                
                $totalBase += $row['base_amount'];
                $totalVat += $row['vat_amount'];
                
                $items[] = $row;
            }
        }
        
        return [
            'items'      => $items,
            'total_base' => round($totalBase, 2),
            'total_vat'  => round($totalVat, 2),
            'period'     => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    /**
     * Generate ภ.พ.30 (PP30) — Monthly VAT Return summary
     * Net VAT = Output VAT - Input VAT
     * 
     * @param int    $companyId Company ID
     * @param int    $year      Tax year
     * @param int    $month     Tax month (1-12)
     * @return array Complete PP30 data
     */
    public function generatePP30(int $companyId, int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $outputVAT = $this->getOutputVAT($companyId, $startDate, $endDate);
        $inputVAT = $this->getInputVAT($companyId, $startDate, $endDate);
        
        $netVAT = $outputVAT['total_vat'] - $inputVAT['total_vat'];
        
        return [
            'report_type' => 'PP30',
            'report_name' => 'ภ.พ.30',
            'report_name_en' => 'Monthly VAT Return',
            'company_id' => $companyId,
            'tax_year' => $year,
            'tax_month' => $month,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'output_vat' => $outputVAT,
            'input_vat' => $inputVAT,
            'net_vat' => round($netVAT, 2),
            'vat_payable' => $netVAT > 0 ? round($netVAT, 2) : 0,
            'vat_refundable' => $netVAT < 0 ? round(abs($netVAT), 2) : 0,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get Withholding Tax (WHT) records for a period
     * For ภ.ง.ด.3 (individuals) and ภ.ง.ด.53 (companies)
     * 
     * @param int    $companyId Company ID
     * @param string $startDate Period start
     * @param string $endDate   Period end
     * @param string $type      'pnd3' (individual) or 'pnd53' (company)
     * @return array WHT records
     */
    public function getWithholdingTax(int $companyId, string $startDate, string $endDate, string $type = 'pnd53'): array
    {
        // WHT records from payments that have withholding tax applied
        $companyType = ($type === 'pnd3') ? 'individual' : 'company';
        
        $sql = "SELECT 
                    pay.pay_id,
                    pay.date AS payment_date,
                    po.po_name AS payee_name,
                    c.tax_id AS payee_tax_id,
                    c.com_type AS company_type,
                    pay.volumn AS payment_amount,
                    pay.wht_rate,
                    pay.wht_amount,
                    pay.wht_type AS income_type
                FROM pay
                JOIN po ON pay.po_id = po.po_id
                LEFT JOIN company c ON po.com_id = c.com_id
                WHERE po.com_id_owner = {$companyId}
                AND pay.deleted_at IS NULL
                AND pay.date BETWEEN '{$startDate}' AND '{$endDate}'
                AND pay.wht_amount > 0
                ORDER BY pay.date ASC";
        
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        $totalPayment = 0;
        $totalWHT = 0;
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $totalPayment += floatval($row['payment_amount']);
                $totalWHT += floatval($row['wht_amount']);
                $items[] = $row;
            }
        }
        
        return [
            'report_type' => strtoupper($type),
            'report_name' => $type === 'pnd3' ? 'ภ.ง.ด.3' : 'ภ.ง.ด.53',
            'report_name_en' => $type === 'pnd3' ? 'WHT Certificate (Individual)' : 'WHT Certificate (Company)',
            'items' => $items,
            'total_payment' => round($totalPayment, 2),
            'total_wht' => round($totalWHT, 2),
            'period' => ['start' => $startDate, 'end' => $endDate],
        ];
    }

    /**
     * Get tax period summary for dashboard
     * 
     * @param int $companyId Company ID
     * @param int $year      Tax year
     * @return array Monthly summaries
     */
    public function getAnnualSummary(int $companyId, int $year): array
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $startDate = sprintf('%04d-%02d-01', $year, $m);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Quick summary query (not full PP30)
            $sqlOutput = "SELECT COALESCE(SUM(
                            (SELECT SUM(p.price * p.qty) FROM product p WHERE p.po_id = po.po_id) 
                            * po.vat / 100
                          ), 0) AS output_vat
                          FROM iv JOIN po ON iv.po_id = po.po_id
                          WHERE po.com_id_owner = {$companyId}
                          AND iv.texiv_create BETWEEN '{$startDate}' AND '{$endDate}'
                          AND iv.deleted_at IS NULL AND po.deleted_at IS NULL AND po.vat > 0";
            
            $r = mysqli_query($this->conn, $sqlOutput);
            $outputVat = $r ? floatval(mysqli_fetch_assoc($r)['output_vat'] ?? 0) : 0;
            
            $months[] = [
                'month'      => $m,
                'month_name' => date('F', mktime(0, 0, 0, $m, 1)),
                'output_vat' => round($outputVat, 2),
                'input_vat'  => 0, // TODO: add input VAT summary query
                'net_vat'    => round($outputVat, 2),
                'status'     => ($m < intval(date('n')) && $year <= intval(date('Y'))) ? 'filed' : 'pending',
            ];
        }
        
        return [
            'year'    => $year,
            'months'  => $months,
            'totals'  => [
                'output_vat' => array_sum(array_column($months, 'output_vat')),
                'input_vat'  => array_sum(array_column($months, 'input_vat')),
                'net_vat'    => array_sum(array_column($months, 'net_vat')),
            ],
        ];
    }

    /**
     * Save a generated tax report to DB
     */
    public function saveReport(array $reportData): int
    {
        $sql = "INSERT INTO tax_reports 
                (company_id, report_type, tax_year, tax_month, report_data, status, created_at, created_by)
                VALUES (
                    " . intval($reportData['company_id']) . ",
                    '" . sql_escape($reportData['report_type']) . "',
                    " . intval($reportData['tax_year']) . ",
                    " . intval($reportData['tax_month'] ?? 0) . ",
                    '" . sql_escape(json_encode($reportData)) . "',
                    'draft',
                    NOW(),
                    " . intval($_SESSION['user_id'] ?? 0) . "
                )";
        mysqli_query($this->conn, $sql);
        return (int) mysqli_insert_id($this->conn);
    }

    /**
     * Get saved tax reports
     */
    public function getSavedReports(int $companyId, int $year = 0): array
    {
        $sql = "SELECT * FROM tax_reports WHERE company_id = {$companyId}";
        if ($year > 0) {
            $sql .= " AND tax_year = {$year}";
        }
        $sql .= " ORDER BY tax_year DESC, tax_month DESC, created_at DESC";
        
        $result = mysqli_query($this->conn, $sql);
        $reports = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $reports[] = $row;
            }
        }
        return $reports;
    }
}
