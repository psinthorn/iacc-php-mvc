<?php
/**
 * Journal Voucher Detail View — Modern UI
 * 
 * Shows journal voucher header + debit/credit entries
 * Variables from controller: $voucher
 */

$isThai = ($lang ?? '2') === '1';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$statusLabels = [
    'draft'     => $isThai ? 'ฉบับร่าง' : 'Draft',
    'posted'    => $isThai ? 'ผ่านรายการแล้ว' : 'Posted',
    'cancelled' => $isThai ? 'ยกเลิก' : 'Cancelled',
];
$statusColors = [
    'draft' => '#f59e0b', 'posted' => '#10b981', 'cancelled' => '#6b7280',
];
$typeLabels = [
    'general'    => $isThai ? 'ทั่วไป' : 'General',
    'payment'    => $isThai ? 'จ่ายเงิน' : 'Payment',
    'receipt'    => $isThai ? 'รับเงิน' : 'Receipt',
    'adjustment' => $isThai ? 'ปรับปรุง' : 'Adjustment',
    'opening'    => $isThai ? 'เปิดบัญชี' : 'Opening',
    'closing'    => $isThai ? 'ปิดบัญชี' : 'Closing',
];
$typeIcons = [
    'general' => '📄', 'payment' => '💸', 'receipt' => '💰',
    'adjustment' => '🔧', 'opening' => '📂', 'closing' => '📕',
];

$entries = $voucher['entries'] ?? [];
$status = $voucher['status'] ?? 'draft';
?>

<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2>
                    <?= $typeIcons[$voucher['voucher_type']] ?? '📄' ?> <?= htmlspecialchars($voucher['jv_number']) ?>
                    <span style="padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; background:<?= $statusColors[$status] ?? '#94a3b8' ?>;">
                        <?= $statusLabels[$status] ?? $status ?>
                    </span>
                </h2>
                <p><?= $typeLabels[$voucher['voucher_type']] ?? '' ?> — <?= date('d/m/Y', strtotime($voucher['transaction_date'])) ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=journal_list" class="btn-header btn-header-outline">
                    ← <?= $isThai ? 'กลับรายการ' : 'Back to List' ?>
                </a>
                <?php if ($status === 'draft'): ?>
                <a href="index.php?page=journal_form&id=<?= $voucher['id'] ?>" class="btn-header btn-header-outline">
                    ✏️ <?= $isThai ? 'แก้ไข' : 'Edit' ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success === 'posted'): ?>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#16a34a; font-weight:500;">
        ✅ <?= $isThai ? 'ผ่านรายการสำเร็จ' : 'Journal voucher posted successfully' ?>
    </div>
    <?php elseif ($success === 'cancelled'): ?>
    <div style="background:#fefce8; border:1px solid #fde68a; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#ca8a04; font-weight:500;">
        ⚠️ <?= $isThai ? 'ยกเลิกใบสำคัญสำเร็จ' : 'Journal voucher cancelled' ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#dc2626; font-weight:500;">
        ❌ <?= $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred' ?>
    </div>
    <?php endif; ?>

    <!-- Info Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:16px; margin-bottom:24px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:12px; font-weight:600; color:#94a3b8; text-transform:uppercase; margin-bottom:8px;"><?= $isThai ? 'ข้อมูลทั่วไป' : 'General Info' ?></div>
            <div style="display:grid; gap:8px; font-size:14px;">
                <div><span style="color:#64748b;"><?= $isThai ? 'ประเภท:' : 'Type:' ?></span> <strong><?= $typeLabels[$voucher['voucher_type']] ?? '' ?></strong></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'วันที่:' : 'Date:' ?></span> <strong><?= date('d/m/Y', strtotime($voucher['transaction_date'])) ?></strong></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'สร้างโดย:' : 'Created by:' ?></span> <strong><?= htmlspecialchars($voucher['created_by_name'] ?? '-') ?></strong></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'สร้างเมื่อ:' : 'Created:' ?></span> <?= $voucher['created_at'] ? date('d/m/Y H:i', strtotime($voucher['created_at'])) : '-' ?></div>
            </div>
        </div>

        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:12px; font-weight:600; color:#94a3b8; text-transform:uppercase; margin-bottom:8px;"><?= $isThai ? 'ยอดรวม' : 'Totals' ?></div>
            <div style="display:grid; gap:8px; font-size:14px;">
                <div><span style="color:#64748b;"><?= $isThai ? 'รวมเดบิต:' : 'Total Debit:' ?></span> <strong style="font-family:monospace; color:#0d9488;">฿<?= number_format($voucher['total_debit'], 2) ?></strong></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'รวมเครดิต:' : 'Total Credit:' ?></span> <strong style="font-family:monospace; color:#0891b2;">฿<?= number_format($voucher['total_credit'], 2) ?></strong></div>
                <?php $diff = abs((float) $voucher['total_debit'] - (float) $voucher['total_credit']); ?>
                <div>
                    <span style="display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; background:<?= $diff < 0.01 ? '#f0fdf4' : '#fef2f2' ?>; color:<?= $diff < 0.01 ? '#10b981' : '#ef4444' ?>;">
                        <?= $diff < 0.01 ? '✅ Balanced' : '❌ Unbalanced: ฿' . number_format($diff, 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:12px; font-weight:600; color:#94a3b8; text-transform:uppercase; margin-bottom:8px;"><?= $isThai ? 'อ้างอิงและสถานะ' : 'Reference & Status' ?></div>
            <div style="display:grid; gap:8px; font-size:14px;">
                <div><span style="color:#64748b;"><?= $isThai ? 'อ้างอิง:' : 'Reference:' ?></span> <strong><?= htmlspecialchars($voucher['reference'] ?: '-') ?></strong></div>
                <?php if ($status === 'posted' && $voucher['posted_at']): ?>
                <div><span style="color:#64748b;"><?= $isThai ? 'ผ่านรายการเมื่อ:' : 'Posted at:' ?></span> <?= date('d/m/Y H:i', strtotime($voucher['posted_at'])) ?></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'ผ่านรายการโดย:' : 'Posted by:' ?></span> <?= htmlspecialchars($voucher['posted_by_name'] ?? '-') ?></div>
                <?php endif; ?>
                <?php if ($status === 'cancelled' && $voucher['cancelled_at']): ?>
                <div><span style="color:#64748b;"><?= $isThai ? 'ยกเลิกเมื่อ:' : 'Cancelled at:' ?></span> <?= date('d/m/Y H:i', strtotime($voucher['cancelled_at'])) ?></div>
                <div><span style="color:#64748b;"><?= $isThai ? 'เหตุผล:' : 'Reason:' ?></span> <?= htmlspecialchars($voucher['cancel_reason'] ?: '-') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if ($voucher['description']): ?>
    <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
        <div style="font-size:12px; font-weight:600; color:#94a3b8; text-transform:uppercase; margin-bottom:8px;"><?= $isThai ? 'คำอธิบาย' : 'Description' ?></div>
        <p style="margin:0; color:#1e293b; font-size:15px;"><?= nl2br(htmlspecialchars($voucher['description'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Journal Entries Table -->
    <div style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:24px;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0;">
            <h3 style="margin:0; font-size:16px; color:#1e293b;">📊 <?= $isThai ? 'รายการบัญชี' : 'Journal Entries' ?> (<?= count($entries) ?> <?= $isThai ? 'รายการ' : 'lines' ?>)</h3>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0; width:50px;">#</th>
                    <th style="padding:12px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'รหัสบัญชี' : 'Account Code' ?></th>
                    <th style="padding:12px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'ชื่อบัญชี' : 'Account Name' ?></th>
                    <th style="padding:12px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'คำอธิบาย' : 'Description' ?></th>
                    <th style="padding:12px 16px; text-align:right; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'เดบิต' : 'Debit' ?></th>
                    <th style="padding:12px 16px; text-align:right; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'เครดิต' : 'Credit' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $i => $entry): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:12px 16px; color:#94a3b8; font-size:13px;"><?= $i + 1 ?></td>
                    <td style="padding:12px 16px; font-family:monospace; font-weight:600; color:#0d9488;"><?= htmlspecialchars($entry['account_code']) ?></td>
                    <td style="padding:12px 16px; font-size:14px; color:#1e293b;">
                        <?= htmlspecialchars($entry['account_name']) ?>
                        <?php if ($isThai && $entry['account_name_th']): ?>
                            <span style="display:block; font-size:12px; color:#94a3b8;"><?= htmlspecialchars($entry['account_name_th']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px; font-size:14px; color:#64748b;"><?= htmlspecialchars($entry['description'] ?: '-') ?></td>
                    <td style="padding:12px 16px; text-align:right; font-family:monospace; font-weight:<?= $entry['debit'] > 0 ? '600' : '400' ?>; color:<?= $entry['debit'] > 0 ? '#1e293b' : '#d1d5db' ?>;">
                        <?= $entry['debit'] > 0 ? '฿' . number_format($entry['debit'], 2) : '-' ?>
                    </td>
                    <td style="padding:12px 16px; text-align:right; font-family:monospace; font-weight:<?= $entry['credit'] > 0 ? '600' : '400' ?>; color:<?= $entry['credit'] > 0 ? '#1e293b' : '#d1d5db' ?>;">
                        <?= $entry['credit'] > 0 ? '฿' . number_format($entry['credit'], 2) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; border-top:2px solid #0d9488;">
                    <td colspan="4" style="padding:14px 16px; font-weight:700; color:#1e293b;"><?= $isThai ? 'รวมทั้งหมด' : 'Grand Total' ?></td>
                    <td style="padding:14px 16px; text-align:right; font-family:monospace; font-weight:700; color:#0d9488; font-size:15px;">
                        ฿<?= number_format($voucher['total_debit'], 2) ?>
                    </td>
                    <td style="padding:14px 16px; text-align:right; font-family:monospace; font-weight:700; color:#0891b2; font-size:15px;">
                        ฿<?= number_format($voucher['total_credit'], 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Action Buttons -->
    <?php if ($status === 'draft'): ?>
    <div style="display:flex; gap:12px; justify-content:flex-end; flex-wrap:wrap;">
        <!-- Post Voucher -->
        <form method="POST" action="index.php?page=journal_post" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ยืนยันผ่านรายการ? หลังผ่านรายการจะแก้ไขไม่ได้' : 'Confirm posting? Posted vouchers cannot be edited.' ?>')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $voucher['id'] ?>">
            <button type="submit" style="padding:12px 28px; background:#10b981; color:white; border:none; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer;">
                ✅ <?= $isThai ? 'ผ่านรายการ (Post)' : 'Post Voucher' ?>
            </button>
        </form>
        <!-- Cancel Voucher -->
        <form method="POST" action="index.php?page=journal_cancel" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ยืนยันยกเลิก?' : 'Confirm cancellation?' ?>')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $voucher['id'] ?>">
            <input type="text" name="cancel_reason" placeholder="<?= $isThai ? 'เหตุผลที่ยกเลิก' : 'Cancellation reason' ?>" style="padding:12px 16px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; width:200px;">
            <button type="submit" style="padding:12px 28px; background:#ef4444; color:white; border:none; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer;">
                ✕ <?= $isThai ? 'ยกเลิก' : 'Cancel Voucher' ?>
            </button>
        </form>
        <!-- Delete Draft -->
        <form method="POST" action="index.php?page=journal_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบฉบับร่างนี้?' : 'Delete this draft?' ?>')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $voucher['id'] ?>">
            <button type="submit" style="padding:12px 28px; background:#f1f5f9; color:#ef4444; border:1px solid #fecaca; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer;">
                🗑️ <?= $isThai ? 'ลบ' : 'Delete' ?>
            </button>
        </form>
    </div>
    <?php elseif ($status === 'posted'): ?>
    <div style="display:flex; gap:12px; justify-content:flex-end;">
        <form method="POST" action="index.php?page=journal_cancel" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ยืนยันยกเลิกใบสำคัญที่ผ่านรายการแล้ว? การยกเลิกจะกลับรายการทั้งหมด' : 'Reverse this posted voucher? This will undo all accounting entries.' ?>')">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $voucher['id'] ?>">
            <input type="text" name="cancel_reason" placeholder="<?= $isThai ? 'เหตุผลที่ยกเลิก' : 'Reversal reason' ?>" required style="padding:12px 16px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; width:250px;">
            <button type="submit" style="padding:12px 28px; background:#f59e0b; color:white; border:none; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer;">
                ↩️ <?= $isThai ? 'กลับรายการ' : 'Reverse' ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

</div>
