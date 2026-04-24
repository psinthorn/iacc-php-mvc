<?php
/**
 * Customer Self-Check-In — Invalid / Error Screen
 * Variables: $reason (string), $booking (array|null)
 */
$isThai  = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 'th') !== false);
$reason  = $reason ?? 'unknown';
$booking = $booking ?? [];

$messages = [
    'expired'      => [
        'en' => ['This check-in link has expired.',      'The check-in window for your tour has closed. Please contact our team for assistance.'],
        'th' => ['ลิงก์เช็คอินนี้หมดอายุแล้ว',            'ช่วงเวลาเช็คอินสำหรับทัวร์นี้ปิดแล้ว กรุณาติดต่อเจ้าหน้าที่'],
    ],
    'not_found'    => [
        'en' => ['Check-in link not found.',             'This link is invalid or has been replaced. Please contact our team.'],
        'th' => ['ไม่พบลิงก์เช็คอินนี้',                  'ลิงก์นี้ไม่ถูกต้องหรือถูกเปลี่ยนแล้ว กรุณาติดต่อเจ้าหน้าที่'],
    ],
    'missing'      => [
        'en' => ['Invalid check-in link.',               'The link you used is incomplete. Please scan the QR code again.'],
        'th' => ['ลิงก์เช็คอินไม่ถูกต้อง',               'ลิงก์ที่ใช้ไม่สมบูรณ์ กรุณาสแกน QR Code ใหม่อีกครั้ง'],
    ],
    'rate_limited' => [
        'en' => ['Too many attempts.',                   'Please wait a moment and try again.'],
        'th' => ['มีการพยายามมากเกินไป',                 'กรุณารอสักครู่แล้วลองใหม่'],
    ],
    'status:draft'     => [
        'en' => ['Booking not yet confirmed.',           'Self check-in is available for confirmed bookings only.'],
        'th' => ['ยังไม่ได้ยืนยันการจอง',                'การเช็คอินด้วยตนเองใช้ได้เฉพาะการจองที่ยืนยันแล้วเท่านั้น'],
    ],
    'status:pending'   => [
        'en' => ['Booking not yet confirmed.',           'Self check-in is available for confirmed bookings only.'],
        'th' => ['การจองรออนุมัติ',                      'การเช็คอินด้วยตนเองใช้ได้เฉพาะการจองที่ยืนยันแล้วเท่านั้น'],
    ],
    'status:cancelled' => [
        'en' => ['This booking has been cancelled.',     'Please contact our team if you believe this is an error.'],
        'th' => ['การจองนี้ถูกยกเลิกแล้ว',               'กรุณาติดต่อเจ้าหน้าที่หากคิดว่าเกิดข้อผิดพลาด'],
    ],
];

$lang = $isThai ? 'th' : 'en';
$msg  = $messages[$reason][$lang] ?? $messages['not_found'][$lang];
?>
<!DOCTYPE html>
<html lang="<?= $isThai ? 'th' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= $isThai ? 'ไม่สามารถเช็คอินได้' : 'Cannot Check In' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Sarabun', 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
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
    background: #fff1f2; border: 3px solid #fecdd3;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px; font-size: 36px;
}
.card h1 { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
.card p  { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
.contact-box {
    background: #f8fafc; border-radius: 12px; padding: 16px;
    font-size: 13px; color: #475569; border: 1px solid #e2e8f0;
}
.contact-box strong { display: block; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
</style>
</head>
<body>

<div class="card">
    <div class="icon-circle">⚠️</div>
    <h1><?= htmlspecialchars($msg[0]) ?></h1>
    <p><?= htmlspecialchars($msg[1]) ?></p>

    <?php if (!empty($booking['company_name_en']) || !empty($booking['company_name_th'])): ?>
    <div class="contact-box">
        <strong><?= $isThai ? 'บริษัท / ติดต่อ' : 'Contact' ?></strong>
        <?= htmlspecialchars($isThai ? ($booking['company_name_th'] ?: $booking['company_name_en']) : ($booking['company_name_en'] ?: $booking['company_name_th'])) ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
