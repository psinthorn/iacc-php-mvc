<?php
/**
 * API Keys Management View
 * 
 * Variables from AdminApiController::keys():
 *   $subscription, $keys
 */
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-key"></i> API Keys</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<?php if (!$subscription): ?>
<div style="text-align:center; padding:60px 20px;">
    <i class="fa fa-lock" style="font-size:3rem; color:#999; margin-bottom:15px;"></i>
    <h4>No Active Subscription</h4>
    <p style="color:#666;">Activate your Booking API trial to get started.</p>
    <a href="index.php?page=api_dashboard" class="btn btn-primary">Go to Dashboard</a>
</div>

<?php else: ?>

<?php if (isset($_SESSION['rotated_key'])): ?>
<div class="alert alert-success" style="border-radius:8px; position:relative;">
    <button type="button" class="close" data-dismiss="alert" style="position:absolute; right:10px; top:8px;">&times;</button>
    <h5 style="margin-top:0;"><i class="fa fa-refresh"></i> Key Rotated Successfully</h5>
    <p style="margin-bottom:8px;">Save your new credentials now — the secret will not be shown again.</p>
    <div style="background:#f0fff0; padding:12px; border-radius:6px; font-family:monospace; font-size:0.85rem;">
        <strong>New API Key:</strong> <code id="rotated-key"><?= htmlspecialchars($_SESSION['rotated_key']['api_key']) ?></code>
        <button class="btn btn-xs btn-link" onclick="copyToClipboard('rotated-key')" title="Copy"><i class="fa fa-copy"></i></button><br>
        <strong>New Secret:</strong> <code id="rotated-secret"><?= htmlspecialchars($_SESSION['rotated_key']['api_secret']) ?></code>
        <button class="btn btn-xs btn-link" onclick="copyToClipboard('rotated-secret')" title="Copy"><i class="fa fa-copy"></i></button>
    </div>
    <p style="margin-top:8px; margin-bottom:0; font-size:0.85rem; color:#666;"><i class="fa fa-clock-o"></i> Old credentials will remain valid for <strong><?= $_SESSION['rotated_key']['grace_hours'] ?? 24 ?> hours</strong> (grace period).</p>
</div>
<?php unset($_SESSION['rotated_key']); ?>
<?php endif; ?>

<!-- Subscription Summary -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-bookmark stat-icon"></i>
        <div class="stat-value" style="text-transform:capitalize;"><?= $subscription['plan'] ?></div>
        <div class="stat-label">Plan</div>
    </div>
    <div class="stat-card info">
        <i class="fa fa-key stat-icon"></i>
        <div class="stat-value"><?= count(array_filter($keys, fn($k) => $k['is_active'])) ?> / <?= $subscription['keys_limit'] ?></div>
        <div class="stat-label">Active Keys</div>
    </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'key_limit'): ?>
<div class="alert alert-warning" style="border-radius:8px;">
    <i class="fa fa-exclamation-triangle"></i> You've reached the API key limit for your plan (<?= $subscription['keys_limit'] ?> keys). 
    Upgrade your plan to add more keys.
</div>
<?php endif; ?>

<!-- Create New Key -->
<?php
$activeCount = count(array_filter($keys, fn($k) => $k['is_active']));
if ($activeCount < $subscription['keys_limit']):
?>
<div style="background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-plus-circle"></i> Generate New API Key</h4>
    <form method="post" action="index.php?page=api_key_create" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div>
            <label style="font-size:0.85rem; color:#666;">Key Name</label>
            <input type="text" name="key_name" class="form-control" value="" placeholder="e.g. Production Website" style="min-width:250px;">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-key"></i> Generate Key</button>
    </form>
</div>
<?php endif; ?>

<!-- Keys List -->
<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-list"></i> Your API Keys</h4>
    
    <?php if (empty($keys)): ?>
        <p style="color:#999; text-align:center; padding:30px;">No API keys yet. Generate your first key above.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>API Key</th>
                    <th>Secret</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($keys as $key): ?>
                <tr style="<?= !$key['is_active'] ? 'opacity:0.5;' : '' ?>">
                    <td><strong><?= htmlspecialchars($key['key_name']) ?></strong></td>
                    <td>
                        <code style="font-size:0.8rem; background:#f8f9fa; padding:3px 8px; border-radius:4px;"
                              id="key-<?= $key['id'] ?>"><?= htmlspecialchars($key['api_key']) ?></code>
                        <button class="btn btn-xs btn-link" onclick="copyToClipboard('key-<?= $key['id'] ?>')" title="Copy">
                            <i class="fa fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        <code style="font-size:0.8rem; background:#f8f9fa; padding:3px 8px; border-radius:4px;"
                              id="secret-<?= $key['id'] ?>"><?= \App\Models\ApiKey::maskSecret($key['api_secret']) ?></code>
                    </td>
                    <td>
                        <?php if ($key['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Revoked</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $key['last_used_at'] ? date('M d, H:i', strtotime($key['last_used_at'])) : 'Never' ?></td>
                    <td><?= date('M d, Y', strtotime($key['created_at'])) ?></td>
                    <td>
                        <?php if ($key['is_active']): ?>
                        <form method="post" action="index.php?page=api_key_rotate" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= $key['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-warning" onclick="return confirm('Rotate this key? New credentials will be generated. Old ones remain valid for 24 hours.')" title="Generate new key/secret">
                                <i class="fa fa-refresh"></i> Rotate
                            </button>
                        </form>
                        <form method="post" action="index.php?page=api_key_revoke" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= $key['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Revoke this key? API calls using it will stop working.')">
                                <i class="fa fa-ban"></i> Revoke
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Start Guide -->
<div style="background:white; border-radius:12px; padding:20px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;"><i class="fa fa-code"></i> Quick Start</h4>
    <p style="color:#666; font-size:0.9rem;">Send a POST request to create a booking:</p>
    <pre style="background:#2d2d2d; color:#f8f8f2; padding:15px; border-radius:8px; overflow-x:auto; font-size:0.85rem;"><code>curl -X POST <?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/api.php/v1/bookings \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "X-API-Secret: YOUR_API_SECRET" \
  -d '{
    "guest_name": "John Doe",
    "guest_email": "john@example.com",
    "check_in": "2026-04-01",
    "check_out": "2026-04-03",
    "room_type": "Deluxe Room",
    "guests": 2,
    "total_amount": 5000,
    "currency": "THB"
  }'</code></pre>
</div>
<?php endif; ?>
</div>

<script>
function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        navigator.clipboard.writeText(el.textContent).then(() => {
            const btn = el.nextElementSibling;
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-check" style="color:green;"></i>';
            setTimeout(() => btn.innerHTML = orig, 1500);
        });
    }
}
</script>
