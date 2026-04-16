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
.agent-filter {
    padding: 6px 16px; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff;
    font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;
}
.agent-filter:hover { border-color: #0d9488; color: #0d9488; }
.agent-filter.active { background: #0d9488; color: #fff; border-color: #0d9488; }
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
/* Rate grid — no longer used here, moved to contract-make.php */
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
                <div style="display:flex;gap:6px;margin-bottom:10px;">
                    <button type="button" class="agent-filter active" data-filter="all" onclick="filterAgents('all',this)"><?= $isThai ? 'ทั้งหมด' : 'All' ?></button>
                    <button type="button" class="agent-filter" data-filter="vendor" onclick="filterAgents('vendor',this)">Vendor</button>
                    <button type="button" class="agent-filter" data-filter="customer" onclick="filterAgents('customer',this)"><?= $isThai ? 'ลูกค้า' : 'Customer' ?></button>
                </div>
                <select name="company_ref_id" id="vendorSelect" required onchange="showVendorInfo(this)">
                    <option value=""><?= $isThai ? '-- เลือกบริษัท --' : '-- Select Company --' ?></option>
                    <?php foreach ($vendors as $v):
                        $isCustomer = !empty($v['customer']);
                        $isVendor   = !empty($v['vender']);
                        $type = [];
                        if ($isCustomer) $type[] = $isThai ? 'ลูกค้า' : 'Customer';
                        if ($isVendor)   $type[] = 'Vendor';
                        $badge = $type ? ' [' . implode('/', $type) . ']' : '';
                    ?>
                    <option value="<?= $v['id'] ?>"
                        data-contact="<?= htmlspecialchars($v['contact'] ?? '') ?>"
                        data-phone="<?= htmlspecialchars($v['phone'] ?? '') ?>"
                        data-email="<?= htmlspecialchars($v['email'] ?? '') ?>"
                        data-customer="<?= $isCustomer ? '1' : '0' ?>"
                        data-vendor="<?= $isVendor ? '1' : '0' ?>"
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

        <!-- Section 2: Contract Information -->
        <?php if ($isEdit): ?>
        <div class="form-card">
            <h3><i class="fa fa-file-text-o"></i> <?= $isThai ? 'ข้อมูลสัญญา' : 'Contract Information' ?></h3>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;">
                <p style="font-size:13px;color:#64748b;margin:0;">
                    <?= $isThai ? 'จัดการสัญญา ประเภทสินค้า และอัตราค่าบริการสำหรับตัวแทนนี้' : 'Manage contracts, product types, and service rates for this agent.' ?>
                </p>
                <a href="index.php?page=agent_contract_list&agent_id=<?= $profile['company_ref_id'] ?>" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#0d9488;color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;">
                    <i class="fa fa-file-text-o"></i> <?= $isThai ? 'จัดการสัญญา' : 'Manage Contracts' ?>
                    <i class="fa fa-arrow-right" style="margin-left:4px;"></i>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="form-card" style="background:#f0fdfa;border:1px solid #99f6e4;">
            <div style="display:flex;align-items:center;gap:10px;padding:12px 0;">
                <i class="fa fa-info-circle" style="color:#0d9488;font-size:18px;"></i>
                <p style="font-size:13px;color:#0f766e;margin:0;">
                    <?= $isThai ? 'สัญญาเริ่มต้นจะถูกสร้างให้อัตโนมัติหลังบันทึก สามารถแก้ไขอัตราค่าบริการได้ภายหลัง' : 'A default contract will be auto-created after saving. You can edit rates and terms later.' ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section 5: Contact Information -->
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

function filterAgents(type, btn) {
    document.querySelectorAll('.agent-filter').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    var sel = document.getElementById('vendorSelect');
    var opts = sel.querySelectorAll('option[value]');
    sel.value = '';
    var info = document.getElementById('vendorInfo');
    if (info) info.style.display = 'none';
    opts.forEach(function(opt) {
        if (type === 'all') { opt.style.display = ''; }
        else if (type === 'vendor') { opt.style.display = opt.dataset.vendor === '1' ? '' : 'none'; }
        else if (type === 'customer') { opt.style.display = opt.dataset.customer === '1' ? '' : 'none'; }
    });
}
</script>
