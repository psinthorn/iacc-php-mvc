<?php
/**
 * Billing List View
 * Variables: $items, $stats, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-money"></i> <?=$xml->billing ?? 'Billing'?></h2>
    <a href="index.php?page=billing_make" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Billing</a>
</div>

<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$stats['total']?></div><div class="stat-label">Total</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$stats['billed']?></div><div class="stat-label">Billed</div></div>
    <div class="stat-card warning"><div class="stat-value"><?=$stats['unbilled']?></div><div class="stat-label">Unbilled</div></div>
</div>

<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="billing">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search invoices..." class="search-input">
        </div>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<div class="panel panel-default">
    <table class="table table-striped table-hover">
        <thead><tr><th>Invoice</th><th>Customer</th><th>PO Ref</th><th class="text-right">Amount</th><th>Billing</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items)): ?><tr><td colspan="7" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items as $row):
            $hasBilling = !empty($row['billing_id']);
        ?>
            <tr class="<?=$hasBilling ? '' : 'warning'?>">
                <td><strong><?=e($row['tax'] ?? '')?></strong></td>
                <td><?=e($row['name_en'] ?? '')?></td>
                <td><?=e($row['po_ref'] ?? '-')?></td>
                <td class="text-right"><?=number_format(floatval($row['calculated_amount'] ?? $row['amount'] ?? 0), 2)?></td>
                <td>
                    <?php if($hasBilling): ?>
                        <span class="label label-success">BL-<?=e($row['billing_id'])?></span>
                    <?php else: ?>
                        <span class="label label-warning">Unbilled</span>
                    <?php endif; ?>
                </td>
                <td><?=e($row['createdate'] ?? '')?></td>
                <td>
                    <?php if($hasBilling): ?>
                        <a href="index.php?page=billing_make&id=<?=$row['billing_id']?>" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                        <form method="post" action="index.php?page=billing_store" style="display:inline">
                            <input type="hidden" name="method" value="D">
                            <input type="hidden" name="id" value="<?=$row['billing_id']?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this billing?')"><i class="fa fa-trash"></i></button>
                        </form>
                    <?php endif; ?>
                    <a href="index.php?page=compl_view&id=<?=$row['iv_id']?>" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php render_pagination($pagination, ['page'=>'billing']); ?>
</div>
