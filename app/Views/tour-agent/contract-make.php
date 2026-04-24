<?php
$pageTitle = 'Tour Agents — New Contract';

/**
 * Agent Contract — Create / Edit Form
 * 
 * Variables: $contract, $agentCompanyId, $agentName, $contractId,
 *            $allTypes, $modelsByType, $contractRates, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';
$isEdit = !empty($contract);
$selectedTypes = $isEdit ? ($contract['type_ids'] ?? []) : array_column($allTypes, 'id');

$messages = [
    'error'     => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบสัญญา' : 'Contract not found'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
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
/* Type selection */
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
/* Rate grid */
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
@media (max-width: 768px) { .rate-inputs { grid-template-columns: 1fr 1fr; } .form-row { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-text-o"></i> <?= $isEdit ? ($isThai ? 'แก้ไขสัญญา' : 'Edit Contract') : ($isThai ? 'สร้างสัญญาใหม่' : 'New Contract') ?></h2>
                <p><?= htmlspecialchars($agentName) ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=agent_contract_list&agent_id=<?= $agentCompanyId ?>" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=agent_contract_store" id="contractForm">
        <?= csrf_field() ?>
        <input type="hidden" name="contract_id" value="<?= $isEdit ? $contract['id'] : '' ?>">
        <input type="hidden" name="agent_company_id" value="<?= $agentCompanyId ?>">

        <!-- Section 1: Contract Details -->
        <div class="form-card">
            <h3><i class="fa fa-info-circle"></i> <?= $isThai ? 'รายละเอียดสัญญา' : 'Contract Details' ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $isThai ? 'ชื่อสัญญา' : 'Contract Name' ?></label>
                    <input type="text" name="contract_name" value="<?= htmlspecialchars($isEdit ? ($contract['contract_name'] ?? '') : '') ?>" placeholder="<?= $isThai ? 'เช่น สัญญาทั่วไป, ฤดูร้อน 2026' : 'e.g. General Contract, Summer 2026' ?>">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                    <select name="status">
                        <option value="draft" <?= ($isEdit && $contract['status'] === 'draft') ? 'selected' : '' ?>><?= $isThai ? 'ร่าง' : 'Draft' ?></option>
                        <option value="active" <?= ($isEdit && $contract['status'] === 'active') ? 'selected' : (!$isEdit ? 'selected' : '') ?>><?= $isThai ? 'ใช้งาน' : 'Active' ?></option>
                        <option value="expired" <?= ($isEdit && $contract['status'] === 'expired') ? 'selected' : '' ?>><?= $isThai ? 'หมดอายุ' : 'Expired' ?></option>
                        <option value="cancelled" <?= ($isEdit && $contract['status'] === 'cancelled') ? 'selected' : '' ?>><?= $isThai ? 'ยกเลิก' : 'Cancelled' ?></option>
                    </select>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div class="form-group" style="margin-bottom:16px;">
                <label><?= $isThai ? 'เลขที่สัญญา' : 'Contract Number' ?></label>
                <input type="text" value="<?= htmlspecialchars($contract['contract_number']) ?>" disabled style="background:#f8fafc;font-family:monospace;">
            </div>
            <?php endif; ?>

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
                    <input type="text" name="payment_terms" value="<?= htmlspecialchars($isEdit ? ($contract['payment_terms'] ?? '') : '') ?>" placeholder="<?= $isThai ? 'เช่น ชำระเงิน 30 วัน' : 'e.g. Net 30 days' ?>">
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

        <!-- Section 2: Product Types -->
        <div class="form-card">
            <h3><i class="fa fa-tags"></i> <?= $isThai ? 'ประเภทสินค้าในสัญญา' : 'Product Types in Contract' ?></h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 12px;">
                <?= $isThai ? 'เลือกประเภทสินค้าที่ตัวแทนสามารถขายได้ภายใต้สัญญานี้' : 'Select which product types this agent can sell under this contract.' ?>
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

        <!-- Section 3: Contract Rates (per product model, filtered by selected types) -->
        <div class="form-card" id="ratesSection">
            <h3><i class="fa fa-money"></i> <?= $isThai ? 'อัตราสัญญา (ตามสินค้า)' : 'Contract Rates (by Product)' ?></h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                <?= $isThai ? 'กำหนดราคาสำหรับไทย/ต่างชาติ แยกผู้ใหญ่/เด็ก และค่าเข้าชม ต่อสินค้าแต่ละรายการ' : 'Set Thai/Foreigner pricing for Adult/Child with optional entrance fees per product.' ?>
            </p>

            <?php foreach ($modelsByType as $typeGroup):
                $tid = $typeGroup['type_id'];
                $isTypeSelected = in_array($tid, $selectedTypes);
            ?>
            <div class="type-group" data-type-id="<?= $tid ?>" style="margin-bottom:16px;<?= $isTypeSelected ? '' : 'display:none;' ?>">
                <div class="type-header" onclick="toggleType(this)" style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-weight:600;font-size:14px;color:#334155;">
                        <i class="fa fa-folder-o"></i> <?= htmlspecialchars($typeGroup['type_name']) ?>
                        <span style="font-size:12px;color:#94a3b8;font-weight:400;margin-left:8px;">(<?= count($typeGroup['models']) ?> <?= $isThai ? 'สินค้า' : 'products' ?>)</span>
                    </span>
                    <i class="fa fa-chevron-down type-chevron" style="color:#94a3b8;transition:transform .2s;"></i>
                </div>
                <div class="type-models" style="display:none;padding:12px 0 0;">
                    <?php foreach ($typeGroup['models'] as $model):
                        $mid = (int)$model['id'];
                        $mr = $contractRates[$mid] ?? null;
                        $hasCustom = ($mr !== null);
                    ?>
                    <div class="rate-card model-rate-card" style="background:<?= $hasCustom ? '#fff' : '#fafafa' ?>;border:1px solid <?= $hasCustom ? '#0d9488' : '#e2e8f0' ?>;border-radius:10px;padding:16px;margin-bottom:10px;<?= $hasCustom ? '' : 'opacity:0.7;' ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:<?= $hasCustom ? '12px' : '0' ?>;">
                            <div>
                                <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;font-weight:500;">
                                    <input type="checkbox" class="model-rate-toggle" data-model-id="<?= $mid ?>"
                                           <?= $hasCustom ? 'checked' : '' ?>
                                           onchange="toggleModelRate(this, <?= $mid ?>)">
                                    <?= htmlspecialchars($model['model_name']) ?>
                                </label>
                                <?php if (!$hasCustom): ?>
                                <span style="font-size:11px;color:#94a3b8;margin-left:8px;"><i class="fa fa-info-circle"></i> <?= $isThai ? 'ยังไม่กำหนดอัตรา' : 'No rate set' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="model-rate-type" style="<?= $hasCustom ? '' : 'display:none;' ?>">
                                <select name="rates[<?= $mid ?>][rate_type]" class="rate-type-select" style="padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;font-size:12px;" <?= $hasCustom ? '' : 'disabled' ?>>
                                    <option value="net_rate" <?= ($mr['rate_type'] ?? 'net_rate') === 'net_rate' ? 'selected' : '' ?>>Net Rate (฿)</option>
                                    <option value="percentage" <?= ($mr['rate_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="rates[<?= $mid ?>][model_id]" value="<?= $mid ?>" <?= $hasCustom ? '' : 'disabled' ?>>
                        <div class="model-rate-fields" style="<?= $hasCustom ? '' : 'display:none;' ?>">
                            <div class="rate-grid">
                                <div class="rate-section">
                                    <div class="rate-section-label"><?= $isThai ? 'ค่าบริการ' : 'Service Rate' ?></div>
                                    <div class="rate-inputs rate-defaults">
                                        <div class="rate-field rate-field-default">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (เริ่มต้น)' : 'Adult (Default)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][adult_default]" step="0.01" min="0" value="<?= $mr['adult_default'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> class="default-rate-input" data-target="adult" data-card="<?= $mid ?>">
                                        </div>
                                        <div class="rate-field rate-field-default">
                                            <label><?= $isThai ? 'เด็ก (เริ่มต้น)' : 'Child (Default)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][child_default]" step="0.01" min="0" value="<?= $mr['child_default'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> class="default-rate-input" data-target="child" data-card="<?= $mid ?>">
                                        </div>
                                    </div>
                                    <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ (ถ้าต่างจากค่าเริ่มต้น)' : 'Override by nationality (if different from default)' ?></div>
                                    <div class="rate-inputs rate-overrides">
                                        <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][adult_thai]" step="0.01" min="0" value="<?= $mr['adult_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][adult_foreigner]" step="0.01" min="0" value="<?= $mr['adult_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][child_thai]" step="0.01" min="0" value="<?= $mr['child_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][child_foreigner]" step="0.01" min="0" value="<?= $mr['child_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                    </div>
                                </div>
                                <div class="rate-section">
                                    <div class="rate-section-label"><?= $isThai ? 'ค่าเข้าชม (ถ้ามี)' : 'Entrance Fee (if any)' ?></div>
                                    <div class="rate-inputs rate-defaults">
                                        <div class="rate-field rate-field-default">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (เริ่มต้น)' : 'Adult (Default)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_adult_default]" step="0.01" min="0" value="<?= $mr['entrance_adult_default'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> class="default-rate-input" data-target="entrance_adult" data-card="<?= $mid ?>">
                                        </div>
                                        <div class="rate-field rate-field-default">
                                            <label><?= $isThai ? 'เด็ก (เริ่มต้น)' : 'Child (Default)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_child_default]" step="0.01" min="0" value="<?= $mr['entrance_child_default'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> class="default-rate-input" data-target="entrance_child" data-card="<?= $mid ?>">
                                        </div>
                                    </div>
                                    <div class="rate-override-label"><?= $isThai ? 'ระบุแยกตามสัญชาติ (ถ้าต่างจากค่าเริ่มต้น)' : 'Override by nationality (if different from default)' ?></div>
                                    <div class="rate-inputs rate-overrides">
                                        <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_adult_thai]" step="0.01" min="0" value="<?= $mr['entrance_adult_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_adult_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_adult_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_child_thai]" step="0.01" min="0" value="<?= $mr['entrance_child_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
                                        <div class="rate-field"><label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_child_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_child_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>"></div>
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

        <!-- Actions -->
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <a href="index.php?page=agent_contract_list&agent_id=<?= $agentCompanyId ?>" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
            <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?= $isThai ? 'บันทึก' : 'Save' ?></button>
        </div>
    </form>
</div>

<script>
// Type toggle: show/hide rate groups based on selected types
function onTypeToggle(cb) {
    var item = cb.closest('.type-cb-item');
    var typeId = cb.value;
    var rateGroup = document.querySelector('.type-group[data-type-id="' + typeId + '"]');
    if (cb.checked) {
        item.classList.add('selected');
        if (rateGroup) rateGroup.style.display = '';
    } else {
        item.classList.remove('selected');
        if (rateGroup) rateGroup.style.display = 'none';
    }
}

function toggleAllTypes(select) {
    document.querySelectorAll('.type-cb-item input[type="checkbox"]').forEach(function(cb) {
        cb.checked = select;
        onTypeToggle(cb);
    });
}

// Rate accordion
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

// Model rate toggle
function toggleModelRate(cb, modelId) {
    var card = cb.closest('.model-rate-card');
    var fields = card.querySelector('.model-rate-fields');
    var typeDiv = card.querySelector('.model-rate-type');
    var inputs = card.querySelectorAll('input[type="number"], select[name*="rate_type"], input[type="hidden"][name*="model_id"]');
    if (cb.checked) {
        fields.style.display = 'block';
        typeDiv.style.display = '';
        card.style.opacity = '1';
        card.style.borderColor = '#0d9488';
        card.style.background = '#fff';
        inputs.forEach(function(inp) { inp.disabled = false; });
    } else {
        fields.style.display = 'none';
        typeDiv.style.display = 'none';
        card.style.opacity = '0.7';
        card.style.borderColor = '#e2e8f0';
        card.style.background = '#fafafa';
        inputs.forEach(function(inp) { inp.disabled = true; });
        cb.disabled = false;
    }
}

// Auto-fill default → override
document.querySelectorAll('.default-rate-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
        var card = this.dataset.card;
        var target = this.dataset.target;
        var val = parseFloat(this.value) || 0;
        var thaiField = document.querySelector('input[name="rates[' + card + '][' + target + '_thai]"]');
        var foreignField = document.querySelector('input[name="rates[' + card + '][' + target + '_foreigner]"]');
        if (thaiField && (parseFloat(thaiField.value) === 0 || thaiField.value === '' || thaiField.value === '0.00')) {
            thaiField.value = val > 0 ? val.toFixed(2) : '0.00';
        }
        if (foreignField && (parseFloat(foreignField.value) === 0 || foreignField.value === '' || foreignField.value === '0.00')) {
            foreignField.value = val > 0 ? val.toFixed(2) : '0.00';
        }
    });
});
</script>
