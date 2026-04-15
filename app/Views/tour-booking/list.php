<?php
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
.bk-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
.bk-stat { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; text-align: center; }
.bk-stat .num { font-size: 32px; font-weight: 700; }
.bk-stat .label { font-size: 12px; color: #64748b; margin-top: 4px; }
.bk-stat .sub { font-size: 11px; color: #94a3b8; }
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
.filter-bar input, .filter-bar select { height: 36px; padding: 0 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.filter-bar input[type="text"] { min-width: 180px; }
.filter-bar input[type="date"] { width: 140px; }
.bk-table { width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.bk-table th { background: #f8fafc; padding: 12px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.bk-table td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.bk-table tr:last-child td { border-bottom: none; }
.bk-table tr:hover td { background: #f8fafc; }
.status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.bk-actions { display: flex; gap: 6px; }
.bk-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px; text-decoration: none; }
.bk-btn:hover { background: #0d9488; color: white; border-color: #0d9488; }
.bk-btn.danger:hover { background: #ef4444; border-color: #ef4444; }
.bk-number { font-weight: 600; color: #0d9488; text-decoration: none; }
.bk-number:hover { text-decoration: underline; }
.pagination { display: flex; gap: 4px; justify-content: center; margin-top: 16px; }
.pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; text-decoration: none; color: #475569; }
.pagination .active { background: #0d9488; color: white; border-color: #0d9488; }
.pagination a:hover { background: #f1f5f9; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; display: block; margin-bottom: 16px; }

@media (max-width: 768px) { .bk-stats { grid-template-columns: repeat(2, 1fr); } }
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
    <div class="bk-stats">
        <div class="bk-stat">
            <div class="num" style="color:#1e293b;"><?= $stats['total'] ?></div>
            <div class="label"><?= $isThai ? 'การจองทั้งหมด' : 'Total Bookings' ?></div>
            <div class="sub"><?= $stats['today_bookings'] ?> <?= $isThai ? 'วันนี้' : 'today' ?></div>
        </div>
        <div class="bk-stat" style="border-bottom:3px solid #10b981;">
            <div class="num" style="color:#059669;"><?= $stats['confirmed'] ?></div>
            <div class="label"><?= $isThai ? 'ยืนยันแล้ว' : 'Confirmed' ?></div>
        </div>
        <div class="bk-stat" style="border-bottom:3px solid #3b82f6;">
            <div class="num" style="color:#2563eb;"><?= $stats['completed'] ?></div>
            <div class="label"><?= $isThai ? 'เสร็จสิ้น' : 'Completed' ?></div>
        </div>
        <div class="bk-stat" style="border-bottom:3px solid #0d9488;">
            <div class="num" style="color:#0d9488;"><?= number_format($stats['revenue'], 0) ?></div>
            <div class="label"><?= $isThai ? 'รายได้ (฿)' : 'Revenue (฿)' ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="tour_booking_list">
        <input type="text" name="search" placeholder="<?= $isThai ? 'ค้นหา...' : 'Search...' ?>" value="<?= htmlspecialchars($filters['search']) ?>">
        <select name="status">
            <option value=""><?= $isThai ? 'สถานะทั้งหมด' : 'All Status' ?></option>
            <?php foreach ($statusConfig as $key => $cfg): ?>
            <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= $cfg['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="agent_id">
            <option value="0"><?= $isThai ? 'ตัวแทนทั้งหมด' : 'All Agents' ?></option>
            <?php foreach ($agents as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $filters['agent_id'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name_en']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" title="<?= $isThai ? 'ตั้งแต่' : 'From' ?>">
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" title="<?= $isThai ? 'ถึง' : 'To' ?>">
        <button type="submit" class="bk-btn" style="width:auto; padding:0 14px; height:36px;"><i class="fa fa-search"></i></button>
        <?php if (!empty($filters['search']) || !empty($filters['status']) || $filters['agent_id'] > 0 || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
        <a href="index.php?page=tour_booking_list" class="bk-btn" style="width:auto; padding:0 14px; height:36px;"><i class="fa fa-times"></i></a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fa fa-calendar-check-o"></i>
        <?= $isThai ? 'ยังไม่มีการจอง' : 'No bookings yet' ?><br>
        <a href="index.php?page=tour_booking_make" style="color:#0d9488; margin-top:12px; display:inline-block;">
            <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างการจองแรก' : 'Create your first booking' ?>
        </a>
    </div>
    <?php else: ?>
    <table class="bk-table">
        <thead>
            <tr>
                <th><?= $isThai ? 'เลขจอง' : 'Booking #' ?></th>
                <th><?= $isThai ? 'วันที่จอง' : 'Booking Date' ?></th>
                <th><?= $isThai ? 'วันเดินทาง' : 'Trip Date' ?></th>
                <th><?= $isThai ? 'ลูกค้า' : 'Customer' ?></th>
                <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                <th style="text-align:center;"><?= $isThai ? 'ผู้เดินทาง' : 'Pax' ?></th>
                <th style="text-align:right;"><?= $isThai ? 'ยอดรวม' : 'Total' ?></th>
                <th style="text-align:center;"><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                <th style="text-align:center;"><?= $isThai ? 'เอกสาร' : 'Docs' ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b):
                $sc = $statusConfig[$b['status']] ?? $statusConfig['draft'];
                $custName = ($isThai && !empty($b['customer_name_th'])) ? $b['customer_name_th'] : ($b['customer_name'] ?: '-');
            ?>
            <tr>
                <td>
                    <a href="index.php?page=tour_booking_view&id=<?= $b['id'] ?>" class="bk-number"><?= htmlspecialchars($b['booking_number']) ?></a>
                </td>
                <td>
                    <i class="fa fa-calendar" style="color:#6366f1;"></i>
                    <?= !empty($b['booking_date']) ? date('d M Y', strtotime($b['booking_date'])) : '-' ?>
                </td>
                <td>
                    <i class="fa fa-plane" style="color:#0d9488;"></i>
                    <?= date('d M Y', strtotime($b['travel_date'])) ?>
                </td>
                <td><?= htmlspecialchars($custName) ?></td>
                <td><?= htmlspecialchars($b['agent_name'] ?: '-') ?></td>
                <td style="text-align:center;">
                    <span title="<?= $b['pax_adult'] ?>A / <?= $b['pax_child'] ?>C / <?= $b['pax_infant'] ?>I">
                        <i class="fa fa-users" style="color:#94a3b8;"></i> <?= $b['total_pax'] ?>
                    </span>
                </td>
                <td style="text-align:right; font-weight:600;">
                    ฿<?= number_format($b['total_amount'], 2) ?>
                </td>
                <td style="text-align:center;">
                    <span class="status-badge" style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>;">
                        <i class="fa <?= $sc['icon'] ?>"></i> <?= $sc['label'] ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <?php
                    $docs = [];
                    if ($b['pr_id'])       $docs[] = '<span title="PR" style="color:#6366f1;">PR</span>';
                    if ($b['po_id'])       $docs[] = '<span title="PO" style="color:#0d9488;">PO</span>';
                    if ($b['invoice_id'])  $docs[] = '<span title="IV" style="color:#f59e0b;">IV</span>';
                    if ($b['receipt_id'])  $docs[] = '<span title="RC" style="color:#10b981;">RC</span>';
                    echo $docs ? implode(' ', $docs) : '<span style="color:#cbd5e1;">-</span>';
                    ?>
                </td>
                <td>
                    <div class="bk-actions">
                        <a href="index.php?page=tour_booking_view&id=<?= $b['id'] ?>" class="bk-btn" title="<?= $isThai ? 'ดู' : 'View' ?>"><i class="fa fa-eye"></i></a>
                        <a href="index.php?page=tour_booking_make&id=<?= $b['id'] ?>" class="bk-btn" title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></a>
                        <form method="post" action="index.php?page=tour_booking_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบการจองนี้?' : 'Delete this booking?' ?>')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="bk-btn danger" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

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
