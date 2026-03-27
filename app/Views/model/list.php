<?php
/**
 * Model List View
 * 
 * Variables provided by ModelController::index():
 *   $items        - array of model rows (with type_name, brand_name)
 *   $total_items  - total model count
 *   $item_count   - count of items on current page
 *   $pagination   - pagination data array
 *   $search       - current search term
 *   $type_id      - current type filter
 *   $brand_id     - current brand filter
 *   $edit_data    - model being edited (or null)
 *   $show_form    - whether to show the inline form
 *   $types        - all types for dropdown
 *   $brands       - all brands for dropdown
 *   $query_params - current GET params
 *   $xml          - i18n strings
 */
require_once __DIR__ . '/../../../inc/pagination.php';

// Build return URL with current filters
$return_params = ['page' => 'mo_list'];
if ($type_id > 0) $return_params['type_id'] = $type_id;
if ($brand_id > 0) $return_params['brand_id'] = $brand_id;
if (!empty($search)) $return_params['search'] = $search;
$return_url = http_build_query($return_params);
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<?php
// Display flash messages
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
if ($flash_success): ?>
<div class="flash-message flash-success" id="flashMessage">
    <i class="fa fa-check-circle"></i> <?=htmlspecialchars($flash_success)?>
    <button type="button" class="flash-close" onclick="this.parentElement.style.display='none';">&times;</button>
</div>
<script>setTimeout(function(){ var el = document.getElementById('flashMessage'); if(el) el.style.display='none'; }, 5000);</script>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="flash-message flash-danger" id="flashMessageError">
    <i class="fa fa-exclamation-circle"></i> <?=htmlspecialchars($flash_error)?>
    <button type="button" class="flash-close" onclick="this.parentElement.style.display='none';">&times;</button>
</div>
<script>setTimeout(function(){ var el = document.getElementById('flashMessageError'); if(el) el.style.display='none'; }, 5000);</script>
<?php endif; ?>

<style>
.flash-message { padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; animation: slideDown 0.3s ease-out; }
.flash-message i { font-size: 20px; }
.flash-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.flash-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.flash-close { margin-left: auto; background: none; border: none; font-size: 24px; cursor: pointer; opacity: 0.6; color: inherit; }
.flash-close:hover { opacity: 1; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-tags"></i> <?=$xml->model ?? 'Model Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-tags stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->model ?? 'Models'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box" style="display:flex;gap:12px;max-width:800px;flex-wrap:wrap;align-items:center;">
        <div style="position:relative;flex:1;min-width:280px;">
            <form method="get" action="" style="margin:0;" id="searchForm">
                <i class="fa fa-search"></i>
                <input type="hidden" name="page" value="mo_list">
                <input type="hidden" name="type_id" value="<?=$type_id?>">
                <input type="hidden" name="brand_id" value="<?=$brand_id?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->model ?? 'model'?>..." 
                       value="<?=htmlspecialchars($search)?>" 
                       onchange="this.form.submit()">
            </form>
        </div>
        <select class="form-control filter-select" style="min-width:180px;" onchange="window.location='?page=mo_list&type_id='+this.value+'&brand_id=<?=$brand_id?>&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->type ?? 'Types'?></option>
            <?php foreach ($types as $type): ?>
            <option value="<?=$type['id']?>" <?=$type_id == $type['id'] ? 'selected' : ''?>><?=htmlspecialchars($type['name'])?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control filter-select" style="min-width:180px;" onchange="window.location='?page=mo_list&brand_id='+this.value+'&type_id=<?=$type_id?>&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->brand ?? 'Brands'?></option>
            <?php foreach ($brands as $brand): ?>
            <option value="<?=$brand['id']?>" <?=$brand_id == $brand['id'] ? 'selected' : ''?>><?=htmlspecialchars($brand['brand_name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <?php if (!empty($search) || $type_id > 0 || $brand_id > 0): ?>
        <a href="?page=mo_list" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=mo_list&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="index.php?page=master_data_guide" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->model ?? 'Model'?></h4>
        <a href="?page=mo_list" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=mo_list_store" method="post" id="modelForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="model_name"><i class="fa fa-tag"></i> <?=$xml->model ?? 'Model'?> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="model_name" name="model_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->model ?? 'model'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['model_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="price"><i class="fa fa-money"></i> <?=$xml->price ?? 'Price'?></label>
                <input type="number" step="0.01" class="form-control" id="price" name="price"
                       placeholder="0.00"
                       value="<?=htmlspecialchars($edit_data['price'] ?? '')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="type"><i class="fa fa-cube"></i> <?=$xml->type ?? 'Product Type'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="type" name="type" required onchange="loadBrands(this.value)">
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> --</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?=$t['id']?>" <?=($edit_data['type_id'] ?? 0) == $t['id'] ? 'selected' : ''?>><?=htmlspecialchars($t['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="brand"><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="brand" name="brand" required>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->type ?? 'Type'?> <?=$xml->first ?? 'first'?> --</option>
                    <?php if ($edit_data): 
                        foreach ($brands as $b): ?>
                    <option value="<?=$b['id']?>" <?=($edit_data['brand_id'] ?? 0) == $b['id'] ? 'selected' : ''?>><?=htmlspecialchars($b['brand_name'])?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" id="des" name="des" rows="4"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..." style="min-height:120px;"><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="mo_list">
            <input type="hidden" name="p_id" value="<?=$edit_data['id'] ?? ''?>">
            <?php if ($type_id > 0): ?><input type="hidden" name="type_id" value="<?=$type_id?>"><?php endif; ?>
            <?php if ($brand_id > 0): ?><input type="hidden" name="brand_id" value="<?=$brand_id?>"><?php endif; ?>
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?=htmlspecialchars($search)?>"><?php endif; ?>
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Model')?>
            </button>
            <a href="?page=mo_list" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="master-data-table">
    <?php if ($item_count > 0): ?>
    <table class="table table-hover">
        <thead>
            <tr>
                <th width="50">#</th>
                <th><?=$xml->model ?? 'Model'?></th>
                <th><?=$xml->type ?? 'Type'?></th>
                <th><?=$xml->brand ?? 'Brand'?></th>
                <th width="120"><?=$xml->price ?? 'Price'?></th>
                <th width="120"><?=$xml->actions ?? 'Actions'?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $row_num = $pagination['offset'];
            foreach ($items as $data): 
                $row_num++;
            ?>
            <tr>
                <td class="text-muted"><?=$row_num?></td>
                <td>
                    <span class="item-name"><?=htmlspecialchars($data['model_name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(substr($data['des'], 0, 40))?><?=strlen($data['des']) > 40 ? '...' : ''?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#e0f2fe;color:#0369a1;"><?=htmlspecialchars($data['type_name'])?></span>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#fef3c7;color:#92400e;"><?=htmlspecialchars($data['brand_name'])?></span>
                </td>
                <td>
                    <?php if ($data['price'] > 0): ?>
                    <strong><?=number_format($data['price'], 2)?></strong>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=mo_list&edit=<?=$data['id']?><?=$type_id > 0 ? '&type_id='.$type_id : ''?><?=$brand_id > 0 ? '&brand_id='.$brand_id : ''?><?=!empty($search) ? '&search='.urlencode($search) : ''?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="?page=mo_list_delete&p_id=<?=$data['id']?><?=$type_id > 0 ? '&type_id='.$type_id : ''?><?=$brand_id > 0 ? '&brand_id='.$brand_id : ''?><?=!empty($search) ? '&search='.urlencode($search) : ''?>" 
                           class="btn btn-delete" title="<?=$xml->delete ?? 'Delete'?>"
                           onclick="return confirm('<?=$xml->confirm_delete ?? 'Are you sure you want to delete this item?'?>');">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php 
    $paginationParams = $query_params;
    unset($paginationParams['p']);
    echo render_pagination($pagination, '?page=mo_list', $paginationParams, 'p'); 
    ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-tags"></i>
        <h4><?=$xml->no_data ?? 'No Models Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first model'?></p>
        <a href="?page=mo_list&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Model'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
// Load brands based on selected type via AJAX
function loadBrands(typeId) {
    if (!typeId) {
        document.getElementById('brand').innerHTML = '<option value="">-- Select Type first --</option>';
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('brand').innerHTML = xhr.responseText;
        }
    };
    xhr.open("GET", "index.php?page=mo_list_brands&q=" + typeId, true);
    xhr.send();
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('model_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>
