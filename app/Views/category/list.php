<?php
/**
 * Category List View
 * 
 * Variables provided by CategoryController::index():
 *   $items       - array of category rows (with product_count)
 *   $total_items - total category count
 *   $item_count  - count of items on current page
 *   $pagination  - pagination data array
 *   $search      - current search term
 *   $edit_data   - category being edited (or null)
 *   $show_form   - whether to show the inline form
 *   $query_params- current GET params for pagination links
 *   $xml         - i18n strings
 *   $user        - current user session data
 */

// Import pagination renderer
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<!-- Page Header -->
<div class="master-data-header">
    <h2><i class="fa fa-folder-open"></i> <?=$xml->category ?? 'Category Management'?></h2>
    <div>
        <span class="text-muted">Master Data</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-folder stat-icon"></i>
        <div class="stat-value"><?=$total_items?></div>
        <div class="stat-label"><?=$xml->total ?? 'Total'?> <?=$xml->category ?? 'Categories'?></div>
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
            <input type="hidden" name="page" value="category">
            <input type="text" class="form-control" name="search" 
                   placeholder="<?=$xml->search ?? 'Search'?> <?=$xml->category ?? 'category'?>..." 
                   value="<?=htmlspecialchars($search)?>" 
                   onchange="this.form.submit()">
        </form>
    </div>
    <div>
        <?php if (!empty($search)): ?>
        <a href="?page=category" class="btn btn-default"><i class="fa fa-times"></i> <?=$xml->clear ?? 'Clear'?></a>
        <?php endif; ?>
        <a href="?page=category&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add New'?></a>
        <a href="index.php?page=master_data_guide" class="btn btn-info" style="border-radius:20px;"><i class="fa fa-book"></i> <?=$xml->guide ?? 'Guide'?></a>
    </div>
</div>

<!-- Inline Create/Edit Form -->
<div class="inline-form-container <?=$show_form ? 'active' : ''?>" id="formContainer">
    <div class="form-header">
        <h4><i class="fa fa-<?=$edit_data ? 'edit' : 'plus-circle'?>"></i> <?=$edit_data ? ($xml->edit ?? 'Edit') : ($xml->create ?? 'Create New')?> <?=$xml->category ?? 'Category'?></h4>
        <a href="?page=category" class="btn-close-form">&times;</a>
    </div>
    <form action="index.php?page=category_store" method="post" id="categoryForm">
        <?=csrf_field()?>
        <div class="form-row">
            <div class="form-group">
                <label for="cat_name"><i class="fa fa-tag"></i> <?=$xml->name ?? 'Name'?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="cat_name" name="cat_name" required
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->category ?? 'category'?> <?=$xml->name ?? 'name'?>..."
                       value="<?=htmlspecialchars($edit_data['cat_name'] ?? '')?>">
            </div>
            <div class="form-group">
                <label for="des"><i class="fa fa-info-circle"></i> <?=$xml->description ?? 'Description'?></label>
                <input type="text" class="form-control" id="des" name="des"
                       placeholder="<?=$xml->enter ?? 'Enter'?> <?=$xml->description ?? 'description'?>..."
                       value="<?=htmlspecialchars($edit_data['des'] ?? '')?>">
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="method" value="<?=$edit_data ? 'E' : 'A'?>">
            <input type="hidden" name="id" value="<?=$edit_data['id'] ?? ''?>">
            <button type="submit" class="btn btn-save">
                <i class="fa fa-<?=$edit_data ? 'save' : 'plus'?>"></i> 
                <?=$edit_data ? ($xml->save ?? 'Save Changes') : ($xml->add ?? 'Add Category')?>
            </button>
            <a href="?page=category" class="btn btn-cancel"><?=$xml->cancel ?? 'Cancel'?></a>
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
                <th><?=$xml->description ?? 'Description'?></th>
                <th width="120"><?=$xml->products ?? 'Products'?></th>
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
                    <span class="item-name"><?=htmlspecialchars($data['cat_name'])?></span>
                </td>
                <td>
                    <span class="item-desc"><?=htmlspecialchars($data['des']) ?: '-'?></span>
                </td>
                <td>
                    <span class="badge badge-info"><?=$data['product_count']?> <?=$xml->items ?? 'items'?></span>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?page=category&edit=<?=$data['id']?>" class="btn btn-edit" title="<?=$xml->edit ?? 'Edit'?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <?php if ($data['product_count'] == 0): ?>
                        <a href="?page=category_delete&id=<?=$data['id']?>" 
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
    echo render_pagination($pagination, '?page=category', $paginationParams, 'p'); 
    ?>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="fa fa-folder-open-o"></i>
        <h4><?=$xml->no_data ?? 'No Categories Found'?></h4>
        <p><?=$xml->no_data_desc ?? 'Start by creating your first category'?></p>
        <a href="?page=category&new=1" class="btn btn-add"><i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Category'?></a>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.master-data-container -->

<script>
// Auto-focus on form when visible and scroll to it
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formContainer');
    if (form && form.classList.contains('active')) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var nameInput = document.getElementById('cat_name');
        if (nameInput) {
            setTimeout(function() { nameInput.focus(); }, 300);
        }
    }
});
</script>
