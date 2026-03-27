<?php
/**
 * Voucher List View
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-o"></i> <?=$xml->voucher ?? 'Vouchers'?></h2>
    <a href="index.php?page=voc_make" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Voucher</a>
</div>

<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$stats['total']?></div><div class="stat-label">Total</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$stats['confirmed']?></div><div class="stat-label">Confirmed</div></div>
    <div class="stat-card warning"><div class="stat-value"><?=$stats['draft']?></div><div class="stat-label">Draft</div></div>
    <div class="stat-card danger"><div class="stat-value"><?=$stats['cancelled']?></div><div class="stat-label">Cancelled</div></div>
</div>

<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="voucher_list">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search..." class="search-input">
        </div>
        <select name="status" class="form-control input-sm" style="width:auto">
            <option value="">All</option>
            <option value="confirmed" <?=($filters['status']??'')==='confirmed'?'selected':''?>>Confirmed</option>
            <option value="draft" <?=($filters['status']??'')==='draft'?'selected':''?>>Draft</option>
            <option value="cancelled" <?=($filters['status']??'')==='cancelled'?'selected':''?>>Cancelled</option>
        </select>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<div class="panel panel-default">
    <table class="table table-striped table-hover">
        <thead><tr><th>Voucher#</th><th>Vendor/Payee</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items as $row):
            $sc = match($row['status']??'') { 'confirmed'=>'success', 'draft'=>'warning', 'cancelled'=>'danger', default=>'default' };
        ?>
            <tr>
                <td><strong><?=e($row['vou_rw'])?></strong></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['payment_method'] ?? '-')?></td>
                <td><span class="label label-<?=$sc?>"><?=e($row['status'])?></span></td>
                <td><?=e($row['createdate'])?></td>
                <td>
                    <a href="index.php?page=voc_view&id=<?=$row['id']?>" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                    <a href="index.php?page=voc_make&id=<?=$row['id']?>" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                    <a href="index.php?page=vou_print&id=<?=$row['id']?>" class="btn btn-xs btn-default" target="_blank"><i class="fa fa-print"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php render_pagination($pagination, ['page'=>'voucher_list']); ?>
</div>
