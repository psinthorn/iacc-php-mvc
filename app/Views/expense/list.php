<?php
/**
 * Expense List — Modern UI
 * 
 * Uses master-data.css design system
 * Variables from controller: $expenses, $summary, $categories, $filters, $message, $lang
 */

$isThai = ($lang ?? '2') === '1';

$statusLabels = [
    'draft'     => $isThai ? 'ฉบับร่าง' : 'Draft',
    'pending'   => $isThai ? 'รอดำเนินการ' : 'Pending',
    'approved'  => $isThai ? 'อนุมัติแล้ว' : 'Approved',
    'paid'      => $isThai ? 'จ่ายแล้ว' : 'Paid',
    'rejected'  => $isThai ? 'ปฏิเสธ' : 'Rejected',
    'cancelled' => $isThai ? 'ยกเลิก' : 'Cancelled',
];
$statusColors = [
    'draft' => '#94a3b8', 'pending' => '#f59e0b', 'approved' => '#3b82f6',
    'paid' => '#10b981', 'rejected' => '#ef4444', 'cancelled' => '#6b7280',
];

$messages = [
    'created' => ['✅', $isThai ? 'สร้างค่าใช้จ่ายสำเร็จ' : 'Expense created successfully'],
    'updated' => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Expense updated successfully'],
    'deleted' => ['🗑️', $isThai ? 'ลบสำเร็จ' : 'Expense deleted'],
    'status_updated' => ['✅', $isThai ? 'อัพเดทสถานะสำเร็จ' : 'Status updated'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Expense not found'],
    'error' => ['❌', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* Header layout — extends master-data-header */
.master-data-header .header-content { display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:12px; }
.master-data-header .header-text h2 { margin:0; }
.master-data-header .header-text p { margin:4px 0 0; font-size:14px; opacity:0.85; }
.master-data-header .header-actions { display:flex; gap:8px; flex-wrap:wrap; }
.btn-header { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.2s; border:none; cursor:pointer; }
.btn-header-primary { background:rgba(255,255,255,0.2); color:white; border:1px solid rgba(255,255,255,0.3); }
.btn-header-primary:hover { background:rgba(255,255,255,0.3); color:white; text-decoration:none; }
.btn-header-outline { background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.9); border:1px solid rgba(255,255,255,0.2); }
.btn-header-outline:hover { background:rgba(255,255,255,0.2); color:white; text-decoration:none; }

.filter-bar {
    display: flex; flex-wrap: wrap; gap: 12px; align-items: end;
    background: white; padding: 16px 20px; border-radius: 12px;
    border: 1px solid var(--md-border, #e2e8f0); margin-bottom: 20px;
}
.filter-bar .filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-bar label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-bar input, .filter-bar select {
    padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 13px; min-width: 140px; outline: none;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--md-primary, #4f46e5); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
.filter-bar .btn-filter {
    padding: 8px 20px; background: var(--md-primary, #4f46e5); color: white;
    border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.filter-bar .btn-clear { padding: 8px 16px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; }

.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
    color: white; white-space: nowrap;
}
.amount-cell { font-family: 'JetBrains Mono', monospace; text-align: right; font-weight: 600; }
.vendor-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.category-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 8px; font-size: 11px; font-weight: 600;
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-money"></i> <?= $isThai ? 'รายการค่าใช้จ่าย' : 'Expense Management' ?></h2>
                <p><?= $isThai ? 'จัดการค่าใช้จ่ายทั้งหมดของบริษัท' : 'Track and manage all company expenses' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=expense_export&format=csv&date_from=<?= $filters['date_from'] ?? '' ?>&date_to=<?= $filters['date_to'] ?? '' ?>&status=<?= $filters['status'] ?? '' ?>&category_id=<?= $filters['category_id'] ?? '' ?>" class="btn-header btn-header-outline" style="color:#10b981;">
                    <i class="fa fa-download"></i> <?= $isThai ? 'ส่งออก CSV' : 'Export CSV' ?>
                </a>
                <a href="index.php?page=expense_project_report" class="btn-header btn-header-outline">
                    <i class="fa fa-folder-open"></i> <?= $isThai ? 'โปรเจกต์' : 'Projects' ?>
                </a>
                <a href="index.php?page=expense_summary" class="btn-header btn-header-outline">
                    <i class="fa fa-bar-chart"></i> <?= $isThai ? 'สรุป' : 'Summary' ?>
                </a>
                <a href="index.php?page=expense_form" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างค่าใช้จ่าย' : 'New Expense' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div class="alert-modern" style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 12px 20px; border-radius: 0 8px 8px 0; margin-bottom: 16px; font-size: 14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: #ede9fe;"><i class="fa fa-file-text-o" style="color: #7c3aed;"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($summary['total_count'] ?? 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'รายการทั้งหมด' : 'Total Expenses' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dbeafe;"><i class="fa fa-money" style="color: #3b82f6;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($summary['total_net'] ?? 0, 2) ?></span>
                <span class="stat-label"><?= $isThai ? 'ยอดรวมสุทธิ' : 'Total Net Amount' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fef3c7;"><i class="fa fa-clock-o" style="color: #f59e0b;"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($summary['pending_count'] ?? 0) ?></span>
                <span class="stat-label"><?= $isThai ? 'รออนุมัติ' : 'Pending' ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #dcfce7;"><i class="fa fa-check-circle" style="color: #10b981;"></i></div>
            <div class="stat-info">
                <span class="stat-value">฿<?= number_format($summary['paid_amount'] ?? 0, 2) ?></span>
                <span class="stat-label"><?= $isThai ? 'จ่ายแล้ว' : 'Total Paid' ?></span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="expense_list">
        <div class="filter-group">
            <label><?= $isThai ? 'ค้นหา' : 'Search' ?></label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= $isThai ? 'ชื่อ, เลขที่, ผู้ขาย...' : 'Title, number, vendor...' ?>">
        </div>
        <div class="filter-group">
            <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
            <select name="status">
                <option value=""><?= $isThai ? 'ทั้งหมด' : 'All' ?></option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><?= $isThai ? 'หมวดหมู่' : 'Category' ?></label>
            <select name="category_id">
                <option value=""><?= $isThai ? 'ทั้งหมด' : 'All' ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($isThai && $cat['name_th'] ? $cat['name_th'] : $cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label><?= $isThai ? 'จากวันที่' : 'From' ?></label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
        </div>
        <div class="filter-group">
            <label><?= $isThai ? 'ถึงวันที่' : 'To' ?></label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-filter"><i class="fa fa-search"></i> <?= $isThai ? 'ค้นหา' : 'Filter' ?></button>
        <a href="index.php?page=expense_list" class="btn-clear"><i class="fa fa-refresh"></i> <?= $isThai ? 'ล้าง' : 'Clear' ?></a>
    </form>

    <!-- Expense Table -->
    <div class="master-data-table-wrapper">
        <table class="master-data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $isThai ? 'เลขที่' : 'Number' ?></th>
                    <th><?= $isThai ? 'วันที่' : 'Date' ?></th>
                    <th><?= $isThai ? 'รายการ' : 'Title' ?></th>
                    <th><?= $isThai ? 'หมวดหมู่' : 'Category' ?></th>
                    <th><?= $isThai ? 'ผู้ขาย' : 'Vendor' ?></th>
                    <th style="text-align:right"><?= $isThai ? 'จำนวนเงิน' : 'Amount' ?></th>
                    <th style="text-align:right"><?= $isThai ? 'สุทธิ' : 'Net' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th style="text-align:center"><?= $isThai ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="10" style="text-align:center; padding:40px;">
                        <div style="color:#94a3b8;">
                            <i class="fa fa-inbox" style="font-size:36px; display:block; margin-bottom:12px;"></i>
                            <?= $isThai ? 'ไม่พบรายการค่าใช้จ่าย' : 'No expenses found' ?>
                            <br><a href="index.php?page=expense_form" style="color:var(--md-primary,#4f46e5); font-weight:600; margin-top:8px; display:inline-block;">
                                <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างรายการใหม่' : 'Create new expense' ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($expenses as $i => $exp): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><a href="index.php?page=expense_view&id=<?= $exp['id'] ?>" style="color:var(--md-primary,#4f46e5); font-weight:600;"><?= htmlspecialchars($exp['expense_number'] ?? '-') ?></a></td>
                    <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($exp['expense_date'])) ?></td>
                    <td><?= htmlspecialchars($exp['title']) ?></td>
                    <td>
                        <?php if ($exp['category_name']): ?>
                        <span class="category-badge" style="background: <?= $exp['category_color'] ?? '#6366f1' ?>22; color: <?= $exp['category_color'] ?? '#6366f1' ?>;">
                            <i class="fa <?= $exp['category_icon'] ?? 'fa-folder' ?>"></i>
                            <?= htmlspecialchars($isThai && $exp['category_name_th'] ? $exp['category_name_th'] : $exp['category_name']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="vendor-cell" title="<?= htmlspecialchars($exp['vendor_name'] ?? '') ?>"><?= htmlspecialchars($exp['vendor_name'] ?? '-') ?></td>
                    <td class="amount-cell"><?= number_format($exp['amount'], 2) ?></td>
                    <td class="amount-cell" style="color: <?= $exp['net_amount'] > 0 ? '#059669' : '#64748b' ?>;">฿<?= number_format($exp['net_amount'], 2) ?></td>
                    <td>
                        <span class="status-pill" style="background: <?= $statusColors[$exp['status']] ?? '#94a3b8' ?>;">
                            <?= $statusLabels[$exp['status']] ?? $exp['status'] ?>
                        </span>
                    </td>
                    <td style="text-align:center; white-space:nowrap;">
                        <a href="index.php?page=expense_view&id=<?= $exp['id'] ?>" class="btn-action" title="View"><i class="fa fa-eye"></i></a>
                        <?php if (in_array($exp['status'], ['draft', 'pending'])): ?>
                        <a href="index.php?page=expense_form&id=<?= $exp['id'] ?>" class="btn-action" title="Edit"><i class="fa fa-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($exp['status'] === 'draft'): ?>
                        <form method="post" action="index.php?page=expense_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ยืนยันการลบ?' : 'Delete this expense?' ?>')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                            <button type="submit" class="btn-action btn-action-danger" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary Footer -->
    <?php if (!empty($expenses)): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding:12px 20px; background:white; border-radius:10px; border:1px solid var(--md-border,#e2e8f0); font-size:13px; color:#64748b;">
        <span><?= $isThai ? 'แสดง' : 'Showing' ?> <?= count($expenses) ?> <?= $isThai ? 'รายการ' : 'expenses' ?></span>
        <span style="font-weight:600; color:#1e293b;">
            <?= $isThai ? 'รวมสุทธิ' : 'Total Net' ?>: 
            <span style="color:#059669; font-family:'JetBrains Mono',monospace;">฿<?= number_format(array_sum(array_column($expenses, 'net_amount')), 2) ?></span>
        </span>
    </div>
    <?php endif; ?>
</div>

<style>
.btn-action { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:8px; border:1px solid #e2e8f0; background:white; color:#64748b; text-decoration:none; font-size:13px; transition:all 0.2s; cursor:pointer; margin:0 2px; }
.btn-action:hover { background:var(--md-primary,#4f46e5); color:white; border-color:var(--md-primary,#4f46e5); }
.btn-action-danger:hover { background:#ef4444; border-color:#ef4444; }
</style>
