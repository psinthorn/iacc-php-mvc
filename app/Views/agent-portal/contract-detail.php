<?php
$pageTitle = 'Agent Portal — Contract Detail';
$isThai = ($_SESSION['lang'] ?? '0') === '1';

// Group rates by season
$ratesBySeason = [];
foreach ($rates as $r) {
    $key = $r['season_name'] ?: '__base__';
    if (!isset($ratesBySeason[$key])) {
        $ratesBySeason[$key] = [
            'season_name' => $r['season_name'],
            'season_start' => $r['season_start'] ?? null,
            'season_end' => $r['season_end'] ?? null,
            'priority' => $r['priority'] ?? 0,
            'rates' => [],
        ];
    }
    $ratesBySeason[$key]['rates'][] = $r;
}
?>

<style>
.contract-summary { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
.contract-summary h2 { margin: 0 0 8px; font-size: 20px; color: #1e293b; }
.contract-summary .operator { font-size: 14px; color: #0d9488; font-weight: 500; margin-bottom: 16px; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
.summary-item dt { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; }
.summary-item dd { margin: 4px 0 0; font-size: 14px; color: #334155; font-weight: 500; }

.season-block { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.season-block.is-base { border-left: 3px solid #0d9488; }
.season-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.season-name { font-size: 15px; font-weight: 600; color: #1e293b; }
.season-period { font-size: 12px; color: #64748b; }

.rate-table { width: 100%; border-collapse: collapse; }
.rate-table th { background: #f8fafc; padding: 10px 12px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid #e2e8f0; }
.rate-table th.text-right { text-align: right; }
.rate-table td { padding: 10px 12px; border-bottom: 1px solid #f8fafc; font-size: 13px; color: #334155; }
.rate-table td.text-right { text-align: right; font-family: monospace; }
.rate-table tr:last-child td { border-bottom: none; }
.rate-table tr:hover td { background: #f8fafc; }
.type-name { color: #0d9488; font-weight: 500; font-size: 11px; }
.rate-type-badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; }
.rate-type-net { background: #eff6ff; color: #2563eb; }
.rate-type-pct { background: #fef3c7; color: #d97706; }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= htmlspecialchars($contract['contract_name']) ?></h2>
                <p><?= htmlspecialchars($contract['operator_name'] ?? '') ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=agent_portal_contracts" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_shared.php'; ?>

    <!-- Contract summary -->
    <div class="contract-summary">
        <div class="summary-grid">
            <div class="summary-item">
                <dt><?= $isThai ? 'เลขที่สัญญา' : 'Contract #' ?></dt>
                <dd style="font-family:monospace;"><?= htmlspecialchars($contract['contract_number'] ?? '—') ?></dd>
            </div>
            <div class="summary-item">
                <dt><?= $isThai ? 'ระยะเวลา' : 'Period' ?></dt>
                <dd>
                    <?php if (!empty($contract['valid_from']) && !empty($contract['valid_to'])): ?>
                        <?= date('d M Y', strtotime($contract['valid_from'])) ?> — <?= date('d M Y', strtotime($contract['valid_to'])) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </dd>
            </div>
            <?php if (!empty($contract['payment_terms'])): ?>
            <div class="summary-item">
                <dt><?= $isThai ? 'การชำระ' : 'Payment' ?></dt>
                <dd><?= htmlspecialchars($contract['payment_terms']) ?></dd>
            </div>
            <?php endif; ?>
            <?php if (($contract['credit_days'] ?? 0) > 0): ?>
            <div class="summary-item">
                <dt><?= $isThai ? 'เครดิต' : 'Credit Days' ?></dt>
                <dd><?= $contract['credit_days'] ?> <?= $isThai ? 'วัน' : 'days' ?></dd>
            </div>
            <?php endif; ?>
            <?php if (($contract['deposit_pct'] ?? 0) > 0): ?>
            <div class="summary-item">
                <dt><?= $isThai ? 'ค่ามัดจำ' : 'Deposit' ?></dt>
                <dd><?= number_format($contract['deposit_pct'], 1) ?>%</dd>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($contract['conditions'])): ?>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9;">
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                <?= $isThai ? 'เงื่อนไขพิเศษ' : 'Special Conditions' ?>
            </div>
            <div style="font-size:13px;color:#334155;white-space:pre-wrap;"><?= htmlspecialchars($contract['conditions']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rates by season -->
    <?php if (empty($ratesBySeason)): ?>
    <div class="empty-state">
        <i class="fa fa-money"></i>
        <p><?= $isThai ? 'ยังไม่มีอัตราในสัญญานี้' : 'No rates set in this contract yet.' ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($ratesBySeason as $key => $season):
        $isBase = ($key === '__base__');
    ?>
    <div class="season-block <?= $isBase ? 'is-base' : '' ?>">
        <div class="season-header">
            <div>
                <div class="season-name">
                    <i class="fa <?= $isBase ? 'fa-home' : 'fa-sun-o' ?>"></i>
                    <?= $isBase ? ($isThai ? 'อัตราฐาน' : 'Base Rate') : htmlspecialchars($season['season_name']) ?>
                </div>
                <?php if (!$isBase && (!empty($season['season_start']) || !empty($season['season_end']))): ?>
                <div class="season-period">
                    <?= !empty($season['season_start']) ? date('d M Y', strtotime($season['season_start'])) : '' ?>
                    <?= !empty($season['season_start']) && !empty($season['season_end']) ? ' — ' : '' ?>
                    <?= !empty($season['season_end']) ? date('d M Y', strtotime($season['season_end'])) : '' ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!$isBase && ($season['priority'] ?? 0) > 0): ?>
            <span style="font-size:11px;background:#f0fdfa;color:#0d9488;padding:3px 10px;border-radius:6px;">
                <?= $isThai ? 'ลำดับ' : 'Priority' ?> <?= $season['priority'] ?>
            </span>
            <?php endif; ?>
        </div>

        <table class="rate-table">
            <thead>
                <tr>
                    <th><?= $isThai ? 'สินค้า' : 'Product' ?></th>
                    <th><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                    <th class="text-right"><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?></th>
                    <th class="text-right"><?= $isThai ? 'เด็ก' : 'Child' ?></th>
                    <th class="text-right"><?= $isThai ? 'เข้าชม (ผู้ใหญ่)' : 'Entrance (Adult)' ?></th>
                    <th class="text-right"><?= $isThai ? 'เข้าชม (เด็ก)' : 'Entrance (Child)' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($season['rates'] as $r):
                    $rt = $r['rate_type'] ?? 'net_rate';
                    $sym = $rt === 'percentage' ? '%' : '฿';
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($r['model_name']) ?></strong>
                        <span class="rate-type-badge <?= $rt === 'net_rate' ? 'rate-type-net' : 'rate-type-pct' ?>"><?= $rt === 'net_rate' ? ($isThai ? 'สุทธิ' : 'Net') : '%' ?></span>
                    </td>
                    <td><span class="type-name"><?= htmlspecialchars($r['type_name'] ?? '—') ?></span></td>
                    <td class="text-right"><?= $sym ?><?= number_format(floatval($r['adult_default']), 2) ?></td>
                    <td class="text-right"><?= $sym ?><?= number_format(floatval($r['child_default']), 2) ?></td>
                    <td class="text-right"><?= $sym ?><?= number_format(floatval($r['entrance_adult_default']), 2) ?></td>
                    <td class="text-right"><?= $sym ?><?= number_format(floatval($r['entrance_child_default']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
