<?php
$pageTitle = 'Super Admin — Tour Operator Platform';

/**
 * Super Admin platform-wide tour overview
 * Variables: $platform, $operators, $recentSync, $pendingApprovals, $cronUrl, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'sent' => ['✅', $isThai ? 'ส่งอีเมลให้ผู้ประกอบการแล้ว' : 'Digest emails dispatched to operators'],
];

$syncActionLabels = [
    'sync'                => $isThai ? 'ซิงค์'             : 'Sync',
    'resync'              => $isThai ? 'ซิงค์ใหม่'         : 'Resync',
    'product_added'       => $isThai ? 'เพิ่มสินค้า'        : 'Product Added',
    'product_removed'     => $isThai ? 'ลบสินค้า'          : 'Product Removed',
    'contract_unassigned' => $isThai ? 'ยกเลิกการมอบหมาย' : 'Contract Unassigned',
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.platform-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
.kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; }
.kpi-card.featured { border-left: 4px solid #0d9488; }
.kpi-card.warning { border-left: 4px solid #d97706; }
.kpi-card.success { border-left: 4px solid #059669; }
.kpi-icon { font-size: 22px; color: #0d9488; margin-bottom: 6px; }
.kpi-value { font-size: 28px; font-weight: 700; color: #1e293b; line-height: 1; }
.kpi-label { font-size: 12px; color: #64748b; margin-top: 4px; }

.dash-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.dash-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
.dash-card h3 { margin: 0 0 16px; font-size: 15px; color: #1e293b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.dash-card h3 .count { background: #f0fdfa; color: #0d9488; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; }

table.list { width: 100%; border-collapse: collapse; }
table.list th { background: #f8fafc; padding: 8px 12px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
table.list th.text-right { text-align: right; }
table.list td { padding: 10px 12px; border-bottom: 1px solid #f8fafc; font-size: 13px; color: #334155; }
table.list td.text-right { text-align: right; font-family: monospace; }
table.list tr:last-child td { border-bottom: none; }
table.list tr:hover td { background: #f8fafc; }

.action-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
.action-bar h3 { margin: 0 0 14px; font-size: 14px; color: #1e293b; }
.action-bar form { display: inline-block; margin-right: 8px; }
.btn-action { padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
.btn-daily { background: #f0fdfa; color: #0d9488; }
.btn-daily:hover { background: #ccfbf1; }
.btn-weekly { background: #eff6ff; color: #2563eb; }
.btn-weekly:hover { background: #dbeafe; }
.btn-monthly { background: #fef3c7; color: #d97706; }
.btn-monthly:hover { background: #fde68a; }

.cron-snippet { background: #1e293b; color: #94a3b8; padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-top: 12px; word-break: break-all; }
.cron-snippet .cmt { color: #64748b; }

@media (max-width: 768px) { .dash-row { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="rose">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-shield"></i> <?= $isThai ? 'แพลตฟอร์มผู้ประกอบการทัวร์ (ผู้ดูแลสูงสุด)' : 'Tour Operator Platform (Super Admin)' ?></h2>
                <p><?= $isThai ? 'ภาพรวมและการจัดการแบบรวมทุกผู้ประกอบการ' : 'Cross-tenant overview and platform-wide management' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'แดชบอร์ด' : 'Dashboard' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#ecfdf5; border-left:4px solid #059669; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
        <?php if (!empty($_GET['sent'])): ?> · <?= intval($_GET['sent']) ?> sent, <?= intval($_GET['failed'] ?? 0) ?> failed<?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Platform KPIs -->
    <div class="platform-kpis">
        <div class="kpi-card featured">
            <i class="fa fa-building kpi-icon"></i>
            <div class="kpi-value"><?= $platform['active_operators'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'ผู้ประกอบการ' : 'Active Operators' ?></div>
        </div>
        <div class="kpi-card success">
            <i class="fa fa-handshake-o kpi-icon"></i>
            <div class="kpi-value"><?= $platform['active_agents'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'ตัวแทนใช้งาน' : 'Active Agents' ?></div>
        </div>
        <div class="kpi-card">
            <i class="fa fa-file-text-o kpi-icon"></i>
            <div class="kpi-value"><?= $platform['new_contracts'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'สัญญาวันนี้' : 'New Contracts Today' ?></div>
        </div>
        <div class="kpi-card warning">
            <i class="fa fa-clock-o kpi-icon"></i>
            <div class="kpi-value"><?= $platform['pending_approvals'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'รออนุมัติ' : 'Pending Approvals' ?></div>
        </div>
        <div class="kpi-card">
            <i class="fa fa-refresh kpi-icon"></i>
            <div class="kpi-value"><?= $platform['sync_events'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'ซิงค์วันนี้' : 'Sync Events Today' ?></div>
        </div>
        <div class="kpi-card success">
            <i class="fa fa-calendar-check-o kpi-icon"></i>
            <div class="kpi-value"><?= $platform['bookings_today'] ?></div>
            <div class="kpi-label"><?= $isThai ? 'การจองวันนี้' : 'Bookings Today' ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
        <h3><i class="fa fa-paper-plane-o"></i> <?= $isThai ? 'ส่งสรุปอีเมลทันที' : 'Send Digest Emails Now' ?></h3>
        <form method="post" action="index.php?page=super_admin_tour_send_all">
            <?= csrf_field() ?>
            <input type="hidden" name="period" value="daily">
            <button type="submit" class="btn-action btn-daily"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'รายวัน' : 'Daily' ?></button>
        </form>
        <form method="post" action="index.php?page=super_admin_tour_send_all">
            <?= csrf_field() ?>
            <input type="hidden" name="period" value="weekly">
            <button type="submit" class="btn-action btn-weekly"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'รายสัปดาห์' : 'Weekly' ?></button>
        </form>
        <form method="post" action="index.php?page=super_admin_tour_send_all">
            <?= csrf_field() ?>
            <input type="hidden" name="period" value="monthly">
            <button type="submit" class="btn-action btn-monthly"><i class="fa fa-envelope-o"></i> <?= $isThai ? 'รายเดือน' : 'Monthly' ?></button>
        </form>

        <div style="margin-top:14px;font-size:12px;color:#64748b;">
            <strong><?= $isThai ? 'หรือใช้ cron บน cPanel:' : 'Or schedule via cPanel cron:' ?></strong>
        </div>
        <div class="cron-snippet">
            <span class="cmt"># Daily at 8am</span><br>
            0 8 * * * curl -s "<?= htmlspecialchars($cronUrl) ?>?task=daily_reports&token=YOUR_SECRET"<br>
            <span class="cmt"># Weekly on Mon at 9am</span><br>
            0 9 * * 1 curl -s "<?= htmlspecialchars($cronUrl) ?>?task=weekly_reports&token=YOUR_SECRET"<br>
            <span class="cmt"># Monthly on 1st at 9am</span><br>
            0 9 1 * * curl -s "<?= htmlspecialchars($cronUrl) ?>?task=monthly_reports&token=YOUR_SECRET"
        </div>
    </div>

    <!-- Two-column: operators + pending approvals -->
    <div class="dash-row">
        <div class="dash-card">
            <h3>
                <span><i class="fa fa-building"></i> <?= $isThai ? 'ผู้ประกอบการ' : 'Operators' ?></span>
                <span class="count"><?= count($operators) ?></span>
            </h3>
            <?php if (empty($operators)): ?>
            <p style="text-align:center;color:#94a3b8;padding:24px;"><?= $isThai ? 'ไม่มีผู้ประกอบการ' : 'No operators yet.' ?></p>
            <?php else: ?>
            <table class="list">
                <thead>
                    <tr>
                        <th><?= $isThai ? 'ผู้ประกอบการ' : 'Operator' ?></th>
                        <th class="text-right"><?= $isThai ? 'ตัวแทน' : 'Agents' ?></th>
                        <th class="text-right"><?= $isThai ? 'สัญญา' : 'Contracts' ?></th>
                        <th class="text-right"><?= $isThai ? 'จอง 30 วัน' : 'Bookings 30d' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operators as $op): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($op['name_en'] ?: 'ID#' . $op['id']) ?></strong>
                            <div style="font-size:11px;color:#94a3b8;">ID#<?= $op['id'] ?></div>
                        </td>
                        <td class="text-right"><?= $op['agent_count'] ?></td>
                        <td class="text-right"><?= $op['contract_count'] ?></td>
                        <td class="text-right"><?= $op['bookings_30d'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="dash-card">
            <h3>
                <span><i class="fa fa-clock-o"></i> <?= $isThai ? 'รออนุมัติทั้งระบบ' : 'Platform Pending Approvals' ?></span>
                <span class="count"><?= count($pendingApprovals) ?></span>
            </h3>
            <?php if (empty($pendingApprovals)): ?>
            <p style="text-align:center;color:#94a3b8;padding:24px;"><?= $isThai ? 'ไม่มีคำขอรออนุมัติ' : 'No pending approvals across the platform.' ?></p>
            <?php else: ?>
            <table class="list">
                <thead>
                    <tr>
                        <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                        <th><?= $isThai ? 'ขอเข้าร่วม' : 'Wants to join' ?></th>
                        <th><?= $isThai ? 'เมื่อ' : 'When' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingApprovals as $p): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($p['agent_name'] ?: 'ID#' . $p['agent_company_id']) ?>
                            <?php if (!empty($p['agent_email'])): ?>
                            <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($p['agent_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['operator_name'] ?: 'ID#' . $p['operator_company_id']) ?></td>
                        <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent sync log -->
    <div class="dash-card">
        <h3>
            <span><i class="fa fa-refresh"></i> <?= $isThai ? 'การซิงค์ล่าสุด' : 'Recent Sync Activity' ?></span>
            <span class="count"><?= count($recentSync) ?></span>
        </h3>
        <?php if (empty($recentSync)): ?>
        <p style="text-align:center;color:#94a3b8;padding:24px;"><?= $isThai ? 'ยังไม่มีกิจกรรมการซิงค์' : 'No sync activity yet.' ?></p>
        <?php else: ?>
        <table class="list">
            <thead>
                <tr>
                    <th><?= $isThai ? 'เวลา' : 'When' ?></th>
                    <th><?= $isThai ? 'ผู้ประกอบการ' : 'Operator' ?></th>
                    <th><?= $isThai ? 'สัญญา' : 'Contract' ?></th>
                    <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                    <th><?= $isThai ? 'การกระทำ' : 'Action' ?></th>
                    <th><?= $isThai ? 'ที่มา' : 'Source' ?></th>
                    <th class="text-right">+/-</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentSync as $s): ?>
                <tr>
                    <td><?= date('d M H:i', strtotime($s['created_at'])) ?></td>
                    <td><?= htmlspecialchars($s['operator_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($s['contract_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($s['agent_name'] ?: ($isThai ? 'ทั้งหมด' : 'all agents')) ?></td>
                    <td><?= $syncActionLabels[$s['action']] ?? htmlspecialchars($s['action']) ?></td>
                    <td><span style="font-size:11px;color:#64748b;"><?= htmlspecialchars($s['triggered_by']) ?></span></td>
                    <td class="text-right">
                        <span style="color:#059669;">+<?= $s['products_added'] ?></span>
                        <span style="color:#dc2626;">-<?= $s['products_removed'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
