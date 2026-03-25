<?php
/**
 * Type (Product) List View
 * 
 * Variables provided by TypeController::index():
 *   $items          - array of type rows (with cat_name, brand_count)
 *   $total_items    - total type count
 *   $item_count     - count of items on current page
 *   $pagination     - pagination data array
 *   $search         - current search term
 *   $cat_id         - current category filter
 *   $edit_data      - type being edited (or null)
 *   $edit_brand_ids - brand IDs associated with edit type
 *   $show_form      - whether to show the inline form
 *   $categories     - all categories for dropdown/filter
 *   $brands         - all brands for checkboxes
 *   $query_params   - current GET params
 *   $xml            - i18n strings
 */
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-cube"></i> <?=$xml->product ?? 'Product Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-cube stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->products ?? 'Products'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box" style="display:flex;gap:10px;max-width:500px;">
        <div style="position:relative;flex:1;">
            <form method="get" action="" style="margin:0;" id="searchForm">
                <i class="fa fa-search"></i>
                <input type="hidden" name="page" value="type">
                <input type="hidden" name="cat_id" value="<?=$cat_id?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->product ?? 'product'?>..." 
                       value="<?=htmlspecialchars($search)?>" 
                       onchange="this.form.submit()">
            </form>
        </div>
        <select class="form-control" style="width:150px;" onchange="window.location='?page=type&cat_id='+this.value+'&search=<?=urlencode($search)?>'">
            <option value="0"><?=$xml->all ?? 'All'?> <?=$xml->category ?? 'Categories'?></option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?=$cat['id']?>" <?=$cat_id == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <?php if (!empty($search) || $cat_id > 0): ?>
        <a href="?page=type" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=type&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="master-data-guide.php" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->product ?? 'Product'?></h4>
        <a href="?page=type" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=type_store" method="post" id="productForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="type_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="type_name" name="type_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->product ?? 'product'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="cat_id"><i class="fa fa-folder"></i> <?=$xml->category ?? 'Category'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="cat_id" name="cat_id" required>
                    <option value="">-- <?=$xml->select ?? 'Select'?> <?=$xml->category ?? 'Category'?> --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?=$cat['id']?>" <?=($edit_data['cat_id'] ?? 0) == $cat['id'] ? 'selected' : ''?>><?=htmlspecialchars($cat['cat_name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="width:100%;">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <textarea class="form-control" id="des" name="des" rows="2"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."><?=htmlspecialchars($edit_data['des'] ?? '')?></textarea>
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
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Product')?>
            </button>
            <a href="?page=type" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->category ?? 'Category'?></th>
                <th width="100"><?=$xml->brand ?? 'Brands'?></th>
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
                    <span class="item-name"><?=htmlspecialchars($data['name'])?></span>
                    <?php if ($data['des']): ?>
                    <br><small class="item-desc"><?=htmlspecialchars(substr($data['des'], 0, 50))?><?=strlen($data['des']) > 50 ? '...' : ''?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-default" style="background:#e9ecef;color:#495057;"><?=htmlspecialchars($data['cat_name'])?></span>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['brand_count']?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=type&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="?page=type_delete&id=<?=$data['id']?>" 
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
    echo render_pagination($pagination, '?page=type', $paginationParams, 'p'); 
    ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-cube"></i>
        <h4><?=$xml->no_data ?? 'No Products Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first product'?></p>
        <a href="?page=type&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Product'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('type_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>
