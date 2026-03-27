<?php
/**
 * API Invoices View
 *
 * Variables: $subscription, $invoices
 */
$generated = intval($_GET['generated'] ?? 0) === 1;
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">
    <div class="master-data-header">
        <h2><i class="fa fa-file-text-o"></i> API Invoices</h2>
        <div>
            <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
            <form method="post" action="index.php?page=api_invoice_generate" style="display:inline; margin-left:8px;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-refresh"></i> Generate / Refresh</button>
            </form>
        </div>
    </div>

    <?php if ($generated): ?>
    <div class="alert alert-success" style="border-radius:8px;">
        <i class="fa fa-check-circle"></i> Invoice generated successfully.
    </div>
    <?php endif; ?>

    <?php if (!$subscription): ?>
    <div style="text-align:center; padding:40px 20px; color:#999; background:#fff; border-radius:12px;">
        <i class="fa fa-info-circle" style="font-size:2rem; margin-bottom:10px;"></i>
        <p>No active API subscription found.</p>
    </div>
    <?php else: ?>

    <div class="stats-row">
        <div class="stat-card primary">
            <i class="fa fa-bookmark stat-icon"></i>
            <div class="stat-value" style="text-transform:capitalize;"><?= htmlspecialchars($subscription['plan']) ?></div>
            <div class="stat-label">Current Plan</div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-list-ol stat-icon"></i>
            <div class="stat-value"><?= count($invoices) ?></div>
            <div class="stat-label">Invoices</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if (empty($invoices)): ?>
            <p style="color:#777; margin:0;">No invoices yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0;">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Period</th>
                        <th>Plan</th>
                        <th>Usage</th>
                        <th>Base</th>
                        <th>Overage</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Issued</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                        <td><?= htmlspecialchars($inv['period_start']) ?> → <?= htmlspecialchars($inv['period_end']) ?></td>
                        <td style="text-transform:capitalize;"><?= htmlspecialchars($inv['plan']) ?></td>
                        <td><?= intval($inv['orders_used']) ?> / <?= intval($inv['orders_limit']) ?></td>
                        <td>฿<?= number_format(floatval($inv['base_amount']), 2) ?></td>
                        <td>฿<?= number_format(floatval($inv['overage_amount']), 2) ?></td>
                        <td><strong>฿<?= number_format(floatval($inv['total_amount']), 2) ?></strong></td>
                        <td>
                            <?php $s = $inv['status']; ?>
                            <span class="badge badge-<?= $s === 'paid' ? 'success' : ($s === 'overdue' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($s) ?></span>
                        </td>
                        <td><?= $inv['issued_at'] ? date('M d, Y', strtotime($inv['issued_at'])) : '-' ?></td>
                        <td><?= $inv['due_at'] ? date('M d, Y', strtotime($inv['due_at'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>
