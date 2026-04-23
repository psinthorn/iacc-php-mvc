<?php
/**
 * Tour Booking Seed — Mar / Apr / May 2026
 * Tours: Angthong National Marine Park | Koh Tao & Koh Nang Yuan | Full Moon Party
 * Company: 165 (บริษัท มายสมุย ไอส์แลนด์ทัวร์ จำกัด)
 *
 * Run: docker exec iacc_php php /var/www/html/database/seeds/tour_mar_apr_may_2026.php
 */

chdir(__DIR__ . '/../../');
require_once 'inc/sys.configs.php';
require_once 'inc/class.dbconn.php';

$db   = new DbConn($config);
$conn = $db->conn;

define('COM_ID', 165);
define('CREATED_BY', 1);

// ─── Reference Data ─────────────────────────────────────────────────────────

// Agents: company.id (used as agent_id in tour_bookings)
$agents = [
    289 => 'Samui Island Tours Co.,Ltd.',
    290 => 'Koh Tao Dive & Tour Co.,Ltd.',
    291 => 'Angthong Marine Trips Co.,Ltd.',
    292 => 'Gulf Explorer Travel Co.,Ltd.',
    293 => 'Phangan Paradise Tours Co.,Ltd.',
    247 => 'Asian Trails Ltd. (Branch 00003)',
];

// Customer company IDs for company 165
$customers = [
    244, 245, 246, 247, 248, 249, 250, 251, 252, 253,
    254, 255, 256, 257, 258, 259, 260, 261, 262, 263,
    264, 265, 266, 267, 268, 269, 270, 271, 272, 273,
    274, 275, 276, 277, 278, 279, 280, 281, 282, 283,
    284, 285, 286, 287, 288,
];

// Hotels for pickup
$hotels = [
    'Chaweng Lake View Hotel', 'Amari Koh Samui', 'Centara Grand Beach Resort Samui',
    'Anantara Lawana Koh Samui Resort', 'Sala Samui Chaweng Beach Hotel',
    'Imperial Boat House Hotel', 'Nora Beach Resort', 'Baan Chaweng Beach Resort',
    'Bo Phut Resort & Spa', 'Le Meridien Koh Samui', 'Bandara Resort & Spa',
    'Muang Samui Spa Resort', 'Chaweng Regent Beach Resort', 'Tongsai Bay',
    'Samui Palm Beach Resort', 'Rocky Resort Koh Samui', 'The Wharf Samui',
    'Sunrise Resort', 'Laem Set Inn', 'Santiburi Samui',
];

// Pickup times
$pickupTimes = ['07:00', '07:30', '08:00', '08:30', '09:00'];

// Foreign tourist names
$foreignNames = [
    ['James Miller', 'male', 'British'], ['Sarah Johnson', 'female', 'Australian'],
    ['David Wilson', 'male', 'American'], ['Emma Thompson', 'female', 'Canadian'],
    ['Michael Brown', 'male', 'German'], ['Olivia Davis', 'female', 'French'],
    ['Robert Taylor', 'male', 'Dutch'], ['Sophie Martin', 'female', 'Swedish'],
    ['William Anderson', 'male', 'Norwegian'], ['Isabella White', 'female', 'Danish'],
    ['Christopher Lee', 'male', 'Korean'], ['Mia Jackson', 'female', 'Japanese'],
    ['Alexander Harris', 'male', 'Russian'], ['Charlotte Clark', 'female', 'Finnish'],
    ['Benjamin Lewis', 'male', 'Israeli'], ['Amelia Robinson', 'female', 'Austrian'],
    ['Daniel Walker', 'male', 'Swiss'], ['Grace Hall', 'female', 'Belgian'],
    ['Matthew Young', 'male', 'American'], ['Lily Allen', 'female', 'British'],
    ['Joshua King', 'male', 'Chinese'], ['Chloe Wright', 'female', 'Singaporean'],
    ['Andrew Scott', 'male', 'Irish'], ['Hannah Green', 'female', 'New Zealander'],
    ['Ryan Adams', 'male', 'Australian'], ['Victoria Baker', 'female', 'Canadian'],
];

// Thai tourist names
$thaiNames = [
    ['สมชาย ใจดี', 'male', 'Thai'], ['สุดา มีสุข', 'female', 'Thai'],
    ['วิชัย เจริญ', 'male', 'Thai'], ['นภา ทองดี', 'female', 'Thai'],
    ['ประเสริฐ รุ่งเรือง', 'male', 'Thai'], ['มาลี ศรีสุข', 'female', 'Thai'],
    ['อนุชา พรมดี', 'male', 'Thai'], ['จิราภา วงศ์ดี', 'female', 'Thai'],
    ['สุรชัย แสงทอง', 'male', 'Thai'], ['กัญญา เพชรดี', 'female', 'Thai'],
    ['ธีรพล มณีรัตน์', 'male', 'Thai'], ['อภิสรา พลายงาม', 'female', 'Thai'],
];

// Booking_by users (staff names)
$staffNames = ['Nook Samui', 'Som Reservations', 'Kanya Tours', 'Pim Booking', 'Ben Operations'];

// ─── Helper Functions ────────────────────────────────────────────────────────

function esc($conn, $v) { return mysqli_real_escape_string($conn, (string)$v); }

function rand_phone() {
    $prefixes = ['+66818', '+66826', '+66837', '+66849', '+66858', '+66872', '+66895', '+66906', '+66917', '+66934'];
    return $prefixes[array_rand($prefixes)] . str_pad(rand(10000, 99999), 5, '0');
}

function rand_el(&$arr) { return $arr[array_rand($arr)]; }

// ─── Tour Definitions ────────────────────────────────────────────────────────

$tours = [
    'angthong' => [
        'label'           => 'Angthong National Marine Park',
        'description'     => 'Angthong National Marine Park',
        'item_type'       => 'tour',
        'product_type_id' => 427,
        'model_id'        => 410,   // SM-IT-03-AM
        'price_thai'      => 1500,
        'price_foreign'   => 2500,
        'entrance_thai'   => 100,
        'entrance_foreign'=> 300,
        'agents'          => [291, 289, 247, 292], // preferred agents
    ],
    'kohtao' => [
        'label'           => 'Koh Tao & Koh Nang Yuan',
        'description'     => 'Koh Tao Koh Nang Yuan',
        'item_type'       => 'tour',
        'product_type_id' => 428,
        'model_id'        => 0,
        'price_thai'      => 1800,
        'price_foreign'   => 2800,
        'entrance_thai'   => 0,
        'entrance_foreign'=> 100,
        'agents'          => [290, 289, 247, 292],
    ],
    'fullmoon' => [
        'label'           => 'Full Moon Party Koh Phangan',
        'description'     => 'Full Moon Party Koh Phangan',
        'item_type'       => 'tour',
        'product_type_id' => 437,
        'model_id'        => 418,   // SM-PT-11-VIP
        'price_thai'      => 1400,
        'price_foreign'   => 1400,
        'entrance_thai'   => 0,
        'entrance_foreign'=> 0,
        'agents'          => [293, 289, 292, 247],
    ],
];

// ─── Monthly Schedule ────────────────────────────────────────────────────────
// Fullmoon dates: Mar 14, Apr 13, May 12 (bookings cluster ±3 days around these)

$schedule = [
    // [ travel_date, tour_key, qty_thai, qty_foreign, status ]
    // MARCH 2026 — 55 bookings
    // Angthong (19 bookings)
    ['2026-03-03', 'angthong', 3, 2, 'confirmed'],
    ['2026-03-05', 'angthong', 0, 4, 'confirmed'],
    ['2026-03-07', 'angthong', 2, 3, 'confirmed'],
    ['2026-03-09', 'angthong', 4, 0, 'confirmed'],
    ['2026-03-11', 'angthong', 1, 5, 'confirmed'],
    ['2026-03-14', 'angthong', 3, 2, 'confirmed'],
    ['2026-03-17', 'angthong', 2, 4, 'confirmed'],
    ['2026-03-19', 'angthong', 0, 6, 'confirmed'],
    ['2026-03-21', 'angthong', 5, 1, 'confirmed'],
    ['2026-03-23', 'angthong', 2, 3, 'confirmed'],
    ['2026-03-25', 'angthong', 3, 2, 'confirmed'],
    ['2026-03-27', 'angthong', 0, 4, 'completed'],
    ['2026-03-28', 'angthong', 4, 2, 'completed'],
    ['2026-03-29', 'angthong', 2, 3, 'confirmed'],
    ['2026-03-30', 'angthong', 3, 1, 'confirmed'],
    ['2026-03-30', 'angthong', 0, 5, 'confirmed'],
    ['2026-03-31', 'angthong', 2, 2, 'confirmed'],
    ['2026-03-31', 'angthong', 4, 0, 'confirmed'],
    ['2026-03-31', 'angthong', 1, 4, 'draft'],
    // Koh Tao (19 bookings)
    ['2026-03-02', 'kohtao', 0, 3, 'confirmed'],
    ['2026-03-04', 'kohtao', 2, 2, 'confirmed'],
    ['2026-03-06', 'kohtao', 4, 1, 'confirmed'],
    ['2026-03-08', 'kohtao', 0, 5, 'confirmed'],
    ['2026-03-10', 'kohtao', 3, 2, 'confirmed'],
    ['2026-03-12', 'kohtao', 2, 3, 'confirmed'],
    ['2026-03-15', 'kohtao', 0, 4, 'confirmed'],
    ['2026-03-17', 'kohtao', 5, 0, 'confirmed'],
    ['2026-03-19', 'kohtao', 2, 2, 'confirmed'],
    ['2026-03-21', 'kohtao', 0, 6, 'confirmed'],
    ['2026-03-24', 'kohtao', 3, 3, 'confirmed'],
    ['2026-03-25', 'kohtao', 1, 4, 'confirmed'],
    ['2026-03-26', 'kohtao', 4, 2, 'completed'],
    ['2026-03-27', 'kohtao', 0, 3, 'completed'],
    ['2026-03-28', 'kohtao', 2, 4, 'confirmed'],
    ['2026-03-29', 'kohtao', 3, 1, 'confirmed'],
    ['2026-03-30', 'kohtao', 0, 5, 'confirmed'],
    ['2026-03-31', 'kohtao', 4, 2, 'draft'],
    ['2026-03-31', 'kohtao', 2, 3, 'confirmed'],
    // Fullmoon (17 bookings — Mar 14 is Fullmoon)
    ['2026-03-12', 'fullmoon', 2, 3, 'confirmed'],
    ['2026-03-13', 'fullmoon', 0, 5, 'confirmed'],
    ['2026-03-13', 'fullmoon', 3, 2, 'confirmed'],
    ['2026-03-14', 'fullmoon', 1, 4, 'confirmed'],
    ['2026-03-14', 'fullmoon', 0, 6, 'confirmed'],
    ['2026-03-14', 'fullmoon', 4, 2, 'confirmed'],
    ['2026-03-14', 'fullmoon', 2, 3, 'confirmed'],
    ['2026-03-14', 'fullmoon', 0, 5, 'confirmed'],
    ['2026-03-14', 'fullmoon', 3, 1, 'confirmed'],
    ['2026-03-15', 'fullmoon', 2, 4, 'completed'],
    ['2026-03-15', 'fullmoon', 0, 3, 'completed'],
    ['2026-03-15', 'fullmoon', 4, 2, 'completed'],
    ['2026-03-16', 'fullmoon', 1, 5, 'completed'],
    ['2026-03-16', 'fullmoon', 3, 2, 'completed'],
    ['2026-03-16', 'fullmoon', 0, 4, 'completed'],
    ['2026-03-17', 'fullmoon', 2, 3, 'confirmed'],
    ['2026-03-17', 'fullmoon', 0, 6, 'confirmed'],

    // APRIL 2026 — 57 bookings
    // Angthong (20 bookings)
    ['2026-04-01', 'angthong', 3, 2, 'confirmed'],
    ['2026-04-02', 'angthong', 0, 5, 'confirmed'],
    ['2026-04-04', 'angthong', 4, 1, 'confirmed'],
    ['2026-04-06', 'angthong', 2, 3, 'confirmed'],
    ['2026-04-07', 'angthong', 0, 6, 'confirmed'],
    ['2026-04-09', 'angthong', 5, 0, 'confirmed'],
    ['2026-04-11', 'angthong', 2, 4, 'confirmed'],
    ['2026-04-14', 'angthong', 3, 3, 'confirmed'],
    ['2026-04-16', 'angthong', 0, 5, 'confirmed'],
    ['2026-04-18', 'angthong', 4, 2, 'confirmed'],
    ['2026-04-20', 'angthong', 2, 3, 'confirmed'],
    ['2026-04-22', 'angthong', 1, 5, 'confirmed'],
    ['2026-04-24', 'angthong', 3, 2, 'confirmed'],
    ['2026-04-25', 'angthong', 0, 4, 'confirmed'],
    ['2026-04-26', 'angthong', 4, 1, 'confirmed'],
    ['2026-04-27', 'angthong', 2, 3, 'confirmed'],
    ['2026-04-28', 'angthong', 0, 6, 'confirmed'],
    ['2026-04-29', 'angthong', 3, 2, 'confirmed'],
    ['2026-04-30', 'angthong', 2, 4, 'confirmed'],
    ['2026-04-30', 'angthong', 4, 0, 'draft'],
    // Koh Tao (20 bookings)
    ['2026-04-01', 'kohtao', 0, 4, 'confirmed'],
    ['2026-04-03', 'kohtao', 2, 3, 'confirmed'],
    ['2026-04-05', 'kohtao', 3, 2, 'confirmed'],
    ['2026-04-07', 'kohtao', 0, 5, 'confirmed'],
    ['2026-04-08', 'kohtao', 4, 1, 'confirmed'],
    ['2026-04-10', 'kohtao', 2, 3, 'confirmed'],
    ['2026-04-12', 'kohtao', 0, 6, 'confirmed'],
    ['2026-04-15', 'kohtao', 3, 2, 'confirmed'],
    ['2026-04-17', 'kohtao', 1, 4, 'confirmed'],
    ['2026-04-19', 'kohtao', 4, 2, 'confirmed'],
    ['2026-04-21', 'kohtao', 2, 3, 'confirmed'],
    ['2026-04-22', 'kohtao', 0, 5, 'confirmed'],
    ['2026-04-23', 'kohtao', 3, 1, 'confirmed'],
    ['2026-04-24', 'kohtao', 2, 4, 'confirmed'],
    ['2026-04-25', 'kohtao', 0, 3, 'confirmed'],
    ['2026-04-26', 'kohtao', 4, 2, 'confirmed'],
    ['2026-04-27', 'kohtao', 1, 5, 'confirmed'],
    ['2026-04-28', 'kohtao', 3, 2, 'confirmed'],
    ['2026-04-29', 'kohtao', 0, 4, 'confirmed'],
    ['2026-04-30', 'kohtao', 2, 3, 'draft'],
    // Fullmoon (17 bookings — Apr 13 is Fullmoon, Songkran peak)
    ['2026-04-11', 'fullmoon', 2, 4, 'confirmed'],
    ['2026-04-12', 'fullmoon', 0, 6, 'confirmed'],
    ['2026-04-12', 'fullmoon', 3, 3, 'confirmed'],
    ['2026-04-13', 'fullmoon', 1, 5, 'confirmed'],
    ['2026-04-13', 'fullmoon', 0, 8, 'confirmed'],
    ['2026-04-13', 'fullmoon', 4, 3, 'confirmed'],
    ['2026-04-13', 'fullmoon', 2, 4, 'confirmed'],
    ['2026-04-13', 'fullmoon', 0, 6, 'confirmed'],
    ['2026-04-13', 'fullmoon', 5, 2, 'confirmed'],
    ['2026-04-14', 'fullmoon', 2, 5, 'completed'],
    ['2026-04-14', 'fullmoon', 0, 4, 'completed'],
    ['2026-04-14', 'fullmoon', 3, 3, 'completed'],
    ['2026-04-15', 'fullmoon', 1, 6, 'completed'],
    ['2026-04-15', 'fullmoon', 0, 5, 'completed'],
    ['2026-04-16', 'fullmoon', 2, 4, 'completed'],
    ['2026-04-16', 'fullmoon', 0, 3, 'confirmed'],
    ['2026-04-17', 'fullmoon', 3, 2, 'confirmed'],

    // MAY 2026 — 54 bookings
    // Angthong (18 bookings)
    ['2026-05-02', 'angthong', 2, 3, 'confirmed'],
    ['2026-05-04', 'angthong', 0, 5, 'confirmed'],
    ['2026-05-06', 'angthong', 4, 2, 'confirmed'],
    ['2026-05-08', 'angthong', 1, 4, 'confirmed'],
    ['2026-05-10', 'angthong', 3, 2, 'confirmed'],
    ['2026-05-13', 'angthong', 0, 6, 'confirmed'],
    ['2026-05-15', 'angthong', 2, 3, 'confirmed'],
    ['2026-05-17', 'angthong', 4, 1, 'confirmed'],
    ['2026-05-19', 'angthong', 2, 4, 'confirmed'],
    ['2026-05-21', 'angthong', 0, 5, 'confirmed'],
    ['2026-05-22', 'angthong', 3, 2, 'confirmed'],
    ['2026-05-23', 'angthong', 1, 4, 'confirmed'],
    ['2026-05-24', 'angthong', 0, 6, 'confirmed'],
    ['2026-05-25', 'angthong', 4, 2, 'confirmed'],
    ['2026-05-26', 'angthong', 2, 3, 'confirmed'],
    ['2026-05-27', 'angthong', 3, 1, 'confirmed'],
    ['2026-05-29', 'angthong', 0, 5, 'confirmed'],
    ['2026-05-31', 'angthong', 2, 3, 'confirmed'],
    // Koh Tao (18 bookings)
    ['2026-05-01', 'kohtao', 0, 4, 'confirmed'],
    ['2026-05-03', 'kohtao', 3, 2, 'confirmed'],
    ['2026-05-05', 'kohtao', 0, 5, 'confirmed'],
    ['2026-05-07', 'kohtao', 2, 3, 'confirmed'],
    ['2026-05-09', 'kohtao', 4, 1, 'confirmed'],
    ['2026-05-11', 'kohtao', 0, 6, 'confirmed'],
    ['2026-05-14', 'kohtao', 2, 3, 'confirmed'],
    ['2026-05-16', 'kohtao', 3, 2, 'confirmed'],
    ['2026-05-18', 'kohtao', 0, 5, 'confirmed'],
    ['2026-05-20', 'kohtao', 4, 2, 'confirmed'],
    ['2026-05-21', 'kohtao', 1, 4, 'confirmed'],
    ['2026-05-22', 'kohtao', 3, 2, 'confirmed'],
    ['2026-05-23', 'kohtao', 0, 5, 'confirmed'],
    ['2026-05-25', 'kohtao', 2, 3, 'confirmed'],
    ['2026-05-27', 'kohtao', 4, 1, 'confirmed'],
    ['2026-05-28', 'kohtao', 0, 4, 'confirmed'],
    ['2026-05-30', 'kohtao', 3, 2, 'confirmed'],
    ['2026-05-31', 'kohtao', 1, 3, 'draft'],
    // Fullmoon (18 bookings — May 12 is Fullmoon)
    ['2026-05-10', 'fullmoon', 2, 4, 'confirmed'],
    ['2026-05-11', 'fullmoon', 0, 5, 'confirmed'],
    ['2026-05-11', 'fullmoon', 3, 3, 'confirmed'],
    ['2026-05-12', 'fullmoon', 1, 6, 'confirmed'],
    ['2026-05-12', 'fullmoon', 0, 7, 'confirmed'],
    ['2026-05-12', 'fullmoon', 4, 3, 'confirmed'],
    ['2026-05-12', 'fullmoon', 2, 5, 'confirmed'],
    ['2026-05-12', 'fullmoon', 0, 6, 'confirmed'],
    ['2026-05-12', 'fullmoon', 3, 2, 'confirmed'],
    ['2026-05-13', 'fullmoon', 2, 4, 'completed'],
    ['2026-05-13', 'fullmoon', 0, 5, 'completed'],
    ['2026-05-13', 'fullmoon', 4, 2, 'completed'],
    ['2026-05-14', 'fullmoon', 1, 5, 'completed'],
    ['2026-05-14', 'fullmoon', 0, 4, 'completed'],
    ['2026-05-15', 'fullmoon', 3, 3, 'completed'],
    ['2026-05-15', 'fullmoon', 0, 5, 'completed'],
    ['2026-05-16', 'fullmoon', 2, 3, 'confirmed'],
    ['2026-05-16', 'fullmoon', 1, 4, 'confirmed'],
];

// ─── Clean Up ────────────────────────────────────────────────────────────────

echo "=== CLEANUP: Removing existing bookings for company " . COM_ID . " ===\n";
$comId = COM_ID;

// Get all booking IDs for this company
$result = mysqli_query($conn, "SELECT id FROM tour_bookings WHERE company_id=$comId AND deleted_at IS NULL AND travel_date BETWEEN '2026-03-01' AND '2026-05-31'");
$existingIds = [];
while ($row = mysqli_fetch_assoc($result)) $existingIds[] = intval($row['id']);

if (!empty($existingIds)) {
    $idList = implode(',', $existingIds);
    mysqli_query($conn, "DELETE FROM tour_booking_payments WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_booking_items WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_booking_contacts WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_booking_pax WHERE booking_id IN ($idList)");
    mysqli_query($conn, "DELETE FROM tour_bookings WHERE id IN ($idList)");
    echo "  Removed " . count($existingIds) . " bookings + related records.\n";
} else {
    echo "  No existing Mar-May bookings found.\n";
}

// ─── Counters per day (for booking number uniqueness) ────────────────────────
$dayCounters = [];

function nextBookingNumber($conn, $date) {
    global $dayCounters;
    $ymd   = date('ymd', strtotime($date)); // e.g. 260301
    if (!isset($dayCounters[$date])) {
        // Find existing max for that date
        $r = mysqli_query($conn, "SELECT COUNT(*) c FROM tour_bookings WHERE booking_number LIKE 'BK-$ymd-%'");
        $row = mysqli_fetch_assoc($r);
        $dayCounters[$date] = intval($row['c']);
    }
    $dayCounters[$date]++;
    return sprintf('BK-%s-%03d', $ymd, $dayCounters[$date]);
}

// ─── Seed ────────────────────────────────────────────────────────────────────

echo "\n=== SEEDING " . count($schedule) . " bookings ===\n";

$inserted = 0;
$nameIdx  = 0;
$thaiNameIdx = 0;
$custIdx  = 0;

foreach ($schedule as $row) {
    [$travelDate, $tourKey, $qtyThai, $qtyForeign, $status] = $row;
    $tour = $tours[$tourKey];

    // Pick customer (cycle through list)
    $customerId = $customers[$custIdx % count($customers)];
    $custIdx++;

    // Pick agent from tour's preferred agents
    $agentId = $tour['agents'][array_rand($tour['agents'])];

    // Booking date = 1-14 days before travel
    $bookingDate = date('Y-m-d', strtotime($travelDate . ' -' . rand(1, 14) . ' days'));

    // Booking number
    $bookingNumber = nextBookingNumber($conn, $travelDate);

    // Pickup
    $hotel       = rand_el($hotels);
    $pickupTime  = rand_el($pickupTimes);
    $pickupRoom  = rand(100, 999);
    $voucherNum  = 'V-' . strtoupper(substr($tourKey, 0, 2)) . '-' . rand(1000, 9999);
    $bookingBy   = rand_el($staffNames);

    // ── Calculate amounts ────────────────────────────────────────
    $priceThai    = $tour['price_thai'];
    $priceForeign = $tour['price_foreign'];
    $entThai      = $tour['entrance_thai'];
    $entForeign   = $tour['entrance_foreign'];

    $mainSubtotal = ($qtyThai * $priceThai) + ($qtyForeign * $priceForeign);
    $entSubtotal  = ($qtyThai * $entThai) + ($qtyForeign * $entForeign);
    $discount     = 0;
    $vat          = 0;
    $entranceFee  = 0;
    $subtotal     = $mainSubtotal;
    $totalAmount  = $mainSubtotal + $entSubtotal;

    $paxAdult  = $qtyThai + $qtyForeign;
    $paxChild  = 0;
    $paxInfant = 0;

    $currency = 'THB';

    // ── Contact (lead passenger) ─────────────────────────────────
    // Mix Thai (~25%) and Foreign (~75%)
    $useThaiContact = ($qtyThai > 0 && ($qtyForeign === 0 || rand(1,4) === 1));
    if ($useThaiContact) {
        $nameData    = $thaiNames[$thaiNameIdx % count($thaiNames)];
        $thaiNameIdx++;
    } else {
        $nameData = $foreignNames[$nameIdx % count($foreignNames)];
        $nameIdx++;
    }
    [$contactName, $contactGender, $contactNat] = $nameData;
    $contactMobile = rand_phone();

    // ── Insert tour_bookings ─────────────────────────────────────
    $sql = "INSERT INTO tour_bookings
        (company_id, booking_number, booking_date, customer_id, agent_id,
         booking_by, travel_date, pax_adult, pax_child, pax_infant,
         pickup_hotel, pickup_room, pickup_time, voucher_number,
         entrance_fee, subtotal, discount, vat, total_amount,
         amount_paid, amount_due, currency, status, payment_status,
         created_by, created_at)
        VALUES
        ($comId,
         '" . esc($conn, $bookingNumber) . "',
         '" . esc($conn, $bookingDate) . "',
         $customerId, $agentId,
         '" . esc($conn, $bookingBy) . "',
         '" . esc($conn, $travelDate) . "',
         $paxAdult, $paxChild, $paxInfant,
         '" . esc($conn, $hotel) . "',
         '" . esc($conn, $pickupRoom) . "',
         '" . esc($conn, $pickupTime) . "',
         '" . esc($conn, $voucherNum) . "',
         $entranceFee,
         " . floatval($totalAmount) . ",
         $discount, $vat,
         " . floatval($totalAmount) . ",
         0, " . floatval($totalAmount) . ",
         '$currency',
         '" . esc($conn, $status) . "',
         'unpaid',
         " . CREATED_BY . ",
         NOW())";

    if (!mysqli_query($conn, $sql)) {
        echo "  ERROR inserting booking $bookingNumber: " . mysqli_error($conn) . "\n";
        continue;
    }
    $bookingId = mysqli_insert_id($conn);

    // ── Insert tour_booking_items (main tour) ────────────────────
    $paxLinesJson = json_encode([
        ['type' => 'adult', 'nat' => 'thai',      'qty' => $qtyThai,    'price' => $priceThai],
        ['type' => 'adult', 'nat' => 'foreigner', 'qty' => $qtyForeign, 'price' => $priceForeign],
    ]);

    $sql = "INSERT INTO tour_booking_items
        (booking_id, item_type, description, product_type_id, model_id,
         quantity, unit_price, price_thai, price_foreigner, qty_thai, qty_foreigner,
         amount, pax_lines_json, created_at)
        VALUES
        ($bookingId,
         '" . esc($conn, $tour['item_type']) . "',
         '" . esc($conn, $tour['description']) . "',
         " . intval($tour['product_type_id']) . ",
         " . intval($tour['model_id']) . ",
         $paxAdult,
         " . floatval(($qtyThai + $qtyForeign > 0) ? $mainSubtotal / ($qtyThai + $qtyForeign) : 0) . ",
         $priceThai, $priceForeign,
         $qtyThai, $qtyForeign,
         " . floatval($mainSubtotal) . ",
         '" . esc($conn, $paxLinesJson) . "',
         NOW())";
    mysqli_query($conn, $sql);

    // ── Insert entrance fee item (if applicable) ─────────────────
    if ($entSubtotal > 0) {
        $entDesc     = 'Entrance Fee - ' . $tour['description'];
        $entPaxLines = json_encode([
            ['type' => 'adult', 'nat' => 'thai',      'qty' => $qtyThai,    'price' => $entThai],
            ['type' => 'adult', 'nat' => 'foreigner', 'qty' => $qtyForeign, 'price' => $entForeign],
        ]);
        $sql = "INSERT INTO tour_booking_items
            (booking_id, item_type, description, product_type_id, model_id,
             quantity, unit_price, price_thai, price_foreigner, qty_thai, qty_foreigner,
             amount, pax_lines_json, created_at)
            VALUES
            ($bookingId, 'entrance',
             '" . esc($conn, $entDesc) . "',
             " . intval($tour['product_type_id']) . ", 0,
             $paxAdult,
             " . floatval(($qtyThai + $qtyForeign > 0) ? $entSubtotal / ($qtyThai + $qtyForeign) : 0) . ",
             $entThai, $entForeign,
             $qtyThai, $qtyForeign,
             " . floatval($entSubtotal) . ",
             '" . esc($conn, $entPaxLines) . "',
             NOW())";
        mysqli_query($conn, $sql);
    }

    // ── Insert tour_booking_contacts ─────────────────────────────
    $sql = "INSERT INTO tour_booking_contacts
        (booking_id, contact_name, mobile, gender, nationality, created_at)
        VALUES
        ($bookingId,
         '" . esc($conn, $contactName) . "',
         '" . esc($conn, $contactMobile) . "',
         '" . esc($conn, $contactGender) . "',
         '" . esc($conn, $contactNat) . "',
         NOW())";
    mysqli_query($conn, $sql);

    $inserted++;
    if ($inserted % 20 === 0) echo "  ... $inserted bookings inserted\n";
}

// ─── Summary ─────────────────────────────────────────────────────────────────

echo "\n=== DONE: $inserted bookings inserted ===\n\n";

// Count per tour / month
$r = mysqli_query($conn, "
    SELECT
        MONTH(b.travel_date) as mo,
        i.description,
        COUNT(DISTINCT b.id) as bookings,
        SUM(b.total_amount) as revenue
    FROM tour_bookings b
    JOIN tour_booking_items i ON i.booking_id=b.id
    WHERE b.company_id=$comId
      AND b.travel_date BETWEEN '2026-03-01' AND '2026-05-31'
      AND i.description NOT LIKE 'Entrance Fee%'
    GROUP BY mo, i.description
    ORDER BY mo, i.description
");

$months = [3 => 'March', 4 => 'April', 5 => 'May'];
echo str_pad('Month', 8) . str_pad('Tour', 38) . str_pad('Bookings', 10) . "Revenue (THB)\n";
echo str_repeat('-', 70) . "\n";
while ($row = mysqli_fetch_assoc($r)) {
    $mo  = $months[intval($row['mo'])] ?? $row['mo'];
    echo str_pad($mo, 8)
        . str_pad($row['description'], 38)
        . str_pad($row['bookings'], 10)
        . number_format($row['revenue']) . "\n";
}

// Monthly totals
$r = mysqli_query($conn, "
    SELECT MONTH(travel_date) as mo, COUNT(*) as total, SUM(total_amount) as revenue
    FROM tour_bookings
    WHERE company_id=$comId AND travel_date BETWEEN '2026-03-01' AND '2026-05-31'
    GROUP BY mo ORDER BY mo
");
echo str_repeat('-', 70) . "\n";
while ($row = mysqli_fetch_assoc($r)) {
    $mo = $months[intval($row['mo'])] ?? $row['mo'];
    echo str_pad($mo . ' TOTAL', 46) . str_pad($row['total'], 10) . number_format($row['revenue']) . "\n";
}
echo "\n";
