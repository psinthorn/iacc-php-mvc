<?php
$pageTitle = 'Expenses — New';

/**
 * Expense Form — Create / Edit
 * 
 * Uses master-data.css design system
 * Variables from controller: $expense, $categories, $vendors, $projects, $expenseNumber, $lang, $message
 */

$isThai = ($lang ?? '2') === '1';
$isEdit = !empty($expense);
$title = $isEdit 
    ? ($isThai ? 'แก้ไขค่าใช้จ่าย' : 'Edit Expense') 
    : ($isThai ? 'สร้างค่าใช้จ่ายใหม่' : 'New Expense');
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.form-container { background: white; border-radius: 14px; padding: 28px; border: 1px solid var(--md-border, #e2e8f0); margin-top: 16px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
.form-full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: #374151; }
.form-group label .req { color: #ef4444; }
.form-group input, .form-group select, .form-group textarea {
    height: 44px; min-height: 44px; box-sizing: border-box;
    padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: 14px; outline: none; transition: all 0.2s; width: 100%;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--md-primary, #4f46e5); box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
.form-group textarea { height: auto; min-height: 80px; resize: vertical; }
.form-group input[type="file"] { height: auto; padding: 10px 14px; line-height: 1.5; }
.form-group input[type="color"] { padding: 4px 8px; }
.form-group .hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.amount-preview {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 12px; padding: 20px; margin-top: 20px;
    border: 1px solid #bae6fd;
}
.amount-preview h4 { margin: 0 0 12px; color: #0369a1; font-size: 14px; }
.amount-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
.amount-row.total { border-top: 2px solid #0ea5e9; padding-top: 10px; margin-top: 6px; font-weight: 700; color: #0369a1; font-size: 16px; }
.btn-submit { padding: 12px 32px; background: var(--md-primary, #4f46e5); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { background: #4338ca; transform: translateY(-1px); }
.btn-cancel { padding: 12px 32px; background: #f1f5f9; color: #64748b; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-<?= $isEdit ? 'pencil' : 'plus-circle' ?>"></i> <?= $title ?></h2>
                <p><?= $isEdit ? ($isThai ? 'แก้ไขรายละเอียดค่าใช้จ่าย' : 'Edit expense details') : ($isThai ? 'กรอกข้อมูลค่าใช้จ่ายใหม่' : 'Fill in expense details') ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=expense_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="form-container">
        <form method="post" action="index.php?page=expense_store" enctype="multipart/form-data" id="expenseForm">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
            <?php endif; ?>

            <!-- Row 1: Number, Date, Category -->
            <div class="form-grid-3">
                <div class="form-group">
                    <label><?= $isThai ? 'เลขที่' : 'Expense Number' ?></label>
                    <input type="text" name="expense_number" value="<?= htmlspecialchars($expenseNumber ?? '') ?>" readonly style="background:#f8fafc; color:#64748b;">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'วันที่' : 'Expense Date' ?> <span class="req">*</span></label>
                    <input type="date" name="expense_date" value="<?= htmlspecialchars($expense['expense_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'หมวดหมู่' : 'Category' ?></label>
                    <select name="category_id">
                        <option value=""><?= $isThai ? '-- เลือกหมวดหมู่ --' : '-- Select Category --' ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($expense['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($isThai && $cat['name_th'] ? $cat['name_th'] : $cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Title -->
            <div class="form-grid" style="margin-top:20px;">
                <div class="form-group form-full">
                    <label><?= $isThai ? 'รายการ' : 'Title' ?> <span class="req">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($expense['title'] ?? '') ?>" required placeholder="<?= $isThai ? 'ระบุชื่อรายการค่าใช้จ่าย' : 'Enter expense title' ?>">
                </div>
            </div>

            <!-- Row 3: Vendor, Tax ID, Reference -->
            <div class="form-grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label><?= $isThai ? 'ผู้ขาย/ซัพพลายเออร์' : 'Vendor / Supplier' ?></label>
                    <input type="text" name="vendor_name" value="<?= htmlspecialchars($expense['vendor_name'] ?? '') ?>" list="vendorList" placeholder="<?= $isThai ? 'ชื่อผู้ขาย' : 'Vendor name' ?>">
                    <datalist id="vendorList">
                        <?php foreach ($vendors ?? [] as $v): ?>
                            <option value="<?= htmlspecialchars($v) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'เลขประจำตัวผู้เสียภาษี' : 'Vendor Tax ID' ?></label>
                    <input type="text" name="vendor_tax_id" value="<?= htmlspecialchars($expense['vendor_tax_id'] ?? '') ?>" placeholder="<?= $isThai ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID for WHT' ?>">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'เลขที่อ้างอิง' : 'Reference No.' ?></label>
                    <input type="text" name="reference_no" value="<?= htmlspecialchars($expense['reference_no'] ?? '') ?>" placeholder="<?= $isThai ? 'เลขที่ใบเสร็จ/ใบแจ้งหนี้' : 'Receipt/Invoice number' ?>">
                </div>
            </div>

            <!-- Row 4: Amount, VAT, WHT -->
            <div class="form-grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label><?= $isThai ? 'จำนวนเงิน (ก่อนภาษี)' : 'Amount (before tax)' ?> <span class="req">*</span></label>
                    <input type="number" name="amount" id="inputAmount" value="<?= $expense['amount'] ?? '0.00' ?>" step="0.01" min="0" required onchange="calcTotal()">
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'อัตรา VAT (%)' : 'VAT Rate (%)' ?></label>
                    <select name="vat_rate" id="inputVat" onchange="calcTotal()">
                        <option value="" <?= empty($expense['vat_rate']) ? 'selected' : '' ?>><?= $isThai ? 'ไม่มี VAT' : 'No VAT' ?></option>
                        <option value="7" <?= ($expense['vat_rate'] ?? '') == '7' ? 'selected' : '' ?>>7%</option>
                        <option value="0" <?= ($expense['vat_rate'] ?? '') === '0' ? 'selected' : '' ?>>0% (Zero-rated)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'อัตรา WHT (%)' : 'WHT Rate (%)' ?></label>
                    <select name="wht_rate" id="inputWht" onchange="calcTotal()">
                        <option value="" <?= empty($expense['wht_rate']) ? 'selected' : '' ?>><?= $isThai ? 'ไม่มี WHT' : 'No WHT' ?></option>
                        <option value="1" <?= ($expense['wht_rate'] ?? '') == '1' ? 'selected' : '' ?>>1%</option>
                        <option value="2" <?= ($expense['wht_rate'] ?? '') == '2' ? 'selected' : '' ?>>2%</option>
                        <option value="3" <?= ($expense['wht_rate'] ?? '') == '3' ? 'selected' : '' ?>>3%</option>
                        <option value="5" <?= ($expense['wht_rate'] ?? '') == '5' ? 'selected' : '' ?>>5%</option>
                        <option value="10" <?= ($expense['wht_rate'] ?? '') == '10' ? 'selected' : '' ?>>10%</option>
                        <option value="15" <?= ($expense['wht_rate'] ?? '') == '15' ? 'selected' : '' ?>>15%</option>
                    </select>
                </div>
            </div>

            <!-- Amount Preview -->
            <div class="amount-preview" id="amountPreview">
                <h4><i class="fa fa-calculator"></i> <?= $isThai ? 'สรุปยอดเงิน' : 'Amount Summary' ?></h4>
                <div class="amount-row"><span><?= $isThai ? 'จำนวนเงิน' : 'Subtotal' ?></span><span id="dispAmount">฿0.00</span></div>
                <div class="amount-row"><span><?= $isThai ? 'ภาษีมูลค่าเพิ่ม (VAT)' : 'VAT' ?></span><span id="dispVat">฿0.00</span></div>
                <div class="amount-row"><span><?= $isThai ? 'ภาษีหัก ณ ที่จ่าย (WHT)' : 'WHT Deducted' ?></span><span id="dispWht" style="color:#ef4444;">-฿0.00</span></div>
                <div class="amount-row total"><span><?= $isThai ? 'ยอดสุทธิ' : 'Net Payable' ?></span><span id="dispNet">฿0.00</span></div>
            </div>

            <!-- Row 5: Project, Payment, Due Date -->
            <div class="form-grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label><?= $isThai ? 'โปรเจกต์' : 'Project' ?></label>
                    <input type="text" name="project_name" value="<?= htmlspecialchars($expense['project_name'] ?? '') ?>" list="projectList" placeholder="<?= $isThai ? 'ชื่อโปรเจกต์ (ถ้ามี)' : 'Project name (optional)' ?>">
                    <datalist id="projectList">
                        <?php foreach ($projects ?? [] as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'วิธีการชำระ' : 'Payment Method' ?></label>
                    <select name="payment_method">
                        <option value=""><?= $isThai ? '-- เลือก --' : '-- Select --' ?></option>
                        <?php $methods = ['Cash'=>'เงินสด', 'Transfer'=>'โอนเงิน', 'Credit Card'=>'บัตรเครดิต', 'Cheque'=>'เช็ค', 'PromptPay'=>'พร้อมเพย์']; ?>
                        <?php foreach ($methods as $en => $th): ?>
                            <option value="<?= $en ?>" <?= ($expense['payment_method'] ?? '') === $en ? 'selected' : '' ?>><?= $isThai ? $th : $en ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'วันครบกำหนด' : 'Due Date' ?></label>
                    <input type="date" name="due_date" value="<?= htmlspecialchars($expense['due_date'] ?? '') ?>">
                </div>
            </div>

            <!-- Row 6: Description, Receipt -->
            <div class="form-grid" style="margin-top:20px;">
                <div class="form-group">
                    <label><?= $isThai ? 'รายละเอียด' : 'Description' ?></label>
                    <textarea name="description" rows="3" placeholder="<?= $isThai ? 'รายละเอียดเพิ่มเติม...' : 'Additional details...' ?>"><?= htmlspecialchars($expense['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label><?= $isThai ? 'แนบใบเสร็จ' : 'Attach Receipt' ?></label>
                    <input type="file" name="receipt_file" accept="image/*,.pdf">
                    <?php if (!empty($expense['receipt_file'])): ?>
                        <span class="hint"><i class="fa fa-paperclip"></i> <?= $isThai ? 'มีไฟล์แนบอยู่แล้ว' : 'File already attached' ?>: <?= basename($expense['receipt_file']) ?></span>
                    <?php endif; ?>
                    <span class="hint"><?= $isThai ? 'รองรับ: JPG, PNG, PDF (สูงสุด 5MB)' : 'Accepted: JPG, PNG, PDF (max 5MB)' ?></span>
                </div>
            </div>

            <!-- Status & Submit -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px; padding-top:20px; border-top:1px solid #e2e8f0;">
                <div class="form-group" style="min-width:160px;">
                    <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                    <select name="status">
                        <option value="draft" <?= ($expense['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>><?= $isThai ? 'ฉบับร่าง' : 'Draft' ?></option>
                        <option value="pending" <?= ($expense['status'] ?? '') === 'pending' ? 'selected' : '' ?>><?= $isThai ? 'ส่งขออนุมัติ' : 'Submit for Approval' ?></option>
                    </select>
                </div>
                <div style="display:flex; gap:12px;">
                    <a href="index.php?page=expense_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
                    <button type="submit" class="btn-submit">
                        <i class="fa fa-save"></i> <?= $isEdit ? ($isThai ? 'บันทึกการเปลี่ยนแปลง' : 'Save Changes') : ($isThai ? 'สร้างค่าใช้จ่าย' : 'Create Expense') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calcTotal() {
    const amount = parseFloat(document.getElementById('inputAmount').value) || 0;
    const vatRate = parseFloat(document.getElementById('inputVat').value) || 0;
    const whtRate = parseFloat(document.getElementById('inputWht').value) || 0;
    
    const vat = Math.round(amount * vatRate / 100 * 100) / 100;
    const wht = Math.round(amount * whtRate / 100 * 100) / 100;
    const net = amount + vat - wht;
    
    document.getElementById('dispAmount').textContent = '฿' + amount.toLocaleString('en', {minimumFractionDigits:2});
    document.getElementById('dispVat').textContent = '฿' + vat.toLocaleString('en', {minimumFractionDigits:2});
    document.getElementById('dispWht').textContent = '-฿' + wht.toLocaleString('en', {minimumFractionDigits:2});
    document.getElementById('dispNet').textContent = '฿' + net.toLocaleString('en', {minimumFractionDigits:2});
}
// Init on load
document.addEventListener('DOMContentLoaded', calcTotal);
</script>
