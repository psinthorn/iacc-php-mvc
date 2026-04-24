<?php
$pageTitle = 'Types';

/**
 * Type (Product) List View
 */
require_once __DIR__ . '/../../../inc/pagination.php';

$search    = $search ?? '';
$status    = $status ?? '';
$hasFilter = $search !== '' || $status !== '' || $cat_id > 0;
$baseUrl   = '?page=type';
?>
<link rel="stylesheet" href="css/master-data.css">
<style>
.search-section{display:flex;flex-direction:column;gap:16px;flex:1}.filter-tabs{display:flex;gap:8px;flex-wrap:wrap}.filter-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:500;color:#64748b;background:#f1f5f9;border:2px solid transparent;text-decoration:none;transition:all .2s}.filter-tab:hover{background:#e2e8f0;color:#475569;text-decoration:none}.filter-tab.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}.filter-tab.active.act{background:linear-gradient(135deg,#10b981,#059669)}.filter-tab.active.ina{background:linear-gradient(135deg,#f59e0b,#d97706)}.tab-count{background:rgba(255,255,255,.2);padding:2px 8px;border-radius:20px;font-size:12px;font-weight:600}.filter-tab:not(.active) .tab-count{background:#e2e8f0;color:#64748b}.action-buttons-group{display:flex;gap:10px;align-items:center;flex-shrink:0}.btn-clear{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border-radius:10px;font-size:14px;font-weight:500;color:#ef4444;background:#fef2f2;border:2px solid #fecaca;text-decoration:none}.btn-clear:hover{background:#fee2e2;text-decoration:none;color:#dc2626}
.md-toggle{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0}.md-toggle input{opacity:0;width:0;height:0}.md-toggle-track{position:absolute;inset:0;background:#cbd5e1;border-radius:22px;cursor:pointer;transition:.2s}.md-toggle input:checked + .md-toggle-track{background:#10b981}.md-toggle-thumb{position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s;pointer-events:none}.md-toggle input:checked ~ .md-toggle-thumb{transform:translateX(18px)}.row-inactive td{opacity:.5}
.badge-cat{background:#e9ecef;color:#495057;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.cat-filter-wrap{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cat-select{padding:6px 12px;border-radius:10px;border:2px solid #e2e8f0;font-size:14px;color:#475569;background:#f8fafc;cursor:pointer}
.cat-select:focus{outline:none;border-color:#667eea}
</style>

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-cube"></i> <?=$xml->product ?? 'Product Type Management'?></h2>
    <div><span class="text-muted">Master Data</span></div>
</div>

<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-cube stat-icon"></i>
        <div class="stat-value"><?=$stats['total']?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->products ?? 'Types'?></div>
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
                <input type="hidden" name="page" value="type">
                <input type="hidden" name="status" value="<?=htmlspecialchars($status)?>">
                <input type="hidden" name="cat_id" value="<?=$cat_id?>">
                <input type="text" class="md-search-input" name="search"
                       placeholder="<?=$xml->search ?? 'Search'?> type..."
                       value="<?=htmlspecialchars($search)?>" autocomplete="off">
                <button type="submit" class="md-search-btn"><i class="fa fa-arrow-right"></i></button>
            </form>
        </div>
        <div class="cat-filter-wrap">
            <div class="filter-tabs">
                <a href="<?=$baseUrl?>&cat_id=<?=$cat_id?>&search=<?=urlencode($search)?>" class="filter-tab <?=$status==='' ? 'active' : ''?>">
                    <i class="fa fa-th-list"></i><span><?=$xml->all ?? 'All'?></span><span class="tab-count"><?=$stats['total']?></span>
                </a>
                <a href="<?=$baseUrl?>&cat_id=<?=$cat_id?>&search=<?=urlencode($search)?>&status=active" class="filter-tab <?=$status==='active' ? 'active act' : ''?>">
                    <i class="fa fa-check-circle"></i><span><?=$xml->active ?? 'Active'?></span><span class="tab-count"><?=$stats['active']?></span>
                </a>
                <a href="<?=$baseUrl?>&cat_id=<?=$cat_id?>&search=<?=urlencode($search)?>&status=inactive" class="filter-tab <?=$status==='inactive' ? 'active ina' : ''?>">
                    <i class="fa fa-pause-circle"></i><span><?=$xml->inactive ?? 'Inactive'?></span><span class="tab-count"><?=$stats['inactive']?></span>
                </a>
            </div>
            <select class="cat-select" onchange="window.location='<?=$baseUrl?>&status=<?=urlencode($status)?>&search=<?=urlencode($search)?>&cat_id='+this.value">
                <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->category ?? 'Categories'?></option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?=$cat['id']?>" <?=$cat_id == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
                <?php endforeach; ?>
            </select>
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
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->product ?? 'Type'?></h4>
        <a href="<?=$baseUrl?>" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=type_store" method="post" id="productForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="type_name" name="type_name" required
                       placeholder="Enter type name..."
                       value="<?=htmlspecialchars($edit_data['name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label><i class="fa fa-folder"></i> <?=$xml->category ?? 'Category'?> <span class="text-danger">*</span></label>
                <select class="form-control" name="cat_id" required>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->category ?? 'Category'?> --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?=$cat['id']?>" <?=($edit_data['cat_id'] ?? 0) == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" name="des" rows="2"
                       placeholder="Enter description..."><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label><i class="fa fa-bookmark"></i> <?=$xml->brandonthistype ?? 'Associated Brands'?></label>
                <div style="display:flex;flex-wrap:wrap;gap:10px;padding:10px;background:#f8f9fa;border-radius:8px;max-height:150px;overflow-y:auto;">
                    <?php foreach ($brands as $brand):
                        $checked = in_array($brand['id'], $edit_brand_ids) ? 'checked' : '';
                    ?>
                    <label style="display:flex;align-items:center;gap:5px;cursor:pointer;padding:5px 10px;background:white;border-radius:5px;border:1px solid #ddd;">
                        <input type="checkbox" name="<?=$brand['id']?>" <?=$checked?>>
                        <?=htmlspecialchars($brand['brand_name'])?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="type">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save"><i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Type')?></button>
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
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->category ?? 'Category'?></th>
                <th width="80"><?=$xml->brand ?? 'Brands'?></th>
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
            <tr class="<?=$isActive ? '' : 'row-inactive'?>" id="row-type-<?=$data['id']?>">
                <td class="text-muted"><?=$row_num?></td>
                <td>
                    <span class="item-name"><?=htmlspecialchars($data['name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(mb_strimwidth($data['des'], 0, 60, '…'))?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge-cat"><?=htmlspecialchars($data['cat_name'])?></span></td>
                <td><span class="badge badge-info"><?=$data['brand_count']?></span></td>
                <td class="text-center">
                    <label class="md-toggle">
                        <input type="checkbox" <?=$isActive ? 'checked' : ''?>
                               onchange="mdToggle('type_toggle', <?=$data['id']?>, this)">
                        <div class="md-toggle-track"></div>
                        <div class="md-toggle-thumb"></div>
                    </label>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="<?=$baseUrl?>&edit=<?=$data['id']?>" class="btn btn-edit"><i class="fa fa-pencil"></i></a>
                        <a href="?page=type_delete&id=<?=$data['id']?>" class="btn btn-delete"
                           onclick="return confirm('Delete this type?');"><i class="fa fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php $paginationParams = $query_params; unset($paginationParams['p']);
    echo render_pagination($pagination, '?page=type', $paginationParams, 'p'); ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="fa fa-cube"></i>
        <h4><?=$xml->no_data ?? 'No Types Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first type'?></p>
        <a href="<?=$baseUrl?>&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Type'?></a>
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
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior:'smooth', block:'start' });
        var inp = document.getElementById('type_name');
        if (inp) setTimeout(() => inp.focus(), 300);
    }
});
</script>
