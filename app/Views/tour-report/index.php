<?php
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
                    <?php foreach ($activities as $act): ?>
                    <option value="<?= intval($act['id']) ?>"><?= htmlspecialchars($act['name']) ?></option>
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
        sectionGroup.className  = 'rpt-group conditional-group' + (selected === 'checkin' ? ' show' : '');
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
