<?php
/**
 * Tour Report — Customer Check-in List PDF (landscape A4)
 * Standalone mPDF view
 */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('display_startup_errors', 0);
ini_set('error_log', __DIR__ . '/../../php-error.log');
error_reporting(E_ALL);

if (ob_get_level()) ob_end_clean();
ob_start();

if (session_status() === PHP_SESSION_NONE) session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.current.php");

global $config;
if (!isset($config)) {
    $config = [
        'hostname' => getenv('DB_HOST') ?: 'mysql',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: 'root',
        'dbname'   => getenv('DB_DATABASE') ?: 'iacc',
    ];
}
$db = new DbConn($config);
$db->checkSecurity();

$com_id  = sql_int($_SESSION['com_id']);
$isThai  = ($_SESSION['lang'] ?? '0') === '1';

// Params
$tourDate     = trim($_GET['tour_date'] ?? '');
$section      = trim($_GET['section'] ?? 'all');
$tourActivity = trim($_GET['activity'] ?? '');

if (empty($tourDate)) {
    http_response_code(400);
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Tour date is required</h2></div>');
}

// ── Fetch data using model ──────────────────────────────────
require_once("app/Models/BaseModel.php");
require_once("app/Models/TourReport.php");

$reportModel      = new \App\Models\TourReport();
$tourActivityName = $reportModel->resolveActivityLabel($tourActivity);
$data = $reportModel->getCheckinData($com_id, $tourDate, $section, $tourActivity);
$direct = $data['direct'];
$agent  = $data['agent'];

// ── Company info ────────────────────────────────────────────
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company.name_th, company.logo
    FROM company WHERE company.id = '$com_id' LIMIT 1
"));
$companyName = ($isThai && !empty($vender['name_th'])) ? $vender['name_th'] : ($vender['name_en'] ?? '');
$logo = $vender['logo'] ?? '';

$formattedDate = date('d/m/Y', strtotime($tourDate));

// ── Build HTML ──────────────────────────────────────────────
$html = '
<style>
    body { font-family: garuda, Arial, sans-serif; font-size: 9px; color: #333; }
    .header { text-align: center; margin-bottom: 10px; }
    .header .title { font-size: 18px; font-weight: bold; color: #009688; margin: 4px 0; }
    .header .subtitle { font-size: 11px; color: #555; }
    .company-name { font-size: 13px; font-weight: bold; color: #333; margin-top: 4px; }
    .section-header { background: #009688; color: white; padding: 6px 10px; font-size: 12px; font-weight: bold; margin: 12px 0 4px; border-radius: 3px; }
    .agent-sub { background: #e0f2f1; padding: 4px 10px; font-size: 10px; font-weight: bold; color: #00695c; margin: 6px 0 3px; border-left: 3px solid #009688; }
    table.checkin { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.checkin th { background: #f1f5f9; color: #475569; padding: 4px 5px; font-size: 8px; text-align: left; text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid #e2e8f0; }
    table.checkin td { padding: 4px 5px; font-size: 9px; border: 1px solid #e2e8f0; vertical-align: top; }
    table.checkin tr.blank td { height: 22px; }
    .right { text-align: right; }
    .center { text-align: center; }
    .summary { margin-top: 8px; font-size: 10px; color: #475569; }
    .footer { text-align: center; margin-top: 12px; font-size: 8px; color: #aaa; }
</style>';

// Header
$html .= '<div class="header">';
if (!empty($logo)) {
    $logoPath = __DIR__ . '/../../upload/' . $logo;
    if (file_exists($logoPath)) {
        $html .= '<img src="' . $logoPath . '" style="max-height:40px; max-width:160px;" /><br>';
    }
}
$html .= '<div class="company-name">' . htmlspecialchars($companyName) . '</div>';
$html .= '<div class="title">' . ($isThai ? 'ใบเช็คอินลูกค้า' : 'Customer Check-in List') . '</div>';
$html .= '<div class="subtitle">' . ($isThai ? 'วันที่ทัวร์: ' : 'Tour Date: ') . $formattedDate;
if (!empty($tourActivityName)) {
    $html .= ' | ' . ($isThai ? 'กิจกรรม: ' : 'Activity: ') . htmlspecialchars($tourActivityName);
}
$html .= '</div>';
$html .= '</div>';

// Table header row template
// Columns: # | Hotel | Customer | Room | Adult | Child | Agent | Pickup | Sale Rep | Entrance | Signature/Remark
$thRow = '<tr>
    <th style="width:22px;">#</th>
    <th style="width:95px;">' . ($isThai ? 'โรงแรม' : 'Hotel') . '</th>
    <th style="width:110px;">' . ($isThai ? 'ชื่อลูกค้า' : 'Customer Name') . '</th>
    <th style="width:38px;">' . ($isThai ? 'ห้อง' : 'Room') . '</th>
    <th class="center" style="width:28px;">' . ($isThai ? 'ผญ.' : 'Adult') . '</th>
    <th class="center" style="width:28px;">' . ($isThai ? 'เด็ก' : 'Child') . '</th>
    <th style="width:80px;">' . ($isThai ? 'ตัวแทน' : 'Agent') . '</th>
    <th style="width:48px;">' . ($isThai ? 'เวลารับ' : 'Pickup') . '</th>
    <th style="width:50px;">' . ($isThai ? 'เซลล์' : 'Sale Rep') . '</th>
    <th class="right" style="width:50px;">' . ($isThai ? 'ค่าเข้าชม' : 'Entrance') . '</th>
    <th style="width:145px;">' . ($isThai ? 'ลายเซ็น/หมายเหตุ' : 'Signature/Remark') . '</th>
</tr>';

$globalNum = 0;
$totalPaxAll = 0;

// ── Section: Direct Bookings ─────────────────
if (!empty($direct)) {
    $sectionPax = 0;
    foreach ($direct as $b) $sectionPax += $b['total_pax'];
    $totalPaxAll += $sectionPax;

    $html .= '<div class="section-header">' . ($isThai ? 'จองตรง (Direct Booking)' : 'Direct Bookings') . ' — ' . count($direct) . ' ' . ($isThai ? 'รายการ' : 'bookings') . ', ' . $sectionPax . ' pax</div>';
    $html .= '<table class="checkin">';
    $html .= $thRow;

    foreach ($direct as $b) {
        $globalNum++;
        $custName = ($isThai && !empty($b['customer_name_th'])) ? $b['customer_name_th'] : ($b['customer_name'] ?: '-');
        $pickupTime = !empty($b['pickup_time']) ? date('H:i', strtotime($b['pickup_time'])) : '-';

        // Main booking row — # | Hotel | Customer | Room | Adult | Child | Agent | Pickup | Sale Rep | Entrance | Signature/Remark
        $html .= '<tr>
            <td class="center">' . $globalNum . '</td>
            <td>' . htmlspecialchars($b['pickup_hotel'] ?: ($b['pickup_location_name'] ?? '-')) . '</td>
            <td><strong>' . htmlspecialchars($custName) . '</strong></td>
            <td>' . htmlspecialchars($b['pickup_room'] ?: '-') . '</td>
            <td class="center">' . intval($b['pax_adult']) . '</td>
            <td class="center">' . intval($b['pax_child']) . '</td>
            <td>-</td>
            <td>' . $pickupTime . '</td>
            <td>' . htmlspecialchars($b['sales_rep_name'] ?: '-') . '</td>
            <td class="right">' . ($b['entrance_fee'] > 0 ? number_format($b['entrance_fee'], 0) : '-') . '</td>
            <td></td>
        </tr>';

        // Blank rows for handwritten signatures (total_pax rows)
        for ($i = 0; $i < $b['total_pax']; $i++) {
            $html .= '<tr class="blank">
                <td></td><td></td><td></td><td></td><td></td><td></td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>';
        }
    }
    $html .= '</table>';
}

// ── Section: Agent Bookings ──────────────────
if (!empty($agent)) {
    $agentPaxTotal = 0;
    $agentBookingTotal = 0;
    foreach ($agent as $ag) {
        $agentBookingTotal += count($ag['bookings']);
        foreach ($ag['bookings'] as $b) $agentPaxTotal += $b['total_pax'];
    }
    $totalPaxAll += $agentPaxTotal;

    $html .= '<div class="section-header">' . ($isThai ? 'จองผ่านตัวแทน (Tour Agent)' : 'Tour Agent Bookings') . ' — ' . $agentBookingTotal . ' ' . ($isThai ? 'รายการ' : 'bookings') . ', ' . $agentPaxTotal . ' pax</div>';

    foreach ($agent as $agentId => $agGroup) {
        $agPax = 0;
        foreach ($agGroup['bookings'] as $b) $agPax += $b['total_pax'];

        $html .= '<div class="agent-sub">' . ($isThai ? 'ตัวแทน: ' : 'Agent: ') . htmlspecialchars($agGroup['agent_name']) . ' (' . count($agGroup['bookings']) . ' ' . ($isThai ? 'รายการ' : 'bookings') . ', ' . $agPax . ' pax)</div>';
        $html .= '<table class="checkin">';
        $html .= $thRow;

        foreach ($agGroup['bookings'] as $b) {
            $globalNum++;
            $custName = ($isThai && !empty($b['customer_name_th'])) ? $b['customer_name_th'] : ($b['customer_name'] ?: '-');
            $agName   = ($isThai && !empty($b['agent_name_th'])) ? $b['agent_name_th'] : ($b['agent_name'] ?: '-');
            $pickupTime = !empty($b['pickup_time']) ? date('H:i', strtotime($b['pickup_time'])) : '-';

            $html .= '<tr>
                <td class="center">' . $globalNum . '</td>
                <td>' . htmlspecialchars($b['pickup_hotel'] ?: ($b['pickup_location_name'] ?? '-')) . '</td>
                <td><strong>' . htmlspecialchars($custName) . '</strong></td>
                <td>' . htmlspecialchars($b['pickup_room'] ?: '-') . '</td>
                <td class="center">' . intval($b['pax_adult']) . '</td>
                <td class="center">' . intval($b['pax_child']) . '</td>
                <td>' . htmlspecialchars($agName) . '</td>
                <td>' . $pickupTime . '</td>
                <td>' . htmlspecialchars($b['sales_rep_name'] ?: '-') . '</td>
                <td class="right">' . ($b['entrance_fee'] > 0 ? number_format($b['entrance_fee'], 0) : '-') . '</td>
                <td></td>
            </tr>';

            for ($i = 0; $i < $b['total_pax']; $i++) {
                $html .= '<tr class="blank">
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                    <td></td><td></td><td></td><td></td><td></td>
                </tr>';
            }
        }
        $html .= '</table>';
    }
}

// Empty state
if (empty($direct) && empty($agent)) {
    $html .= '<div style="text-align:center; padding:40px; color:#94a3b8; font-size:14px;">'
        . ($isThai ? 'ไม่พบข้อมูลการจองสำหรับวันที่ ' : 'No bookings found for ') . $formattedDate
        . '</div>';
}

// Summary
$html .= '<div class="summary">'
    . ($isThai ? 'รวมทั้งหมด: ' : 'Grand Total: ')
    . $globalNum . ' ' . ($isThai ? 'รายการ' : 'bookings')
    . ', ' . $totalPaxAll . ' pax'
    . '</div>';

// Footer
$html .= '<div class="footer">'
    . ($isThai ? 'พิมพ์เมื่อ: ' : 'Printed: ') . date('d/m/Y H:i')
    . ' | ' . htmlspecialchars($companyName)
    . '</div>';

// ── Generate PDF ─────────────────────────────
if (ob_get_level()) ob_end_clean();

require_once 'vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode'             => 'utf-8',
    'format'           => 'A4-L',
    'default_font'     => 'garuda',
    'margin_left'      => 10,
    'margin_right'     => 10,
    'margin_top'       => 10,
    'margin_bottom'    => 10,
    'margin_header'    => 0,
    'margin_footer'    => 0,
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
]);

$mpdf->SetDisplayMode('fullpage');

$filename = 'CheckinList-' . $tourDate;
$mpdf->WriteHTML($html);
$mpdf->Output($filename . '.pdf', 'I');
exit;
