<?php
$pageTitle = 'Import Tour Bookings — Preview';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');

$valid   = $parsed['valid']   ?? [];
$invalid = $parsed['invalid'] ?? [];
$total   = count($valid) + count($invalid);
?>
<style>
.preview-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.preview-header { padding:20px 24px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.stat-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.stat-pill.ok  { background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; }
.stat-pill.err { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.stat-pill.tot { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.preview-section { padding:0 24px 24px; }
.preview-section h5 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; margin:20px 0 10px; }
.preview-section h5.ok  { color:#0f766e; }
.preview-section h5.err { color:#dc2626; }
.tbl { width:100%; border-collapse:collapse; font-size:13px; }
.tbl th { background:#f8fafc; padding:8px 10px; text-align:left; font-weight:600; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid #e2e8f0; }
.tbl td { padding:8px 10px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:top; }
.tbl tr:last-child td { border-bottom:none; }
.tbl tr.err-row td { background:#fef9f9; }
.err-list { margin:0; padding:0 0 0 16px; color:#dc2626; font-size:12px; }
.footer-bar { background:#f8fafc; padding:16px 24px; display:flex; align-items:center; gap:12px; border-top:1px solid #e2e8f0; flex-wrap:wrap; }
</style>

<div class="container-fluid" style="padding:24px 20px;">
    <div class="page-header" style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h2 style="margin:0;"><i class="fa fa-eye" style="color:#0d9488;"></i> Import Preview</h2>
            <p class="text-muted" style="margin:4px 0 0;">Review rows before confirming. Invalid rows will be skipped.</p>
        </div>
        <a href="index.php?page=tour_booking_csv_import" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Upload Different File
        </a>
    </div>

    <div class="preview-card">
        <div class="preview-header">
            <strong style="font-size:15px;">Import Summary</strong>
            <span class="stat-pill tot"><i class="fa fa-list"></i> <?= $total ?> rows total</span>
            <span class="stat-pill ok"><i class="fa fa-check-circle"></i> <?= count($valid) ?> will import</span>
            <?php if ($invalid): ?>
            <span class="stat-pill err"><i class="fa fa-times-circle"></i> <?= count($invalid) ?> will be skipped</span>
            <?php endif; ?>
        </div>

        <div class="preview-section">

            <?php if ($invalid): ?>
            <h5 class="err"><i class="fa fa-times-circle"></i> Rows with errors (will be skipped)</h5>
            <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th><th>Travel Date</th><th>Booking By</th>
                        <th>Pax</th><th>Amount</th><th>Status</th><th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($invalid as $row): ?>
                <tr class="err-row">
                    <td><?= $row['row_num'] ?></td>
                    <td><?= $e($row['travel_date']) ?></td>
                    <td><?= $e($row['booking_by']) ?></td>
                    <td><?= intval($row['pax_adult']) + intval($row['pax_child']) + intval($row['pax_infant']) ?></td>
                    <td><?= number_format(floatval($row['total_amount']), 2) ?></td>
                    <td><?= $e($row['status']) ?></td>
                    <td>
                        <ul class="err-list">
                        <?php foreach ($row['errors'] as $err): ?>
                            <li><?= $e($err) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>

            <?php if ($valid): ?>
            <h5 class="ok"><i class="fa fa-check-circle"></i> Rows ready to import</h5>
            <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th><th>Travel Date</th><th>Booking By</th>
                        <th>Customer</th><th>Agent</th>
                        <th>Adult</th><th>Child</th><th>Infant</th>
                        <th>Amount</th><th>Currency</th><th>Status</th><th>Pickup Hotel</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($valid as $row): ?>
                <tr>
                    <td><?= $row['row_num'] ?></td>
                    <td><?= $e($row['travel_date']) ?></td>
                    <td><?= $e($row['booking_by']) ?></td>
                    <td><?= $e($row['customer_name']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= $e($row['agent_name'])    ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= intval($row['pax_adult']) ?></td>
                    <td><?= intval($row['pax_child']) ?></td>
                    <td><?= intval($row['pax_infant']) ?></td>
                    <td><?= number_format(floatval($row['total_amount']), 2) ?></td>
                    <td><?= $e($row['currency']) ?></td>
                    <td>
                        <span class="label label-<?= $row['status'] === 'confirmed' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : 'default') ?>">
                            <?= $e($row['status']) ?>
                        </span>
                    </td>
                    <td><?= $e($row['pickup_hotel']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="alert alert-warning" style="margin-top:16px;">
                <i class="fa fa-exclamation-triangle"></i>
                No valid rows to import. Please fix the errors and upload again.
            </div>
            <?php endif; ?>
        </div>

        <div class="footer-bar">
            <?php if ($valid): ?>
            <form method="POST" action="index.php?page=tour_booking_csv_preview">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="btn btn-success" style="border-radius:8px; padding:9px 22px;"
                        onclick="this.disabled=true; this.innerHTML='<i class=\'fa fa-spinner fa-spin\'></i> Importing...'; this.form.submit();">
                    <i class="fa fa-check"></i> Confirm &amp; Import <?= count($valid) ?> Booking<?= count($valid) !== 1 ? 's' : '' ?>
                </button>
            </form>
            <?php endif; ?>
            <a href="index.php?page=tour_booking_csv_import" class="btn btn-default" style="border-radius:8px;">
                <i class="fa fa-arrow-left"></i> Start Over
            </a>
        </div>
    </div>
</div>
