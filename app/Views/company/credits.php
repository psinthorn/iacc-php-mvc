<?php
$pageTitle = 'Companies — Credits';

/**
 * Company Credits View
 * 
 * Variables from CompanyController::credits():
 *   $company_id         - The company ID
 *   $vendor_credits     - Credits where this company is the customer (given BY vendors)
 *   $customer_credits   - Credits where this company is the vendor (given TO customers)
 *   $available_customers - Customers available for new credit assignment
 */
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">
<div class="master-data-header">
    <h2><i class="fa fa-credit-card"></i> <?=$xml->credit ?? 'Credit Management'?></h2>
    <a href="index.php?page=company" class="btn btn-default">
        <i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back to Companies'?>
    </a>
</div>

<!-- Vendor Credits (credits given to this company by vendors) -->
<div class="master-data-table" style="margin-bottom: 20px;">
    <h4 style="padding: 10px 0;"><i class="fa fa-truck"></i> <?=$xml->vender ?? 'Vendor'?> <?=$xml->credit ?? 'Credits'?></h4>
    <table class="table table-hover" width="100%">
        <thead>
            <tr>
                <th><?=$xml->vender ?? 'Vendor'?></th>
                <th><?=$xml->limitcredit ?? 'Credit Limit'?></th>
                <th><?=$xml->limitday ?? 'Days'?></th>
                <th><?=$xml->start ?? 'Start Date'?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendor_credits)): ?>
            <tr><td colspan="4" class="text-center text-muted">No vendor credits found</td></tr>
            <?php else: ?>
            <?php foreach ($vendor_credits as $vc): ?>
            <tr>
                <td><?=htmlspecialchars($vc['name_sh'])?></td>
                <td><?=htmlspecialchars($vc['limit_credit'])?></td>
                <td><?=htmlspecialchars($vc['limit_day'])?></td>
                <td><?=htmlspecialchars($vc['valid_start'])?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Customer Credits (credits given by this company to customers) -->
<div class="master-data-table">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0;">
        <h4><i class="fa fa-users"></i> <?=$xml->customer ?? 'Customer'?> <?=$xml->credit ?? 'Credits'?></h4>
        <button type="button" class="btn btn-add" onclick="toggleCreditForm()" style="font-size: 12px; padding: 6px 14px;">
            <i class="fa fa-plus"></i> <?=$xml->create ?? 'Add Credit'?>
        </button>
    </div>
    <table class="table table-hover" width="100%">
        <thead>
            <tr>
                <th><?=$xml->customer ?? 'Customer'?></th>
                <th><?=$xml->limitcredit ?? 'Credit Limit'?></th>
                <th><?=$xml->limitday ?? 'Days'?></th>
                <th><?=$xml->start ?? 'Start Date'?></th>
                <th width="80"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customer_credits)): ?>
            <tr><td colspan="5" class="text-center text-muted">No customer credits found</td></tr>
            <?php else: ?>
            <?php foreach ($customer_credits as $cc): ?>
            <tr>
                <td><?=htmlspecialchars($cc['name_sh'])?></td>
                <td><?=htmlspecialchars($cc['limit_credit'])?></td>
                <td><?=htmlspecialchars($cc['limit_day'])?></td>
                <td><?=htmlspecialchars($cc['valid_start'])?></td>
                <td>
                    <a href="#" onclick="editCredit(<?=$cc['id']?>, '<?=htmlspecialchars(addslashes($cc['name_sh']))?>', '<?=htmlspecialchars($cc['limit_credit'])?>', '<?=htmlspecialchars($cc['limit_day'])?>')" class="btn btn-edit btn-xs" title="<?=$xml->edit ?? 'Edit'?>">
                        <i class="fa fa-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Credit Form (hidden by default) -->
<div id="credit-form-area" style="display: none; margin-top: 16px;">
    <div class="form-card" style="background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
        <div class="card-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-weight: 600; font-size: 13px;">
            <i class="fa fa-plus-circle" style="color: #667eea;"></i> 
            <span id="credit-form-title"><?=$xml->create ?? 'Add'?> <?=$xml->credit ?? 'Credit'?></span>
        </div>
        <div style="padding: 14px;">
            <form action="index.php?page=company_store" method="post" id="credit-form">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <div id="customer-select-wrapper">
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;"><?=$xml->customer ?? 'Customer'?></label>
                        <select name="cus_id" id="credit_cus_id" class="form-control" style="border-radius: 8px; height: 36px; font-size: 13px;">
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($available_customers as $cust): ?>
                            <option value="<?=$cust['id']?>"><?=htmlspecialchars($cust['name_en'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;"><?=$xml->limitcredit ?? 'Credit Limit'?></label>
                        <input type="number" name="limit_credit" id="credit_limit" class="form-control" step="0.01" 
                               style="border-radius: 8px; height: 36px; font-size: 13px;" placeholder="0.00">
                    </div>
                    <div>
                        <label style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px;"><?=$xml->limitday ?? 'Credit Days'?></label>
                        <input type="number" name="limit_day" id="credit_days" class="form-control" 
                               style="border-radius: 8px; height: 36px; font-size: 13px;" placeholder="30">
                    </div>
                </div>
                <input type="hidden" name="method" id="credit_method" value="A3">
                <input type="hidden" name="ven_id" value="<?=$company_id?>">
                <input type="hidden" name="id" id="credit_id" value="">
                <input type="hidden" name="page" value="company">
                <?=csrf_field()?>
                <div style="margin-top: 12px; display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="font-size: 12px;">
                        <i class="fa fa-save"></i> <?=$xml->save ?? 'Save'?>
                    </button>
                    <button type="button" class="btn btn-default" style="font-size: 12px;" onclick="toggleCreditForm()">
                        <?=$xml->cancel ?? 'Cancel'?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div><!-- /master-data-container -->

<script>
function toggleCreditForm() {
    var area = document.getElementById('credit-form-area');
    if (area.style.display === 'none') {
        area.style.display = 'block';
        resetCreditForm();
    } else {
        area.style.display = 'none';
    }
}

function resetCreditForm() {
    document.getElementById('credit_method').value = 'A3';
    document.getElementById('credit_id').value = '';
    document.getElementById('credit_limit').value = '';
    document.getElementById('credit_days').value = '';
    document.getElementById('credit-form-title').textContent = '<?=$xml->create ?? "Add"?> <?=$xml->credit ?? "Credit"?>';
    var selWrapper = document.getElementById('customer-select-wrapper');
    selWrapper.style.display = '';
    var sel = document.getElementById('credit_cus_id');
    if (sel) sel.selectedIndex = 0;
}

function editCredit(id, name, limit, days) {
    document.getElementById('credit-form-area').style.display = 'block';
    document.getElementById('credit_method').value = 'A4';
    document.getElementById('credit_id').value = id;
    document.getElementById('credit_limit').value = limit;
    document.getElementById('credit_days').value = days;
    document.getElementById('credit-form-title').textContent = '<?=$xml->edit ?? "Edit"?> <?=$xml->credit ?? "Credit"?>: ' + name;
    // Hide customer select for edit (customer is already set)
    document.getElementById('customer-select-wrapper').style.display = 'none';
}
</script>
