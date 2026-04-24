<?php
$pageTitle = 'Tour Agents';

/**
 * Tour Agent Profiles — List Page
 * 
 * Variables from controller: $profiles, $filters, $message
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'created'   => ['✅', $isThai ? 'สร้างโปรไฟล์ตัวแทนสำเร็จ' : 'Agent profile created'],
    'updated'   => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Agent profile updated'],
    'deleted'   => ['🗑️', $isThai ? 'ลบสำเร็จ' : 'Agent profile deleted'],
    'duplicate' => ['⚠️', $isThai ? 'ตัวแทนนี้มีโปรไฟล์แล้ว' : 'This vendor already has a profile'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Profile not found'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.agent-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
.agent-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e2e8f0; }
.agent-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
.agent-table tr:hover { background: #f8fafc; }
.comm-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.comm-net { background: #dbeafe; color: #1e40af; }
.comm-pct { background: #fef3c7; color: #92400e; }
.contract-dates { font-size: 12px; color: #64748b; }
.contact-icons { display: flex; gap: 8px; }
.contact-icons a, .contact-icons span { font-size: 13px; color: #64748b; }
.contact-icons a:hover { color: #059669; }
.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; font-size: 13px; text-decoration: none; }
.action-btn:hover { background: #4f46e5; color: white; border-color: #4f46e5; }
.action-btn.danger:hover { background: #ef4444; border-color: #ef4444; }
.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 48px; display: block; margin-bottom: 16px; }
/* Filter bar — matches company list style */
.search-section { display: flex; flex-direction: column; gap: 16px; flex: 1; }
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.filter-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; color: #64748b; background: #f1f5f9; border: 2px solid transparent; text-decoration: none; transition: all 0.2s ease; }
.filter-tab:hover { background: #e2e8f0; color: #475569; text-decoration: none; }
.filter-tab.active { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); color: #fff; }
.filter-tab.active.net { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
.filter-tab.active.pct { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.filter-tab.active.expired { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.tab-count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.filter-tab:not(.active) .tab-count { background: #e2e8f0; color: #64748b; }
.action-toolbar { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; }
.action-buttons-group { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.btn-clear { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; color: #ef4444; background: #fef2f2; border: 2px solid #fecaca; text-decoration: none; }
.btn-clear:hover { background: #fee2e2; border-color: #fca5a5; text-decoration: none; color: #dc2626; }
@media (max-width: 768px) { .action-toolbar { flex-direction: column; align-items: stretch; } .filter-tabs { overflow-x: auto; flex-wrap: nowrap; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-handshake-o"></i> <?= $xml->touragents ?? ($isThai ? 'ตัวแทน' : 'Tour Agents') ?></h2>
                <p><?= $isThai ? 'จัดการโปรไฟล์ตัวแทนทัวร์ ค่าคอมมิชชั่น และสัญญา' : 'Manage tour agent profiles, commissions, and contracts' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'การจอง' : 'Bookings' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card primary">
            <i class="fa fa-handshake-o stat-icon"></i>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label"><?= $isThai ? 'ตัวแทนทั้งหมด' : 'Total Agents' ?></div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-tag stat-icon"></i>
            <div class="stat-value"><?= $stats['net_rate'] ?></div>
            <div class="stat-label"><?= $isThai ? 'Net Rate' : 'Net Rate' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-percent stat-icon"></i>
            <div class="stat-value"><?= $stats['percentage'] ?></div>
            <div class="stat-label"><?= $isThai ? 'คอมมิชชั่น %' : 'Percentage' ?></div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-label"><?= $isThai ? 'สัญญามีผล' : 'Active Contracts' ?></div>
        </div>
    </div>

    <!-- Search + Filter Tabs -->
    <?php
    $search         = $filters['search'] ?? '';
    $commType       = $filters['commission_type'] ?? '';
    $contractStatus = $filters['contract_status'] ?? '';
    $hasFilter      = $search !== '' || $commType !== '' || $contractStatus !== '';
    $baseUrl        = 'index.php?page=tour_agent_list';
    ?>
    <div class="action-toolbar">
        <div class="search-section">
            <div class="md-search-box md-search-has-btn" style="max-width:500px;">
                <i class="fa fa-search md-search-icon"></i>
                <form method="get" action="">
                    <input type="hidden" name="page" value="tour_agent_list">
                    <input type="hidden" name="commission_type" value="<?= htmlspecialchars($commType) ?>">
                    <input type="hidden" name="contract_status" value="<?= htmlspecialchars($contractStatus) ?>">
                    <input type="text" class="md-search-input" name="search"
                           placeholder="<?= $isThai ? 'ค้นหาชื่อ, โทร...' : 'Search name, phone...' ?>"
                           value="<?= htmlspecialchars($search) ?>"
                           autocomplete="off">
                    <button type="submit" class="md-search-btn"><i class="fa fa-arrow-right"></i></button>
                </form>
            </div>

            <div class="filter-tabs">
                <a href="<?= $baseUrl ?>&search=<?= urlencode($search) ?>"
                   class="filter-tab <?= ($commType === '' && $contractStatus === '') ? 'active' : '' ?>">
                    <i class="fa fa-th-list"></i>
                    <span><?= $isThai ? 'ทั้งหมด' : 'All' ?></span>
                    <span class="tab-count"><?= $stats['total'] ?></span>
                </a>
                <a href="<?= $baseUrl ?>&search=<?= urlencode($search) ?>&commission_type=net_rate"
                   class="filter-tab <?= $commType === 'net_rate' ? 'active net' : '' ?>">
                    <i class="fa fa-tag"></i>
                    <span><?= $isThai ? 'Net Rate' : 'Net Rate' ?></span>
                    <span class="tab-count"><?= $stats['net_rate'] ?></span>
                </a>
                <a href="<?= $baseUrl ?>&search=<?= urlencode($search) ?>&commission_type=percentage"
                   class="filter-tab <?= $commType === 'percentage' ? 'active pct' : '' ?>">
                    <i class="fa fa-percent"></i>
                    <span><?= $isThai ? 'คอมมิชชั่น %' : 'Percentage' ?></span>
                    <span class="tab-count"><?= $stats['percentage'] ?></span>
                </a>
                <a href="<?= $baseUrl ?>&search=<?= urlencode($search) ?>&contract_status=active"
                   class="filter-tab <?= $contractStatus === 'active' ? 'active' : '' ?>">
                    <i class="fa fa-check-circle"></i>
                    <span><?= $isThai ? 'สัญญามีผล' : 'Active' ?></span>
                    <span class="tab-count"><?= $stats['active'] ?></span>
                </a>
                <a href="<?= $baseUrl ?>&search=<?= urlencode($search) ?>&contract_status=expired"
                   class="filter-tab <?= $contractStatus === 'expired' ? 'active expired' : '' ?>">
                    <i class="fa fa-times-circle"></i>
                    <span><?= $isThai ? 'หมดสัญญา' : 'Expired' ?></span>
                    <span class="tab-count"><?= $stats['expired'] ?></span>
                </a>
            </div>
        </div>

        <div class="action-buttons-group">
            <?php if ($hasFilter): ?>
            <a href="<?= $baseUrl ?>" class="btn-clear">
                <i class="fa fa-times"></i>
                <span><?= $isThai ? 'ล้าง' : 'Clear' ?></span>
            </a>
            <?php endif; ?>
            <a href="index.php?page=tour_agent_make" class="btn btn-add">
                <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มตัวแทน' : 'Add Agent' ?>
            </a>
        </div>
    </div>

    <!-- Table -->
    <div style="background:white; border-radius:14px; border:1px solid #e2e8f0; overflow-x:auto;">
        <?php if (empty($profiles)): ?>
        <div class="empty-state">
            <i class="fa fa-handshake-o"></i>
            <?= $isThai ? 'ยังไม่มีตัวแทนทัวร์' : 'No tour agents yet' ?><br>
            <a href="index.php?page=tour_agent_make" style="color:#0d9488; margin-top:12px; display:inline-block;">
                <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มตัวแทนรายแรก' : 'Add your first agent' ?>
            </a>
        </div>
        <?php else: ?>
        <table class="agent-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $isThai ? 'ชื่อตัวแทน' : 'Agent Name' ?></th>
                    <th><?= $isThai ? 'ผู้ติดต่อ' : 'Contact' ?></th>
                    <th><?= $xml->tourcommissiontype ?? ($isThai ? 'ประเภทคอมฯ' : 'Commission') ?></th>
                    <th><?= $isThai ? 'ผู้ใหญ่ / เด็ก' : 'Adult / Child' ?></th>
                    <th><?= $isThai ? 'สัญญา' : 'Contract' ?></th>
                    <th><?= $isThai ? 'ช่องทาง' : 'Channels' ?></th>
                    <th style="width:100px; text-align:center;"><?= $isThai ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($isThai && $p['name_th'] ? $p['name_th'] : $p['name_en']) ?></strong>
                        <?php if ($p['email']): ?>
                        <div style="font-size:12px; color:#64748b;"><?= htmlspecialchars($p['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($p['contact'] ?: '-') ?>
                        <?php if ($p['phone']): ?>
                        <div style="font-size:12px; color:#64748b;"><i class="fa fa-phone"></i> <?= htmlspecialchars($p['phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['commission_type'] === 'net_rate'): ?>
                        <span class="comm-badge comm-net"><?= $xml->tournetrate ?? 'Net Rate' ?></span>
                        <?php else: ?>
                        <span class="comm-badge comm-pct"><?= $xml->tourpercentage ?? '%' ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= number_format($p['commission_adult'], 2) ?>
                        / <?= number_format($p['commission_child'], 2) ?>
                    </td>
                    <td class="contract-dates">
                        <?php if ($p['contract_start'] || $p['contract_end']): ?>
                        <?= $p['contract_start'] ? date('d/m/Y', strtotime($p['contract_start'])) : '—' ?>
                        → <?= $p['contract_end'] ? date('d/m/Y', strtotime($p['contract_end'])) : '—' ?>
                        <?php else: ?>
                        <span style="color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="contact-icons">
                            <?php if ($p['contact_line']): ?>
                            <span title="LINE: <?= htmlspecialchars($p['contact_line']) ?>"><i class="fa fa-comment-o"></i></span>
                            <?php endif; ?>
                            <?php if ($p['contact_whatsapp']): ?>
                            <span title="WhatsApp: <?= htmlspecialchars($p['contact_whatsapp']) ?>"><i class="fa fa-whatsapp"></i></span>
                            <?php endif; ?>
                            <?php if (!$p['contact_line'] && !$p['contact_whatsapp']): ?>
                            <span style="color:#cbd5e1;">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center;">
                            <a href="index.php?page=tour_agent_make&id=<?= $p['id'] ?>" class="action-btn" title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></a>
                            <form method="post" action="index.php?page=tour_agent_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบตัวแทนนี้?' : 'Delete this agent?' ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="action-btn danger" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
