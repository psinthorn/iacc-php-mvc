<?php
$pageTitle = 'Tour Locations';

/**
 * Tour Locations — List Page
 * 
 * Variables from controller: $locations, $stats, $filters, $message
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'created'   => ['✅', $isThai ? 'สร้างสถานที่สำเร็จ' : 'Location created'],
    'updated'   => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Location updated'],
    'deleted'   => ['🗑️', $isThai ? 'ลบสำเร็จ' : 'Location deleted'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Location not found'],
];

$typeLabels = [
    'pickup'   => ['fa-car',        '#0d9488', $isThai ? 'จุดรับ' : 'Pickup'],
    'dropoff'  => ['fa-flag-checkered', '#6366f1', $isThai ? 'จุดส่ง' : 'Dropoff'],
    'activity' => ['fa-sun-o',      '#f59e0b', $isThai ? 'กิจกรรม' : 'Activity'],
    'hotel'    => ['fa-bed',        '#ec4899', $isThai ? 'โรงแรม' : 'Hotel'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.stat-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
.stat-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px; text-align: center; }
.stat-card .num { font-size: 28px; font-weight: 700; color: #1e293b; }
.stat-card .label { font-size: 12px; color: #64748b; margin-top: 4px; }
.loc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.loc-card { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: all 0.25s ease; }
.loc-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.loc-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.loc-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.loc-header h3 { margin: 0; font-size: 15px; }
.loc-type-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; }
.loc-address { font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.5; }
.loc-actions { display: flex; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; justify-content: flex-end; }
.loc-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; text-decoration: none; }
.loc-btn:hover { background: #0d9488; color: white; border-color: #0d9488; }
.loc-btn.danger:hover { background: #ef4444; border-color: #ef4444; }
.filter-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
.filter-bar input, .filter-bar select { height: 38px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.filter-bar input[type="text"] { min-width: 220px; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; display: block; margin-bottom: 16px; }

@media (max-width: 768px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-map-marker"></i> <?= $xml->tourlocations ?? ($isThai ? 'สถานที่' : 'Locations') ?></h2>
                <p><?= $isThai ? 'จัดการจุดรับ, จุดส่ง, โรงแรม และสถานที่กิจกรรม' : 'Manage pickup, dropoff, hotel, and activity locations' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'การจอง' : 'Bookings' ?>
                </a>
                <a href="index.php?page=tour_location_make" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มสถานที่' : 'Add Location' ?>
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
    <div class="stat-cards">
        <div class="stat-card">
            <div class="num"><?= $stats['total'] ?? 0 ?></div>
            <div class="label"><?= $isThai ? 'ทั้งหมด' : 'Total' ?></div>
        </div>
        <?php foreach ($typeLabels as $type => $info): ?>
        <div class="stat-card" style="border-bottom: 3px solid <?= $info[1] ?>;">
            <div class="num" style="color:<?= $info[1] ?>;"><?= $stats[$type] ?? 0 ?></div>
            <div class="label"><i class="fa <?= $info[0] ?>"></i> <?= $info[2] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="tour_location_list">
        <input type="text" name="search" placeholder="<?= $isThai ? 'ค้นหาชื่อ, ที่อยู่...' : 'Search name, address...' ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        <select name="location_type">
            <option value=""><?= $isThai ? 'ประเภททั้งหมด' : 'All Types' ?></option>
            <?php foreach ($typeLabels as $type => $info): ?>
            <option value="<?= $type ?>" <?= ($filters['location_type'] ?? '') === $type ? 'selected' : '' ?>><?= $info[2] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="loc-btn" style="width:auto; padding:0 16px; height:38px;"><i class="fa fa-search"></i></button>
        <?php if (!empty($filters['search']) || !empty($filters['location_type'])): ?>
        <a href="index.php?page=tour_location_list" class="loc-btn" style="width:auto; padding:0 16px; height:38px; font-size:12px;"><i class="fa fa-times"></i></a>
        <?php endif; ?>
    </form>

    <!-- Location Cards -->
    <?php if (empty($locations)): ?>
    <div class="empty-state">
        <i class="fa fa-map-marker"></i>
        <?= $isThai ? 'ยังไม่มีสถานที่' : 'No locations yet' ?><br>
        <a href="index.php?page=tour_location_make" style="color:#0d9488; margin-top:12px; display:inline-block;">
            <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มสถานที่แรก' : 'Add your first location' ?>
        </a>
    </div>
    <?php else: ?>
    <div class="loc-grid">
        <?php foreach ($locations as $loc):
            $t = $typeLabels[$loc['location_type']] ?? ['fa-map-pin', '#64748b', $loc['location_type']];
        ?>
        <div class="loc-card" style="border-left: 4px solid <?= $t[1] ?>;">
            <div class="loc-header">
                <div class="loc-icon" style="background: <?= $t[1] ?>22; color: <?= $t[1] ?>;">
                    <i class="fa <?= $t[0] ?>"></i>
                </div>
                <div>
                    <h3><?= htmlspecialchars($loc['name']) ?></h3>
                    <span class="loc-type-badge" style="background: <?= $t[1] ?>18; color: <?= $t[1] ?>;"><?= $t[2] ?></span>
                </div>
            </div>
            <?php if ($loc['address']): ?>
            <div class="loc-address"><i class="fa fa-map-o"></i> <?= nl2br(htmlspecialchars($loc['address'])) ?></div>
            <?php endif; ?>
            <?php if ($loc['notes']): ?>
            <div class="loc-address"><i class="fa fa-sticky-note-o"></i> <?= htmlspecialchars($loc['notes']) ?></div>
            <?php endif; ?>
            <div class="loc-actions">
                <a href="index.php?page=tour_location_make&id=<?= $loc['id'] ?>" class="loc-btn" title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></a>
                <form method="post" action="index.php?page=tour_location_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบสถานที่นี้?' : 'Delete this location?' ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                    <button type="submit" class="loc-btn danger" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
