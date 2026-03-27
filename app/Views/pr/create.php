<?php
/**
 * PR Create View (Vendor mode - creating PR from vendor side)
 * Variables: $mode, $vendors, $customers, $categories
 */
?>
<link rel="stylesheet" href="css/master-data.css">
<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-plus-circle"></i> Create Purchase Request</h2>
    <a href="index.php?page=pr_list" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
</div>

<form method="post" action="index.php?page=pr_store" id="prForm">
    <input type="hidden" name="method" value="A">
    <input type="hidden" name="page" value="pr_list">
    <?php echo csrf_field(); ?>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>PR Details</strong></div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>PR Name / Description</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Customer</label>
                        <select name="cus_id" class="form-control" required>
                            <option value="">-- Select Customer --</option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?=$c['id']?>"><?=e($c['name_en'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="des" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <!-- Product Rows -->
    <div class="panel panel-default">
        <div class="panel-heading"><strong>Products</strong></div>
        <div class="panel-body">
            <table class="table table-bordered" id="productTable">
                <thead><tr><th>#</th><th>Category</th><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                <?php for($i=0; $i<9; $i++): ?>
                <tr>
                    <td><?=$i+1?></td>
                    <td>
                        <select class="form-control input-sm catSelect" data-row="<?=$i?>" onchange="loadTypes(this, <?=$i?>)">
                            <option value="">-- Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?=$cat['id']?>"><?=e($cat['cat_name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="id<?=$i?>" id="id<?=$i?>" value="0">
                        <input type="text" name="ordername<?=$i?>" id="ordername<?=$i?>" class="form-control input-sm" readonly placeholder="Select product...">
                    </td>
                    <td><input type="number" name="quantity<?=$i?>" class="form-control input-sm" value="0" min="0" onchange="calcRow(<?=$i?>)"></td>
                    <td><input type="text" name="price<?=$i?>" id="price<?=$i?>" class="form-control input-sm" value="0" readonly></td>
                    <td><input type="text" name="total<?=$i?>" id="total<?=$i?>" class="form-control input-sm" value="0" readonly></td>
                </tr>
                <?php endfor; ?>
                </tbody>
                <tfoot><tr><td colspan="5" class="text-right"><strong>Net Total:</strong></td><td><input type="text" name="totalnet" id="totalnet" class="form-control input-sm" readonly value="0"></td></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Create PR</button>
    </div>
</form>
</div>

<script>
function calcRow(i) {
    var qty = parseFloat(document.querySelector('[name="quantity'+i+'"]').value) || 0;
    var price = parseFloat(document.getElementById('price'+i).value) || 0;
    document.getElementById('total'+i).value = (qty * price).toFixed(2);
    calcNet();
}
function calcNet() {
    var total = 0;
    for(var i=0; i<9; i++) { total += parseFloat(document.getElementById('total'+i).value) || 0; }
    document.getElementById('totalnet').value = total.toFixed(2);
}
</script>
