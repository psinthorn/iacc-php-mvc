<?php
/**
 * Tour Agent Profile — Create / Edit Form
 * 
 * Variables from controller: $profile (null for create), $vendors, $message
 */

$isThai = ($_SESSION['lang'] ?? '0') === '1';
$isEdit = !empty($profile);

$messages = [
    'error'     => ['⚠️', $isThai ? 'กรุณาเลือกบริษัทตัวแทน' : 'Please select agent vendor'],
    'duplicate' => ['⚠️', $isThai ? 'ตัวแทนนี้มีโปรไฟล์แล้ว' : 'This vendor already has an agent profile'],
    'not_found' => ['⚠️', $isThai ? 'ไม่พบข้อมูล' : 'Profile not found'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.form-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 20px; }
.form-card h3 { margin: 0 0 20px; font-size: 16px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; }
.form-row.three { grid-template-columns: 1fr 1fr 1fr; }
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
.vendor-info { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 10px; padding: 14px; margin-top: 8px; font-size: 13px; color: #0f766e; display: none; }
/* Rate grid */
.rate-grid { display: flex; flex-direction: column; gap: 14px; }
.rate-section-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.rate-inputs { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
.rate-field label { display: block; font-size: 11px; color: #94a3b8; margin-bottom: 4px; font-weight: 500; }
.rate-field input { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box; height: 38px; }
.rate-field input:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.1); outline: none; }
.rate-field input:disabled { background: #f8fafc; color: #cbd5e1; }
/* Default rate fields — highlighted */
.rate-defaults { grid-template-columns: 1fr 1fr; margin-bottom: 6px; }
.rate-field-default input { border-color: #0d9488; background: #f0fdfa; font-weight: 600; }
.rate-field-default label { color: #0f766e; font-weight: 600; }
.rate-override-label { font-size: 11px; color: #94a3b8; margin: 4px 0 6px; font-style: italic; }
.rate-overrides input { background: #fff; }
@media (max-width: 768px) { .rate-inputs { grid-template-columns: 1fr 1fr; } .rate-defaults { grid-template-columns: 1fr 1fr; } }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-<?= $isEdit ? 'pencil' : 'plus' ?>"></i> <?= $isEdit ? ($isThai ? 'แก้ไขโปรไฟล์ตัวแทน' : 'Edit Agent Profile') : ($isThai ? 'เพิ่มตัวแทนทัวร์' : 'Add Tour Agent') ?></h2>
                <p><?= $isThai ? 'กำหนดค่าคอมมิชชั่นและข้อมูลสัญญา' : 'Configure commission and contract details' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_list" class="btn-header btn-header-outline">
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

    <form method="post" action="index.php?page=tour_agent_store">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $isEdit ? $profile['id'] : '' ?>">

        <!-- Section 1: Agent Selection -->
        <div class="form-card">
            <h3><i class="fa fa-building"></i> <?= $isThai ? 'บริษัทตัวแทน' : 'Agent Company' ?></h3>

            <?php if ($isEdit): ?>
            <div class="form-group">
                <label><?= $isThai ? 'ตัวแทน' : 'Agent' ?></label>
                <input type="text" value="<?= htmlspecialchars($profile['name_en']) ?>" disabled style="background:#f8fafc;">
                <input type="hidden" name="company_ref_id" value="<?= $profile['company_ref_id'] ?>">
            </div>
            <?php else: ?>
            <div class="form-group">
                <label><?= $isThai ? 'เลือกบริษัทตัวแทน (ลูกค้า/Vendor)' : 'Select Agent (Customer / Vendor)' ?> *</label>
                <select name="company_ref_id" id="vendorSelect" required onchange="showVendorInfo(this)">
                    <option value=""><?= $isThai ? '-- เลือกบริษัท --' : '-- Select Company --' ?></option>
                    <?php foreach ($vendors as $v):
                        $type = [];
                        if (!empty($v['customer'])) $type[] = $isThai ? 'ลูกค้า' : 'Customer';
                        if (!empty($v['vender']))   $type[] = 'Vendor';
                        $badge = $type ? ' [' . implode('/', $type) . ']' : '';
                    ?>
                    <option value="<?= $v['id'] ?>"
                        data-contact="<?= htmlspecialchars($v['contact'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($v['phone'] ?? '') ?>"
                        data-email="<?= htmlspecialchars($v['email'] ?? '') ?>"
                        <?= $v['profile_id'] ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($v['name_en']) ?><?= $badge ?>
                        <?= $v['profile_id'] ? ($isThai ? ' (มีโปรไฟล์แล้ว)' : ' (has profile)') : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="help"><?= $isThai ? 'เลือกจากบริษัทลูกค้าหรือ Vendor ที่มีอยู่แล้ว หากไม่เห็นให้สร้างบริษัทใหม่ในหน้า Master Data > Company' : 'Select from existing customers or vendors. Create new companies in Master Data > Company.' ?></div>
                <div class="vendor-info" id="vendorInfo"></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Commission -->
        <div class="form-card">
            <h3><i class="fa fa-percent"></i> <?= $isThai ? 'ค่าคอมมิชชั่น' : 'Commission' ?></h3>

            <div class="form-group">
                <label><?= $xml->tourcommissiontype ?? ($isThai ? 'ประเภท' : 'Type') ?></label>
                <select name="commission_type" id="commType">
                    <option value="percentage" <?= ($isEdit && $profile['commission_type'] === 'percentage') ? 'selected' : '' ?>><?= $xml->tourpercentage ?? ($isThai ? 'เปอร์เซ็นต์ (%)' : 'Percentage (%)') ?></option>
                    <option value="net_rate" <?= ($isEdit && $profile['commission_type'] === 'net_rate') ? 'selected' : '' ?>><?= $xml->tournetrate ?? ($isThai ? 'ราคาเน็ต (บาท)' : 'Net Rate (THB)') ?></option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $xml->tourcommissionadult ?? ($isThai ? 'ค่าคอมมิชชั่น (ผู้ใหญ่)' : 'Commission (Adult)') ?></label>
                    <input type="number" name="commission_adult" step="0.01" min="0" value="<?= $isEdit ? $profile['commission_adult'] : '0.00' ?>">
                    <div class="help" id="commAdultHelp"><?= $isThai ? 'เปอร์เซ็นต์ เช่น 10.00 = 10%' : 'Percentage e.g. 10.00 = 10%' ?></div>
                </div>
                <div class="form-group">
                    <label><?= $xml->tourcommissionchild ?? ($isThai ? 'ค่าคอมมิชชั่น (เด็ก)' : 'Commission (Child)') ?></label>
                    <input type="number" name="commission_child" step="0.01" min="0" value="<?= $isEdit ? $profile['commission_child'] : '0.00' ?>">
                    <div class="help" id="commChildHelp"><?= $isThai ? 'เปอร์เซ็นต์ เช่น 5.00 = 5%' : 'Percentage e.g. 5.00 = 5%' ?></div>
                </div>
            </div>
        </div>

        <!-- Section 3: Contract -->
        <div class="form-card">
            <h3><i class="fa fa-calendar"></i> <?= $isThai ? 'สัญญา' : 'Contract' ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $xml->tourcontractstart ?? ($isThai ? 'วันเริ่มสัญญา' : 'Contract Start') ?></label>
                    <input type="date" name="contract_start" value="<?= $isEdit ? ($profile['contract_start'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label><?= $xml->tourcontractend ?? ($isThai ? 'วันสิ้นสุดสัญญา' : 'Contract End') ?></label>
                    <input type="date" name="contract_end" value="<?= $isEdit ? ($profile['contract_end'] ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <!-- Section 4: Contract Rates (per product model) -->
        <?php if ($isEdit && !empty($modelsByType)): ?>
        <div class="form-card">
            <h3><i class="fa fa-money"></i> <?= $isThai ? 'อัตราสัญญา (ตามสินค้า)' : 'Contract Rates (by Product)' ?></h3>
            <p style="font-size:13px;color:#64748b;margin:0 0 16px;">
                <?= $isThai ? 'กำหนดราคาสำหรับไทย/ต่างชาติ แยกผู้ใหญ่/เด็ก และค่าเข้าชม ต่อสินค้าแต่ละรายการ' : 'Set Thai/Foreigner pricing for Adult/Child with optional entrance fees per product.' ?>
            </p>

            <!-- Models grouped by Type -->
            <?php foreach ($modelsByType as $typeGroup): ?>
            <div class="type-group" style="margin-bottom:16px;">
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
                                <span style="font-size:11px;color:#94a3b8;margin-left:8px;"><i class="fa fa-info-circle"></i> <?= $isThai ? 'ใช้อัตราเริ่มต้น' : 'Using default rate' ?></span>
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
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][adult_thai]" step="0.01" min="0" value="<?= $mr['adult_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][adult_foreigner]" step="0.01" min="0" value="<?= $mr['adult_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][child_thai]" step="0.01" min="0" value="<?= $mr['child_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][child_foreigner]" step="0.01" min="0" value="<?= $mr['child_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
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
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (ไทย)' : 'Adult (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_adult_thai]" step="0.01" min="0" value="<?= $mr['entrance_adult_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'ผู้ใหญ่ (ต่างชาติ)' : 'Adult (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_adult_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_adult_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'เด็ก (ไทย)' : 'Child (Thai)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_child_thai]" step="0.01" min="0" value="<?= $mr['entrance_child_thai'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
                                        <div class="rate-field">
                                            <label><?= $isThai ? 'เด็ก (ต่างชาติ)' : 'Child (Foreigner)' ?></label>
                                            <input type="number" name="rates[<?= $mid ?>][entrance_child_foreigner]" step="0.01" min="0" value="<?= $mr['entrance_child_foreigner'] ?? '0.00' ?>" <?= $hasCustom ? '' : 'disabled' ?> placeholder="0 = <?= $isThai ? 'ใช้ค่าเริ่มต้น' : 'use default' ?>">
                                        </div>
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
        <?php elseif (!$isEdit): ?>
        <div class="form-card" style="background:#f8fafc;border:1px dashed #cbd5e1;">
            <p style="text-align:center;color:#94a3b8;margin:0;padding:20px 0;">
                <i class="fa fa-info-circle"></i> <?= $isThai ? 'บันทึกโปรไฟล์ก่อนเพื่อกำหนดอัตราสัญญา' : 'Save the profile first to configure contract rates.' ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Section 5: Contact Channels -->
        <div class="form-card">
            <h3><i class="fa fa-comments-o"></i> <?= $isThai ? 'ช่องทางติดต่อ' : 'Contact Channels' ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $isThai ? 'ชื่อผู้ติดต่อ' : 'Contact Person' ?></label>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($isEdit ? ($profile['contact_person'] ?? '') : '') ?>" placeholder="<?= $isThai ? 'ชื่อ-นามสกุล' : 'Full name' ?>">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'มือถือ' : 'Mobile' ?></label>
                    <input type="tel" name="contact_mobile" value="<?= htmlspecialchars($isEdit ? ($profile['contact_mobile'] ?? '') : '') ?>" placeholder="+66812345678">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= $isThai ? 'อีเมล' : 'Email' ?></label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($isEdit ? ($profile['contact_email'] ?? '') : '') ?>" placeholder="agent@example.com">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'เว็บไซต์' : 'Website' ?></label>
                    <input type="url" name="website" value="<?= htmlspecialchars($isEdit ? ($profile['website'] ?? '') : '') ?>" placeholder="https://www.example.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= $xml->tourcontactline ?? 'LINE ID' ?></label>
                    <input type="text" name="contact_line" value="<?= htmlspecialchars($isEdit ? ($profile['contact_line'] ?? '') : '') ?>" placeholder="@agent-line">
                </div>
                <div class="form-group">
                    <label><?= $xml->tourcontactwhatsapp ?? 'WhatsApp' ?></label>
                    <input type="text" name="contact_whatsapp" value="<?= htmlspecialchars($isEdit ? ($profile['contact_whatsapp'] ?? '') : '') ?>" placeholder="+66812345678">
                </div>
            </div>
        </div>

        <!-- Section 6: Notes -->
        <div class="form-card">
            <h3><i class="fa fa-sticky-note-o"></i> <?= $isThai ? 'หมายเหตุ' : 'Notes' ?></h3>
            <div class="form-group" style="margin-bottom:0;">
                <textarea name="notes" rows="3" placeholder="<?= $isThai ? 'หมายเหตุเพิ่มเติม...' : 'Additional notes...' ?>"><?= htmlspecialchars($isEdit ? ($profile['notes'] ?? '') : '') ?></textarea>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <a href="index.php?page=tour_agent_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
            <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?= $isThai ? 'บันทึก' : 'Save' ?></button>
        </div>
    </form>
</div>

<script>
function showVendorInfo(sel) {
    var opt = sel.options[sel.selectedIndex];
    var info = document.getElementById('vendorInfo');
    if (!opt.value) { info.style.display = 'none'; return; }
    var parts = [];
    if (opt.dataset.contact) parts.push('<i class="fa fa-user"></i> ' + opt.dataset.contact);
    if (opt.dataset.phone) parts.push('<i class="fa fa-phone"></i> ' + opt.dataset.phone);
    if (opt.dataset.email) parts.push('<i class="fa fa-envelope"></i> ' + opt.dataset.email);
    if (parts.length) { info.innerHTML = parts.join(' &nbsp;|&nbsp; '); info.style.display = 'block'; }
    else { info.style.display = 'none'; }
}

// Update help text based on commission type
document.getElementById('commType').addEventListener('change', function() {
    var isNet = this.value === 'net_rate';
    var thLang = <?= json_encode($isThai) ?>;
    document.getElementById('commAdultHelp').textContent = isNet
        ? (thLang ? 'จำนวนเงิน เช่น 500.00 = 500 บาท' : 'Amount e.g. 500.00 = 500 THB')
        : (thLang ? 'เปอร์เซ็นต์ เช่น 10.00 = 10%' : 'Percentage e.g. 10.00 = 10%');
    document.getElementById('commChildHelp').textContent = isNet
        ? (thLang ? 'จำนวนเงิน เช่น 300.00 = 300 บาท' : 'Amount e.g. 300.00 = 300 THB')
        : (thLang ? 'เปอร์เซ็นต์ เช่น 5.00 = 5%' : 'Percentage e.g. 5.00 = 5%');
});

// Contract Rate: toggle type group accordion
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

// Contract Rate: toggle model custom rate on/off
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
        cb.disabled = false; // keep checkbox itself enabled
    }
}

// Auto-fill: when default field changes, fill blank override fields as hint
document.querySelectorAll('.default-rate-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
        var card = this.dataset.card;
        var target = this.dataset.target; // 'adult', 'child', 'entrance_adult', 'entrance_child'
        var val = parseFloat(this.value) || 0;
        var thaiField, foreignField;
        if (card === '0') {
            thaiField = document.querySelector('input[name="rates[0][' + target + '_thai]"]');
            foreignField = document.querySelector('input[name="rates[0][' + target + '_foreigner]"]');
        } else {
            thaiField = document.querySelector('input[name="rates[' + card + '][' + target + '_thai]"]');
            foreignField = document.querySelector('input[name="rates[' + card + '][' + target + '_foreigner]"]');
        }
        if (thaiField && (parseFloat(thaiField.value) === 0 || thaiField.value === '' || thaiField.value === '0.00')) {
            thaiField.value = val > 0 ? val.toFixed(2) : '0.00';
        }
        if (foreignField && (parseFloat(foreignField.value) === 0 || foreignField.value === '' || foreignField.value === '0.00')) {
            foreignField.value = val > 0 ? val.toFixed(2) : '0.00';
        }
    });
});
</script>
