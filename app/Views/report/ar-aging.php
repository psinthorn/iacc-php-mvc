<?php
$pageTitle = 'Reports — AR Aging';

/**
 * AR Aging Report — Accounts Receivable Aging
 * Variables: $buckets, $grand_total, $com_id
 */

function format_baht($amount) {
    return '฿' . number_format($amount, 2);
}

function aging_badge($days) {
    if ($days <= 30)  return '<span class="aging-badge current">Current</span>';
    if ($days <= 60)  return '<span class="aging-badge days30">31-60d</span>';
    if ($days <= 90)  return '<span class="aging-badge days60">61-90d</span>';
    if ($days <= 120) return '<span class="aging-badge days90">91-120d</span>';
    return '<span class="aging-badge overdue">120+d</span>';
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .aging-wrapper { max-width: 1400px; margin: 0 auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .aging-header { padding: 24px 28px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 16px; box-shadow: 0 10px 40px rgba(239, 68, 68, 0.25); color: white; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .aging-header h2 { font-size: 24px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
    .aging-header p { font-size: 14px; opacity: 0.9; margin: 5px 0 0; }
    .aging-header .total-outstanding { text-align: right; }
    .aging-header .total-outstanding .amount { font-size: 32px; font-weight: 700; }
    .aging-header .total-outstanding .label { font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }

    .aging-summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
    @media (max-width: 900px) { .aging-summary { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 576px) { .aging-summary { grid-template-columns: 1fr 1fr; } }
    .aging-bucket-card { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center; }
    .aging-bucket-card .bucket-label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .aging-bucket-card .bucket-amount { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
    .aging-bucket-card .bucket-count { font-size: 11px; color: #9ca3af; }
    .aging-bucket-card.current .bucket-amount { color: #10b981; }
    .aging-bucket-card.days31 .bucket-amount { color: #f59e0b; }
    .aging-bucket-card.days61 .bucket-amount { color: #f97316; }
    .aging-bucket-card.days91 .bucket-amount { color: #ef4444; }
    .aging-bucket-card.days121 .bucket-amount { color: #dc2626; }

    .aging-detail-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 16px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .aging-detail-card h5 { font-size: 15px; font-weight: 600; color: #1f2937; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
    .aging-detail-card h5 .badge-count { background: #667eea; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 8px; }

    .aging-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .aging-table thead th { background: #f9fafb; padding: 10px 12px; text-align: left; font-weight: 600; color: #374151; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; }
    .aging-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    .aging-table tbody tr:hover { background: rgba(102, 126, 234, 0.03); }
    .aging-table .text-right { text-align: right; }
    .aging-table tfoot td { padding: 12px; font-weight: 700; border-top: 2px solid #e5e7eb; background: #f9fafb; }

    .aging-badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; }
    .aging-badge.current { background: #d1fae5; color: #065f46; }
    .aging-badge.days30 { background: #fef3c7; color: #92400e; }
    .aging-badge.days60 { background: #ffedd5; color: #9a3412; }
    .aging-badge.days90 { background: #fee2e2; color: #991b1b; }
    .aging-badge.overdue { background: #fecaca; color: #7f1d1d; }

    .back-link { display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.9); text-decoration: none; font-size: 13px; margin-top: 8px; }
    .back-link:hover { color: white; text-decoration: none; }
    .empty-bucket { text-align: center; padding: 20px; color: #9ca3af; font-size: 13px; }
    .empty-bucket i { font-size: 24px; margin-bottom: 8px; display: block; }

    .print-btn { padding: 8px 16px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .print-btn:hover { background: rgba(255,255,255,0.3); color: white; text-decoration: none; }
    @media print {
        .aging-header .print-btn, .back-link { display: none !important; }
        .aging-bucket-card, .aging-detail-card { break-inside: avoid; }
    }
</style>

<div class="aging-wrapper">
    <!-- Header -->
    <div class="aging-header">
        <div>
            <h2><i class="fa fa-clock-o"></i> Accounts Receivable Aging</h2>
            <p>Outstanding invoices by aging period — <?php echo date('F j, Y'); ?></p>
            <a href="index.php?page=report_hub" class="back-link"><i class="fa fa-arrow-left"></i> Back to Reports</a>
        </div>
        <div class="total-outstanding">
            <div class="label">Total Outstanding</div>
            <div class="amount"><?php echo format_baht($grand_total); ?></div>
            <button onclick="window.print()" class="print-btn"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>

    <!-- Summary Buckets -->
    <div class="aging-summary">
        <?php 
        $bucketClasses = ['current' => 'current', 'days31' => 'days31', 'days61' => 'days61', 'days91' => 'days91', 'days121' => 'days121'];
        foreach ($buckets as $key => $bucket): 
        ?>
        <div class="aging-bucket-card <?php echo $bucketClasses[$key] ?? ''; ?>">
            <div class="bucket-label"><?php echo $bucket['label']; ?></div>
            <div class="bucket-amount"><?php echo format_baht($bucket['total']); ?></div>
            <div class="bucket-count"><?php echo count($bucket['items']); ?> invoice<?php echo count($bucket['items']) != 1 ? 's' : ''; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Detail Tables -->
    <?php foreach ($buckets as $key => $bucket): ?>
    <div class="aging-detail-card">
        <h5>
            <i class="fa fa-<?php echo $key === 'current' ? 'check-circle' : 'exclamation-triangle'; ?>" 
               style="color: <?php echo $key === 'current' ? '#10b981' : ($key === 'days121' ? '#dc2626' : '#f59e0b'); ?>;"></i>
            <?php echo $bucket['label']; ?>
            <span class="badge-count"><?php echo count($bucket['items']); ?></span>
            <span style="float: right; font-size: 14px; color: #6b7280;">Total: <strong><?php echo format_baht($bucket['total']); ?></strong></span>
        </h5>

        <?php if (empty($bucket['items'])): ?>
            <div class="empty-bucket">
                <i class="fa fa-check-circle"></i>
                No outstanding invoices in this period
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="aging-table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Customer</th>
                        <th>Invoice Date</th>
                        <th>Days</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Outstanding</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bucket['items'] as $item): ?>
                    <tr>
                        <td><strong>#<?php echo htmlspecialchars($item['po_number'] ?: $item['po_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars(mb_substr($item['company_name'] ?? 'N/A', 0, 30)); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($item['invoice_date'])); ?></td>
                        <td><strong><?php echo $item['days_outstanding']; ?></strong>d</td>
                        <td class="text-right"><?php echo format_baht($item['total_amount']); ?></td>
                        <td class="text-right"><?php echo format_baht($item['paid_amount']); ?></td>
                        <td class="text-right" style="font-weight: 600; color: #ef4444;"><?php echo format_baht($item['outstanding']); ?></td>
                        <td><?php echo aging_badge($item['days_outstanding']); ?></td>
                        <td>
                            <a href="index.php?page=po_view&id=<?php echo $item['po_id']; ?>" class="btn btn-xs btn-default" title="View PO">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong><?php echo count($bucket['items']); ?> invoice<?php echo count($bucket['items']) != 1 ? 's' : ''; ?></strong></td>
                        <td class="text-right"><?php echo format_baht(array_sum(array_column($bucket['items'], 'total_amount'))); ?></td>
                        <td class="text-right"><?php echo format_baht(array_sum(array_column($bucket['items'], 'paid_amount'))); ?></td>
                        <td class="text-right" style="color: #ef4444;"><?php echo format_baht($bucket['total']); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
