<?php
/**
 * Tour Report — Pickup Report for Driver PDF (portrait A4)
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
$grouping     = trim($_GET['grouping'] ?? 'time');
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
$result = $reportModel->getPickupData($com_id, $tourDate, $grouping, $tourActivity);
$groups = $result['groups'];
$totals = $result['totals'];

// ── Company info ────────────────────────────────────────────
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company.name_th, company.logo
    FROM company WHERE company.id = '$com_id' LIMIT 1
"));
$companyName = ($isThai && !empty($vender['name_th'])) ? $vender['name_th'] : ($vender['name_en'] ?? '');
$logo = $vender['logo'] ?? '';

$formattedDate = date('d/m/Y', strtotime($tourDate));
$groupLabel = $grouping === 'location'
    ? ($isThai ? 'จัดกลุ่มตามจุดรับ' : 'Grouped by Location')
    : ($isThai ? 'จัดกลุ่มตามเวลา' : 'Grouped by Time');

// ── Build HTML ──────────────────────────────────────────────
$html = '
<style>
    body { font-family: garuda, Arial, sans-serif; font-size: 10px; color: #333; }
    .header { text-align: center; margin-bottom: 10px; }
    .header .title { font-size: 18px; font-weight: bold; color: #009688; margin: 4px 0; }
    .header .subtitle { font-size: 11px; color: #555; }
    .company-name { font-size: 13px; font-weight: bold; }
    .group-header { background: #009688; color: white; padding: 5px 10px; font-size: 12px; font-weight: bold; margin: 10px 0 4px; border-radius: 3px; }
    table.pickup { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
    table.pickup th { background: #f1f5f9; color: #475569; padding: 5px 6px; font-size: 9px; text-align: left; text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid #e2e8f0; }
    table.pickup td { padding: 5px 6px; font-size: 10px; border: 1px solid #e2e8f0; vertical-align: top; }
    .right { text-align: right; }
    .center { text-align: center; }
    .totals-bar { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 10px 16px; margin-top: 14px; font-size: 12px; }
    .totals-bar strong { color: #009688; }
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
$html .= '<div class="title">' . ($isThai ? 'รายงานรับลูกค้า (สำหรับคนขับ)' : 'Pickup Report for Driver') . '</div>';
$html .= '<div class="subtitle">' . ($isThai ? 'วันที่ทัวร์: ' : 'Tour Date: ') . $formattedDate . ' | ' . $groupLabel;
if (!empty($tourActivityName)) {
    $html .= ' | ' . ($isThai ? 'กิจกรรม: ' : 'Activity: ') . htmlspecialchars($tourActivityName);
}
$html .= '</div>';
$html .= '</div>';

// Table header
$thRow = '<tr>
    <th style="width:22px;">#</th>
    <th style="width:42px;">' . ($isThai ? 'เวลารับ' : 'Pickup') . '</th>
    <th style="width:140px;">' . ($isThai ? 'จุดรับ / โรงแรม' : 'Location / Hotel') . '</th>
    <th style="width:30px;">' . ($isThai ? 'ห้อง' : 'Room') . '</th>
    <th style="width:180px;">' . ($isThai ? 'ชื่อลูกค้า' : 'Customer') . '</th>
    <th class="center" style="width:55px;">' . ($isThai ? 'จำนวน' : 'Pax') . '</th>
    <th style="width:80px;">' . ($isThai ? 'โทร/ติดต่อ' : 'Phone') . '</th>
    <th style="width:70px;">' . ($isThai ? 'คนขับ' : 'Driver') . '</th>
    <th style="width:60px;">' . ($isThai ? 'หมายเหตุ' : 'Remark') . '</th>
</tr>';

if (!empty($groups)) {
    $globalNum = 0;

    foreach ($groups as $groupKey => $bookings) {
        $groupPax = 0;
        foreach ($bookings as $b) $groupPax += $b['total_pax'];

        $icon = $grouping === 'location' ? '&#9679;' : '&#9679;';
        $html .= '<div class="group-header">' . $icon . ' ' . htmlspecialchars($groupKey) . ' (' . count($bookings) . ' ' . ($isThai ? 'รายการ' : 'bookings') . ', ' . $groupPax . ' pax)</div>';
        $html .= '<table class="pickup">';
        $html .= $thRow;

        foreach ($bookings as $b) {
            $globalNum++;
            $custName   = ($isThai && !empty($b['customer_name_th'])) ? $b['customer_name_th'] : ($b['customer_name'] ?: '-');
            $pickupTime = !empty($b['pickup_time']) ? date('H:i', strtotime($b['pickup_time'])) : '-';
            $hotel      = $b['pickup_hotel'] ?: ($b['pickup_location_name'] ?? '-');
            $phone      = $b['customer_phone'] ?: '';
            $paxStr     = intval($b['pax_adult']) . 'A';
            if (intval($b['pax_child'])  > 0) $paxStr .= '/' . intval($b['pax_child'])  . 'C';
            if (intval($b['pax_infant']) > 0) $paxStr .= '/' . intval($b['pax_infant']) . 'I';

            $html .= '<tr>
                <td class="center">' . $globalNum . '</td>
                <td><strong>' . $pickupTime . '</strong></td>
                <td>' . htmlspecialchars($hotel) . '</td>
                <td>' . htmlspecialchars($b['pickup_room'] ?: '-') . '</td>
                <td><strong>' . htmlspecialchars($custName) . '</strong></td>
                <td class="center"><strong>' . $b['total_pax'] . '</strong> <span style="color:#888;">(' . $paxStr . ')</span></td>
                <td>' . htmlspecialchars($phone) . '</td>
                <td>' . htmlspecialchars($b['driver_name'] ?? '') . '</td>
                <td>' . htmlspecialchars(mb_substr($b['remark'] ?? '', 0, 30)) . '</td>
            </tr>';
        }
        $html .= '</table>';
    }
} else {
    $html .= '<div style="text-align:center; padding:40px; color:#94a3b8; font-size:14px;">'
        . ($isThai ? 'ไม่พบข้อมูลการจองสำหรับวันที่ ' : 'No bookings found for ') . $formattedDate
        . '</div>';
}

// Totals bar
$html .= '<div class="totals-bar">'
    . ($isThai ? 'รวม: ' : 'Total: ')
    . '<strong>' . $totals['bookings'] . '</strong> ' . ($isThai ? 'รายการ' : 'bookings')
    . ' | <strong>' . $totals['pax'] . '</strong> pax'
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
    'margin_left'      => 12,
    'margin_right'     => 12,
    'margin_top'       => 12,
    'margin_bottom'    => 12,
    'margin_header'    => 0,
    'margin_footer'    => 0,
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
]);

$mpdf->SetDisplayMode('fullpage');

$filename = 'PickupReport-' . $tourDate;
$mpdf->WriteHTML($html);
$mpdf->Output($filename . '.pdf', 'I');
exit;
