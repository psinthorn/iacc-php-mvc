<?php
$pageTitle = 'Payment History';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
$statusColors = ['pending'=>'warning','completed'=>'success','failed'=>'danger','refunded'=>'default'];
?>
<div class="container-fluid" style="padding:24px 20px;">
    <div class="page-header" style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h2 style="margin:0;"><i class="fa fa-history" style="color:#6366f1;"></i> Payment History</h2>
        </div>
        <a href="index.php?page=billing" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Back to Billing
        </a>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    <?php if (empty($payments)): ?>
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fa fa-credit-card" style="font-size:40px;display:block;margin-bottom:12px;"></i>
            No payment records yet.
        </div>
    <?php else: ?>
        <table class="table table-hover" style="margin:0;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Date</th>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Plan</th>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Cycle</th>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Amount</th>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Method</th>
                    <th style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td style="font-size:13px;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td style="font-size:13px;font-weight:600;text-transform:capitalize;"><?= $e($p['plan_code']) ?></td>
                <td style="font-size:13px;text-transform:capitalize;"><?= $e($p['billing_cycle']) ?></td>
                <td style="font-size:13px;font-weight:600;">฿<?= number_format(floatval($p['amount']), 2) ?></td>
                <td style="font-size:13px;text-transform:capitalize;"><?= $e(str_replace('_', ' ', $p['payment_method'])) ?></td>
                <td>
                    <span class="label label-<?= $statusColors[$p['status']] ?? 'default' ?>" style="border-radius:10px;padding:3px 9px;font-size:11px;">
                        <?= $e(ucfirst($p['status'])) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div>
</div>
