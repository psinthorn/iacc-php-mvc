<?php
/**
 * Plan Upgrade View
 * 
 * Variables from AdminApiController::upgradePlan():
 *   $subscription, $plans
 */
$currentPlan = $subscription['plan'] ?? 'none';
$planOrder = ['trial' => 0, 'starter' => 1, 'professional' => 2, 'enterprise' => 3];
$currentLevel = $planOrder[$currentPlan] ?? -1;

$error = $_GET['error'] ?? '';
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-arrow-circle-up"></i> Upgrade Plan</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<?php if ($error === 'invalid_plan'): ?>
<div class="alert alert-danger" style="border-radius:8px;">
    <i class="fa fa-exclamation-triangle"></i> Invalid plan selected. Please choose from the options below.
</div>
<?php elseif ($error === 'not_upgrade'): ?>
<div class="alert alert-warning" style="border-radius:8px;">
    <i class="fa fa-info-circle"></i> You can only upgrade to a higher plan than your current one.
</div>
<?php endif; ?>

<?php if (!$subscription): ?>
<div style="text-align:center; padding:40px 20px; color:#999;">
    <i class="fa fa-rocket" style="font-size:3rem; margin-bottom:15px;"></i>
    <h4>No Active Subscription</h4>
    <p>Start your free trial first to access plan upgrades.</p>
    <a href="index.php?page=api_dashboard" class="btn btn-primary">Go to Dashboard</a>
</div>
<?php else: ?>

<!-- Current Plan Info -->
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:25px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin:0 0 10px;">
        <i class="fa fa-bookmark"></i> Current Plan: 
        <span style="text-transform:capitalize; color:#3498db;"><?= $currentPlan ?></span>
    </h4>
    <p style="color:#666; margin:0;">
        <?= number_format($subscription['orders_limit']) ?> orders/month • 
        <?= $subscription['keys_limit'] ?> API key<?= $subscription['keys_limit'] > 1 ? 's' : '' ?> • 
        Channels: <?= $subscription['channels'] ?>
    </p>
</div>

<!-- Plan Comparison -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:25px;">

    <?php 
    $planColors = ['trial' => '#95a5a6', 'starter' => '#3498db', 'professional' => '#8e44ad', 'enterprise' => '#e67e22'];
    $planIcons = ['trial' => 'fa-leaf', 'starter' => 'fa-rocket', 'professional' => 'fa-diamond', 'enterprise' => 'fa-building'];
    $planPrices = ['trial' => 'Free', 'starter' => '฿990/mo', 'professional' => '฿4,900/mo', 'enterprise' => '฿19,900/mo'];
    
    foreach ($plans as $planName => $config): 
        $isCurrentPlan = ($planName === $currentPlan);
        $isUpgrade = ($planOrder[$planName] ?? 0) > $currentLevel;
        $color = $planColors[$planName] ?? '#3498db';
        $icon = $planIcons[$planName] ?? 'fa-star';
    ?>
    <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,0.08); border-top:4px solid <?= $color ?>; position:relative; <?= $isCurrentPlan ? 'ring: 2px solid ' . $color . ';' : '' ?>">
        
        <?php if ($isCurrentPlan): ?>
        <div style="position:absolute; top:10px; right:10px;">
            <span class="badge badge-primary" style="font-size:0.75rem;">Current</span>
        </div>
        <?php endif; ?>

        <div style="text-align:center; margin-bottom:15px;">
            <i class="fa <?= $icon ?>" style="font-size:2rem; color:<?= $color ?>; margin-bottom:10px;"></i>
            <h3 style="margin:0; text-transform:capitalize; color:<?= $color ?>;"><?= $planName ?></h3>
            <div style="font-size:1.5rem; font-weight:bold; margin:10px 0;"><?= $planPrices[$planName] ?></div>
        </div>

        <ul style="list-style:none; padding:0; margin:0 0 20px;">
            <li style="padding:6px 0; border-bottom:1px solid #f0f0f0;">
                <i class="fa fa-check" style="color:#27ae60; width:20px;"></i>
                <strong><?= number_format($config['orders_limit']) ?></strong> orders/month
            </li>
            <li style="padding:6px 0; border-bottom:1px solid #f0f0f0;">
                <i class="fa fa-check" style="color:#27ae60; width:20px;"></i>
                <strong><?= $config['keys_limit'] ?></strong> API key<?= $config['keys_limit'] > 1 ? 's' : '' ?>
            </li>
            <li style="padding:6px 0; border-bottom:1px solid #f0f0f0;">
                <i class="fa fa-check" style="color:#27ae60; width:20px;"></i>
                <?= ucwords(str_replace(',', ', ', $config['channels'])) ?> channels
            </li>
            <li style="padding:6px 0; border-bottom:1px solid #f0f0f0;">
                <i class="fa fa-check" style="color:#27ae60; width:20px;"></i>
                AI: <?= strtoupper(str_replace(',', ', ', $config['ai_providers'])) ?>
            </li>
            <li style="padding:6px 0;">
                <i class="fa fa-check" style="color:#27ae60; width:20px;"></i>
                <?= $config['duration_days'] ?> day<?= $config['duration_days'] > 1 ? 's' : '' ?> validity
            </li>
        </ul>

        <div style="text-align:center;">
            <?php if ($isCurrentPlan): ?>
                <button class="btn btn-secondary btn-block" disabled style="border-radius:8px;">
                    <i class="fa fa-check"></i> Current Plan
                </button>
            <?php elseif ($isUpgrade): ?>
                <form method="post" action="index.php?page=api_request_upgrade" onsubmit="return confirm('Upgrade to <?= ucfirst($planName) ?>? Your new limits will be effective immediately.');">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="plan" value="<?= $planName ?>">
                    <button type="submit" class="btn btn-block" style="background:<?= $color ?>; color:white; border:none; border-radius:8px; padding:10px;">
                        <i class="fa fa-arrow-circle-up"></i> Upgrade to <?= ucfirst($planName) ?>
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-outline-secondary btn-block" disabled style="border-radius:8px;">
                    <i class="fa fa-minus-circle"></i> Lower Plan
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- FAQ -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-question-circle"></i> Frequently Asked Questions</h4>
    
    <div style="margin-bottom:15px;">
        <strong>When does my new plan start?</strong>
        <p style="color:#666; margin:5px 0 0;">Your new plan limits are applied immediately after upgrading.</p>
    </div>
    
    <div style="margin-bottom:15px;">
        <strong>Can I downgrade?</strong>
        <p style="color:#666; margin:5px 0 0;">Downgrades are handled by our support team. Please contact us for assistance.</p>
    </div>
    
    <div style="margin-bottom:15px;">
        <strong>What happens to my existing data?</strong>
        <p style="color:#666; margin:5px 0 0;">All your orders, webhooks, and API keys are preserved when upgrading. Nothing is lost.</p>
    </div>
    
    <div>
        <strong>What if I exceed my monthly quota?</strong>
        <p style="color:#666; margin:5px 0 0;">New orders will be rejected with a quota exceeded error. Existing orders are not affected.</p>
    </div>
</div>

<?php endif; ?>

</div>
