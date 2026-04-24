<?php
/**
 * Customer Self-Check-In — Already Checked In Screen
 * Variables: $booking (array)
 */
$isThai = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 'th') !== false);
$b = $booking;
$checkinTime = !empty($b['checkin_at'])
    ? date('d M Y H:i', strtotime($b['checkin_at']))
    : '—';
?>
<!DOCTYPE html>
<html lang="<?= $isThai ? 'th' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= $isThai ? 'เช็คอินแล้ว' : 'Already Checked In' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Sarabun', 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 24px 16px;
}
.card {
    background: white; border-radius: 20px; width: 100%; max-width: 420px;
    padding: 40px 24px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.icon-circle {
    width: 80px; height: 80px; border-radius: 50%;
    background: #f0fdfa; border: 3px solid #0d9488;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; font-size: 36px;
}
.card h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.card p  { font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.6; }
.checkin-time {
    background: #f0fdfa; border-radius: 12px; padding: 14px;
    font-size: 15px; color: #0d9488; font-weight: 600;
}
.booking-ref {
    margin-top: 12px; font-size: 13px; color: #94a3b8;
}
</style>
</head>
<body>

<div class="card">
    <div class="icon-circle">✓</div>
    <h1><?= $isThai ? 'เช็คอินไว้แล้ว' : 'Already Checked In' ?></h1>
    <p>
        <?= $isThai
            ? 'คุณได้เช็คอินทัวร์นี้ไว้แล้ว ไม่ต้องดำเนินการอีกครั้ง'
            : 'You have already checked in for this tour. No further action is needed.' ?>
    </p>
    <div class="checkin-time">
        <?= $isThai ? '⏱ เวลาเช็คอิน: ' : '⏱ Check-in time: ' ?>
        <?= htmlspecialchars($checkinTime) ?>
    </div>
    <div class="booking-ref">
        <?= htmlspecialchars($b['booking_number'] ?? '') ?>
    </div>
</div>

</body>
</html>
