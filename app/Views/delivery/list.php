<?php
/**
 * Delivery List View
 * Variables: $items_out, $items_in, $sendouts_out, $sendouts_in, $total_out, $total_in, $total_records, $pagination, $filters, $per_page
 */
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-truck"></i> <?=$xml->deliver ?? 'Delivery Notes'?></h2>
    <a href="index.php?page=deliv_make" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Create Delivery</a>
</div>

<div class="action-toolbar">
    <form method="get" class="search-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;width:100%">
        <input type="hidden" name="page" value="deliv_list">
        <div class="search-input-wrapper" style="flex:1;min-width:200px">
            <i class="fa fa-search search-icon"></i>
            <input type="text" name="search" value="<?=e($filters['search'])?>" placeholder="Search..." class="search-input">
        </div>
        <?php render_date_presets($filters['date_from'] ?? '', $filters['date_to'] ?? ''); ?>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i></button>
    </form>
</div>

<div class="stats-row">
    <div class="stat-card primary"><div class="stat-value"><?=$total_records?></div><div class="stat-label">Total DN</div></div>
    <div class="stat-card info"><div class="stat-value"><?=$total_out?></div><div class="stat-label">Outgoing</div></div>
    <div class="stat-card success"><div class="stat-value"><?=$total_in?></div><div class="stat-label">Incoming</div></div>
</div>

<!-- OUT DN (PO-based) -->
<div class="panel panel-default">
    <div class="panel-heading"><strong><i class="fa fa-arrow-up"></i> Outgoing Deliveries</strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>DN#</th><th>Customer</th><th>Description</th><th>Due Date</th><th>Delivery Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items_out) && empty($sendouts_out)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else:
            foreach($items_out as $row): ?>
            <tr>
                <td><?=e($row['id'])?></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['valid_pay'])?></td>
                <td><?=e($row['deliver_date'])?></td>
                <td>
                    <a href="index.php?page=deliv_view&id=<?=$row['id']?>" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                    <a href="rec.php?id=<?=$row['id']?>" class="btn btn-xs btn-default" target="_blank"><i class="fa fa-print"></i></a>
                </td>
            </tr>
            <?php endforeach;
            foreach($sendouts_out as $row): ?>
            <tr class="info">
                <td><small>SO-<?=e($row['deliv_id'])?></small></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['description'])?></td>
                <td>-</td>
                <td><?=e($row['deliver_date'])?></td>
                <td>
                    <a href="index.php?page=deliv_view&id=<?=$row['deliv_id']?>&modep=ad" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                    <a href="index.php?page=deliv_edit&id=<?=$row['deliv_id']?>&modep=ad" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i></a>
                </td>
            </tr>
            <?php endforeach;
        endif; ?>
        </tbody>
    </table>
</div>

<!-- IN DN -->
<div class="panel panel-default">
    <div class="panel-heading"><strong><i class="fa fa-arrow-down"></i> Incoming Deliveries</strong></div>
    <table class="table table-striped table-hover">
        <thead><tr><th>DN#</th><th>Vendor</th><th>Description</th><th>Due Date</th><th>Delivery Date</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($items_in)): ?><tr><td colspan="6" class="text-center text-muted">No records</td></tr>
        <?php else: foreach($items_in as $row): ?>
            <tr>
                <td><?=e($row['id'])?></td>
                <td><?=e($row['name_en'])?></td>
                <td><?=e($row['name'])?></td>
                <td><?=e($row['valid_pay'])?></td>
                <td><?=e($row['deliver_date'])?></td>
                <td>
                    <a href="index.php?page=deliv_view&id=<?=$row['id']?>" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php render_pagination($pagination, ['page'=>'deliv_list']); ?>
</div>
