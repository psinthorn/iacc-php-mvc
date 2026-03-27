<?php
/**
 * Payment List View
 * Variables: $items, $total, $search, $edit_id, $edit_data
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-credit-card"></i> <?=$xml->payment ?? 'Payment Records'?></h2>
</div>

<!-- Create/Edit Form -->
<div class="panel panel-default">
    <div class="panel-heading"><strong><?=$edit_id > 0 ? 'Edit' : 'Add'?> Payment</strong></div>
    <div class="panel-body">
        <form method="post" action="index.php?page=payment_store" class="form-inline">
            <input type="hidden" name="method" value="<?=$edit_id > 0 ? 'E' : 'A'?>">
            <?php if($edit_id > 0): ?><input type="hidden" name="id" value="<?=$edit_id?>"><?php endif; ?>
            <?php echo csrf_field(); ?>
            <div class="form-group" style="margin-right:10px">
                <input type="text" name="payment_name" class="form-control" placeholder="Payment Name" required
                    value="<?=e($edit_data['payment_name'] ?? '')?>">
            </div>
            <div class="form-group" style="margin-right:10px">
                <input type="text" name="payment_des" class="form-control" placeholder="Description"
                    value="<?=e($edit_data['payment_des'] ?? '')?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?=$edit_id > 0 ? 'Update' : 'Add'?></button>
            <?php if($edit_id > 0): ?>
                <a href="index.php?page=payment" class="btn btn-default">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Search -->
<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;width:100%">
        <input type="hidden" name="page" value="payment">
        <div class="search-input-wrapper" style="flex:1">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($search)?>" placeholder="Search..." class="search-input">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<!-- List -->
<div class="panel panel-default">
    <div class="panel-heading"><strong>Payment Records (<?=$total?>)</strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items)): ?><tr><td colspan="4" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items as $i=>$row): ?>
            <tr>
                <td><?=$i+1?></td>
                <td><?=e($row['payment_name'])?></td>
                <td><?=e($row['payment_des'])?></td>
                <td><a href="index.php?page=payment&edit=<?=$row['id']?>" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
</div>
