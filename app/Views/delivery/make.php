<?php
$pageTitle = 'Delivery — New';

/**
 * Delivery Make View (standalone sendout) — Legacy Modern Design
 * Variables: $customers, $types
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .delivery-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(102,126,234,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden; }
    .form-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
    .form-card .card-body { padding: 20px; }
    .form-card .form-control { border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
    .form-card .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); outline: none; }
    .form-card label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 6px; }
    .product-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; position: relative; }
    .product-item .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .product-item .product-number { font-weight: 700; color: #667eea; font-size: 14px; }
    .product-item .btn-remove { background: rgba(239,68,68,0.1); color: #ef4444; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; }
    .product-item .btn-remove:hover { background: #ef4444; color: white; }
    .btn-add-product { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-submit { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 16px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit:hover { box-shadow: 0 6px 20px rgba(102,126,234,0.35); color: white; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<div class="delivery-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-plus-circle"></i> <?=$xml->create ?? 'Create'?> <?=$xml->deliver ?? 'Standalone Delivery'?></h2>
        <div class="header-actions">
            <a href="index.php?page=deliv_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <form method="post" action="index.php?page=deliv_store" id="delivForm">
        <input type="hidden" name="method" value="AD">
        <?= csrf_field() ?>

        <div class="form-card">
            <div class="card-header"><i class="fa fa-info-circle" style="color:#667eea;margin-right:8px"></i> <?=$xml->deliver ?? 'Delivery'?> <?=$xml->detail ?? 'Details'?></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->customer ?? 'Customer'?></label>
                        <select name="cus_id" class="form-control" required>
                            <option value="">-- <?=$xml->select ?? 'Select'?> --</option>
                            <?php foreach($customers as $c): ?><option value="<?=$c['id']?>"><?=e($c['name_en'])?></option><?php endforeach; ?>
                        </select></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->deliverydate ?? 'Delivery Date'?></label><input type="date" name="deliver_date" class="form-control" value="<?=date('Y-m-d')?>"></div></div>
                    <div class="col-md-4"><div class="form-group"><label><?=$xml->description ?? 'Description'?></label><textarea name="des" class="form-control" rows="2"></textarea></div></div>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="card-header">
                <span><i class="fa fa-cubes" style="color:#667eea;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></span>
                <button type="button" class="btn-add-product" id="addRow"><i class="fa fa-plus"></i> <?=$xml->addnew ?? 'Add'?></button>
            </div>
            <div class="card-body" id="productsContainer">
                <div class="product-item" data-index="0">
                    <div class="product-header">
                        <span class="product-number">#1 Product</span>
                        <button type="button" class="btn-remove removeRow"><i class="fa fa-times"></i></button>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label><?=$xml->Product ?? 'Product'?></label>
                            <select name="type[]" class="form-control"><option value="">--</option>
                                <?php foreach($types as $t): ?><option value="<?=$t['id']?>"><?=e($t['name'])?></option><?php endforeach; ?>
                            </select></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->brand ?? 'Brand'?></label><select name="ban_id[]" class="form-control"><option value="0">--</option></select></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->model ?? 'Model'?></label><select name="model[]" class="form-control"><option value="0">--</option></select></div></div>
                        <div class="col-md-1"><div class="form-group"><label><?=$xml->Unit ?? 'Qty'?></label><input type="number" name="quantity[]" class="form-control" value="1" min="1"></div></div>
                        <div class="col-md-2"><div class="form-group"><label><?=$xml->Price ?? 'Price'?></label><input type="number" name="price[]" class="form-control" value="0" step="0.01"></div></div>
                        <div class="col-md-2"><div class="form-group"><label>S/N</label><input type="text" name="s_n[]" class="form-control" placeholder="Serial#"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Warranty</label><input type="date" name="warranty[]" class="form-control" value="<?=date('Y-m-d', strtotime('+1 year'))?>"></div></div>
                    </div>
                    <input type="hidden" name="discount[]" value="0">
                    <input type="hidden" name="pack_quantity[]" value="1">
                </div>
            </div>
        </div>

        <div class="text-right" style="margin-bottom:40px">
            <button type="submit" class="btn-submit"><i class="fa fa-truck"></i> <?=$xml->create ?? 'Create'?> <?=$xml->deliver ?? 'Delivery'?></button>
        </div>
    </form>
</div>
<script>
var productCount = 1;
document.getElementById('addRow')?.addEventListener('click', function() {
    var idx = productCount++;
    var container = document.getElementById('productsContainer');
    var first = container.querySelector('.product-item');
    var clone = first.cloneNode(true);
    clone.dataset.index = idx;
    clone.querySelector('.product-number').textContent = '#' + (idx+1) + ' Product';
    clone.querySelectorAll('input').forEach(el => {
        if(el.type === 'number') el.value = el.name.includes('quantity') ? '1' : '0';
        else if(el.type !== 'hidden') el.value = '';
    });
    clone.querySelectorAll('select').forEach(el => el.selectedIndex = 0);
    container.appendChild(clone);
});
document.addEventListener('click', function(e) {
    if(e.target.closest('.removeRow') && document.querySelectorAll('.product-item').length > 1) {
        e.target.closest('.product-item').remove();
        document.querySelectorAll('.product-item').forEach((item, i) => {
            item.querySelector('.product-number').textContent = '#' + (i+1) + ' Product';
        });
    }
});
</script>
