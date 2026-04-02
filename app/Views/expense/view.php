<?php
/**
 * Expense Detail View
 * 
 * Uses master-data.css design system
 * Variables from controller: $expense, $message, $lang
 */

$isThai = ($lang ?? '2') === '1';

$messages = [
    'status_updated' => ['\u2705', $isThai ? '\u0e2d\u0e31\u0e1e\u0e40\u0e14\u0e17\u0e2a\u0e16\u0e32\u0e19\u0e30\u0e2a\u0e33\u0e40\u0e23\u0e47\u0e08' : 'Status updated'],
];

$statusLabels = [
    'draft' => $isThai ? 'ฉบับร่าง' : 'Draft',
    'pending' => $isThai ? 'รอดำเนินการ' : 'Pending',
    'approved' => $isThai ? 'อนุมัติแล้ว' : 'Approved',
    'paid' => $isThai ? 'จ่ายแล้ว' : 'Paid',
    'rejected' => $isThai ? 'ปฏิเสธ' : 'Rejected',
    'cancelled' => $isThai ? 'ยกเลิก' : 'Cancelled',
];
$statusColors = [
    'draft' => '#94a3b8', 'pending' => '#f59e0b', 'approved' => '#3b82f6',
    'paid' => '#10b981', 'rejected' => '#ef4444', 'cancelled' => '#6b7280',
];

$canEdit = in_array($expense['status'], ['draft', 'pending']);
$canApprove = in_array($expense['status'], ['pending']);
$canPay = in_array($expense['status'], ['approved']);
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.detail-container { background: white; border-radius: 14px; padding: 28px; border: 1px solid var(--md-border, #e2e8f0); margin-top: 16px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.detail-item label { display: block; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.detail-item .value { font-size: 15px; color: #1e293b; font-weight: 500; }
.detail-divider { border-top: 1px solid #f1f5f9; margin: 24px 0; }
.amount-summary {
    background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);
    border-radius: 14px; padding: 24px; border: 1px solid #e0f2fe;
}
.amount-line { display: flex; justify-content: space-between; padding: 8px 0; font-size: 15px; }
.amount-line.net { border-top: 2px solid #0ea5e9; margin-top: 8px; padding-top: 14px; font-size: 20px; font-weight: 700; color: #0369a1; }
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; color: white; }
.action-btn { padding: 10px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.action-btn:hover { transform: translateY(-1px); }
.btn-approve { background: #3b82f6; color: white; }
.btn-pay { background: #10b981; color: white; }
.btn-reject { background: #ef4444; color: white; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= htmlspecialchars($expense['expense_number'] ?? '-') ?></h2>
                <p><?= htmlspecialchars($expense['title']) ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=expense_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <?php if ($canEdit): ?>
                <a href="index.php?page=expense_form&id=<?= $expense['id'] ?>" class="btn-header btn-header-primary">
                    <i class="fa fa-pencil"></i> <?= $isThai ? 'แก้ไข' : 'Edit' ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <div class="detail-container">
        <!-- Status Bar -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <span class="status-badge" style="background: <?= $statusColors[$expense['status']] ?? '#94a3b8' ?>;">
                <i class="fa fa-circle" style="font-size:8px;"></i> <?= $statusLabels[$expense['status']] ?? $expense['status'] ?>
            </span>
            <span style="color:#94a3b8; font-size:13px;">
                <?= $isThai ? 'สร้างเมื่อ' : 'Created' ?>: <?= date('d/m/Y H:i', strtotime($expense['created_at'])) ?>
            </span>
        </div>

        <!-- Main Details -->
        <div class="detail-grid">
            <div class="detail-item">
                <label><?= $isThai ? 'วันที่' : 'Expense Date' ?></label>
                <div class="value"><?= date('d/m/Y', strtotime($expense['expense_date'])) ?></div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'หมวดหมู่' : 'Category' ?></label>
                <div class="value">
                    <?php if ($expense['category_name']): ?>
                        <i class="fa <?= $expense['category_icon'] ?? 'fa-folder' ?>" style="color:<?= $expense['category_color'] ?? '#6366f1' ?>;"></i>
                        <?= htmlspecialchars($isThai && $expense['category_name_th'] ? $expense['category_name_th'] : $expense['category_name']) ?>
                    <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'วิธีการชำระ' : 'Payment Method' ?></label>
                <div class="value"><?= htmlspecialchars($expense['payment_method'] ?: '-') ?></div>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <label><?= $isThai ? 'ผู้ขาย' : 'Vendor' ?></label>
                <div class="value"><?= htmlspecialchars($expense['vendor_name'] ?: '-') ?></div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID' ?></label>
                <div class="value"><?= htmlspecialchars($expense['vendor_tax_id'] ?: '-') ?></div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'เลขที่อ้างอิง' : 'Reference' ?></label>
                <div class="value"><?= htmlspecialchars($expense['reference_no'] ?: '-') ?></div>
            </div>
        </div>

        <?php if ($expense['project_name'] || $expense['due_date']): ?>
        <div class="detail-grid">
            <div class="detail-item">
                <label><?= $isThai ? 'โปรเจกต์' : 'Project' ?></label>
                <div class="value"><?= htmlspecialchars($expense['project_name'] ?: '-') ?></div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'วันครบกำหนด' : 'Due Date' ?></label>
                <div class="value"><?= $expense['due_date'] ? date('d/m/Y', strtotime($expense['due_date'])) : '-' ?></div>
            </div>
            <div class="detail-item">
                <label><?= $isThai ? 'วันที่จ่าย' : 'Paid Date' ?></label>
                <div class="value"><?= $expense['paid_date'] ? date('d/m/Y', strtotime($expense['paid_date'])) : '-' ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($expense['description']): ?>
        <div class="detail-divider"></div>
        <div class="detail-item">
            <label><?= $isThai ? 'รายละเอียด' : 'Description' ?></label>
            <div class="value" style="white-space:pre-wrap;"><?= htmlspecialchars($expense['description']) ?></div>
        </div>
        <?php endif; ?>

        <?php if ($expense['receipt_file']): ?>
        <div class="detail-divider"></div>
        <div class="detail-item">
            <label><?= $isThai ? 'ไฟล์แนบ' : 'Receipt' ?></label>
            <div class="value">
                <a href="<?= htmlspecialchars($expense['receipt_file']) ?>" target="_blank" style="color:var(--md-primary,#4f46e5);">
                    <i class="fa fa-paperclip"></i> <?= basename($expense['receipt_file']) ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Amount Summary -->
        <div class="detail-divider"></div>
        <div class="amount-summary">
            <div class="amount-line">
                <span><?= $isThai ? 'จำนวนเงิน' : 'Subtotal' ?></span>
                <span style="font-family:'JetBrains Mono',monospace;">฿<?= number_format($expense['amount'], 2) ?></span>
            </div>
            <?php if ($expense['vat_amount'] > 0): ?>
            <div class="amount-line">
                <span><?= $isThai ? 'VAT' : 'VAT' ?> (<?= $expense['vat_rate'] ?>%)</span>
                <span style="font-family:'JetBrains Mono',monospace;">+฿<?= number_format($expense['vat_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($expense['wht_amount'] > 0): ?>
            <div class="amount-line">
                <span><?= $isThai ? 'WHT หัก ณ ที่จ่าย' : 'WHT Deducted' ?> (<?= $expense['wht_rate'] ?>%)</span>
                <span style="font-family:'JetBrains Mono',monospace; color:#ef4444;">-฿<?= number_format($expense['wht_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="amount-line net">
                <span><?= $isThai ? 'ยอดสุทธิ' : 'Net Payable' ?></span>
                <span style="font-family:'JetBrains Mono',monospace;">฿<?= number_format($expense['net_amount'], 2) ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <?php if ($canApprove || $canPay): ?>
        <div class="detail-divider"></div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <?php if ($canApprove): ?>
            <form method="post" action="index.php?page=expense_status" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <input type="hidden" name="status" value="rejected">
                <input type="hidden" name="redirect" value="expense_view&id=<?= $expense['id'] ?>">
                <button type="submit" class="action-btn btn-reject" onclick="return confirm('<?= $isThai ? 'ยืนยันการปฏิเสธ?' : 'Reject this expense?' ?>')">
                    <i class="fa fa-times"></i> <?= $isThai ? 'ปฏิเสธ' : 'Reject' ?>
                </button>
            </form>
            <form method="post" action="index.php?page=expense_status" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <input type="hidden" name="status" value="approved">
                <input type="hidden" name="redirect" value="expense_view&id=<?= $expense['id'] ?>">
                <button type="submit" class="action-btn btn-approve">
                    <i class="fa fa-check"></i> <?= $isThai ? 'อนุมัติ' : 'Approve' ?>
                </button>
            </form>
            <?php endif; ?>
            <?php if ($canPay): ?>
            <form method="post" action="index.php?page=expense_status" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
                <input type="hidden" name="status" value="paid">
                <input type="hidden" name="redirect" value="expense_view&id=<?= $expense['id'] ?>">
                <button type="submit" class="action-btn btn-pay">
                    <i class="fa fa-money"></i> <?= $isThai ? 'บันทึกการจ่าย' : 'Mark as Paid' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
