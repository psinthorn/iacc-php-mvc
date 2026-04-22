<?php
/**
 * Tour Booking — PromptPay QR Payment Page
 *
 * Variables: $booking, $amount, $paymentType, $qrData, $promptpayName
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';
?>
<link rel="stylesheet" href="css/master-data.css">

<style>
.pp-container { max-width: 640px; margin: 0 auto; }
.pp-card { background: white; border-radius: 16px; padding: 32px; border: 1px solid #e2e8f0; margin-bottom: 16px; text-align: center; }
.pp-amount { font-size: 42px; font-weight: 800; color: #0891b2; margin: 16px 0 4px; }
.pp-amount-label { font-size: 13px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.pp-qr-wrap { display: inline-block; padding: 16px; background: white; border: 2px solid #e0f2fe; border-radius: 16px; margin: 20px 0; }
.pp-qr-wrap img { display: block; max-width: 220px; }
.pp-name { font-size: 15px; font-weight: 600; color: #1e293b; margin-top: 8px; }
.pp-id   { font-size: 13px; color: #64748b; margin-top: 4px; }
.pp-steps { text-align: left; background: #f0fdf4; border-radius: 12px; padding: 16px 20px; margin-top: 20px; }
.pp-steps h4 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #059669; margin: 0 0 10px; }
.pp-steps ol { margin: 0; padding-left: 18px; }
.pp-steps li { font-size: 13px; color: #374151; padding: 3px 0; line-height: 1.5; }
.pp-form-card { background: white; border-radius: 16px; padding: 28px; border: 1px solid #e2e8f0; }
.pp-form-card h3 { font-size: 15px; font-weight: 700; margin: 0 0 18px; color: #1e293b; }
.pp-field { margin-bottom: 14px; }
.pp-field label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }
.pp-field input { width: 100%; padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box; }
.pp-field input:focus { border-color: #0891b2; outline: none; box-shadow: 0 0 0 3px rgba(8,145,178,0.1); }
.btn-pp-confirm { width: 100%; padding: 13px; background: #0891b2; color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-pp-confirm:hover { background: #0e7490; }
.btn-pp-back { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; margin-top: 14px; }
.btn-pp-back:hover { background: #f8fafc; }
</style>

<div class="master-data-header" data-theme="teal">
    <div class="header-content">
        <h2><i class="fa fa-qrcode"></i> <?= $isThai ? 'ชำระด้วย PromptPay' : 'Pay with PromptPay' ?></h2>
        <div class="header-actions">
            <a href="index.php?page=tour_booking_payments&booking_id=<?= $booking['id'] ?>" class="btn-header btn-header-outline">
                <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
            </a>
        </div>
    </div>
</div>

<div class="pp-container">

    <!-- QR Code Card -->
    <div class="pp-card">
        <div class="pp-amount-label"><?= $isThai ? 'ยอดชำระ' : 'Amount to Pay' ?></div>
        <div class="pp-amount">฿<?= number_format($amount, 2) ?></div>
        <div style="font-size:12px; color:#94a3b8;"><?= $isThai ? 'การจอง' : 'Booking' ?> <?= htmlspecialchars($booking['booking_number']) ?></div>

        <?php if (!empty($qrData['qr_url'])): ?>
        <div class="pp-qr-wrap">
            <img src="<?= htmlspecialchars($qrData['qr_url']) ?>" alt="PromptPay QR">
        </div>
        <?php else: ?>
        <div style="padding:40px 20px; color:#94a3b8; font-size:13px;">
            <i class="fa fa-qrcode" style="font-size:48px; display:block; margin-bottom:12px; opacity:0.3;"></i>
            <?= $isThai ? 'ไม่สามารถสร้าง QR Code ได้ กรุณาติดต่อผู้ดูแล' : 'QR code unavailable. Please contact admin.' ?>
        </div>
        <?php endif; ?>

        <?php if ($promptpayName): ?>
        <div class="pp-name"><i class="fa fa-user-circle-o" style="color:#0891b2;"></i> <?= htmlspecialchars($promptpayName) ?></div>
        <?php endif; ?>
        <?php if (!empty($qrData['target'])): ?>
        <div class="pp-id"><?= htmlspecialchars($qrData['target']) ?></div>
        <?php endif; ?>

        <div class="pp-steps">
            <h4><i class="fa fa-list-ol"></i> <?= $isThai ? 'ขั้นตอน' : 'Steps' ?></h4>
            <ol>
                <?php if ($isThai): ?>
                <li>เปิดแอปธนาคารและเลือก "สแกน QR"</li>
                <li>สแกน QR Code ด้านบน</li>
                <li>ตรวจสอบจำนวนเงิน <strong>฿<?= number_format($amount, 2) ?></strong> และยืนยันการโอน</li>
                <li>บันทึกสลิปการโอน แล้วอัพโหลดด้านล่าง</li>
                <?php else: ?>
                <li>Open your banking app and tap "Scan QR"</li>
                <li>Scan the QR code above</li>
                <li>Confirm the amount <strong>฿<?= number_format($amount, 2) ?></strong> and complete the transfer</li>
                <li>Take a screenshot of the slip and upload it below</li>
                <?php endif; ?>
            </ol>
        </div>
    </div>

    <!-- Slip Upload Form -->
    <div class="pp-form-card">
        <h3><i class="fa fa-upload" style="color:#0891b2;"></i>
            <?= $isThai ? 'อัพโหลดหลักฐานการโอน' : 'Upload Transfer Slip' ?>
        </h3>
        <form method="post" action="index.php?page=tour_booking_payment_promptpay_confirm" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
            <input type="hidden" name="amount" value="<?= $amount ?>">
            <input type="hidden" name="payment_type" value="<?= htmlspecialchars($paymentType) ?>">

            <div class="pp-field">
                <label><?= $isThai ? 'เลขอ้างอิงการโอน (ถ้ามี)' : 'Transaction Reference (optional)' ?></label>
                <input type="text" name="reference_id" placeholder="<?= $isThai ? 'เลขที่อ้างอิง' : 'e.g., TH123456' ?>">
            </div>

            <div class="pp-field">
                <label><?= $isThai ? 'สลิปการโอน' : 'Transfer Slip' ?> *</label>
                <input type="file" name="slip" accept="image/*,.pdf" required
                       style="padding:8px; background:#f8fafc;">
                <div style="font-size:11px; color:#94a3b8; margin-top:4px;">
                    <?= $isThai ? 'รองรับ JPG, PNG, WebP, PDF ขนาดไม่เกิน 5MB' : 'JPG, PNG, WebP, PDF — max 5MB' ?>
                </div>
            </div>

            <button type="submit" class="btn-pp-confirm">
                <i class="fa fa-paper-plane"></i>
                <?= $isThai ? 'ส่งหลักฐานการชำระ' : 'Submit Payment Proof' ?>
            </button>
        </form>

        <a href="index.php?page=tour_booking_payments&booking_id=<?= $booking['id'] ?>" class="btn-pp-back">
            <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back to Payments' ?>
        </a>
    </div>

</div>
