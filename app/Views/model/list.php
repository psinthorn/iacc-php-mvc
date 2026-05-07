<?php
$pageTitle = 'Models';

/**
 * Model List View
 */
require_once __DIR__ . '/../../../inc/pagination.php';

$search    = $search ?? '';
$status    = $status ?? '';
$hasFilter = $search !== '' || $status !== '' || $type_id > 0 || $brand_id > 0;
$baseUrl   = '?page=mo_list';

// Flash messages
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<link rel="stylesheet" href="css/master-data.css">
<style>
.search-section{display:flex;flex-direction:column;gap:16px;flex:1}.filter-tabs{display:flex;gap:8px;flex-wrap:wrap}.filter-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:500;color:#64748b;background:#f1f5f9;border:2px solid transparent;text-decoration:none;transition:all .2s}.filter-tab:hover{background:#e2e8f0;color:#475569;text-decoration:none}.filter-tab.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}.filter-tab.active.act{background:linear-gradient(135deg,#10b981,#059669)}.filter-tab.active.ina{background:linear-gradient(135deg,#f59e0b,#d97706)}.tab-count{background:rgba(255,255,255,.2);padding:2px 8px;border-radius:20px;font-size:12px;font-weight:600}.filter-tab:not(.active) .tab-count{background:#e2e8f0;color:#64748b}.action-buttons-group{display:flex;gap:10px;align-items:center;flex-shrink:0}.btn-clear{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:500;color:#ef4444;background:#fef2f2;border:2px solid #fecaca;text-decoration:none}.btn-clear:hover{background:#fee2e2;text-decoration:none;color:#dc2626}
.md-toggle{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0}.md-toggle input{opacity:0;width:0;height:0}.md-toggle-track{position:absolute;inset:0;background:#cbd5e1;border-radius:22px;cursor:pointer;transition:.2s}.md-toggle input:checked + .md-toggle-track{background:#10b981}.md-toggle-thumb{position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s;pointer-events:none}.md-toggle input:checked ~ .md-toggle-thumb{transform:translateX(18px)}.row-inactive td{opacity:.5}
.badge-type{background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}.badge-brand{background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.filter-selects{display:flex;gap:8px;flex-wrap:wrap}
.filter-select-wrap select{padding:6px 12px;border-radius:10px;border:2px solid #e2e8f0;font-size:14px;color:#475569;background:#f8fafc;cursor:pointer}
.filter-select-wrap select:focus{outline:none;border-color:#667eea}
.flash-message{padding:14px 20px;border-radius:10px;margin-bottom:16px;display:flex;align-items:center;gap:12px;font-weight:500}
.flash-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}.flash-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.flash-close{margin-left:auto;background:none;border:none;font-size:22px;cursor:pointer;opacity:.6;color:inherit}.flash-close:hover{opacity:1}
</style>

<div class="master-data-container">

<?php if ($flash_success): ?>
<div class="flash-message flash-success" id="flashMsg">
    <i class="fa fa-check-circle"></i> <?=htmlspecialchars($flash_success)?>
    <button class="flash-close" onclick="this.parentElement.style.display='none'">&times;</button>
</div>
<script>setTimeout(()=>{var e=document.getElementById('flashMsg');if(e)e.style.display='none';},5000);</script>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="flash-message flash-danger" id="flashMsgErr">
    <i class="fa fa-exclamation-circle"></i> <?=htmlspecialchars($flash_error)?>
    <button class="flash-close" onclick="this.parentElement.style.display='none'">&times;</button>
</div>
<script>setTimeout(()=>{var e=document.getElementById('flashMsgErr');if(e)e.style.display='none';},5000);</script>
<?php endif; ?>

<div class="master-data-header">
    <h2><i class="fa fa-tags"></i> <?=$xml->model ?? 'Model Management'?></h2>
    <div><span class="text-muted">Master Data</span></div>
</div>

<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-tags stat-icon"></i>
        <div class="stat-value"><?=$stats['total']?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->model ?? 'Models'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$stats['active']?></div>
        <div class="stat-label"><?=$xml->active ?? 'Active'?></div>
    </div>
    <div class="stat-card warning">
        <i class="fa fa-pause-circle stat-icon"></i>
        <div class="stat-value"><?=$stats['inactive']?></div>
        <div class="stat-label"><?=$xml->inactive ?? 'Inactive'?></div>
    </div>
</div>

<div class="action-toolbar">
    <div class="search-section">
        <div class="md-search-box md-search-has-btn" style="max-width:500px;">
            <i class="fa fa-search md-search-icon"></i>
            <form method="get" action="">
                <input type="hidden" name="page" value="mo_list">
                <input type="hidden" name="status" value="<?=htmlspecialchars($status)?>">
                <input type="hidden" name="type_id" value="<?=$type_id?>">
                <input type="hidden" name="brand_id" value="<?=$brand_id?>">
                <input type="text" class="md-search-input" name="search"
                       placeholder="<?=$xml->search ?? 'Search'?> model..."
                       value="<?=htmlspecialchars($search)?>" autocomplete="off">
                <button type="submit" class="md-search-btn"><i class="fa fa-arrow-right"></i></button>
            </form>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <div class="filter-tabs">
                <a href="<?=$baseUrl?>&type_id=<?=$type_id?>&brand_id=<?=$brand_id?>&search=<?=urlencode($search)?>" class="filter-tab <?=$status==='' ? 'active' : ''?>">
                    <i class="fa fa-th-list"></i><span><?=$xml->all ?? 'All'?></span><span class="tab-count"><?=$stats['total']?></span>
                </a>
                <a href="<?=$baseUrl?>&type_id=<?=$type_id?>&brand_id=<?=$brand_id?>&search=<?=urlencode($search)?>&status=active" class="filter-tab <?=$status==='active' ? 'active act' : ''?>">
                    <i class="fa fa-check-circle"></i><span><?=$xml->active ?? 'Active'?></span><span class="tab-count"><?=$stats['active']?></span>
                </a>
                <a href="<?=$baseUrl?>&type_id=<?=$type_id?>&brand_id=<?=$brand_id?>&search=<?=urlencode($search)?>&status=inactive" class="filter-tab <?=$status==='inactive' ? 'active ina' : ''?>">
                    <i class="fa fa-pause-circle"></i><span><?=$xml->inactive ?? 'Inactive'?></span><span class="tab-count"><?=$stats['inactive']?></span>
                </a>
            </div>
            <div class="filter-selects">
                <div class="filter-select-wrap">
                    <select onchange="window.location='<?=$baseUrl?>&status=<?=urlencode($status)?>&brand_id=<?=$brand_id?>&search=<?=urlencode($search)?>&type_id='+this.value">
                        <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->type ?? 'Types'?></option>
                        <?php foreach ($types as $t): ?>
                        <option value="<?=$t['id']?>" <?=$type_id == $t['id'] ? 'selected' : ''?>><?=htmlspecialchars($t['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-select-wrap">
                    <select onchange="window.location='<?=$baseUrl?>&status=<?=urlencode($status)?>&type_id=<?=$type_id?>&search=<?=urlencode($search)?>&brand_id='+this.value">
                        <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->brand ?? 'Brands'?></option>
                        <?php foreach ($brands as $b): ?>
                        <option value="<?=$b['id']?>" <?=$brand_id == $b['id'] ? 'selected' : ''?>><?=htmlspecialchars($b['brand_name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="action-buttons-group">
        <?php if ($hasFilter): ?>
        <a href="<?=$baseUrl?>" class="btn-clear"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="<?=$baseUrl?>&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="index.php?page=master_data_guide" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->model ?? 'Model'?></h4>
        <a href="<?=$baseUrl?>" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=mo_list_store" method="post" id="modelForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label><i class="fa fa-tag"></i> <?=$xml->model ?? 'Model'?> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="model_name" name="model_name" required
                       placeholder="Enter model name..."
                       value="<?=htmlspecialchars($edit_data['model_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label><i class="fa fa-money"></i> <?=$xml->price ?? 'Price'?></label>
                <input type="number" step="0.01" class="form-control" name="price"
                       placeholder="0.00"
                       value="<?=htmlspecialchars($edit_data['price'] ?? '')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label><i class="fa fa-cube"></i> <?=$xml->type ?? 'Product Type'?><?php if (!$edit_data): ?> <span class="text-danger">*</span><?php endif; ?></label>
                <select class="form-control" name="type" id="typeSelect" <?=$edit_data ? '' : 'required'?> onchange="loadBrands(this.value)">
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> --</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?=$t['id']?>" <?=($edit_data['type_id'] ?? 0) == $t['id'] ? 'selected' : ''?>><?=htmlspecialchars($t['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand'?><?php if (!$edit_data): ?> <span class="text-danger">*</span><?php endif; ?></label>
                <select class="form-control" id="brand" name="brand" <?=$edit_data ? '' : 'required'?>>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> first --</option>
                    <?php if ($edit_data): foreach ($brands as $b): ?>
                    <option value="<?=$b['id']?>" <?=($edit_data['brand_id'] ?? 0) == $b['id'] ? 'selected' : ''?>><?=htmlspecialchars($b['brand_name'])?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" name="des" rows="4"
                       placeholder="Enter description..." style="min-height:100px;"><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
            </div>
        </div>
        <?php
            // v6.6 #135 follow-up — LINE catalog visibility toggle.
            // Defaults to 1 (visible) for new rows; admins uncheck to hide
            // non-tour models like entrance fees from the customer-facing
            // carousel triggered by "ดูทัวร์" / "show tours".
            $_isThai = ($_SESSION['lang'] ?? '0') === '1';
            $_isCustomerBookable = (int)($edit_data['is_customer_bookable'] ?? 1);
        ?>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_customer_bookable" value="1"
                           <?= $_isCustomerBookable === 1 ? 'checked' : '' ?>
                           style="width:auto; margin:0;">
                    <span style="font-weight:500;">
                        <i class="fa fa-line-chart" style="color:#06C755;"></i>
                        <?= $_isThai ? 'แสดงในรายการทัวร์ LINE OA' : 'Show in LINE OA catalog' ?>
                    </span>
                </label>
                <small class="text-muted" style="display:block; margin-top:4px; margin-left:24px;">
                    <?= $_isThai
                        ? 'ติ๊กเพื่อให้ทัวร์นี้แสดงในรายการที่ลูกค้าเห็นเมื่อพิมพ์ "ดูทัวร์" ผ่าน LINE OA — ยกเลิกถ้าเป็นรายการที่ไม่ใช่ทัวร์ (เช่น ค่าเข้าหน้าท่า)'
                        : 'Check to include this row in the customer-facing carousel triggered by "show tours" / "ดูทัวร์". Uncheck for non-tour items (e.g. entrance fees).' ?>
                </small>
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="mo_list">
            <input type="hidden" name="p_id" value="<?=$edit_data['id'] ?? ''?>">
            <?php if ($type_id > 0): ?><input type="hidden" name="type_id" value="<?=$type_id?>"><?php endif; ?>
            <?php if ($brand_id > 0): ?><input type="hidden" name="brand_id" value="<?=$brand_id?>"><?php endif; ?>
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>"><?php endif; ?>
            <button type="submit" class="btn btn-save"><i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Model')?></button>
            <a href="<?=$baseUrl?>" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
        </div>
    </form>
</div>

<div class="master-data-table">
    <?php if ($item_count > 0): ?>
    <table class="table table-hover">
        <thead>
            <tr>
                <th width="50">#</th>
                <th><?=$xml->model ?? 'Model'?></th>
                <th><?=$xml->type ?? 'Type'?></th>
                <th><?=$xml->brand ?? 'Brand'?></th>
                <th width="110"><?=$xml->price ?? 'Price'?></th>
                <th width="80" class="text-center"><?=$xml->active ?? 'Active'?></th>
                <th width="100"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
            <?php $row_num = $pagination['offset'];
            foreach ($items as $data):
                $row_num++;
                $isActive = intval($data['is_active'] ?? 1);
            ?>
            <tr class="<?=$isActive ? '' : 'row-inactive'?>" id="row-mo_list-<?=$data['id']?>">
                <td class="text-muted"><?=$row_num?></td>
                <td>
                    <span class="item-name"><?=htmlspecialchars($data['model_name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(mb_strimwidth($data['des'], 0, 50, '…'))?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge-type"><?=htmlspecialchars($data['type_name'])?></span></td>
                <td><span class="badge-brand"><?=htmlspecialchars($data['brand_name'])?></span></td>
                <td>
                    <?php if ($data['price'] > 0): ?>
                    <strong><?=number_format($data['price'], 2)?></strong>
                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                </td>
                <td class="text-center">
                    <label class="md-toggle">
                        <input type="checkbox" <?=$isActive ? 'checked' : ''?>
                               onchange="mdToggle('mo_list_toggle', <?=$data['id']?>, this)">
                        <div class="md-toggle-track"></div>
                        <div class="md-toggle-thumb"></div>
                    </label>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="<?=$baseUrl?>&edit=<?=$data['id']?><?=$type_id > 0 ? '&type_id='.$type_id : ''?><?=$brand_id > 0 ? '&brand_id='.$brand_id : ''?><?=!empty($search) ? '&search='.urlencode($search) : ''?>" class="btn btn-edit"><i class="fa fa-pencil"></i></a>
                        <a href="?page=mo_list_delete&p_id=<?=$data['id']?><?=$type_id > 0 ? '&type_id='.$type_id : ''?><?=$brand_id > 0 ? '&brand_id='.$brand_id : ''?><?=!empty($search) ? '&search='.urlencode($search) : ''?>"
                           class="btn btn-delete"
                           onclick="return confirm('Delete this model?');"><i class="fa fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php $paginationParams = $query_params; unset($paginationParams['p']);
    echo render_pagination($pagination, '?page=mo_list', $paginationParams, 'p'); ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="fa fa-tags"></i>
        <h4><?=$xml->no_data ?? 'No Models Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first model'?></p>
        <a href="<?=$baseUrl?>&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Model'?></a>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
function mdToggle(route, id, cb) {
    var active = cb.checked ? 1 : 0;
    var row = document.getElementById('row-' + route.replace('_toggle','') + '-' + id) || cb.closest('tr');
    var fd = new FormData();
    fd.append('id', id); fd.append('active', active);
    fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    fetch('index.php?page=' + route, { method:'POST', body:fd })
        .then(r => r.json())
        .then(d => { if (!d.success) { cb.checked = !cb.checked; return; }
            row && row.classList.toggle('row-inactive', !d.active); })
        .catch(() => { cb.checked = !cb.checked; });
}
function loadBrands(typeId) {
    if (!typeId) { document.getElementById('brand').innerHTML = '<option value="">-- Select Type first --</option>'; return; }
    fetch('index.php?page=mo_list_brands&q=' + typeId)
        .then(r => r.text())
        .then(html => { document.getElementById('brand').innerHTML = html; });
}
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior:'smooth', block:'start' });
        var inp = document.getElementById('model_name');
        if (inp) setTimeout(() => inp.focus(), 300);

        // In edit mode: pre-load brands for the already-selected type
        var typeSelect = document.getElementById('typeSelect');
        var brandSelect = document.getElementById('brand');
        if (typeSelect && typeSelect.value && brandSelect) {
            var currentBrandId = brandSelect.value; // capture before replacing
            fetch('index.php?page=mo_list_brands&q=' + typeSelect.value)
                .then(r => r.text())
                .then(html => {
                    brandSelect.innerHTML = html;
                    // Re-select the current brand after loading filtered list
                    if (currentBrandId) {
                        var opt = brandSelect.querySelector('option[value="' + currentBrandId + '"]');
                        if (opt) opt.selected = true;
                    }
                });
        }
    }
});
</script>
