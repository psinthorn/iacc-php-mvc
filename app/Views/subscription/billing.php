<?php
$pageTitle = 'Billing & Subscription';
$e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');

$planColors = [
    'trial'        => ['#64748b', '#475569'],
    'starter'      => ['#0d9488', '#0f766e'],
    'professional' => ['#6366f1', '#4f46e5'],
    'enterprise'   => ['#8e44ad', '#6c3483'],
];
$currentPlan = $sub['plan'] ?? 'trial';
$currentColor = $planColors[$currentPlan] ?? $planColors['trial'];
?>
<style>
.billing-grid { display:grid; grid-template-columns:1fr 2fr; gap:24px; align-items:start; }
.card-b { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.05); }
.card-b-header { padding:18px 22px; border-bottom:1px solid #f1f5f9; font-weight:700; font-size:14px; color:#334155; display:flex; align-items:center; gap:8px; }
.card-b-body { padding:22px; }

/* Current plan card */
.plan-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:.03em; }
.plan-badge.active { background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4; }
.plan-badge.locked { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.plan-badge.trial  { background:#fef9e7; color:#d97706; border:1px solid #fde68a; }

.trial-bar { background:#f1f5f9; border-radius:8px; height:8px; margin:10px 0 4px; overflow:hidden; }
.trial-bar-fill { height:100%; border-radius:8px; transition:width .5s; }

/* Plan cards grid */
.plans-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
.plan-card { border-radius:12px; border:2px solid #e2e8f0; padding:20px; cursor:pointer; transition:all .2s; position:relative; }
.plan-card:hover { border-color:#6366f1; box-shadow:0 4px 16px rgba(99,102,241,.1); }
.plan-card.current { border-color:var(--plan-color,#0d9488); background:rgba(99,102,241,.03); }
.plan-card.recommended::after { content:'Most Popular'; position:absolute; top:-1px; right:16px; background:#6366f1; color:white; font-size:10px; font-weight:700; padding:3px 10px; border-radius:0 0 8px 8px; letter-spacing:.05em; }
.plan-card h5 { margin:0 0 4px; font-size:15px; font-weight:700; }
.plan-card .price { font-size:26px; font-weight:800; color:#334155; margin:10px 0 4px; }
.plan-card .price span { font-size:13px; font-weight:500; color:#94a3b8; }
.plan-card .feat-list { list-style:none; padding:0; margin:12px 0 16px; }
.plan-card .feat-list li { font-size:13px; color:#64748b; padding:3px 0; display:flex; gap:8px; }
.plan-card .feat-list li::before { content:'✓'; color:#0d9488; font-weight:700; flex-shrink:0; }
.plan-card .btn-upgrade { width:100%; border-radius:8px; padding:9px; font-weight:600; font-size:13px; }

@media(max-width:768px) {
    .billing-grid { grid-template-columns:1fr; }
    .plans-grid { grid-template-columns:1fr; }
}
</style>

<div class="container-fluid" style="padding:24px 20px;">
    <div class="page-header" style="margin-bottom:20px;">
        <h2 style="margin:0;"><i class="fa fa-credit-card" style="color:#6366f1;"></i> Billing &amp; Subscription</h2>
        <p class="text-muted" style="margin:4px 0 0;">Manage your plan, view payment history, and upgrade.</p>
    </div>

    <?php if (!empty($_SESSION['billing_error'])): ?>
    <div class="alert alert-danger"><?= $e($_SESSION['billing_error']) ?></div>
    <?php unset($_SESSION['billing_error']); endif; ?>

    <div class="billing-grid">

        <!-- ── Left: Current plan ── -->
        <div>
            <div class="card-b">
                <div class="card-b-header" style="background:linear-gradient(135deg,<?= $currentColor[0] ?>,<?= $currentColor[1] ?>);color:white;border-bottom:none;">
                    <i class="fa fa-star"></i> Current Plan
                </div>
                <div class="card-b-body">
                    <div style="text-align:center;padding:10px 0 16px;">
                        <div style="font-size:22px;font-weight:800;color:#334155;text-transform:capitalize;margin-bottom:6px;">
                            <?= $e(ucfirst($currentPlan)) ?>
                        </div>

                        <?php if ($isTrialExpired): ?>
                        <span class="plan-badge locked"><i class="fa fa-lock"></i> Trial Expired</span>
                        <?php elseif ($currentPlan === 'trial'): ?>
                        <span class="plan-badge trial"><i class="fa fa-clock-o"></i> Free Trial</span>
                        <?php elseif ($isActive): ?>
                        <span class="plan-badge active"><i class="fa fa-check-circle"></i> Active</span>
                        <?php else: ?>
                        <span class="plan-badge locked"><i class="fa fa-lock"></i> Inactive</span>
                        <?php endif; ?>

                        <?php if ($currentPlan === 'trial' && $trialDaysLeft !== null): ?>
                        <div style="margin-top:16px;">
                            <?php
                            $pct   = min(100, max(0, ($trialDaysLeft / 14) * 100));
                            $color = $trialDaysLeft <= 3 ? '#ef4444' : ($trialDaysLeft <= 7 ? '#f59e0b' : '#0d9488');
                            ?>
                            <div style="font-size:13px;color:#64748b;margin-bottom:4px;">
                                <strong style="color:<?= $color ?>;font-size:20px;"><?= $trialDaysLeft ?></strong>
                                day<?= $trialDaysLeft !== 1 ? 's' : '' ?> remaining
                            </div>
                            <div class="trial-bar">
                                <div class="trial-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                            </div>
                            <small class="text-muted">Trial ends <?= $sub['trial_end'] ? date('d M Y', strtotime($sub['trial_end'])) : '—' ?></small>
                        </div>
                        <?php elseif ($sub && $sub['expires_at']): ?>
                        <div style="margin-top:12px;font-size:13px;color:#64748b;">
                            Renews <strong><?= date('d M Y', strtotime($sub['expires_at'])) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Limits -->
                    <?php if ($sub): ?>
                    <div style="background:#f8fafc;border-radius:10px;padding:14px;font-size:13px;">
                        <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#64748b;">Bookings/month</span>
                            <strong><?= number_format($sub['orders_limit']) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9;">
                            <span style="color:#64748b;">API Keys</span>
                            <strong><?= intval($sub['keys_limit']) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:4px 0;">
                            <span style="color:#64748b;">Status</span>
                            <strong style="color:<?= $isActive ? '#0d9488' : '#dc2626' ?>;">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:16px;">
                        <a href="index.php?page=billing_history" class="btn btn-default btn-block" style="border-radius:8px;font-size:13px;">
                            <i class="fa fa-history"></i> Payment History
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right: Plan cards ── -->
        <div>
            <div class="card-b">
                <div class="card-b-header">
                    <i class="fa fa-arrow-up" style="color:#6366f1;"></i>
                    <?= $currentPlan === 'trial' ? 'Choose a Plan to Continue' : 'Change Plan' ?>
                </div>
                <div class="card-b-body">

                    <!-- Billing cycle toggle -->
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                        <span style="font-size:13px;color:#64748b;">Billing cycle:</span>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                            <input type="radio" name="cycle_toggle" value="monthly" checked onchange="setCycle(this.value)"> Monthly
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                            <input type="radio" name="cycle_toggle" value="annual" onchange="setCycle(this.value)">
                            Annual <span style="background:#dcfce7;color:#16a34a;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:700;margin-left:4px;">Save 20%</span>
                        </label>
                    </div>

                    <div class="plans-grid">
                    <?php foreach ($plans as $code => $plan):
                        if ($code === 'trial') continue;
                        $isCurrent = $code === $currentPlan;
                        $colors    = $planColors[$code] ?? ['#64748b','#475569'];
                    ?>
                    <div class="plan-card <?= $isCurrent ? 'current' : '' ?> <?= $code === 'professional' ? 'recommended' : '' ?>"
                         style="--plan-color:<?= $colors[0] ?>">
                        <h5 style="color:<?= $colors[0] ?>;"><?= $e($plan['name']) ?></h5>

                        <div class="price monthly-price">
                            ฿<?= number_format($plan['price_monthly']) ?><span>/mo</span>
                        </div>
                        <div class="price annual-price" style="display:none;">
                            ฿<?= number_format($plan['price_annual']) ?><span>/yr</span>
                        </div>

                        <ul class="feat-list">
                        <?php foreach (($plan['features'] ?? []) as $feat): ?>
                            <li><?= $e($feat) ?></li>
                        <?php endforeach; ?>
                        </ul>

                        <?php if ($isCurrent): ?>
                        <button class="btn btn-default btn-upgrade" disabled>Current Plan</button>
                        <?php else: ?>
                        <button class="btn btn-upgrade"
                                style="background:<?= $colors[0] ?>;color:white;border:none;"
                                onclick="openUpgrade('<?= $e($code) ?>', '<?= $e($plan['name']) ?>')">
                            Upgrade to <?= $e($plan['name']) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <p style="font-size:12px;color:#94a3b8;margin-top:16px;text-align:center;">
                        <i class="fa fa-lock"></i> Secure payment via PromptPay or bank transfer.
                        Questions? <a href="mailto:support@iacc.app" style="color:#6366f1;">Contact support</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;">
                <h4 class="modal-title" style="margin:0;font-size:16px;">
                    <i class="fa fa-arrow-up"></i> Upgrade Plan
                </h4>
                <button type="button" class="close" data-dismiss="modal" style="color:white;opacity:.8;">&times;</button>
            </div>
            <form method="POST" action="index.php?page=billing_upgrade">
                <?= csrf_field() ?>
                <input type="hidden" name="plan"  id="modalPlan">
                <input type="hidden" name="cycle" id="modalCycle" value="monthly">
                <div class="modal-body" style="padding:24px;">
                    <p style="font-size:14px;color:#334155;">You are upgrading to <strong id="modalPlanName"></strong>.</p>
                    <div style="background:#f8fafc;border-radius:10px;padding:16px;margin:16px 0;">
                        <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px;">
                            <span>Amount</span>
                            <strong id="modalAmount" style="font-size:18px;color:#6366f1;"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748b;">
                            <span>Billing cycle</span>
                            <span id="modalCycleLabel">Monthly</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;color:#475569;">Payment Method</label>
                        <select name="method" class="form-control" style="border-radius:8px;">
                            <option value="promptpay">PromptPay / QR Code</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="manual">Manual (contact us)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#6366f1;border-color:#6366f1;border-radius:8px;">
                        <i class="fa fa-check"></i> Confirm Upgrade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var currentCycle = 'monthly';
var planPrices = {
    <?php foreach ($plans as $code => $plan): if ($code === 'trial') continue; ?>
    '<?= $code ?>': { monthly: <?= floatval($plan['price_monthly']) ?>, annual: <?= floatval($plan['price_annual']) ?> },
    <?php endforeach; ?>
};

function setCycle(val) {
    currentCycle = val;
    document.querySelectorAll('.monthly-price').forEach(el => el.style.display = val === 'monthly' ? '' : 'none');
    document.querySelectorAll('.annual-price').forEach(el  => el.style.display = val === 'annual'  ? '' : 'none');
}

function openUpgrade(code, name) {
    var amount = currentCycle === 'annual' ? planPrices[code].annual : planPrices[code].monthly;
    document.getElementById('modalPlan').value   = code;
    document.getElementById('modalCycle').value  = currentCycle;
    document.getElementById('modalPlanName').textContent  = name;
    document.getElementById('modalAmount').textContent    = '฿' + amount.toLocaleString() + (currentCycle === 'annual' ? '/yr' : '/mo');
    document.getElementById('modalCycleLabel').textContent = currentCycle === 'annual' ? 'Annual' : 'Monthly';
    $('#upgradeModal').modal('show');
}
</script>
