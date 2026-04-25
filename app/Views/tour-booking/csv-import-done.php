<?php
$pageTitle = 'Import Tour Bookings — Complete';
$inserted = $result['inserted'] ?? 0;
$failed   = $result['failed']   ?? 0;
$errors   = $result['errors']   ?? [];
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid" style="padding:24px 20px;">
    <div style="max-width:600px; margin:0 auto; text-align:center; padding:40px 20px;">

        <?php if ($inserted > 0): ?>
        <div style="width:80px; height:80px; background:linear-gradient(135deg,#0d9488,#0f766e); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:36px; color:white;">
            <i class="fa fa-check"></i>
        </div>
        <h3 style="margin:0 0 8px; color:#0f766e;">Import Complete</h3>
        <p style="color:#64748b; font-size:15px;">
            <strong><?= $inserted ?></strong> booking<?= $inserted !== 1 ? 's' : '' ?> created successfully.
            <?php if ($failed > 0): ?>
            <strong class="text-danger"><?= $failed ?></strong> failed.
            <?php endif; ?>
        </p>
        <?php else: ?>
        <div style="width:80px; height:80px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:36px; color:#dc2626;">
            <i class="fa fa-times"></i>
        </div>
        <h3 style="margin:0 0 8px; color:#dc2626;">Import Failed</h3>
        <p style="color:#64748b;">No bookings were created.</p>
        <?php endif; ?>

        <?php if ($errors): ?>
        <div class="alert alert-warning" style="text-align:left; margin:20px 0; border-radius:10px;">
            <strong><i class="fa fa-exclamation-triangle"></i> Errors:</strong>
            <ul style="margin:8px 0 0; padding-left:20px;">
            <?php foreach ($errors as $err): ?>
                <li style="font-size:13px;"><?= $e($err) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; justify-content:center; margin-top:24px; flex-wrap:wrap;">
            <a href="index.php?page=tour_booking" class="btn btn-primary" style="border-radius:8px; padding:10px 24px;">
                <i class="fa fa-list"></i> View All Bookings
            </a>
            <a href="index.php?page=tour_booking_csv_import" class="btn btn-default" style="border-radius:8px; padding:10px 24px;">
                <i class="fa fa-upload"></i> Import Another File
            </a>
        </div>
    </div>
</div>
