<?php
/**
 * Chart of Accounts — Modern UI
 * 
 * Account list with add/edit modal, grouped by type
 * Variables from controller: $accounts, $trialBalance, $filters
 */

$isThai = ($lang ?? '2') === '1';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$typeLabels = [
    'asset'     => $isThai ? 'สินทรัพย์' : 'Assets',
    'liability' => $isThai ? 'หนี้สิน' : 'Liabilities',
    'equity'    => $isThai ? 'ส่วนของเจ้าของ' : 'Equity',
    'revenue'   => $isThai ? 'รายได้' : 'Revenue',
    'expense'   => $isThai ? 'ค่าใช้จ่าย' : 'Expenses',
];
$typeColors = [
    'asset' => '#3b82f6', 'liability' => '#ef4444', 'equity' => '#8b5cf6',
    'revenue' => '#10b981', 'expense' => '#f59e0b',
];
$typeIcons = [
    'asset' => '🏦', 'liability' => '📋', 'equity' => '👤',
    'revenue' => '💰', 'expense' => '💸',
];

$errorMessages = [
    'missing_fields' => $isThai ? 'กรุณากรอกข้อมูลที่จำเป็น' : 'Please fill in required fields',
    'duplicate_code'  => $isThai ? 'รหัสบัญชีซ้ำ' : 'Account code already exists',
];

// Group accounts by type
$grouped = [];
foreach ($accounts as $acc) {
    $grouped[$acc['account_type']][] = $acc;
}
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.coa-modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
.coa-modal { background:white; border-radius:16px; padding:32px; width:90%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
.coa-row:hover { background:#f8fafc; }
</style>

<div class="master-data-container">

    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2>📊 <?= $isThai ? 'ผังบัญชี (Chart of Accounts)' : 'Chart of Accounts' ?></h2>
                <p><?= $isThai ? 'จัดการรหัสบัญชีสำหรับบันทึกรายการ' : 'Manage account codes for journal entries' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=journal_list" class="btn-header btn-header-outline">
                    ← <?= $isThai ? 'สมุดรายวัน' : 'Journal Vouchers' ?>
                </a>
                <button onclick="openModal()" class="btn-header btn-header-primary">
                    ＋ <?= $isThai ? 'เพิ่มบัญชี' : 'Add Account' ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success): ?>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#16a34a; font-weight:500;">
        ✅ <?= $isThai ? 'บันทึกสำเร็จ' : 'Saved successfully' ?>
    </div>
    <?php endif; ?>
    <?php if ($error && isset($errorMessages[$error])): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#dc2626; font-weight:500;">
        ❌ <?= $errorMessages[$error] ?>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; background:white; padding:16px 20px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px;">
        <input type="hidden" name="page" value="journal_accounts">
        <div style="flex:1; min-width:180px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ค้นหา' : 'Search' ?></label>
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="<?= $isThai ? 'รหัสหรือชื่อบัญชี...' : 'Code or name...' ?>" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
        </div>
        <div style="min-width:160px;">
            <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?= $isThai ? 'ประเภท' : 'Type' ?></label>
            <select name="account_type" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                <option value=""><?= $isThai ? 'ทั้งหมด' : 'All Types' ?></option>
                <?php foreach ($typeLabels as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['account_type'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" style="padding:8px 20px; background:#0d9488; color:white; border:none; border-radius:8px; font-size:14px; cursor:pointer; font-weight:600;">🔍</button>
        <a href="index.php?page=journal_accounts" style="padding:8px 16px; background:#f1f5f9; color:#64748b; text-decoration:none; border-radius:8px; font-size:14px;">↻</a>
    </form>

    <!-- Account Type Summary Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:24px;">
        <?php foreach ($typeLabels as $type => $label): ?>
        <div style="background:white; border-radius:12px; padding:16px; border-left:4px solid <?= $typeColors[$type] ?>; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="font-size:22px; font-weight:700; color:<?= $typeColors[$type] ?>;"><?= $typeIcons[$type] ?> <?= count($grouped[$type] ?? []) ?></div>
            <div style="font-size:13px; color:#64748b;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Accounts Table Grouped by Type -->
    <?php foreach ($typeLabels as $type => $typeLabel): ?>
    <?php $typeAccounts = $grouped[$type] ?? []; ?>
    <?php if (empty($typeAccounts) && !empty($filters['account_type'])) continue; ?>
    
    <div style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:20px;">
        <div style="padding:14px 20px; background:<?= $typeColors[$type] ?>10; border-bottom:2px solid <?= $typeColors[$type] ?>20; display:flex; align-items:center; gap:8px;">
            <span style="font-size:18px;"><?= $typeIcons[$type] ?></span>
            <h3 style="margin:0; font-size:15px; color:<?= $typeColors[$type] ?>; font-weight:700;">
                <?= $typeLabel ?> (<?= count($typeAccounts) ?>)
            </h3>
        </div>
        <?php if (empty($typeAccounts)): ?>
        <div style="padding:30px; text-align:center; color:#94a3b8; font-size:14px;">
            <?= $isThai ? 'ไม่มีบัญชีในหมวดนี้' : 'No accounts in this category' ?>
        </div>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 16px; text-align:left; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'รหัส' : 'Code' ?></th>
                    <th style="padding:10px 16px; text-align:left; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'ชื่อบัญชี' : 'Account Name' ?></th>
                    <th style="padding:10px 16px; text-align:center; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'ระดับ' : 'Level' ?></th>
                    <th style="padding:10px 16px; text-align:center; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'ด้านปกติ' : 'Normal' ?></th>
                    <th style="padding:10px 16px; text-align:center; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'สถานะ' : 'Status' ?></th>
                    <th style="padding:10px 16px; text-align:center; font-size:12px; font-weight:600; color:#64748b;"><?= $isThai ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($typeAccounts as $acc): ?>
                <tr class="coa-row" style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 16px; font-family:monospace; font-weight:600; color:<?= $typeColors[$type] ?>; padding-left:<?= 16 + ($acc['level'] - 1) * 20 ?>px;">
                        <?= htmlspecialchars($acc['account_code']) ?>
                    </td>
                    <td style="padding:10px 16px; font-size:14px;">
                        <?= htmlspecialchars($acc['account_name']) ?>
                        <?php if ($isThai && $acc['account_name_th']): ?>
                            <span style="display:block; font-size:12px; color:#94a3b8;"><?= htmlspecialchars($acc['account_name_th']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 16px; text-align:center; font-size:13px; color:#64748b;"><?= $acc['level'] ?></td>
                    <td style="padding:10px 16px; text-align:center;">
                        <span style="padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; background:<?= $acc['normal_balance'] === 'debit' ? '#dbeafe' : '#fce7f3' ?>; color:<?= $acc['normal_balance'] === 'debit' ? '#2563eb' : '#db2777' ?>;">
                            <?= strtoupper($acc['normal_balance']) ?>
                        </span>
                    </td>
                    <td style="padding:10px 16px; text-align:center;">
                        <form method="POST" action="index.php?page=journal_account_toggle" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                            <button type="submit" style="background:none; border:none; cursor:pointer; font-size:16px;" title="<?= $acc['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <?= $acc['is_active'] ? '✅' : '⬜' ?>
                            </button>
                        </form>
                    </td>
                    <td style="padding:10px 16px; text-align:center;">
                        <button onclick='editAccount(<?= json_encode($acc) ?>)' style="background:none; border:none; cursor:pointer; font-size:14px; color:#3b82f6;" title="Edit">✏️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

</div>

<!-- Add/Edit Modal -->
<div class="coa-modal-overlay" id="accountModal" onclick="if(event.target===this)closeModal()">
    <div class="coa-modal">
        <h3 style="margin:0 0 20px; font-size:18px; color:#1e293b;" id="modalTitle">
            ＋ <?= $isThai ? 'เพิ่มบัญชี' : 'Add Account' ?>
        </h3>
        <form method="POST" action="index.php?page=journal_account_store" id="accountForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" id="formId" value="">

            <div style="display:grid; gap:14px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'รหัสบัญชี' : 'Account Code' ?> *</label>
                        <input type="text" name="account_code" id="formCode" required style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; font-family:monospace;" placeholder="e.g. 1150">
                    </div>
                    <div>
                        <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'ประเภท' : 'Type' ?> *</label>
                        <select name="account_type" id="formType" required style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                            <?php foreach ($typeLabels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'ชื่อบัญชี (EN)' : 'Account Name (EN)' ?> *</label>
                    <input type="text" name="account_name" id="formName" required style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'ชื่อบัญชี (TH)' : 'Account Name (TH)' ?></label>
                    <input type="text" name="account_name_th" id="formNameTh" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'ระดับ' : 'Level' ?></label>
                        <select name="level" id="formLevel" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                            <option value="1"><?= $isThai ? '1 — หมวดหลัก' : '1 — Group' ?></option>
                            <option value="2" selected><?= $isThai ? '2 — หมวดย่อย' : '2 — Sub-group' ?></option>
                            <option value="3"><?= $isThai ? '3 — รายละเอียด' : '3 — Detail' ?></option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                        <select name="is_active" id="formActive" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                            <option value="1"><?= $isThai ? 'ใช้งาน' : 'Active' ?></option>
                            <option value="0"><?= $isThai ? 'ไม่ใช้งาน' : 'Inactive' ?></option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:4px;"><?= $isThai ? 'คำอธิบาย' : 'Description' ?></label>
                    <input type="text" name="description" id="formDesc" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeModal()" style="padding:10px 24px; background:#f1f5f9; color:#64748b; border:none; border-radius:10px; font-weight:600; cursor:pointer;">
                    <?= $isThai ? 'ยกเลิก' : 'Cancel' ?>
                </button>
                <button type="submit" style="padding:10px 24px; background:#0d9488; color:white; border:none; border-radius:10px; font-weight:600; cursor:pointer;">
                    💾 <?= $isThai ? 'บันทึก' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('accountModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = '＋ <?= $isThai ? "เพิ่มบัญชี" : "Add Account" ?>';
    document.getElementById('formId').value = '';
    document.getElementById('formCode').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formNameTh').value = '';
    document.getElementById('formType').value = 'asset';
    document.getElementById('formLevel').value = '2';
    document.getElementById('formActive').value = '1';
    document.getElementById('formDesc').value = '';
}

function editAccount(acc) {
    document.getElementById('accountModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = '✏️ <?= $isThai ? "แก้ไขบัญชี" : "Edit Account" ?>';
    document.getElementById('formId').value = acc.id;
    document.getElementById('formCode').value = acc.account_code;
    document.getElementById('formName').value = acc.account_name;
    document.getElementById('formNameTh').value = acc.account_name_th || '';
    document.getElementById('formType').value = acc.account_type;
    document.getElementById('formLevel').value = acc.level;
    document.getElementById('formActive').value = acc.is_active;
    document.getElementById('formDesc').value = acc.description || '';
}

function closeModal() {
    document.getElementById('accountModal').style.display = 'none';
}
</script>
