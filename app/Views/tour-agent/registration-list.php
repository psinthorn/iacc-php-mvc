<?php
$pageTitle = 'Tour Operator — Agent Registrations';

/**
 * Agent Registration List (operator-side)
 * Variables: $agents, $counts, $statusFilter, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'approved'    => ['✅', $isThai ? 'อนุมัติตัวแทนสำเร็จ' : 'Agent approved successfully'],
    'rejected'    => ['✅', $isThai ? 'ปฏิเสธคำขอสำเร็จ' : 'Registration rejected'],
    'suspended'   => ['✅', $isThai ? 'ระงับตัวแทนสำเร็จ' : 'Agent suspended'],
    'reactivated' => ['✅', $isThai ? 'เปิดใช้งานตัวแทนสำเร็จ' : 'Agent reactivated'],
    'invited'     => ['✅', $isThai ? 'ส่งคำเชิญสำเร็จ' : 'Invitation sent'],
    'not_found'   => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Registration not found'],
    'error'       => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
];

$statusBadges = [
    'pending'   => [$isThai ? 'รออนุมัติ' : 'Pending',   '#d97706', '#fffbeb'],
    'approved'  => [$isThai ? 'อนุมัติแล้ว' : 'Approved', '#059669', '#ecfdf5'],
    'suspended' => [$isThai ? 'ถูกระงับ'   : 'Suspended', '#dc2626', '#fef2f2'],
    'rejected'  => [$isThai ? 'ปฏิเสธแล้ว' : 'Rejected',  '#94a3b8', '#f1f5f9'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; transition: all .15s; text-decoration: none; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.stat-card.active { border-color: #0d9488; background: #f0fdfa; }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
.stat-card .stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }
.stat-card.warning { border-left: 3px solid #d97706; }
.stat-card.success { border-left: 3px solid #059669; }
.stat-card.danger { border-left: 3px solid #dc2626; }
.stat-card.muted { border-left: 3px solid #94a3b8; }

.agent-table { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.agent-table table { width: 100%; border-collapse: collapse; }
.agent-table th { background: #f8fafc; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.agent-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
.agent-table tr:last-child td { border-bottom: none; }
.agent-table tr:hover td { background: #f8fafc; }
.agent-avatar { width: 36px; height: 36px; border-radius: 50%; background: #f0fdfa; color: #0d9488; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
.agent-name { font-weight: 600; color: #1e293b; }
.agent-detail { font-size: 12px; color: #94a3b8; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.via-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; background: #f1f5f9; color: #64748b; }
.btn-row { display: inline-flex; gap: 6px; }
.btn-icon { padding: 6px 12px; border-radius: 6px; font-size: 12px; text-decoration: none; border: none; cursor: pointer; }
.btn-view { background: #f0fdfa; color: #0d9488; }
.btn-view:hover { background: #ccfbf1; }
.empty-state { text-align: center; padding: 48px; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-user-plus"></i> <?= $isThai ? 'การลงทะเบียนตัวแทน' : 'Agent Registrations' ?></h2>
                <p><?= $isThai ? 'จัดการการลงทะเบียนและอนุมัติตัวแทน' : 'Manage agent registrations and approvals' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <a href="index.php?page=tour_agent_make" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มตัวแทน' : 'Add Agent' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Status filter cards -->
    <div class="stats-row">
        <a href="index.php?page=tour_agent_reg_list" class="stat-card <?= empty($statusFilter) ? 'active' : '' ?>">
            <div class="stat-value"><?= $counts['total'] ?></div>
            <div class="stat-label"><?= $isThai ? 'ทั้งหมด' : 'All' ?></div>
        </a>
        <a href="index.php?page=tour_agent_reg_list&status=pending" class="stat-card warning <?= $statusFilter === 'pending' ? 'active' : '' ?>">
            <div class="stat-value"><?= $counts['pending'] ?></div>
            <div class="stat-label"><?= $isThai ? 'รออนุมัติ' : 'Pending' ?></div>
        </a>
        <a href="index.php?page=tour_agent_reg_list&status=approved" class="stat-card success <?= $statusFilter === 'approved' ? 'active' : '' ?>">
            <div class="stat-value"><?= $counts['approved'] ?></div>
            <div class="stat-label"><?= $isThai ? 'อนุมัติแล้ว' : 'Approved' ?></div>
        </a>
        <a href="index.php?page=tour_agent_reg_list&status=suspended" class="stat-card danger <?= $statusFilter === 'suspended' ? 'active' : '' ?>">
            <div class="stat-value"><?= $counts['suspended'] ?></div>
            <div class="stat-label"><?= $isThai ? 'ระงับ' : 'Suspended' ?></div>
        </a>
        <a href="index.php?page=tour_agent_reg_list&status=rejected" class="stat-card muted <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
            <div class="stat-value"><?= $counts['rejected'] ?></div>
            <div class="stat-label"><?= $isThai ? 'ปฏิเสธ' : 'Rejected' ?></div>
        </a>
    </div>

    <!-- Agents list -->
    <?php if (empty($agents)): ?>
    <div class="empty-state">
        <i class="fa fa-users"></i>
        <p><?= $isThai ? 'ยังไม่มีการลงทะเบียนตัวแทน' : 'No agent registrations yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="agent-table">
        <table>
            <thead>
                <tr>
                    <th><?= $isThai ? 'ตัวแทน' : 'Agent' ?></th>
                    <th><?= $isThai ? 'ติดต่อ' : 'Contact' ?></th>
                    <th><?= $isThai ? 'ลงทะเบียน' : 'Registered' ?></th>
                    <th><?= $isThai ? 'วิธี' : 'Via' ?></th>
                    <th><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th><?= $isThai ? 'สัญญาเริ่มต้น' : 'Default Contract' ?></th>
                    <th style="text-align:right;"><?= $isThai ? 'การจัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $a):
                    $st = $statusBadges[$a['status']] ?? $statusBadges['pending'];
                    $name = $a['agent_name_en'] ?: $a['agent_name_th'] ?: ('ID#' . $a['agent_company_id']);
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="agent-avatar"><?= mb_strtoupper(mb_substr($name, 0, 1)) ?></div>
                            <div>
                                <div class="agent-name"><?= htmlspecialchars($name) ?></div>
                                <div class="agent-detail">ID#<?= $a['agent_company_id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($a['agent_email'])): ?>
                        <div><i class="fa fa-envelope-o" style="color:#94a3b8;"></i> <?= htmlspecialchars($a['agent_email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($a['agent_phone'])): ?>
                        <div><i class="fa fa-phone" style="color:#94a3b8;"></i> <?= htmlspecialchars($a['agent_phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= date('d M Y', strtotime($a['created_at'])) ?>
                        <div class="agent-detail"><?= date('H:i', strtotime($a['created_at'])) ?></div>
                    </td>
                    <td>
                        <span class="via-badge"><?= htmlspecialchars(ucfirst($a['registered_via'])) ?></span>
                    </td>
                    <td>
                        <span class="status-badge" style="background:<?= $st[2] ?>;color:<?= $st[1] ?>;"><?= $st[0] ?></span>
                    </td>
                    <td>
                        <?php if (!empty($a['default_contract_name'])): ?>
                        <span style="color:#0d9488;"><i class="fa fa-file-text-o"></i> <?= htmlspecialchars($a['default_contract_name']) ?></span>
                        <?php else: ?>
                        <span style="color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <div class="btn-row">
                            <a href="index.php?page=tour_agent_reg_view&id=<?= $a['id'] ?>" class="btn-icon btn-view">
                                <i class="fa fa-eye"></i> <?= $isThai ? 'ดู' : 'View' ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
