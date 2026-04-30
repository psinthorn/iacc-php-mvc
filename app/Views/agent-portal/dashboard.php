<?php
$pageTitle = 'Agent Portal — Dashboard';
$isThai = ($_SESSION['lang'] ?? '0') === '1';
?>

<style>
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; }
.stat-card .stat-icon { font-size: 28px; color: #0d9488; margin-bottom: 8px; }
.stat-card .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; line-height: 1; }
.stat-card .stat-label { font-size: 13px; color: #64748b; margin-top: 6px; }
.stat-card.success .stat-icon { color: #059669; }
.stat-card.info .stat-icon { color: #2563eb; }
.stat-card.warning .stat-icon { color: #d97706; }

.dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.dash-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
.dash-card h3 { margin: 0 0 16px; font-size: 16px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }

.operator-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.operator-row:last-child { border-bottom: none; }
.operator-info { display: flex; align-items: center; gap: 12px; }
.operator-avatar { width: 36px; height: 36px; border-radius: 50%; background: #f0fdfa; color: #0d9488; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
.operator-name { font-weight: 600; color: #1e293b; font-size: 14px; }
.operator-meta { font-size: 12px; color: #94a3b8; }
.contract-badge { background: #f0fdfa; color: #0d9488; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; }

.booking-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
.booking-row:last-child { border-bottom: none; }
.booking-no { font-family: monospace; color: #0d9488; font-weight: 600; }
.booking-customer { color: #64748b; font-size: 12px; }
.booking-amount { font-weight: 600; color: #1e293b; }

@media (max-width: 768px) { .dash-grid { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-tachometer"></i> <?= $isThai ? 'แดชบอร์ดตัวแทน' : 'Agent Dashboard' ?></h2>
                <p><?= $isThai ? 'ภาพรวมบัญชีตัวแทนของคุณ' : 'Overview of your agent account' ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <i class="fa fa-building stat-icon"></i>
            <div class="stat-value"><?= $stats['operators'] ?></div>
            <div class="stat-label"><?= $isThai ? 'ผู้ประกอบการ' : 'Operators' ?></div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-cubes stat-icon"></i>
            <div class="stat-value"><?= $stats['products'] ?></div>
            <div class="stat-label"><?= $isThai ? 'สินค้าใช้งาน' : 'Active Products' ?></div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-file-text-o stat-icon"></i>
            <div class="stat-value"><?= $stats['contracts'] ?></div>
            <div class="stat-label"><?= $isThai ? 'สัญญา' : 'Contracts' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-calendar-check-o stat-icon"></i>
            <div class="stat-value"><?= $stats['bookings_30d'] ?></div>
            <div class="stat-label"><?= $isThai ? 'จอง (30 วัน)' : 'Bookings (30d)' ?></div>
        </div>
    </div>

    <!-- Two-column: operators + recent bookings -->
    <div class="dash-grid">
        <div class="dash-card">
            <h3><i class="fa fa-handshake-o"></i> <?= $isThai ? 'ผู้ประกอบการ' : 'My Operators' ?></h3>
            <?php if (empty($operators)): ?>
            <div class="empty-state">
                <i class="fa fa-handshake-o"></i>
                <p><?= $isThai ? 'ยังไม่มีผู้ประกอบการที่อนุมัติคุณ' : 'No operators have approved you yet.' ?></p>
            </div>
            <?php else: ?>
            <?php foreach ($operators as $op):
                $name = $op['operator_name_en'] ?: $op['operator_name_th'] ?: 'ID#' . $op['operator_company_id'];
            ?>
            <div class="operator-row">
                <div class="operator-info">
                    <div class="operator-avatar"><?= mb_strtoupper(mb_substr($name, 0, 1)) ?></div>
                    <div>
                        <div class="operator-name"><?= htmlspecialchars($name) ?></div>
                        <div class="operator-meta">
                            <?= $isThai ? 'อนุมัติเมื่อ' : 'Approved' ?>: <?= date('d M Y', strtotime($op['approved_at'] ?: $op['updated_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($op['default_contract_name'])): ?>
                <span class="contract-badge"><?= htmlspecialchars($op['default_contract_name']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="dash-card">
            <h3><i class="fa fa-calendar"></i> <?= $isThai ? 'การจองล่าสุด' : 'Recent Bookings' ?></h3>
            <?php if (empty($recentBookings)): ?>
            <div class="empty-state">
                <i class="fa fa-calendar-o"></i>
                <p><?= $isThai ? 'ยังไม่มีการจอง' : 'No bookings yet.' ?></p>
            </div>
            <?php else: ?>
            <?php foreach ($recentBookings as $b): ?>
            <div class="booking-row">
                <div>
                    <div class="booking-no"><?= htmlspecialchars($b['booking_number'] ?? '#' . $b['id']) ?></div>
                    <div class="booking-customer"><?= htmlspecialchars($b['customer_name'] ?: '—') ?></div>
                </div>
                <div style="text-align:right;">
                    <div class="booking-amount">฿<?= number_format(floatval($b['total_amount'] ?? 0), 2) ?></div>
                    <div class="booking-customer"><?= date('d M', strtotime($b['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:12px;text-align:center;">
                <a href="index.php?page=agent_portal_bookings" style="color:#0d9488;font-weight:600;font-size:13px;">
                    <?= $isThai ? 'ดูทั้งหมด' : 'View all' ?> →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
