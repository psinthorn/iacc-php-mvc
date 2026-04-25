<?php
$pageTitle = 'API — Subscriptions';

/**
 * API Subscriptions Management (Super Admin)
 * 
 * Variables from AdminApiController::subscriptions():
 *   $subscriptions, $plans
 */
?>
<link rel="stylesheet" href="css/master-data.css">

<div class="master-data-container">

<div class="master-data-header">
    <h2><i class="fa fa-id-card"></i> API Subscriptions</h2>
    <div>
        <a href="index.php?page=api_dashboard" class="btn btn-sm btn-outline-primary"><i class="fa fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <?php if (empty($subscriptions)): ?>
        <p style="color:#999; text-align:center; padding:30px;">No API subscriptions found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>Plan</th>
                    <th>Monthly Limit</th>
                    <th>Status</th>
                    <th>Sponsor</th>
                    <th>Enabled</th>
                    <th>Expires</th>
                    <th>Created</th>
                    <th style="width:260px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscriptions as $sub): ?>
                <tr>
                    <td>#<?= $sub['id'] ?></td>
                    <td><strong><?= htmlspecialchars($sub['company_name'] ?? 'Company #' . $sub['company_id']) ?></strong></td>
                    <td>
                        <?php
                        $planColors = ['trial' => 'secondary', 'starter' => 'info', 'professional' => 'primary', 'enterprise' => 'warning'];
                        ?>
                        <span class="badge badge-<?= $planColors[$sub['plan']] ?? 'secondary' ?>"><?= ucfirst($sub['plan']) ?></span>
                    </td>
                    <td><?= intval($sub['orders_limit']) == 0 ? 'Unlimited' : number_format($sub['orders_limit']) ?></td>
                    <td>
                        <?php if ($sub['status'] === 'active'): ?>
                            <span class="badge badge-success">Active</span>
                        <?php elseif ($sub['status'] === 'expired'): ?>
                            <span class="badge badge-danger">Expired</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?= ucfirst($sub['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($sub['sponsor_type'] === 'sponsor'): ?>
                            <span style="background:#fef9c3;color:#854d0e;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;">
                                <i class="fa fa-star"></i> Sponsor
                            </span>
                        <?php elseif ($sub['sponsor_type'] === 'adopter'): ?>
                            <span style="background:#ede9fe;color:#5b21b6;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;">
                                <i class="fa fa-heart"></i> Adopter
                            </span>
                        <?php else: ?>
                            <span style="color:#cbd5e1;font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="index.php?page=api_subscription_toggle" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                            <?php if ($sub['enabled']): ?>
                                <button type="submit" class="btn btn-sm btn-success" title="Click to disable">
                                    <i class="fa fa-check-circle"></i> ON
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Click to enable">
                                    <i class="fa fa-times-circle"></i> OFF
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td>
                        <?php if ($sub['expires_at']): ?>
                            <?php
                            $exp = strtotime($sub['expires_at']);
                            $isExpired = $exp < time();
                            ?>
                            <span style="color:<?= $isExpired ? '#dc3545' : '#333' ?>;">
                                <?= date('M d, Y', $exp) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#999;">Never</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($sub['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
                            <form method="post" action="index.php?page=api_subscription_plan" style="display:inline-flex;gap:5px;align-items:center;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                <select name="plan" class="form-control form-control-sm" style="width:130px;">
                                    <?php foreach (['trial','starter','professional','enterprise'] as $p): ?>
                                    <option value="<?= $p ?>" <?= $sub['plan'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Change</button>
                            </form>
                            <button class="btn btn-sm btn-warning"
                                    onclick="openExtend(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['company_name'] ?? 'Company #'.$sub['company_id'], ENT_QUOTES) ?>', '<?= $sub['plan'] === 'trial' ? ($sub['trial_end'] ?? '') : substr($sub['expires_at'] ?? '', 0, 10) ?>')"
                                    title="Override expiry date">
                                <i class="fa fa-calendar"></i> Extend
                            </button>
                            <button class="btn btn-sm <?= $sub['sponsor_type'] ? 'btn-success' : 'btn-default' ?>"
                                    onclick="openSponsor(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['company_name'] ?? 'Company #'.$sub['company_id'], ENT_QUOTES) ?>', '<?= $sub['sponsor_type'] ?? '' ?>', <?= $sub['show_on_landing'] ?? 0 ?>, '<?= htmlspecialchars(addslashes($sub['testimonial'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($sub['testimonial_contact'] ?? ''), ENT_QUOTES) ?>')"
                                    title="Set sponsor/adopter status">
                                <i class="fa fa-star"></i> Sponsor
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Extend / Override expiry modal ──────────────────────────── -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);border:none;">
                <h4 class="modal-title" style="margin:0;font-size:15px;color:white;">
                    <i class="fa fa-calendar"></i> Override Expiry Date
                </h4>
                <button type="button" class="close" data-dismiss="modal" style="color:white;opacity:.8;">&times;</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p style="font-size:14px;color:#334155;margin-bottom:16px;">
                    Company: <strong id="extCompany"></strong>
                </p>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">New Expiry Date</label>
                    <input type="date" id="extDate" class="form-control" style="border-radius:8px;">
                    <small class="text-muted">Sets <code>trial_end</code> (trial plans) or <code>expires_at</code> (paid plans). Also clears any lock and re-enables the subscription.</small>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Internal Note (optional)</label>
                    <input type="text" id="extNote" class="form-control" placeholder="e.g. Extended per customer request" style="border-radius:8px;">
                </div>
                <div id="extResult" style="display:none;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="btnExtend" style="border-radius:8px;">
                    <i class="fa fa-save"></i> Save New Date
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var extSubId = 0;
function openExtend(subId, company, currentDate) {
    extSubId = subId;
    document.getElementById('extCompany').textContent = company;
    document.getElementById('extDate').value = currentDate || '';
    document.getElementById('extNote').value = '';
    document.getElementById('extResult').style.display = 'none';
    $('#extendModal').modal('show');
}
document.getElementById('btnExtend').addEventListener('click', function () {
    var btn = this, newDate = document.getElementById('extDate').value;
    var note = document.getElementById('extNote').value;
    var result = document.getElementById('extResult');
    if (!newDate) { alert('Please select a date.'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    var params = new URLSearchParams();
    params.append('csrf_token',      '<?= $_SESSION['csrf_token'] ?? '' ?>');
    params.append('subscription_id', extSubId);
    params.append('new_date',        newDate);
    params.append('note',            note);
    fetch('index.php?page=api_subscription_extend', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        result.style.cssText = 'display:block;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;background:'
            + (data.success ? '#f0fdfa;border:1px solid #99f6e4;color:#0f766e' : '#fef2f2;border:1px solid #fecaca;color:#dc2626');
        result.innerHTML = '<i class="fa fa-' + (data.success ? 'check-circle' : 'times-circle') + '"></i> ' + data.message;
        if (data.success) setTimeout(function(){ location.reload(); }, 1200);
    })
    .catch(e => {
        result.style.cssText = 'display:block;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626';
        result.innerHTML = '<i class="fa fa-times-circle"></i> Request failed: ' + e.message;
    })
    .finally(function(){ btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Save New Date'; });
});
</script>

<!-- ── Sponsor / Adopter modal ──────────────────────────────────── -->
<div class="modal fade" id="sponsorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#8e44ad,#6c3483);border:none;">
                <h4 class="modal-title" style="margin:0;font-size:15px;color:white;">
                    <i class="fa fa-star"></i> Sponsor / Adopter Status
                </h4>
                <button type="button" class="close" data-dismiss="modal" style="color:white;opacity:.8;">&times;</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p style="font-size:14px;color:#334155;margin-bottom:16px;">Company: <strong id="sponCompany"></strong></p>

                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Type</label>
                    <select id="sponType" class="form-control" style="border-radius:8px;">
                        <option value="">None (regular customer)</option>
                        <option value="adopter">&#x1F49C; Adopter — adopted our open-source project</option>
                        <option value="sponsor">&#x2B50; Sponsor — financial sponsor</option>
                    </select>
                    <small class="text-muted">Adopters and sponsors get <strong>lifetime access</strong> — expiry date is ignored.</small>
                </div>

                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">
                        <input type="checkbox" id="sponShowLanding" style="margin-right:5px;">
                        Show on Landing Page
                    </label>
                    <small class="text-muted" style="display:block;margin-top:4px;">Display this company's logo and testimonial in the public-facing testimonials section.</small>
                </div>

                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Testimonial Quote</label>
                    <textarea id="sponTestimonial" class="form-control" rows="3" style="border-radius:8px;" placeholder="&quot;iACC transformed the way we manage our tours...&quot;"></textarea>
                </div>

                <div class="form-group">
                    <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Name &amp; Title</label>
                    <input type="text" id="sponContact" class="form-control" style="border-radius:8px;" placeholder="e.g. Jane Smith, Operations Director">
                </div>

                <div id="sponResult" style="display:none;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSponsor" style="background:#8e44ad;border-color:#6c3483;border-radius:8px;">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var sponSubId = 0;
function openSponsor(subId, company, type, showLanding, testimonial, contact) {
    sponSubId = subId;
    document.getElementById('sponCompany').textContent  = company;
    document.getElementById('sponType').value           = type || '';
    document.getElementById('sponShowLanding').checked  = !!showLanding;
    document.getElementById('sponTestimonial').value    = testimonial || '';
    document.getElementById('sponContact').value        = contact || '';
    document.getElementById('sponResult').style.display = 'none';
    $('#sponsorModal').modal('show');
}
document.getElementById('btnSponsor').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    var result = document.getElementById('sponResult');
    var params = new URLSearchParams();
    params.append('csrf_token',          '<?= $_SESSION['csrf_token'] ?? '' ?>');
    params.append('subscription_id',     sponSubId);
    params.append('sponsor_type',        document.getElementById('sponType').value);
    params.append('show_on_landing',     document.getElementById('sponShowLanding').checked ? '1' : '0');
    params.append('testimonial',         document.getElementById('sponTestimonial').value);
    params.append('testimonial_contact', document.getElementById('sponContact').value);
    fetch('index.php?page=api_subscription_sponsor', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        result.style.cssText = 'display:block;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;background:'
            + (data.success ? '#f0fdfa;border:1px solid #99f6e4;color:#0f766e' : '#fef2f2;border:1px solid #fecaca;color:#dc2626');
        result.innerHTML = '<i class="fa fa-' + (data.success ? 'check-circle' : 'times-circle') + '"></i> ' + data.message;
        if (data.success) setTimeout(function(){ location.reload(); }, 1200);
    })
    .catch(e => {
        result.style.cssText = 'display:block;border-radius:8px;padding:10px 14px;font-size:13px;margin-top:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626';
        result.innerHTML = '<i class="fa fa-times-circle"></i> Request failed: ' + e.message;
    })
    .finally(function(){ btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Save'; });
});
</script>

<!-- Plan Reference -->
<div style="margin-top:20px; background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-bottom:15px;">Plan Reference</h4>
    <div class="table-responsive">
        <table class="table table-bordered" style="margin:0; font-size:0.9rem;">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Monthly Limit</th>
                    <th>Max API Keys</th>
                    <th>Channels</th>
                    <th>AI Providers</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $planRef = [
                    'trial' => ['limit' => 50, 'keys' => 1, 'channels' => 'website', 'ai' => 'ollama'],
                    'starter' => ['limit' => 500, 'keys' => 3, 'channels' => 'website, email', 'ai' => 'ollama, openai'],
                    'professional' => ['limit' => 5000, 'keys' => 10, 'channels' => 'website, email, line, facebook, manual', 'ai' => 'ollama, openai, claude, gemini'],
                    'enterprise' => ['limit' => 0, 'keys' => 999, 'channels' => 'All', 'ai' => 'All']
                ];
                foreach ($planRef as $name => $info):
                ?>
                <tr>
                    <td><strong><?= ucfirst($name) ?></strong></td>
                    <td><?= $info['limit'] == 0 ? 'Unlimited' : $info['limit'] ?>/mo</td>
                    <td><?= $info['keys'] ?></td>
                    <td><?= $info['channels'] ?></td>
                    <td><?= $info['ai'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
