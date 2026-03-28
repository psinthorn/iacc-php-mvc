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
 * Key relationships:
 *   iv.tex = po.id (invoice links to PO)
 *   po.ref = pr.id (PO links to PR)
 *   pr.ven_id = company.id (vendor/seller)
 *   pr.cus_id = company.id (customer/buyer)
 *   product.po_id = po.id (products on a PO)
 *   pay.po_id = po.id (payments on a PO)
 * 
 * @package App\Models
 * @version 1.0.0 — Q2 2026
 */
class TaxReport extends BaseModel
{
    protected string $table = 'tax_reports';

    /**
     * Get Output VAT summary (ภาษีขาย) for a period
     * Output VAT = VAT collected from sales invoices (where we are the vendor)
     * 
     * @param int    $companyId Company ID (our company = pr.ven_id)
     * @param string $startDate Period start (Y-m-d)
     * @param string $endDate   Period end (Y-m-d)
     * @return array ['items' => [...], 'total_base' => float, 'total_vat' => float]
     */
    public function getOutputVAT(int $companyId, string $startDate, string $endDate): array
    {
        $startDate = sql_escape($startDate);
        $endDate = sql_escape($endDate);
        
        $sql = "SELECT 
                    iv.tex AS invoice_no,
                    iv.taxrw AS invoice_display_no,
                    iv.texiv_rw AS tax_invoice_no,
                    iv.texiv_create AS tax_invoice_date,
                    po.name AS description,
                    c_cus.name_en AS customer_name,
                    c_cus.tax AS customer_tax_id,
                    po.vat AS vat_rate,
                    SUM(p.price * p.quantity) AS subtotal,
                    po.dis AS discount_pct,
                    po.over AS overhead_pct
                FROM iv 
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                JOIN product p ON p.po_id = po.id AND p.deleted_at IS NULL
                LEFT JOIN company c_cus ON pr.cus_id = c_cus.id
                WHERE pr.ven_id = {$companyId}
                AND iv.deleted_at IS NULL
                AND po.deleted_at IS NULL
                AND (po.po_id_new IS NULL OR po.po_id_new = '' OR po.po_id_new = '0')
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
     * Input VAT = VAT paid on purchases (where we are the customer)
     * 
     * @param int    $companyId Company ID (our company = pr.cus_id)
     * @param string $startDate Period start
     * @param string $endDate   Period end
     * @return array
     */
    public function getInputVAT(int $companyId, string $startDate, string $endDate): array
    {
        $startDate = sql_escape($startDate);
        $endDate = sql_escape($endDate);
        
        $sql = "SELECT 
                    po.id AS po_id,
                    po.tax AS po_number,
                    po.date AS po_date,
                    po.name AS vendor_name,
                    c_ven.name_en AS vendor_company_name,
                    c_ven.tax AS vendor_tax_id,
                    po.vat AS vat_rate,
                    SUM(p.price * p.quantity) AS subtotal,
                    po.dis AS discount_pct,
                    po.over AS overhead_pct
                FROM po
                JOIN pr ON po.ref = pr.id
                JOIN product p ON p.po_id = po.id AND p.deleted_at IS NULL
                LEFT JOIN company c_ven ON pr.ven_id = c_ven.id
                WHERE pr.cus_id = {$companyId}
                AND po.deleted_at IS NULL
                AND (po.po_id_new IS NULL OR po.po_id_new = '' OR po.po_id_new = '0')
                AND pr.status >= 2
                AND po.date BETWEEN '{$startDate}' AND '{$endDate}'
                AND po.vat > 0
                GROUP BY po.id
                ORDER BY po.date ASC";
        
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
     */
    public function getWithholdingTax(int $companyId, string $startDate, string $endDate, string $type = 'pnd53'): array
    {
        $startDate = sql_escape($startDate);
        $endDate = sql_escape($endDate);
        
        $sql = "SELECT 
                    pay.id AS payment_id,
                    pay.date AS payment_date,
                    po.name AS payee_description,
                    c_payee.name_en AS payee_name,
                    c_payee.tax AS payee_tax_id,
                    pay.volumn AS payment_amount,
                    pay.wht_rate,
                    pay.wht_amount,
                    pay.wht_type
                FROM pay
                JOIN po ON pay.po_id = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c_payee ON pr.ven_id = c_payee.id
                WHERE pr.cus_id = {$companyId}
                AND pay.deleted_at IS NULL
                AND pay.date BETWEEN '{$startDate}' AND '{$endDate}'
                AND pay.wht_amount IS NOT NULL AND pay.wht_amount > 0
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
     * Get annual summary for dashboard — monthly output/input VAT breakdown
     */
    public function getAnnualSummary(int $companyId, int $year): array
    {
        $months = [];
        $totalOutputVat = 0;
        $totalInputVat = 0;
        
        for ($m = 1; $m <= 12; $m++) {
            $startDate = sprintf('%04d-%02d-01', $year, $m);
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Output VAT quick summary (sales — we are vendor)
            $sqlOutput = "SELECT COALESCE(SUM(sub.vat_amount), 0) AS output_vat FROM (
                            SELECT 
                                (SUM(p.price * p.quantity) * (1 - po.dis/100) * (1 + po.over/100)) * (po.vat / 100) AS vat_amount
                            FROM iv 
                            JOIN po ON iv.tex = po.id
                            JOIN pr ON po.ref = pr.id
                            JOIN product p ON p.po_id = po.id AND p.deleted_at IS NULL
                            WHERE pr.ven_id = {$companyId}
                            AND iv.texiv_create BETWEEN '{$startDate}' AND '{$endDate}'
                            AND iv.deleted_at IS NULL AND po.deleted_at IS NULL
                            AND (po.po_id_new IS NULL OR po.po_id_new = '' OR po.po_id_new = '0')
                            AND po.vat > 0
                            GROUP BY iv.tex
                          ) sub";
            
            $r = mysqli_query($this->conn, $sqlOutput);
            $outputVat = $r ? floatval(mysqli_fetch_assoc($r)['output_vat'] ?? 0) : 0;
            
            // Input VAT quick summary (purchases — we are customer)
            $sqlInput = "SELECT COALESCE(SUM(sub.vat_amount), 0) AS input_vat FROM (
                            SELECT 
                                (SUM(p.price * p.quantity) * (1 - po.dis/100) * (1 + po.over/100)) * (po.vat / 100) AS vat_amount
                            FROM po
                            JOIN pr ON po.ref = pr.id
                            JOIN product p ON p.po_id = po.id AND p.deleted_at IS NULL
                            WHERE pr.cus_id = {$companyId}
                            AND po.date BETWEEN '{$startDate}' AND '{$endDate}'
                            AND po.deleted_at IS NULL
                            AND (po.po_id_new IS NULL OR po.po_id_new = '' OR po.po_id_new = '0')
                            AND pr.status >= 2
                            AND po.vat > 0
                            GROUP BY po.id
                          ) sub";
            
            $r2 = mysqli_query($this->conn, $sqlInput);
            $inputVat = $r2 ? floatval(mysqli_fetch_assoc($r2)['input_vat'] ?? 0) : 0;
            
            $netVat = round($outputVat - $inputVat, 2);
            $totalOutputVat += $outputVat;
            $totalInputVat += $inputVat;
            
            // Check saved report status
            $status = 'pending';
            $sqlStatus = "SELECT status FROM tax_reports 
                          WHERE com_id = {$companyId} AND report_type = 'PP30' 
                          AND tax_year = {$year} AND tax_month = {$m} LIMIT 1";
            $r3 = mysqli_query($this->conn, $sqlStatus);
            if ($r3 && $row = mysqli_fetch_assoc($r3)) {
                $status = $row['status'];
            }
            
            $months[] = [
                'month'      => $m,
                'month_name' => date('F', mktime(0, 0, 0, $m, 1)),
                'output_vat' => round($outputVat, 2),
                'input_vat'  => round($inputVat, 2),
                'net_vat'    => $netVat,
                'status'     => $status,
            ];
        }
        
        return [
            'year'    => $year,
            'months'  => $months,
            'totals'  => [
                'output_vat' => round($totalOutputVat, 2),
                'input_vat'  => round($totalInputVat, 2),
                'net_vat'    => round($totalOutputVat - $totalInputVat, 2),
            ],
        ];
    }

    /**
     * Save a generated tax report to DB
     */
    public function saveReport(array $reportData): int
    {
        $comId = intval($reportData['company_id']);
        $reportType = sql_escape($reportData['report_type']);
        $taxYear = intval($reportData['tax_year']);
        $taxMonth = intval($reportData['tax_month'] ?? 0);
        $outputVat = floatval($reportData['output_vat']['total_vat'] ?? 0);
        $inputVat = floatval($reportData['input_vat']['total_vat'] ?? 0);
        $netVat = floatval($reportData['net_vat'] ?? 0);
        $totalWht = floatval($reportData['total_wht'] ?? 0);
        $jsonData = sql_escape(json_encode($reportData, JSON_UNESCAPED_UNICODE));
        $userId = intval($_SESSION['user_id'] ?? 0);
        
        $sql = "INSERT INTO tax_reports 
                (com_id, report_type, tax_year, tax_month, output_vat, input_vat, net_vat, total_wht, report_data, status, created_at, created_by)
                VALUES ({$comId}, '{$reportType}', {$taxYear}, {$taxMonth}, {$outputVat}, {$inputVat}, {$netVat}, {$totalWht}, '{$jsonData}', 'draft', NOW(), {$userId})
                ON DUPLICATE KEY UPDATE 
                    output_vat = {$outputVat}, input_vat = {$inputVat}, net_vat = {$netVat}, 
                    total_wht = {$totalWht}, report_data = '{$jsonData}', updated_at = NOW()";
        mysqli_query($this->conn, $sql);
        return (int) mysqli_insert_id($this->conn);
    }

    /**
     * Get saved tax reports
     */
    public function getSavedReports(int $companyId, int $year = 0): array
    {
        $sql = "SELECT * FROM tax_reports WHERE com_id = {$companyId}";
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
