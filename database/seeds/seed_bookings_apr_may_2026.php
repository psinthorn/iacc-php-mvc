<?php
/**
 * Seed: Tour Bookings for April-May 2026
 * 
 * - 20-38 bookings per day
 * - Two tours: Angthong National Marine Park, Koh Tao Koh Nang Yuan
 * - Three booking types: Thai only, Foreigner only, Mixed
 * - 2-8 pax per booking (random adult/child)
 * 
 * Usage: 
 *   docker exec -it iacc_php php database/seeds/seed_bookings_apr_may_2026.php
 */

// --- DB Connection ---
$host = 'mysql';
$db   = 'iacc';
$user = 'root';
$pass = 'root';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB Error: " . $conn->connect_error . "\n");
$conn->set_charset('utf8mb4');

$companyId = 165;

// --- Setup: Create agent vendors if none exist ---
$agentVendors = [];
$vendorNames = [
    ['name_en' => 'Samui Island Tours Co.,Ltd.',   'name_th' => 'สมุยไอแลนด์ทัวร์ จำกัด',     'contact' => 'Somchai K.',   'phone' => '077-412-001', 'email' => 'info@samuiislandtours.com'],
    ['name_en' => 'Koh Tao Dive & Tour Co.,Ltd.',  'name_th' => 'เกาะเต่าไดฟ์แอนด์ทัวร์ จำกัด', 'contact' => 'David L.',   'phone' => '077-412-002', 'email' => 'booking@kohtaodive.com'],
    ['name_en' => 'Angthong Marine Trips Co.,Ltd.','name_th' => 'อ่างทองมารีนทริป จำกัด',       'contact' => 'Napat S.',    'phone' => '077-412-003', 'email' => 'ops@angthongtrips.com'],
    ['name_en' => 'Gulf Explorer Travel Co.,Ltd.', 'name_th' => 'กัลฟ์เอ็กซ์พลอเรอร์ จำกัด',    'contact' => 'Anna W.',    'phone' => '077-412-004', 'email' => 'tour@gulfexplorer.com'],
    ['name_en' => 'Phangan Paradise Tours Co.,Ltd.','name_th' => 'พะงันพาราไดซ์ทัวร์ จำกัด',    'contact' => 'Prasit T.',   'phone' => '077-412-005', 'email' => 'book@phanganparadise.com'],
];

foreach ($vendorNames as $v) {
    // Check if already exists
    $chk = $conn->query("SELECT id FROM company WHERE name_en = '" . $conn->real_escape_string($v['name_en']) . "' AND company_id = $companyId AND deleted_at IS NULL LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $row = $chk->fetch_assoc();
        $agentVendors[] = $row['id'];
    } else {
        $conn->query("INSERT INTO company (name_en, name_th, name_sh, contact, phone, email, fax, tax, customer, vender, logo, term, company_id) 
                       VALUES ('" . $conn->real_escape_string($v['name_en']) . "','" . $conn->real_escape_string($v['name_th']) . "',
                               '" . $conn->real_escape_string(substr($v['name_en'], 0, 20)) . "',
                               '" . $conn->real_escape_string($v['contact']) . "','" . $conn->real_escape_string($v['phone']) . "',
                               '" . $conn->real_escape_string($v['email']) . "', '', '', 0, 1, '', '', $companyId)");
        $agentVendors[] = $conn->insert_id;
    }
}

// Create agent profiles for vendors
foreach ($agentVendors as $vid) {
    $chk = $conn->query("SELECT id FROM tour_agent_profiles WHERE company_ref_id = $vid AND company_id = $companyId AND deleted_at IS NULL LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        $conn->query("INSERT INTO tour_agent_profiles (company_ref_id, company_id, commission_type, commission_adult, commission_child, contract_start, contract_end)
                       VALUES ($vid, $companyId, 'percentage', 10.00, 5.00, '2026-01-01', '2026-12-31')");
    }
}

// Get customer IDs
$customerIds = [];
$res = $conn->query("SELECT id FROM company WHERE company_id = $companyId AND customer = 1 AND deleted_at IS NULL");
while ($row = $res->fetch_assoc()) $customerIds[] = (int)$row['id'];

echo "Agents: " . count($agentVendors) . ", Customers: " . count($customerIds) . "\n";

// --- Name pools ---
$thaiFirstNames = ['สมชาย','สมหญิง','วิชัย','อรุณ','นภา','ประสิทธิ์','สุดา','มานะ','พิมพ์','ธนา','ศิริ','กมล','วรรณ','จันทร์','สุรีย์','อนุชา','พรทิพย์','ณัฐ','ปิยะ','รัตนา'];
$thaiLastNames  = ['ใจดี','รักสวย','สุขสันต์','แสงทอง','ดีเลิศ','วงศ์ใหญ่','ทองดี','มั่นคง','รุ่งเรือง','สว่าง','พิทักษ์','เจริญ','ศรีสุข','บุญมี','สมบูรณ์'];

$foreignFirstNames = ['James','Sarah','Michael','Emily','David','Lisa','Robert','Jennifer','William','Amanda','Daniel','Jessica','Thomas','Emma','Christopher','Olivia','Andrew','Sophia','Matthew','Isabella'];
$foreignLastNames  = ['Wilson','Johnson','Brown','Davis','Martinez','Anderson','Taylor','Thomas','Garcia','Lee','Harris','Clark','Lewis','Robinson','Walker','Young','Allen','King','Wright','Scott'];
$foreignNations    = ['British','American','Australian','German','French','Swedish','Dutch','Canadian','Japanese','Korean','Chinese','Russian','Italian','Spanish','Swiss','Norwegian','Danish','Finnish','Austrian','Belgian'];

$tours = [
    ['desc' => 'Angthong National Marine Park',  'type' => 'tour', 'price_thai' => 1500, 'price_foreign' => 2500, 'entrance_thai' => 100, 'entrance_foreign' => 300],
    ['desc' => 'Koh Tao Koh Nang Yuan',          'type' => 'tour', 'price_thai' => 1800, 'price_foreign' => 2800, 'entrance_thai' =>   0, 'entrance_foreign' => 100],
];

$pickupLocations = ['Chaweng Beach','Lamai Beach','Bophut Beach','Maenam Beach','Nathon Pier','Bangrak Pier','Lipa Noi Beach','Taling Ngam Beach'];
$pickupTimes     = ['06:30:00','07:00:00','07:30:00','08:00:00'];

$statuses = ['confirmed','confirmed','confirmed','confirmed','completed','completed','draft'];

// --- Cleanup existing bookings in Apr-May 2026 ---
echo "Cleaning up existing bookings for Apr-May 2026...\n";
$existingIds = [];
$res = $conn->query("SELECT id FROM tour_bookings WHERE company_id = $companyId AND travel_date BETWEEN '2026-04-01' AND '2026-05-31'");
while ($row = $res->fetch_assoc()) $existingIds[] = $row['id'];

if (!empty($existingIds)) {
    $idList = implode(',', $existingIds);
    $conn->query("DELETE FROM tour_booking_pax WHERE booking_id IN ($idList)");
    $conn->query("DELETE FROM tour_booking_items WHERE booking_id IN ($idList)");
    $conn->query("DELETE FROM tour_booking_contacts WHERE booking_id IN ($idList)");
    $conn->query("DELETE FROM tour_bookings WHERE id IN ($idList)");
    echo "  Deleted " . count($existingIds) . " old bookings\n";
}

// --- Generate bookings ---
$totalBookings = 0;
$startDate = new DateTime('2026-04-01');
$endDate   = new DateTime('2026-05-31');

$interval = new DateInterval('P1D');
$period   = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

foreach ($period as $date) {
    $travelDate  = $date->format('Y-m-d');
    $dayNum      = (int)$date->format('j');
    $monthNum    = (int)$date->format('n');
    
    // 20-38 bookings per day
    $dailyCount = rand(20, 38);
    
    for ($b = 0; $b < $dailyCount; $b++) {
        // Pick tour
        $tour = $tours[array_rand($tours)];
        
        // Pick booking type: 0=thai, 1=foreigner, 2=mixed
        $bookingType = rand(0, 2);
        
        // Pax: 2-8 total 
        $totalPax = rand(2, 8);
        
        // Split into adult/child (at least 1 adult)
        $childPax = rand(0, min(3, $totalPax - 1));
        $adultPax = $totalPax - $childPax;
        
        // For mixed bookings, split thai/foreigner
        $thaiAdult = 0; $thaiChild = 0; $foreignAdult = 0; $foreignChild = 0;
        
        if ($bookingType === 0) { // Thai only
            $thaiAdult = $adultPax;
            $thaiChild = $childPax;
        } elseif ($bookingType === 1) { // Foreigner only
            $foreignAdult = $adultPax;
            $foreignChild = $childPax;
        } else { // Mixed
            $thaiAdult    = rand(1, max(1, $adultPax - 1));
            $foreignAdult = $adultPax - $thaiAdult;
            if ($childPax > 0) {
                $thaiChild    = rand(0, $childPax);
                $foreignChild = $childPax - $thaiChild;
            }
        }
        
        $qtyThai     = $thaiAdult + $thaiChild;
        $qtyForeign  = $foreignAdult + $foreignChild;
        
        // Pricing
        $tourAmountThai    = ($thaiAdult * $tour['price_thai']) + ($thaiChild * intval($tour['price_thai'] * 0.7));
        $tourAmountForeign = ($foreignAdult * $tour['price_foreign']) + ($foreignChild * intval($tour['price_foreign'] * 0.7));
        $tourAmount        = $tourAmountThai + $tourAmountForeign;
        
        $entranceThai    = ($thaiAdult + $thaiChild) * $tour['entrance_thai'];
        $entranceForeign = ($foreignAdult + $foreignChild) * $tour['entrance_foreign'];
        $entranceFee     = $entranceThai + $entranceForeign;
        
        $subtotal    = $tourAmount + $entranceFee;
        $totalAmount = $subtotal;
        
        // Agent (70% have agent, 30% walk-in)
        $agentId = (rand(1, 10) <= 7) ? $agentVendors[array_rand($agentVendors)] : 0;
        
        // Customer
        $customerId = !empty($customerIds) ? $customerIds[array_rand($customerIds)] : 0;
        
        // Booking number
        $seq = str_pad($b + 1, 3, '0', STR_PAD_LEFT);
        $bookingNumber = 'BK-' . $date->format('ymd') . '-' . $seq;
        
        // Booking date (1-3 days before travel)
        $bookingDate = (clone $date)->modify('-' . rand(1, 3) . ' days')->format('Y-m-d');
        
        $pickup     = $pickupLocations[array_rand($pickupLocations)];
        $pickupTime = $pickupTimes[array_rand($pickupTimes)];
        $status     = $statuses[array_rand($statuses)];
        $currency   = ($bookingType === 0) ? 'THB' : (rand(0,1) ? 'THB' : 'USD');
        
        // Insert booking
        $conn->query("INSERT INTO tour_bookings 
            (company_id, booking_number, booking_date, customer_id, agent_id, booking_by, travel_date,
             pax_adult, pax_child, pax_infant, pickup_hotel, pickup_time, voucher_number,
             entrance_fee, subtotal, discount, vat, total_amount, currency, status)
            VALUES 
            ($companyId, '$bookingNumber', '$bookingDate', $customerId, $agentId, 'System Seed', '$travelDate',
             $adultPax, $childPax, 0, '" . $conn->real_escape_string($pickup) . "', '$pickupTime', 
             '" . ($agentId > 0 ? 'V-' . $date->format('ymd') . '-' . $seq : '') . "',
             $entranceFee, $subtotal, 0, 0, $totalAmount, '$currency', '$status')");
        
        $bookingId = $conn->insert_id;
        if (!$bookingId) continue;
        
        // Insert tour item
        $conn->query("INSERT INTO tour_booking_items 
            (booking_id, item_type, description, quantity, unit_price, price_thai, price_foreigner,
             qty_thai, qty_foreigner, amount)
            VALUES 
            ($bookingId, 'tour', '" . $conn->real_escape_string($tour['desc']) . "', 
             $totalPax, " . $tour['price_foreign'] . ", " . $tour['price_thai'] . ", " . $tour['price_foreign'] . ",
             $qtyThai, $qtyForeign, $tourAmount)");
        
        // Insert entrance item if applicable
        if ($entranceFee > 0) {
            $conn->query("INSERT INTO tour_booking_items 
                (booking_id, item_type, description, quantity, unit_price, price_thai, price_foreigner,
                 qty_thai, qty_foreigner, amount)
                VALUES 
                ($bookingId, 'entrance', 'Entrance Fee - " . $conn->real_escape_string($tour['desc']) . "', 
                 $totalPax, " . $tour['entrance_foreign'] . ", " . $tour['entrance_thai'] . ", " . $tour['entrance_foreign'] . ",
                 $qtyThai, $qtyForeign, $entranceFee)");
        }
        
        // Insert pax records
        for ($p = 0; $p < $thaiAdult; $p++) {
            $fn = $thaiFirstNames[array_rand($thaiFirstNames)];
            $ln = $thaiLastNames[array_rand($thaiLastNames)];
            $conn->query("INSERT INTO tour_booking_pax (booking_id, pax_type, is_thai, full_name, nationality)
                          VALUES ($bookingId, 'adult', 1, '" . $conn->real_escape_string("$fn $ln") . "', 'Thai')");
        }
        for ($p = 0; $p < $thaiChild; $p++) {
            $fn = $thaiFirstNames[array_rand($thaiFirstNames)];
            $ln = $thaiLastNames[array_rand($thaiLastNames)];
            $conn->query("INSERT INTO tour_booking_pax (booking_id, pax_type, is_thai, full_name, nationality)
                          VALUES ($bookingId, 'child', 1, '" . $conn->real_escape_string("$fn $ln") . "', 'Thai')");
        }
        for ($p = 0; $p < $foreignAdult; $p++) {
            $fn = $foreignFirstNames[array_rand($foreignFirstNames)];
            $ln = $foreignLastNames[array_rand($foreignLastNames)];
            $nat = $foreignNations[array_rand($foreignNations)];
            $conn->query("INSERT INTO tour_booking_pax (booking_id, pax_type, is_thai, full_name, nationality, passport_number)
                          VALUES ($bookingId, 'adult', 0, '$fn $ln', '$nat', '" . strtoupper(substr($nat,0,2)) . rand(1000000,9999999) . "')");
        }
        for ($p = 0; $p < $foreignChild; $p++) {
            $fn = $foreignFirstNames[array_rand($foreignFirstNames)];
            $ln = $foreignLastNames[array_rand($foreignLastNames)];
            $nat = $foreignNations[array_rand($foreignNations)];
            $conn->query("INSERT INTO tour_booking_pax (booking_id, pax_type, is_thai, full_name, nationality)
                          VALUES ($bookingId, 'child', 0, '$fn $ln', '$nat')");
        }
        
        // Insert contact
        if ($bookingType === 0) {
            $cfn = $thaiFirstNames[array_rand($thaiFirstNames)];
            $cln = $thaiLastNames[array_rand($thaiLastNames)];
            $cnat = 'Thai';
        } else {
            $cfn = $foreignFirstNames[array_rand($foreignFirstNames)];
            $cln = $foreignLastNames[array_rand($foreignLastNames)];
            $cnat = $foreignNations[array_rand($foreignNations)];
        }
        $gender = ['male','female'][array_rand(['male','female'])];
        $conn->query("INSERT INTO tour_booking_contacts (booking_id, contact_name, mobile, email, gender, nationality)
                      VALUES ($bookingId, '" . $conn->real_escape_string("$cfn $cln") . "', 
                              '+66" . rand(80,99) . rand(1000000,9999999) . "', 
                              '" . strtolower($cfn) . "." . strtolower($cln) . "@example.com',
                              '$gender', '$cnat')");
        
        $totalBookings++;
    }
    
    echo "  $travelDate: $dailyCount bookings\n";
}

echo "\nDone! Total bookings created: $totalBookings\n";
echo "Agent vendors created: " . count($agentVendors) . "\n";

$conn->close();
