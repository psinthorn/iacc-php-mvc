<?php
$pageTitle = 'Tour Operator — Reports';

/**
 * Operator-side report dashboard
 * Variables: $daily, $weekly, $monthly, $period, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'sent'        => ['✅', $isThai ? 'ส่งอีเมลสำเร็จ' : 'Report email sent successfully'],
    'no_email'    => ['⚠️', $isThai ? 'ไม่พบอีเมลผู้ดูแล' : 'No admin email found for this company'],
    'send_failed' => ['⚠️', $isThai ? 'ส่งอีเมลไม่สำเร็จ' : 'Failed to send email'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px; }
.kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
.kpi-icon { font-size: 22px; color: #0d9488; margin-bottom: 6px; }
.kpi-value { font-size: 26px; font-weight: 700; color: #1e293b; line-height: 1.1; }
.kpi-label { font-size: 12px; color: #64748b; margin-top: 4px; }
.kpi-card.success .kpi-icon { color: #059669; }
.kpi-card.warning .kpi-icon { color: #d97706; }
.kpi-card.danger .kpi-icon { color: #dc2626; }
.kpi-card.info .kpi-icon { color: #2563eb; }

.report-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.report-section h3 { margin: 0 0 16px; font-size: 16px; color: #1e293b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.send-btn { padding: 6px 14px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; }
.send-btn:hover { background: #0f766e; }

.rank-table { width: 100%; border-collapse: collapse; }
.rank-table th { background: #f8fafc; padding: 8px 12px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
.rank-table th.text-right { text-align: right; }
.rank-table td { padding: 10px 12px; border-bottom: 1px solid #f8fafc; font-size: 13px; color: #334155; }
.rank-table td.text-right { text-align: right; font-family: monospace; }
.rank-num { color: #0d9488; font-weight: 600; }

.delta-up { color: #059669; font-size: 11px; font-weight: 600; }
.delta-down { color: #dc2626; font-size: 11px; font-weight: 600; }
.delta-na { color: #94a3b8; font-size: 11px; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-bar-chart"></i> <?= $isThai ? 'รายงานสัญญาผู้ประกอบการ' : 'Contract & Operator Reports' ?></h2>
                <p><?= $isThai ? 'สรุปรายวัน รายสัปดาห์ และรายเดือน' : 'Daily, weekly, and monthly summaries' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_contract_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- ── Daily ── -->
    <div class="report-section">
        <h3>
            <span><i class="fa fa-calendar-o"></i> <?= $isThai ? 'รายวัน' : 'Daily' ?> · <?= date('d M Y', strtotime($daily['date'])) ?></span>
            <form method="post" action="index.php?page=tour_contract_report_send" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="period" value="daily">
                <button type="submit" class="send-btn"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'ส่งทางอีเมล' : 'Email Now' ?></button>
            </form>
        </h3>
        <div class="kpi-row">
            <div class="kpi-card"><i class="fa fa-file-text-o kpi-icon"></i><div class="kpi-value"><?= $daily['new_contracts'] ?></div><div class="kpi-label"><?= $isThai ? 'สัญญาใหม่' : 'New Contracts' ?></div></div>
            <div class="kpi-card success"><i class="fa fa-user-plus kpi-icon"></i><div class="kpi-value"><?= $daily['new_agents'] ?></div><div class="kpi-label"><?= $isThai ? 'ตัวแทนอนุมัติ' : 'New Agents' ?></div></div>
            <div class="kpi-card warning"><i class="fa fa-clock-o kpi-icon"></i><div class="kpi-value"><?= $daily['pending_approvals'] ?></div><div class="kpi-label"><?= $isThai ? 'รออนุมัติ' : 'Pending' ?></div></div>
            <div class="kpi-card info"><i class="fa fa-refresh kpi-icon"></i><div class="kpi-value"><?= $daily['sync_events'] ?></div><div class="kpi-label"><?= $isThai ? 'ซิงค์' : 'Sync Events' ?></div></div>
            <div class="kpi-card"><i class="fa fa-calendar-check-o kpi-icon"></i><div class="kpi-value"><?= $daily['bookings_today'] ?></div><div class="kpi-label"><?= $isThai ? 'การจอง' : 'Bookings' ?></div></div>
        </div>
    </div>

    <!-- ── Weekly ── -->
    <div class="report-section">
        <h3>
            <span><i class="fa fa-calendar"></i> <?= $isThai ? 'รายสัปดาห์' : 'Weekly' ?> · <?= date('d M', strtotime($weekly['period_start'])) ?> — <?= date('d M Y', strtotime($weekly['period_end'])) ?></span>
            <form method="post" action="index.php?page=tour_contract_report_send" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="period" value="weekly">
                <button type="submit" class="send-btn"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'ส่งทางอีเมล' : 'Email Now' ?></button>
            </form>
        </h3>
        <div class="kpi-row">
            <div class="kpi-card"><i class="fa fa-file-text-o kpi-icon"></i><div class="kpi-value"><?= $weekly['new_contracts'] ?></div><div class="kpi-label"><?= $isThai ? 'สัญญาใหม่' : 'New Contracts' ?></div></div>
            <div class="kpi-card success"><i class="fa fa-user-plus kpi-icon"></i><div class="kpi-value"><?= $weekly['new_agents'] ?></div><div class="kpi-label"><?= $isThai ? 'ตัวแทนใหม่' : 'New Agents' ?></div></div>
            <div class="kpi-card info"><i class="fa fa-calendar-check-o kpi-icon"></i><div class="kpi-value"><?= $weekly['bookings'] ?></div><div class="kpi-label"><?= $isThai ? 'การจอง' : 'Bookings' ?></div></div>
            <div class="kpi-card success"><i class="fa fa-money kpi-icon"></i><div class="kpi-value">฿<?= number_format($weekly['revenue'], 0) ?></div><div class="kpi-label"><?= $isThai ? 'รายได้' : 'Revenue' ?></div></div>
            <div class="kpi-card"><i class="fa fa-refresh kpi-icon"></i><div class="kpi-value"><?= $weekly['sync_events'] ?></div><div class="kpi-label"><?= $isThai ? 'ซิงค์' : 'Sync Events' ?></div></div>
        </div>

        <?php if (!empty($weekly['top_agents'])): ?>
        <h4 style="margin:20px 0 12px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?= $isThai ? '5 อันดับตัวแทน' : 'Top 5 Agents' ?></h4>
        <table class="rank-table">
            <thead><tr><th>#</th><th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th><th class="text-right"><?= $isThai ? 'การจอง' : 'Bookings' ?></th><th class="text-right"><?= $isThai ? 'รายได้' : 'Revenue' ?></th></tr></thead>
            <tbody>
                <?php foreach ($weekly['top_agents'] as $i => $a): ?>
                <tr>
                    <td><span class="rank-num"><?= $i + 1 ?></span></td>
                    <td><?= htmlspecialchars($a['agent_name'] ?: 'Agent #' . $a['agent_id']) ?></td>
                    <td class="text-right"><?= $a['booking_count'] ?></td>
                    <td class="text-right">฿<?= number_format(floatval($a['revenue']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── Monthly ── -->
    <div class="report-section">
        <h3>
            <span><i class="fa fa-calendar-plus-o"></i> <?= $isThai ? 'รายเดือน' : 'Monthly' ?> · <?= date('M Y', strtotime($monthly['period_start'])) ?></span>
            <form method="post" action="index.php?page=tour_contract_report_send" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="period" value="monthly">
                <button type="submit" class="send-btn"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'ส่งทางอีเมล' : 'Email Now' ?></button>
            </form>
        </h3>
        <div class="kpi-row">
            <div class="kpi-card info">
                <i class="fa fa-calendar-check-o kpi-icon"></i>
                <div class="kpi-value"><?= $monthly['current']['bookings'] ?></div>
                <div class="kpi-label">
                    <?= $isThai ? 'การจอง' : 'Bookings' ?>
                    <?php if ($monthly['change_pct']['bookings'] !== null): ?>
                        <span class="<?= $monthly['change_pct']['bookings'] >= 0 ? 'delta-up' : 'delta-down' ?>">
                            <?= $monthly['change_pct']['bookings'] >= 0 ? '▲' : '▼' ?> <?= abs($monthly['change_pct']['bookings']) ?>%
                        </span>
                    <?php else: ?>
                        <span class="delta-na">—</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kpi-card success">
                <i class="fa fa-money kpi-icon"></i>
                <div class="kpi-value">฿<?= number_format($monthly['current']['revenue'], 0) ?></div>
                <div class="kpi-label">
                    <?= $isThai ? 'รายได้' : 'Revenue' ?>
                    <?php if ($monthly['change_pct']['revenue'] !== null): ?>
                        <span class="<?= $monthly['change_pct']['revenue'] >= 0 ? 'delta-up' : 'delta-down' ?>">
                            <?= $monthly['change_pct']['revenue'] >= 0 ? '▲' : '▼' ?> <?= abs($monthly['change_pct']['revenue']) ?>%
                        </span>
                    <?php else: ?>
                        <span class="delta-na">—</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kpi-card"><i class="fa fa-file-text-o kpi-icon"></i><div class="kpi-value"><?= $monthly['new_contracts'] ?></div><div class="kpi-label"><?= $isThai ? 'สัญญาใหม่' : 'New Contracts' ?></div></div>
            <div class="kpi-card success"><i class="fa fa-user-plus kpi-icon"></i><div class="kpi-value"><?= $monthly['new_agents'] ?></div><div class="kpi-label"><?= $isThai ? 'ตัวแทนใหม่' : 'New Agents' ?></div></div>
        </div>

        <?php if (!empty($monthly['top_products'])): ?>
        <h4 style="margin:20px 0 12px;font-size:14px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?= $isThai ? '10 อันดับสินค้า' : 'Top 10 Products' ?></h4>
        <table class="rank-table">
            <thead><tr><th>#</th><th><?= $isThai ? 'สินค้า' : 'Product' ?></th><th class="text-right"><?= $isThai ? 'การจอง' : 'Bookings' ?></th><th class="text-right"><?= $isThai ? 'รายได้' : 'Revenue' ?></th></tr></thead>
            <tbody>
                <?php foreach ($monthly['top_products'] as $i => $p): ?>
                <tr>
                    <td><span class="rank-num"><?= $i + 1 ?></span></td>
                    <td><?= htmlspecialchars($p['model_name'] ?: 'Product #' . $p['model_id']) ?></td>
                    <td class="text-right"><?= $p['bookings'] ?></td>
                    <td class="text-right">฿<?= number_format(floatval($p['revenue']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
