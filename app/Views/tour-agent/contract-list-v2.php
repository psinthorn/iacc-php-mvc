<?php
$pageTitle = 'Tour Operator — Contracts';

/**
 * Operator-Level Contract List (v2)
 *
 * Variables: $contracts, $defaultContractId, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'created'        => ['✅', $isThai ? 'สร้างสัญญาสำเร็จ'           : 'Contract created successfully'],
    'updated'        => ['✅', $isThai ? 'อัปเดตสัญญาสำเร็จ'          : 'Contract updated successfully'],
    'deleted'        => ['✅', $isThai ? 'ลบสัญญาสำเร็จ'              : 'Contract deleted successfully'],
    'cloned'         => ['✅', $isThai ? 'คัดลอกสัญญาสำเร็จ'          : 'Contract cloned successfully'],
    'default_set'    => ['✅', $isThai ? 'ตั้งเป็นสัญญาหลักสำเร็จ'     : 'Default contract set successfully'],
    'not_found'      => ['⚠️', $isThai ? 'ไม่พบสัญญา'                : 'Contract not found'],
    'error'          => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด'            : 'An error occurred'],
];

$statusLabels = [
    'draft'     => [$isThai ? 'ร่าง'      : 'Draft',     '#94a3b8', '#f1f5f9'],
    'active'    => [$isThai ? 'ใช้งาน'     : 'Active',    '#059669', '#ecfdf5'],
    'expired'   => [$isThai ? 'หมดอายุ'    : 'Expired',   '#d97706', '#fffbeb'],
    'cancelled' => [$isThai ? 'ยกเลิก'     : 'Cancelled', '#dc2626', '#fef2f2'],
];

// Stats
$totalContracts = count($contracts);
$activeContracts = count(array_filter($contracts, fn($c) => $c['status'] === 'active'));
$draftContracts  = count(array_filter($contracts, fn($c) => $c['status'] === 'draft'));
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.contracts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 16px; }
.contract-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; transition: box-shadow .2s; position: relative; }
.contract-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.contract-card.is-default { border-left: 4px solid #0d9488; }
.cc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.cc-title { font-size: 15px; font-weight: 600; color: #1e293b; }
.cc-number { font-size: 12px; color: #94a3b8; font-family: monospace; margin-top: 2px; }
.cc-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.cc-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px; color: #64748b; margin-bottom: 14px; }
.cc-meta dt { font-weight: 500; color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
.cc-meta dd { margin: 0 0 6px; }
.cc-stats { display: flex; gap: 16px; padding: 12px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #64748b; }
.cc-stats .stat-num { font-weight: 700; color: #0d9488; }
.cc-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
.cc-actions a, .cc-actions button { padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
.btn-cc-edit { background: #f0fdfa; color: #0d9488; }
.btn-cc-edit:hover { background: #ccfbf1; }
.btn-cc-clone { background: #eff6ff; color: #2563eb; }
.btn-cc-clone:hover { background: #dbeafe; }
.btn-cc-default { background: #fefce8; color: #ca8a04; }
.btn-cc-default:hover { background: #fef9c3; }
.btn-cc-delete { background: #fef2f2; color: #dc2626; }
.btn-cc-delete:hover { background: #fee2e2; }
.empty-state { text-align: center; padding: 48px; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: center; }
.stat-card .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
.stat-card .stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }
.stat-card.primary { border-left: 3px solid #0d9488; }
.stat-card.success { border-left: 3px solid #059669; }
.stat-card.warning { border-left: 3px solid #d97706; }
.default-star { color: #f59e0b; font-size: 14px; margin-left: 4px; }
@media (max-width: 768px) { .contracts-grid { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= $isThai ? 'สัญญาผู้ประกอบการ' : 'Operator Contracts' ?></h2>
                <p><?= $isThai ? 'จัดการสัญญาระดับบริษัท กำหนดอัตราตามฤดูกาล และมอบหมายตัวแทน' : 'Manage company-level contracts, season rates, and agent assignments' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <a href="index.php?page=tour_contract_make" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างสัญญาใหม่' : 'New Contract' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php if ($totalContracts > 0): ?>
    <div class="stats-row">
        <div class="stat-card primary">
            <div class="stat-value"><?= $totalContracts ?></div>
            <div class="stat-label"><?= $isThai ? 'สัญญาทั้งหมด' : 'Total Contracts' ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-value"><?= $activeContracts ?></div>
            <div class="stat-label"><?= $isThai ? 'ใช้งาน' : 'Active' ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value"><?= $draftContracts ?></div>
            <div class="stat-label"><?= $isThai ? 'ร่าง' : 'Draft' ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($contracts)): ?>
    <div class="empty-state">
        <i class="fa fa-file-text-o"></i>
        <p><?= $isThai ? 'ยังไม่มีสัญญาระดับผู้ประกอบการ' : 'No operator contracts yet.' ?></p>
        <a href="index.php?page=tour_contract_make" style="color:#0d9488;font-weight:600;">
            <i class="fa fa-plus"></i> <?= $isThai ? 'สร้างสัญญาแรก' : 'Create first contract' ?>
        </a>
    </div>
    <?php else: ?>
    <div class="contracts-grid">
        <?php foreach ($contracts as $c):
            $st = $statusLabels[$c['status']] ?? $statusLabels['draft'];
            $isDefault = ((int)$c['id'] === (int)$defaultContractId);
        ?>
        <div class="contract-card <?= $isDefault ? 'is-default' : '' ?>">
            <div class="cc-header">
                <div>
                    <div class="cc-title">
                        <?= htmlspecialchars($c['contract_name'] ?: ($isThai ? 'ไม่มีชื่อ' : 'Untitled')) ?>
                        <?php if ($isDefault): ?>
                        <span class="default-star" title="<?= $isThai ? 'สัญญาหลัก' : 'Default Contract' ?>"><i class="fa fa-star"></i></span>
                        <?php endif; ?>
                    </div>
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
                    <dt><?= $isThai ? 'เครดิต' : 'Credit' ?></dt>
                    <dd>
                        <?php if (($c['credit_days'] ?? 0) > 0): ?>
                            <?= $c['credit_days'] ?> <?= $isThai ? 'วัน' : 'days' ?>
                        <?php else: ?>
                            <span style="color:#cbd5e1;">—</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if (!empty($c['payment_terms'])): ?>
                <div>
                    <dt><?= $isThai ? 'เงื่อนไขชำระ' : 'Payment' ?></dt>
                    <dd><?= htmlspecialchars($c['payment_terms']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (($c['deposit_pct'] ?? 0) > 0): ?>
                <div>
                    <dt><?= $isThai ? 'มัดจำ' : 'Deposit' ?></dt>
                    <dd><?= number_format($c['deposit_pct'], 1) ?>%</dd>
                </div>
                <?php endif; ?>
            </dl>

            <div class="cc-stats">
                <span><span class="stat-num"><?= $c['agent_count'] ?? 0 ?></span> <?= $isThai ? 'ตัวแทน' : 'Agents' ?></span>
                <span><span class="stat-num"><?= $c['season_count'] ?? 0 ?></span> <?= $isThai ? 'ช่วงเวลา' : 'Seasons' ?></span>
                <span><span class="stat-num"><?= $c['rate_count'] ?? 0 ?></span> <?= $isThai ? 'อัตรา' : 'Rates' ?></span>
            </div>

            <div class="cc-actions">
                <a href="index.php?page=tour_contract_make&contract_id=<?= $c['id'] ?>" class="btn-cc-edit">
                    <i class="fa fa-pencil"></i> <?= $isThai ? 'แก้ไข' : 'Edit' ?>
                </a>
                <?php if (!$isDefault): ?>
                <form method="post" action="index.php?page=tour_contract_default" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn-cc-default" title="<?= $isThai ? 'ตั้งเป็นสัญญาหลัก' : 'Set as default' ?>">
                        <i class="fa fa-star-o"></i> <?= $isThai ? 'ตั้งหลัก' : 'Default' ?>
                    </button>
                </form>
                <?php endif; ?>
                <form method="post" action="index.php?page=tour_contract_clone" style="display:inline;"
                      onsubmit="var n=prompt('<?= $isThai ? 'ชื่อสัญญาใหม่:' : 'New contract name:' ?>','<?= htmlspecialchars(($c['contract_name'] ?? '') . ' (Copy)') ?>');if(!n)return false;this.querySelector('[name=new_name]').value=n;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="new_name" value="">
                    <button type="submit" class="btn-cc-clone">
                        <i class="fa fa-copy"></i> <?= $isThai ? 'คัดลอก' : 'Clone' ?>
                    </button>
                </form>
                <?php if (!$isDefault): ?>
                <form method="post" action="index.php?page=tour_contract_delete" style="display:inline;"
                      onsubmit="return confirm('<?= $isThai ? 'ลบสัญญานี้? ข้อมูลอัตราและการมอบหมายตัวแทนจะถูกลบด้วย' : 'Delete this contract? Rates and agent assignments will also be removed.' ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn-cc-delete">
                        <i class="fa fa-trash-o"></i> <?= $isThai ? 'ลบ' : 'Delete' ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
