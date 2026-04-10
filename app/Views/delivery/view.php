<?php
/**
 * Delivery View (Detail) — Legacy Modern Design
 * Variables: $detail, $products, $id, $mode
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .view-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(16,185,129,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .dn-badge { background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; margin-left: 8px; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #10b981; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; text-align: right; }
    .products-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .products-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .products-card .table { margin: 0; font-size: 13px; }
    .products-card .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
    .products-card .table tbody td { padding: 12px 16px; border-color: #f3f4f6; vertical-align: middle; }
    .products-card .table tbody tr:hover { background: rgba(16,185,129,0.03); }
    .product-model { display: inline-flex; background: #f0fdf4; color: #16a34a; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .btn-receive { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-receive:hover { box-shadow: 0 6px 20px rgba(16,185,129,0.35); color: white; }
    .empty-state { text-align: center; padding: 40px 20px; color: #6b7280; }
    .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; }
    .error-card { background: white; border-radius: 12px; border: 1px solid #fecaca; padding: 40px; text-align: center; }
    .error-card i { font-size: 48px; color: #f87171; margin-bottom: 16px; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .info-cards { grid-template-columns: 1fr; } }
</style>

<div class="view-wrapper">
<?php if(!$detail): ?>
    <div class="error-card">
        <i class="fa fa-exclamation-triangle"></i>
        <h3><?=$xml->nodata ?? 'Delivery note not found'?></h3>
        <p><a href="index.php?page=deliv_list" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a></p>
    </div>
<?php else: ?>
    <div class="page-header">
        <h2>
            <i class="fa fa-truck"></i> <?=$xml->deliver ?? 'Delivery Note'?>
            <span class="dn-badge">DN-<?=str_pad($id, 7, '0', STR_PAD_LEFT)?></span>
        </h2>
        <div class="header-actions">
            <a href="index.php?page=deliv_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            <a href="index.php?page=deliv_print&id=<?=$id?><?=$mode==='ad'?'&modep=ad':''?>" target="_blank"><i class="fa fa-print"></i> <?=$xml->print ?? 'Print'?></a>
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fa fa-file-text"></i> <?=$xml->order ?? 'Order'?> <?=$xml->information ?? 'Info'?></h4>
            <?php if($mode === 'ad'): ?>
                <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($detail['tmp'] ?? '')?></span></div>
                <div class="info-row"><span class="info-label"><?=$xml->deliverydate ?? 'Delivery Date'?></span><span class="info-value"><?=e($detail['deliver_date'])?></span></div>
            <?php else: ?>
                <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($detail['name'] ?? '')?></span></div>
                <div class="info-row"><span class="info-label"><?=$xml->duedate ?? 'Due Date'?></span><span class="info-value"><?=e($detail['valid_pay'] ?? '')?></span></div>
                <div class="info-row"><span class="info-label"><?=$xml->deliverydate ?? 'Delivery Date'?></span><span class="info-value"><?=e($detail['deliv_date'] ?? '')?></span></div>
            <?php endif; ?>
        </div>
        <div class="info-card">
            <h4><i class="fa fa-building"></i> <?=$xml->party ?? 'Parties'?></h4>
            <?php if($mode === 'ad'): ?>
                <div class="info-row"><span class="info-label"><?=$xml->customer ?? 'Customer'?></span><span class="info-value"><?=e($detail['name_sh'] ?? '')?></span></div>
            <?php else: ?>
                <div class="info-row"><span class="info-label"><?=$xml->customer ?? 'Customer'?></span><span class="info-value"><?=e($detail['name_en'] ?? '')?></span></div>
                <?php if(!empty($detail['tax'])): ?>
                <div class="info-row"><span class="info-label">PO#</span><span class="info-value"><?=e($detail['tax'])?></span></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if(!empty($detail['des'])): ?>
        <div class="info-card">
            <h4><i class="fa fa-align-left"></i> <?=$xml->description ?? 'Description'?></h4>
            <p style="font-size:13px;color:#4b5563;line-height:1.6"><?=nl2br(e($detail['des']))?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="products-card">
        <div class="card-header"><i class="fa fa-cubes"></i> <?=$xml->Product ?? 'Products'?></div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr>
                    <th>#</th><th><?=$xml->Product ?? 'Name'?></th><th><?=$xml->description ?? 'Description'?></th>
                    <th><?=$xml->model ?? 'Model'?></th><th><?=$xml->serialno ?? 'Serial Number'?></th><th>Warranty</th>
                </tr></thead>
                <tbody>
                <?php if(empty($products)): ?>
                <tr><td colspan="6"><div class="empty-state"><i class="fa fa-inbox"></i><h4><?=$xml->nodata ?? 'No products found'?></h4></div></td></tr>
                <?php else: foreach($products as $i => $p): ?>
                <tr>
                    <td><?=$i+1?></td>
                    <td><?=e($p['type_name'] ?? '')?></td>
                    <td><?=e($p['des'] ?? '')?></td>
                    <td><?php if(!empty($p['model_name'])): ?><span class="product-model"><?=e($p['model_name'])?></span><?php else: ?>-<?php endif; ?></td>
                    <td><?=e($p['s_n'] ?? '-')?></td>
                    <td><?=e($p['warranty'] ?? '-')?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Receive button -->
        <div style="padding: 20px; text-align: right;">
        <?php if($mode !== 'ad' && ($detail['status'] ?? '') == '3'): ?>
            <form method="post" action="index.php?page=deliv_store" style="display:inline">
                <input type="hidden" name="method" value="R">
                <input type="hidden" name="po_id" value="<?=e($detail['po_id'] ?? '')?>">
                <input type="hidden" name="ref" value="<?=e($detail['pr_id'] ?? '')?>">
                <input type="hidden" name="deliv_id" value="<?=$id?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-receive" onclick="return confirm('<?=$xml->confirmreceive ?? 'Confirm receive?'?>')">
                    <i class="fa fa-check-circle"></i> <?=$xml->receive ?? 'Receive'?> (<?=$xml->createinvoice ?? 'Create Invoice'?>)
                </button>
            </form>
        <?php elseif($mode === 'ad'): ?>
            <form method="post" action="index.php?page=deliv_store" style="display:inline">
                <input type="hidden" name="method" value="R2">
                <input type="hidden" name="po_id" value="<?=e($detail['id'] ?? '')?>">
                <input type="hidden" name="deliv_id" value="<?=$id?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-receive" onclick="return confirm('<?=$xml->confirmreceive ?? 'Confirm receive?'?>')">
                    <i class="fa fa-check-circle"></i> <?=$xml->receive ?? 'Receive'?>
                </button>
            </form>
        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>
