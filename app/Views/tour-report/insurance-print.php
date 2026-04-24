<?php
/**
 * Tour Report — Passenger Accident Insurance PDF (landscape A4)
 * Standalone mPDF view — one row per passenger
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

// ── Fetch data ──────────────────────────────────────────────
require_once("app/Models/BaseModel.php");
require_once("app/Models/TourReport.php");

$reportModel      = new \App\Models\TourReport();
$tourActivityName = $reportModel->resolveActivityLabel($tourActivity);
$data  = $reportModel->getInsuranceData($com_id, $tourDate, $section, $tourActivity);
$rows  = $data['rows'];

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
    .header .title { font-size: 18px; font-weight: bold; color: #1d4ed8; margin: 4px 0; }
    .header .subtitle { font-size: 11px; color: #555; }
    .company-name { font-size: 13px; font-weight: bold; color: #333; margin-top: 4px; }
    table.insurance { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.insurance th { background: #f1f5f9; color: #475569; padding: 4px 5px; font-size: 8px; text-align: left; text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid #e2e8f0; }
    table.insurance td { padding: 6px 5px; font-size: 9px; border: 1px solid #e2e8f0; vertical-align: middle; height: 28px; }
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
$html .= '<div class="title">' . ($isThai ? 'ประกันอุบัติเหตุผู้โดยสาร' : 'Passenger Accident Insurance') . '</div>';
$html .= '<div class="subtitle">' . ($isThai ? 'วันที่ทัวร์: ' : 'Tour Date: ') . $formattedDate;
if (!empty($tourActivityName)) {
    $html .= ' | ' . ($isThai ? 'กิจกรรม: ' : 'Activity: ') . htmlspecialchars($tourActivityName);
}
$html .= '</div>';
$html .= '</div>';

// Table
$html .= '<table class="insurance">';
$html .= '<tr>
    <th style="width:22px;">' . ($isThai ? 'ที่' : 'No') . '</th>
    <th style="width:160px;">' . ($isThai ? 'ชื่อ' : 'Name') . '</th>
    <th style="width:160px;">' . ($isThai ? 'นามสกุล' : 'Surname') . '</th>
    <th style="width:80px;">' . ($isThai ? 'วันเดือนปีเกิด' : 'Date of Birth') . '</th>
    <th style="width:90px;">' . ($isThai ? 'สัญชาติ' : 'Nationality') . '</th>
    <th style="width:130px;">' . ($isThai ? 'ชื่อโรงแรม' : 'Hotel Name') . '</th>
    <th style="width:50px;">' . ($isThai ? 'ห้องที่' : 'Room No.') . '</th>
    <th style="width:130px;">' . ($isThai ? 'อีเมล' : 'Email') . '</th>
    <th>' . ($isThai ? 'ลายเซ็น' : 'Signature') . '</th>
</tr>';

if (!empty($rows)) {
    foreach ($rows as $i => $r) {
        $num   = $i + 1;
        $hotel = htmlspecialchars($r['pickup_hotel'] ?: ($r['pickup_location_name'] ?? ''));
        $room  = htmlspecialchars($r['pickup_room'] ?: '');
        $email = htmlspecialchars($r['customer_email'] ?: '');
        $name  = htmlspecialchars($r['pax_full_name'] ?? '');
        $nat   = htmlspecialchars($r['pax_nationality'] ?? '');

        $html .= '<tr>
            <td class="center">' . $num . '</td>
            <td>' . $name . '</td>
            <td></td>
            <td></td>
            <td>' . $nat . '</td>
            <td>' . $hotel . '</td>
            <td>' . $room . '</td>
            <td>' . $email . '</td>
            <td></td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="9" style="text-align:center; padding:40px; color:#94a3b8;">'
        . ($isThai ? 'ไม่พบข้อมูลสำหรับวันที่ ' : 'No records found for ') . $formattedDate
        . '</td></tr>';
}

$html .= '</table>';

// Summary
$html .= '<div class="summary">'
    . ($isThai ? 'รวมผู้โดยสาร: ' : 'Total Passengers: ')
    . count($rows) . ' ' . ($isThai ? 'คน' : 'pax')
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

$filename = 'InsuranceReport-' . $tourDate;
$mpdf->WriteHTML($html);
$mpdf->Output($filename . '.pdf', 'I');
exit;
