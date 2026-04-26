<?php
$pageTitle = 'Fleet Management';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'fleet_created'  => ['#10b981', $isThai ? 'สร้างฟลีทสำเร็จ' : 'Fleet created'],
    'fleet_updated'  => ['#10b981', $isThai ? 'อัพเดทฟลีทสำเร็จ' : 'Fleet updated'],
    'fleet_deleted'  => ['#f59e0b', $isThai ? 'ลบฟลีทสำเร็จ' : 'Fleet deleted'],
    'name_required'  => ['#ef4444', $isThai ? 'กรุณาระบุชื่อฟลีท' : 'Fleet name is required'],
];

$typeLabels = \App\Models\TourAllotment::getFleetTypeLabels($isThai);
$edit = $editFleet ?? null;
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.fleet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.fleet-card { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: all 0.25s ease; }
.fleet-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.fleet-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.fleet-icon { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #0d9488, #14b8a6); display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
.fleet-header h3 { margin: 0; font-size: 15px; font-weight: 600; }
.fleet-meta { display: flex; gap: 16px; font-size: 13px; color: #64748b; margin-bottom: 12px; }
.fleet-meta span i { margin-right: 4px; }
.fleet-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; }
.fleet-badge.active { background: #dcfce7; color: #16a34a; }
.fleet-badge.inactive { background: #fee2e2; color: #dc2626; }
.fleet-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #f1f5f9; justify-content: flex-end; }
.fleet-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; text-decoration: none; }
.fleet-btn:hover { background: #0d9488; color: white; border-color: #0d9488; }
.fleet-btn.danger:hover { background: #ef4444; border-color: #ef4444; }

.fleet-form { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
.fleet-form h3 { margin: 0 0 16px; font-size: 16px; font-weight: 600; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 12px; font-weight: 600; color: #475569; }
.form-group input, .form-group select, .form-group textarea { height: 38px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.form-group textarea { height: 60px; padding: 8px 12px; resize: vertical; }
.form-group .toggle { display: flex; align-items: center; gap: 8px; height: 38px; }
.form-actions { display: flex; gap: 10px; margin-top: 16px; }
.btn-save { padding: 10px 24px; background: #0d9488; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; }
.btn-save:hover { background: #0f766e; }
.btn-cancel { padding: 10px 24px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; }
.btn-cancel:hover { background: #e2e8f0; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; display: block; margin-bottom: 16px; }

@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-ship"></i> <?= $isThai ? 'จัดการฟลีท' : 'Fleet Management' ?></h2>
                <p><?= $isThai ? 'จัดการเรือ/ยานพาหนะ และจำนวนที่นั่ง' : 'Manage boats/vehicles and seat capacity' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_allotment_list" class="btn-header btn-header-outline">
                    <i class="fa fa-calendar"></i> <?= $isThai ? 'ดูที่นั่ง' : 'Allotments' ?>
                </a>
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'การจอง' : 'Bookings' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($msg) && isset($messages[$msg])): ?>
    <div style="background:<?= $messages[$msg][0] === '#ef4444' ? '#fef2f2' : '#f0fdf4' ?>; border-left:4px solid <?= $messages[$msg][0] ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$msg][1] ?>
    </div>
    <?php endif; ?>

    <!-- Form (Create / Edit) -->
    <div class="fleet-form">
        <h3><i class="fa fa-<?= $edit ? 'pencil' : 'plus' ?>"></i> <?= $edit ? ($isThai ? 'แก้ไขฟลีท' : 'Edit Fleet') : ($isThai ? 'เพิ่มฟลีทใหม่' : 'Add New Fleet') ?></h3>
        <form method="POST" action="index.php?page=tour_fleet_store">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= intval($edit['id']) ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label><?= $isThai ? 'ชื่อฟลีท' : 'Fleet Name' ?> *</label>
                    <input type="text" name="fleet_name" value="<?= htmlspecialchars($edit['fleet_name'] ?? '') ?>" placeholder="<?= $isThai ? 'เช่น Speedboat 1' : 'e.g. Speedboat 1' ?>" required>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'ประเภท' : 'Type' ?></label>
                    <select name="fleet_type">
                        <?php foreach ($typeLabels as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($edit['fleet_type'] ?? 'speedboat') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'จำนวนที่นั่ง' : 'Seats per Unit' ?></label>
                    <input type="number" name="capacity" value="<?= intval($edit['capacity'] ?? 38) ?>" min="1" max="999">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'จำนวนลำ' : 'Unit Count' ?></label>
                    <input type="number" name="unit_count" value="<?= intval($edit['unit_count'] ?? 1) ?>" min="1" max="99">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                    <select name="is_active">
                        <option value="1" <?= ($edit['is_active'] ?? 1) ? 'selected' : '' ?>><?= $isThai ? 'ใช้งาน' : 'Active' ?></option>
                        <option value="0" <?= isset($edit['is_active']) && !$edit['is_active'] ? 'selected' : '' ?>><?= $isThai ? 'ไม่ใช้งาน' : 'Inactive' ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></label>
                    <input type="text" name="notes" value="<?= htmlspecialchars($edit['notes'] ?? '') ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save"><i class="fa fa-check"></i> <?= $edit ? ($isThai ? 'บันทึก' : 'Save') : ($isThai ? 'สร้าง' : 'Create') ?></button>
                <?php if ($edit): ?>
                <a href="index.php?page=tour_fleet_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Fleet List -->
    <?php if (empty($fleets)): ?>
    <div class="empty-state">
        <i class="fa fa-ship"></i>
        <p><?= $isThai ? 'ยังไม่มีฟลีท — เพิ่มเรือ/ยานพาหนะแรกของคุณ' : 'No fleets yet — add your first boat or vehicle above' ?></p>
    </div>
    <?php else: ?>
    <div class="fleet-grid">
        <?php foreach ($fleets as $f):
            $type = $f['fleet_type'] ?? 'speedboat';
            $totalSeats = intval($f['capacity']) * intval($f['unit_count']);
            $isActive = intval($f['is_active']);
        ?>
        <div class="fleet-card">
            <div class="fleet-header">
                <div class="fleet-icon"><i class="fa fa-ship"></i></div>
                <div>
                    <h3><?= htmlspecialchars($f['fleet_name']) ?></h3>
                    <span class="fleet-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? ($isThai ? 'ใช้งาน' : 'Active') : ($isThai ? 'ไม่ใช้งาน' : 'Inactive') ?></span>
                </div>
            </div>
            <div class="fleet-meta">
                <span><i class="fa fa-tag"></i> <?= htmlspecialchars($typeLabels[$type] ?? $type) ?></span>
                <span><i class="fa fa-users"></i> <?= $totalSeats ?> <?= $isThai ? 'ที่นั่ง' : 'seats' ?></span>
                <?php if (intval($f['unit_count']) > 1): ?>
                <span><i class="fa fa-clone"></i> <?= intval($f['unit_count']) ?> <?= $isThai ? 'ลำ' : 'units' ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($f['notes'])): ?>
            <div style="font-size:12px;color:#94a3b8;margin-bottom:8px;"><?= htmlspecialchars($f['notes']) ?></div>
            <?php endif; ?>
            <div class="fleet-actions">
                <a href="index.php?page=tour_fleet_make&id=<?= intval($f['id']) ?>" class="fleet-btn" title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></a>
                <form method="POST" action="index.php?page=tour_fleet_delete" style="margin:0;" onsubmit="return confirm('<?= $isThai ? 'ยืนยันลบฟลีทนี้?' : 'Delete this fleet?' ?>')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="id" value="<?= intval($f['id']) ?>">
                    <button type="submit" class="fleet-btn danger" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
