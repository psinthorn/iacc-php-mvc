<?php
$pageTitle = 'Purchase Requests — Details';

/**
 * Purchase Request View — Legacy Modern Design
 * Variables: $pr, $products, $id
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .pr-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(102,126,234,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .page-header .header-actions a { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .page-header .header-actions a:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; }
    .info-card h4 { margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: #667eea; display: flex; align-items: center; gap: 8px; }
    .info-card .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
    .info-card .info-row:last-child { border-bottom: none; }
    .info-card .info-label { color: #6b7280; font-weight: 500; }
    .info-card .info-value { color: #1f2937; font-weight: 600; text-align: right; max-width: 60%; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #f9fafb; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-new { background: #dbeafe; color: #1d4ed8; }
    .status-confirmed { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    .summary-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 24px; }
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .summary-item { text-align: center; padding: 12px; }
    .summary-item .label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    .summary-item .value { font-size: 20px; font-weight: 700; color: #1f2937; }
    .summary-item .value.total { color: #667eea; font-size: 24px; }
    .action-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; margin-bottom: 40px; text-align: center; }
    .btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; margin: 4px; }
    .btn-quotation { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
    .btn-quotation:hover { box-shadow: 0 6px 20px rgba(102,126,234,0.35); color: white; text-decoration: none; }
    .btn-cancel { background: rgba(239,68,68,0.1); color: #ef4444; border: none; }
    .btn-cancel:hover { background: #ef4444; color: white; text-decoration: none; }
    .error-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 60px 20px; text-align: center; color: #ef4444; }
    @media (max-width: 768px) { .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<?php if(!$pr): ?>
<div class="pr-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-exclamation-triangle"></i> <?=$xml->error ?? 'Error'?></h2>
    </div>
    <div class="error-card">
        <i class="fa fa-exclamation-triangle" style="font-size:48px;margin-bottom:12px;display:block"></i>
        <p style="font-size:16px;font-weight:600"><?=$xml->nodata ?? 'PR not found'?></p>
        <a href="index.php?page=pr_list" class="btn-action btn-quotation" style="margin-top:16px"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
    </div>
</div>
<?php return; endif; ?>

<?php
$pr_id = $id ?? ($pr['id'] ?? '');
$status = $pr['status'] ?? '0';
$cancelled = $pr['cancel'] ?? '0';
$status_class = $cancelled == '1' ? 'status-cancelled' : ($status >= 2 ? 'status-confirmed' : 'status-new');
$status_text = $cancelled == '1' ? ($xml->cancelled ?? 'Cancelled') : e(decodenum($status));
?>

<div class="pr-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-file-text-o"></i> <?=$xml->pr ?? 'PR'?> #<?=e($pr_id)?></h2>
        <div class="header-actions">
            <a href="index.php?page=pr_list"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
            <?php if($cancelled != '1' && $status == '0'): ?>
            <a href="index.php?page=po_make&pr_id=<?=e($pr_id)?>"><i class="fa fa-plus"></i> <?=$xml->quotation ?? 'Create Quotation'?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fa fa-info-circle"></i> <?=$xml->pr ?? 'PR'?> <?=$xml->detail ?? 'Details'?></h4>
            <div class="info-row"><span class="info-label">PR#</span><span class="info-value"><?=e($pr_id)?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->datecreate ?? 'Date'?></span><span class="info-value"><?=e($pr['createdate'] ?? '')?></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->status ?? 'Status'?></span><span class="info-value"><span class="status-badge <?=$status_class?>"><?=$status_text?></span></span></div>
            <div class="info-row"><span class="info-label"><?=$xml->description ?? 'Description'?></span><span class="info-value"><?=e($pr['des'] ?? '')?></span></div>
        </div>

        <div class="info-card">
            <h4><i class="fa fa-building"></i> <?=$xml->customerinfo ?? 'Parties'?></h4>
            <?php if(!empty($pr['customer_name'])): ?>
            <div class="info-row"><span class="info-label"><?=$xml->customer ?? 'Customer'?></span><span class="info-value"><?=e($pr['customer_name'])?></span></div>
            <?php endif; ?>
            <?php if(!empty($pr['vendor_name'])): ?>
            <div class="info-row"><span class="info-label"><?=$xml->supplier ?? 'Vendor'?></span><span class="info-value"><?=e($pr['vendor_name'])?></span></div>
            <?php endif; ?>
            <?php if(!empty($pr['company_name']) && empty($pr['customer_name']) && empty($pr['vendor_name'])): ?>
            <div class="info-row"><span class="info-label"><?=$xml->Company ?? 'Company'?></span><span class="info-value"><?=e($pr['company_name'])?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label"><?=$xml->from ?? 'Requester'?></span><span class="info-value"><?=e($pr['name'] ?? '')?></span></div>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header"><i class="fa fa-cubes" style="color:#667eea;margin-right:8px"></i> <?=$xml->Product ?? 'Products'?></div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?=$xml->Product ?? 'Product'?></th>
                        <th><?=$xml->Unit ?? 'Qty'?></th>
                        <th><?=$xml->Price ?? 'Price'?></th>
                        <th><?=$xml->total ?? 'Amount'?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($products)):
                        $grand = 0;
                        foreach($products as $i => $p):
                            $qty = floatval($p['quantity'] ?? $p['qty'] ?? 0);
                            $price = floatval($p['price'] ?? 0);
                            $amount = $qty * $price;
                            $grand += $amount;
                    ?>
                    <tr>
                        <td><?=$i+1?></td>
                        <td><strong><?=e($p['type_name'] ?? '')?></strong></td>
                        <td><?=number_format($qty)?></td>
                        <td><?=number_format($price, 2)?></td>
                        <td><strong><?=number_format($amount, 2)?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:#f9fafb;font-weight:700">
                        <td colspan="4" class="text-right" style="padding-right:20px"><?=$xml->total ?? 'Total'?></td>
                        <td style="color:#667eea;font-size:15px"><?=number_format($grand, 2)?></td>
                    </tr>
                    <?php else: ?>
                    <tr><td colspan="5" class="text-center" style="padding:40px;color:#9ca3af"><i class="fa fa-inbox"></i> <?=$xml->nodata ?? 'No products'?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($cancelled != '1' && $status == '0'): ?>
    <div class="action-card">
        <a href="index.php?page=po_make&pr_id=<?=e($pr_id)?>" class="btn-action btn-quotation"><i class="fa fa-magic"></i> <?=$xml->quotation ?? 'Create Quotation'?></a>
        <a href="index.php?page=pr_store&method=D&id=<?=e($pr_id)?>&csrf_token=<?=csrf_token()?>" onclick="return confirm('<?=$xml->confirmdelete ?? 'Are you sure?'?>')" class="btn-action btn-cancel"><i class="fa fa-times"></i> <?=$xml->cancel ?? 'Cancel'?></a>
    </div>
    <?php endif; ?>
</div>
