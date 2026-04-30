<?php
$pageTitle = 'Agent Portal — Products';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'resynced' => ['✅', $isThai ? 'ซิงค์แคตตาล็อกสำเร็จ' : 'Catalog resynced successfully'],
    'error'    => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
];

// Group products by operator
$byOperator = [];
foreach ($products as $p) {
    $opId = $p['operator_company_id'];
    if (!isset($byOperator[$opId])) {
        $byOperator[$opId] = [
            'name' => $p['operator_name'],
            'products' => [],
        ];
    }
    $byOperator[$opId]['products'][] = $p;
}
?>

<style>
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.product-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; transition: box-shadow .15s; }
.product-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.product-name { font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 4px; }
.product-type { font-size: 11px; color: #0d9488; background: #f0fdfa; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-bottom: 8px; }
.product-desc { font-size: 12px; color: #64748b; line-height: 1.5; }
.product-contract { font-size: 11px; color: #94a3b8; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; }

.operator-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.operator-section h3 { margin: 0 0 16px; font-size: 16px; color: #1e293b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.operator-section h3 .count { font-size: 12px; background: #f0fdfa; color: #0d9488; padding: 2px 10px; border-radius: 12px; margin-left: 8px; font-weight: 500; }

.filter-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.filter-bar select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; min-width: 200px; }
.btn-resync { padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-resync:hover { background: #1d4ed8; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-cubes"></i> <?= $isThai ? 'แคตตาล็อกสินค้า' : 'Product Catalog' ?></h2>
                <p><?= $isThai ? 'สินค้าที่ซิงค์มาจากผู้ประกอบการ' : 'Products synced from your operators' ?></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Filter + Resync -->
    <div class="filter-bar">
        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="agent_portal_products">
            <label style="font-size:13px;color:#64748b;"><?= $isThai ? 'กรองตามผู้ประกอบการ:' : 'Filter by operator:' ?></label>
            <select name="operator_id" onchange="this.form.submit()">
                <option value=""><?= $isThai ? '— ทั้งหมด —' : '— All —' ?></option>
                <?php foreach ($operators as $op): ?>
                <option value="<?= $op['operator_company_id'] ?>" <?= ($_GET['operator_id'] ?? '') == $op['operator_company_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($op['operator_name_en'] ?: $op['operator_name_th']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <form method="post" action="index.php?page=agent_portal_resync"
              onsubmit="return confirm('<?= $isThai ? 'รีเฟรชแคตตาล็อกสินค้า?' : 'Refresh product catalog?' ?>')">
            <?= csrf_field() ?>
            <button type="submit" class="btn-resync">
                <i class="fa fa-refresh"></i> <?= $isThai ? 'ซิงค์ใหม่' : 'Resync' ?>
            </button>
        </form>
    </div>

    <!-- Products grouped by operator -->
    <?php if (empty($byOperator)): ?>
    <div class="empty-state">
        <i class="fa fa-cubes"></i>
        <p><?= $isThai ? 'ยังไม่มีสินค้าในแคตตาล็อก กดปุ่ม Resync เพื่อรีเฟรช' : 'No products in catalog. Click Resync to refresh.' ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($byOperator as $opId => $opData): ?>
    <div class="operator-section">
        <h3>
            <i class="fa fa-handshake-o"></i> <?= htmlspecialchars($opData['name']) ?>
            <span class="count"><?= count($opData['products']) ?> <?= $isThai ? 'สินค้า' : 'products' ?></span>
        </h3>

        <div class="product-grid">
            <?php foreach ($opData['products'] as $p): ?>
            <div class="product-card">
                <div class="product-type"><?= htmlspecialchars($p['type_name'] ?: 'Product') ?></div>
                <div class="product-name"><?= htmlspecialchars($p['model_name']) ?></div>
                <?php if (!empty($p['model_desc'])): ?>
                <div class="product-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($p['model_desc'], 0, 140, '…'))) ?></div>
                <?php endif; ?>
                <div class="product-contract">
                    <i class="fa fa-file-text-o"></i> <?= htmlspecialchars($p['contract_name']) ?>
                    · <?= $isThai ? 'ซิงค์เมื่อ' : 'synced' ?> <?= date('d M', strtotime($p['synced_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
