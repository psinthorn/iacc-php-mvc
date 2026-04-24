<?php
/**
 * Customer Self-Check-In — Success Screen
 * Variables: $booking (array)
 */
$isThai = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 'th') !== false);
$b = $booking;
$companyName = $isThai
    ? ($b['company_name_th'] ?: $b['company_name_en'])
    : ($b['company_name_en'] ?: $b['company_name_th']);
$checkinTime = !empty($b['checkin_at'])
    ? date('d M Y H:i', strtotime($b['checkin_at']))
    : date('d M Y H:i');
$displayDate = !empty($b['travel_date'])
    ? date($isThai ? 'd/m/Y' : 'd M Y', strtotime($b['travel_date']))
    : '—';
?>
<!DOCTYPE html>
<html lang="<?= $isThai ? 'th' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= $isThai ? 'เช็คอินสำเร็จ!' : 'Checked In!' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Sarabun', 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    min-height: 100vh;
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 24px 16px;
}
.card {
    background: white; border-radius: 20px; width: 100%; max-width: 420px;
    padding: 40px 24px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.tick-circle {
    width: 88px; height: 88px; border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    animation: pop 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes pop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.tick-circle svg { width: 44px; height: 44px; }
.card h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
.card .subtitle { font-size: 14px; color: #64748b; margin-bottom: 28px; }
.info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid #f1f5f9; text-align: left;
}
.info-row:last-of-type { border-bottom: none; }
.info-row .lbl { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.info-row .val { font-size: 14px; color: #1e293b; font-weight: 600; }
.have-great {
    margin-top: 24px; padding: 16px; background: #f0fdf4;
    border-radius: 12px; border: 1px solid #bbf7d0;
    font-size: 15px; color: #166534; font-weight: 600;
}
.footer { margin-top: 20px; font-size: 11px; color: rgba(255,255,255,0.6); }
</style>
</head>
<body>

<div class="card">
    <div class="tick-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>

    <h1><?= $isThai ? 'เช็คอินสำเร็จ!' : 'You\'re Checked In!' ?></h1>
    <p class="subtitle"><?= $isThai ? 'ยืนยันการเข้าร่วมทัวร์เรียบร้อยแล้ว' : 'Your attendance has been confirmed' ?></p>

    <div class="info-row">
        <span class="lbl"><?= $isThai ? 'เลขที่ใบจอง' : 'Booking' ?></span>
        <span class="val"><?= htmlspecialchars($b['booking_number'] ?? '—') ?></span>
    </div>
    <div class="info-row">
        <span class="lbl"><?= $isThai ? 'วันที่ทัวร์' : 'Tour Date' ?></span>
        <span class="val"><?= $displayDate ?></span>
    </div>
    <div class="info-row">
        <span class="lbl"><?= $isThai ? 'เวลาเช็คอิน' : 'Check-in Time' ?></span>
        <span class="val"><?= $checkinTime ?></span>
    </div>
    <div class="info-row">
        <span class="lbl"><?= $isThai ? 'ผู้โดยสาร' : 'Passengers' ?></span>
        <span class="val"><?= intval($b['total_pax'] ?? 0) ?> <?= $isThai ? 'ท่าน' : 'pax' ?></span>
    </div>

    <div class="have-great">
        <?= $isThai ? '🌟 ขอให้สนุกกับทริปนี้นะครับ/ค่ะ!' : '🌟 Have a wonderful tour! Enjoy every moment.' ?>
    </div>
</div>

<p class="footer">Powered by iACC Tour Management</p>

</body>
</html>
