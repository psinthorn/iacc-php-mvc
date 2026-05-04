<?php
/**
 * Tour Booking Voucher PDF
 * Standalone PDF view — generates A4 voucher using mPDF
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

$id     = sql_int($_REQUEST['id'] ?? 0);
$com_id = sql_int($_SESSION['com_id']);
$isThai = ($_SESSION['lang'] ?? '0') === '1';

// ── Fetch booking ───────────────────────────────────────────
$sql = "SELECT b.*, 
               cust.name_en AS customer_name, cust.name_th AS customer_name_th,
               cust.phone AS customer_phone, cust.email AS customer_email,
               agt.name_en AS agent_name, agt.name_th AS agent_name_th,
               loc.name AS pickup_location_name
        FROM tour_bookings b
        LEFT JOIN company cust ON b.customer_id = cust.id
        LEFT JOIN company agt  ON b.agent_id = agt.id
        LEFT JOIN tour_locations loc ON b.pickup_location_id = loc.id
        WHERE b.id = '" . sql_int($id) . "'
          AND b.company_id = '$com_id'
          AND b.deleted_at IS NULL
        LIMIT 1";

$result = mysqli_query($db->conn, $sql);
if (!$result || mysqli_num_rows($result) != 1) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    die('<div style="text-align:center;padding:50px;font-family:Arial;"><h2>Booking Not Found</h2></div>');
}
$booking = mysqli_fetch_assoc($result);

// ── Fetch items with type + model info ──────────────────────
$items = [];
$itemResult = mysqli_query($db->conn, "SELECT bi.*, t.name AS type_name, m.model_name, m.des AS model_des
    FROM tour_booking_items bi
    LEFT JOIN type t ON bi.product_type_id = t.id
    LEFT JOIN model m ON bi.model_id = m.id
    WHERE bi.booking_id = " . sql_int($id) . " ORDER BY bi.id");
while ($itemResult && $row = mysqli_fetch_assoc($itemResult)) {
    $items[] = $row;
}

// ── Fetch passengers ────────────────────────────────────────
$paxList = [];
$paxResult = mysqli_query($db->conn, "SELECT * FROM tour_booking_pax WHERE booking_id = " . sql_int($id) . " ORDER BY id");
while ($paxResult && $row = mysqli_fetch_assoc($paxResult)) {
    $paxList[] = $row;
}

// ── Fetch company (operator) info ───────────────────────────
$vender = mysqli_fetch_array(mysqli_query($db->conn, "
    SELECT company.name_en, company.name_th, company.phone, company.email, company.fax, company.tax, company.logo,
           company_addr.adr_tax, company_addr.city_tax, company_addr.district_tax,
           company_addr.province_tax, company_addr.zip_tax
    FROM company
    LEFT JOIN company_addr ON company.id = company_addr.com_id AND company_addr.deleted_at IS NULL
    WHERE company.id = '$com_id'
    ORDER BY (company_addr.valid_end = '0000-00-00' OR company_addr.valid_end = '9999-12-31') DESC,
             company_addr.valid_start DESC
    LIMIT 1
"));

$logo = $vender['logo'] ?? '';
$companyName = ($isThai && !empty($vender['name_th'])) ? $vender['name_th'] : ($vender['name_en'] ?? '');
$custName    = ($isThai && !empty($booking['customer_name_th'])) ? $booking['customer_name_th'] : ($booking['customer_name'] ?: '-');
$agentName   = ($isThai && !empty($booking['agent_name_th'])) ? $booking['agent_name_th'] : ($booking['agent_name'] ?: '-');

// Item type labels
$itemTypeLabels = [
    'tour'     => $isThai ? 'ทัวร์' : 'Tour',
    'transfer' => $isThai ? 'รถรับส่ง' : 'Transfer',
    'entrance' => $isThai ? 'ค่าเข้าชม' : 'Entrance',
    'extra'    => $isThai ? 'อื่นๆ' : 'Extra',
    'hotel'    => $isThai ? 'โรงแรม' : 'Hotel',
];

$paxTypeLabels = [
    'adult'  => $isThai ? 'ผู้ใหญ่' : 'Adult',
    'child'  => $isThai ? 'เด็ก' : 'Child',
    'infant' => $isThai ? 'ทารก' : 'Infant',
];

$statusLabels = [
    'draft'     => $isThai ? 'ฉบับร่าง'   : 'Draft',
    'confirmed' => $isThai ? 'ยืนยัน'     : 'Confirmed',
    'paid'      => $isThai ? 'ชำระแล้ว'   : 'Paid',
    'completed' => $isThai ? 'เสร็จสิ้น'  : 'Completed',
    'no_show'   => $isThai ? 'ไม่มาตามนัด' : 'No Show',
    'cancelled' => $isThai ? 'ยกเลิก'     : 'Cancelled',
];

$travelDate = !empty($booking['travel_date']) ? date('d/m/Y', strtotime($booking['travel_date'])) : '-';
$bookingDate = !empty($booking['booking_date']) ? date('d/m/Y', strtotime($booking['booking_date'])) : '-';
$pickupTime = !empty($booking['pickup_time']) ? date('H:i', strtotime($booking['pickup_time'])) : '-';

$address = trim(implode(', ', array_filter([
    $vender['adr_tax'] ?? '',
    $vender['district_tax'] ?? '',
    $vender['city_tax'] ?? '',
    $vender['province_tax'] ?? '',
    $vender['zip_tax'] ?? '',
])));

// ── Build HTML ──────────────────────────────────────────────
$html = '
<style>
    body { font-family: garuda, Arial, sans-serif; font-size: 11px; color: #333; }
    .header { text-align: center; margin-bottom: 12px; }
    .header .title { font-size: 20px; font-weight: bold; color: #009688; margin: 6px 0 2px; }
    .header .subtitle { font-size: 11px; color: #777; }
    .company-name { font-size: 14px; font-weight: bold; color: #333; margin-top: 6px; }
    .company-info { font-size: 10px; color: #666; }
    .section-title { font-size: 13px; font-weight: bold; color: #009688; border-bottom: 2px solid #009688; padding-bottom: 4px; margin: 16px 0 8px; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.info td { padding: 3px 6px; font-size: 11px; vertical-align: top; }
    table.info td.label { font-weight: bold; color: #555; width: 130px; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items th { background: #009688; color: white; padding: 6px 8px; font-size: 10px; text-align: left; text-transform: uppercase; letter-spacing: 0.03em; }
    table.items td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e0e0e0; }
    table.items tr:nth-child(even) td { background: #f9fafb; }
    .right { text-align: right; }
    .center { text-align: center; }
    .totals { width: 240px; margin-left: auto; margin-top: 8px; }
    .totals td { padding: 3px 6px; font-size: 11px; }
    .totals .grand td { font-size: 14px; font-weight: bold; color: #009688; border-top: 2px solid #009688; padding-top: 6px; }
    .pax-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .pax-table th { background: #f1f5f9; color: #475569; padding: 5px 8px; font-size: 10px; text-align: left; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .pax-table td { padding: 5px 8px; font-size: 11px; border-bottom: 1px solid #f1f5f9; }
    .type-badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
    .remark-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; font-size: 11px; color: #475569; margin-bottom: 12px; }
    .signatures { width: 100%; margin-top: 40px; }
    .signatures td { width: 50%; text-align: center; font-size: 11px; padding-top: 50px; }
    .signatures .line { border-top: 1px solid #999; display: inline-block; width: 180px; margin-bottom: 4px; }
    .status-label { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
</style>';

// Logo + Header
$html .= '<div class="header">';
if (!empty($logo)) {
    $logoPath = __DIR__ . '/../../upload/' . $logo;
    if (file_exists($logoPath)) {
        $html .= '<img src="' . $logoPath . '" style="max-height:50px; max-width:200px;" /><br>';
    }
}
$html .= '<div class="company-name">' . htmlspecialchars($companyName) . '</div>';
if (!empty($address)) {
    $html .= '<div class="company-info">' . htmlspecialchars($address) . '</div>';
}
$contactParts = array_filter([
    !empty($vender['phone']) ? ($isThai ? 'โทร: ' : 'Tel: ') . htmlspecialchars($vender['phone']) : '',
    !empty($vender['email']) ? ($isThai ? 'อีเมล: ' : 'Email: ') . htmlspecialchars($vender['email']) : '',
]);
if (!empty($contactParts)) {
    $html .= '<div class="company-info">' . implode(' | ', $contactParts) . '</div>';
}
$html .= '<div class="title">' . ($isThai ? 'ใบส่งบริการ / Booking Voucher' : 'Booking Voucher') . '</div>';
$html .= '<div class="subtitle">' . htmlspecialchars($booking['booking_number']) . '</div>';
$html .= '</div>';

// ── Booking Info ─────────────────────────────
$statusLabel = $statusLabels[$booking['status']] ?? $booking['status'];
$statusColor = ['draft' => '#94a3b8', 'confirmed' => '#10b981', 'paid' => '#0d9488', 'completed' => '#3b82f6', 'no_show' => '#d97706', 'cancelled' => '#ef4444'];
$stColor = $statusColor[$booking['status']] ?? '#94a3b8';

$html .= '<div class="section-title">' . ($isThai ? 'ข้อมูลการจอง' : 'Booking Information') . '</div>';
$html .= '<table class="info">';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'วันที่จอง' : 'Booking Date') . '</td>
    <td>' . $bookingDate . '</td>
    <td class="label">' . ($isThai ? 'วันเดินทาง' : 'Trip Date') . '</td>
    <td><strong>' . $travelDate . '</strong></td>
</tr>';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'เลขที่จอง' : 'Booking No.') . '</td>
    <td><strong>' . htmlspecialchars($booking['booking_number']) . '</strong></td>
    <td class="label">' . ($isThai ? 'สถานะ' : 'Status') . '</td>
    <td><span class="status-label" style="background:' . $stColor . '22; color:' . $stColor . ';">' . $statusLabel . '</span></td>
</tr>';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'เลข Voucher' : 'Voucher No.') . '</td>
    <td>' . htmlspecialchars($booking['voucher_number'] ?: '-') . '</td>
    <td class="label">' . ($isThai ? 'สกุลเงิน' : 'Currency') . '</td>
    <td>' . htmlspecialchars($booking['currency']) . '</td>
</tr>';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'ลูกค้า' : 'Customer') . '</td>
    <td>' . htmlspecialchars($custName) . '</td>
    <td class="label">' . ($isThai ? 'ตัวแทน' : 'Agent') . '</td>
    <td>' . htmlspecialchars($agentName) . '</td>
</tr>';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'ผู้จอง' : 'Booking By') . '</td>
    <td>' . htmlspecialchars($booking['booking_by'] ?: '-') . '</td>
    <td class="label">' . ($isThai ? 'สกุลเงิน' : 'Currency') . '</td>
    <td>' . htmlspecialchars($booking['currency']) . '</td>
</tr>';
$html .= '</table>';

// ── Passengers & Pickup ─────────────────────
$html .= '<div class="section-title">' . ($isThai ? 'ผู้เดินทาง & การรับ-ส่ง' : 'Passengers & Pickup') . '</div>';
$html .= '<table class="info">';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'ผู้ใหญ่' : 'Adults') . '</td>
    <td>' . intval($booking['pax_adult']) . '</td>
    <td class="label">' . ($isThai ? 'เด็ก' : 'Children') . '</td>
    <td>' . intval($booking['pax_child']) . '</td>
    <td class="label">' . ($isThai ? 'ทารก' : 'Infants') . '</td>
    <td>' . intval($booking['pax_infant']) . '</td>
    <td class="label">' . ($isThai ? 'รวม' : 'Total') . '</td>
    <td><strong>' . intval($booking['total_pax']) . '</strong></td>
</tr>';
$html .= '</table>';
$html .= '<table class="info">';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'จุดรับ' : 'Pickup Location') . '</td>
    <td>' . htmlspecialchars($booking['pickup_location_name'] ?? '-') . '</td>
    <td class="label">' . ($isThai ? 'โรงแรม' : 'Hotel') . '</td>
    <td>' . htmlspecialchars($booking['pickup_hotel'] ?: '-') . '</td>
</tr>';
$html .= '<tr>
    <td class="label">' . ($isThai ? 'ห้อง' : 'Room') . '</td>
    <td>' . htmlspecialchars($booking['pickup_room'] ?: '-') . '</td>
    <td class="label">' . ($isThai ? 'เวลารับ' : 'Pickup Time') . '</td>
    <td><strong>' . $pickupTime . '</strong></td>
</tr>';
$html .= '</table>';

// ── Items Table ──────────────────────────────
if (!empty($items)) {
    $html .= '<div class="section-title">' . ($isThai ? 'รายการบริการ' : 'Service Items') . '</div>';
    $html .= '<table class="items">';
    $html .= '<tr>
        <th style="width:30px;">#</th>
        <th style="width:65px;">' . ($isThai ? 'ประเภท' : 'Type') . '</th>
        <th>' . ($isThai ? 'รายละเอียด' : 'Description') . '</th>
        <th class="right" style="width:90px;">' . ($isThai ? 'ยอดรวม' : 'Amount') . '</th>
    </tr>';

    foreach ($items as $idx => $item) {
        $typeLabel = $itemTypeLabels[$item['item_type']] ?? $item['item_type'];

        // Build rich description
        $descHtml = htmlspecialchars($item['description']);
        if (!empty($item['model_name'])) {
            $descHtml .= ' | <strong>' . htmlspecialchars($item['model_name']) . '</strong>';
        }
        if (!empty($item['model_des'])) {
            $descHtml .= ' | ' . htmlspecialchars($item['model_des']);
        }
        if (!empty($item['notes'])) {
            $descHtml .= '<br><span style="color:#999; font-size:9px;">' . htmlspecialchars($item['notes']) . '</span>';
        }

        // Pax breakdown sub-table
        $paxLines = json_decode($item['pax_lines_json'] ?? '[]', true) ?: [];
        if (!empty($paxLines)) {
            $descHtml .= '<table style="width:100%; margin-top:4px; border-collapse:collapse;">';
            $descHtml .= '<tr style="background:#f1f5f9;">
                <td style="padding:2px 4px; font-size:9px; font-weight:bold; color:#475569;">' . ($isThai ? 'ประเภท' : 'Type') . '</td>
                <td style="padding:2px 4px; font-size:9px; font-weight:bold; color:#475569;">' . ($isThai ? 'สัญชาติ' : 'Nationality') . '</td>
                <td style="padding:2px 4px; font-size:9px; font-weight:bold; color:#475569; text-align:center;">' . ($isThai ? 'จำนวน' : 'Qty') . '</td>
                <td style="padding:2px 4px; font-size:9px; font-weight:bold; color:#475569; text-align:right;">' . ($isThai ? 'ราคา' : 'Price') . '</td>
                <td style="padding:2px 4px; font-size:9px; font-weight:bold; color:#475569; text-align:right;">' . ($isThai ? 'รวม' : 'Total') . '</td>
            </tr>';
            foreach ($paxLines as $pl) {
                $pType = ($pl['type'] ?? 'adult') === 'child' ? ($isThai ? 'เด็ก' : 'Child') : ($isThai ? 'ผู้ใหญ่' : 'Adult');
                $pNat  = ($pl['nat'] ?? 'thai') === 'foreigner' ? ($isThai ? 'ต่างชาติ' : 'Foreign') : ($isThai ? 'ไทย' : 'Thai');
                $pQty  = intval($pl['qty'] ?? 0);
                $pPrice = floatval($pl['price'] ?? 0);
                $pTotal = $pQty * $pPrice;
                if ($pQty > 0) {
                    $descHtml .= '<tr>
                        <td style="padding:2px 4px; font-size:9px;">' . $pType . '</td>
                        <td style="padding:2px 4px; font-size:9px;">' . $pNat . '</td>
                        <td style="padding:2px 4px; font-size:9px; text-align:center;">×' . $pQty . '</td>
                        <td style="padding:2px 4px; font-size:9px; text-align:right;">@' . number_format($pPrice, 2) . '</td>
                        <td style="padding:2px 4px; font-size:9px; text-align:right; font-weight:bold;">= ' . number_format($pTotal, 2) . '</td>
                    </tr>';
                }
            }
            $descHtml .= '</table>';
        }

        $html .= '<tr>
            <td class="center">' . ($idx + 1) . '</td>
            <td><span class="type-badge" style="background:#e0f2f1; color:#009688;">' . htmlspecialchars($typeLabel) . '</span></td>
            <td>' . $descHtml . '</td>
            <td class="right" style="font-weight:bold;">' . number_format(floatval($item['amount']), 2) . '</td>
        </tr>';
    }
    $html .= '</table>';

    // Totals
    $html .= '<table class="totals">';
    $html .= '<tr><td>' . ($isThai ? 'รวมย่อย' : 'Subtotal') . '</td><td class="right">' . number_format(floatval($booking['subtotal']), 2) . '</td></tr>';
    if (floatval($booking['entrance_fee']) > 0) {
        $html .= '<tr><td>' . ($isThai ? 'ค่าเข้าชม' : 'Entrance Fee') . '</td><td class="right">' . number_format(floatval($booking['entrance_fee']), 2) . '</td></tr>';
    }
    if (floatval($booking['discount']) > 0) {
        $html .= '<tr><td>' . ($isThai ? 'ส่วนลด' : 'Discount') . '</td><td class="right" style="color:#ef4444;">-' . number_format(floatval($booking['discount']), 2) . '</td></tr>';
    }
    if (floatval($booking['vat']) > 0) {
        $html .= '<tr><td>VAT</td><td class="right">' . number_format(floatval($booking['vat']), 2) . '</td></tr>';
    }
    $html .= '<tr class="grand"><td>' . ($isThai ? 'ยอดรวมทั้งหมด' : 'Grand Total') . '</td><td class="right">฿' . number_format(floatval($booking['total_amount']), 2) . '</td></tr>';
    $html .= '</table>';
}

// ── Customer Information ─────────────────────
$cusInfo = [];
if (!empty($booking['customer_id'])) {
    $cusResult = mysqli_query($db->conn, "SELECT name_en, name_th, contact, email, phone, fax, tax FROM company WHERE id = " . sql_int($booking['customer_id']) . " LIMIT 1");
    if ($cusResult && $cr = mysqli_fetch_assoc($cusResult)) $cusInfo = $cr;
}
// Per-booking contact info (from tour_booking_contacts)
$bkContact = [];
$bcResult = mysqli_query($db->conn, "SELECT * FROM tour_booking_contacts WHERE booking_id = " . intval($booking['id']) . " LIMIT 1");
if ($bcResult && $bcr = mysqli_fetch_assoc($bcResult)) $bkContact = $bcr;
if (!empty($cusInfo)) {
    $html .= '<div class="section-title">' . ($isThai ? 'ข้อมูลลูกค้า' : 'Customer Information') . '</div>';
    $html .= '<table class="info">';
    $cusNameFull = ($isThai && !empty($cusInfo['name_th'])) ? $cusInfo['name_th'] : ($cusInfo['name_en'] ?? '-');
    $contactPerson = trim($bkContact['contact_name'] ?? '') ?: trim($cusInfo['contact'] ?? '') ?: '-';
    $contactPhone  = trim($bkContact['mobile'] ?? '') ?: trim($cusInfo['phone'] ?? '') ?: '-';
    $contactEmail  = trim($bkContact['email'] ?? '') ?: trim($cusInfo['email'] ?? '') ?: '-';
    $html .= '<tr><td class="label">' . ($isThai ? 'ชื่อลูกค้า' : 'Customer') . '</td><td><strong>' . htmlspecialchars($cusNameFull) . '</strong></td>';
    $html .= '<td class="label">' . ($isThai ? 'ผู้ติดต่อ' : 'Contact') . '</td><td>' . htmlspecialchars($contactPerson) . '</td></tr>';
    $html .= '<tr><td class="label">' . ($isThai ? 'โทรศัพท์' : 'Phone') . '</td><td>' . htmlspecialchars($contactPhone) . '</td>';
    $html .= '<td class="label">' . ($isThai ? 'อีเมล' : 'Email') . '</td><td>' . htmlspecialchars($contactEmail) . '</td></tr>';
    if (!empty(trim($bkContact['gender'] ?? '')) || !empty(trim($bkContact['nationality'] ?? ''))) {
        $genderLabel = !empty($bkContact['gender']) ? ucfirst($bkContact['gender']) : '-';
        $natLabel    = !empty($bkContact['nationality']) ? $bkContact['nationality'] : '-';
        $html .= '<tr><td class="label">' . ($isThai ? 'เพศ' : 'Gender') . '</td><td>' . htmlspecialchars($genderLabel) . '</td>';
        $html .= '<td class="label">' . ($isThai ? 'สัญชาติ' : 'Nationality') . '</td><td>' . htmlspecialchars($natLabel) . '</td></tr>';
    }
    $html .= '<tr><td class="label">' . ($isThai ? 'แฟกซ์' : 'Fax') . '</td><td>' . htmlspecialchars(trim($cusInfo['fax'] ?? '') ?: '-') . '</td>';
    $html .= '<td class="label">' . ($isThai ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID') . '</td><td>' . htmlspecialchars(trim($cusInfo['tax'] ?? '') ?: '-') . '</td></tr>';
    $html .= '</table>';
}

// ── Remark ───────────────────────────────────
if (!empty($booking['remark'])) {
    $html .= '<div class="section-title">' . ($isThai ? 'หมายเหตุ' : 'Remarks') . '</div>';
    $html .= '<div class="remark-box">' . nl2br(htmlspecialchars($booking['remark'])) . '</div>';
}

// ── Signatures ───────────────────────────────
$html .= '<table class="signatures">';
$html .= '<tr>
    <td>
        <div class="line"></div><br>
        ' . ($isThai ? 'ผู้ดำเนินการ / Operator' : 'Operator') . '
    </td>
    <td>
        <div class="line"></div><br>
        ' . ($isThai ? 'ลูกค้า / Customer' : 'Customer') . '
    </td>
</tr>';
$html .= '</table>';

// ── Footer ───────────────────────────────────
$html .= '<div style="text-align:center; margin-top:16px; font-size:9px; color:#aaa;">'
    . ($isThai ? 'เอกสารนี้จัดทำโดยระบบ iACC' : 'Generated by iACC System')
    . ' | ' . date('d/m/Y H:i')
    . '</div>';

// ── Generate PDF ─────────────────────────────
if (ob_get_level()) ob_end_clean();

require_once 'vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode'               => 'utf-8',
    'format'             => 'A4',
    'default_font'       => 'garuda',
    'margin_left'        => 12,
    'margin_right'       => 12,
    'margin_top'         => 12,
    'margin_bottom'      => 12,
    'margin_header'      => 0,
    'margin_footer'      => 0,
    'autoScriptToLang'   => true,
    'autoLangToFont'     => true,
]);

$mpdf->SetDisplayMode('fullpage');

$filename = 'Voucher-' . ($booking['booking_number'] ?: $id);
$mpdf->WriteHTML($html);
$mpdf->Output($filename . '.pdf', 'I');
exit;
