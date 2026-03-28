<?php
/**
 * Billing Note View — Admin Shell
 * Variables: $billing, $invoices, $vendor, $customer, $amount
 */
$bil = $billing ?? [];
$invs = $invoices ?? [];
$ven = $vendor ?? [];
$cus = $customer ?? [];
$amt = $amount ?? 0;
$bilNo = 'BN-' . str_pad($bil['bil_id'] ?? 0, 6, '0', STR_PAD_LEFT);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .billing-wrapper { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1000px; margin: 0 auto; }
    .page-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 24px 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 40px rgba(139,92,246,0.25); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .page-header h2 { margin: 0; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .page-header .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .page-header .header-actions a, .page-header .header-actions button { background: rgba(255,255,255,0.2); color: white; border: none; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
    .page-header .header-actions a:hover, .page-header .header-actions button:hover { background: rgba(255,255,255,0.35); text-decoration: none; color: white; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .info-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 24px; }
    .info-card h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin: 0 0 12px 0; font-weight: 600; }
    .info-card .name { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px; }
    .info-card .details { color: #6b7280; font-size: 13px; line-height: 1.8; }
    .billing-meta { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 24px; margin-bottom: 24px; }
    .billing-meta .meta-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
    .billing-meta .meta-row:last-child { border-bottom: none; }
    .billing-meta .meta-label { font-size: 13px; color: #6b7280; font-weight: 500; }
    .billing-meta .meta-value { font-size: 14px; color: #1f2937; font-weight: 600; }
    .description-box { background: #f9fafb; border-radius: 12px; padding: 20px; margin-bottom: 24px; border: 1px solid #e5e7eb; }
    .description-box h3 { font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 8px 0; }
    .description-box p { color: #4b5563; font-size: 14px; margin: 0; white-space: pre-line; }
    .data-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px; }
    .data-card .card-header { background: #f9fafb; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .data-card .count-badge { background: #8b5cf6; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .data-card table { width: 100%; border-collapse: collapse; }
    .data-card thead th { background: #f9fafb; padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; text-align: left; letter-spacing: 0.05em; }
    .data-card tbody td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-card tbody tr:hover { background: #faf5ff; }
    .data-card .total-row { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); }
    .data-card .total-row td { font-weight: 700; color: #7c3aed; border-top: 2px solid #e9d5ff; }
    .amount-col { text-align: right; font-family: 'Courier New', monospace; font-weight: 600; }
    .amount-highlight { text-align: right; font-family: 'Courier New', monospace; font-weight: 600; color: #059669; }
    .total-section { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 2px solid #e9d5ff; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px; }
    .total-section .label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #7c3aed; margin-bottom: 8px; font-weight: 600; }
    .total-section .amount { font-size: 32px; font-weight: 700; color: #7c3aed; }
    @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } .page-header { padding: 16px 20px; } .page-header h2 { font-size: 18px; } }
</style>

<div class="billing-wrapper">
    <div class="page-header">
        <h2><i class="fa fa-file-text"></i> <?=$bilNo?></h2>
        <div class="header-actions">
            <a href="index.php?page=billing_print&id=<?=e($bil['bil_id'])?>" target="_blank"><i class="fa fa-print"></i> <?=$xml->print ?? 'Print'?> / Download</a>
            <a href="index.php?page=billing"><i class="fa fa-arrow-left"></i> <?=$xml->back ?? 'Back'?></a>
        </div>
    </div>

    <!-- Billing Meta -->
    <div class="billing-meta">
        <div class="meta-row">
            <span class="meta-label">Billing Note No.</span>
            <span class="meta-value"><?=$bilNo?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label"><?=$xml->datecreate ?? 'Date Created'?></span>
            <span class="meta-value"><?=e($bil['billing_date'] ?? '')?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Invoices Included</span>
            <span class="meta-value"><?=count($invs)?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label"><?=$xml->total ?? 'Total'?> <?=$xml->price ?? 'Amount'?></span>
            <span class="meta-value" style="color:#7c3aed;font-size:18px">฿<?=number_format($amt, 2)?></span>
        </div>
    </div>

    <!-- From / To -->
    <div class="info-grid">
        <div class="info-card">
            <h3>From (Vendor)</h3>
            <div class="name"><?=e($ven['name_en'] ?? '')?></div>
            <div class="details">
                <?php if(!empty($ven['adr_tax'])): ?><?=e($ven['adr_tax'])?><br><?php endif; ?>
                <?php if(!empty($ven['city_tax']) || !empty($ven['province_tax'])): ?><?=e(trim(($ven['city_tax'] ?? '').' '.($ven['province_tax'] ?? '').' '.($ven['zip_tax'] ?? '')))?><br><?php endif; ?>
                <?php if(!empty($ven['tax'])): ?>Tax ID: <?=e($ven['tax'])?><br><?php endif; ?>
                <?php if(!empty($ven['phone'])): ?>Tel: <?=e($ven['phone'])?><br><?php endif; ?>
                <?php if(!empty($ven['email'])): ?>Email: <?=e($ven['email'])?><?php endif; ?>
            </div>
        </div>
        <div class="info-card">
            <h3>Bill To (<?=$xml->customer ?? 'Customer'?>)</h3>
            <div class="name"><?=e($cus['name_en'] ?? $bil['name_en'] ?? '')?></div>
            <div class="details">
                <?php if(!empty($cus['adr_tax'])): ?><?=e($cus['adr_tax'])?><br><?php endif; ?>
                <?php if(!empty($cus['city_tax']) || !empty($cus['province_tax'])): ?><?=e(trim(($cus['city_tax'] ?? '').' '.($cus['province_tax'] ?? '').' '.($cus['zip_tax'] ?? '')))?><br><?php endif; ?>
                <?php if(!empty($cus['tax'])): ?>Tax ID: <?=e($cus['tax'])?><br><?php endif; ?>
                <?php if(!empty($cus['phone'])): ?>Tel: <?=e($cus['phone'])?><br><?php endif; ?>
                <?php if(!empty($cus['email'])): ?>Email: <?=e($cus['email'])?><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if(!empty($bil['des'])): ?>
    <div class="description-box">
        <h3><?=$xml->description ?? 'Description'?></h3>
        <p><?=e($bil['des'])?></p>
    </div>
    <?php endif; ?>

    <!-- Invoices Table -->
    <div class="data-card">
        <div class="card-header">
            <i class="fa fa-list" style="color:#8b5cf6"></i> Invoices Included
            <span class="count-badge"><?=count($invs)?></span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice No.</th>
                        <th><?=$xml->datecreate ?? 'Date'?></th>
                        <th><?=$xml->description ?? 'Description'?></th>
                        <th style="text-align:right"><?=$xml->price ?? 'Amount'?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($invs)): $i=1; foreach($invs as $inv): ?>
                    <tr>
                        <td><?=$i++?></td>
                        <td><strong><?=e($inv['po_number'] ?? $inv['inv_no'] ?? '')?></strong></td>
                        <td><?=e($inv['invoice_date'] ?? '')?></td>
                        <td><?=e($inv['pr_description'] ?? $inv['po_name'] ?? '')?></td>
                        <td class="amount-highlight">฿<?=number_format(floatval($inv['amount']), 2)?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align:right"><?=$xml->total ?? 'TOTAL'?></td>
                        <td class="amount-col" style="color:#7c3aed">฿<?=number_format($amt, 2)?></td>
                    </tr>
                    <?php else: ?>
                    <tr><td colspan="5" class="text-center" style="padding:40px;color:#9ca3af">No invoices linked</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Total Amount -->
    <div class="total-section">
        <div class="label"><?=$xml->total ?? 'Total'?> Billing <?=$xml->price ?? 'Amount'?></div>
        <div class="amount">฿<?=number_format($amt, 2)?></div>
    </div>
</div>
