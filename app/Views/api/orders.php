<?php
/**
 * Channel Orders List View
 * 
 * Variables from AdminApiController::orders():
 *   $orders, $total, $pagination, $filters, $stats, $subscription
 */
require_once __DIR__ . '/../../inc/pagination.php';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-calendar"></i> Channel Orders</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-calendar stat-icon"></i>
        <div class="stat-value"><?= intval($stats['total'] ?? 0) ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
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
    <div class="stat-card info">
        <i class="fa fa-bar-chart stat-icon"></i>
        <div class="stat-value"><?= intval($stats['this_month'] ?? 0) ?></div>
        <div class="stat-label">This Month</div>
    </div>
</div>

<!-- Filters -->
<div class="action-toolbar">
    <form method="get" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end; width:100%;">
        <input type="hidden" name="page" value="api_orders">
        <div>
            <input type="text" name="search" class="form-control" placeholder="Search guest..." value="<?= htmlspecialchars($filters['search']) ?>" style="min-width:200px;">
        </div>
        <div>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <?php foreach (['pending','processing','completed','failed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select name="channel" class="form-control">
                <option value="">All Channels</option>
                <?php foreach (['website','email','line','facebook','manual'] as $ch): ?>
                <option value="<?= $ch ?>" <?= $filters['channel'] === $ch ? 'selected' : '' ?>><?= ucfirst($ch) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from']) ?>" placeholder="From">
        </div>
        <div>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to']) ?>" placeholder="To">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
        <a href="index.php?page=api_orders" class="btn btn-outline-secondary">Clear</a>
    </form>
</div>

<!-- Orders Table -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <?php if (empty($orders)): ?>
        <p style="color:#999; text-align:center; padding:30px;">No orders found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest</th>
                    <th>Contact</th>
                    <th>Channel</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Room</th>
                    <th>Amount</th>
                    <th>PO</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $b): ?>
                <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['guest_name']) ?></strong></td>
                    <td>
                        <?php if ($b['guest_email']): ?><small><?= htmlspecialchars($b['guest_email']) ?></small><br><?php endif; ?>
                        <?php if ($b['guest_phone']): ?><small><?= htmlspecialchars($b['guest_phone']) ?></small><?php endif; ?>
                    </td>
                    <td><span class="badge badge-info"><?= $b['channel'] ?></span></td>
                    <td><?= $b['check_in'] ?: '-' ?></td>
                    <td><?= $b['check_out'] ?: '-' ?></td>
                    <td><?= htmlspecialchars($b['room_type'] ?? '-') ?></td>
                    <td>฿<?= number_format(floatval($b['total_amount']), 0) ?></td>
                    <td>
                        <?php if ($b['linked_po_id']): ?>
                            <a href="index.php?page=po_view&id=<?= $b['linked_po_id'] ?>">#<?= $b['linked_po_id'] ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $colors = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary'];
                        ?>
                        <span class="badge badge-<?= $colors[$b['status']] ?? 'secondary' ?>"><?= $b['status'] ?></span>
                    </td>
                    <td><?= date('M d, H:i', strtotime($b['created_at'])) ?></td>
                    <td>
                        <a href="index.php?page=api_order_detail&id=<?= $b['id'] ?>" class="btn btn-xs btn-outline-primary" title="View Details">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total > 15): ?>
    <div style="margin-top:15px;">
        <?php render_pagination($pagination, 'index.php?page=api_orders&' . http_build_query(array_filter($filters))); ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

</div>
