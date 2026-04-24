<?php
$pageTitle = 'Tour Reports';

/**
 * Tour Report — Filter Hub Page
 *
 * Variables: $activities
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.rpt-container { max-width: 720px; margin: 0 auto; }
.rpt-card { background: white; border-radius: 14px; padding: 28px 32px; border: 1px solid #e2e8f0; }
.rpt-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.rpt-card h3 i { color: #0d9488; margin-right: 6px; }
.rpt-group { margin-bottom: 20px; }
.rpt-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
.rpt-group input[type="date"],
.rpt-group select { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b; background: white; }
.rpt-group input[type="date"]:focus,
.rpt-group select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
.rpt-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.rpt-radio-group { display: flex; gap: 12px; flex-wrap: wrap; }
.rpt-radio { display: flex; align-items: center; gap: 6px; padding: 8px 16px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.15s; }
.rpt-radio:hover { border-color: #0d9488; }
.rpt-radio input[type="radio"] { accent-color: #0d9488; }
.rpt-radio.active { border-color: #0d9488; background: #f0fdfa; }
.rpt-radio span { font-size: 13px; font-weight: 500; color: #334155; }
.quick-dates { display: flex; gap: 8px; margin-top: 6px; }
.quick-date { padding: 4px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; color: #64748b; cursor: pointer; background: white; }
.quick-date:hover { background: #f0fdfa; border-color: #0d9488; color: #0d9488; }
.conditional-group { display: none; }
.conditional-group.show { display: block; }
.rpt-actions { display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
.rpt-actions .btn-print { padding: 11px 28px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.rpt-actions .btn-print:hover { background: #0f766e; }
.rpt-actions .btn-back { padding: 11px 24px; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.rpt-actions .btn-back:hover { background: #f8fafc; }

@media (max-width: 640px) {
    .rpt-row { grid-template-columns: 1fr; }
    .rpt-radio-group { flex-direction: column; }
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-bar-chart"></i> <?= $isThai ? 'รายงานทัวร์' : 'Tour Reports' ?></h2>
                <p><?= $isThai ? 'เลือกประเภทรายงานและกรองข้อมูล' : 'Select report type and filters' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Summary Section -->
    <?php
    $kpi     = $kpi     ?? [];
    $kpiFrom = $kpiFrom ?? date('Y-m-01');
    $kpiTo   = $kpiTo   ?? date('Y-m-d');
    ?>
    <div style="margin-bottom:24px;">
        <!-- Date range picker for KPI -->
        <form method="get" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
            <input type="hidden" name="page" value="tour_report">
            <span style="font-size:13px;font-weight:600;color:#475569;"><?= $isThai ? 'ช่วงวันที่:' : 'Date range:' ?></span>
            <input type="date" name="kpi_from" value="<?= htmlspecialchars($kpiFrom) ?>"
                   style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            <span style="color:#94a3b8;">–</span>
            <input type="date" name="kpi_to" value="<?= htmlspecialchars($kpiTo) ?>"
                   style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;">
            <button type="submit" style="padding:7px 16px;background:#0d9488;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fa fa-refresh"></i> <?= $isThai ? 'อัปเดต' : 'Update' ?>
            </button>
            <?php foreach (['month'=>[$isThai?'เดือนนี้':'This Month',date('Y-m-01'),date('Y-m-d')],'q'=>[$isThai?'ไตรมาสนี้':'This Qtr',date('Y-m-01',strtotime('first day of -'.(((int)date('n')-1)%3).' months')),date('Y-m-d')],'year'=>[$isThai?'ปีนี้':'This Year',date('Y-01-01'),date('Y-m-d')]] as $k=>[$lbl,$f,$t]): ?>
            <a href="?page=tour_report&kpi_from=<?= $f ?>&kpi_to=<?= $t ?>"
               style="padding:6px 12px;border:1px solid<?= ($kpiFrom===$f&&$kpiTo===$t)?' #0d9488;background:#f0fdfa;color:#0d9488':' #e2e8f0;background:#fff;color:#64748b' ?>;border-radius:6px;font-size:12px;text-decoration:none;">
                <?= $lbl ?>
            </a>
            <?php endforeach; ?>
        </form>

        <!-- KPI Tiles -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
            <?php
            $tiles = [
                ['val'=>number_format($kpi['total_bookings']??0), 'lbl'=>$isThai?'การจองทั้งหมด':'Total Bookings', 'icon'=>'fa-ticket',       'c'=>'#667eea'],
                ['val'=>'฿'.number_format($kpi['revenue']??0,0),  'lbl'=>$isThai?'รายได้':'Revenue',               'icon'=>'fa-dollar',       'c'=>'#0d9488'],
                ['val'=>number_format($kpi['total_pax']??0),       'lbl'=>$isThai?'นักท่องเที่ยว':'Total Pax',     'icon'=>'fa-users',        'c'=>'#f59e0b'],
                ['val'=>number_format($kpi['confirmed']??0),       'lbl'=>$isThai?'ยืนยันแล้ว':'Confirmed',        'icon'=>'fa-check-circle', 'c'=>'#10b981'],
                ['val'=>number_format($kpi['pending']??0),         'lbl'=>$isThai?'รอดำเนินการ':'Pending',         'icon'=>'fa-clock-o',      'c'=>'#f59e0b'],
                ['val'=>number_format($kpi['cancelled']??0),       'lbl'=>$isThai?'ยกเลิก':'Cancelled',            'icon'=>'fa-times-circle', 'c'=>'#ef4444'],
            ];
            foreach ($tiles as $tile): ?>
            <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e2e8f0;text-align:center;">
                <i class="fa <?= $tile['icon'] ?>" style="font-size:20px;color:<?= $tile['c'] ?>;margin-bottom:8px;display:block;"></i>
                <div style="font-size:20px;font-weight:700;color:#1e293b;"><?= $tile['val'] ?></div>
                <div style="font-size:11px;color:#64748b;margin-top:3px;"><?= $tile['lbl'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Top Agents Table -->
        <?php if (!empty($kpi['agents'])): ?>
        <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:600;color:#475569;">
                <i class="fa fa-trophy" style="color:#f59e0b;margin-right:6px;"></i>
                <?= $isThai ? 'ตัวแทนท่องเที่ยว — อันดับรายได้' : 'Top Agents by Revenue' ?>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:10px 16px;text-align:left;color:#64748b;font-weight:600;">#</th>
                        <th style="padding:10px 16px;text-align:left;color:#64748b;font-weight:600;"><?= $isThai?'ตัวแทน':'Agent' ?></th>
                        <th style="padding:10px 16px;text-align:right;color:#64748b;font-weight:600;"><?= $isThai?'การจอง':'Bookings' ?></th>
                        <th style="padding:10px 16px;text-align:right;color:#64748b;font-weight:600;"><?= $isThai?'นักท่องเที่ยว':'Pax' ?></th>
                        <th style="padding:10px 16px;text-align:right;color:#64748b;font-weight:600;"><?= $isThai?'รายได้':'Revenue' ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($kpi['agents'] as $i => $ag): ?>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:10px 16px;color:<?= $i<3?'#f59e0b':'#94a3b8' ?>;font-weight:700;"><?= $i+1 ?></td>
                        <td style="padding:10px 16px;font-weight:600;"><?= htmlspecialchars(mb_substr($ag['agent_name'],0,30)) ?></td>
                        <td style="padding:10px 16px;text-align:right;"><?= number_format($ag['bookings']) ?></td>
                        <td style="padding:10px 16px;text-align:right;"><?= number_format($ag['pax']) ?></td>
                        <td style="padding:10px 16px;text-align:right;font-weight:700;color:#0d9488;">฿<?= number_format($ag['revenue'],0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="rpt-container">
        <div class="rpt-card">
            <h3><i class="fa fa-filter"></i> <?= $isThai ? 'ตั้งค่ารายงาน' : 'Report Settings' ?></h3>

            <!-- Tour Date -->
            <div class="rpt-group">
                <label><?= $isThai ? 'วันที่ทัวร์ *' : 'Tour Date *' ?></label>
                <input type="date" id="rpt_tour_date" value="<?= $today ?>" required>
                <div class="quick-dates">
                    <button type="button" class="quick-date" data-date="<?= $today ?>"><?= $isThai ? 'วันนี้' : 'Today' ?></button>
                    <button type="button" class="quick-date" data-date="<?= $tomorrow ?>"><?= $isThai ? 'พรุ่งนี้' : 'Tomorrow' ?></button>
                    <button type="button" class="quick-date" data-date="<?= date('Y-m-d', strtotime('monday this week')) ?>"><?= $isThai ? 'จันทร์นี้' : 'This Monday' ?></button>
                </div>
            </div>

            <!-- Report Type -->
            <div class="rpt-group">
                <label><?= $isThai ? 'ประเภทรายงาน' : 'Report Type' ?></label>
                <div class="rpt-radio-group" id="rpt_type_group">
                    <label class="rpt-radio active">
                        <input type="radio" name="report_type" value="checkin" checked>
                        <span><i class="fa fa-list-alt"></i> <?= $isThai ? 'ใบเช็คอินลูกค้า' : 'Customer Check-in List' ?></span>
                    </label>
                    <label class="rpt-radio">
                        <input type="radio" name="report_type" value="pickup">
                        <span><i class="fa fa-car"></i> <?= $isThai ? 'รายงานรับลูกค้า (คนขับ)' : 'Pickup Report for Driver' ?></span>
                    </label>
                    <label class="rpt-radio">
                        <input type="radio" name="report_type" value="insurance">
                        <span><i class="fa fa-shield"></i> <?= $isThai ? 'ประกันอุบัติเหตุผู้โดยสาร' : 'Passenger Accident Insurance' ?></span>
                    </label>
                </div>
            </div>

            <!-- Section Filter (Check-in only) -->
            <div class="rpt-group conditional-group show" id="section_group">
                <label><?= $isThai ? 'กรองตามประเภท' : 'Section Filter' ?></label>
                <select id="rpt_section">
                    <option value="all"><?= $isThai ? 'ทั้งหมด' : 'All' ?></option>
                    <option value="direct"><?= $isThai ? 'จองตรงเท่านั้น' : 'Direct Booking Only' ?></option>
                    <option value="agent"><?= $isThai ? 'ผ่านตัวแทนเท่านั้น' : 'Tour Agent Only' ?></option>
                </select>
            </div>

            <!-- Grouping (Pickup only) -->
            <div class="rpt-group conditional-group" id="grouping_group">
                <label><?= $isThai ? 'จัดกลุ่มตาม' : 'Group By' ?></label>
                <select id="rpt_grouping">
                    <option value="time"><?= $isThai ? 'เวลารับ' : 'Pickup Time' ?></option>
                    <option value="location"><?= $isThai ? 'จุดรับ' : 'Pickup Location' ?></option>
                </select>
            </div>

            <!-- Tour Activity filter -->
            <?php if (!empty($activities)): ?>
            <div class="rpt-group">
                <label><?= $isThai ? 'ทัวร์/กิจกรรม (ไม่บังคับ)' : 'Tour/Activity (Optional)' ?></label>
                <select id="rpt_activity">
                    <option value=""><?= $isThai ? '— ทั้งหมด —' : '— All —' ?></option>
                    <?php foreach ($activities as $type): ?>
                    <optgroup label="<?= htmlspecialchars($type['name']) ?>">
                        <option value="type:<?= intval($type['id']) ?>">
                            <?= htmlspecialchars($type['name']) ?> (<?= $isThai ? 'ทั้งหมด' : 'All' ?>)
                        </option>
                        <?php foreach ($type['models'] as $model): ?>
                        <option value="model:<?= intval($model['id']) ?>">
                            &nbsp;&nbsp;↳ <?= htmlspecialchars($model['name']) ?><?= !empty($model['des']) ? ' — ' . htmlspecialchars($model['des']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="rpt-actions">
                <button type="button" class="btn-print" id="btn_print_report">
                    <i class="fa fa-print"></i> <?= $isThai ? 'พิมพ์รายงาน' : 'Print Report' ?>
                </button>
                <a href="index.php?page=tour_booking_list" class="btn-back">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var typeRadios = document.querySelectorAll('input[name="report_type"]');
    var sectionGroup  = document.getElementById('section_group');
    var groupingGroup = document.getElementById('grouping_group');

    function toggleGroups() {
        var selected = document.querySelector('input[name="report_type"]:checked').value;
        sectionGroup.className  = 'rpt-group conditional-group' + (selected === 'checkin' || selected === 'insurance' ? ' show' : '');
        groupingGroup.className = 'rpt-group conditional-group' + (selected === 'pickup' ? ' show' : '');

        // Update radio active state
        document.querySelectorAll('.rpt-radio').forEach(function(lbl) {
            lbl.classList.toggle('active', lbl.querySelector('input').checked);
        });
    }

    typeRadios.forEach(function(r) { r.addEventListener('change', toggleGroups); });

    // Quick date buttons
    document.querySelectorAll('.quick-date').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('rpt_tour_date').value = this.getAttribute('data-date');
        });
    });

    // Print button
    document.getElementById('btn_print_report').addEventListener('click', function() {
        var tourDate = document.getElementById('rpt_tour_date').value;
        if (!tourDate) {
            alert('<?= $isThai ? 'กรุณาเลือกวันที่ทัวร์' : 'Please select a tour date' ?>');
            return;
        }

        var reportType = document.querySelector('input[name="report_type"]:checked').value;
        var activityEl = document.getElementById('rpt_activity');
        var activity   = activityEl ? activityEl.value : '';

        var url;
        if (reportType === 'checkin') {
            var section = document.getElementById('rpt_section').value;
            url = 'index.php?page=tour_report_checkin&tour_date=' + encodeURIComponent(tourDate)
                + '&section=' + encodeURIComponent(section)
                + '&activity=' + encodeURIComponent(activity);
        } else if (reportType === 'insurance') {
            var section = document.getElementById('rpt_section').value;
            url = 'index.php?page=tour_report_insurance&tour_date=' + encodeURIComponent(tourDate)
                + '&section=' + encodeURIComponent(section)
                + '&activity=' + encodeURIComponent(activity);
        } else {
            var grouping = document.getElementById('rpt_grouping').value;
            url = 'index.php?page=tour_report_pickup&tour_date=' + encodeURIComponent(tourDate)
                + '&grouping=' + encodeURIComponent(grouping)
                + '&activity=' + encodeURIComponent(activity);
        }

        window.open(url, '_blank');
    });
})();
</script>
