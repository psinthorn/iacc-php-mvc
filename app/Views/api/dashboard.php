<?php
/**
 * Sales Channel Dashboard View
 * 
 * Variables from AdminApiController::dashboard():
 *   $subscription, $stats, $recentOrders, $dailyUsage, $monthlyUsage
 */
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-plug"></i> Sales Channel Dashboard</h2>
    <div>
        <a href="index.php?page=api_keys" class="btn btn-sm btn-outline-primary"><i class="fa fa-key"></i> API Keys</a>
        <a href="index.php?page=api_orders" class="btn btn-sm btn-outline-primary"><i class="fa fa-calendar"></i> Orders</a>
        <a href="index.php?page=api_webhooks" class="btn btn-sm btn-outline-primary"><i class="fa fa-bell"></i> Webhooks</a>
        <a href="index.php?page=api_usage_logs" class="btn btn-sm btn-outline-primary"><i class="fa fa-bar-chart"></i> Usage Logs</a>
        <a href="index.php?page=api_docs" class="btn btn-sm btn-outline-primary"><i class="fa fa-book"></i> API Docs</a>
    </div>
</div>

<?php if (!$subscription): ?>
<!-- No Subscription — Show Activation CTA -->
<div style="text-align:center; padding:60px 20px;">
    <i class="fa fa-rocket" style="font-size:4rem; color:#8e44ad; margin-bottom:20px;"></i>
    <h3>Start Your Sales Channel API Trial</h3>
    <p style="color:#666; max-width:500px; margin:10px auto 30px;">
        Receive orders from any website, LINE, Facebook, or email — automatically synced to your iACC account.
        Try it free for 14 days.
    </p>
    <form method="post" action="index.php?page=api_activate_trial">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa fa-play-circle"></i> Activate 14-Day Free Trial
        </button>
    </form>
    <p style="color:#999; font-size:0.85rem; margin-top:15px;">50 orders/month • 1 API key • Website channel • No credit card required</p>
</div>

<?php else: ?>
<!-- Subscription Info -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-bookmark stat-icon"></i>
        <div class="stat-value" style="text-transform:capitalize;"><?= htmlspecialchars($subscription['plan']) ?></div>
        <div class="stat-label">Current Plan</div>
    </div>
    <div class="stat-card <?= $subscription['status'] === 'active' ? 'success' : 'danger' ?>">
        <i class="fa fa-<?= $subscription['status'] === 'active' ? 'check-circle' : 'times-circle' ?> stat-icon"></i>
        <div class="stat-value" style="text-transform:capitalize;"><?= $subscription['status'] ?></div>
        <div class="stat-label">Status</div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-bar-chart stat-icon"></i>
        <div class="stat-value"><?= $monthlyUsage ?> / <?= $subscription['orders_limit'] ?></div>
        <div class="stat-label">Orders This Month</div>
    </div>
    <div class="stat-card warning">
        <i class="fa fa-calendar-check-o stat-icon"></i>
        <div class="stat-value"><?= intval($stats['completed'] ?? 0) ?></div>
        <div class="stat-label">Completed Orders</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #8e44ad;">
        <i class="fa fa-bell stat-icon" style="color:#8e44ad;"></i>
        <div class="stat-value"><?= intval($webhookCount ?? 0) ?></div>
        <div class="stat-label"><a href="index.php?page=api_webhooks" style="color:inherit;">Webhooks</a></div>
    </div>
</div>

<!-- Quota Progress Bar -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
        <strong>Monthly Quota</strong>
        <span><?= $monthlyUsage ?> / <?= $subscription['orders_limit'] ?> orders</span>
    </div>
    <?php $pct = $subscription['orders_limit'] > 0 ? min(100, round($monthlyUsage / $subscription['orders_limit'] * 100)) : 0; ?>
    <div style="background:#e9ecef; border-radius:8px; height:12px; overflow:hidden;">
        <div style="background:<?= $pct > 90 ? '#e74c3c' : ($pct > 70 ? '#f39c12' : '#27ae60') ?>; width:<?= $pct ?>%; height:100%; border-radius:8px; transition:width 0.5s;"></div>
    </div>
    <?php if ($subscription['plan'] === 'trial' && $subscription['trial_end']): ?>
    <div style="margin-top:8px; font-size:0.85rem; color:#666;">
        <i class="fa fa-clock-o"></i> Trial expires: <?= date('M d, Y', strtotime($subscription['trial_end'])) ?>
        — <a href="index.php?page=api_upgrade" style="color:#3498db;">Upgrade Now →</a>
    </div>
    <?php endif; ?>

    <?php if (($quotaPercent ?? 0) >= 80): ?>
    <div class="alert alert-warning" style="margin-top:12px; margin-bottom:0; border-radius:8px;">
        <i class="fa fa-exclamation-triangle"></i>
        You are using <strong><?= intval($quotaPercent) ?>%</strong> of your monthly order quota.
        <a href="index.php?page=api_upgrade" style="margin-left:8px;">Upgrade plan</a>
    </div>
    <?php endif; ?>
</div>

<!-- Usage Trend -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h4 style="margin:0;"><i class="fa fa-line-chart"></i> Daily API Usage (Last 7 Days)</h4>
        <small style="color:#777;">Requests per day</small>
    </div>
    <?php
    $dailyRows = $dailyUsage ?? [];
    $maxReq = 1;
    foreach ($dailyRows as $r) {
        $maxReq = max($maxReq, intval($r['requests'] ?? 0));
    }
    ?>
    <?php if (empty($dailyRows)): ?>
        <p style="color:#999; margin:0;">No usage data yet.</p>
    <?php else: ?>
        <div style="display:flex; gap:8px; align-items:flex-end; height:180px; padding:10px 0;">
            <?php foreach (array_reverse($dailyRows) as $r): ?>
                <?php
                $req = intval($r['requests'] ?? 0);
                $h = max(6, intval(($req / $maxReq) * 140));
                $errors = intval($r['errors'] ?? 0);
                $barColor = $errors > 0 ? '#f39c12' : '#3498db';
                ?>
                <div style="flex:1; min-width:30px; text-align:center;">
                    <div title="<?= htmlspecialchars($r['day']) ?>: <?= $req ?> request(s), <?= $errors ?> error(s)"
                         style="height:<?= $h ?>px; background:<?= $barColor ?>; border-radius:6px 6px 0 0;"></div>
                    <div style="font-size:0.75rem; color:#666; margin-top:6px;"><?= date('m/d', strtotime($r['day'])) ?></div>
                    <div style="font-size:0.75rem; color:#222;"><?= $req ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Order Stats -->
<div class="stats-row">
    <div class="stat-card success">
        <i class="fa fa-check stat-icon"></i>
        <div class="stat-value"><?= intval($stats['completed'] ?? 0) ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card warning">
        <i class="fa fa-clock-o stat-icon"></i>
        <div class="stat-value"><?= intval($stats['pending'] ?? 0) ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card danger">
        <i class="fa fa-exclamation-triangle stat-icon"></i>
        <div class="stat-value"><?= intval($stats['failed'] ?? 0) ?></div>
        <div class="stat-label">Failed</div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-money stat-icon"></i>
        <div class="stat-value">฿<?= number_format(floatval($stats['total_revenue'] ?? 0), 0) ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
</div>

<!-- Recent Orders -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h4 style="margin:0;"><i class="fa fa-calendar"></i> Recent Orders</h4>
        <a href="index.php?page=api_orders" style="font-size:0.9rem;">View All →</a>
    </div>
    <?php if (empty($recentOrders)): ?>
        <p style="color:#999; text-align:center; padding:30px;">No orders yet. Send your first API request to get started!</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest</th>
                    <th>Channel</th>
                    <th>Check-in</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $b): ?>
                <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td><?= htmlspecialchars($b['guest_name']) ?></td>
                    <td><span class="badge badge-info"><?= $b['channel'] ?></span></td>
                    <td><?= $b['check_in'] ?: '-' ?></td>
                    <td>฿<?= number_format(floatval($b['total_amount']), 0) ?></td>
                    <td>
                        <?php
                        $statusColors = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary'];
                        $color = $statusColors[$b['status']] ?? 'secondary';
                        ?>
                        <span class="badge badge-<?= $color ?>"><?= $b['status'] ?></span>
                    </td>
                    <td><?= date('M d, H:i', strtotime($b['created_at'])) ?></td>
                    <td><a href="index.php?page=api_order_detail&id=<?= $b['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fa fa-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
