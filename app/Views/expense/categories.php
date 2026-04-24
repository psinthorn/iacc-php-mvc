<?php
$pageTitle = 'Expenses — Categories';

/**
 * Expense Categories — Management Page
 * 
 * Uses master-data.css design system
 * Variables from controller: $categories, $message, $lang
 */

$isThai = ($lang ?? '2') === '1';

$messages = [
    'created' => ['✅', $isThai ? 'สร้างหมวดหมู่สำเร็จ' : 'Category created'],
    'updated' => ['✅', $isThai ? 'อัพเดทสำเร็จ' : 'Category updated'],
    'deleted' => ['🗑️', $isThai ? 'ลบสำเร็จ' : 'Category deleted'],
    'code_exists' => ['⚠️', $isThai ? 'รหัสนี้มีอยู่แล้ว' : 'Code already exists'],
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-top: 16px; }
.cat-card {
    background: white; border-radius: 14px; padding: 20px;
    border: 1px solid var(--md-border, #e2e8f0);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03); transition: all 0.25s ease;
}
.cat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.cat-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.cat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.cat-card-header h3 { margin: 0; font-size: 15px; }
.cat-card-header .code { font-size: 11px; color: #94a3b8; font-weight: 500; }
.cat-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
.cat-count { font-size: 13px; color: #64748b; }
.cat-actions { display: flex; gap: 8px; }
.cat-btn { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; }
.cat-btn:hover { background: var(--md-primary, #4f46e5); color: white; border-color: var(--md-primary); }
.inactive-badge { background: #fef2f2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }

/* Modal */
.modal-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:1000; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:white; border-radius:16px; padding:28px; width:100%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
.modal-box h3 { margin:0 0 20px; }
.modal-form-group { margin-bottom: 16px; }
.modal-form-group label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.modal-form-group input, .modal-form-group textarea { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; box-sizing:border-box; height:44px; min-height:44px; }
.modal-form-group textarea { height:auto; min-height:80px; }
.modal-form-group input[type="color"] { height:44px; min-height:44px; padding:4px 8px; }
.modal-form-group input:focus { border-color:var(--md-primary,#4f46e5); box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
.modal-actions { display:flex; gap:12px; justify-content:flex-end; margin-top:20px; }
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-tags"></i> <?= $isThai ? 'หมวดหมู่ค่าใช้จ่าย' : 'Expense Categories' ?></h2>
                <p><?= $isThai ? 'จัดการหมวดหมู่สำหรับจำแนกค่าใช้จ่าย' : 'Manage categories for organizing expenses' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=expense_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
                <button onclick="openModal()" class="btn-header btn-header-primary">
                    <i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มหมวดหมู่' : 'Add Category' ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <!-- Category Grid -->
    <div class="cat-grid">
        <?php if (empty($categories)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:60px 0; color:#94a3b8;">
            <i class="fa fa-tags" style="font-size:48px; display:block; margin-bottom:16px;"></i>
            <?= $isThai ? 'ยังไม่มีหมวดหมู่' : 'No categories yet' ?>
        </div>
        <?php else: ?>
        <?php foreach ($categories as $cat): ?>
        <div class="cat-card" style="border-left: 4px solid <?= $cat['color'] ?? '#6366f1' ?>;">
            <div class="cat-card-header">
                <div class="cat-icon" style="background: <?= $cat['color'] ?? '#6366f1' ?>22; color: <?= $cat['color'] ?? '#6366f1' ?>;">
                    <i class="fa <?= $cat['icon'] ?? 'fa-folder' ?>"></i>
                </div>
                <div>
                    <h3><?= htmlspecialchars($isThai && $cat['name_th'] ? $cat['name_th'] : $cat['name']) ?></h3>
                    <div class="code"><?= htmlspecialchars($cat['code'] ?? '-') ?></div>
                </div>
                <?php if (!$cat['is_active']): ?>
                    <span class="inactive-badge"><?= $isThai ? 'ปิดใช้งาน' : 'Inactive' ?></span>
                <?php endif; ?>
            </div>
            <div class="cat-meta">
                <span class="cat-count">
                    <i class="fa fa-file-text-o"></i> <?= $cat['expense_count'] ?? 0 ?> <?= $isThai ? 'รายการ' : 'expenses' ?>
                </span>
                <div class="cat-actions">
                    <button class="cat-btn" onclick='openModal(<?= json_encode($cat) ?>)' title="<?= $isThai ? 'แก้ไข' : 'Edit' ?>"><i class="fa fa-pencil"></i></button>
                    <form method="post" action="index.php?page=expense_cat_toggle" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="cat-btn" title="<?= $cat['is_active'] ? ($isThai ? 'ปิดใช้งาน' : 'Disable') : ($isThai ? 'เปิดใช้งาน' : 'Enable') ?>">
                            <i class="fa fa-<?= $cat['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                        </button>
                    </form>
                    <?php if (($cat['expense_count'] ?? 0) == 0): ?>
                    <form method="post" action="index.php?page=expense_cat_delete" style="display:inline;" onsubmit="return confirm('<?= $isThai ? 'ลบหมวดหมู่นี้?' : 'Delete this category?' ?>')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="cat-btn" style="color:#ef4444;" title="<?= $isThai ? 'ลบ' : 'Delete' ?>"><i class="fa fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="catModal">
    <div class="modal-box">
        <h3 id="modalTitle"><i class="fa fa-tags"></i> <?= $isThai ? 'เพิ่มหมวดหมู่' : 'Add Category' ?></h3>
        <form method="post" action="index.php?page=expense_cat_store">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="catId" value="">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="modal-form-group">
                    <label><?= $isThai ? 'ชื่อ (EN)' : 'Name (EN)' ?> *</label>
                    <input type="text" name="name" id="catName" required>
                </div>
                <div class="modal-form-group">
                    <label><?= $isThai ? 'ชื่อ (TH)' : 'Name (TH)' ?></label>
                    <input type="text" name="name_th" id="catNameTh">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                <div class="modal-form-group">
                    <label><?= $isThai ? 'รหัส' : 'Code' ?></label>
                    <input type="text" name="code" id="catCode" placeholder="EXP-XXX" style="text-transform:uppercase;">
                </div>
                <div class="modal-form-group">
                    <label><?= $isThai ? 'ไอคอน' : 'Icon' ?></label>
                    <input type="text" name="icon" id="catIcon" value="fa-folder" placeholder="fa-folder">
                </div>
                <div class="modal-form-group">
                    <label><?= $isThai ? 'สี' : 'Color' ?></label>
                    <input type="color" name="color" id="catColor" value="#6366f1">
                </div>
            </div>
            <div class="modal-form-group">
                <label><?= $isThai ? 'ลำดับ' : 'Sort Order' ?></label>
                <input type="number" name="sort_order" id="catSort" value="0" min="0">
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeModal()" style="padding:10px 24px; background:#f1f5f9; color:#64748b; border:none; border-radius:10px; font-size:14px; cursor:pointer;">
                    <?= $isThai ? 'ยกเลิก' : 'Cancel' ?>
                </button>
                <button type="submit" style="padding:10px 24px; background:var(--md-primary,#4f46e5); color:white; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer;">
                    <i class="fa fa-save"></i> <?= $isThai ? 'บันทึก' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(cat) {
    document.getElementById('catModal').classList.add('active');
    if (cat) {
        document.getElementById('modalTitle').innerHTML = '<i class="fa fa-pencil"></i> <?= $isThai ? "แก้ไขหมวดหมู่" : "Edit Category" ?>';
        document.getElementById('catId').value = cat.id || '';
        document.getElementById('catName').value = cat.name || '';
        document.getElementById('catNameTh').value = cat.name_th || '';
        document.getElementById('catCode').value = cat.code || '';
        document.getElementById('catIcon').value = cat.icon || 'fa-folder';
        document.getElementById('catColor').value = cat.color || '#6366f1';
        document.getElementById('catSort').value = cat.sort_order || 0;
    } else {
        document.getElementById('modalTitle').innerHTML = '<i class="fa fa-plus"></i> <?= $isThai ? "เพิ่มหมวดหมู่" : "Add Category" ?>';
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catNameTh').value = '';
        document.getElementById('catCode').value = '';
        document.getElementById('catIcon').value = 'fa-folder';
        document.getElementById('catColor').value = '#6366f1';
        document.getElementById('catSort').value = '0';
    }
}
function closeModal() {
    document.getElementById('catModal').classList.remove('active');
}
document.getElementById('catModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
