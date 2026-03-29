<?php
/**
 * Trial Balance Report — Modern UI
 * 
 * Shows all accounts with debit/credit totals from posted journal entries
 * Variables from controller: $trialBalance
 */

$isThai = ($lang ?? '2') === '1';

$typeLabels = [
    'asset'     => $isThai ? 'สินทรัพย์' : 'Assets',
    'liability' => $isThai ? 'หนี้สิน' : 'Liabilities',
    'equity'    => $isThai ? 'ส่วนของเจ้าของ' : 'Equity',
    'revenue'   => $isThai ? 'รายได้' : 'Revenue',
    'expense'   => $isThai ? 'ค่าใช้จ่าย' : 'Expenses',
];
$typeColors = [
    'asset' => '#3b82f6', 'liability' => '#ef4444', 'equity' => '#8b5cf6',
    'revenue' => '#10b981', 'expense' => '#f59e0b',
];

$grandDebit = 0;
$grandCredit = 0;
foreach ($trialBalance as $row) {
    $grandDebit += (float) $row['total_debit'];
    $grandCredit += (float) $row['total_credit'];
}
$isBalanced = abs($grandDebit - $grandCredit) < 0.01;
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
}
</style>

<div style="max-width:1000px; margin:0 auto; padding:20px;">

    <!-- Header -->
    <div class="no-print" style="background:linear-gradient(135deg, #0d9488, #0891b2); border-radius:16px; padding:28px 32px; margin-bottom:24px; color:white;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 style="margin:0; font-size:24px; font-weight:700;">
                    📋 <?= $isThai ? 'งบทดลอง (Trial Balance)' : 'Trial Balance' ?>
                </h2>
                <p style="margin:4px 0 0; opacity:0.9; font-size:14px;">
                    <?= $isThai ? 'ยอดรวม Debit/Credit จากใบสำคัญที่ผ่านรายการแล้ว' : 'Debit/Credit totals from posted journal vouchers' ?>
                </p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="index.php?page=journal_list" style="padding:10px 20px; background:rgba(255,255,255,0.2); color:white; text-decoration:none; border-radius:10px; font-weight:600; font-size:14px;">
                    ← <?= $isThai ? 'สมุดรายวัน' : 'Journal Vouchers' ?>
                </a>
                <button onclick="window.print()" style="padding:10px 20px; background:white; color:#0d9488; border:none; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer;">
                    🖨️ <?= $isThai ? 'พิมพ์' : 'Print' ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Print Header -->
    <div style="display:none; text-align:center; margin-bottom:20px;" class="print-header">
        <h2 style="margin:0;"><?= $isThai ? 'งบทดลอง' : 'Trial Balance' ?></h2>
        <p style="margin:4px 0; color:#666;"><?= date('d/m/Y') ?></p>
    </div>

    <!-- Balance Status -->
    <div style="text-align:center; padding:16px; margin-bottom:24px; border-radius:12px; background:<?= $isBalanced ? '#f0fdf4' : '#fef2f2' ?>; border:1px solid <?= $isBalanced ? '#bbf7d0' : '#fecaca' ?>;">
        <span style="font-weight:700; font-size:16px; color:<?= $isBalanced ? '#10b981' : '#ef4444' ?>;">
            <?= $isBalanced ? '✅' : '❌' ?>
            <?= $isBalanced 
                ? ($isThai ? 'งบทดลองสมดุล' : 'Trial Balance is Balanced') 
                : ($isThai ? 'งบทดลองไม่สมดุล — ส่วนต่าง: ฿' . number_format(abs($grandDebit - $grandCredit), 2) : 'Trial Balance is Unbalanced — Difference: ฿' . number_format(abs($grandDebit - $grandCredit), 2)) 
            ?>
        </span>
    </div>

    <!-- Table -->
    <div style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'รหัส' : 'Code' ?></th>
                    <th style="padding:14px 16px; text-align:left; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'ชื่อบัญชี' : 'Account Name' ?></th>
                    <th style="padding:14px 16px; text-align:center; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                    <th style="padding:14px 16px; text-align:right; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'เดบิต' : 'Debit' ?></th>
                    <th style="padding:14px 16px; text-align:right; font-size:13px; font-weight:600; color:#475569; border-bottom:2px solid #e2e8f0;"><?= $isThai ? 'เครดิต' : 'Credit' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trialBalance)): ?>
                <tr>
                    <td colspan="5" style="padding:60px 20px; text-align:center; color:#94a3b8;">
                        <div style="font-size:48px; margin-bottom:12px;">📋</div>
                        <div style="font-size:16px; font-weight:600;"><?= $isThai ? 'ยังไม่มีรายการ — กรุณาสร้างใบสำคัญและผ่านรายการก่อน' : 'No entries yet — create and post journal vouchers first' ?></div>
                    </td>
                </tr>
                <?php else: ?>
                <?php $currentType = ''; ?>
                <?php foreach ($trialBalance as $row): ?>
                    <?php if ($row['account_type'] !== $currentType): ?>
                        <?php $currentType = $row['account_type']; ?>
                        <tr style="background:<?= $typeColors[$currentType] ?>08;">
                            <td colspan="5" style="padding:10px 16px; font-weight:700; font-size:13px; color:<?= $typeColors[$currentType] ?>; border-bottom:1px solid <?= $typeColors[$currentType] ?>20;">
                                <?= $typeLabels[$currentType] ?? $currentType ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php $hasActivity = (float) $row['total_debit'] > 0 || (float) $row['total_credit'] > 0; ?>
                    <tr style="border-bottom:1px solid #f1f5f9; <?= !$hasActivity ? 'opacity:0.5;' : '' ?>">
                        <td style="padding:10px 16px; font-family:monospace; font-weight:600; color:<?= $typeColors[$row['account_type']] ?>; padding-left:<?= 16 + ($row['level'] - 1) * 20 ?>px;">
                            <?= htmlspecialchars($row['account_code']) ?>
                        </td>
                        <td style="padding:10px 16px; font-size:14px; color:#1e293b;">
                            <?= htmlspecialchars($isThai && $row['account_name_th'] ? $row['account_name_th'] : $row['account_name']) ?>
                        </td>
                        <td style="padding:10px 16px; text-align:center;">
                            <span style="padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; background:<?= $typeColors[$row['account_type']] ?>15; color:<?= $typeColors[$row['account_type']] ?>;">
                                <?= $typeLabels[$row['account_type']] ?? '' ?>
                            </span>
                        </td>
                        <td style="padding:10px 16px; text-align:right; font-family:monospace; font-weight:<?= (float) $row['total_debit'] > 0 ? '600' : '400' ?>; color:<?= (float) $row['total_debit'] > 0 ? '#1e293b' : '#d1d5db' ?>;">
                            <?= (float) $row['total_debit'] > 0 ? '฿' . number_format($row['total_debit'], 2) : '-' ?>
                        </td>
                        <td style="padding:10px 16px; text-align:right; font-family:monospace; font-weight:<?= (float) $row['total_credit'] > 0 ? '600' : '400' ?>; color:<?= (float) $row['total_credit'] > 0 ? '#1e293b' : '#d1d5db' ?>;">
                            <?= (float) $row['total_credit'] > 0 ? '฿' . number_format($row['total_credit'], 2) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; border-top:3px solid #0d9488;">
                    <td colspan="3" style="padding:16px; font-weight:700; font-size:15px; color:#1e293b;">
                        <?= $isThai ? 'รวมทั้งหมด' : 'Grand Total' ?>
                    </td>
                    <td style="padding:16px; text-align:right; font-family:monospace; font-weight:700; font-size:16px; color:#0d9488;">
                        ฿<?= number_format($grandDebit, 2) ?>
                    </td>
                    <td style="padding:16px; text-align:right; font-family:monospace; font-weight:700; font-size:16px; color:#0891b2;">
                        ฿<?= number_format($grandCredit, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

<style>
@media print {
    .print-header { display: block !important; }
}
</style>
