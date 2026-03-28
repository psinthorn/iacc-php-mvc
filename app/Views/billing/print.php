<?php
/**
 * Billing Note Print — Standalone Printable Page
 * Variables available from controller: $billing, $invoices, $vendor, $customer, $amount
 */
$bil = $billing ?? [];
$invs = $invoices ?? [];
$ven = $vendor ?? [];
$cus = $customer ?? [];
$amt = $amount ?? 0;
$bilNo = 'BN-' . str_pad($bil['bil_id'] ?? 0, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?=$bilNo?> - Billing Note</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 12px; line-height: 1.5; color: #1f2937; background: #f3f4f6; padding: 20px; }
        .billing-container { max-width: 800px; margin: 0 auto; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden; }
        .billing-header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #fff; padding: 30px 40px; display: flex; justify-content: space-between; align-items: flex-start; }
        .billing-header .logo-section { display: flex; align-items: center; gap: 16px; }
        .billing-header .logo-section img { max-height: 60px; max-width: 150px; }
        .billing-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 4px; }
        .billing-header .subtitle { opacity: 0.9; font-size: 14px; }
        .billing-info { text-align: right; }
        .billing-info .number { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .billing-info .date { opacity: 0.9; font-size: 13px; }
        .billing-body { padding: 40px; }
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .party-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 12px; font-weight: 600; }
        .party-box .name { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px; }
        .party-box .details { color: #6b7280; font-size: 12px; }
        .party-box .details p { margin-bottom: 4px; }
        .description-section { background: #f9fafb; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
        .description-section h3 { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px; }
        .description-section .content { color: #4b5563; font-size: 14px; white-space: pre-line; }
        .invoices-section { margin-bottom: 30px; }
        .invoices-section h3 { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .invoices-section .count-badge { background: #8b5cf6; color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .invoice-table { width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .invoice-table th { background: #f9fafb; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        .invoice-table td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        .invoice-table tr:last-child td { border-bottom: none; }
        .invoice-table .amount { text-align: right; font-weight: 600; color: #059669; }
        .invoice-table .total-row { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); }
        .invoice-table .total-row td { font-weight: 700; color: #7c3aed; border-top: 2px solid #e9d5ff; }
        .amount-section { background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 2px solid #e9d5ff; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 30px; }
        .amount-section .label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #7c3aed; margin-bottom: 8px; font-weight: 600; }
        .amount-section .amount { font-size: 32px; font-weight: 700; color: #7c3aed; }
        .billing-footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .billing-footer p { color: #6b7280; font-size: 11px; }
        @media print {
            body { background: #fff; padding: 0; }
            .billing-container { box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
        }
        .action-buttons { text-align: center; margin-bottom: 20px; }
        .action-buttons button { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; margin: 0 8px; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; }
        .action-buttons button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(139,92,246,0.3); }
        .action-buttons a { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .action-buttons a:hover { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button onclick="window.print()">🖨️ Print</button>
        <a href="index.php?page=billing_view&id=<?=htmlspecialchars($bil['bil_id'] ?? 0)?>">← Back to View</a>
        <a href="index.php?page=billing">← Billing List</a>
    </div>

    <div class="billing-container">
        <div class="billing-header">
            <div class="logo-section">
                <?php if (!empty($ven['logo'])): ?>
                <img src="upload/<?=htmlspecialchars($ven['logo'])?>" alt="Logo">
                <?php endif; ?>
                <div>
                    <h1>BILLING NOTE</h1>
                    <div class="subtitle"><?=htmlspecialchars($ven['name_en'] ?? '')?></div>
                </div>
            </div>
            <div class="billing-info">
                <div class="number"><?=$bilNo?></div>
                <div class="date">Date: <?=htmlspecialchars($bil['billing_date'] ?? date('d/m/Y'))?></div>
                <div class="date"><?=count($invs)?> Invoice(s)</div>
            </div>
        </div>

        <div class="billing-body">
            <div class="parties">
                <div class="party-box">
                    <h3>From</h3>
                    <div class="name"><?=htmlspecialchars($ven['name_en'] ?? '')?></div>
                    <div class="details">
                        <?php if(!empty($ven['adr_tax'])): ?><p><?=htmlspecialchars($ven['adr_tax'])?></p><?php endif; ?>
                        <?php if(!empty($ven['city_tax']) || !empty($ven['province_tax'])): ?>
                        <p><?=htmlspecialchars(trim(($ven['city_tax'] ?? '').' '.($ven['province_tax'] ?? '').' '.($ven['zip_tax'] ?? '')))?></p>
                        <?php endif; ?>
                        <?php if(!empty($ven['tax'])): ?><p>Tax ID: <?=htmlspecialchars($ven['tax'])?></p><?php endif; ?>
                        <?php if(!empty($ven['phone'])): ?><p>Tel: <?=htmlspecialchars($ven['phone'])?></p><?php endif; ?>
                    </div>
                </div>
                <div class="party-box">
                    <h3>Bill To</h3>
                    <div class="name"><?=htmlspecialchars($cus['name_en'] ?? $bil['name_en'] ?? '')?></div>
                    <div class="details">
                        <?php if(!empty($cus['adr_tax'])): ?><p><?=htmlspecialchars($cus['adr_tax'])?></p><?php endif; ?>
                        <?php if(!empty($cus['city_tax']) || !empty($cus['province_tax'])): ?>
                        <p><?=htmlspecialchars(trim(($cus['city_tax'] ?? '').' '.($cus['province_tax'] ?? '').' '.($cus['zip_tax'] ?? '')))?></p>
                        <?php endif; ?>
                        <?php if(!empty($cus['tax'])): ?><p>Tax ID: <?=htmlspecialchars($cus['tax'])?></p><?php endif; ?>
                        <?php if(!empty($cus['phone'])): ?><p>Tel: <?=htmlspecialchars($cus['phone'])?></p><?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if(!empty($bil['des'])): ?>
            <div class="description-section">
                <h3>Description</h3>
                <div class="content"><?=htmlspecialchars($bil['des'])?></div>
            </div>
            <?php endif; ?>

            <!-- Invoice List -->
            <div class="invoices-section">
                <h3>Invoices Included <span class="count-badge"><?=count($invs)?></span></h3>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th style="text-align:right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($invs)): $i=1; foreach($invs as $inv): ?>
                        <tr>
                            <td><?=$i++?></td>
                            <td><strong><?=htmlspecialchars($inv['po_number'] ?? $inv['inv_no'] ?? '')?></strong></td>
                            <td><?=htmlspecialchars($inv['invoice_date'] ?? '')?></td>
                            <td><?=htmlspecialchars($inv['pr_description'] ?? $inv['po_name'] ?? '')?></td>
                            <td class="amount">฿<?=number_format(floatval($inv['amount']), 2)?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right">TOTAL</td>
                            <td class="amount" style="color:#7c3aed">฿<?=number_format($amt, 2)?></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af">No invoices found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="amount-section">
                <div class="label">Total Billing Amount</div>
                <div class="amount">฿<?=number_format($amt, 2)?></div>
            </div>
        </div>

        <div class="billing-footer">
            <p>This is a computer-generated document. No signature is required.</p>
        </div>
    </div>
</body>
</html>
