<?php
$pageTitle = 'Tour Bookings';

/**
 * Tour Bookings — List Page
 *
 * Variables: $bookings, $stats, $agents, $filters, $page, $totalPages, $totalCount, $message
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'created'   => ['✅', $isThai ? 'สร้างการจองสำเร็จ' : 'Booking created'],
    'updated'   => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Booking updated'],
    'deleted'   => ['🗑️', $isThai ? 'ลบสำเร็จ' : 'Booking deleted'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Booking not found'],
];

$statusConfig = [
    'draft'     => ['label' => $isThai ? 'ฉบับร่าง' : 'Draft',     'bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'fa-pencil'],
    'confirmed' => ['label' => $isThai ? 'ยืนยัน' : 'Confirmed',   'bg' => '#d1fae5', 'color' => '#059669', 'icon' => 'fa-check-circle'],
    'completed' => ['label' => $isThai ? 'เสร็จสิ้น' : 'Completed', 'bg' => '#dbeafe', 'color' => '#2563eb', 'icon' => 'fa-flag-checkered'],
    'cancelled' => ['label' => $isThai ? 'ยกเลิก' : 'Cancelled',   'bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'fa-ban'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* Override primary to teal for this page */
.master-data-container { --md-primary: #0d9488; --md-primary-light: #14b8a6; }

/* Filter bar */
.filter-bar {
    display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
    background: white; padding: 16px 20px; border-radius: 12px;
    border: 1px solid #e2e8f0; margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.filter-bar input, .filter-bar select {
    height: 36px; padding: 0 10px; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;
    background: #fff; transition: border-color 0.15s;
}
.filter-bar input:focus, .filter-bar select:focus {
    border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1);
}
.filter-bar input[type="text"] { min-width: 220px; }
.filter-bar input[type="date"] { width: 148px; }
.filter-bar select { min-width: 140px; -webkit-appearance: none; cursor: pointer; padding-right: 30px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.btn-filter {
    height: 36px; padding: 0 18px; background: #0d9488; color: white;
    border: none; border-radius: 8px; font-size: 13px; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s;
}
.btn-filter:hover { background: #0f766e; }
.btn-clear {
    height: 36px; padding: 0 14px; background: #f1f5f9; color: #64748b;
    border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;
    cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: background 0.15s;
}
.btn-clear:hover { background: #e2e8f0; }
.filter-active-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; background: #ef4444; color: white;
    border-radius: 50%; font-size: 10px; font-weight: 700;
}

/* Result bar */
.result-bar { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #64748b; margin-bottom: 12px; }

/* Status badge */
.status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; white-space: nowrap; }

/* Action buttons */
.row-actions { display: flex; gap: 5px; }
.row-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px; text-decoration: none; transition: all 0.15s; }
.row-btn:hover { background: #0d9488; color: white; border-color: #0d9488; }
.row-btn.danger:hover { background: #ef4444; border-color: #ef4444; color: white; }
.row-btn.payment:hover { background: #7c3aed; border-color: #7c3aed; color: white; }

/* Booking number link */
.bk-link { font-weight: 600; color: #0d9488; text-decoration: none; }
.bk-link:hover { text-decoration: underline; }

/* Master-data-table hover teal */
.master-data-table tbody tr:hover { background-color: rgba(13,148,136,0.04) !important; }

/* Docs tags */
.doc-tag { display: inline-flex; align-items: center; padding: 1px 6px; border-radius: 5px; font-size: 10px; font-weight: 700; }

/* Pagination */
.pagination { display: flex; gap: 4px; justify-content: center; margin-top: 16px; }
.pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; text-decoration: none; color: #475569; }
.pagination .active { background: #0d9488; color: white; border-color: #0d9488; }
.pagination a:hover { background: #f1f5f9; }

/* Empty state */
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; display: block; margin-bottom: 16px; }

@media (max-width: 768px) {
    .filter-bar input[type="text"] { min-width: 160px; }
}
</style>

<div class="master-data-container">

    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar-check-o"></i> <?= $isThai ? 'การจองทัวร์' : 'Tour Bookings' ?></h2>
                <p><?= $isThai ? 'จัดการการจอง, ลูกค้า, และเอกสาร' : 'Manage bookings, customers, and documents' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_list" class="btn-header btn-header-outline">
                    <i class="fa fa-users"></i> <?= $isThai ? 'ตัวแทน' : 'Agents' ?>
                </a>
                <a href="index.php?page=tour_location_list" class="btn-header btn-header-outline">
                    <i class="fa fa-map-marker"></i> <?= $isThai ? 'สถานที่' : 'Locations' ?>
                </a>
                <a href="index.php?page=tour_booking_make" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'จองใหม่' : 'New Booking' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <i class="fa fa-calendar stat-icon"></i>
            <div>
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
                <div class="stat-label"><?= $isThai ? 'การจองทั้งหมด' : 'Total Bookings' ?></div>
                <?php if ($stats['today_bookings'] > 0): ?>
                <div style="font-size:11px;color:#0d9488;margin-top:4px;font-weight:600;">
                    +<?= $stats['today_bookings'] ?> <?= $isThai ? 'วันนี้' : 'today' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-check-circle stat-icon"></i>
            <div>
                <div class="stat-value"><?= number_format($stats['confirmed']) ?></div>
                <div class="stat-label"><?= $isThai ? 'ยืนยันแล้ว' : 'Confirmed' ?></div>
            </div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-flag-checkered stat-icon"></i>
            <div>
                <div class="stat-value"><?= number_format($stats['completed']) ?></div>
                <div class="stat-label"><?= $isThai ? 'เสร็จสิ้น' : 'Completed' ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left:4px solid #0d9488;background:linear-gradient(135deg,#f0fdfa 0%,#fff 100%);">
            <i class="fa fa-money stat-icon" style="color:#0d9488;"></i>
            <div>
                <div class="stat-value" style="font-size:28px;">฿<?= number_format($stats['revenue'], 0) ?></div>
                <div class="stat-label"><?= $isThai ? 'รายได้รวม' : 'Total Revenue' ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left:4px solid #f59e0b;background:linear-gradient(135deg,#fffbeb 0%,#fff 100%);">
            <i class="fa fa-users stat-icon" style="color:#f59e0b;"></i>
            <div>
                <div class="stat-value"><?= number_format($stats['total_pax']) ?></div>
                <div class="stat-label"><?= $isThai ? 'นักท่องเที่ยวทั้งหมด' : 'Total Pax' ?></div>
            </div>
        </div>
        <div class="stat-card" style="border-left:4px solid #667eea;background:linear-gradient(135deg,#f5f3ff 0%,#fff 100%);">
            <i class="fa fa-bar-chart stat-icon" style="color:#667eea;"></i>
            <div>
                <div class="stat-value">฿<?= number_format($stats['month_revenue'], 0) ?></div>
                <div class="stat-label"><?= $isThai ? 'รายได้เดือนนี้' : 'This Month Revenue' ?></div>
                <div style="font-size:11px;color:#667eea;margin-top:4px;font-weight:600;">
                    <?= $stats['month_bookings'] ?> <?= $isThai ? 'การจอง' : 'bookings' ?> · <?= $stats['month_pax'] ?> <?= $isThai ? 'คน' : 'pax' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <?php
    $activeFilters = 0;
    if (!empty($filters['search']))    $activeFilters++;
    if (!empty($filters['status']))    $activeFilters++;
    if ($filters['agent_id'] != 0)     $activeFilters++;
    if (!empty($filters['date_from'])) $activeFilters++;
    if (!empty($filters['date_to']))   $activeFilters++;
    ?>
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="tour_booking_list">

        <div class="filter-group">
            <label><?= $isThai ? 'ค้นหา' : 'Search' ?></label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                   placeholder="<?= $isThai ? 'เลขจอง, ลูกค้า, ตัวแทน...' : 'Booking #, customer, agent...' ?>">
        </div>

        <div class="filter-group">
            <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
            <select name="status">
                <option value=""><?= $isThai ? 'ทุกสถานะ' : 'All Status' ?></option>
                <?php foreach ($statusConfig as $key => $cfg): ?>
                <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= $cfg['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><?= $isThai ? 'ตัวแทน' : 'Agent' ?></label>
            <select name="agent_id">
                <option value="0"><?= $isThai ? 'ทุกตัวแทน' : 'All Agents' ?></option>
                <option value="-1" <?= $filters['agent_id'] == -1 ? 'selected' : '' ?>><?= $isThai ? 'ลูกค้าตรง' : 'Direct' ?></option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $filters['agent_id'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name_en']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><?= $isThai ? 'วันเดินทาง ตั้งแต่' : 'Trip From' ?></label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>">
        </div>

        <div class="filter-group">
            <label><?= $isThai ? 'ถึงวันที่' : 'Trip To' ?></label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>">
        </div>

        <div style="display:flex; gap:8px; align-self:flex-end;">
            <button type="submit" class="btn-filter">
                <i class="fa fa-search"></i>
                <?= $isThai ? 'ค้นหา' : 'Search' ?>
                <?php if ($activeFilters > 0): ?>
                <span class="filter-active-badge"><?= $activeFilters ?></span>
                <?php endif; ?>
            </button>
            <a href="index.php?page=tour_booking_list" class="btn-clear">
                <i class="fa fa-refresh"></i> <?= $isThai ? 'ล้าง' : 'Clear' ?>
            </a>
        </div>
    </form>

    <!-- Result count -->
    <div class="result-bar">
        <i class="fa fa-list-ul" style="color:#0d9488;"></i>
        <?php if ($activeFilters > 0): ?>
            <?= $isThai
                ? "พบ <strong style='color:#1e293b;'>$totalCount</strong> รายการ"
                : "Found <strong style='color:#1e293b;'>$totalCount</strong> result" . ($totalCount != 1 ? 's' : '') ?>
        <?php else: ?>
            <?= $isThai
                ? "การจองทั้งหมด <strong style='color:#1e293b;'>$totalCount</strong> รายการ"
                : "<strong style='color:#1e293b;'>$totalCount</strong> booking" . ($totalCount != 1 ? 's' : '') . " total" ?>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
        <span style="color:#cbd5e1;">·</span>
        <span><?= $isThai ? "หน้า $page / $totalPages" : "Page $page of $totalPages" ?></span>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fa fa-calendar-check-o"></i>
        <?= $isThai ? 'ยังไม่มีการจอง' : 'No bookings found' ?><br>
        <?php if ($activeFilters > 0): ?>
        <a href="index.php?page=tour_booking_list" style="color:#0d9488; margin-top:12px; display:inline-block;">
            <i class="fa fa-refresh"></i> <?= $isThai ? 'ล้างตัวกรอง' : 'Clear filters' ?>
        </a>
        <?php else: ?>
        <a href="index.php?page=tour_booking_make" style="color:#0d9488; margin-top:12px; display:inline-block;">
            <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างการจองแรก' : 'Create your first booking' ?>
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php include __DIR__ . '/../partials/bulk-toolbar.php'; ?>
    <div class="master-data-table">
        <table id="tour-bookings-table">
            <thead>
                <tr>
                    <th class="bulk-col"><input type="checkbox" class="bulk-select-all" title="Select all"></th>
                    <th><?= $isThai ? 'เลขจอง' : 'Booking #' ?></th>
                    <th><?= $isThai ? 'วันที่จอง' : 'Booking Date' ?></th>
                    <th><?= $isThai ? 'วันเดินทาง' : 'Trip Date' ?></th>
                    <th><?= $isThai ? 'ลูกค้า' : 'Customer' ?></th>
                    <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'ผู้เดินทาง' : 'Pax' ?></th>
                    <th style="text-align:right;"><?= $isThai ? 'ยอดรวม' : 'Total' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th style="text-align:center;" title="<?= $isThai ? 'สถานะเช็คอินด้วยตนเอง' : 'Self-check-in status' ?>">
                        <i class="fa fa-qrcode"></i>
                    </th>
                    <th style="text-align:center;"><?= $isThai ? 'เอกสาร' : 'Docs' ?></th>
                    <th style="text-align:center;"><?= $isThai ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b):
                    $sc = $statusConfig[$b['status']] ?? $statusConfig['draft'];
                    $custName = ($isThai && !empty($b['customer_name_th']))
                        ? $b['customer_name_th']
                        : ($b['customer_name'] ?: $b['contact_name'] ?: '-');
                ?>
                <tr>
                    <td class="bulk-col"><input type="checkbox" class="bulk-select-row" value="<?= $b['id'] ?>" data-balance="<?= number_format($b['amount_due'] ?? $b['total_amount'], 2, '.', '') ?>" aria-label="Select booking <?= htmlspecialchars($b['booking_number']) ?>"></td>
                    <td>
                        <a href="index.php?page=tour_booking_view&id=<?= $b['id'] ?>" class="bk-link">
                            <?= htmlspecialchars($b['booking_number']) ?>
                        </a>
                    </td>
                    <td style="white-space:nowrap; color:#64748b; font-size:12px;">
                        <i class="fa fa-calendar" style="color:#6366f1; margin-right:3px;"></i>
                        <?= !empty($b['booking_date']) ? date('d M Y', strtotime($b['booking_date'])) : '-' ?>
                    </td>
                    <td style="white-space:nowrap; font-weight:500;">
                        <i class="fa fa-plane" style="color:#0d9488; margin-right:3px;"></i>
                        <?= date('d M Y', strtotime($b['travel_date'])) ?>
                    </td>
                    <td><?= htmlspecialchars($custName) ?></td>
                    <td style="color:#64748b;">
                        <?= htmlspecialchars($b['agent_name'] ?: '') ?>
                        <?php if (!$b['agent_name']): ?>
                        <span style="font-size:11px; color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span title="<?= $b['pax_adult'] ?>A / <?= $b['pax_child'] ?>C / <?= $b['pax_infant'] ?>I" style="font-weight:600;">
                            <i class="fa fa-users" style="color:#94a3b8; margin-right:2px;"></i><?= $b['total_pax'] ?>
                        </span>
                    </td>
                    <td style="text-align:right; font-weight:700; font-family:'JetBrains Mono',monospace;">
                        ฿<?= number_format($b['total_amount'], 0) ?>
                    </td>
                    <td style="text-align:center;">
                        <span class="status-badge" style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>;">
                            <i class="fa <?= $sc['icon'] ?>"></i> <?= $sc['label'] ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <?php if (intval($b['checkin_status'] ?? 0) === 1): ?>
                            <span title="<?= $isThai ? 'เช็คอินแล้ว: ' . ($b['checkin_at'] ? date('d M H:i', strtotime($b['checkin_at'])) : '') : 'Checked in: ' . ($b['checkin_at'] ? date('d M H:i', strtotime($b['checkin_at'])) : '') ?>"
                                  style="color:#059669;font-size:16px;">
                                <i class="fa fa-check-circle"></i>
                            </span>
                        <?php else: ?>
                            <span style="color:#e2e8f0;font-size:16px;"><i class="fa fa-circle-o"></i></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php
                        $docs = [];
                        if ($b['pr_id'])      $docs[] = '<span class="doc-tag" style="background:#ede9fe;color:#6d28d9;">PR</span>';
                        if ($b['po_id'])      $docs[] = '<span class="doc-tag" style="background:#d1fae5;color:#065f46;">PO</span>';
                        if ($b['invoice_id']) $docs[] = '<span class="doc-tag" style="background:#fef3c7;color:#92400e;">IV</span>';
                        if ($b['receipt_id']) $docs[] = '<span class="doc-tag" style="background:#dcfce7;color:#166534;">RC</span>';
                        echo $docs ? implode(' ', $docs) : '<span style="color:#cbd5e1; font-size:12px;">—</span>';
                        ?>
                    </td>
                    <td style="text-align:center;">
                        <div class="row-actions" style="justify-content:center;">
                            <a href="index.php?page=tour_booking_view&id=<?= $b['id'] ?>" class="row-btn" title="<?= $isThai ? 'ดู' : 'View' ?>"><i class="fa fa-eye"></i></a>
                            <a href="index.php?page=tour_booking_make&id=<?= $b['id'] ?>" class="row-btn" title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></a>
                            <a href="index.php?page=tour_booking_payments&booking_id=<?= $b['id'] ?>" class="row-btn payment" title="<?= $isThai ? 'จัดการชำระเงิน' : 'Manage Payments' ?>"><i class="fa fa-credit-card"></i></a>
                            <form method="post" action="index.php?page=tour_booking_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบการจองนี้?' : 'Delete this booking?' ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="row-btn danger" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="js/bulk-select.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        BulkSelect.init({
            tableId:  'tour-bookings-table',
            actions: [
                {
                    key: 'confirm',
                    label: '<?= $isThai ? 'ยืนยัน' : 'Confirm' ?>',
                    icon: 'fa-check-circle',
                    class: 'btn-success',
                    confirm: true,
                    confirmMsg: '<?= $isThai ? 'ยืนยัน {n} การจอง?' : 'Confirm {n} booking(s)?' ?>'
                },
                {
                    key: 'mark_payment',
                    label: '<?= $isThai ? 'รับชำระเงิน' : 'Mark Payment' ?>',
                    icon: 'fa-money',
                    class: 'btn-primary',
                    modal: 'payment'
                },
                {
                    key: 'send_vouchers',
                    label: '<?= $isThai ? 'ส่ง Voucher' : 'Send Vouchers' ?>',
                    icon: 'fa-paper-plane',
                    class: 'btn-info',
                    confirm: true,
                    confirmMsg: '<?= $isThai ? 'ส่ง Voucher สำหรับ {n} การจอง?' : 'Send vouchers for {n} booking(s)?' ?>'
                },
                {
                    key: 'send_invoices',
                    label: '<?= $isThai ? 'ส่ง Invoice' : 'Send Invoices' ?>',
                    icon: 'fa-file-text-o',
                    class: 'btn-warning',
                    confirm: true,
                    confirmMsg: '<?= $isThai ? 'ส่ง Invoice สำหรับ {n} การจอง?' : 'Send invoices for {n} booking(s)?' ?>'
                },
                {
                    key: 'export_csv',
                    label: '<?= $isThai ? 'ส่งออก CSV' : 'Export CSV' ?>',
                    icon: 'fa-download',
                    class: 'btn-default'
                },
                {
                    key: 'delete',
                    label: '<?= $isThai ? 'ลบ' : 'Delete' ?>',
                    icon: 'fa-trash',
                    class: 'btn-danger',
                    confirm: true,
                    confirmMsg: '<?= $isThai ? 'ลบ {n} การจอง? ไม่สามารถกู้คืนได้' : 'Delete {n} booking(s)? This cannot be undone.' ?>'
                },
            ],
            actionUrl: 'index.php?page=tour_booking_bulk',
            csrfToken: '<?= csrf_token() ?>'
        });
    });
    </script>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $baseUrl = 'index.php?page=tour_booking_list';
        foreach ($filters as $k => $v) {
            if ($v !== '' && $v !== 0 && $v !== '0') $baseUrl .= '&' . $k . '=' . urlencode($v);
        }
        ?>
        <?php if ($page > 1): ?>
        <a href="<?= $baseUrl ?>&p=<?= $page - 1 ?>">&laquo;</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= $baseUrl ?>&p=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="<?= $baseUrl ?>&p=<?= $page + 1 ?>">&raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
