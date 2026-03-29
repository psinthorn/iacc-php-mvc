<?php
/**
 * Journal Voucher Create/Edit Form — Modern UI
 * 
 * Dynamic debit/credit entry rows with auto-balance validation
 * Variables from controller: $voucher, $accounts, $accountsByType, $jvNumber, $isEdit
 */

$isThai = ($lang ?? '2') === '1';
$error = $_GET['error'] ?? '';
$entries = $voucher['entries'] ?? [];

$typeLabels = [
    'general'    => $isThai ? 'ทั่วไป' : 'General',
    'payment'    => $isThai ? 'จ่ายเงิน' : 'Payment',
    'receipt'    => $isThai ? 'รับเงิน' : 'Receipt',
    'adjustment' => $isThai ? 'ปรับปรุง' : 'Adjustment',
    'opening'    => $isThai ? 'เปิดบัญชี' : 'Opening',
    'closing'    => $isThai ? 'ปิดบัญชี' : 'Closing',
];

$errorMessages = [
    'missing_fields' => $isThai ? 'กรุณากรอกข้อมูลที่จำเป็น' : 'Please fill in required fields',
    'unbalanced'     => $isThai ? 'ยอดเดบิตและเครดิตไม่เท่ากัน' : 'Total debit and credit must be equal',
    'save_failed'    => $isThai ? 'บันทึกไม่สำเร็จ กรุณาลองใหม่' : 'Save failed. Please try again',
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.jv-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.jv-entry-row { display: grid; grid-template-columns: 2fr 1.5fr 1fr 1fr 40px; gap: 8px; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
.jv-entry-header { font-weight: 600; font-size: 13px; color: #64748b; background: #f8fafc; padding: 10px 0; border-radius: 8px 8px 0 0; }
.jv-input { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
.jv-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
.jv-amount { text-align: right; font-family: monospace; }
.jv-total-row { display: grid; grid-template-columns: 2fr 1.5fr 1fr 1fr 40px; gap: 8px; padding: 12px 0; border-top: 2px solid #0d9488; font-weight: 700; }
.jv-balance-ok { color: #10b981; }
.jv-balance-err { color: #ef4444; }
.btn-remove { background: none; border: none; cursor: pointer; font-size: 18px; color: #ef4444; padding: 4px; }
.btn-remove:hover { background: #fef2f2; border-radius: 6px; }
@media (max-width: 768px) {
    .jv-form-grid { grid-template-columns: 1fr; }
    .jv-entry-row, .jv-entry-header, .jv-total-row { grid-template-columns: 1fr; gap: 4px; }
}
</style>

<div style="max-width:1200px; margin:0 auto; padding:20px;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg, #0d9488, #0891b2); border-radius:16px; padding:28px 32px; margin-bottom:24px; color:white;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 style="margin:0; font-size:24px; font-weight:700;">
                    📒 <?= $isEdit ? ($isThai ? 'แก้ไขใบสำคัญ' : 'Edit Journal Voucher') : ($isThai ? 'สร้างใบสำคัญใหม่' : 'New Journal Voucher') ?>
                </h2>
                <p style="margin:4px 0 0; opacity:0.9; font-size:14px;">
                    <?= htmlspecialchars($jvNumber) ?>
                </p>
            </div>
            <a href="index.php?page=journal_list" style="padding:10px 20px; background:rgba(255,255,255,0.2); color:white; text-decoration:none; border-radius:10px; font-weight:600; font-size:14px;">
                ← <?= $isThai ? 'กลับรายการ' : 'Back to List' ?>
            </a>
        </div>
    </div>

    <!-- Error Message -->
    <?php if ($error && isset($errorMessages[$error])): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#dc2626; font-weight:500;">
        ❌ <?= $errorMessages[$error] ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="index.php?page=journal_store" id="jvForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $voucher['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="jv_number" value="<?= htmlspecialchars($jvNumber) ?>">

        <!-- Header Fields -->
        <div style="background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
            <h3 style="margin:0 0 16px; font-size:16px; color:#1e293b;">
                📋 <?= $isThai ? 'ข้อมูลใบสำคัญ' : 'Voucher Details' ?>
            </h3>
            <div class="jv-form-grid">
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                        <?= $isThai ? 'ประเภทใบสำคัญ' : 'Voucher Type' ?> <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="voucher_type" class="jv-input">
                        <?php foreach ($typeLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($voucher['voucher_type'] ?? 'general') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                        <?= $isThai ? 'วันที่ทำรายการ' : 'Transaction Date' ?> <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="date" name="transaction_date" value="<?= htmlspecialchars($voucher['transaction_date'] ?? date('Y-m-d')) ?>" class="jv-input" required>
                </div>
                <div style="grid-column: span 2;">
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                        <?= $isThai ? 'คำอธิบาย' : 'Description' ?>
                    </label>
                    <input type="text" name="description" value="<?= htmlspecialchars($voucher['description'] ?? '') ?>" class="jv-input" placeholder="<?= $isThai ? 'อธิบายรายการ...' : 'Describe this journal entry...' ?>">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                        <?= $isThai ? 'เอกสารอ้างอิง' : 'Reference' ?>
                    </label>
                    <input type="text" name="reference" value="<?= htmlspecialchars($voucher['reference'] ?? '') ?>" class="jv-input" placeholder="<?= $isThai ? 'เช่น INV-001, PO-123' : 'e.g. INV-001, PO-123' ?>">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                        <?= $isThai ? 'ประเภทอ้างอิง' : 'Reference Type' ?>
                    </label>
                    <select name="reference_type" class="jv-input">
                        <option value=""><?= $isThai ? '- ไม่ระบุ -' : '- None -' ?></option>
                        <option value="po" <?= ($voucher['reference_type'] ?? '') === 'po' ? 'selected' : '' ?>>PO / Quotation</option>
                        <option value="invoice" <?= ($voucher['reference_type'] ?? '') === 'invoice' ? 'selected' : '' ?>>Invoice</option>
                        <option value="receipt" <?= ($voucher['reference_type'] ?? '') === 'receipt' ? 'selected' : '' ?>>Receipt</option>
                        <option value="voucher" <?= ($voucher['reference_type'] ?? '') === 'voucher' ? 'selected' : '' ?>>Voucher</option>
                        <option value="expense" <?= ($voucher['reference_type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
                        <option value="other" <?= ($voucher['reference_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Journal Entries (Debit/Credit Lines) -->
        <div style="background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:16px; color:#1e293b;">
                    📊 <?= $isThai ? 'รายการบัญชี (Debit/Credit)' : 'Journal Entries (Debit/Credit)' ?>
                </h3>
                <button type="button" onclick="addEntryRow()" style="padding:8px 16px; background:#0d9488; color:white; border:none; border-radius:8px; font-size:13px; cursor:pointer; font-weight:600;">
                    ＋ <?= $isThai ? 'เพิ่มรายการ' : 'Add Line' ?>
                </button>
            </div>

            <!-- Header Row -->
            <div class="jv-entry-row jv-entry-header">
                <div style="padding-left:12px;"><?= $isThai ? 'บัญชี' : 'Account' ?></div>
                <div><?= $isThai ? 'คำอธิบาย' : 'Description' ?></div>
                <div style="text-align:right; padding-right:12px;"><?= $isThai ? 'เดบิต' : 'Debit' ?></div>
                <div style="text-align:right; padding-right:12px;"><?= $isThai ? 'เครดิต' : 'Credit' ?></div>
                <div></div>
            </div>

            <!-- Entry Rows Container -->
            <div id="entriesContainer">
                <?php if (!empty($entries)): ?>
                    <?php foreach ($entries as $i => $entry): ?>
                    <div class="jv-entry-row" data-row="<?= $i ?>">
                        <div>
                            <select name="account_id[]" class="jv-input" required>
                                <option value=""><?= $isThai ? '-- เลือกบัญชี --' : '-- Select Account --' ?></option>
                                <?php foreach (['asset' => ($isThai ? '── สินทรัพย์ ──' : '── Assets ──'), 'liability' => ($isThai ? '── หนี้สิน ──' : '── Liabilities ──'), 'equity' => ($isThai ? '── ส่วนของเจ้าของ ──' : '── Equity ──'), 'revenue' => ($isThai ? '── รายได้ ──' : '── Revenue ──'), 'expense' => ($isThai ? '── ค่าใช้จ่าย ──' : '── Expenses ──')] as $type => $label): ?>
                                    <optgroup label="<?= $label ?>">
                                    <?php foreach ($accountsByType[$type] ?? [] as $acc): ?>
                                        <option value="<?= $acc['id'] ?>" <?= (int) $entry['account_id'] === (int) $acc['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($acc['account_code'] . ' — ' . $acc['account_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><input type="text" name="entry_description[]" value="<?= htmlspecialchars($entry['description'] ?? '') ?>" class="jv-input" placeholder="<?= $isThai ? 'รายละเอียด' : 'Detail' ?>"></div>
                        <div><input type="number" name="debit[]" value="<?= $entry['debit'] > 0 ? number_format($entry['debit'], 2, '.', '') : '' ?>" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><input type="number" name="credit[]" value="<?= $entry['credit'] > 0 ? number_format($entry['credit'], 2, '.', '') : '' ?>" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove">✕</button></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default 2 empty rows -->
                    <div class="jv-entry-row" data-row="0">
                        <div>
                            <select name="account_id[]" class="jv-input" required>
                                <option value=""><?= $isThai ? '-- เลือกบัญชี --' : '-- Select Account --' ?></option>
                                <?php foreach (['asset' => ($isThai ? '── สินทรัพย์ ──' : '── Assets ──'), 'liability' => ($isThai ? '── หนี้สิน ──' : '── Liabilities ──'), 'equity' => ($isThai ? '── ส่วนของเจ้าของ ──' : '── Equity ──'), 'revenue' => ($isThai ? '── รายได้ ──' : '── Revenue ──'), 'expense' => ($isThai ? '── ค่าใช้จ่าย ──' : '── Expenses ──')] as $type => $label): ?>
                                    <optgroup label="<?= $label ?>">
                                    <?php foreach ($accountsByType[$type] ?? [] as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' — ' . $acc['account_name']) ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><input type="text" name="entry_description[]" class="jv-input" placeholder="<?= $isThai ? 'รายละเอียด' : 'Detail' ?>"></div>
                        <div><input type="number" name="debit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><input type="number" name="credit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove">✕</button></div>
                    </div>
                    <div class="jv-entry-row" data-row="1">
                        <div>
                            <select name="account_id[]" class="jv-input" required>
                                <option value=""><?= $isThai ? '-- เลือกบัญชี --' : '-- Select Account --' ?></option>
                                <?php foreach (['asset' => ($isThai ? '── สินทรัพย์ ──' : '── Assets ──'), 'liability' => ($isThai ? '── หนี้สิน ──' : '── Liabilities ──'), 'equity' => ($isThai ? '── ส่วนของเจ้าของ ──' : '── Equity ──'), 'revenue' => ($isThai ? '── รายได้ ──' : '── Revenue ──'), 'expense' => ($isThai ? '── ค่าใช้จ่าย ──' : '── Expenses ──')] as $type => $label): ?>
                                    <optgroup label="<?= $label ?>">
                                    <?php foreach ($accountsByType[$type] ?? [] as $acc): ?>
                                        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_code'] . ' — ' . $acc['account_name']) ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><input type="text" name="entry_description[]" class="jv-input" placeholder="<?= $isThai ? 'รายละเอียด' : 'Detail' ?>"></div>
                        <div><input type="number" name="debit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><input type="number" name="credit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
                        <div><button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove">✕</button></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Totals Row -->
            <div class="jv-total-row">
                <div style="padding-left:12px; color:#1e293b;"><?= $isThai ? 'รวม' : 'Total' ?></div>
                <div></div>
                <div style="text-align:right; padding-right:12px; font-family:monospace;" id="totalDebit">฿0.00</div>
                <div style="text-align:right; padding-right:12px; font-family:monospace;" id="totalCredit">฿0.00</div>
                <div></div>
            </div>

            <!-- Balance Indicator -->
            <div style="text-align:center; padding:12px; margin-top:8px; border-radius:8px;" id="balanceIndicator">
                <span id="balanceText" style="font-weight:600; font-size:14px;"></span>
            </div>
        </div>

        <!-- Submit -->
        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="index.php?page=journal_list" style="padding:12px 28px; background:#f1f5f9; color:#64748b; text-decoration:none; border-radius:10px; font-weight:600; font-size:15px;">
                <?= $isThai ? 'ยกเลิก' : 'Cancel' ?>
            </a>
            <button type="submit" id="submitBtn" style="padding:12px 28px; background:#0d9488; color:white; border:none; border-radius:10px; font-weight:600; font-size:15px; cursor:pointer;">
                💾 <?= $isEdit ? ($isThai ? 'บันทึกการแก้ไข' : 'Save Changes') : ($isThai ? 'สร้างใบสำคัญ' : 'Create Voucher') ?>
            </button>
        </div>
    </form>
</div>

<script>
// Account options HTML template (for new rows)
const accountOptionsHtml = document.querySelector('select[name="account_id[]"]').innerHTML;

function addEntryRow() {
    const container = document.getElementById('entriesContainer');
    const idx = container.children.length;
    const row = document.createElement('div');
    row.className = 'jv-entry-row';
    row.dataset.row = idx;
    row.innerHTML = `
        <div><select name="account_id[]" class="jv-input" required>${accountOptionsHtml}</select></div>
        <div><input type="text" name="entry_description[]" class="jv-input" placeholder="<?= $isThai ? 'รายละเอียด' : 'Detail' ?>"></div>
        <div><input type="number" name="debit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
        <div><input type="number" name="credit[]" class="jv-input jv-amount" step="0.01" min="0" placeholder="0.00" oninput="updateTotals()"></div>
        <div><button type="button" class="btn-remove" onclick="removeRow(this)" title="Remove">✕</button></div>
    `;
    container.appendChild(row);
}

function removeRow(btn) {
    const container = document.getElementById('entriesContainer');
    if (container.children.length <= 2) {
        alert('<?= $isThai ? 'ต้องมีอย่างน้อย 2 รายการ' : 'Minimum 2 entries required' ?>');
        return;
    }
    btn.closest('.jv-entry-row').remove();
    updateTotals();
}

function updateTotals() {
    const debits = document.querySelectorAll('input[name="debit[]"]');
    const credits = document.querySelectorAll('input[name="credit[]"]');
    
    let totalDebit = 0, totalCredit = 0;
    debits.forEach(d => totalDebit += parseFloat(d.value) || 0);
    credits.forEach(c => totalCredit += parseFloat(c.value) || 0);

    document.getElementById('totalDebit').textContent = '฿' + totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('totalCredit').textContent = '฿' + totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    const indicator = document.getElementById('balanceIndicator');
    const text = document.getElementById('balanceText');
    const diff = Math.abs(totalDebit - totalCredit);

    if (totalDebit === 0 && totalCredit === 0) {
        indicator.style.background = '#f8fafc';
        text.textContent = '<?= $isThai ? 'กรุณากรอกจำนวนเงิน' : 'Enter amounts to begin' ?>';
        text.className = '';
        text.style.color = '#94a3b8';
    } else if (diff < 0.01) {
        indicator.style.background = '#f0fdf4';
        text.textContent = '✅ <?= $isThai ? 'ยอดสมดุล (Balanced)' : 'Balanced' ?>';
        text.className = 'jv-balance-ok';
        text.style.color = '#10b981';
    } else {
        indicator.style.background = '#fef2f2';
        text.textContent = '❌ <?= $isThai ? 'ไม่สมดุล — ส่วนต่าง' : 'Unbalanced — Difference' ?>: ฿' + diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        text.className = 'jv-balance-err';
        text.style.color = '#ef4444';
    }
}

// Form validation before submit
document.getElementById('jvForm').addEventListener('submit', function(e) {
    const debits = document.querySelectorAll('input[name="debit[]"]');
    const credits = document.querySelectorAll('input[name="credit[]"]');
    let totalDebit = 0, totalCredit = 0;
    debits.forEach(d => totalDebit += parseFloat(d.value) || 0);
    credits.forEach(c => totalCredit += parseFloat(c.value) || 0);

    if (Math.abs(totalDebit - totalCredit) >= 0.01) {
        e.preventDefault();
        alert('<?= $isThai ? 'ยอดเดบิตและเครดิตต้องเท่ากัน' : 'Total debit and credit must be equal' ?>');
        return false;
    }
    if (totalDebit === 0) {
        e.preventDefault();
        alert('<?= $isThai ? 'กรุณากรอกจำนวนเงิน' : 'Please enter amounts' ?>');
        return false;
    }
});

// Init totals on page load
updateTotals();
</script>
