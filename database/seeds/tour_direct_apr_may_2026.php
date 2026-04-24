<?php
/**
 * Tour Booking Seed — Direct Customer Bookings, Apr / May 2026
 * No agent (agent_id=0, customer_id=0 walk-in tourists)
 * Tours: Angthong | Koh Tao & Koh Nang Yuan | Full Moon Party
 * Company: 165
 *
 * Run: docker exec iacc_php php /var/www/html/database/seeds/tour_direct_apr_may_2026.php
 */

chdir(__DIR__ . '/../../');
require_once 'inc/sys.configs.php';
require_once 'inc/class.dbconn.php';

$db   = new DbConn($config);
$conn = $db->conn;

define('COM_ID', 165);
define('CREATED_BY', 1);

// ─── Reference Data ──────────────────────────────────────────────────────────

$hotels = [
    'Chaweng Lake View Hotel', 'Amari Koh Samui', 'Centara Grand Beach Resort Samui',
    'Anantara Lawana Koh Samui Resort', 'Sala Samui Chaweng Beach Hotel',
    'Imperial Boat House Hotel', 'Nora Beach Resort', 'Baan Chaweng Beach Resort',
    'Bo Phut Resort & Spa', 'Le Meridien Koh Samui', 'Bandara Resort & Spa',
    'Muang Samui Spa Resort', 'Chaweng Regent Beach Resort', 'Tongsai Bay',
    'Samui Palm Beach Resort', 'Laem Set Inn', 'The Wharf Samui',
    'Sunrise Resort', 'Santiburi Samui', 'Vana Belle Koh Samui',
    'SAii Koh Samui Choengmon', 'Fair House Beach Resort', 'KC Grande Resort',
];

$pickupTimes = ['07:00', '07:30', '08:00', '08:30', '09:00'];
$staffNames  = ['Nook Samui', 'Som Reservations', 'Kanya Tours', 'Pim Booking', 'Ben Operations'];

// Foreign tourists — walk-in / direct contact
$contacts = [
    // name, gender, nationality, email_suffix
    ['John Mackenzie',    'male',   'British',        'jmac'],
    ['Anna Holmberg',     'female', 'Swedish',        'anna.h'],
    ['Lucas Brunner',     'male',   'German',         'lbrunner'],
    ['Chloe Dupont',      'female', 'French',         'cdupont'],
    ['Nathan Kowalski',   'male',   'Polish',         'nkowal'],
    ['Ingrid Andersen',   'female', 'Norwegian',      'ingrid.a'],
    ['Leo Tanaka',        'male',   'Japanese',       'ltanaka'],
    ['Mei-Ling Chen',     'female', 'Taiwanese',      'mlchen'],
    ['Ethan Okafor',      'male',   'Nigerian',       'eokafor'],
    ['Priya Sharma',      'female', 'Indian',         'priya.s'],
    ['Jake Sullivan',     'male',   'Irish',          'jsull'],
    ['Zoe Christodoulou', 'female', 'Greek',          'zchris'],
    ['Marco Ricci',       'male',   'Italian',        'mricci'],
    ['Freya Larsen',      'female', 'Danish',         'flarsen'],
    ['Tomás García',      'male',   'Spanish',        'tgarcia'],
    ['Nadia Petrov',      'female', 'Russian',        'npetrov'],
    ['Callum Ross',       'male',   'Scottish',       'cross'],
    ['Yuki Suzuki',       'female', 'Japanese',       'ysuzuki'],
    ['Pieter van Dijk',   'male',   'Dutch',          'pvdijk'],
    ['Saoirse Murphy',    'female', 'Irish',          'smurphy'],
    ['Henrik Sjöberg',    'male',   'Swedish',        'hsjob'],
    ['Katarina Novak',    'female', 'Czech',          'knovak'],
    ['Dylan Hughes',      'male',   'Welsh',          'dhughes'],
    ['Valentina Romano',  'female', 'Italian',        'vromano'],
    ['Rashid Al-Farsi',   'male',   'Omani',          'ralfarsi'],
    ['Emily Watkins',     'female', 'New Zealander',  'ewatkins'],
    ['Bruno Ferreira',    'male',   'Brazilian',      'bferreira'],
    ['Sofia Martínez',    'female', 'Mexican',        'smartinez'],
    ['สมศักดิ์ บุญมา',    'male',   'Thai',           'somsak.b'],
    ['วันดี ศรีสวัสดิ์',  'female', 'Thai',           'wandee.s'],
    ['ชาญชัย แก้วมณี',    'male',   'Thai',           'chanchai.k'],
    ['รุ่งนภา เพ็ชรดี',   'female', 'Thai',           'rung.p'],
    ['ปิยะ สุขสม',        'male',   'Thai',           'piya.s'],
    ['มาริสา วงศ์ไทย',    'female', 'Thai',           'marisa.w'],
    ['กิตติ ธรรมดี',      'male',   'Thai',           'kitti.t'],
    ['พรรณี ชัยชนะ',      'female', 'Thai',           'pannee.c'],
];

// ─── Tour Definitions ────────────────────────────────────────────────────────

$tours = [
    'angthong' => [
        'description'     => 'Angthong National Marine Park',
        'item_type'       => 'tour',
        'product_type_id' => 427,
        'model_id'        => 410,
        'price_thai'      => 1500,
        'price_foreign'   => 2500,
        'entrance_thai'   => 100,
        'entrance_foreign'=> 300,
    ],
    'kohtao' => [
        'description'     => 'Koh Tao Koh Nang Yuan',
        'item_type'       => 'tour',
        'product_type_id' => 428,
        'model_id'        => 0,
        'price_thai'      => 1800,
        'price_foreign'   => 2800,
        'entrance_thai'   => 0,
        'entrance_foreign'=> 100,
    ],
    'fullmoon' => [
        'description'     => 'Full Moon Party Koh Phangan',
        'item_type'       => 'tour',
        'product_type_id' => 437,
        'model_id'        => 418,
        'price_thai'      => 1400,
        'price_foreign'   => 1400,
        'entrance_thai'   => 0,
        'entrance_foreign'=> 0,
    ],
];

// ─── Schedule: [travel_date, tour_key, qty_thai, qty_foreign, status] ────────
// Fullmoon dates: Apr 13, May 12
// Direct customers tend to be smaller groups (1-4 pax), solo or couple travellers

$schedule = [
    // ── APRIL 2026 — 21 direct bookings ───────────────────────
    // Angthong (7)
    ['2026-04-03', 'angthong', 0, 2, 'confirmed'],
    ['2026-04-06', 'angthong', 2, 0, 'confirmed'],
    ['2026-04-10', 'angthong', 0, 3, 'confirmed'],
    ['2026-04-15', 'angthong', 1, 1, 'confirmed'],
    ['2026-04-19', 'angthong', 0, 2, 'confirmed'],
    ['2026-04-23', 'angthong', 3, 0, 'confirmed'],
    ['2026-04-28', 'angthong', 0, 4, 'confirmed'],

    // Koh Tao (7)
    ['2026-04-02', 'kohtao',   0, 2, 'confirmed'],
    ['2026-04-05', 'kohtao',   1, 1, 'confirmed'],
    ['2026-04-09', 'kohtao',   0, 3, 'confirmed'],
    ['2026-04-14', 'kohtao',   2, 0, 'confirmed'],
    ['2026-04-18', 'kohtao',   0, 2, 'confirmed'],
    ['2026-04-24', 'kohtao',   0, 4, 'confirmed'],
    ['2026-04-29', 'kohtao',   1, 2, 'confirmed'],

    // Fullmoon Party — Apr 13 (7)
    ['2026-04-11', 'fullmoon', 0, 2, 'confirmed'],
    ['2026-04-12', 'fullmoon', 1, 1, 'confirmed'],
    ['2026-04-13', 'fullmoon', 0, 3, 'confirmed'],
    ['2026-04-13', 'fullmoon', 2, 1, 'confirmed'],
    ['2026-04-13', 'fullmoon', 0, 2, 'confirmed'],
    ['2026-04-14', 'fullmoon', 0, 4, 'completed'],
    ['2026-04-15', 'fullmoon', 1, 2, 'completed'],

    // ── MAY 2026 — 21 direct bookings ─────────────────────────
    // Angthong (7)
    ['2026-05-03', 'angthong', 0, 2, 'confirmed'],
    ['2026-05-07', 'angthong', 2, 1, 'confirmed'],
    ['2026-05-11', 'angthong', 0, 3, 'confirmed'],
    ['2026-05-16', 'angthong', 1, 0, 'confirmed'],
    ['2026-05-20', 'angthong', 0, 2, 'confirmed'],
    ['2026-05-24', 'angthong', 3, 0, 'confirmed'],
    ['2026-05-29', 'angthong', 0, 4, 'confirmed'],

    // Koh Tao (7)
    ['2026-05-02', 'kohtao',   0, 3, 'confirmed'],
    ['2026-05-06', 'kohtao',   1, 1, 'confirmed'],
    ['2026-05-10', 'kohtao',   0, 2, 'confirmed'],
    ['2026-05-15', 'kohtao',   2, 0, 'confirmed'],
    ['2026-05-19', 'kohtao',   0, 3, 'confirmed'],
    ['2026-05-23', 'kohtao',   1, 2, 'confirmed'],
    ['2026-05-28', 'kohtao',   0, 2, 'confirmed'],

    // Fullmoon Party — May 12 (7)
    ['2026-05-10', 'fullmoon', 0, 2, 'confirmed'],
    ['2026-05-11', 'fullmoon', 2, 0, 'confirmed'],
    ['2026-05-12', 'fullmoon', 0, 3, 'confirmed'],
    ['2026-05-12', 'fullmoon', 1, 2, 'confirmed'],
    ['2026-05-12', 'fullmoon', 0, 4, 'confirmed'],
    ['2026-05-13', 'fullmoon', 0, 2, 'completed'],
    ['2026-05-14', 'fullmoon', 2, 1, 'completed'],
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function esc($conn, $v) { return mysqli_real_escape_string($conn, (string)$v); }

function rand_phone() {
    $pre = ['+66818','+66826','+66837','+66849','+66858','+66872','+66895','+66906','+66917','+66934'];
    return $pre[array_rand($pre)] . str_pad(rand(10000,99999), 5, '0');
}

$dayCounters = [];
function nextBkNum($conn, $date) {
    global $dayCounters;
    $ymd = date('ymd', strtotime($date));
    if (!isset($dayCounters[$date])) {
        $r   = mysqli_query($conn, "SELECT COUNT(*) c FROM tour_bookings WHERE booking_number LIKE 'BK-$ymd-%'");
        $dayCounters[$date] = intval(mysqli_fetch_assoc($r)['c']);
    }
    $dayCounters[$date]++;
    return sprintf('BK-%s-%03d', $ymd, $dayCounters[$date]);
}

// ─── Seed ────────────────────────────────────────────────────────────────────

// ─── Clean Up Previous Direct Bookings (idempotent) ─────────────────────────
echo "=== CLEANUP: Removing existing direct bookings Apr-May 2026 ===\n";
$r = mysqli_query($conn, "SELECT id FROM tour_bookings WHERE company_id=" . COM_ID . " AND agent_id=0 AND travel_date BETWEEN '2026-04-01' AND '2026-05-31'");
$ids = [];
while ($row = mysqli_fetch_assoc($r)) $ids[] = intval($row['id']);
if (!empty($ids)) {
    $idList = implode(',', $ids);
    mysqli_query($conn, "DELETE FROM tour_booking_payments WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_booking_items WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_booking_contacts WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_bookings WHERE id IN ($idList)");
    echo "  Removed " . count($ids) . " existing direct bookings.\n";
} else {
    echo "  No existing direct bookings found.\n";
}

echo "\n=== SEEDING " . count($schedule) . " direct customer bookings (Apr + May 2026) ===\n\n";

$inserted  = 0;
$contactIdx = 0;
$comId = COM_ID;

foreach ($schedule as $row) {
    [$travelDate, $tourKey, $qtyThai, $qtyForeign, $status] = $row;
    $tour = $tours[$tourKey];

    // Pick contact (cycle through list)
    $ct = $contacts[$contactIdx % count($contacts)];
    $contactIdx++;
    [$contactName, $contactGender, $contactNat, $emailSlug] = $ct;
    $contactEmail  = $emailSlug . rand(10,99) . '@gmail.com';
    $contactMobile = rand_phone();

    // Booking metadata
    $bookingDate = date('Y-m-d', strtotime($travelDate . ' -' . rand(1, 10) . ' days'));
    $bookingNumber = nextBkNum($conn, $travelDate);
    $hotel       = $hotels[array_rand($hotels)];
    $pickupTime  = $pickupTimes[array_rand($pickupTimes)];
    $pickupRoom  = rand(100, 999);
    $voucherNum  = 'DIR-' . strtoupper(substr($tourKey,0,2)) . '-' . rand(1000,9999);
    $bookingBy   = $staffNames[array_rand($staffNames)];

    // Amounts
    $pt  = $tour['price_thai'];
    $pf  = $tour['price_foreign'];
    $et  = $tour['entrance_thai'];
    $ef  = $tour['entrance_foreign'];

    $mainSub = ($qtyThai * $pt) + ($qtyForeign * $pf);
    $entSub  = ($qtyThai * $et) + ($qtyForeign * $ef);
    $total   = $mainSub + $entSub;
    $paxAdult = $qtyThai + $qtyForeign;

    // ── tour_bookings ─────────────────────────────────────────
    $sql = "INSERT INTO tour_bookings
        (company_id, booking_number, booking_date,
         customer_id, agent_id, sales_rep_id,
         booking_by, travel_date, pax_adult, pax_child, pax_infant,
         pickup_hotel, pickup_room, pickup_time, voucher_number,
         entrance_fee, subtotal, discount, vat, total_amount,
         amount_paid, amount_due, currency, status, payment_status,
         created_by, created_at)
        VALUES
        ($comId,
         '" . esc($conn,$bookingNumber) . "',
         '" . esc($conn,$bookingDate) . "',
         0, 0, 0,
         '" . esc($conn,$bookingBy) . "',
         '" . esc($conn,$travelDate) . "',
         $paxAdult, 0, 0,
         '" . esc($conn,$hotel) . "',
         '" . esc($conn,$pickupRoom) . "',
         '" . esc($conn,$pickupTime) . "',
         '" . esc($conn,$voucherNum) . "',
         0,
         " . floatval($total) . ",
         0, 0,
         " . floatval($total) . ",
         0, " . floatval($total) . ",
         'THB',
         '" . esc($conn,$status) . "',
         'unpaid',
         " . CREATED_BY . ",
         NOW())";

    if (!mysqli_query($conn, $sql)) {
        echo "  ERROR $bookingNumber: " . mysqli_error($conn) . "\n";
        continue;
    }
    $bid = mysqli_insert_id($conn);

    // ── tour_booking_items (main) ─────────────────────────────
    $paxLinesJson = json_encode([
        ['type'=>'adult','nat'=>'thai',      'qty'=>$qtyThai,    'price'=>$pt],
        ['type'=>'adult','nat'=>'foreigner', 'qty'=>$qtyForeign, 'price'=>$pf],
    ]);
    $avgPrice = $paxAdult > 0 ? round($mainSub / $paxAdult, 2) : 0;
    $sql = "INSERT INTO tour_booking_items
        (booking_id, item_type, description, product_type_id, model_id,
         quantity, unit_price, price_thai, price_foreigner,
         qty_thai, qty_foreigner, amount, pax_lines_json, created_at)
        VALUES
        ($bid,
         '" . esc($conn,$tour['item_type']) . "',
         '" . esc($conn,$tour['description']) . "',
         " . intval($tour['product_type_id']) . ",
         " . intval($tour['model_id']) . ",
         $paxAdult, $avgPrice, $pt, $pf,
         $qtyThai, $qtyForeign,
         " . floatval($mainSub) . ",
         '" . esc($conn,$paxLinesJson) . "',
         NOW())";
    mysqli_query($conn, $sql);

    // ── entrance fee item ─────────────────────────────────────
    if ($entSub > 0) {
        $entDesc  = 'Entrance Fee - ' . $tour['description'];
        $entLines = json_encode([
            ['type'=>'adult','nat'=>'thai',      'qty'=>$qtyThai,    'price'=>$et],
            ['type'=>'adult','nat'=>'foreigner', 'qty'=>$qtyForeign, 'price'=>$ef],
        ]);
        $entAvg = $paxAdult > 0 ? round($entSub / $paxAdult, 2) : 0;
        $sql = "INSERT INTO tour_booking_items
            (booking_id, item_type, description, product_type_id, model_id,
             quantity, unit_price, price_thai, price_foreigner,
             qty_thai, qty_foreigner, amount, pax_lines_json, created_at)
            VALUES
            ($bid, 'entrance',
             '" . esc($conn,$entDesc) . "',
             " . intval($tour['product_type_id']) . ", 0,
             $paxAdult, $entAvg, $et, $ef,
             $qtyThai, $qtyForeign,
             " . floatval($entSub) . ",
             '" . esc($conn,$entLines) . "',
             NOW())";
        mysqli_query($conn, $sql);
    }

    // ── tour_booking_contacts ─────────────────────────────────
    $sql = "INSERT INTO tour_booking_contacts
        (booking_id, contact_name, mobile, email, gender, nationality, created_at)
        VALUES
        ($bid,
         '" . esc($conn,$contactName) . "',
         '" . esc($conn,$contactMobile) . "',
         '" . esc($conn,$contactEmail) . "',
         '" . esc($conn,$contactGender) . "',
         '" . esc($conn,$contactNat) . "',
         NOW())";
    mysqli_query($conn, $sql);

    $inserted++;
    echo sprintf("  %s  %-10s  %-42s  %dT+%dF  %s  %s\n",
        $bookingNumber, $travelDate, $contactName,
        $qtyThai, $qtyForeign, $status,
        number_format($total) . ' THB');
}

// ─── Summary ─────────────────────────────────────────────────────────────────

echo "\n=== DONE: $inserted direct bookings inserted ===\n\n";

$r = mysqli_query($conn, "
    SELECT MONTH(b.travel_date) mo,
           i.description,
           COUNT(DISTINCT b.id) bookings,
           SUM(b.pax_adult) pax,
           SUM(b.total_amount) revenue
    FROM tour_bookings b
    JOIN tour_booking_items i ON i.booking_id=b.id
    WHERE b.company_id=$comId
      AND b.agent_id = 0
      AND b.travel_date BETWEEN '2026-04-01' AND '2026-05-31'
      AND i.description NOT LIKE 'Entrance%'
    GROUP BY MONTH(b.travel_date), i.description
    ORDER BY MONTH(b.travel_date), i.description
");

echo str_pad('Month', 7) . str_pad('Tour', 40) . str_pad('Bk', 5) . str_pad('Pax', 6) . "Revenue\n";
echo str_repeat('-', 65) . "\n";
$months = [4=>'April', 5=>'May'];
$prevMo = null; $moTotal = ['bk'=>0,'pax'=>0,'rev'=>0]; $moName = '';
while ($row = mysqli_fetch_assoc($r)) {
    $mo = intval($row['mo']);
    if ($prevMo && $prevMo !== $mo) {
        echo str_pad("  $moName TOTAL", 47) . str_pad($moTotal['bk'],5) . str_pad($moTotal['pax'],6) . number_format($moTotal['rev']) . "\n";
        echo str_repeat('-', 65) . "\n";
        $moTotal = ['bk'=>0,'pax'=>0,'rev'=>0];
    }
    $moName = $months[$mo] ?? $mo;
    echo str_pad($moName, 7)
        . str_pad($row['description'], 40)
        . str_pad($row['bookings'], 5)
        . str_pad($row['pax'], 6)
        . number_format($row['revenue']) . "\n";
    $moTotal['bk']  += $row['bookings'];
    $moTotal['pax'] += $row['pax'];
    $moTotal['rev'] += $row['revenue'];
    $prevMo = $mo;
}
if ($prevMo) {
    echo str_pad("  $moName TOTAL", 47) . str_pad($moTotal['bk'],5) . str_pad($moTotal['pax'],6) . number_format($moTotal['rev']) . "\n";
}
echo "\n";
