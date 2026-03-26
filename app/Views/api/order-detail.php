<?php
/**
 * Order Detail View
 * 
 * Variables from AdminApiController::orderDetail():
 *   $order
 */
$statusColors = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary'];
$color = $statusColors[$order['status']] ?? 'secondary';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-calendar-check-o"></i> Order #<?= $order['id'] ?></h2>
    <div>
        <a href="index.php?page=api_orders" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Back to Orders</a>
    </div>
</div>

<!-- Status Banner -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
    <div>
        <span class="badge badge-<?= $color ?>" style="font-size:1.1rem; padding:6px 15px;">
            <?= ucfirst($order['status']) ?>
        </span>
        <span style="margin-left:10px; color:#666;">
            Channel: <span class="badge badge-info"><?= htmlspecialchars($order['channel']) ?></span>
        </span>
    </div>
    <div style="text-align:right; color:#666; font-size:0.9rem;">
        <div>Created: <?= date('M d, Y H:i:s', strtotime($order['created_at'])) ?></div>
        <?php if ($order['processed_at']): ?>
        <div>Processed: <?= date('M d, Y H:i:s', strtotime($order['processed_at'])) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Panel -->
<?php
$allowedActions = [];
switch ($order['status']) {
    case 'pending':    $allowedActions = ['approve' => ['Approve & Process', 'success', 'check'], 'reject' => ['Reject', 'danger', 'times'], 'cancel' => ['Cancel', 'secondary', 'ban']]; break;
    case 'processing': $allowedActions = ['cancel' => ['Cancel', 'secondary', 'ban']]; break;
    case 'completed':  $allowedActions = ['cancel' => ['Cancel', 'secondary', 'ban']]; break;
    case 'failed':     $allowedActions = ['retry' => ['Retry Processing', 'warning', 'refresh'], 'cancel' => ['Cancel', 'secondary', 'ban']]; break;
}
if (!empty($allowedActions)):
?>
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-cogs"></i> Order Actions</h4>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_action'): ?>
    <div class="alert alert-danger" style="margin-bottom:15px;">
        <i class="fa fa-exclamation-triangle"></i> That action is not allowed for the current order status.
    </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=api_order_update_status" id="orderActionForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">
        <input type="hidden" name="action" id="orderAction" value="">
        
        <div style="margin-bottom:15px;">
            <label style="color:#666; font-size:0.9rem; display:block; margin-bottom:5px;">Admin Notes (optional)</label>
            <textarea name="admin_notes" class="form-control" rows="2" placeholder="Add a note about this action..." style="resize:vertical;"></textarea>
        </div>
        
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ($allowedActions as $action => [$label, $btnColor, $icon]): ?>
            <button type="button" 
                    class="btn btn-<?= $btnColor ?>" 
                    onclick="confirmAction('<?= $action ?>', '<?= $label ?>')"
                    style="min-width:120px;">
                <i class="fa fa-<?= $icon ?>"></i> <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<script>
function confirmAction(action, label) {
    var messages = {
        'approve': 'This will process the order and create PR/PO records. Continue?',
        'reject': 'This will mark the order as failed (rejected). Continue?',
        'cancel': 'This will cancel the order. Continue?',
        'retry': 'This will retry processing the failed order. Continue?'
    };
    if (confirm(messages[action] || ('Are you sure you want to ' + label + '?'))) {
        document.getElementById('orderAction').value = action;
        document.getElementById('orderActionForm').submit();
    }
}
</script>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

<!-- Guest Information -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-user"></i> Guest Information</h4>
    <table class="table" style="margin:0;">
        <tr><td style="width:120px; color:#666;"><strong>Name</strong></td><td><?= htmlspecialchars($order['guest_name']) ?></td></tr>
        <tr><td style="color:#666;"><strong>Email</strong></td><td><?= htmlspecialchars($order['guest_email'] ?: '-') ?></td></tr>
        <tr><td style="color:#666;"><strong>Phone</strong></td><td><?= htmlspecialchars($order['guest_phone'] ?: '-') ?></td></tr>
        <tr><td style="color:#666;"><strong>Guests</strong></td><td><?= intval($order['guests']) ?></td></tr>
    </table>
</div>

<!-- Order Details -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-info-circle"></i> Order Details</h4>
    <table class="table" style="margin:0;">
        <tr><td style="width:120px; color:#666;"><strong>Room Type</strong></td><td><?= htmlspecialchars($order['room_type'] ?: '-') ?></td></tr>
        <tr><td style="color:#666;"><strong>Check-in</strong></td><td><?= $order['check_in'] ?: '-' ?></td></tr>
        <tr><td style="color:#666;"><strong>Check-out</strong></td><td><?= $order['check_out'] ?: '-' ?></td></tr>
        <tr>
            <td style="color:#666;"><strong>Amount</strong></td>
            <td><strong style="font-size:1.1rem;">฿<?= number_format(floatval($order['total_amount']), 2) ?></strong> <?= $order['currency'] ?></td>
        </tr>
    </table>
</div>

</div>

<!-- Linked Records -->
<?php if ($order['linked_pr_id'] || $order['linked_po_id'] || $order['linked_company_id']): ?>
<div style="background:white; border-radius:12px; padding:20px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-link"></i> Linked Records</h4>
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <?php if ($order['linked_company_id']): ?>
        <div style="background:#f8f9fa; border-radius:8px; padding:15px; flex:1; min-width:200px;">
            <div style="color:#666; font-size:0.85rem;">Customer</div>
            <div style="font-size:1.2rem; font-weight:bold;">
                <i class="fa fa-building"></i> 
                <a href="index.php?page=company&method=V&id=<?= $order['linked_company_id'] ?>">
                    #<?= $order['linked_company_id'] ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($order['linked_pr_id']): ?>
        <div style="background:#f8f9fa; border-radius:8px; padding:15px; flex:1; min-width:200px;">
            <div style="color:#666; font-size:0.85rem;">Purchase Requisition</div>
            <div style="font-size:1.2rem; font-weight:bold;">
                <i class="fa fa-file-text"></i> 
                <a href="index.php?page=pr_make&id=<?= $order['linked_pr_id'] ?>">
                    PR #<?= $order['linked_pr_id'] ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($order['linked_po_id']): ?>
        <div style="background:#f8f9fa; border-radius:8px; padding:15px; flex:1; min-width:200px;">
            <div style="color:#666; font-size:0.85rem;">Purchase Order / Quotation</div>
            <div style="font-size:1.2rem; font-weight:bold;">
                <i class="fa fa-file-text-o"></i> 
                <a href="index.php?page=po_view&id=<?= $order['linked_po_id'] ?>">
                    PO #<?= $order['linked_po_id'] ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Notes & Error -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px;">
    <?php if ($order['notes']): ?>
    <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <h4 style="margin-bottom:10px;"><i class="fa fa-sticky-note"></i> Notes</h4>
        <p style="color:#555; white-space:pre-line;"><?= htmlspecialchars($order['notes']) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($order['error_message']): ?>
    <div style="background:#fff5f5; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #fed7d7;">
        <h4 style="margin-bottom:10px; color:#e53e3e;"><i class="fa fa-exclamation-triangle"></i> Error</h4>
        <pre style="background:#fff; padding:10px; border-radius:6px; font-size:0.85rem; color:#c53030; overflow-x:auto;"><?= htmlspecialchars($order['error_message']) ?></pre>
    </div>
    <?php endif; ?>
</div>

<!-- Raw Data -->
<?php if ($order['raw_data']): ?>
<div style="background:white; border-radius:12px; padding:20px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:10px;"><i class="fa fa-code"></i> Raw Request Data</h4>
    <pre style="background:#f8f9fa; padding:15px; border-radius:8px; font-size:0.85rem; overflow-x:auto; max-height:300px;"><code><?= htmlspecialchars(json_encode(json_decode($order['raw_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
</div>
<?php endif; ?>

<!-- Processing Timeline -->
<div style="background:white; border-radius:12px; padding:20px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-clock-o"></i> Timeline</h4>
    <div style="position:relative; padding-left:30px;">
        <?php
        $events = [];
        $events[] = ['time' => $order['created_at'], 'label' => 'Order Created', 'icon' => 'plus-circle', 'color' => '#3498db'];
        
        if ($order['status'] === 'completed' && $order['processed_at']) {
            $events[] = ['time' => $order['processed_at'], 'label' => 'Processing Completed', 'icon' => 'check-circle', 'color' => '#27ae60'];
        } elseif ($order['status'] === 'failed' && $order['processed_at']) {
            $events[] = ['time' => $order['processed_at'], 'label' => 'Processing Failed', 'icon' => 'times-circle', 'color' => '#e74c3c'];
        } elseif ($order['status'] === 'cancelled') {
            $events[] = ['time' => $order['updated_at'], 'label' => 'Order Cancelled', 'icon' => 'ban', 'color' => '#95a5a6'];
        }

        foreach ($events as $i => $evt):
        ?>
        <div style="position:relative; padding-bottom:<?= $i < count($events) - 1 ? '25px' : '0' ?>; border-left:<?= $i < count($events) - 1 ? '2px solid #e9ecef' : 'none' ?>; margin-left:6px; padding-left:20px;">
            <div style="position:absolute; left:-8px; top:2px;">
                <i class="fa fa-<?= $evt['icon'] ?>" style="color:<?= $evt['color'] ?>; font-size:1.1rem; background:white; padding:2px;"></i>
            </div>
            <div>
                <strong><?= $evt['label'] ?></strong>
                <div style="color:#999; font-size:0.85rem;"><?= date('M d, Y H:i:s', strtotime($evt['time'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div>
