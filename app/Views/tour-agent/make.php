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
            <h3><i class="fa fa-building"></i> <?= $isThai ? 'บริษัทตัวแทน' : 'Agent Vendor' ?></h3>

            <?php if ($isEdit): ?>
            <div class="form-group">
                <label><?= $isThai ? 'ตัวแทน' : 'Agent' ?></label>
                <input type="text" value="<?= htmlspecialchars($profile['name_en']) ?>" disabled style="background:#f8fafc;">
                <input type="hidden" name="company_ref_id" value="<?= $profile['company_ref_id'] ?>">
            </div>
            <?php else: ?>
            <div class="form-group">
                <label><?= $isThai ? 'เลือกบริษัทตัวแทน (Vendor)' : 'Select Agent Vendor' ?> *</label>
                <select name="company_ref_id" id="vendorSelect" required onchange="showVendorInfo(this)">
                    <option value=""><?= $isThai ? '-- เลือกตัวแทน --' : '-- Select Vendor --' ?></option>
                    <?php foreach ($vendors as $v): ?>
                    <option value="<?= $v['id'] ?>"
                        data-contact="<?= htmlspecialchars($v['contact'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($v['phone'] ?? '') ?>"
                        data-email="<?= htmlspecialchars($v['email'] ?? '') ?>"
                        <?= $v['profile_id'] ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($v['name_en']) ?>
                        <?= $v['profile_id'] ? ($isThai ? ' (มีโปรไฟล์แล้ว)' : ' (has profile)') : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="help"><?= $isThai ? 'เฉพาะบริษัทที่เป็น Vendor เท่านั้น หากไม่เห็นให้สร้างบริษัทใหม่ในหน้า Master Data > Company' : 'Only companies marked as Vendor appear here. Create new vendors in Master Data > Company.' ?></div>
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

        <!-- Section 4: Contact Channels -->
        <div class="form-card">
            <h3><i class="fa fa-comments-o"></i> <?= $isThai ? 'ข้อมูลติดต่อ' : 'Contact Information' ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $xml->contactperson ?? 'Contact Person' ?></label>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($isEdit ? ($profile['contact_person'] ?? '') : '') ?>" placeholder="<?= $isThai ? 'ชื่อผู้ติดต่อ' : 'Contact person name' ?>">
                </div>
                <div class="form-group">
                    <label><?= $xml->email ?? 'Email' ?></label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($isEdit ? ($profile['contact_email'] ?? '') : '') ?>" placeholder="agent@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= $xml->phone ?? 'Phone' ?></label>
                    <input type="text" name="contact_phone" value="<?= htmlspecialchars($isEdit ? ($profile['contact_phone'] ?? '') : '') ?>" placeholder="02-xxx-xxxx">
                </div>
                <div class="form-group">
                    <label><?= $xml->fax ?? 'Fax' ?></label>
                    <input type="text" name="contact_fax" value="<?= htmlspecialchars($isEdit ? ($profile['contact_fax'] ?? '') : '') ?>" placeholder="02-xxx-xxxx">
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

        <!-- Section 5: Notes -->
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
</script>
