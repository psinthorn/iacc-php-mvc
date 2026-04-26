<?php
/**
 * Staff Check-In Dashboard
 * Shows all bookings for a selected tour date with real-time check-in status.
 * Auto-refreshes every 60 seconds via meta-refresh (cPanel compatible, no websockets).
 *
 * Variables: $bookings (array), $summary (array), $date (string)
 */
$pageTitle = 'Check-In Dashboard';
$isThai    = ($_SESSION['lang'] ?? '0') === '1';
$today     = date('Y-m-d');
$prev      = date('Y-m-d', strtotime($date . ' -1 day'));
$next      = date('Y-m-d', strtotime($date . ' +1 day'));

$checkedIn  = intval($summary['checked_in']  ?? 0);
$total      = intval($summary['total']        ?? 0);
$totalPax   = intval($summary['total_pax']    ?? 0);
$paxIn      = intval($summary['pax_checked_in'] ?? 0);
$pct        = $total > 0 ? round(($checkedIn / $total) * 100) : 0;
?>
<meta http-equiv="refresh" content="60;url=index.php?page=tour_checkin_staff&date=<?= urlencode($date) ?>">
<link rel="stylesheet" href="css/master-data.css">

<style>
.ci-header-bar {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
}
.ci-date-nav { display: flex; align-items: center; gap: 6px; }
.ci-date-nav a {
    width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; text-decoration: none;
    font-size: 14px; background: white;
}
.ci-date-nav a:hover { background: #f0fdfa; color: #0d9488; border-color: #0d9488; }
.ci-date-input { padding: 7px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
.kpi-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px; }
.kpi-tile {
    background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; text-align: center;
}
.kpi-tile .val { font-size: 24px; font-weight: 700; color: #1e293b; }
.kpi-tile .lbl { font-size: 11px; color: #94a3b8; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
.progress-bar-wrap { background: #f1f5f9; border-radius: 999px; height: 8px; margin-top: 6px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #0d9488, #10b981); transition: width 0.4s; }

.ci-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ci-table th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.ci-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.ci-table tr:hover td { background: #f8fafc; }
.ci-table tr.is-checked td { background: #f0fdf4; }

.badge-in  { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#dcfce7;color:#166534;border-radius:20px;font-size:12px;font-weight:600; }
.badge-out { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#f1f5f9;color:#64748b;border-radius:20px;font-size:12px;font-weight:600; }
.btn-ci-action {
    padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; transition: all 0.15s;
}
.btn-override { background: #0d9488; color: white; }
.btn-override:hover { background: #0f766e; }
.btn-reset    { background: #fff; color: #ef4444; border: 1px solid #fecaca !important; }
.btn-reset:hover { background: #fff1f2; }
.auto-refresh-note {
    font-size: 11px; color: #94a3b8; margin-top: 16px; text-align: center;
}
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-check-square-o"></i> <?= $isThai ? 'แดชบอร์ดเช็คอิน' : 'Check-In Dashboard' ?></h2>
                <p><?= $isThai ? 'ติดตามสถานะการเช็คอินแบบเรียลไทม์' : 'Real-time check-in status tracker' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_report" class="btn-header btn-header-outline">
                    <i class="fa fa-bar-chart"></i> <?= $isThai ? 'รายงาน' : 'Reports' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Date Navigation -->
    <div class="ci-header-bar">
        <div class="ci-date-nav">
            <a href="index.php?page=tour_checkin_staff&date=<?= $prev ?>"><i class="fa fa-chevron-left"></i></a>
            <form method="get" style="display:contents;">
                <input type="hidden" name="page" value="tour_checkin_staff">
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"
                       class="ci-date-input" onchange="this.form.submit()">
            </form>
            <a href="index.php?page=tour_checkin_staff&date=<?= $next ?>"><i class="fa fa-chevron-right"></i></a>
        </div>
        <?php if ($date !== $today): ?>
        <a href="index.php?page=tour_checkin_staff&date=<?= $today ?>"
           style="padding:7px 14px;background:#0d9488;color:white;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
            <i class="fa fa-calendar-check-o"></i> <?= $isThai ? 'วันนี้' : 'Today' ?>
        </a>
        <?php endif; ?>
        <span style="font-size:13px;color:#64748b;margin-left:auto;">
            <i class="fa fa-refresh" style="color:#0d9488;"></i>
            <?= $isThai ? 'รีเฟรชอัตโนมัติทุก 60 วินาที' : 'Auto-refreshes every 60 seconds' ?>
        </span>
    </div>

    <!-- KPI Tiles -->
    <div class="kpi-tiles">
        <div class="kpi-tile">
            <div class="val"><?= $total ?></div>
            <div class="lbl"><?= $isThai ? 'ทั้งหมด' : 'Total Bookings' ?></div>
        </div>
        <div class="kpi-tile" style="border-color:#bbf7d0;">
            <div class="val" style="color:#059669;"><?= $checkedIn ?></div>
            <div class="lbl"><?= $isThai ? 'เช็คอินแล้ว' : 'Checked In' ?></div>
        </div>
        <div class="kpi-tile">
            <div class="val" style="color:#64748b;"><?= $total - $checkedIn ?></div>
            <div class="lbl"><?= $isThai ? 'ยังไม่เช็คอิน' : 'Not Yet' ?></div>
        </div>
        <div class="kpi-tile">
            <div class="val"><?= $totalPax ?></div>
            <div class="lbl"><?= $isThai ? 'ผู้โดยสารทั้งหมด' : 'Total Pax' ?></div>
        </div>
        <div class="kpi-tile" style="border-color:#bbf7d0;">
            <div class="val" style="color:#059669;"><?= $paxIn ?></div>
            <div class="lbl"><?= $isThai ? 'ผู้โดยสารเช็คอินแล้ว' : 'Pax Checked In' ?></div>
        </div>
        <div class="kpi-tile">
            <div class="val"><?= $pct ?>%</div>
            <div class="lbl"><?= $isThai ? 'เปอร์เซ็นต์เช็คอิน' : 'Check-In Rate' ?></div>
            <div class="progress-bar-wrap" style="margin-top:8px;">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- Booking Table -->
    <?php if (empty($bookings)): ?>
    <div class="empty-state">
        <i class="fa fa-calendar-times-o"></i>
        <p style="font-size:15px;font-weight:600;color:#475569;"><?= $isThai ? 'ไม่มีการจองในวันนี้' : 'No bookings for this date' ?></p>
        <p style="font-size:13px;"><?= $isThai ? 'เลือกวันอื่น หรือ ' : 'Try another date or ' ?>
            <a href="index.php?page=tour_booking_list" style="color:#0d9488;"><?= $isThai ? 'ดูรายการจอง' : 'view all bookings' ?></a>
        </p>
    </div>
    <?php else: ?>
    <div style="background:white;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;">
        <table class="ci-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $isThai ? 'ลูกค้า' : 'Customer' ?></th>
                    <th><?= $isThai ? 'เลขจอง' : 'Booking' ?></th>
                    <th><?= $isThai ? 'ผู้โดยสาร' : 'Pax' ?></th>
                    <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th><?= $isThai ? 'เวลาเช็คอิน' : 'Check-In Time' ?></th>
                    <th><?= $isThai ? 'จัดการ' : 'Action' ?></th>
                </tr>
            </thead>
            <tbody id="checkin-tbody">
            <?php foreach ($bookings as $i => $bk): ?>
            <?php $isIn = intval($bk['checkin_status']) === 1; ?>
            <tr class="<?= $isIn ? 'is-checked' : '' ?>" id="row-<?= intval($bk['id']) ?>">
                <td style="color:#94a3b8;font-weight:600;"><?= $i + 1 ?></td>
                <td>
                    <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars(mb_substr($bk['contact_name'] ?? '—', 0, 30)) ?></div>
                    <?php if (!empty($bk['contact_phone'])): ?>
                    <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($bk['contact_phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;color:#0d9488;">
                    <a href="index.php?page=tour_booking_view&id=<?= intval($bk['id']) ?>" style="color:#0d9488;text-decoration:none;">
                        <?= htmlspecialchars($bk['booking_number']) ?>
                    </a>
                </td>
                <td>
                    <span style="font-weight:600;"><?= intval($bk['total_pax']) ?></span>
                    <span style="font-size:11px;color:#94a3b8;">
                        (<?= intval($bk['pax_adult']) ?>A
                        <?= $bk['pax_child']  > 0 ? '+' . intval($bk['pax_child'])  . 'C' : '' ?>
                        <?= $bk['pax_infant'] > 0 ? '+' . intval($bk['pax_infant']) . 'I' : '' ?>)
                    </span>
                </td>
                <td style="color:#64748b;font-size:12px;">
                    <?= htmlspecialchars(mb_substr($bk['agent_name'] ?? '—', 0, 20)) ?>
                </td>
                <td>
                    <?php if ($isIn): ?>
                        <span class="badge-in"><i class="fa fa-check-circle"></i> <?= $isThai ? 'เช็คอินแล้ว' : 'Checked In' ?></span>
                    <?php else: ?>
                        <span class="badge-out"><i class="fa fa-clock-o"></i> <?= $isThai ? 'ยังไม่เช็คอิน' : 'Pending' ?></span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748b;">
                    <?= $isIn && $bk['checkin_at'] ? date('H:i', strtotime($bk['checkin_at'])) : '—' ?>
                </td>
                <td>
                    <?php if ($isIn): ?>
                    <button class="btn-ci-action btn-reset"
                            onclick="ciAction('reset', <?= intval($bk['id']) ?>, this)"
                            style="border:1px solid #fecaca;">
                        <i class="fa fa-undo"></i> <?= $isThai ? 'รีเซ็ต' : 'Reset' ?>
                    </button>
                    <?php else: ?>
                    <button class="btn-ci-action btn-override"
                            onclick="ciAction('override', <?= intval($bk['id']) ?>, this)">
                        <i class="fa fa-check"></i> <?= $isThai ? 'เช็คอิน' : 'Check In' ?>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <p class="auto-refresh-note">
        <i class="fa fa-info-circle"></i>
        <?= $isThai
            ? 'หน้านี้จะรีเฟรชอัตโนมัติทุก 60 วินาที หรือกด F5 เพื่อรีเฟรชทันที'
            : 'This page auto-refreshes every 60 seconds. Press F5 to refresh immediately.' ?>
    </p>
</div>

<script>
function ciAction(action, bookingId, btn) {
    var route = action === 'reset' ? 'tour_checkin_reset' : 'tour_checkin_override';
    var isThai = <?= json_encode($isThai) ?>;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    fetch('index.php?page=' + route, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'booking_id=' + bookingId + '&csrf_token=<?= csrf_token() ?>'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.success) {
            alert(d.message || 'Action failed');
            btn.disabled = false;
            btn.innerHTML = action === 'reset'
                ? '<i class="fa fa-undo"></i> ' + (isThai ? 'รีเซ็ต' : 'Reset')
                : '<i class="fa fa-check"></i> ' + (isThai ? 'เช็คอิน' : 'Check In');
            return;
        }

        // Update the row DOM in-place — no full page reload needed
        var row = document.getElementById('row-' + bookingId);
        if (!row) { location.reload(); return; }

        if (action === 'reset') {
            row.classList.remove('is-checked');
            // Status badge cell (col index 5)
            row.cells[5].innerHTML = '<span class="badge-out"><i class="fa fa-clock-o"></i> ' + (isThai ? 'ยังไม่เช็คอิน' : 'Pending') + '</span>';
            // Time cell (col index 6)
            row.cells[6].textContent = '—';
            // Action cell (col index 7)
            row.cells[7].innerHTML = '<button class="btn-ci-action btn-override" onclick="ciAction(\'override\',' + bookingId + ',this)">'
                + '<i class="fa fa-check"></i> ' + (isThai ? 'เช็คอิน' : 'Check In') + '</button>';
        } else {
            row.classList.add('is-checked');
            row.cells[5].innerHTML = '<span class="badge-in"><i class="fa fa-check-circle"></i> ' + (isThai ? 'เช็คอินแล้ว' : 'Checked In') + '</span>';
            row.cells[6].textContent = d.time_label || '—';
            row.cells[7].innerHTML = '<button class="btn-ci-action btn-reset" onclick="ciAction(\'reset\',' + bookingId + ',this)" style="border:1px solid #fecaca;">'
                + '<i class="fa fa-undo"></i> ' + (isThai ? 'รีเซ็ต' : 'Reset') + '</button>';
        }

        // Move row to bottom (checked) or top (reset) — optional visual re-sort
        var tbody = row.parentNode;
        if (action === 'override') {
            tbody.appendChild(row);
        } else {
            tbody.insertBefore(row, tbody.firstChild);
        }

        // Update KPI counter
        updateKpiCounters();
    })
    .catch(function() { location.reload(); });
}

function updateKpiCounters() {
    var rows        = document.querySelectorAll('#checkin-tbody tr');
    var total       = rows.length;
    var checkedIn   = document.querySelectorAll('#checkin-tbody tr.is-checked').length;
    var notYet      = total - checkedIn;
    var pct         = total > 0 ? Math.round((checkedIn / total) * 100) : 0;

    var kpiVals = document.querySelectorAll('.kpi-tile .val');
    // tiles: total, checked_in, not_yet, total_pax, pax_checked_in, rate
    if (kpiVals[1]) kpiVals[1].textContent = checkedIn;
    if (kpiVals[2]) kpiVals[2].textContent = notYet;
    if (kpiVals[5]) {
        kpiVals[5].textContent = pct + '%';
        var fill = document.querySelector('.progress-bar-fill');
        if (fill) fill.style.width = pct + '%';
    }
}
</script>
