<?php
/**
 * PO Delivery View — Legacy Modern Design
 * Variables: $po, $id, $action, $products
 */
$defaultExpiry = date('Y-m-d', strtotime('+1 year'));
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .delivery-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(245,158,11,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .po-badge { background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; }
    .products-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .products-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .products-card .table { margin: 0; font-size: 13px; }
    .products-card .table thead th { background: #f9fafb; color: #1f2937; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 2px solid #e5e7eb; border-top: none; }
    .products-card .table tbody td { padding: 12px 16px; border-color: #f3f4f6; vertical-align: middle; }
    .products-card .table .form-control { border-radius: 8px; border: 1px solid #e5e7eb; font-size: 13px; }
    .btn-submit { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(245,158,11,0.35); color: white; }
    .error-card { background: white; border-radius: 12px; border: 1px solid #fecaca; padding: 40px; text-align: center; }
    .error-card i { font-size: 48px; color: #f87171; margin-bottom: 16px; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } .info-cards { grid-template-columns: 1fr; } }
</style>

<div class="delivery-wrapper">
<?php if(!$po): ?>
    <div class="error-card">
        <i class="fa fa-exclamation-triangle"></i>
        <h3>PO not found or not available for delivery</h3>
        <p><a href="index.php?page=po_list" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a></p>
    </div>
<?php else: ?>
    <div class="page-header">
        <h2>
            <i class="fa fa-truck"></i> <?=$xml->deliver ?? 'Make Delivery Note'?>
            <span class="po-badge">PO-<?=e($po['tax'])?></span>
        </h2>
        <div class="header-actions">
            <a href="index.php?page=po_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <form method="post" action="index.php?page=deliv_store" id="delivForm">
        <input type="hidden" name="method" value="<?=e($action)?>">
        <input type="hidden" name="po_id" value="<?=$id?>">
        <input type="hidden" name="ref" value="<?=e($po['pr_id'])?>">
        <input type="hidden" name="cus_id" value="<?=e($po['cus_id'])?>">
        <?= csrf_field() ?>

        <div class="info-cards">
            <div class="info-card">
                <h4><i class="fa fa-file-text"></i> <?=$xml->order ?? 'Order'?> <?=$xml->information ?? 'Info'?></h4>
                <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($po['name'])?></span></div>
                <div class="form-group" style="margin-top:12px"><label style="font-size:12px;color:#6b7280"><?=$xml->duedate ?? 'Due Date'?></label>
                    <input type="date" name="valid_pay" class="form-control" value="<?=e($po['valid_pay'] ?? date('Y-m-d'))?>" style="border-radius:8px">
                </div>
                <div class="form-group"><label style="font-size:12px;color:#6b7280"><?=$xml->deliverydate ?? 'Delivery Date'?></label>
                    <input type="date" name="deliver_date" class="form-control" value="<?=date('Y-m-d')?>" style="border-radius:8px">
                    <input type="hidden" name="name" value="<?=e($po['name'])?>">
                </div>
            </div>
            <div class="info-card">
                <h4><i class="fa fa-building"></i> <?=$xml->party ?? 'Parties'?></h4>
                <div class="info-row"><span class="info-label"><?=$xml->customer ?? 'Customer'?></span><span class="info-value"><?=e($po['name_en'])?></span></div>
            </div>
        </div>

        <div class="products-card">
            <div class="card-header"><i class="fa fa-cubes"></i> <?=$xml->Product ?? 'Products'?> & <?=$xml->serialno ?? 'Serial Numbers'?></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr>
                        <th>#</th><th><?=$xml->Product ?? 'Product'?></th><th><?=$xml->description ?? 'Description'?></th>
                        <th><?=$xml->serialno ?? 'Serial Number'?></th><th>Warranty Expiry</th>
                    </tr></thead>
                    <tbody>
                    <?php $j = 0; foreach($products as $i => $p):
                        $totalQty = intval($p['quantity']) * intval($p['pack_quantity'] ?? 1);
                        for($q = 0; $q < $totalQty; $q++): ?>
                    <tr>
                        <td><?=$j+1?></td>
                        <td><?=e($p['type_name'] ?? '')?> / <?=e($p['model_name'] ?? '')?></td>
                        <td><?=e($p['des'] ?? '')?></td>
                        <td>
                            <input type="hidden" name="pro_id[<?=$j?>]" value="<?=e($p['pro_id'])?>">
                            <?php if($action == 'c'): ?>
                            <input type="text" name="sn[<?=$j?>]" class="form-control" placeholder="Auto-generate if empty">
                            <?php else: ?>
                            <select name="sn[<?=$j?>]" class="form-control"><option value="">-- Select from stock --</option></select>
                            <?php endif; ?>
                        </td>
                        <td><input type="text" name="exp[<?=$j?>]" class="form-control" value="<?=$defaultExpiry?>"></td>
                    </tr>
                    <?php $j++; endfor; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($po['status'] == '2' || $po['status'] == '1'): ?>
        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-truck"></i> <?=$xml->save ?? 'Save'?> <?=$xml->deliver ?? 'Delivery'?></button>
        </div>
        <?php endif; ?>
    </form>
<?php endif; ?>
</div>
