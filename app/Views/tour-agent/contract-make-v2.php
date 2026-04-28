<?php
$pageTitle = 'Tour Operator — Contract';

/**
 * Operator-Level Contract — Create / Edit Form (v2)
 * Tabbed: Details | Seasons & Rates | Agent Assignment | Sync Status
 *
 * Variables: $contract, $contractId, $allTypes, $modelsByType,
 *            $contractRates, $contractAgents, $availableAgents,
 *            $seasons, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';
$isEdit = !empty($contract);
$selectedTypes = $isEdit ? ($contract['type_ids'] ?? []) : array_column($allTypes, 'id');

$messages = [
    'created'          => ['✅', $isThai ? 'สร้างสัญญาสำเร็จ'           : 'Contract created successfully'],
    'updated'          => ['✅', $isThai ? 'อัปเดตสัญญาสำเร็จ'          : 'Contract updated successfully'],
    'cloned'           => ['✅', $isThai ? 'คัดลอกสัญญาสำเร็จ'          : 'Contract cloned successfully'],
    'agent_assigned'   => ['✅', $isThai ? 'มอบหมายตัวแทนสำเร็จ'        : 'Agent assigned successfully'],
    'agent_unassigned' => ['✅', $isThai ? 'ยกเลิกการมอบหมายสำเร็จ'     : 'Agent unassigned successfully'],
    'resynced'         => ['✅', $isThai ? 'ซิงค์ข้อมูลสำเร็จ'          : 'Contract resynced successfully'],
    'not_found'        => ['⚠️', $isThai ? 'ไม่พบสัญญา'                : 'Contract not found'],
    'error'            => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด'            : 'An error occurred'],
];

// Group rates by season
$ratesBySeason = [];
foreach ($contractRates as $r) {
    $sKey = $r['season_name'] ?? '__base__';
    $ratesBySeason[$sKey][] = $r;
}
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* ── Form ── */
.form-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 20px; }
.form-card h3 { margin: 0 0 20px; font-size: 16px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; }
.form-row.three { grid-template-columns: 1fr 1fr 1fr; }
.form-row.four { grid-template-columns: 1fr 1fr 1fr 1fr; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: 14px; outline: none; box-sizing: border-box; height: 44px; min-height: 44px;
}
.form-group textarea { height: auto; min-height: 80px; resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}
.form-group .help { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.btn-save { padding: 12px 32px; background: #0d9488; color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: #0f766e; }
.btn-cancel { padding: 12px 32px; background: #f1f5f9; color: #64748b; border: none; border-radius: 10px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-cancel:hover { background: #e2e8f0; }

/* ── Tabs ── */
.v2-tabs { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; }
.v2-tab { padding: 12px 24px; font-size: 14px; font-weight: 500; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .15s; background: none; border-top: none; border-left: none; border-right: none; }
.v2-tab:hover { color: #0d9488; }
.v2-tab.active { color: #0d9488; border-bottom-color: #0d9488; font-weight: 600; }
.v2-tab-panel { display: none; }
.v2-tab-panel.active { display: block; }

/* ── Type selection ── */
.type-checkboxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; }
.type-cb-item { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all .15s; }
.type-cb-item:hover { border-color: #0d9488; background: #f0fdfa; }
.type-cb-item.selected { border-color: #0d9488; background: #f0fdfa; }
.type-cb-item input[type="checkbox"] { accent-color: #0d9488; width: 16px; height: 16px; }
.type-cb-label { font-size: 13px; font-weight: 500; color: #334155; flex: 1; }
.type-cb-count { font-size: 11px; color: #94a3b8; }
.type-select-actions { display: flex; gap: 8px; margin-bottom: 10px; }
.type-select-actions button { background: none; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 12px; font-size: 12px; color: #64748b; cursor: pointer; }
.type-select-actions button:hover { border-color: #0d9488; color: #0d9488; }

/* ── Season cards ── */
.season-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
.season-card.is-base { border-left: 3px solid #0d9488; }
.season-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.season-name { font-size: 15px; font-weight: 600; color: #1e293b; }
.season-period { font-size: 12px; color: #64748b; }
.btn-add-season { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: #f0fdfa; color: #0d9488; border: 1px dashed #0d9488; border-radius: 10px; font-size: 13px; font-weight: 500; cursor: pointer; margin-bottom: 16px; }
.btn-add-season:hover { background: #ccfbf1; }
.btn-remove-season { background: #fef2f2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; }
.btn-remove-season:hover { background: #fee2e2; }

/* ── Rate grid ── */
.rate-grid { display: flex; flex-direction: column; gap: 14px; }
.rate-section-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.rate-inputs { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
.rate-field label { display: block; font-size: 11px; color: #94a3b8; margin-bottom: 4px; font-weight: 500; }
.rate-field input { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box; height: 38px; }
.rate-field input:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.1); outline: none; }
.rate-field input:disabled { background: #f8fafc; color: #cbd5e1; }
.rate-defaults { grid-template-columns: 1fr 1fr; margin-bottom: 6px; }
.rate-field-default input { border-color: #0d9488; background: #f0fdfa; font-weight: 600; }
.rate-field-default label { color: #0f766e; font-weight: 600; }
.rate-override-label { font-size: 11px; color: #94a3b8; margin: 4px 0 6px; font-style: italic; }

/* ── Agent assignment ── */
.agent-list { display: flex; flex-direction: column; gap: 10px; }
.agent-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
.agent-row:hover { border-color: #cbd5e1; }
.agent-info { display: flex; align-items: center; gap: 12px; }
.agent-avatar { width: 36px; height: 36px; border-radius: 50%; background: #f0fdfa; color: #0d9488; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
.agent-name { font-size: 14px; font-weight: 500; color: #1e293b; }
.agent-detail { font-size: 12px; color: #94a3b8; }
.btn-unassign { background: #fef2f2; color: #dc2626; border: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; cursor: pointer; }
.btn-unassign:hover { background: #fee2e2; }
.assign-form { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; }
.assign-form select { flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; height: 44px; }
.btn-assign { padding: 10px 20px; background: #0d9488; color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.btn-assign:hover { background: #0f766e; }

/* ── Sync ── */
.sync-info { padding: 16px; background: #f8fafc; border-radius: 10px; font-size: 13px; color: #64748b; }
.sync-info dt { font-weight: 600; color: #374151; }
.sync-info dd { margin: 0 0 10px; }

/* ── Model rate card ── */
.model-rate-card { background: #fafafa; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 10px; opacity: 0.7; }
.model-rate-card.has-rate { background: #fff; border-color: #0d9488; opacity: 1; }
.model-rate-type select { padding: 4px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 12px; }

@media (max-width: 768px) {
    .rate-inputs { grid-template-columns: 1fr 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .v2-tabs { flex-wrap: wrap; }
    .assign-form { flex-direction: column; }
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= $isEdit ? ($isThai ? 'แก้ไขสัญญา' : 'Edit Contract') : ($isThai ? 'สร้างสัญญาใหม่' : 'New Contract') ?></h2>
                <?php if ($isEdit && !empty($contract['contract_number'])): ?>
                <p style="font-family:monospace;"><?= htmlspecialchars($contract['contract_number']) ?></p>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_contract_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <?php if ($isEdit): ?>
                <form method="post" action="index.php?page=tour_contract_resync" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                    <button type="submit" class="btn-header btn-header-outline" style="border-color:rgba(255,255,255,0.5);color:#fff;">
                        <i class="fa fa-refresh"></i> <?= $isThai ? 'ซิงค์' : 'Resync' ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="v2-tabs">
        <button class="v2-tab active" onclick="switchTab('details')" data-tab="details">
            <i class="fa fa-info-circle"></i> <?= $isThai ? 'รายละเอียด' : 'Details' ?>
        </button>
        <button class="v2-tab" onclick="switchTab('rates')" data-tab="rates">
            <i class="fa fa-money"></i> <?= $isThai ? 'ฤดูกาล & อัตรา' : 'Seasons & Rates' ?>
        </button>
        <?php if ($isEdit): ?>
        <button class="v2-tab" onclick="switchTab('agents')" data-tab="agents">
            <i class="fa fa-users"></i> <?= $isThai ? 'ตัวแทน' : 'Agents' ?>
            <?php if (!empty($contractAgents)): ?>
            <span style="background:#0d9488;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= count($contractAgents) ?></span>
            <?php endif; ?>
        </button>
        <button class="v2-tab" onclick="switchTab('sync')" data-tab="sync">
            <i class="fa fa-refresh"></i> <?= $isThai ? 'สถานะซิงค์' : 'Sync Status' ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 1: Contract Details
         ═══════════════════════════════════════════════════════════ -->
    <div class="v2-tab-panel active" id="tab-details">
        <form method="post" action="index.php?page=tour_contract_store" id="contractForm">
            <?= csrf_field() ?>
            <input type="hidden" name="contract_id" value="<?= $isEdit ? $contract['id'] : '' ?>">

            <div class="form-card">
                <h3><i class="fa fa-info-circle"></i> <?= $isThai ? 'รายละเอียดสัญญา' : 'Contract Details' ?></h3>

                <div class="form-row">
                    <div class="form-group">
                        <label><?= $isThai ? 'ชื่อสัญญา' : 'Contract Name' ?></label>
                        <input type="text" name="contract_name" value="<?= htmlspecialchars($isEdit ? ($contract['contract_name'] ?? '') : '') ?>"
                               placeholder="<?= $isThai ? 'เช่น สัญญาทั่วไป, ฤดูร้อน 2026' : 'e.g. General Contract, Summer 2026' ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                        <select name="status">
                            <option value="draft"     <?= ($isEdit && $contract['status'] === 'draft') ? 'selected' : '' ?>><?= $isThai ? 'ร่าง' : 'Draft' ?></option>
                            <option value="active"    <?= ($isEdit && $contract['status'] === 'active') ? 'selected' : (!$isEdit ? 'selected' : '') ?>><?= $isThai ? 'ใช้งาน' : 'Active' ?></option>
                            <option value="expired"   <?= ($isEdit && $contract['status'] === 'expired') ? 'selected' : '' ?>><?= $isThai ? 'หมดอายุ' : 'Expired' ?></option>
                            <option value="cancelled" <?= ($isEdit && $contract['status'] === 'cancelled') ? 'selected' : '' ?>><?= $isThai ? 'ยกเลิก' : 'Cancelled' ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?= $isThai ? 'วันเริ่มต้น' : 'Valid From' ?></label>
                        <input type="date" name="valid_from" value="<?= htmlspecialchars($isEdit ? ($contract['valid_from'] ?? '') : date('Y-01-01')) ?>">
                    </div>
                    <div class="form-group">
                        <label><?= $isThai ? 'วันสิ้นสุด' : 'Valid To' ?></label>
                        <input type="date" name="valid_to" value="<?= htmlspecialchars($isEdit ? ($contract['valid_to'] ?? '') : date('Y-12-31')) ?>">
                    </div>
                </div>

                <div class="form-row three">
                    <div class="form-group">
                        <label><?= $isThai ? 'เงื่อนไขชำระเงิน' : 'Payment Terms' ?></label>
                        <input type="text" name="payment_terms" value="<?= htmlspecialchars($isEdit ? ($contract['payment_terms'] ?? '') : '') ?>"
                               placeholder="<?= $isThai ? 'เช่น ชำระเงิน 30 วัน' : 'e.g. Net 30 days' ?>">
                    </div>
                    <div class="form-group">
                        <label><?= $isThai ? 'เครดิต (วัน)' : 'Credit Days' ?></label>
                        <input type="number" name="credit_days" min="0" value="<?= $isEdit ? intval($contract['credit_days']) : 0 ?>">
                    </div>
                    <div class="form-group">
                        <label><?= $isThai ? 'ค่ามัดจำ (%)' : 'Deposit (%)' ?></label>
                        <input type="number" name="deposit_pct" step="0.1" min="0" max="100" value="<?= $isEdit ? number_format(floatval($contract['deposit_pct']), 1) : '0.0' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label><?= $isThai ? 'เงื่อนไขพิเศษ' : 'Special Conditions' ?></label>
                    <textarea name="conditions" rows="3" placeholder="<?= $isThai ? 'เงื่อนไขเพิ่มเติม...' : 'Additional terms and conditions...' ?>"><?= htmlspecialchars($isEdit ? ($contract['conditions'] ?? '') : '') ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></label>
                    <textarea name="notes" rows="2" placeholder="<?= $isThai ? 'หมายเหตุภายใน...' : 'Internal notes...' ?>"><?= htmlspecialchars($isEdit ? ($contract['notes'] ?? '') : '') ?></textarea>
                </div>
            </div>

            <!-- Product Types -->
            <div class="form-card">
                <h3><i class="fa fa-tags"></i> <?= $isThai ? 'ประเภทสินค้าในสัญญา' : 'Product Types in Contract' ?></h3>
                <p style="font-size:13px;color:#64748b;margin:0 0 12px;">
                    <?= $isThai ? 'เลือกประเภทสินค้าที่ตัวแทนสามารถขายได้ภายใต้สัญญานี้' : 'Select which product types agents can sell under this contract.' ?>
                </p>

                <div class="type-select-actions">
                    <button type="button" onclick="toggleAllTypes(true)"><?= $isThai ? 'เลือกทั้งหมด' : 'Select All' ?></button>
                    <button type="button" onclick="toggleAllTypes(false)"><?= $isThai ? 'ยกเลิกทั้งหมด' : 'Deselect All' ?></button>
                </div>

                <div class="type-checkboxes">
                    <?php foreach ($allTypes as $type):
                        $isSelected = in_array($type['id'], $selectedTypes);
                    ?>
                    <label class="type-cb-item <?= $isSelected ? 'selected' : '' ?>" data-type-id="<?= $type['id'] ?>">
                        <input type="checkbox" name="type_ids[]" value="<?= $type['id'] ?>"
                               <?= $isSelected ? 'checked' : '' ?>
                               onchange="onTypeToggle(this)">
                        <span class="type-cb-label"><?= htmlspecialchars($type['name']) ?></span>
                        <span class="type-cb-count"><?= $type['model_count'] ?> <?= $isThai ? 'สินค้า' : 'products' ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Save -->
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <a href="index.php?page=tour_contract_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
                <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?= $isThai ? 'บันทึก' : 'Save' ?></button>
            </div>
        </form>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 2: Seasons & Rates
         ═══════════════════════════════════════════════════════════ -->
    <div class="v2-tab-panel" id="tab-rates">
        <?php if (!$isEdit): ?>
        <div class="form-card">
            <p style="text-align:center;color:#94a3b8;padding:24px;">
                <i class="fa fa-info-circle"></i> <?= $isThai ? 'กรุณาบันทึกสัญญาก่อนกำหนดอัตรา' : 'Please save the contract first before setting rates.' ?>
            </p>
        </div>
        <?php else: ?>
        <form method="post" action="index.php?page=tour_contract_store" id="ratesForm">
            <?= csrf_field() ?>
            <input type="hidden" name="contract_id" value="<?= $contractId ?>">
            <input type="hidden" name="contract_name" value="<?= htmlspecialchars($contract['contract_name'] ?? '') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($contract['status'] ?? 'active') ?>">
            <input type="hidden" name="valid_from" value="<?= htmlspecialchars($contract['valid_from'] ?? '') ?>">
            <input type="hidden" name="valid_to" value="<?= htmlspecialchars($contract['valid_to'] ?? '') ?>">
            <input type="hidden" name="payment_terms" value="<?= htmlspecialchars($contract['payment_terms'] ?? '') ?>">
            <input type="hidden" name="credit_days" value="<?= intval($contract['credit_days'] ?? 0) ?>">
            <input type="hidden" name="deposit_pct" value="<?= floatval($contract['deposit_pct'] ?? 0) ?>">

            <div class="form-card">
                <h3><i class="fa fa-calendar"></i> <?= $isThai ? 'ช่วงเวลาและอัตรา' : 'Seasons & Rates' ?></h3>
                <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                    <?= $isThai
                        ? 'กำหนดอัตราตามฤดูกาล เช่น High Season, Low Season — อัตราฐาน (ไม่ระบุช่วง) จะใช้เมื่อไม่มีช่วงเวลาตรง'
                        : 'Set rates by season period. Base rate (no period) applies when no season matches the travel date.' ?>
                </p>

                <button type="button" class="btn-add-season" onclick="addSeason()">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มช่วงเวลา' : 'Add Season' ?>
                </button>

                <div id="seasonContainer">
                    <?php
                    // Base rate season (always shown)
                    $baseRates = $ratesBySeason['__base__'] ?? [];
                    $seasonIdx = 0;
                    ?>
                    <!-- Base Rate -->
                    <div class="season-card is-base" data-season-idx="base">
                        <div class="season-header">
                            <div>
                                <div class="season-name"><i class="fa fa-home"></i> <?= $isThai ? 'อัตราฐาน (ค่าเริ่มต้น)' : 'Base Rate (Default)' ?></div>
                                <div class="season-period"><?= $isThai ? 'ใช้เมื่อไม่มีช่วงเวลาตรง' : 'Applied when no season matches' ?></div>
                            </div>
                        </div>
                        <?php foreach ($modelsByType as $typeGroup):
                            $tid = $typeGroup['type_id'];
                        ?>
                        <div class="type-group" data-type-id="<?= $tid ?>" style="margin-bottom:12px;">
                            <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;" onclick="toggleType(this)">
                                <span style="font-weight:600;font-size:13px;color:#334155;">
                                    <i class="fa fa-folder-o"></i> <?= htmlspecialchars($typeGroup['type_name']) ?>
                                    <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:6px;">(<?= count($typeGroup['models']) ?>)</span>
                                </span>
                                <i class="fa fa-chevron-down type-chevron" style="color:#94a3b8;transition:transform .2s;"></i>
                            </div>
                            <div class="type-models" style="display:none;padding:10px 0 0;">
                                <?php foreach ($typeGroup['models'] as $model):
                                    $mid = (int)$model['id'];
                                    // Find base rate for this model
                                    $mr = null;
                                    foreach ($baseRates as $br) {
                                        if ((int)$br['model_id'] === $mid) { $mr = $br; break; }
                                    }
                                    $hasRate = ($mr !== null);
                                ?>
                                <div class="model-rate-card <?= $hasRate ? 'has-rate' : '' ?>" style="<?= $hasRate ? 'opacity:1;border-color:#0d9488;background:#fff;' : '' ?>">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:<?= $hasRate ? '10px' : '0' ?>;">
                                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;">
                                            <input type="checkbox" class="model-rate-toggle"
                                                   <?= $hasRate ? 'checked' : '' ?>
                                                   onchange="toggleModelRate(this, 'base', <?= $mid ?>)">
                                            <?= htmlspecialchars($model['model_name']) ?>
                                        </label>
                                        <div class="model-rate-type" style="<?= $hasRate ? '' : 'display:none;' ?>">
                                            <select name="season_rates[0][rates][<?= $mid ?>][rate_type]" class="rate-type-select" <?= $hasRate ? '' : 'disabled' ?>>
                                                <option value="net_rate" <?= ($mr['rate_type'] ?? 'net_rate') === 'net_rate' ? 'selected' : '' ?>>Net Rate (฿)</option>
                                                <option value="percentage" <?= ($mr['rate_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="season_rates[0][rates][<?= $mid ?>][model_id]" value="<?= $mid ?>" <?= $hasRate ? '' : 'disabled' ?>>
                                    <div class="model-rate-fields" style="<?= $hasRate ? '' : 'display:none;' ?>">
                                        <div class="rate-grid">
                                            <div class="rate-section">
                                                <div class="rate-section-label"><?= $isThai ? 'ค่าบริการ' : 'Service Rate' ?></div>
                                                <div class="rate-inputs rate-defaults">
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'ผู้ใหญ่ (เริ่มต้น)' : 'Adult (Default)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][adult_default]" step="0.01" min="0" value="<?= $mr['adult_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'เด็ก (เริ่มต้น)' : 'Child (Default)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][child_default]" step="0.01" min="0" value="<?= $mr['child_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                </div>
                                                <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ (ถ้าต่างจากค่าเริ่มต้น)' : 'Override by nationality (if different from default)' ?></div>
                                                <div class="rate-inputs">
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][adult_thai]" step="0.01" min="0" value="<?= $mr['adult_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][adult_foreigner]" step="0.01" min="0" value="<?= $mr['adult_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][child_thai]" step="0.01" min="0" value="<?= $mr['child_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][child_foreigner]" step="0.01" min="0" value="<?= $mr['child_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                </div>
                                            </div>
                                            <div class="rate-section">
                                                <div class="rate-section-label"><?= $isThai ? 'ค่าเข้าชม (ถ้ามี)' : 'Entrance Fee (if any)' ?></div>
                                                <div class="rate-inputs rate-defaults">
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_adult_default]" step="0.01" min="0" value="<?= $mr['entrance_adult_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'เด็ก' : 'Child' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_child_default]" step="0.01" min="0" value="<?= $mr['entrance_child_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                </div>
                                                <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ' : 'Override by nationality' ?></div>
                                                <div class="rate-inputs">
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_adult_thai]" step="0.01" min="0" value="<?= $mr['entrance_adult_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_adult_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_adult_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_child_thai]" step="0.01" min="0" value="<?= $mr['entrance_child_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[0][rates][<?= $mid ?>][entrance_child_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_child_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Existing Season Rates -->
                    <?php
                    $seasonIdx = 1;
                    foreach ($seasons as $season):
                        $sName = $season['season_name'];
                        $sRates = $ratesBySeason[$sName] ?? [];
                    ?>
                    <div class="season-card" data-season-idx="<?= $seasonIdx ?>">
                        <div class="season-header">
                            <div>
                                <div class="season-name"><i class="fa fa-sun-o"></i> <?= htmlspecialchars($sName) ?></div>
                                <div class="season-period">
                                    <?= !empty($season['season_start']) ? date('d M Y', strtotime($season['season_start'])) : '' ?>
                                    <?= !empty($season['season_start']) && !empty($season['season_end']) ? ' — ' : '' ?>
                                    <?= !empty($season['season_end']) ? date('d M Y', strtotime($season['season_end'])) : '' ?>
                                    <?php if (($season['priority'] ?? 0) > 0): ?>
                                    <span style="font-size:11px;color:#0d9488;margin-left:8px;"><?= $isThai ? 'ลำดับ' : 'Priority' ?>: <?= $season['priority'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-season" onclick="removeSeason(this)">
                                <i class="fa fa-times"></i> <?= $isThai ? 'ลบ' : 'Remove' ?>
                            </button>
                        </div>

                        <div class="form-row three" style="margin-bottom:14px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?= $isThai ? 'ชื่อช่วงเวลา' : 'Season Name' ?></label>
                                <input type="text" name="season_rates[<?= $seasonIdx ?>][season_name]" value="<?= htmlspecialchars($sName) ?>" placeholder="<?= $isThai ? 'เช่น High Season' : 'e.g. High Season' ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?= $isThai ? 'เริ่มต้น' : 'Start' ?></label>
                                <input type="date" name="season_rates[<?= $seasonIdx ?>][season_start]" value="<?= htmlspecialchars($season['season_start'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label><?= $isThai ? 'สิ้นสุด' : 'End' ?></label>
                                <input type="date" name="season_rates[<?= $seasonIdx ?>][season_end]" value="<?= htmlspecialchars($season['season_end'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:14px;max-width:120px;">
                            <label><?= $isThai ? 'ลำดับความสำคัญ' : 'Priority' ?></label>
                            <input type="number" name="season_rates[<?= $seasonIdx ?>][priority]" min="0" value="<?= intval($season['priority'] ?? 0) ?>">
                        </div>

                        <?php foreach ($modelsByType as $typeGroup):
                            $tid = $typeGroup['type_id'];
                        ?>
                        <div class="type-group" data-type-id="<?= $tid ?>" style="margin-bottom:12px;">
                            <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;" onclick="toggleType(this)">
                                <span style="font-weight:600;font-size:13px;color:#334155;">
                                    <i class="fa fa-folder-o"></i> <?= htmlspecialchars($typeGroup['type_name']) ?>
                                </span>
                                <i class="fa fa-chevron-down type-chevron" style="color:#94a3b8;transition:transform .2s;"></i>
                            </div>
                            <div class="type-models" style="display:none;padding:10px 0 0;">
                                <?php foreach ($typeGroup['models'] as $model):
                                    $mid = (int)$model['id'];
                                    $mr = null;
                                    foreach ($sRates as $sr) {
                                        if ((int)$sr['model_id'] === $mid) { $mr = $sr; break; }
                                    }
                                    $hasRate = ($mr !== null);
                                ?>
                                <div class="model-rate-card <?= $hasRate ? 'has-rate' : '' ?>" style="<?= $hasRate ? 'opacity:1;border-color:#0d9488;background:#fff;' : '' ?>">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:<?= $hasRate ? '10px' : '0' ?>;">
                                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;">
                                            <input type="checkbox" class="model-rate-toggle"
                                                   <?= $hasRate ? 'checked' : '' ?>
                                                   onchange="toggleModelRate(this, '<?= $seasonIdx ?>', <?= $mid ?>)">
                                            <?= htmlspecialchars($model['model_name']) ?>
                                        </label>
                                        <div class="model-rate-type" style="<?= $hasRate ? '' : 'display:none;' ?>">
                                            <select name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][rate_type]" <?= $hasRate ? '' : 'disabled' ?>>
                                                <option value="net_rate" <?= ($mr['rate_type'] ?? 'net_rate') === 'net_rate' ? 'selected' : '' ?>>Net Rate (฿)</option>
                                                <option value="percentage" <?= ($mr['rate_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][model_id]" value="<?= $mid ?>" <?= $hasRate ? '' : 'disabled' ?>>
                                    <div class="model-rate-fields" style="<?= $hasRate ? '' : 'display:none;' ?>">
                                        <div class="rate-grid">
                                            <div class="rate-section">
                                                <div class="rate-section-label"><?= $isThai ? 'ค่าบริการ' : 'Service Rate' ?></div>
                                                <div class="rate-inputs rate-defaults">
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][adult_default]" step="0.01" min="0" value="<?= $mr['adult_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'เด็ก' : 'Child' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][child_default]" step="0.01" min="0" value="<?= $mr['child_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                </div>
                                                <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ' : 'Override by nationality' ?></div>
                                                <div class="rate-inputs">
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][adult_thai]" step="0.01" min="0" value="<?= $mr['adult_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][adult_foreigner]" step="0.01" min="0" value="<?= $mr['adult_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][child_thai]" step="0.01" min="0" value="<?= $mr['child_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][child_foreigner]" step="0.01" min="0" value="<?= $mr['child_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                </div>
                                            </div>
                                            <div class="rate-section">
                                                <div class="rate-section-label"><?= $isThai ? 'ค่าเข้าชม' : 'Entrance Fee' ?></div>
                                                <div class="rate-inputs rate-defaults">
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'ผู้ใหญ่' : 'Adult' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_adult_default]" step="0.01" min="0" value="<?= $mr['entrance_adult_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                    <div class="rate-field rate-field-default"><label><?= $isThai ? 'เด็ก' : 'Child' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_child_default]" step="0.01" min="0" value="<?= $mr['entrance_child_default'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?>></div>
                                                </div>
                                                <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ' : 'Override by nationality' ?></div>
                                                <div class="rate-inputs">
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_adult_thai]" step="0.01" min="0" value="<?= $mr['entrance_adult_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_adult_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_adult_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_child_thai]" step="0.01" min="0" value="<?= $mr['entrance_child_thai'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                    <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                                        <input type="number" name="season_rates[<?= $seasonIdx ?>][rates][<?= $mid ?>][entrance_child_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_child_foreigner'] ?? '0.00' ?>" <?= $hasRate ? '' : 'disabled' ?> placeholder="0 = default"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    $seasonIdx++;
                    endforeach;
                    ?>
                </div>
            </div>

            <!-- Save Rates -->
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?= $isThai ? 'บันทึกอัตรา' : 'Save Rates' ?></button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 3: Agent Assignment (edit only)
         ═══════════════════════════════════════════════════════════ -->
    <?php if ($isEdit): ?>
    <div class="v2-tab-panel" id="tab-agents">
        <div class="form-card">
            <h3><i class="fa fa-users"></i> <?= $isThai ? 'ตัวแทนที่มอบหมาย' : 'Assigned Agents' ?></h3>

            <!-- Assign new agent -->
            <?php if (!empty($availableAgents)): ?>
            <form method="post" action="index.php?page=tour_contract_assign" class="assign-form">
                <?= csrf_field() ?>
                <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                <select name="agent_company_id" required>
                    <option value=""><?= $isThai ? '— เลือกตัวแทน —' : '— Select Agent —' ?></option>
                    <?php foreach ($availableAgents as $ag): ?>
                    <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['name_en'] ?: $ag['name_th'] ?: 'ID#' . $ag['id']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-assign"><i class="fa fa-plus"></i> <?= $isThai ? 'มอบหมาย' : 'Assign' ?></button>
            </form>
            <?php endif; ?>

            <!-- Current agents -->
            <?php if (empty($contractAgents)): ?>
            <div style="text-align:center;color:#94a3b8;padding:24px;">
                <i class="fa fa-users" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                <p><?= $isThai ? 'ยังไม่มีตัวแทนในสัญญานี้' : 'No agents assigned to this contract yet.' ?></p>
            </div>
            <?php else: ?>
            <div class="agent-list">
                <?php foreach ($contractAgents as $agent): ?>
                <div class="agent-row">
                    <div class="agent-info">
                        <div class="agent-avatar"><?= mb_strtoupper(mb_substr($agent['name_en'] ?: $agent['name_th'] ?: '?', 0, 1)) ?></div>
                        <div>
                            <div class="agent-name"><?= htmlspecialchars($agent['name_en'] ?: $agent['name_th'] ?: 'ID#' . $agent['agent_company_id']) ?></div>
                            <div class="agent-detail">
                                <?= $isThai ? 'มอบหมายเมื่อ' : 'Assigned' ?>: <?= date('d M Y', strtotime($agent['assigned_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <form method="post" action="index.php?page=tour_contract_unassign" style="display:inline;"
                          onsubmit="return confirm('<?= $isThai ? 'ยกเลิกการมอบหมายตัวแทนนี้? สินค้าที่ซิงค์จะถูกลบ' : 'Unassign this agent? Synced products will be removed.' ?>')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                        <input type="hidden" name="agent_company_id" value="<?= $agent['agent_company_id'] ?>">
                        <button type="submit" class="btn-unassign">
                            <i class="fa fa-times"></i> <?= $isThai ? 'ยกเลิก' : 'Remove' ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TAB 4: Sync Status (edit only)
         ═══════════════════════════════════════════════════════════ -->
    <div class="v2-tab-panel" id="tab-sync">
        <div class="form-card">
            <h3><i class="fa fa-refresh"></i> <?= $isThai ? 'สถานะการซิงค์' : 'Sync Status' ?></h3>

            <div class="sync-info">
                <dl>
                    <dt><?= $isThai ? 'สัญญา' : 'Contract' ?></dt>
                    <dd><?= htmlspecialchars($contract['contract_name'] ?? '') ?> (#<?= $contractId ?>)</dd>

                    <dt><?= $isThai ? 'ตัวแทนที่มอบหมาย' : 'Assigned Agents' ?></dt>
                    <dd><?= count($contractAgents) ?></dd>

                    <dt><?= $isThai ? 'ช่วงเวลา (Seasons)' : 'Seasons' ?></dt>
                    <dd><?= count($seasons) ?> + <?= $isThai ? 'อัตราฐาน' : 'Base Rate' ?></dd>

                    <dt><?= $isThai ? 'สถานะ' : 'Status' ?></dt>
                    <dd>
                        <span style="color:#059669;"><i class="fa fa-check-circle"></i> <?= $isThai ? 'พร้อม — ซิงค์จะทำงานเมื่อบันทึกหรือกดปุ่ม Resync' : 'Ready — sync runs on save or manual Resync' ?></span>
                    </dd>
                </dl>

                <form method="post" action="index.php?page=tour_contract_resync" style="margin-top:12px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="contract_id" value="<?= $contractId ?>">
                    <button type="submit" class="btn-save" style="background:#2563eb;">
                        <i class="fa fa-refresh"></i> <?= $isThai ? 'ซิงค์ตอนนี้' : 'Resync Now' ?>
                    </button>
                </form>
            </div>

            <p style="font-size:12px;color:#94a3b8;margin-top:16px;">
                <i class="fa fa-info-circle"></i>
                <?= $isThai
                    ? 'การซิงค์จะอัปเดตสินค้าในแคตตาล็อกของตัวแทนทุกรายที่มอบหมายในสัญญานี้ — ยังไม่ซ้ำข้อมูลสินค้า แต่สร้างลิงก์อ้างอิง'
                    : 'Sync updates the product catalog for all assigned agents — it creates reference links, not data duplicates.' ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ── Tab switching ──
function switchTab(tab) {
    document.querySelectorAll('.v2-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.v2-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelector('[data-tab="' + tab + '"]').classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

// ── Type toggle ──
function onTypeToggle(cb) {
    var item = cb.closest('.type-cb-item');
    if (cb.checked) { item.classList.add('selected'); } else { item.classList.remove('selected'); }
}

function toggleAllTypes(select) {
    document.querySelectorAll('.type-cb-item input[type="checkbox"]').forEach(function(cb) {
        cb.checked = select;
        onTypeToggle(cb);
    });
}

// ── Rate accordion ──
function toggleType(el) {
    var models = el.nextElementSibling;
    var chevron = el.querySelector('.type-chevron');
    if (models.style.display === 'none') {
        models.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        models.style.display = 'none';
        chevron.style.transform = '';
    }
}

// ── Model rate toggle ──
function toggleModelRate(cb, seasonIdx, modelId) {
    var card = cb.closest('.model-rate-card');
    var fields = card.querySelector('.model-rate-fields');
    var typeDiv = card.querySelector('.model-rate-type');
    var inputs = card.querySelectorAll('input[type="number"], select, input[type="hidden"][name*="model_id"]');
    if (cb.checked) {
        fields.style.display = 'block';
        typeDiv.style.display = '';
        card.classList.add('has-rate');
        card.style.opacity = '1';
        card.style.borderColor = '#0d9488';
        card.style.background = '#fff';
        inputs.forEach(function(inp) { inp.disabled = false; });
    } else {
        fields.style.display = 'none';
        typeDiv.style.display = 'none';
        card.classList.remove('has-rate');
        card.style.opacity = '0.7';
        card.style.borderColor = '#e2e8f0';
        card.style.background = '#fafafa';
        inputs.forEach(function(inp) { inp.disabled = true; });
        cb.disabled = false;
    }
}

// ── Add season ──
var nextSeasonIdx = <?= $seasonIdx ?>;

function addSeason() {
    var container = document.getElementById('seasonContainer');
    var idx = nextSeasonIdx++;
    var isThai = <?= $isThai ? 'true' : 'false' ?>;

    var html = '<div class="season-card" data-season-idx="' + idx + '">'
        + '<div class="season-header"><div>'
        + '<div class="season-name"><i class="fa fa-sun-o"></i> ' + (isThai ? 'ช่วงเวลาใหม่' : 'New Season') + '</div>'
        + '</div>'
        + '<button type="button" class="btn-remove-season" onclick="removeSeason(this)">'
        + '<i class="fa fa-times"></i> ' + (isThai ? 'ลบ' : 'Remove') + '</button></div>'
        + '<div class="form-row three" style="margin-bottom:14px;">'
        + '<div class="form-group" style="margin-bottom:0;"><label>' + (isThai ? 'ชื่อช่วงเวลา' : 'Season Name') + '</label>'
        + '<input type="text" name="season_rates[' + idx + '][season_name]" placeholder="' + (isThai ? 'เช่น High Season' : 'e.g. High Season') + '" required></div>'
        + '<div class="form-group" style="margin-bottom:0;"><label>' + (isThai ? 'เริ่มต้น' : 'Start') + '</label>'
        + '<input type="date" name="season_rates[' + idx + '][season_start]"></div>'
        + '<div class="form-group" style="margin-bottom:0;"><label>' + (isThai ? 'สิ้นสุด' : 'End') + '</label>'
        + '<input type="date" name="season_rates[' + idx + '][season_end]"></div></div>'
        + '<div class="form-group" style="margin-bottom:14px;max-width:120px;"><label>' + (isThai ? 'ลำดับความสำคัญ' : 'Priority') + '</label>'
        + '<input type="number" name="season_rates[' + idx + '][priority]" min="0" value="0"></div>'
        + '<p style="text-align:center;color:#94a3b8;font-size:13px;padding:20px;">'
        + '<i class="fa fa-info-circle"></i> ' + (isThai ? 'บันทึกก่อนเพื่อเพิ่มอัตราสำหรับช่วงเวลานี้' : 'Save first to add rates for this season') + '</p>'
        + '</div>';

    container.insertAdjacentHTML('beforeend', html);
}

function removeSeason(btn) {
    var msg = <?= $isThai ? "'ลบช่วงเวลานี้?'" : "'Remove this season?'" ?>;
    if (confirm(msg)) {
        btn.closest('.season-card').remove();
    }
}

// ── Auto-switch to tab from URL hash ──
(function() {
    var hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('tab-' + hash)) {
        switchTab(hash);
    }
})();
</script>
