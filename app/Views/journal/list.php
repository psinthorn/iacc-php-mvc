<?php
$pageTitle = 'Journal';

/**
 * Journal Voucher List — Modern UI
 * 
 * Uses master-data.css design system with teal/cyan theme for Accounting module
 * Variables from controller: $vouchers, $filters, $stats, $page, $perPage, $totalCount, $totalPages
 */

$isThai = ($lang ?? '2') === '1';

$statusLabels = [
    'draft'     => $isThai ? 'ฉบับร่าง' : 'Draft',
    'posted'    => $isThai ? 'ผ่านรายการ' : 'Posted',
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
?>

<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2>📒 <?= $isThai ? 'สมุดรายวัน' : 'Journal Vouchers' ?></h2>
                <p><?= $isThai ? 'บันทึกรายการบัญชีแบบ Debit/Credit' : 'Double-entry bookkeeping journal entries' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=journal_accounts" class="btn-header btn-header-outline">
                    📊 <?= $isThai ? 'ผังบัญชี' : 'Chart of Accounts' ?>
                </a>
                <a href="index.php?page=journal_trial_balance" class="btn-header btn-header-outline">
                    📋 <?= $isThai ? 'งบทดลอง' : 'Trial Balance' ?>
                </a>
                <a href="index.php?page=journal_form" class="btn-header btn-header-primary">
                    ＋ <?= $isThai ? 'สร้างใบสำคัญ' : 'New Journal Voucher' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:24px;">
        <div style="background:white; border-radius:12px; padding:20px; border-left:4px solid #0d9488; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:28px; font-weight:700; color:#0d9488;"><?= number_format($stats['total'] ?? 0) ?></div>
            <div style="font-size:13px; color:#64748b;"><?= $isThai ? 'ทั้งหมด' : 'Total Vouchers' ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border-left:4px solid #f59e0b; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:28px; font-weight:700; color:#f59e0b;"><?= number_format($stats['draft_count'] ?? 0) ?></div>
            <div style="font-size:13px; color:#64748b;"><?= $isThai ? 'ฉบับร่าง' : 'Drafts' ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border-left:4px solid #10b981; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:28px; font-weight:700; color:#10b981;"><?= number_format($stats['posted_count'] ?? 0) ?></div>
            <div style="font-size:13px; color:#64748b;"><?= $isThai ? 'ผ่านรายการแล้ว' : 'Posted' ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border-left:4px solid #3b82f6; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:28px; font-weight:700; color:#3b82f6;">฿<?= number_format($stats['total_posted_amount'] ?? 0, 2) ?></div>
            <div style="font-size:13px; color:#64748b;"><?= $isThai ? 'ยอดผ่านรายการ' : 'Posted Amount' ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; background:white; padding:16px 20px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px;">
        <input type="hidden" name="page" value="journal_list">
        <div style="flex:1; min-width:180px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ค้นหา' : 'Search' ?></label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= $isThai ? 'เลขที่, คำอธิบาย, อ้างอิง...' : 'JV#, description, reference...' ?>" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
        </div>
        <div style="min-width:140px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'สถานะ' : 'Status' ?></label>
            <select name="status" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                <option value=""><?= $isThai ? 'ทั้งหมด' : 'All' ?></option>
                <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:140px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ประเภท' : 'Type' ?></label>
            <select name="voucher_type" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                <option value=""><?= $isThai ? 'ทั้งหมด' : 'All' ?></option>
                <?php foreach ($typeLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['voucher_type'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:140px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ตั้งแต่วันที่' : 'From Date' ?></label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
        </div>
        <div style="min-width:140px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ถึงวันที่' : 'To Date' ?></label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" style="padding:8px 20px; background:#0d9488; color:white; border:none; border-radius:8px; font-size:14px; cursor:pointer; font-weight:600;">🔍 <?= $isThai ? 'ค้นหา' : 'Search' ?></button>
            <a href="index.php?page=journal_list" style="padding:8px 16px; background:#f1f5f9; color:#64748b; text-decoration:none; border-radius:8px; font-size:14px;">↻ <?= $isThai ? 'รีเซ็ต' : 'Reset' ?></a>
        </div>
    </form>

    <!-- Table -->
    <div style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'เลขที่' : 'JV Number' ?></th>
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'วันที่' : 'Date' ?></th>
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'คำอธิบาย' : 'Description' ?></th>
                    <th style="padding:14px 16px; text-align:right; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'ยอดเดบิต' : 'Debit' ?></th>
                    <th style="padding:14px 16px; text-align:center; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th style="padding:14px 16px; text-align:center; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'การดำเนินการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                <tr>
                    <td colspan="7" style="padding:60px 20px; text-align:center; color:#94a3b8;">
                        <div style="font-size:48px; margin-bottom:12px;">📒</div>
                        <div style="font-size:16px; font-weight:600;"><?= $isThai ? 'ยังไม่มีรายการสมุดรายวัน' : 'No journal vouchers yet' ?></div>
                        <a href="index.php?page=journal_form" style="display:inline-block; margin-top:12px; padding:10px 24px; background:#0d9488; color:white; text-decoration:none; border-radius:8px; font-weight:600;">
                            ＋ <?= $isThai ? 'สร้างรายการแรก' : 'Create First Entry' ?>
                        </a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($vouchers as $v): ?>
                <tr style="border-bottom:1px solid #f1f5f9; cursor:pointer;" onclick="window.location='index.php?page=journal_view&id=<?= $v['id'] ?>'" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding:12px 16px;">
                        <span style="font-weight:600; color:#0d9488; font-family:monospace;"><?= htmlspecialchars($v['jv_number']) ?></span>
                    </td>
                    <td style="padding:12px 16px; color:#475569; font-size:14px;"><?= date('d/m/Y', strtotime($v['transaction_date'])) ?></td>
                    <td style="padding:12px 16px; font-size:14px;">
                        <?= $typeIcons[$v['voucher_type']] ?? '📄' ?> <?= $typeLabels[$v['voucher_type']] ?? $v['voucher_type'] ?>
                    </td>
                    <td style="padding:12px 16px; color:#475569; font-size:14px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= htmlspecialchars($v['description'] ?? '-') ?>
                        <?php if ($v['reference']): ?>
                            <span style="font-size:12px; color:#94a3b8; display:block;">Ref: <?= htmlspecialchars($v['reference']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 16px; text-align:right; font-weight:600; font-family:monospace; color:#1e293b;">
                        ฿<?= number_format($v['total_debit'], 2) ?>
                    </td>
                    <td style="padding:12px 16px; text-align:center;">
                        <span style="display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; background:<?= $statusColors[$v['status']] ?? '#94a3b8' ?>20; color:<?= $statusColors[$v['status']] ?? '#94a3b8' ?>;">
                            <?= $statusLabels[$v['status']] ?? $v['status'] ?>
                        </span>
                    </td>
                    <td style="padding:12px 16px; text-align:center;" onclick="event.stopPropagation()">
                        <a href="index.php?page=journal_view&id=<?= $v['id'] ?>" style="color:#0d9488; text-decoration:none; font-size:13px; margin-right:8px;" title="View">👁️</a>
                        <?php if ($v['status'] === 'draft'): ?>
                        <a href="index.php?page=journal_form&id=<?= $v['id'] ?>" style="color:#3b82f6; text-decoration:none; font-size:13px;" title="Edit">✏️</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex; justify-content:center; gap:6px; margin-top:20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="index.php?page=journal_list&p=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>&voucher_type=<?= urlencode($filters['voucher_type'] ?? '') ?>"
           style="padding:8px 14px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:<?= $i === $page ? '700' : '400' ?>; background:<?= $i === $page ? '#0d9488' : '#f1f5f9' ?>; color:<?= $i === $page ? 'white' : '#64748b' ?>;">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div>
