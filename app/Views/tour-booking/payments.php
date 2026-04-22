<?php
/**
 * Tour Booking — Payments tab / page
 *
 * Variables: $booking, $payments, $summary, $gateways, $flash
 */
use App\Models\TourBookingPayment;

$isThai = ($_SESSION['lang'] ?? '0') === '1';
$methodLabels = TourBookingPayment::getMethodLabels($isThai);
$typeLabels   = TourBookingPayment::getTypeLabels($isThai);
$statusCfg    = TourBookingPayment::getStatusConfig($isThai);

$totalAmount = floatval($booking['total_amount'] ?? 0);
$amountPaid  = floatval($summary['net_paid'] ?? $booking['amount_paid'] ?? 0);
$amountDue   = max(0.0, $totalAmount - $amountPaid);
$payStatus   = $booking['payment_status'] ?? 'unpaid';
$psCfg       = $statusCfg[$payStatus] ?? $statusCfg['pending'] ?? ['label' => ucfirst($payStatus), 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-circle'];

$paymentStatusLabels = [
    'unpaid'  => ['label' => $isThai ? 'ยังไม่ชำระ' : 'Unpaid',   'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
    'deposit' => ['label' => $isThai ? 'ชำระมัดจำ' : 'Deposit',   'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-clock-o'],
    'partial' => ['label' => $isThai ? 'ชำระบางส่วน' : 'Partial', 'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-adjust'],
    'paid'    => ['label' => $isThai ? 'ชำระแล้ว' : 'Paid',       'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-check-circle'],
    'refunded'=> ['label' => $isThai ? 'คืนเงินแล้ว' : 'Refunded','color' => '#6366f1', 'bg' => '#e0e7ff', 'icon' => 'fa-undo'],
];
$psCfg    = $paymentStatusLabels[$payStatus] ?? $paymentStatusLabels['unpaid'];
$gateways = $gateways ?? [];
$flash    = $flash ?? null;

// Generate shareable payment link
use App\Controllers\BookingPayController;
$payLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST']
         . '/index.php?page=booking_pay&id=' . $booking['id']
         . '&token=' . BookingPayController::makeToken(intval($booking['id']), intval($booking['company_id']));

$gwMeta = [
    'stripe'    => ['icon' => 'fa-cc-stripe',  'label' => 'Stripe',    'color' => '#635bff', 'bg' => '#f0efff'],
    'paypal'    => ['icon' => 'fa-paypal',      'label' => 'PayPal',    'color' => '#0070ba', 'bg' => '#e8f4fd'],
    'promptpay' => ['icon' => 'fa-qrcode',      'label' => 'PromptPay', 'color' => '#0891b2', 'bg' => '#ecfeff'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.pay-container { max-width: 960px; margin: 0 auto; }
.pay-card { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
.pay-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.pay-card h3 i { color: #0d9488; margin-right: 6px; }

/* Summary strip */
.pay-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
.pay-summary .pay-stat { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; text-align: center; }
.pay-stat .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; margin-bottom: 6px; }
.pay-stat .stat-value { font-size: 20px; font-weight: 700; }

/* Payment table */
.pay-table { width: 100%; border-collapse: collapse; }
.pay-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; text-align: left; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; }
.pay-table td { padding: 10px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.pay-table .right { text-align: right; }
.pay-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; }

/* Actions */
.pay-actions { display: flex; gap: 6px; }
.pay-btn-sm { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 4px; }
.pay-btn-approve { background: #d1fae5; color: #059669; }
.pay-btn-reject { background: #fee2e2; color: #dc2626; }
.pay-btn-delete { background: #f1f5f9; color: #94a3b8; }
.pay-btn-approve:hover { background: #a7f3d0; }
.pay-btn-reject:hover { background: #fecaca; }
.pay-btn-delete:hover { background: #e2e8f0; }

/* Record payment button */
.btn-record { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-record:hover { background: #0f766e; }

/* Modal overlay */
.pay-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9000; justify-content: center; align-items: center; }
.pay-modal-overlay.active { display: flex; }
.pay-modal { background: white; border-radius: 16px; width: 520px; max-width: 95vw; max-height: 90vh; overflow-y: auto; padding: 28px; }
.pay-modal h3 { font-size: 16px; font-weight: 700; margin: 0 0 20px; color: #1e293b; }
.pay-modal .form-row { margin-bottom: 14px; }
.pay-modal label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
.pay-modal input, .pay-modal select, .pay-modal textarea { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; box-sizing: border-box; }
.pay-modal textarea { resize: vertical; min-height: 60px; }
.pay-modal .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.pay-modal .btn-cancel { padding: 8px 20px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.pay-modal .btn-save { padding: 8px 20px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.pay-modal .btn-save:hover { background: #0f766e; }

.slip-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #e2e8f0; }

/* Gateway buttons */
.gw-section { margin-bottom: 16px; }
.gw-section h4 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; margin: 0 0 10px; }
.gw-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.gw-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; border: 1.5px solid; cursor: pointer; transition: all 0.15s; background: white; }
.gw-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }

/* Flash */
.pay-flash { display: flex; align-items: center; gap: 10px; padding: 13px 18px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
.pay-flash.success { background: #f0fdf4; border-left: 4px solid #10b981; color: #065f46; }
.pay-flash.error   { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
.pay-flash.warning { background: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; }

.action-bar { display: flex; gap: 10px; margin-top: 20px; }
.action-bar a { padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-back { background: white; color: #64748b; border: 1px solid #e2e8f0; }
.btn-back:hover { background: #f8fafc; }

@media (max-width: 768px) {
    .pay-summary { grid-template-columns: 1fr 1fr; }
    .pay-table { font-size: 12px; }
    .pay-modal { width: 100%; margin: 10px; }
}
</style>

<div class="master-data-header" data-theme="teal">
    <div class="header-content">
        <h2><i class="fa fa-money"></i> <?= $isThai ? 'การชำระเงิน' : 'Payments' ?> — <?= htmlspecialchars($booking['booking_number'] ?? '') ?></h2>
        <div class="header-actions">
            <?php if (($booking['status'] ?? 'draft') !== 'draft'): ?>
            <button class="btn-header-outline" onclick="copyPayLink()" id="copyLinkBtn" title="<?= $amountDue > 0 ? 'Copy payment link for customer' : 'Booking is fully paid' ?>">
                <i class="fa fa-link"></i> <?= $isThai ? 'ลิงก์ชำระ' : 'Payment Link' ?>
            </button>
            <?php endif; ?>
            <button class="btn-header-primary" onclick="openPaymentModal('payment')"><i class="fa fa-plus"></i> <?= $isThai ? 'บันทึกการชำระ' : 'Record Payment' ?></button>
            <button class="btn-header-outline" onclick="openPaymentModal('refund')"><i class="fa fa-undo"></i> <?= $isThai ? 'คืนเงิน' : 'Refund' ?></button>
        </div>
    </div>
</div>

<div class="pay-container">

    <!-- Draft notice -->
    <?php if (($booking['status'] ?? 'draft') === 'draft'): ?>
    <div style="display:flex; align-items:flex-start; gap:12px; background:#fef9c3; border:1.5px solid #fde047; border-radius:12px; padding:14px 18px; margin-bottom:20px;">
        <i class="fa fa-lock" style="font-size:20px; color:#ca8a04; flex-shrink:0; margin-top:1px;"></i>
        <div>
            <div style="font-size:14px; font-weight:700; color:#854d0e; margin-bottom:4px;">
                <?= $isThai ? 'บุ๊กกิ้งอยู่ในสถานะ ฉบับร่าง' : 'Booking is in Draft status' ?>
            </div>
            <div style="font-size:13px; color:#92400e; line-height:1.6;">
                <?= $isThai
                    ? 'ลิงก์ชำระเงินสำหรับลูกค้าจะพร้อมใช้งานเมื่อสถานะเป็น <strong>ยืนยัน</strong> — คุณยังสามารถบันทึกการชำระเงินด้วยตนเองได้'
                    : 'The customer payment link becomes available once the booking is <strong>Confirmed</strong>. You can still record payments manually.' ?>
                &nbsp;<a href="index.php?page=tour_booking_make&id=<?= $booking['id'] ?>" style="color:#ca8a04; font-weight:700; text-decoration:underline;">
                    <?= $isThai ? 'อัปเดตสถานะ →' : 'Update status →' ?>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="pay-flash <?= htmlspecialchars($flash['type']) ?>">
        <i class="fa fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="pay-summary">
        <div class="pay-stat">
            <div class="stat-label"><?= $isThai ? 'ยอดรวม' : 'Total' ?></div>
            <div class="stat-value" style="color:#1e293b;">฿<?= number_format($totalAmount, 2) ?></div>
        </div>
        <div class="pay-stat">
            <div class="stat-label"><?= $isThai ? 'ชำระแล้ว' : 'Paid' ?></div>
            <div class="stat-value" style="color:#059669;">฿<?= number_format($amountPaid, 2) ?></div>
        </div>
        <div class="pay-stat">
            <div class="stat-label"><?= $isThai ? 'คงเหลือ' : 'Balance Due' ?></div>
            <div class="stat-value" style="color:<?= $amountDue > 0 ? '#ef4444' : '#059669' ?>;">฿<?= number_format($amountDue, 2) ?></div>
        </div>
        <div class="pay-stat">
            <div class="stat-label"><?= $isThai ? 'สถานะ' : 'Status' ?></div>
            <div>
                <span class="pay-badge" style="background:<?= $psCfg['bg'] ?>;color:<?= $psCfg['color'] ?>;">
                    <i class="fa <?= $psCfg['icon'] ?>"></i> <?= $psCfg['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Online Payment Gateways -->
    <?php if (!empty($gateways) && $amountDue > 0): ?>
    <div class="pay-card">
        <h3><i class="fa fa-credit-card"></i> <?= $isThai ? 'ชำระออนไลน์' : 'Pay Online' ?></h3>
        <div class="gw-section">
            <div class="gw-section" style="font-size:13px; color:#64748b; margin-bottom:12px;">
                <?= $isThai ? 'ยอดค้างชำระ' : 'Amount due' ?>:
                <strong style="color:#ef4444; font-size:15px;">฿<?= number_format($amountDue, 2) ?></strong>
                &nbsp;—&nbsp;
                <?= $isThai ? 'เลือกช่องทางชำระ:' : 'Select a payment gateway:' ?>
            </div>
            <div class="gw-buttons">
            <?php foreach ($gateways as $gw):
                $code = strtolower($gw['code']);
                $meta = $gwMeta[$code] ?? ['icon' => 'fa-plug', 'label' => $gw['name'], 'color' => '#64748b', 'bg' => '#f1f5f9'];
            ?>
            <form method="post" action="index.php?page=tour_booking_payment_checkout">
                <?= csrf_field() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="gateway" value="<?= $code ?>">
                <input type="hidden" name="amount" value="<?= $amountDue ?>">
                <input type="hidden" name="payment_type" value="full">
                <button type="submit" class="gw-btn"
                        style="color:<?= $meta['color'] ?>; border-color:<?= $meta['color'] ?>; background:<?= $meta['bg'] ?>;">
                    <i class="fa <?= $meta['icon'] ?>" style="font-size:16px;"></i>
                    <?= $isThai ? 'ชำระด้วย ' : 'Pay with ' ?><?= htmlspecialchars($meta['label']) ?>
                </button>
            </form>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Custom amount override -->
        <details style="margin-top:12px;">
            <summary style="font-size:12px; color:#94a3b8; cursor:pointer;"><?= $isThai ? 'ชำระบางส่วน / มัดจำ' : 'Pay partial / deposit' ?></summary>
            <div style="margin-top:12px; display:grid; grid-template-columns:1fr 1fr; gap:12px; max-width:420px;">
                <div>
                    <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'จำนวนเงิน' : 'Amount' ?></label>
                    <input type="number" id="gwCustomAmount" step="0.01" min="1" max="<?= $amountDue ?>" value="<?= $amountDue ?>"
                           style="width:100%; padding:8px 10px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'ประเภท' : 'Type' ?></label>
                    <select id="gwCustomType" style="width:100%; padding:8px 10px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; height:36px; box-sizing:border-box;">
                        <option value="full"><?= $isThai ? 'ชำระเต็มจำนวน' : 'Full Payment' ?></option>
                        <option value="deposit"><?= $isThai ? 'มัดจำ' : 'Deposit' ?></option>
                        <option value="partial"><?= $isThai ? 'บางส่วน' : 'Partial' ?></option>
                    </select>
                </div>
            </div>
            <div class="gw-buttons" style="margin-top:10px;">
            <?php foreach ($gateways as $gw):
                $code = strtolower($gw['code']);
                $meta = $gwMeta[$code] ?? ['icon' => 'fa-plug', 'label' => $gw['name'], 'color' => '#64748b', 'bg' => '#f1f5f9'];
            ?>
            <form method="post" action="index.php?page=tour_booking_payment_checkout" class="gw-partial-form">
                <?= csrf_field() ?>
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="gateway" value="<?= $code ?>">
                <input type="hidden" name="amount" class="gw-amount-field" value="<?= $amountDue ?>">
                <input type="hidden" name="payment_type" class="gw-type-field" value="full">
                <button type="submit" class="gw-btn"
                        style="color:<?= $meta['color'] ?>; border-color:<?= $meta['color'] ?>; background:<?= $meta['bg'] ?>; font-size:12px; padding:8px 14px;">
                    <i class="fa <?= $meta['icon'] ?>"></i> <?= htmlspecialchars($meta['label']) ?>
                </button>
            </form>
            <?php endforeach; ?>
            </div>
        </details>
    </div>
    <?php endif; ?>

    <!-- Payment History -->
    <div class="pay-card">
        <h3><i class="fa fa-history"></i> <?= $isThai ? 'ประวัติการชำระเงิน' : 'Payment History' ?></h3>

        <?php if (empty($payments)): ?>
        <p style="color:#94a3b8; font-size:13px; font-style:italic; text-align:center; padding:20px 0;">
            <i class="fa fa-inbox" style="font-size:24px; display:block; margin-bottom:8px;"></i>
            <?= $isThai ? 'ยังไม่มีรายการชำระเงิน' : 'No payments recorded yet' ?>
        </p>
        <?php else: ?>
        <table class="pay-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $isThai ? 'วันที่' : 'Date' ?></th>
                    <th><?= $isThai ? 'ช่องทาง' : 'Method' ?></th>
                    <th><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                    <th class="right"><?= $isThai ? 'จำนวน' : 'Amount' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th><?= $isThai ? 'หลักฐาน' : 'Slip' ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $i => $p):
                $pSt = $statusCfg[$p['status']] ?? $statusCfg['pending'];
                $isRefund = $p['payment_type'] === 'refund';
            ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= date('d/m/Y', strtotime($p['payment_date'])) ?></td>
                    <td><?= htmlspecialchars($methodLabels[$p['payment_method']] ?? $p['payment_method']) ?></td>
                    <td>
                        <span class="pay-badge" style="background:<?= $isRefund ? '#fee2e2' : '#f0fdf4' ?>; color:<?= $isRefund ? '#dc2626' : '#166534' ?>;">
                            <?= htmlspecialchars($typeLabels[$p['payment_type']] ?? $p['payment_type']) ?>
                        </span>
                    </td>
                    <td class="right" style="font-weight:600; color:<?= $isRefund ? '#dc2626' : '#059669' ?>;">
                        <?= $isRefund ? '-' : '' ?>฿<?= number_format($p['amount'], 2) ?>
                    </td>
                    <td>
                        <span class="pay-badge" style="background:<?= $pSt['bg'] ?>; color:<?= $pSt['color'] ?>;">
                            <i class="fa <?= $pSt['icon'] ?>"></i> <?= $pSt['label'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($p['slip_image'])): ?>
                        <img src="<?= htmlspecialchars($p['slip_image']) ?>" class="slip-thumb" onclick="window.open('<?= htmlspecialchars($p['slip_image']) ?>', '_blank')" alt="slip">
                        <?php else: ?>
                        <span style="color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="pay-actions">
                            <?php if (in_array($p['status'], ['pending', 'pending_review'])): ?>
                            <form method="post" action="index.php?page=tour_booking_payment_approve" style="margin:0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <button type="submit" class="pay-btn-sm pay-btn-approve" title="<?= $isThai ? 'อนุมัติ' : 'Approve' ?>"><i class="fa fa-check"></i></button>
                            </form>
                            <form method="post" action="index.php?page=tour_booking_payment_reject" style="margin:0;" onsubmit="var r=prompt('<?= $isThai ? 'เหตุผลที่ปฏิเสธ' : 'Reject reason' ?>'); if(!r) return false; this.querySelector('[name=reject_reason]').value=r;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <input type="hidden" name="reject_reason" value="">
                                <button type="submit" class="pay-btn-sm pay-btn-reject" title="<?= $isThai ? 'ปฏิเสธ' : 'Reject' ?>"><i class="fa fa-times"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="post" action="index.php?page=tour_booking_payment_delete" style="margin:0;" onsubmit="return confirm('<?= $isThai ? 'ลบรายการนี้?' : 'Delete this payment?' ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                <button type="submit" class="pay-btn-sm pay-btn-delete" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash-o"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php if (!empty($p['notes'])): ?>
                <tr><td colspan="8" style="padding:2px 10px 10px; font-size:12px; color:#64748b;"><i class="fa fa-comment-o"></i> <?= htmlspecialchars($p['notes']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['reject_reason'])): ?>
                <tr><td colspan="8" style="padding:2px 10px 10px; font-size:12px; color:#dc2626;"><i class="fa fa-ban"></i> <?= htmlspecialchars($p['reject_reason']) ?></td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Quick Reference -->
    <?php if (!empty($summary['pending_amount']) && $summary['pending_amount'] > 0): ?>
    <div class="pay-card" style="border-left: 4px solid #f59e0b;">
        <p style="font-size:13px; color:#92400e; margin:0;">
            <i class="fa fa-exclamation-triangle"></i>
            <?= $isThai ? 'มีรายการรอตรวจสอบ' : 'Pending slip reviews' ?>: ฿<?= number_format($summary['pending_amount'], 2) ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <div class="action-bar">
        <a href="index.php?page=tour_booking_view&id=<?= $booking['id'] ?>" class="btn-back"><i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back to Booking' ?></a>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="pay-modal-overlay" id="paymentModal">
    <div class="pay-modal">
        <h3 id="modalTitle"><i class="fa fa-money"></i> <?= $isThai ? 'บันทึกการชำระเงิน' : 'Record Payment' ?></h3>
        <form id="paymentForm" method="post" action="index.php?page=tour_booking_payment_store" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label><?= $isThai ? 'จำนวนเงิน' : 'Amount' ?> *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="<?= $amountDue ?>" value="<?= $amountDue ?>" required>
                </div>
                <div>
                    <label><?= $isThai ? 'วันที่ชำระ' : 'Payment Date' ?></label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label><?= $isThai ? 'ช่องทางชำระ' : 'Payment Method' ?></label>
                    <select name="payment_method">
                        <?php foreach ($methodLabels as $key => $label): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?= $isThai ? 'ประเภท' : 'Payment Type' ?></label>
                    <select name="payment_type" id="paymentType">
                        <option value="full"><?= $typeLabels['full'] ?></option>
                        <option value="deposit"><?= $typeLabels['deposit'] ?></option>
                        <option value="partial"><?= $typeLabels['partial'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <label><?= $isThai ? 'เลขอ้างอิง' : 'Reference / Transaction ID' ?></label>
                <input type="text" name="reference_id" placeholder="<?= $isThai ? 'เลขที่ทำรายการ' : 'e.g., transfer ref, cheque number' ?>">
            </div>

            <div class="form-row">
                <label><?= $isThai ? 'หลักฐานการชำระ (สลิป)' : 'Payment Slip' ?></label>
                <input type="file" name="slip_image" accept="image/*,.pdf">
            </div>

            <div class="form-row">
                <label><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></label>
                <textarea name="notes" rows="2" placeholder="<?= $isThai ? 'บันทึกเพิ่มเติม' : 'Optional notes' ?>"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closePaymentModal()"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
                <button type="submit" class="btn-save" id="modalSubmitBtn"><i class="fa fa-check"></i> <?= $isThai ? 'บันทึก' : 'Save' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Refund Modal -->
<div class="pay-modal-overlay" id="refundModal">
    <div class="pay-modal">
        <h3><i class="fa fa-undo" style="color:#dc2626;"></i> <?= $isThai ? 'บันทึกการคืนเงิน' : 'Record Refund' ?></h3>
        <form method="post" action="index.php?page=tour_booking_payment_refund">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label><?= $isThai ? 'จำนวนเงินคืน' : 'Refund Amount' ?> *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="<?= $amountPaid ?>" required>
                </div>
                <div>
                    <label><?= $isThai ? 'วันที่' : 'Date' ?></label>
                    <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label><?= $isThai ? 'ช่องทาง' : 'Method' ?></label>
                    <select name="payment_method">
                        <?php foreach ($methodLabels as $key => $label): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?= $isThai ? 'เลขอ้างอิง' : 'Reference' ?></label>
                    <input type="text" name="reference_id">
                </div>
            </div>

            <div class="form-row">
                <label><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></label>
                <textarea name="notes" rows="2" placeholder="<?= $isThai ? 'เหตุผลในการคืนเงิน' : 'Reason for refund' ?>"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closePaymentModal()"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
                <button type="submit" class="btn-save" style="background:#dc2626;"><i class="fa fa-undo"></i> <?= $isThai ? 'คืนเงิน' : 'Process Refund' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
var PAYMENT_LINK = <?= json_encode($payLink) ?>;

function copyPayLink() {
    navigator.clipboard.writeText(PAYMENT_LINK).then(function() {
        var btn = document.getElementById('copyLinkBtn');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check"></i> <?= $isThai ? 'คัดลอกแล้ว!' : 'Copied!' ?>';
        btn.style.background = '#d1fae5';
        btn.style.color = '#059669';
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.style.background = '';
            btn.style.color = '';
        }, 2500);
    }).catch(function() {
        // Fallback: show link in a prompt
        prompt('Copy this payment link:', PAYMENT_LINK);
    });
}

function openPaymentModal(type) {
    if (type === 'refund') {
        document.getElementById('refundModal').classList.add('active');
    } else {
        document.getElementById('paymentModal').classList.add('active');
    }
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.getElementById('refundModal').classList.remove('active');
}
// Close on overlay click
document.querySelectorAll('.pay-modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) closePaymentModal();
    });
});
// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePaymentModal();
});

// Sync partial amount/type fields across partial gateway forms
(function() {
    var amountInput = document.getElementById('gwCustomAmount');
    var typeSelect  = document.getElementById('gwCustomType');
    if (!amountInput || !typeSelect) return;

    function syncPartial() {
        var amt  = amountInput.value;
        var type = typeSelect.value;
        document.querySelectorAll('.gw-amount-field').forEach(function(el) { el.value = amt; });
        document.querySelectorAll('.gw-type-field').forEach(function(el)  { el.value = type; });
    }
    amountInput.addEventListener('input', syncPartial);
    typeSelect.addEventListener('change', syncPartial);
})();
</script>
