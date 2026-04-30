<?php
$pageTitle = 'Agent Portal — Documents';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'not_found'    => ['⚠️', $isThai ? 'ไม่พบเอกสาร' : 'Document not found'],
    'file_missing' => ['⚠️', $isThai ? 'ไฟล์ไม่พบในระบบ' : 'File missing on server'],
];

$categoryLabels = [
    'contract'   => [$isThai ? 'สัญญา'      : 'Contract',   '#0d9488', 'fa-file-text-o'],
    'brochure'   => [$isThai ? 'โบรชัวร์'    : 'Brochure',   '#2563eb', 'fa-image'],
    'terms'      => [$isThai ? 'ข้อกำหนด'   : 'Terms',      '#d97706', 'fa-gavel'],
    'rate_sheet' => [$isThai ? 'ใบเสนอราคา' : 'Rate Sheet', '#7c3aed', 'fa-money'],
    'other'      => [$isThai ? 'อื่นๆ'       : 'Other',      '#94a3b8', 'fa-file-o'],
];

function fmtSize2($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

// Group by operator
$byOperator = [];
foreach ($documents as $d) {
    $op = $d['operator_name'] ?: '—';
    $byOperator[$op][] = $d;
}
?>

<style>
.operator-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.operator-section h3 { margin: 0 0 16px; font-size: 16px; color: #1e293b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.operator-section h3 .count { font-size: 12px; background: #f0fdfa; color: #0d9488; padding: 2px 10px; border-radius: 12px; margin-left: 8px; font-weight: 500; }

.doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
.doc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; transition: all .15s; display: flex; gap: 14px; }
.doc-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); border-color: #0d9488; }
.doc-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; flex-shrink: 0; }
.doc-info { flex: 1; min-width: 0; }
.doc-title { font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 4px; word-break: break-word; }
.doc-desc { font-size: 12px; color: #64748b; line-height: 1.4; margin-bottom: 8px; }
.doc-meta { font-size: 11px; color: #94a3b8; }
.doc-actions { margin-top: 10px; }
.btn-download { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f0fdfa; color: #0d9488; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 500; }
.btn-download:hover { background: #ccfbf1; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-folder-open-o"></i> <?= $isThai ? 'เอกสาร' : 'Documents' ?></h2>
                <p><?= $isThai ? 'เอกสารที่ผู้ประกอบการแชร์กับคุณ' : 'Documents shared by your operators' ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <?php if (empty($byOperator)): ?>
    <div class="empty-state">
        <i class="fa fa-folder-open-o"></i>
        <p><?= $isThai ? 'ยังไม่มีเอกสารที่แชร์กับคุณ' : 'No documents shared with you yet.' ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($byOperator as $opName => $opDocs): ?>
    <div class="operator-section">
        <h3>
            <i class="fa fa-handshake-o"></i> <?= htmlspecialchars($opName) ?>
            <span class="count"><?= count($opDocs) ?> <?= $isThai ? 'ไฟล์' : 'files' ?></span>
        </h3>

        <div class="doc-grid">
            <?php foreach ($opDocs as $d):
                $cat = $categoryLabels[$d['category']] ?? $categoryLabels['other'];
            ?>
            <div class="doc-card">
                <div class="doc-icon" style="background:<?= $cat[1] ?>;">
                    <i class="fa <?= $cat[2] ?>"></i>
                </div>
                <div class="doc-info">
                    <div class="doc-title"><?= htmlspecialchars($d['title']) ?></div>
                    <?php if (!empty($d['description'])): ?>
                    <div class="doc-desc"><?= htmlspecialchars(mb_strimwidth($d['description'], 0, 100, '…')) ?></div>
                    <?php endif; ?>
                    <div class="doc-meta">
                        <span style="color:<?= $cat[1] ?>;font-weight:500;"><?= $cat[0] ?></span>
                        · <?= fmtSize2($d['file_size']) ?>
                        · <?= date('d M Y', strtotime($d['created_at'])) ?>
                        <?php if (!empty($d['contract_name'])): ?>
                        <div style="margin-top:3px;font-size:11px;"><i class="fa fa-file-text-o"></i> <?= htmlspecialchars($d['contract_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="doc-actions">
                        <a href="index.php?page=agent_portal_doc_download&id=<?= $d['id'] ?>" class="btn-download">
                            <i class="fa fa-download"></i> <?= $isThai ? 'ดาวน์โหลด' : 'Download' ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
