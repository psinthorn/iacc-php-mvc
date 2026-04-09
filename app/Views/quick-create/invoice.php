<?php
/**
 * Quick Create — Invoice Entry Form (Entry Point B)
 * Auto-creates: PR + PO + Delivery (upstream)
 * Variables: $types, $models, $models_by_type, $brands, $companies, $customers, $entry_type
 */
global $xml;
$isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);
?>

<div class="quick-create-page">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.quick-create-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1400px; margin: 0 auto;
}
.quick-create-page .page-header-qc {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px;
    box-shadow: 0 10px 40px rgba(16, 185, 129, 0.25);
    display: flex; justify-content: space-between; align-items: center;
}
.quick-create-page .page-header-qc h2 { margin: 0; font-size: 24px; font-weight: 700; }
.quick-create-page .page-header-qc .subtitle { opacity: 0.9; font-size: 14px; margin-top: 4px; }
.quick-create-page .btn-back {
    background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 20px;
    border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
}
.quick-create-page .btn-back:hover { background: rgba(255,255,255,0.3); color: white; text-decoration: none; }
.quick-create-page .auto-info-banner {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #fcd34d; border-radius: 12px; padding: 16px 20px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
}
.quick-create-page .auto-info-banner i { color: #d97706; font-size: 20px; }
.quick-create-page .auto-info-banner .info-text { font-size: 13px; color: #92400e; line-height: 1.4; }
.quick-create-page .auto-info-banner .info-text strong { color: #78350f; }
.quick-create-page .form-card {
    background: white; border-radius: 16px; padding: 24px;
    margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.quick-create-page .section-title {
    font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 20px;
    padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;
    display: flex; align-items: center; gap: 8px;
}
.quick-create-page .section-title i { color: #10b981; }
.quick-create-page .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; align-items: start; }
.quick-create-page .form-group { margin-bottom: 16px; }
.quick-create-page .form-group label {
    display: flex; align-items: center; font-size: 11px; font-weight: 600; color: #374151;
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;
    min-height: 30px;
}
.quick-create-page .form-group .form-control {
    width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 8px;
    font-size: 14px; transition: all 0.2s; box-sizing: border-box; height: 46px;
}
.quick-create-page .form-group textarea.form-control { height: auto; min-height: 70px; resize: vertical; }
.quick-create-page .form-group .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.quick-create-page .input-with-addon { display: flex; align-items: stretch; }
.quick-create-page .input-with-addon .form-control { border-radius: 8px 0 0 8px; border-right: none; }
.quick-create-page .input-with-addon .addon {
    background: #f3f4f6; border: 1px solid #e5e7eb; border-left: none;
    border-radius: 0 8px 8px 0; padding: 12px 14px; font-size: 12px;
    color: #6b7280; white-space: nowrap; display: flex; align-items: center;
}
.quick-create-page .product-item {
    background: white; border: 1px solid #e5e7eb; border-radius: 12px;
    padding: 20px; margin-bottom: 16px; transition: all 0.2s;
}
.quick-create-page .product-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.quick-create-page .product-item-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f3f4f6;
}
.quick-create-page .product-item-number {
    background: #10b981; color: white; width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600;
}
.quick-create-page .product-item-name { font-weight: 600; color: #374151; flex: 1; margin-left: 12px; }
.quick-create-page .product-row-main {
    display: grid; grid-template-columns: 1.5fr 1.5fr 1fr 0.8fr 1fr 1.2fr; gap: 12px; align-items: end;
}
.quick-create-page .labour-field { display: flex; align-items: center; gap: 8px; height: 46px; }
.quick-create-page .labour-field input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: #10b981; flex-shrink: 0; }
.quick-create-page .labour-field input[type="text"] { flex: 1; height: 46px; }
.quick-create-page .products-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px; padding: 16px; margin-bottom: 16px; border: 1px solid #e2e8f0;
}
.quick-create-page .products-header h3 { margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #334155; }
.quick-create-page .products-header p { margin: 0; font-size: 13px; color: #64748b; }
.quick-create-page .notes-row label {
    display: block; font-size: 11px; font-weight: 600; color: #374151;
    margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;
}
.quick-create-page .form-actions {
    display: flex; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e5e7eb;
}
.quick-create-page .btn-add-row {
    background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px;
    cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: all 0.2s;
}
.quick-create-page .btn-add-row:hover { background: #059669; }
.quick-create-page .btn-remove-row {
    background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px;
    cursor: pointer; font-weight: 500; transition: all 0.2s;
}
.quick-create-page .btn-remove-row:hover { background: #dc2626; }
.quick-create-page .btn-submit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white; border: none; padding: 14px 32px; border-radius: 10px;
    cursor: pointer; font-weight: 600; font-size: 15px; margin-left: auto;
    display: flex; align-items: center; gap: 8px; transition: all 0.2s;
}
.quick-create-page .btn-submit:hover {
    transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}
@media (max-width: 1400px) { .quick-create-page .product-row-main { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 992px) {
    .quick-create-page .product-row-main { grid-template-columns: 1fr 1fr; }
    .quick-create-page .form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .quick-create-page .product-row-main { grid-template-columns: 1fr; }
    .quick-create-page .page-header-qc { flex-direction: column; align-items: flex-start; gap: 12px; }
}
.quick-create-page .btn-cust-toggle {
    padding: 3px 10px; border-radius: 4px; border: 1px solid #e5e7eb;
    background: #f9fafb; color: #6b7280; cursor: pointer; font-size: 11px;
    font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 3px;
    line-height: 1;
}
.quick-create-page .btn-cust-toggle.active {
    background: #10b981; color: white; border-color: #10b981;
}
.quick-create-page .btn-cust-toggle:hover:not(.active) { background: #f3f4f6; }
</style>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible" style="border-radius:12px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="page-header-qc">
    <div>
        <h2><i class="fa fa-file-text"></i> <?= $isThaiLang ? 'สร้างใบแจ้งหนี้ด่วน' : 'Quick Create — Invoice' ?></h2>
        <div class="subtitle"><?= $isThaiLang ? 'ระบบจะสร้าง PR, PO, ใบส่งของ ให้อัตโนมัติ' : 'System will auto-create PR, PO, and Delivery' ?></div>
    </div>
    <a href="index.php?page=qc_index" class="btn-back"><i class="fa fa-arrow-left"></i> <?= $isThaiLang ? 'กลับ' : 'Back' ?></a>
</div>

<!-- Auto-create info banner -->
<div class="auto-info-banner">
    <i class="fa fa-magic"></i>
    <div class="info-text">
        <strong><?= $isThaiLang ? 'สร้างอัตโนมัติ:' : 'Auto-creates:' ?></strong> 
        <?= $isThaiLang 
            ? 'ใบขอซื้อ (PR), ใบสั่งซื้อ (PO), ใบส่งของ (Delivery) จะถูกสร้างให้อัตโนมัติ → ใบแจ้งหนี้จะถูกสร้างเมื่อบันทึก' 
            : 'Purchase Request (PR), Purchase Order (PO), and Delivery will be auto-generated. Invoice will be created on save.' ?>
    </div>
</div>

<form method="POST" action="index.php?page=qc_store" id="qcForm">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="entry_type" value="invoice">

    <!-- Document Info -->
    <div class="form-card">
        <div class="section-title"><i class="fa fa-info-circle"></i> <?= $isThaiLang ? 'ข้อมูลเอกสาร' : 'Document Info' ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label><?= $isThaiLang ? 'หัวข้อ' : 'Title' ?></label>
                <input type="text" class="form-control" name="name" id="qcTitle" required placeholder="<?= $isThaiLang ? 'หัวข้อเอกสาร' : 'Document title' ?>">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;justify-content:space-between;">
                    <span><?= $isThaiLang ? 'ลูกค้า' : 'Customer' ?></span>
                    <span class="customer-toggle" style="display:flex;gap:4px;">
                        <button type="button" class="btn-cust-toggle active" data-mode="existing" onclick="toggleCustomerMode('existing',this)">
                            <i class="fa fa-search"></i> <?= $isThaiLang ? 'เดิม' : 'Existing' ?>
                        </button>
                        <button type="button" class="btn-cust-toggle" data-mode="new" onclick="toggleCustomerMode('new',this)">
                            <i class="fa fa-plus"></i> <?= $isThaiLang ? 'ใหม่' : 'New' ?>
                        </button>
                    </span>
                </label>
                <div id="existingCustomerWrap">
                    <select name="cus_id" id="cusIdSelect" class="form-control smart-dropdown">
                        <option value=""><?= $isThaiLang ? '-- เลือกลูกค้า --' : '-- Select Customer --' ?></option>
                        <?php foreach ($companies as $c): ?>
                            <?php if (!empty($c['customer'])): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name_en'] ?: $c['name_sh'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="newCustomerWrap" style="display:none;">
                    <input type="text" class="form-control" name="new_customer_name" id="newCustomerName" placeholder="<?= $isThaiLang ? 'ชื่อลูกค้าใหม่' : 'New customer name' ?>">
                </div>
                <input type="hidden" name="customer_mode" id="customerMode" value="existing">
            </div>
            <div class="form-group">
                <label><?= $xml->des ?? 'Description' ?></label>
                <textarea class="form-control" name="des" id="qcDescription" rows="2" placeholder="<?= $isThaiLang ? 'เว้นว่างจะใช้หัวข้อแทน' : 'Leave blank to use title' ?>"></textarea>
            </div>
        </div>
    </div>

    <!-- Payment & Dates -->
    <div class="form-card">
        <div class="section-title"><i class="fa fa-calendar"></i> <?= $isThaiLang ? 'เงื่อนไข' : 'Terms' ?></div>
        <div class="form-grid">
            <div class="form-group">
                <label><?= $isThaiLang ? 'วันที่เริ่มต้น' : 'Start Date' ?></label>
                <input type="date" class="form-control" name="valid_pay" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label><?= $xml->deliverdate ?? 'Delivery Date' ?></label>
                <input type="date" class="form-control" name="deliver_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label><?= $xml->vat ?? 'VAT' ?> (%)</label>
                <div class="input-with-addon">
                    <input type="number" class="form-control" name="vat" value="7" min="0" max="100" step="0.01">
                    <span class="addon">%</span>
                </div>
            </div>
            <div class="form-group">
                <label><?= $xml->discount ?? 'Discount' ?></label>
                <div class="input-with-addon">
                    <input type="number" class="form-control" name="dis" value="0" min="0" step="0.01">
                    <span class="addon">%</span>
                </div>
            </div>
            <div class="form-group">
                <label><?= $isThaiLang ? 'ภาษีหัก ณ ที่จ่าย' : 'WHT' ?> (%)</label>
                <div class="input-with-addon">
                    <input type="number" class="form-control" name="over" value="0" min="0" max="100" step="0.01">
                    <span class="addon">%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Products (shared partial) -->
    <?php include __DIR__ . '/_products.php'; ?>

    <!-- Submit -->
    <div class="form-card">
        <div class="form-actions" style="border-top:none; margin-top:0; padding-top:0;">
            <a href="index.php?page=qc_index" class="btn btn-default" style="padding:14px 24px; border-radius:10px;">
                <i class="fa fa-times"></i> <?= $isThaiLang ? 'ยกเลิก' : 'Cancel' ?>
            </a>
            <button type="submit" class="btn-submit">
                <i class="fa fa-bolt"></i> <?= $isThaiLang ? 'สร้างใบแจ้งหนี้' : 'Create Invoice' ?>
            </button>
        </div>
    </div>
</form>

<script>
function toggleCustomerMode(mode, btn) {
    document.getElementById('customerMode').value = mode;
    document.querySelectorAll('.btn-cust-toggle').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    if (mode === 'new') {
        document.getElementById('existingCustomerWrap').style.display = 'none';
        document.getElementById('newCustomerWrap').style.display = 'block';
        document.getElementById('cusIdSelect').removeAttribute('required');
        document.getElementById('newCustomerName').setAttribute('required', 'required');
    } else {
        document.getElementById('existingCustomerWrap').style.display = 'block';
        document.getElementById('newCustomerWrap').style.display = 'none';
        document.getElementById('newCustomerName').removeAttribute('required');
        document.getElementById('cusIdSelect').setAttribute('required', 'required');
    }
}
document.getElementById('qcForm').addEventListener('submit', function() {
    var des = document.getElementById('qcDescription');
    if (des && !des.value.trim()) { des.value = document.getElementById('qcTitle').value; }
});
</script>

</div>
