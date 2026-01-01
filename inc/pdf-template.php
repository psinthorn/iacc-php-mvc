<?php
/**
 * Shared PDF Template for Invoice, Quotation, Tax Invoice, etc.
 * 
 * Usage:
 *   require_once('inc/pdf-template.php');
 *   $pdfHtml = generatePdfHtml($docType, $docNumber, $data, $vender, $customer, $products, $paymentMethods, $options);
 *   
 * Parameters:
 *   $docType - 'INVOICE', 'QUOTATION', 'TAX INVOICE', 'RECEIPT', etc.
 *   $docNumber - Document number (e.g., 'INV-68000202')
 *   $data - Main document data (date, dis, vat, over, etc.)
 *   $vender - Vendor/Company info
 *   $customer - Customer info  
 *   $products - Array of product rows
 *   $paymentMethods - Array of payment methods (optional)
 *   $options - Additional options array
 */

// Include security helper for e() function
if (!function_exists('e')) {
    require_once(__DIR__ . '/security.php');
}

/**
 * Get PDF CSS styles
 */
function getPdfStyles() {
    return '
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    
    /* Header */
    .header { text-align: center; margin-bottom: 10px; }
    .header img { width: 50px; height: 50px; }
    .company-name { font-size: 14px; font-weight: bold; color: #1a5276; margin-top: 5px; }
    .company-addr { font-size: 10px; color: #444; line-height: 1.4; }
    
    /* Title */
    .title { background: #1a5276; color: #fff; text-align: center; padding: 8px; font-size: 16px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
    
    /* Info Section */
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { vertical-align: top; font-size: 10px; }
    .info-left { width: 55%; }
    .info-right { width: 45%; padding-left: 20px; }
    .inv-box { padding: 4px 0; margin-bottom: 6px; }
    .inv-num { font-size: 13px; font-weight: bold; color: #1a5276; margin: 0; }
    .inv-meta { font-size: 9px; color: #666; margin-top: 2px; }
    .lbl { font-weight: bold; color: #555; width: 55px; }
    .cust-name { font-weight: bold; }
    
    /* Items Table */
    .items { width: 100%; border-collapse: collapse; margin: 10px 0; }
    .items th { background: #1a5276; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
    .items th.r { text-align: right; }
    .items th.c { text-align: center; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
    .items td.r { text-align: right; }
    .items td.c { text-align: center; }
    .items tr:nth-child(even) { background: #f8f9fa; }
    .desc { font-size: 9px; color: #666; margin-top: 3px; line-height: 1.3; }
    
    /* Totals */
    .summary-section { width: 100%; margin-top: 10px; }
    .summary-section td { vertical-align: top; }
    .bank-info { width: 55%; font-size: 10px; }
    .bank-title { font-weight: bold; color: #1a5276; margin-bottom: 5px; }
    .bank-item { margin-bottom: 4px; line-height: 1.3; }
    .bank-name { font-weight: bold; }
    .totals-wrap { width: 45%; text-align: right; }
    .totals { width: 220px; margin-left: auto; }
    .totals td { padding: 4px 0; font-size: 10px; }
    .totals .lbl { text-align: right; padding-right: 12px; color: #555; white-space: nowrap; }
    .totals .val { text-align: right; }
    .totals .grand { border-top: 2px solid #1a5276; }
    .totals .grand td { padding: 8px 0; font-size: 12px; font-weight: bold; color: #1a5276; }
    
    /* Words */
    .words { background: #eaf2f8; padding: 8px 10px; font-size: 10px; color: #333; margin: 10px 0; }
    
    /* Terms */
    .terms { border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; }
    .terms-title { font-weight: bold; font-size: 10px; color: #1a5276; margin-bottom: 5px; }
    .terms-content { font-size: 9px; color: #555; line-height: 1.4; }
    
    /* Signatures */
    .sigs { margin-top: 30px; }
    .sigs td { width: 50%; text-align: center; padding: 0 25px; vertical-align: bottom; }
    .sig-name { font-size: 9px; color: #666; margin-bottom: 40px; }
    .sig-line { border-top: 1px solid #333; padding-top: 5px; font-size: 10px; font-weight: bold; }
    .sig-date { font-size: 9px; color: #888; margin-top: 3px; }
</style>';
}

/**
 * Generate PDF Header HTML
 */
function getPdfHeader($vender, $logo) {
    return '
<!-- Header -->
<div class="header">
    <img src="upload/' . e($logo) . '" width="50" height="50"><br>
    <div class="company-name">' . e($vender['name_en'] ?? '') . '</div>
    <div class="company-addr">
        ' . e($vender['adr_tax'] ?? '') . ' ' . e($vender['city_tax'] ?? '') . ' ' . e($vender['district_tax'] ?? '') . ' ' . e($vender['province_tax'] ?? '') . ' ' . e($vender['zip_tax'] ?? '') . '<br>
        Tel: ' . e($vender['phone'] ?? '') . ' &nbsp; Fax: ' . e($vender['fax'] ?? '') . ' &nbsp; Email: ' . e($vender['email'] ?? '') . ' &nbsp; Tax ID: ' . e($vender['tax'] ?? '') . '
    </div>
</div>';
}

/**
 * Generate PDF Title Bar
 */
function getPdfTitle($docType) {
    return '
<!-- Title -->
<div class="title">' . e($docType) . '</div>';
}

/**
 * Generate PDF Info Section
 */
function getPdfInfoSection($docPrefix, $docNumber, $date, $refNumber, $customer) {
    return '
<!-- Info Section -->
<table class="info-table">
    <tr>
        <td class="info-left">
            <div class="inv-box">
                <div class="inv-num">' . e($docPrefix) . '-' . e($docNumber) . '</div>
                <div class="inv-meta">Date: ' . e($date) . ' &nbsp;|&nbsp; Ref: PO-' . e($refNumber) . '</div>
            </div>
            <table>
                <tr><td class="lbl">Customer</td><td class="cust-name">' . e($customer['name_en'] ?? '') . '</td></tr>
                <tr><td class="lbl">Address</td><td>' . e($customer['adr_tax'] ?? '') . ' ' . e($customer['city_tax'] ?? '') . ' ' . e($customer['district_tax'] ?? '') . ' ' . e($customer['province_tax'] ?? '') . ' ' . e($customer['zip_tax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Tax ID</td><td>' . e($customer['tax'] ?? '') . '</td></tr>
            </table>
        </td>
        <td class="info-right">
            <table>
                <tr><td class="lbl">Tel</td><td>' . e($customer['phone'] ?? '') . '</td></tr>
                <tr><td class="lbl">Fax</td><td>' . e($customer['fax'] ?? '') . '</td></tr>
                <tr><td class="lbl">Email</td><td>' . e($customer['email'] ?? '') . '</td></tr>
            </table>
        </td>
    </tr>
</table>';
}

/**
 * Generate PDF Items Table
 */
function getPdfItemsTable($products, $hasLabour = false) {
    $html = '
<!-- Items -->
<table class="items">
    <tr>
        <th style="width:4%">#</th>
        <th style="width:14%">Model</th>
        <th style="width:' . ($hasLabour ? '28%' : '52%') . '">Description</th>
        <th class="c" style="width:6%">Qty</th>
        <th class="r" style="width:10%">Price</th>';

    if ($hasLabour) {
        $html .= '
        <th class="r" style="width:10%">Equipment</th>
        <th class="r" style="width:8%">Labour</th>
        <th class="r" style="width:10%">L.Total</th>';
    }

    $html .= '
        <th class="r" style="width:10%">Amount</th>
    </tr>';

    $cot = 1;
    foreach ($products as $prod) {
        $html .= '<tr>
        <td>' . $cot . '</td>
        <td>' . e($prod['model'] ?? '') . '</td>
        <td>' . e($prod['name'] ?? '');
        if (!empty($prod['des'])) {
            $safe_des = strip_tags($prod['des'], '<br><b><strong><i><em><u>');
            $html .= '<div class="desc">' . $safe_des . '</div>';
        }
        $html .= '</td>
        <td class="c">' . intval($prod['quantity'] ?? 0) . '</td>
        <td class="r">' . number_format($prod['price'] ?? 0, 2) . '</td>';
        
        if ($hasLabour) {
            $html .= '
        <td class="r">' . number_format($prod['equip'] ?? 0, 2) . '</td>
        <td class="r">' . number_format($prod['labour1'] ?? 0, 2) . '</td>
        <td class="r">' . number_format($prod['labour'] ?? 0, 2) . '</td>';
        }
        
        $html .= '
        <td class="r">' . number_format($prod['total'] ?? 0, 2) . '</td>
    </tr>';
        $cot++;
    }
    $html .= '</table>';
    
    return $html;
}

/**
 * Generate PDF Summary Section (Bank Info + Totals)
 */
function getPdfSummarySection($paymentMethods, $summary, $disco, $overh, $stotal, $vat, $grandTotal, $data) {
    $html = '
<!-- Summary Section -->
<table class="summary-section">
    <tr>
        <td class="bank-info">
            <div class="bank-title">Payment Information</div>';

    // Display payment methods
    if (!empty($paymentMethods)) {
        foreach ($paymentMethods as $pm) {
            if ($pm['method_type'] == 'bank') {
                $html .= '
            <div class="bank-item">
                <span class="bank-name">' . e($pm['method_name']) . '</span><br>
                Account: ' . e($pm['account_number']) . '<br>
                Name: ' . e($pm['account_name']) . '
                ' . (!empty($pm['branch']) ? '<br>Branch: ' . e($pm['branch']) : '') . '
            </div>';
            } elseif ($pm['method_type'] == 'qrcode' && !empty($pm['qr_image'])) {
                $html .= '
            <div class="bank-item">
                <span class="bank-name">' . e($pm['method_name']) . '</span><br>
                <img src="' . e($pm['qr_image']) . '" width="80" height="80">
            </div>';
            }
        }
    } else {
        $html .= '<div class="bank-item">Please contact us for payment details.</div>';
    }

    $html .= '
        </td>
        <td class="totals-wrap">
            <table class="totals">
                <tr><td class="lbl">Subtotal</td><td class="val">' . number_format($summary, 2) . '</td></tr>';

    if (($data['dis'] ?? 0) > 0) {
        $html .= '<tr><td class="lbl">Discount ' . e($data['dis']) . '%</td><td class="val">-' . number_format($disco, 2) . '</td></tr>';
    }

    if (($data['over'] ?? 0) > 0) {
        $html .= '<tr><td class="lbl">Overhead ' . e($data['over']) . '%</td><td class="val">+' . number_format($overh, 2) . '</td></tr>';
    }

    $html .= '
                <tr><td class="lbl">Net Amount</td><td class="val">' . number_format($stotal, 2) . '</td></tr>
                <tr><td class="lbl">VAT ' . e($data['vat'] ?? 0) . '%</td><td class="val">+' . number_format($vat, 2) . '</td></tr>
                <tr class="grand"><td class="lbl">Grand Total</td><td class="val">' . number_format($grandTotal, 2) . '</td></tr>
            </table>
        </td>
    </tr>
</table>';

    return $html;
}

/**
 * Generate Amount in Words Section
 */
function getPdfAmountWords($grandTotal) {
    // Use bahtEng function if available
    $words = function_exists('bahtEng') ? bahtEng($grandTotal) : number_format($grandTotal, 2) . ' Baht';
    return '
<!-- Amount in Words -->
<div class="words"><b>Amount in words:</b> ' . $words . '</div>';
}

/**
 * Generate Terms Section
 */
function getPdfTerms($terms) {
    if (empty($terms)) return '';
    
    return '
<!-- Terms -->
<div class="terms">
    <div class="terms-title">Terms & Conditions</div>
    <div class="terms-content">' . nl2br(e($terms)) . '</div>
</div>';
}

/**
 * Generate Signatures Section
 */
function getPdfSignatures($customerName, $venderName) {
    return '
<!-- Signatures -->
<table class="sigs" width="100%">
    <tr>
        <td>
            <div class="sig-name">' . e($customerName) . '</div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
        <td>
            <div class="sig-name">' . e($venderName) . '</div>
            <div class="sig-line">Authorized Signature</div>
            <div class="sig-date">Date: ____/____/________</div>
        </td>
    </tr>
</table>';
}

/**
 * Generate complete PDF HTML
 * 
 * @param string $docType - Document type title (INVOICE, QUOTATION, TAX INVOICE)
 * @param string $docPrefix - Document prefix (INV, QT, TIV)
 * @param string $docNumber - Document number
 * @param array $data - Main document data
 * @param array $vender - Vendor info
 * @param array $customer - Customer info
 * @param array $products - Products array
 * @param string $logo - Logo filename
 * @param array $paymentMethods - Payment methods array
 * @param array $totals - Array with summary, disco, overh, stotal, vat, grandTotal
 * @param bool $hasLabour - Whether to show labour columns
 * @return string - Complete HTML for PDF
 */
function generatePdfHtml($docType, $docPrefix, $docNumber, $data, $vender, $customer, $products, $logo, $paymentMethods, $totals, $hasLabour = false) {
    $html = getPdfStyles();
    $html .= getPdfHeader($vender, $logo);
    $html .= getPdfTitle($docType);
    $html .= getPdfInfoSection($docPrefix, $docNumber, $data['date'] ?? '', $data['tax'] ?? '', $customer);
    $html .= getPdfItemsTable($products, $hasLabour);
    $html .= getPdfSummarySection(
        $paymentMethods,
        $totals['summary'],
        $totals['disco'],
        $totals['overh'],
        $totals['stotal'],
        $totals['vat'],
        $totals['grandTotal'],
        $data
    );
    $html .= getPdfAmountWords($totals['grandTotal']);
    $html .= getPdfTerms($vender['term'] ?? '');
    $html .= getPdfSignatures($customer['name_en'] ?? '', $vender['name_en'] ?? '');
    
    return $html;
}

/**
 * Output PDF using mPDF
 */
function outputPdf($html, $filename) {
    include(__DIR__ . "/../MPDF/mpdf.php");
    
    $mpdf = new mPDF('th', 'A4', 0, 'Arial', 12, 12, 12, 12, 0, 0);
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, "I");
    exit;
}
