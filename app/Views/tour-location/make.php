<?php
/**
 * Tour Location — Create / Edit Form
 * 
 * Variables from controller: $location (null = create), $message
 */

$isThai    = ($_SESSION['lang'] ?? '0') === '1';
$isEdit    = !empty($location);
$pageTitle = $isEdit
    ? ($isThai ? 'แก้ไขสถานที่' : 'Edit Location')
    : ($isThai ? 'เพิ่มสถานที่' : 'Add Location');

$typeLabels = [
    'pickup'   => ['fa-car',        '#0d9488', $isThai ? 'จุดรับ' : 'Pickup'],
    'dropoff'  => ['fa-flag-checkered', '#6366f1', $isThai ? 'จุดส่ง' : 'Dropoff'],
    'activity' => ['fa-sun-o',      '#f59e0b', $isThai ? 'กิจกรรม' : 'Activity'],
    'hotel'    => ['fa-bed',        '#ec4899', $isThai ? 'โรงแรม' : 'Hotel'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.loc-form-card { background: white; border-radius: 14px; padding: 28px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); max-width: 640px; margin: 0 auto; }
.loc-form-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 20px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.loc-form-group { margin-bottom: 18px; }
.loc-form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
.loc-form-group label .required { color: #ef4444; }
.loc-form-group input,
.loc-form-group select,
.loc-form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
.loc-form-group input:focus,
.loc-form-group select:focus,
.loc-form-group textarea:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
.loc-form-group textarea { resize: vertical; min-height: 80px; }
.loc-type-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.loc-type-radio { display: none; }
.loc-type-label { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-size: 14px; }
.loc-type-label:hover { border-color: #cbd5e1; }
.loc-type-radio:checked + .loc-type-label { border-color: var(--type-color); background: color-mix(in srgb, var(--type-color) 6%, white); }
.loc-type-label .icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 28px; padding-top: 18px; border-top: 1px solid #f1f5f9; }
.btn-save { padding: 10px 28px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: #0f766e; }
.btn-cancel { padding: 10px 28px; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.btn-cancel:hover { background: #f8fafc; color: #475569; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-map-marker"></i> <?= $pageTitle ?></h2>
                <p><?= $isThai ? 'กำหนดรายละเอียดสถานที่' : 'Set location details' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_location_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message)): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        ⚠️ <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="post" action="index.php?page=tour_location_store">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $location['id'] ?>">
        <?php endif; ?>

        <div class="loc-form-card">
            <h3><i class="fa fa-info-circle" style="color:#0d9488;"></i> <?= $isThai ? 'ข้อมูลสถานที่' : 'Location Details' ?></h3>

            <!-- Name -->
            <div class="loc-form-group">
                <label><?= $isThai ? 'ชื่อสถานที่' : 'Location Name' ?> <span class="required">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($location['name'] ?? '') ?>" placeholder="<?= $isThai ? 'เช่น สนามบินสุวรรณภูมิ' : 'e.g. Suvarnabhumi Airport' ?>" required>
            </div>

            <!-- Type -->
            <div class="loc-form-group">
                <label><?= $isThai ? 'ประเภท' : 'Type' ?> <span class="required">*</span></label>
                <div class="loc-type-options">
                    <?php foreach ($typeLabels as $type => $info): ?>
                    <input type="radio" class="loc-type-radio" name="location_type" id="type_<?= $type ?>" value="<?= $type ?>"
                        <?= ($location['location_type'] ?? 'pickup') === $type ? 'checked' : '' ?>>
                    <label for="type_<?= $type ?>" class="loc-type-label" style="--type-color: <?= $info[1] ?>;">
                        <span class="icon" style="background: <?= $info[1] ?>22; color: <?= $info[1] ?>;"><i class="fa <?= $info[0] ?>"></i></span>
                        <?= $info[2] ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Address -->
            <div class="loc-form-group">
                <label><?= $isThai ? 'ที่อยู่ / รายละเอียด' : 'Address / Details' ?></label>
                <textarea name="address" placeholder="<?= $isThai ? 'ที่อยู่เต็ม หรือคำอธิบายตำแหน่ง' : 'Full address or location description' ?>"><?= htmlspecialchars($location['address'] ?? '') ?></textarea>
            </div>

            <!-- Notes -->
            <div class="loc-form-group">
                <label><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></label>
                <textarea name="notes" rows="3" placeholder="<?= $isThai ? 'หมายเหตุเพิ่มเติม' : 'Additional notes' ?>"><?= htmlspecialchars($location['notes'] ?? '') ?></textarea>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="index.php?page=tour_location_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
                <button type="submit" class="btn-save">
                    <i class="fa fa-check"></i> <?= $isEdit ? ($isThai ? 'บันทึก' : 'Save') : ($isThai ? 'สร้าง' : 'Create') ?>
                </button>
            </div>
        </div>
    </form>
</div>
