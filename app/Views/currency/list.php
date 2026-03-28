<?php
/**
 * Currency Management — Modern UI
 * 
 * Uses master-data.css design system
 * Variables from controller: $currencies, $activeCurrencies, $defaultCurrency, $message, $lang
 */

$activeMap = array_column($activeCurrencies, null, 'code');
$activeCount = count($activeCurrencies);
$totalCount = count($currencies);

// Flag emoji map (decorative)
$flags = [
    'THB'=>'🇹🇭','USD'=>'🇺🇸','EUR'=>'🇪🇺','GBP'=>'🇬🇧','JPY'=>'🇯🇵',
    'CNY'=>'🇨🇳','SGD'=>'🇸🇬','MYR'=>'🇲🇾','KRW'=>'🇰🇷','AUD'=>'🇦🇺',
    'CAD'=>'🇨🇦','CHF'=>'🇨🇭','HKD'=>'🇭🇰','NZD'=>'🇳🇿','INR'=>'🇮🇳',
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.currency-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 16px;
}
.currency-card {
    background: white;
    border-radius: 14px;
    padding: 20px;
    border: 1px solid var(--md-border, #e2e8f0);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.currency-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: var(--md-primary, #4f46e5);
}
.currency-card.is-default { border-left: 4px solid var(--md-primary, #4f46e5); }
.currency-card.is-active { border-left: 4px solid #10b981; }
.currency-card.is-inactive { border-left: 4px solid var(--md-border, #e2e8f0); opacity: 0.7; }
.currency-card.is-inactive:hover { opacity: 1; }

.currency-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.currency-identity {
    display: flex;
    align-items: center;
    gap: 12px;
}
.currency-flag {
    font-size: 32px;
    line-height: 1;
}
.currency-symbol-box {
    width: 44px; height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(79,70,229,0.08), rgba(79,70,229,0.15));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: #4f46e5;
}
.currency-code {
    font-size: 18px;
    font-weight: 800;
    color: var(--md-text-primary, #1e293b);
    letter-spacing: 0.5px;
}
.currency-name {
    font-size: 13px;
    color: var(--md-text-secondary, #64748b);
    font-weight: 500;
}

.currency-card-body {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.currency-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.currency-detail-row {
    font-size: 12px;
    color: var(--md-text-secondary, #64748b);
}
.currency-detail-row strong {
    color: var(--md-text-primary, #1e293b);
}

.currency-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.currency-badge.default { background: rgba(79,70,229,0.10); color: #4f46e5; }
.currency-badge.active { background: #d1fae5; color: #059669; }
.currency-badge.inactive { background: #f1f5f9; color: #94a3b8; }

.toggle-btn {
    width: 36px; height: 36px; border-radius: 8px;
    border: none; cursor: pointer; display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 14px; transition: all 0.2s;
}
.toggle-btn.on { background: rgba(16,185,129,0.12); color: #059669; }
.toggle-btn.on:hover { background: #10b981; color: white; box-shadow: 0 3px 8px rgba(16,185,129,0.3); }
.toggle-btn.off { background: rgba(148,163,184,0.12); color: #94a3b8; }
.toggle-btn.off:hover { background: #64748b; color: white; box-shadow: 0 3px 8px rgba(100,116,139,0.3); }

.default-indicator {
    position: absolute;
    top: 12px; right: 12px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 6px;
    letter-spacing: 0.5px;
}

@media (max-width: 640px) {
    .currency-grid { grid-template-columns: 1fr; }
}
</style>

<div class="master-data-container">

    <!-- Page Header -->
    <div class="master-data-header">
        <h2><i class="fa fa-globe"></i> <?= $lang === 'th' ? 'จัดการสกุลเงิน' : 'Currency Management' ?></h2>
        <div style="display:flex; gap:8px;">
            <a href="index.php?page=currency_rates" class="btn btn-sm btn-outline-primary" style="border-radius:8px; font-weight:600;">
                <i class="fa fa-line-chart"></i> <?= $lang === 'th' ? 'อัตราแลกเปลี่ยน' : 'Exchange Rates' ?>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible" style="border-radius:10px; border-left:4px solid #10b981;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check-circle"></i> <?= $lang === 'th' ? 'อัปเดตเรียบร้อย' : 'Updated successfully' ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card primary">
            <i class="fa fa-globe stat-icon"></i>
            <div class="stat-value"><?= $totalCount ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ทั้งหมด' : 'Total Currencies' ?></div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= $activeCount ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'เปิดใช้งาน' : 'Active' ?></div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-star stat-icon"></i>
            <div class="stat-value"><?= $defaultCurrency ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'สกุลเงินหลัก' : 'Default Currency' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-exchange stat-icon"></i>
            <div class="stat-value"><?= $activeCount - 1 ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'แลกเปลี่ยนได้' : 'Convertible' ?></div>
        </div>
    </div>

    <!-- Info Banner -->
    <div style="background:linear-gradient(135deg, rgba(79,70,229,0.06), rgba(99,102,241,0.04)); border:1px solid rgba(79,70,229,0.12); border-radius:12px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(79,70,229,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fa fa-info" style="color:#4f46e5; font-size:16px;"></i>
        </div>
        <div>
            <div style="font-weight:600; font-size:13px; color:var(--md-text-primary, #1e293b);">
                <?= $lang === 'th' ? 'สกุลเงินเริ่มต้น: ' : 'Default currency: ' ?><strong><?= $defaultCurrency ?></strong>
            </div>
            <div style="font-size:12px; color:var(--md-text-secondary, #64748b); margin-top:2px;">
                <?= $lang === 'th' 
                    ? 'เปิด/ปิดสกุลเงินที่ต้องการใช้ — อัตราแลกเปลี่ยนจาก BOT' 
                    : 'Toggle currencies to activate — Exchange rates sourced from Bank of Thailand API' ?>
            </div>
        </div>
    </div>

    <!-- Currency Grid -->
    <div class="currency-grid">
        <?php foreach ($currencies as $code => $cur): 
            $isDefault = ($code === $defaultCurrency);
            $isActive = $isDefault || isset($activeMap[$code]);
            $dbRecord = $activeMap[$code] ?? null;
            $cardClass = $isDefault ? 'is-default' : ($isActive ? 'is-active' : 'is-inactive');
            $flag = $flags[$code] ?? '💱';
        ?>
        <div class="currency-card <?= $cardClass ?>">
            <?php if ($isDefault): ?>
            <div class="default-indicator"><?= $lang === 'th' ? 'ค่าเริ่มต้น' : 'DEFAULT' ?></div>
            <?php endif; ?>

            <div class="currency-card-header">
                <div class="currency-identity">
                    <div class="currency-flag"><?= $flag ?></div>
                    <div>
                        <div class="currency-code"><?= $code ?></div>
                        <div class="currency-name"><?= $cur['name'] ?></div>
                    </div>
                </div>
            </div>

            <div class="currency-card-body">
                <div class="currency-details">
                    <div class="currency-detail-row">
                        <i class="fa fa-font"></i> 
                        <?= $lang === 'th' ? 'ชื่อไทย' : 'Thai' ?>: <strong><?= $cur['name_th'] ?? '-' ?></strong>
                    </div>
                    <div class="currency-detail-row">
                        <i class="fa fa-tag"></i>
                        <?= $lang === 'th' ? 'สัญลักษณ์' : 'Symbol' ?>: 
                        <strong style="font-size:16px;"><?= $cur['symbol'] ?></strong>
                    </div>
                    <div class="currency-detail-row">
                        <i class="fa fa-sort-numeric-asc"></i>
                        <?= $lang === 'th' ? 'ทศนิยม' : 'Decimals' ?>: <strong><?= $cur['decimals'] ?></strong>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <?php if ($isDefault): ?>
                        <span class="currency-badge default"><i class="fa fa-star"></i> <?= $lang === 'th' ? 'หลัก' : 'Default' ?></span>
                    <?php elseif ($isActive): ?>
                        <span class="currency-badge active"><i class="fa fa-check"></i> <?= $lang === 'th' ? 'เปิดใช้' : 'Active' ?></span>
                    <?php else: ?>
                        <span class="currency-badge inactive"><i class="fa fa-minus-circle"></i> <?= $lang === 'th' ? 'ปิด' : 'Inactive' ?></span>
                    <?php endif; ?>

                    <?php if (!$isDefault && $dbRecord): ?>
                    <form method="post" action="index.php?page=currency_toggle" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="id" value="<?= $dbRecord['id'] ?>">
                        <button type="submit" class="toggle-btn <?= $isActive ? 'on' : 'off' ?>" 
                                title="<?= $isActive ? ($lang === 'th' ? 'ปิดใช้งาน' : 'Deactivate') : ($lang === 'th' ? 'เปิดใช้งาน' : 'Activate') ?>">
                            <i class="fa fa-power-off"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
