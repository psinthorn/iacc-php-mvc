<?php
$pageTitle = 'Agent Portal — Contracts';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'not_found' => ['⚠️', $isThai ? 'ไม่พบสัญญา' : 'Contract not found'],
];

$statusBadges = [
    'draft'     => [$isThai ? 'ร่าง' : 'Draft', '#94a3b8', '#f1f5f9'],
    'active'    => [$isThai ? 'ใช้งาน' : 'Active', '#059669', '#ecfdf5'],
    'expired'   => [$isThai ? 'หมดอายุ' : 'Expired', '#d97706', '#fffbeb'],
    'cancelled' => [$isThai ? 'ยกเลิก' : 'Cancelled', '#dc2626', '#fef2f2'],
];
?>

<style>
.contracts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 16px; }
.contract-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; transition: box-shadow .2s; }
.contract-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.cc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.cc-title { font-size: 15px; font-weight: 600; color: #1e293b; }
.cc-operator { font-size: 12px; color: #0d9488; font-weight: 500; margin-top: 2px; }
.cc-number { font-size: 11px; color: #94a3b8; font-family: monospace; margin-top: 4px; }
.cc-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.cc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 14px; }
.cc-meta dt { font-weight: 500; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
.cc-meta dd { margin: 0 0 4px; }
.cc-stats { display: flex; gap: 16px; padding: 12px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #64748b; }
.cc-stats .stat-num { font-weight: 700; color: #0d9488; }
.cc-actions { padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: right; }
.btn-view { display: inline-block; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; background: #f0fdfa; color: #0d9488; text-decoration: none; }
.btn-view:hover { background: #ccfbf1; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= $isThai ? 'สัญญาของคุณ' : 'My Contracts' ?></h2>
                <p><?= $isThai ? 'สัญญาที่คุณได้รับมอบหมายจากผู้ประกอบการ' : 'Contracts assigned to you by operators' ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <?php if (empty($contracts)): ?>
    <div class="empty-state">
        <i class="fa fa-file-text-o"></i>
        <p><?= $isThai ? 'ยังไม่มีสัญญาที่มอบหมาย' : 'No contracts assigned yet.' ?></p>
    </div>
    <?php else: ?>
    <div class="contracts-grid">
        <?php foreach ($contracts as $c):
            $st = $statusBadges[$c['status']] ?? $statusBadges['draft'];
        ?>
        <div class="contract-card">
            <div class="cc-header">
                <div>
                    <div class="cc-title"><?= htmlspecialchars($c['contract_name'] ?: ($isThai ? 'ไม่มีชื่อ' : 'Untitled')) ?></div>
                    <div class="cc-operator"><i class="fa fa-handshake-o"></i> <?= htmlspecialchars($c['operator_name'] ?? '—') ?></div>
                    <div class="cc-number"><?= htmlspecialchars($c['contract_number'] ?? '') ?></div>
                </div>
                <span class="cc-badge" style="background:<?= $st[2] ?>;color:<?= $st[1] ?>;"><?= $st[0] ?></span>
            </div>

            <dl class="cc-meta">
                <div>
                    <dt><?= $isThai ? 'ระยะเวลา' : 'Period' ?></dt>
                    <dd>
                        <?php if (!empty($c['valid_from']) && !empty($c['valid_to'])): ?>
                            <?= date('d M Y', strtotime($c['valid_from'])) ?> — <?= date('d M Y', strtotime($c['valid_to'])) ?>
                        <?php else: ?>
                            <span style="color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt><?= $isThai ? 'มอบหมายเมื่อ' : 'Assigned' ?></dt>
                    <dd><?= date('d M Y', strtotime($c['assigned_at'])) ?></dd>
                </div>
                <?php if (!empty($c['payment_terms'])): ?>
                <div>
                    <dt><?= $isThai ? 'เงื่อนไขชำระ' : 'Payment' ?></dt>
                    <dd><?= htmlspecialchars($c['payment_terms']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (($c['credit_days'] ?? 0) > 0): ?>
                <div>
                    <dt><?= $isThai ? 'เครดิต' : 'Credit' ?></dt>
                    <dd><?= $c['credit_days'] ?> <?= $isThai ? 'วัน' : 'days' ?></dd>
                </div>
                <?php endif; ?>
            </dl>

            <div class="cc-stats">
                <span><span class="stat-num"><?= $c['rate_count'] ?></span> <?= $isThai ? 'อัตรา' : 'Rates' ?></span>
                <span><span class="stat-num"><?= $c['season_count'] ?></span> <?= $isThai ? 'ช่วงเวลา' : 'Seasons' ?></span>
            </div>

            <div class="cc-actions">
                <a href="index.php?page=agent_portal_contract&contract_id=<?= $c['id'] ?>" class="btn-view">
                    <i class="fa fa-eye"></i> <?= $isThai ? 'ดูอัตรา' : 'View Rates' ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
