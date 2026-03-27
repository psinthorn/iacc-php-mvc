<?php
/**
 * Brand List View
 * 
 * Variables provided by BrandController::index():
 *   $items        - array of brand rows (with vendor_name, product_count)
 *   $total_items  - total brand count
 *   $item_count   - count of items on current page
 *   $pagination   - pagination data array
 *   $search       - current search term
 *   $edit_data    - brand being edited (or null)
 *   $show_form    - whether to show the inline form
 *   $own_company  - logged-in company info
 *   $vendors      - vendor list for dropdown
 *   $query_params - current GET params
 *   $xml          - i18n strings
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-bookmark"></i> <?=$xml->brand ?? 'Brand Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-bookmark stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->brand ?? 'Brands'?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?=$item_count?></div>
        <div class="stat-label"><?=$xml->showing ?? 'Showing'?></div>
    </div>
</div>

<!-- Action Toolbar -->
<div class="action-toolbar">
    <div class="search-box">
        <form method="get" action="" style="margin:0;">
            <i class="fa fa-search"></i>
            <input type="hidden" name="page" value="brand">
            <input type="text" class="form-control" name="search" 
                   placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->brand ?? 'brand'?>..." 
                   value="<?=htmlspecialchars($search)?>" 
                   onchange="this.form.submit()">
        </form>
    </div>
    <div>
        <?php if (!empty($search)): ?>
        <a href="?page=brand" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=brand&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="index.php?page=master_data_guide" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->brand ?? 'Brand'?></h4>
        <a href="?page=brand" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=brand_store" method="post" enctype="multipart/form-data" id="brandForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="brand_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="brand_name" name="brand_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->brand ?? 'brand'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['brand_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <input type="text" class="form-control" id="des" name="des"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."
                       value="<?=htmlspecialchars($edit_data['des'] ?? '')?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="ven_id"><i class="fa fa-truck"></i> <?=$xml->owner ?? 'Owner/Vendor'?></label>
                <select class="form-control" id="ven_id" name="ven_id">
                    <option value="0">-- <?=$xml->no_owner ?? 'No Owner'?> --</option>
                    <?php if ($own_company): ?>
                    <option value="<?=$own_company['id']?>" <?=($edit_data['ven_id'] ?? $own_company['id']) == $own_company['id'] ? 'selected' : ''?>>
                        <?=htmlspecialchars($own_company['name_en'])?> (<?=$xml->own_company ?? 'Own Company'?>)
                    </option>
                    <?php endif; ?>
                    <?php foreach ($vendors as $vendor): ?>
                    <option value="<?=$vendor['id']?>" <?=($edit_data['ven_id'] ?? 0) == $vendor['id'] ? 'selected' : ''?>>
                        <?=htmlspecialchars($vendor['name_en'])?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="logo"><i class="fa fa-image"></i> <?=$xml->logo ?? 'Logo'?></label>
                <?php if (!empty($edit_data['logo'])): ?>
                <div style="margin-bottom: 8px;">
                    <img src="upload/<?=htmlspecialchars($edit_data['logo'])?>" class="logo-preview-large">
                </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/jpg">
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="page" value="brand">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Brand')?>
            </button>
            <a href="?page=brand" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th width="60"><?=$xml->logo ?? 'Logo'?></th>
                <th><?=$xml->name ?? 'Name'?></th>
                <th><?=$xml->description ?? 'Description'?></th>
                <th><?=$xml->owner ?? 'Owner'?></th>
                <th width="100"><?=$xml->products ?? 'Products'?></th>
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
                    <?php if (!empty($data['logo'])): ?>
                    <img src="upload/<?=htmlspecialchars($data['logo'])?>" class="logo-preview" alt="<?=htmlspecialchars($data['brand_name'])?>">
                    <?php else: ?>
                    <div class="logo-preview" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-bookmark text-muted"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="item-name"><?=htmlspecialchars($data['brand_name'])?></span>
                </td>
                <td>
                    <span class="item-desc"><?=htmlspecialchars($data['des']) ?: '-'?></span>
                </td>
                <td>
                    <?php if ($data['vendor_name']): ?>
                    <span class="badge-vendor"><?=htmlspecialchars($data['vendor_name'])?></span>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['product_count']?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=brand&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <?php if ($data['product_count'] == 0): ?>
                        <a href="?page=brand_delete&id=<?=$data['id']?>" 
                           class="btn btn-delete" title="<?=$xml->delete ?? 'Delete'?>"
                           onclick="return confirm('<?=$xml->confirm_delete ?? 'Are you sure you want to delete this item?'?>');">
                            <i class="fa fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <button class="btn btn-delete" disabled title="<?=$xml->cannot_delete ?? 'Cannot delete: has associated products'?>">
                            <i class="fa fa-trash"></i>
                        </button>
                        <?php endif; ?>
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
    echo render_pagination($pagination, '?page=brand', $paginationParams, 'p'); 
    ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-bookmark-o"></i>
        <h4><?=$xml->no_data ?? 'No Brands Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first brand'?></p>
        <a href="?page=brand&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Brand'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('brand_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>
