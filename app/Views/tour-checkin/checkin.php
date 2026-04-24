<?php
/**
 * Customer Self-Check-In — Landing Page
 * Public, no auth. Mobile-first, 320px min-width.
 * Variables: $booking (array), $token (string)
 */
$isThai = (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 'th') !== false);
$b = $booking;
$companyName = $isThai
    ? ($b['company_name_th'] ?: $b['company_name_en'])
    : ($b['company_name_en'] ?: $b['company_name_th']);
$contactName = $b['contact_name'] ?? '—';
$travelDate  = $b['travel_date']  ?? '';
$displayDate = $travelDate ? date($isThai ? 'd/m/Y' : 'd M Y', strtotime($travelDate)) : '—';
?>
<!DOCTYPE html>
<html lang="<?= $isThai ? 'th' : 'en' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= $isThai ? 'เช็คอิน' : 'Check In' ?> — <?= htmlspecialchars($companyName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Sarabun', 'Inter', -apple-system, sans-serif;
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 16px 40px;
}
.card {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 420px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.card-header {
    background: linear-gradient(135deg, #0d9488, #0f766e);
    padding: 28px 24px 20px;
    text-align: center;
    color: white;
}
.company-logo {
    width: 64px; height: 64px; border-radius: 12px;
    background: rgba(255,255,255,0.2); margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 700; color: white;
    overflow: hidden;
}
.company-logo img { width: 100%; height: 100%; object-fit: cover; }
.card-header h1 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
.card-header p  { font-size: 14px; opacity: 0.85; }
.card-body { padding: 24px; }
.booking-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.booking-row:last-of-type { border-bottom: none; }
.booking-row .label { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.booking-row .value { font-size: 15px; color: #1e293b; font-weight: 600; text-align: right; max-width: 60%; }
.pax-badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.pax-badge {
    background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1;
    border-radius: 20px; padding: 3px 10px; font-size: 12px; font-weight: 600;
}
.checkin-btn {
    display: block; width: 100%; margin-top: 24px;
    background: #0d9488; color: white; border: none; border-radius: 14px;
    padding: 18px; font-size: 18px; font-weight: 700; cursor: pointer;
    font-family: inherit; transition: background 0.15s;
    min-height: 60px;
}
.checkin-btn:active { background: #0f766e; transform: scale(0.98); }
.checkin-btn .icon { font-size: 22px; margin-right: 8px; }
.disclaimer {
    text-align: center; font-size: 11px; color: #94a3b8;
    margin-top: 14px; line-height: 1.5;
}
.footer {
    margin-top: 24px; text-align: center;
    font-size: 12px; color: rgba(255,255,255,0.6);
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="company-logo">
            <?php if (!empty($b['company_logo'])): ?>
                <img src="<?= htmlspecialchars($b['company_logo']) ?>" alt="">
            <?php else: ?>
                <?= mb_substr($companyName, 0, 1) ?>
            <?php endif; ?>
        </div>
        <h1><?= $isThai ? 'เช็คอินทัวร์' : 'Tour Check-In' ?></h1>
        <p><?= htmlspecialchars($companyName) ?></p>
    </div>

    <div class="card-body">
        <div class="booking-row">
            <span class="label"><?= $isThai ? 'ลูกค้า' : 'Customer' ?></span>
            <span class="value"><?= htmlspecialchars($contactName) ?></span>
        </div>
        <div class="booking-row">
            <span class="label"><?= $isThai ? 'เลขที่ใบจอง' : 'Booking Ref' ?></span>
            <span class="value"><?= htmlspecialchars($b['booking_number']) ?></span>
        </div>
        <div class="booking-row">
            <span class="label"><?= $isThai ? 'วันที่ทัวร์' : 'Tour Date' ?></span>
            <span class="value"><?= htmlspecialchars($displayDate) ?></span>
        </div>
        <div class="booking-row">
            <span class="label"><?= $isThai ? 'จำนวนผู้โดยสาร' : 'Passengers' ?></span>
            <span class="value">
                <span class="pax-badges">
                    <?php if ($b['pax_adult'] > 0): ?>
                        <span class="pax-badge"><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?> ×<?= intval($b['pax_adult']) ?></span>
                    <?php endif; ?>
                    <?php if ($b['pax_child'] > 0): ?>
                        <span class="pax-badge"><?= $isThai ? 'เด็ก' : 'Child' ?> ×<?= intval($b['pax_child']) ?></span>
                    <?php endif; ?>
                    <?php if ($b['pax_infant'] > 0): ?>
                        <span class="pax-badge"><?= $isThai ? 'ทารก' : 'Infant' ?> ×<?= intval($b['pax_infant']) ?></span>
                    <?php endif; ?>
                </span>
            </span>
        </div>

        <form method="POST" action="index.php?page=tour_checkin_submit">
            <input type="hidden" name="id"    value="<?= intval($b['id']) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <button type="submit" class="checkin-btn">
                <span class="icon">✓</span>
                <?= $isThai ? 'เช็คอินเลย!' : 'Check In Now!' ?>
            </button>
        </form>

        <p class="disclaimer">
            <?= $isThai
                ? 'กดปุ่มเพื่อยืนยันการเช็คอินทัวร์ของคุณ<br>ข้อมูลจะถูกบันทึกและแจ้งเจ้าหน้าที่โดยอัตโนมัติ'
                : 'Tap the button to confirm your tour check-in.<br>Our team will be notified automatically.' ?>
        </p>
    </div>
</div>

<p class="footer">Powered by iACC Tour Management</p>

</body>
</html>
