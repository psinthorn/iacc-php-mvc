<?php
$pageTitle = 'Expenses — Project Report';

/**
 * Expense Project Cost Report
 * 
 * Uses master-data.css design system
 * Variables from controller: $projects, $projectExpenses, $selectedProject, $summary, $filters, $lang
 */

$isThai = ($lang ?? '2') === '1';

// Grand totals across all projects
$grandNet = array_sum(array_column($projects, 'total_net'));
$grandPaid = array_sum(array_column($projects, 'paid_amount'));
$grandUnpaid = array_sum(array_column($projects, 'unpaid_amount'));

$statusLabels = [
    'draft' => $isThai ? 'ฉบับร่าง' : 'Draft',
    'pending' => $isThai ? 'รอดำเนินการ' : 'Pending',
    'approved' => $isThai ? 'อนุมัติแล้ว' : 'Approved',
    'paid' => $isThai ? 'จ่ายแล้ว' : 'Paid',
    'rejected' => $isThai ? 'ปฏิเสธ' : 'Rejected',
    'cancelled' => $isThai ? 'ยกเลิก' : 'Cancelled',
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* Filter bar */
.filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; background:white; padding:16px 20px; border-radius:12px; border:1px solid var(--md-border,#e2e8f0); margin-bottom:20px; }
.filter-bar .filter-input,
.filter-bar .filter-select { padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; outline:none; font-family:'Inter',sans-serif; height:36px; min-height:36px; box-sizing:border-box; }
.filter-bar .filter-input:focus,
.filter-bar .filter-select:focus { border-color:var(--md-primary,#4f46e5); box-shadow:0 0 0 3px rgba(79,70,229,0.1); }

/* Data table — extends master-data-table pattern */
.data-table-container { background:white; border-radius:var(--md-radius-md,12px); border:1px solid var(--md-border,#e2e8f0); box-shadow:var(--md-shadow-sm,0 1px 3px rgba(0,0,0,0.08)); overflow:hidden; margin-bottom:20px; }
.data-table { width:100%; border-collapse:collapse; font-size:13px; }
.data-table thead th { padding:12px 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--md-text-secondary,#64748b); background:var(--md-bg-light,#f8fafc); border-bottom:2px solid var(--md-border,#e2e8f0); white-space:nowrap; }
.data-table tbody td { padding:14px 16px; border-bottom:1px solid #f1f5f9; color:var(--md-text-primary,#1e293b); vertical-align:middle; }
.data-table tbody tr { transition:background 0.15s; }
.data-table tbody tr:hover { background:rgba(79,70,229,0.03); }
.data-table tfoot td, .data-table tfoot tr td { padding:14px 16px; }

/* Action button */
.action-btn-sm { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; text-decoration:none; transition:all 0.2s; border:none; cursor:pointer; }
.action-btn-sm:hover { transform:translateY(-1px); box-shadow:0 2px 8px rgba(0,0,0,0.12); }

@media (max-width:768px) {
    .master-data-header .header-content { flex-direction:column; align-items:flex-start; }
    .filter-bar { flex-direction:column; align-items:stretch; }
    .filter-bar .filter-input, .filter-bar .filter-select { width:100% !important; }
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-folder-open"></i> <?= $isThai ? 'รายงานต้นทุนตามโปรเจกต์' : 'Project Cost Report' ?></h2>
                <p><?= $isThai ? 'สรุปค่าใช้จ่ายแยกตามโปรเจกต์' : 'Expense breakdown by project' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=expense_export&format=csv&project=<?= urlencode($selectedProject ?? '') ?>&date_from=<?= $filters['date_from'] ?? '' ?>&date_to=<?= $filters['date_to'] ?? '' ?>" class="btn-header btn-header-primary">
                    <i class="fa fa-download"></i> <?= $isThai ? 'ส่งออก CSV' : 'Export CSV' ?>
                </a>
                <a href="index.php?page=expense_summary" class="btn-header btn-header-outline">
                    <i class="fa fa-bar-chart"></i> <?= $isThai ? 'สรุป' : 'Summary' ?>
                </a>
                <a href="index.php?page=expense_list" class="btn-header btn-header-outline">
                    <i class="fa fa-list"></i> <?= $isThai ? 'รายการ' : 'List' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="index.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; width:100%;">
            <input type="hidden" name="page" value="expense_project_report">
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" class="filter-input" style="width:150px;">
            <span style="color:#94a3b8;">→</span>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" class="filter-input" style="width:150px;">
            <select name="status" class="filter-select" style="width:140px;">
                <option value=""><?= $isThai ? 'ทุกสถานะ' : 'All Status' ?></option>
                <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>><?= $isThai ? 'ฉบับร่าง' : 'Draft' ?></option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>><?= $isThai ? 'รออนุมัติ' : 'Pending' ?></option>
                <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>><?= $isThai ? 'อนุมัติ' : 'Approved' ?></option>
                <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>><?= $isThai ? 'จ่ายแล้ว' : 'Paid' ?></option>
            </select>
            <button type="submit" class="btn-header btn-header-primary" style="padding:8px 16px; background:var(--md-primary,#4f46e5); color:white; border:1px solid var(--md-primary,#4f46e5);"><i class="fa fa-search"></i></button>
            <?php if (!empty($filters['date_from']) || !empty($filters['status'])): ?>
            <a href="index.php?page=expense_project_report" style="color:#ef4444; font-size:13px; text-decoration:none;"><i class="fa fa-times"></i> <?= $isThai ? 'ล้าง' : 'Clear' ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ede9fe;"><i class="fa fa-folder" style="color: #7c3aed;"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= count($projects) ?></span>
                <span class="stat-label"><?= $isThai ? 'โปรเจกต์' : 'Projects' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe;"><i class="fa fa-money" style="color: #3b82f6;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($grandNet, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'ยอดรวม' : 'Total Cost' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dcfce7;"><i class="fa fa-check-circle" style="color: #10b981;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($grandPaid, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'จ่ายแล้ว' : 'Paid' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7;"><i class="fa fa-clock-o" style="color: #f59e0b;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($grandUnpaid, 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'ค้างจ่าย' : 'Unpaid' ?></span>
            </div>
        </div>
    </div>

    <!-- Project Table -->
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= $isThai ? 'โปรเจกต์' : 'Project' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'รายการ' : 'Items' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'ช่วงเวลา' : 'Period' ?></th>
                    <th style="text-align:right;"><?= $isThai ? 'ยอดรวม' : 'Total' ?></th>
                    <th style="text-align:right;"><?= $isThai ? 'จ่ายแล้ว' : 'Paid' ?></th>
                    <th style="text-align:right;"><?= $isThai ? 'ค้างจ่าย' : 'Unpaid' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'ดู' : 'View' ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($projects)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="fa fa-folder-open-o" style="font-size:24px;"></i><br>
                    <?= $isThai ? 'ไม่พบข้อมูลโปรเจกต์' : 'No project expenses found' ?>
                </td></tr>
            <?php else: ?>
                <?php foreach ($projects as $p): 
                    $isSelected = ($selectedProject === $p['project_name']);
                    $pctPaid = $p['total_net'] > 0 ? ($p['paid_amount'] / $p['total_net'] * 100) : 0;
                ?>
                <tr style="<?= $isSelected ? 'background:#f0f9ff;' : '' ?>">
                    <td>
                        <strong style="color:#1e293b;"><?= htmlspecialchars($p['project_name']) ?></strong>
                        <div style="margin-top:4px; height:4px; background:#f1f5f9; border-radius:2px; overflow:hidden;">
                            <div style="height:100%; width:<?= $pctPaid ?>%; background:#10b981; border-radius:2px;"></div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span style="background:#f1f5f9; padding:2px 8px; border-radius:10px; font-size:12px; font-weight:600;"><?= $p['expense_count'] ?></span>
                    </td>
                    <td style="text-align:center; font-size:12px; color:#64748b;">
                        <?= date('d/m/y', strtotime($p['first_expense'])) ?> – <?= date('d/m/y', strtotime($p['last_expense'])) ?>
                    </td>
                    <td style="text-align:right; font-weight:600;">฿<?= number_format($p['total_net'], 2) ?></td>
                    <td style="text-align:right; color:#10b981;">฿<?= number_format($p['paid_amount'], 2) ?></td>
                    <td style="text-align:right; color:#f59e0b;">฿<?= number_format($p['unpaid_amount'], 2) ?></td>
                    <td style="text-align:center;">
                        <a href="index.php?page=expense_project_report&project=<?= urlencode($p['project_name']) ?>&date_from=<?= $filters['date_from'] ?? '' ?>&date_to=<?= $filters['date_to'] ?? '' ?>&status=<?= $filters['status'] ?? '' ?>" 
                           class="action-btn-sm" style="background:<?= $isSelected ? '#4f46e5' : '#f1f5f9' ?>; color:<?= $isSelected ? 'white' : '#64748b' ?>;">
                            <i class="fa fa-<?= $isSelected ? 'eye' : 'chevron-right' ?>"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($projects)): ?>
            <tfoot>
                <tr style="font-weight:700; background:#f8fafc;">
                    <td><?= $isThai ? 'รวมทั้งหมด' : 'Grand Total' ?></td>
                    <td style="text-align:center;"><?= array_sum(array_column($projects, 'expense_count')) ?></td>
                    <td></td>
                    <td style="text-align:right;">฿<?= number_format($grandNet, 2) ?></td>
                    <td style="text-align:right; color:#10b981;">฿<?= number_format($grandPaid, 2) ?></td>
                    <td style="text-align:right; color:#f59e0b;">฿<?= number_format($grandUnpaid, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <!-- Project Detail (if selected) -->
    <?php if (!empty($selectedProject) && !empty($projectExpenses)): ?>
    <div style="margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="font-size:16px; color:#1e293b; margin:0;">
                <i class="fa fa-folder-open" style="color:#4f46e5;"></i> 
                <?= htmlspecialchars($selectedProject) ?>
                <span style="font-size:12px; color:#94a3b8; margin-left:8px;">(<?= count($projectExpenses) ?> <?= $isThai ? 'รายการ' : 'items' ?>)</span>
            </h3>
            <a href="index.php?page=expense_export&format=csv&project=<?= urlencode($selectedProject) ?>&date_from=<?= $filters['date_from'] ?? '' ?>&date_to=<?= $filters['date_to'] ?? '' ?>" 
               class="btn-header btn-header-outline" style="font-size:12px; padding:5px 12px;">
                <i class="fa fa-download"></i> <?= $isThai ? 'ส่งออก' : 'Export' ?>
            </a>
        </div>
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= $isThai ? 'เลขที่' : 'No.' ?></th>
                        <th><?= $isThai ? 'วันที่' : 'Date' ?></th>
                        <th><?= $isThai ? 'รายการ' : 'Title' ?></th>
                        <th><?= $isThai ? 'หมวด' : 'Category' ?></th>
                        <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                        <th style="text-align:right;"><?= $isThai ? 'จำนวน' : 'Amount' ?></th>
                        <th style="text-align:right;"><?= $isThai ? 'VAT' : 'VAT' ?></th>
                        <th style="text-align:right;"><?= $isThai ? 'สุทธิ' : 'Net' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $detailNet = 0;
                foreach ($projectExpenses as $ex): 
                    $detailNet += (float) $ex['net_amount'];
                    $statusColors = ['draft'=>'#94a3b8','pending'=>'#f59e0b','approved'=>'#3b82f6','paid'=>'#10b981','rejected'=>'#ef4444','cancelled'=>'#6b7280'];
                ?>
                <tr>
                    <td><a href="index.php?page=expense_view&id=<?= $ex['id'] ?>" style="color:#4f46e5; text-decoration:none; font-weight:500;"><?= $ex['expense_number'] ?></a></td>
                    <td style="font-size:13px;"><?= date('d/m/Y', strtotime($ex['expense_date'])) ?></td>
                    <td><?= htmlspecialchars($ex['title']) ?></td>
                    <td>
                        <span style="display:inline-flex; align-items:center; gap:5px; font-size:12px;">
                            <i class="fa <?= $ex['category_icon'] ?? 'fa-folder' ?>" style="color:<?= $ex['category_color'] ?? '#6366f1' ?>;"></i>
                            <?= htmlspecialchars($isThai && $ex['category_name_th'] ? $ex['category_name_th'] : $ex['category_name']) ?>
                        </span>
                    </td>
                    <td><span style="background:<?= $statusColors[$ex['status']] ?? '#94a3b8' ?>22; color:<?= $statusColors[$ex['status']] ?? '#94a3b8' ?>; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600;"><?= $statusLabels[$ex['status']] ?? ucfirst($ex['status']) ?></span></td>
                    <td style="text-align:right;">฿<?= number_format($ex['amount'], 2) ?></td>
                    <td style="text-align:right; color:#64748b;">฿<?= number_format($ex['vat_amount'], 2) ?></td>
                    <td style="text-align:right; font-weight:600;">฿<?= number_format($ex['net_amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight:700; background:#f8fafc;">
                        <td colspan="7"><?= $isThai ? 'รวม' : 'Total' ?></td>
                        <td style="text-align:right;">฿<?= number_format($detailNet, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
