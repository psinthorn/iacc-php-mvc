<?php
/**
 * Payment List View — Legacy Modern Design (inline add/edit)
 * Variables: $items, $total, $search, $edit_id, $edit_data
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .payment-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1000px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(79,70,229,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
    .form-card .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .btn-submit { background: linear-gradient(135deg, #4f46e5, #4338ca); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(79,70,229,0.35); color: white; }
    .search-bar { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 16px 20px; margin-bottom: 24px; }
    .search-bar form { display: flex; gap: 10px; align-items: center; }
    .search-bar .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 8px 14px; font-size: 13px; flex: 1; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f9fafb; }
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; text-decoration: none; margin: 0 2px; border: none; cursor: pointer; }
    .action-edit { background: rgba(79,70,229,0.1); color: #4f46e5; }
    .action-edit:hover { background: #4f46e5; color: white; text-decoration: none; }
    .edit-row { background: #eef2ff !important; }
    .edit-row td { padding: 10px 14px !important; }
    .edit-row .form-control { padding: 6px 10px; font-size: 13px; border-radius: 6px; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php
$search = $search ?? '';
$edit_id = $edit_id ?? null;
$edit_data = $edit_data ?? null;
$total = $total ?? 0;
?>

<div class="payment-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-money"></i> <?=$xml->payment ?? 'Payment'?> <?=$xml->method ?? 'Methods'?></h2>
        <span class="badge"><?=number_format($total)?> <?=$xml->total ?? 'total'?></span>
    </div>

    <!-- Add new -->
    <div class="form-card">
        <div class="card-header"><i class="fa fa-plus-circle" style="color:#4f46e5;margin-right:8px"></i> <?=$xml->addnew ?? 'Add New'?> <?=$xml->payment ?? 'Payment'?> <?=$xml->method ?? 'Method'?></div>
        <div class="card-body">
            <form method="post" action="index.php?page=payment_store">
                <input type="hidden" name="method" value="A">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->name ?? 'Name'?></label><input type="text" name="payment_name" class="form-control" required placeholder="e.g. Bank Transfer"></div></div>
                    <div class="col-md-5"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><input type="text" name="payment_des" class="form-control" placeholder="Optional description"></div></div>
                    <div class="col-md-3"><div class="form-group"><label>&nbsp;</label><button type="submit" class="btn-submit" style="width:100%"><i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add'?></button></div></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search -->
    <div class="search-bar">
        <form method="get">
            <input type="hidden" name="page" value="payment">
            <input type="text" name="search" class="form-control" placeholder="<?=$xml->search ?? 'Search'?> payment methods..." value="<?=e($search)?>">
            <button type="submit" class="btn-submit"><i class="fa fa-search"></i></button>
            <?php if($search): ?><a href="index.php?page=payment" class="btn btn-default" style="border-radius:8px"><i class="fa fa-times"></i></a><?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="data-card">
        <div class="card-header"><i class="fa fa-list" style="color:#4f46e5;margin-right:8px"></i> <?=$xml->payment ?? 'Payment'?> <?=$xml->method ?? 'Methods'?></div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th style="width:50px">#</th><th><?=$xml->name ?? 'Name'?></th><th><?=$xml->description ?? 'Description'?></th><th style="width:100px"><?=$xml->action ?? 'Actions'?></th></tr>
                </thead>
                <tbody>
                    <?php if(!empty($items)): foreach($items as $i => $pm): ?>
                    <?php if($edit_id && $edit_id == $pm['id']): ?>
                    <!-- Edit row inline -->
                    <tr class="edit-row">
                        <td><?=$i+1?></td>
                        <td colspan="2">
                            <form method="post" action="index.php?page=payment_store" style="display:flex;gap:8px;align-items:center">
                                <input type="hidden" name="method" value="E">
                                <input type="hidden" name="id" value="<?=e($pm['id'])?>">
                                <?= csrf_field() ?>
                                <input type="text" name="payment_name" class="form-control" value="<?=e($edit_data['payment_name'] ?? $pm['payment_name'] ?? '')?>" required style="flex:1">
                                <input type="text" name="payment_des" class="form-control" value="<?=e($edit_data['payment_des'] ?? $pm['payment_des'] ?? '')?>" style="flex:1">
                                <button type="submit" class="btn-submit" style="padding:6px 14px"><i class="fa fa-check"></i></button>
                                <a href="index.php?page=payment" class="btn btn-default" style="border-radius:8px;padding:6px 14px"><i class="fa fa-times"></i></a>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($pm['payment_name'] ?? '')?></strong></td>
                        <td style="color:#6b7280"><?=e($pm['payment_des'] ?? '')?></td>
                        <td>
                            <a href="index.php?page=payment&edit=<?=e($pm['id'])?>" class="action-btn action-edit" title="<?=$xml->edits ?? 'Edit'?>"><i class="fa fa-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endif; endforeach; else: ?>
                    <tr><td colspan="4" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:8px"></i><?=$xml->nodata ?? 'No payment methods found'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
