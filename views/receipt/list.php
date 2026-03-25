<?php
/**
 * Receipt List View
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-file-text"></i> <?=$xml->receipt ?? 'Receipts'?></h2>
    <a href="index.php?page=rep_make" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Receipt</a>
</div>

<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$stats['total']?></div><div class="stat-label">Total</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$stats['confirmed']?></div><div class="stat-label">Confirmed</div></div>
    <div class="stat-card warning"><div class="stat-value"><?=$stats['draft']?></div><div class="stat-label">Draft</div></div>
</div>

<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="receipt_list">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search..." class="search-input">
        </div>
        <select name="source_type" class="form-control input-sm" style="width:auto">
            <option value="">All Sources</option>
            <option value="manual" <?=($filters['source_type']??'')==='manual'?'selected':''?>>Manual</option>
            <option value="quotation" <?=($filters['source_type']??'')==='quotation'?'selected':''?>>Quotation</option>
            <option value="invoice" <?=($filters['source_type']??'')==='invoice'?'selected':''?>>Invoice</option>
        </select>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<div class="panel panel-default">
    <table class="table table-striped table-hover">
        <thead><tr><th>Receipt#</th><th>Source</th><th>Payment</th><th class="text-right">VAT</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items)): ?><tr><td colspan="7" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items as $row):
            $sc = match($row['status']??'') { 'confirmed'=>'success', 'draft'=>'warning', 'cancelled'=>'danger', default=>'default' };
        ?>
            <tr>
                <td><strong><?=e($row['rep_rw'])?></strong></td>
                <td><span class="label label-info"><?=e($row['source_type']??'manual')?></span></td>
                <td><?=e($row['payment_method'] ?? '-')?></td>
                <td class="text-right"><?=number_format(floatval($row['vat']??0),2)?></td>
                <td><span class="label label-<?=$sc?>"><?=e($row['status'])?></span></td>
                <td><?=e($row['createdate'])?></td>
                <td>
                    <a href="index.php?page=rep_view&id=<?=$row['id']?>" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                    <a href="index.php?page=rep_make&id=<?=$row['id']?>" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                    <a href="rep-print.php?id=<?=$row['id']?>" class="btn btn-xs btn-default" target="_blank"><i class="fa fa-print"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php render_pagination($pagination, ['page'=>'receipt_list']); ?>
</div>
