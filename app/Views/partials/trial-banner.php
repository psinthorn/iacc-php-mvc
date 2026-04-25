<?php
/**
 * Trial expiry banner — injected in index.php after auth for trial accounts.
 * Variables: $trialDaysLeft (int), $isTrialExpired (bool)
 */
if (!isset($trialDaysLeft)) return;
?>
<?php if ($isTrialExpired ?? false): ?>
<!-- ── Trial EXPIRED — hard lock ── -->
<div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.85);z-index:9999;display:flex;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:40px;max-width:480px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="width:72px;height:72px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px;color:#dc2626;">
            <i class="fa fa-lock"></i>
        </div>
        <h3 style="margin:0 0 8px;color:#334155;">Your trial has expired</h3>
        <p style="color:#64748b;line-height:1.7;margin-bottom:24px;">
            Your 14-day free trial ended. Upgrade to a paid plan to continue using iACC and keep all your data.
        </p>
        <a href="index.php?page=billing" style="display:inline-block;padding:12px 28px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;text-decoration:none;border-radius:9px;font-weight:700;font-size:15px;">
            <i class="fa fa-arrow-up"></i> View Plans &amp; Upgrade
        </a>
        <div style="margin-top:14px;">
            <a href="index.php?page=logout" style="font-size:12px;color:#94a3b8;">Sign out</a>
        </div>
    </div>
</div>

<?php elseif (($trialDaysLeft ?? 99) <= 7): ?>
<!-- ── Trial warning banner (≤7 days left) ── -->
<?php
$color = $trialDaysLeft <= 3 ? '#dc2626' : '#d97706';
$bg    = $trialDaysLeft <= 3 ? '#fef2f2' : '#fffbeb';
$border= $trialDaysLeft <= 3 ? '#fecaca' : '#fde68a';
$icon  = $trialDaysLeft <= 3 ? 'exclamation-triangle' : 'clock-o';
?>
<div style="background:<?= $bg ?>;border-bottom:2px solid <?= $border ?>;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:sticky;top:0;z-index:1000;">
    <div style="display:flex;align-items:center;gap:10px;">
        <i class="fa fa-<?= $icon ?>" style="color:<?= $color ?>;font-size:16px;"></i>
        <span style="font-size:13px;color:<?= $color ?>;font-weight:600;">
            <?php if ($trialDaysLeft === 0): ?>
                Your trial expires <strong>today</strong>.
            <?php else: ?>
                Your free trial expires in <strong><?= $trialDaysLeft ?> day<?= $trialDaysLeft !== 1 ? 's' : '' ?></strong>.
            <?php endif; ?>
            Upgrade to keep full access.
        </span>
    </div>
    <a href="index.php?page=billing" style="padding:6px 16px;background:<?= $color ?>;color:white;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;">
        <i class="fa fa-arrow-up"></i> Upgrade Now
    </a>
</div>
<?php endif; ?>
