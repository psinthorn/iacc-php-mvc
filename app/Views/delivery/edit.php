<?php
$pageTitle = 'Delivery — Edit';

/**
 * Delivery Edit View — Legacy Modern Design
 * Variables: $detail, $products, $id, $mode, $customers
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .delivery-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(16,185,129,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
    .form-card .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .product-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative; }
    .product-item .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .product-item .product-number { font-weight: 700; color: #10b981; font-size: 14px; }
    .product-item .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }
    .product-item .btn-remove:hover { background: #ef4444; color: white; }
    .btn-add-product { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(16,185,129,0.35); color: white; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php
$dn = $detail ?? [];
$dn_id = $id ?? ($dn['id'] ?? '');
$dn_mode = $mode ?? 'po';
?>

<div class="delivery-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-pencil"></i> <?=$xml->edits ?? 'Edit'?> <?=$xml->deliver ?? 'Delivery'?> #<?=e($dn_id)?></h2>
        <div class="header-actions">
            <a href="index.php?page=deliv_view&id=<?=e($dn_id)?>"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <form method="post" action="index.php?page=deliv_store" id="delivEditForm">
        <input type="hidden" name="method" value="ED">
        <input type="hidden" name="id" value="<?=e($dn_id)?>">
        <input type="hidden" name="mode" value="<?=e($dn_mode)?>">
        <?= csrf_field() ?>

        <div class="form-card">
            <div class="card-header"><i class="fa fa-info-circle" style="color:#10b981;margin-right:8px"></i> <?=$xml->deliver ?? 'Delivery'?> <?=$xml->detail ?? 'Details'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->customer ?? 'Customer'?></label>
                        <?php if($dn_mode === 'standalone' && !empty($customers)): ?>
                        <select name="cus_id" class="form-control">
                            <?php foreach($customers as $c): ?><option value="<?=$c['id']?>" <?=($dn['cus_id'] ?? '') == $c['id'] ? 'selected' : ''?>><?=e($c['name_en'])?></option><?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control" value="<?=e($dn['cus_name'] ?? $dn['customer_name'] ?? '')?>" readonly>
                        <input type="hidden" name="cus_id" value="<?=e($dn['cus_id'] ?? '')?>">
                        <?php endif; ?>
                    </div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->deliverydate ?? 'Delivery Date'?></label><input type="date" name="deliver_date" class="form-control" value="<?=e($dn['deliver_date'] ?? date('Y-m-d'))?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="des" class="form-control" rows="2"><?=e($dn['des'] ?? $dn['description'] ?? '')?></textarea></div></div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <span><i class="fa fa-cubes" style="color:#10b981;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></span>
            </div>
            <div class="card-body" id="productsContainer">
                <?php if(!empty($products)): foreach($products as $i => $p): ?>
                <div class="product-item" data-index="<?=$i?>">
                    <div class="product-header">
                        <span class="product-number">#<?=$i+1?> Product</span>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->Product ?? 'Product'?></label>
                            <input type="text" class="form-control" value="<?=e($p['type_name'] ?? $p['product_name'] ?? '')?>" readonly>
                            <input type="hidden" name="type[]" value="<?=e($p['type'] ?? $p['type_id'] ?? '')?>">
                        </div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->brand ?? 'Brand'?></label>
                            <input type="text" class="form-control" value="<?=e($p['brand_name'] ?? '')?>" readonly>
                            <input type="hidden" name="ban_id[]" value="<?=e($p['ban_id'] ?? $p['brand_id'] ?? '')?>">
                        </div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->model ?? 'Model'?></label>
                            <input type="text" class="form-control" value="<?=e($p['model_name'] ?? '')?>" readonly>
                            <input type="hidden" name="model[]" value="<?=e($p['model'] ?? $p['model_id'] ?? '')?>">
                        </div></div>
                        <div class="col-md-1"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label>
                            <input type="number" name="quantity[]" class="form-control" value="<?=e($p['quantity'] ?? 1)?>" min="1">
                        </div></div>
                        <div class="col-md-2"><div class="form-group"><label>S/N</label>
                            <input type="text" name="s_n[]" class="form-control" value="<?=e($p['s_n'] ?? $p['serial_number'] ?? '')?>">
                        </div></div>
                        <div class="col-md-2"><div class="form-group"><label>Warranty</label>
                            <input type="date" name="warranty[]" class="form-control" value="<?=e($p['warranty'] ?? $p['warranty_expiry'] ?? '')?>">
                        </div></div>
                    </div>
                    <input type="hidden" name="price[]" value="<?=e($p['price'] ?? 0)?>">
                    <input type="hidden" name="discount[]" value="<?=e($p['discount'] ?? 0)?>">
                    <input type="hidden" name="pack_quantity[]" value="<?=e($p['pack_quantity'] ?? 1)?>">
                </div>
                <?php endforeach; else: ?>
                <div class="text-center" style="padding:40px;color:#9ca3af">
                    <i class="fa fa-inbox" style="font-size:48px;margin-bottom:12px;display:block"></i>
                    <p><?=$xml->nodata ?? 'No products found'?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-save"></i> <?=$xml->save ?? 'Save'?> <?=$xml->deliver ?? 'Delivery'?></button>
        </div>
    </form>
</div>
