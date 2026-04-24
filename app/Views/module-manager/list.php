<?php
$pageTitle = 'Module Manager';

/**
 * Module Manager - List View (Card-Based Layout)
 * Variables: $companies, $stats, $totalCompanies, $modules, $plans, $search, $xml
 */
?>
<link rel="stylesheet" href="css/toast.css">
<link rel="stylesheet" href="css/master-data.css">

<div class="module-manager-page master-data-container">

<!-- Page Header -->
<div class="master-data-header" data-theme="amber">
    <div class="header-content">
        <div class="header-text">
            <h2><i class="fa fa-cubes"></i> <?= $xml->modulemanager ?? 'Module Manager' ?></h2>
            <p><?= $xml->modulemanagerdesc ?? 'Enable/disable modules per company, manage plans and validity' ?></p>
        </div>
        <div class="header-actions">
            <a href="index.php?page=api_subscriptions" class="btn-header btn-header-outline">
                <i class="fa fa-id-card"></i> <?= $xml->apisubscriptions ?? 'API Subscriptions' ?>
            </a>
        </div>
    </div>
</div>

<!-- Stats Row (uses master-data.css .stats-row / .stat-card pattern) -->
<div class="stats-row">
    <div class="stat-card primary">
        <i class="fa fa-building stat-icon"></i>
        <div class="stat-value"><?= intval($totalCompanies) ?></div>
        <div class="stat-label"><?= $xml->companies ?? 'Companies' ?></div>
    </div>
    <div class="stat-card success">
        <i class="fa fa-check-circle stat-icon"></i>
        <div class="stat-value"><?= intval($stats['active_modules']) ?></div>
        <div class="stat-label"><?= $xml->activemodules ?? 'Active' ?></div>
    </div>
    <div class="stat-card warning">
        <i class="fa fa-flask stat-icon"></i>
        <div class="stat-value"><?= intval($stats['trial_count']) ?></div>
        <div class="stat-label"><?= $xml->trial ?? 'Trial' ?></div>
    </div>
    <div class="stat-card danger">
        <i class="fa fa-clock-o stat-icon"></i>
        <div class="stat-value"><?= intval($stats['expired_count']) ?></div>
        <div class="stat-label"><?= $xml->expired ?? 'Expired' ?></div>
    </div>
</div>

<!-- Search -->
<div class="mm-search-bar">
    <form method="GET" action="index.php">
        <input type="hidden" name="page" value="module_manager">
        <div class="input-group">
            <span class="input-group-addon"><i class="fa fa-search"></i></span>
            <input type="text" name="search" class="form-control" 
                   placeholder="<?= $xml->searchcompany ?? 'Search company...' ?>" 
                   value="<?= htmlspecialchars($search) ?>">
            <?php if ($search): ?>
            <span class="input-group-btn">
                <a href="index.php?page=module_manager" class="btn btn-default"><i class="fa fa-times"></i></a>
            </span>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Company Cards -->
<?php if (empty($companies)): ?>
<div class="mm-empty-state">
    <i class="fa fa-building-o"></i>
    <p><?= $xml->nocompanyfound ?? 'No companies found' ?></p>
</div>
<?php endif; ?>

<?php foreach ($companies as $company): ?>
<div class="mm-company-card" id="company-<?= intval($company['id']) ?>">
    <div class="mm-card-header">
        <div class="mm-company-info">
            <h4><?= htmlspecialchars($company['name_en'] ?: $company['name_th'] ?: '—') ?></h4>
            <?php if ($company['email']): ?>
            <span class="mm-company-email"><?= htmlspecialchars($company['email']) ?></span>
            <?php endif; ?>
        </div>
        <span class="mm-company-id">#<?= intval($company['id']) ?></span>
    </div>
    <div class="mm-card-body">
        <?php foreach ($modules as $key => $mod): ?>
        <?php $m = $company['modules'][$key] ?? null; ?>
        <?php
            $isEnabled = $m && $m['is_enabled'];
            $isExpired = $m && $m['valid_to'] && strtotime($m['valid_to']) < time();
            $planClass = '';
            if ($m) {
                $planClass = match($m['plan']) {
                    'trial'      => 'mm-plan-trial',
                    'basic'      => 'mm-plan-basic',
                    'pro'        => 'mm-plan-pro',
                    'enterprise' => 'mm-plan-enterprise',
                    default      => 'mm-plan-default',
                };
            }
        ?>
        <div class="mm-module-row <?= $isEnabled ? 'mm-module-active' : 'mm-module-inactive' ?>" 
             data-company="<?= intval($company['id']) ?>" data-module="<?= htmlspecialchars($key) ?>">
            <!-- Module Identity -->
            <div class="mm-module-identity">
                <div class="mm-module-icon" style="background:<?= $isEnabled ? $mod['color'] : '#cbd5e1' ?>;">
                    <i class="fa <?= $mod['icon'] ?>"></i>
                </div>
                <div class="mm-module-name">
                    <strong><?= $mod['name'] ?></strong>
                    <small><?= $mod['description'] ?></small>
                </div>
            </div>

            <!-- Toggle -->
            <div class="mm-module-toggle-wrap">
                <label class="mm-toggle">
                    <input type="checkbox" class="module-switch" 
                           aria-label="Toggle <?= $mod['name'] ?>"
                           data-company="<?= intval($company['id']) ?>" 
                           data-module="<?= htmlspecialchars($key) ?>"
                           <?= $isEnabled ? 'checked' : '' ?>>
                    <span class="mm-toggle-slider"></span>
                </label>
            </div>

            <!-- Status & Actions -->
            <div class="mm-module-meta">
                <?php if ($m): ?>
                <div class="mm-plan-badges">
                    <span class="mm-plan-badge <?= $planClass ?>"><?= ucfirst($m['plan']) ?></span>
                    <?php if ($isExpired): ?>
                    <span class="mm-plan-badge mm-plan-expired"><?= $xml->expired ?? 'Expired' ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($m['valid_to']): ?>
                <div class="mm-validity">
                    <i class="fa fa-calendar-o"></i>
                    <?php if ($m['valid_from']): ?>
                        <?= date('M j', strtotime($m['valid_from'])) ?> —
                    <?php endif; ?>
                    <?= date('M j, Y', strtotime($m['valid_to'])) ?>
                </div>
                <?php endif; ?>
                <?php if ($m['usage_limit']): ?>
                <div class="mm-usage-bar-wrap">
                    <?php $usagePct = min(100, intval($m['usage_count']) / max(1, intval($m['usage_limit'])) * 100); ?>
                    <div class="mm-usage-bar">
                        <div class="mm-usage-fill" style="width:<?= $usagePct ?>%;background:<?= $usagePct > 80 ? '#ef4444' : ($usagePct > 60 ? '#f59e0b' : '#10b981') ?>;"></div>
                    </div>
                    <span class="mm-usage-text"><?= intval($m['usage_count']) ?>/<?= intval($m['usage_limit']) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="mm-module-actions">
                <?php if ($m): ?>
                <button class="btn btn-xs btn-default edit-module-btn" 
                        data-company="<?= intval($company['id']) ?>"
                        data-company-name="<?= htmlspecialchars($company['name_en'] ?: $company['name_th'] ?: '#' . $company['id']) ?>"
                        data-module="<?= htmlspecialchars($key) ?>"
                        data-module-name="<?= htmlspecialchars($mod['name']) ?>"
                        data-module-color="<?= $mod['color'] ?>"
                        data-module-icon="<?= $mod['icon'] ?>"
                        data-plan="<?= htmlspecialchars($m['plan']) ?>"
                        data-usage-count="<?= intval($m['usage_count']) ?>"
                        data-usage-limit="<?= $m['usage_limit'] ?? '' ?>"
                        data-valid-from="<?= htmlspecialchars($m['valid_from'] ?? '') ?>"
                        data-valid-to="<?= htmlspecialchars($m['valid_to'] ?? '') ?>"
                        title="<?= $xml->editsettings ?? 'Edit settings' ?>">
                    <i class="fa fa-pencil"></i>
                </button>
                <?php endif; ?>
                <?php if ($isEnabled): ?>
                <a href="<?= $mod['manage_url'] ?>" class="btn btn-xs btn-default mm-manage-link" title="<?= $mod['name'] ?>">
                    <?= $xml->manage ?? 'Manage' ?> <i class="fa fa-external-link"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</div><!-- /.module-manager-page -->

<!-- Edit Module Modal -->
<div class="modal fade" id="editModuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mm-modal-header">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:0.8;">&times;</button>
                <h4 class="modal-title">
                    <span id="modal_icon_wrap" class="mm-modal-icon"><i id="modal_icon" class="fa fa-cog"></i></span>
                    <span id="modal_module_name">Module</span>
                    <small id="modal_company_name" style="display:block;font-size:13px;opacity:0.85;margin-top:2px;"></small>
                </h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_company_id">
                <input type="hidden" id="modal_module_key">

                <!-- Plan Selector (Pills) -->
                <div class="form-group">
                    <label><?= $xml->plan ?? 'Plan' ?></label>
                    <div class="mm-plan-selector" id="modal_plan_selector">
                        <?php foreach ($plans as $p): ?>
                        <label class="mm-plan-pill">
                            <input type="radio" name="modal_plan" value="<?= $p ?>">
                            <span><?= ucfirst($p) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $xml->usagelimit ?? 'Usage Limit' ?></label>
                            <input type="number" id="modal_usage_limit" class="form-control" min="0" placeholder="<?= $xml->emptyforunlimited ?? 'Unlimited' ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $xml->usagecount ?? 'Current Usage' ?></label>
                            <div class="mm-modal-usage-display">
                                <div class="mm-usage-bar" style="height:8px;">
                                    <div class="mm-usage-fill" id="modal_usage_bar" style="width:0%;background:#10b981;"></div>
                                </div>
                                <span id="modal_usage_count" class="mm-usage-text">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar"></i> <?= $xml->validfrom ?? 'Valid From' ?></label>
                            <input type="date" id="modal_valid_from" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar"></i> <?= $xml->validto ?? 'Valid To' ?></label>
                            <input type="date" id="modal_valid_to" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $xml->cancel ?? 'Cancel' ?></button>
                <button type="button" class="btn btn-primary" id="saveModuleBtn">
                    <i class="fa fa-save" id="saveBtnIcon"></i> <span id="saveBtnText"><?= $xml->save ?? 'Save' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ========== Module Manager — Scoped Styles ========== */
.module-manager-page { max-width: 1400px; }

/* Search */
.mm-search-bar { margin-bottom: 20px; max-width: 420px; }
.mm-search-bar .form-control {
    border-radius: 0 8px 8px 0;
    height: 44px; min-height: 44px;
    padding: 10px 14px; font-size: 14px;
    border: 1px solid #e2e8f0;
}
.mm-search-bar .form-control:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
}
.mm-search-bar .input-group-addon {
    background: #fff; border-radius: 8px 0 0 8px;
    color: #94a3b8; border: 1px solid #e2e8f0; border-right: none;
    height: 44px; line-height: 44px; padding: 0 12px;
}
.mm-search-bar .btn { height: 44px; }

/* Empty State */
.mm-empty-state {
    text-align: center; padding: 60px 20px; color: #94a3b8;
}
.mm-empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }

/* Company Cards */
.mm-company-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.mm-company-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }

.mm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}
.mm-company-info h4 { margin: 0; font-size: 15px; font-weight: 600; color: #1e293b; }
.mm-company-email { font-size: 12px; color: #94a3b8; }
.mm-company-id {
    font-size: 12px; font-weight: 600; color: #94a3b8;
    background: #f1f5f9; padding: 3px 10px; border-radius: 20px;
}

.mm-card-body { padding: 0; }

/* Module Rows */
.mm-module-row {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    gap: 16px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s;
}
.mm-module-row:last-child { border-bottom: none; }
.mm-module-row:hover { background: #fafbfc; }
.mm-module-inactive { opacity: 0.55; }
.mm-module-inactive:hover { opacity: 0.75; }

.mm-module-identity {
    display: flex; align-items: center; gap: 12px;
    min-width: 220px; flex-shrink: 0;
}
.mm-module-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 15px; flex-shrink: 0;
    transition: background 0.2s;
}
.mm-module-name strong { display: block; font-size: 13px; color: #1e293b; }
.mm-module-name small { font-size: 11px; color: #94a3b8; }

/* Toggle */
.mm-module-toggle-wrap { flex-shrink: 0; }
.mm-toggle {
    position: relative; display: inline-block;
    width: 44px; height: 22px; margin: 0; cursor: pointer;
}
.mm-toggle input { opacity: 0; width: 0; height: 0; }
.mm-toggle-slider {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: #cbd5e1; border-radius: 22px; transition: 0.25s;
}
.mm-toggle-slider:before {
    content: ""; position: absolute;
    height: 16px; width: 16px; left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%; transition: 0.25s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.mm-toggle input:checked + .mm-toggle-slider { background: #10b981; }
.mm-toggle input:checked + .mm-toggle-slider:before { transform: translateX(22px); }

/* Meta (plan, dates, usage) */
.mm-module-meta { flex: 1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; min-width: 0; }
.mm-plan-badges { display: flex; gap: 4px; }
.mm-plan-badge {
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 10px; text-transform: uppercase; letter-spacing: 0.3px;
}
.mm-plan-trial { background: #fef3c7; color: #92400e; }
.mm-plan-basic { background: #dbeafe; color: #1e40af; }
.mm-plan-pro { background: #d1fae5; color: #065f46; }
.mm-plan-enterprise { background: #ede9fe; color: #5b21b6; }
.mm-plan-default { background: #f1f5f9; color: #64748b; }
.mm-plan-expired { background: #fee2e2; color: #991b1b; }

.mm-validity { font-size: 11px; color: #94a3b8; white-space: nowrap; }

.mm-usage-bar-wrap { display: flex; align-items: center; gap: 6px; }
.mm-usage-bar {
    width: 60px; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;
}
.mm-usage-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
.mm-usage-text { font-size: 10px; color: #64748b; white-space: nowrap; }

/* Actions */
.mm-module-actions { display: flex; gap: 6px; flex-shrink: 0; align-items: center; }
.mm-manage-link {
    font-size: 11px; font-weight: 600;
    border-radius: 6px;
}

/* Modal */
.mm-modal-header {
    background: linear-gradient(135deg, #4338ca, #3b82f6);
    color: #fff; border-bottom: none; border-radius: 6px 6px 0 0;
    padding: 16px 20px;
}
.mm-modal-header .modal-title { font-size: 16px; font-weight: 600; }
.mm-modal-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    background: rgba(255,255,255,0.2); margin-right: 8px; font-size: 14px;
}

/* Modal form inputs — match tour agent form pattern (44px, 10px radius) */
#editModuleModal .form-group label {
    display: block; font-size: 13px; font-weight: 600;
    color: #374151; margin-bottom: 6px;
}
#editModuleModal .form-control {
    height: 44px; min-height: 44px;
    padding: 10px 14px; font-size: 14px;
    border: 1px solid #e2e8f0; border-radius: 10px;
    box-sizing: border-box;
}
#editModuleModal .form-control:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1);
}
#editModuleModal .modal-footer .btn {
    padding: 10px 28px; border-radius: 10px; font-size: 14px; font-weight: 600;
}
#editModuleModal .modal-footer .btn-primary {
    background: #d97706; border-color: #b45309;
}
#editModuleModal .modal-footer .btn-primary:hover {
    background: #b45309;
}
#editModuleModal .modal-footer .btn-default {
    background: #f1f5f9; color: #64748b; border-color: #e2e8f0;
}
#editModuleModal .modal-content { border-radius: 14px; border: none; }

/* Plan Pill Selector */
.mm-plan-selector { display: flex; gap: 6px; flex-wrap: wrap; }
.mm-plan-pill {
    cursor: pointer; margin: 0;
}
.mm-plan-pill input { display: none; }
.mm-plan-pill span {
    display: inline-block; padding: 6px 16px;
    border: 2px solid #e2e8f0; border-radius: 8px;
    font-size: 13px; font-weight: 500; color: #64748b;
    transition: all 0.15s; user-select: none;
}
.mm-plan-pill input:checked + span {
    border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; font-weight: 600;
}
.mm-plan-pill:hover span { border-color: #93c5fd; }

.mm-modal-usage-display {
    padding-top: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .mm-stats-row { grid-template-columns: repeat(2, 1fr); }
    .mm-module-row { flex-wrap: wrap; gap: 10px; }
    .mm-module-identity { min-width: 100%; }
    .mm-module-meta { min-width: 100%; }
    .mm-module-actions { width: 100%; justify-content: flex-end; }
    .mm-search-bar { max-width: 100%; }
}
</style>

<script src="js/toast-notifications.js"></script>
<script>
(function() {
    var csrfToken = '<?= csrf_token() ?>';

    // Toggle module on/off
    document.querySelectorAll('.module-switch').forEach(function(sw) {
        sw.addEventListener('change', function() {
            var el = this;
            var row = el.closest('.mm-module-row');
            var companyId = el.getAttribute('data-company');
            var moduleKey = el.getAttribute('data-module');

            fetch('index.php?page=module_manager_toggle', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'company_id=' + companyId + '&module_key=' + encodeURIComponent(moduleKey) + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    Toast.error(data.error || 'Failed to toggle module');
                    el.checked = !el.checked;
                } else {
                    var modName = row.querySelector('.mm-module-name strong').textContent;
                    if (data.is_enabled) {
                        Toast.success(modName + ' enabled');
                        row.classList.remove('mm-module-inactive');
                        row.classList.add('mm-module-active');
                    } else {
                        Toast.info(modName + ' disabled');
                        row.classList.add('mm-module-inactive');
                        row.classList.remove('mm-module-active');
                    }
                    // Reload after short delay to update badges/actions
                    setTimeout(function() { location.reload(); }, 800);
                }
            })
            .catch(function() {
                Toast.error('Network error');
                el.checked = !el.checked;
            });
        });
    });

    // Open edit modal
    document.querySelectorAll('.edit-module-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var d = this.dataset;
            document.getElementById('modal_company_id').value = d.company;
            document.getElementById('modal_module_key').value = d.module;
            document.getElementById('modal_company_name').textContent = 'for ' + d.companyName;
            document.getElementById('modal_module_name').textContent = d.moduleName;
            document.getElementById('modal_icon').className = 'fa ' + d.moduleIcon;
            document.getElementById('modal_icon_wrap').style.background = d.moduleColor + '44';

            // Set header color
            document.querySelector('.mm-modal-header').style.background = 
                'linear-gradient(135deg, ' + d.moduleColor + ', ' + d.moduleColor + 'cc)';

            // Plan pills
            document.querySelectorAll('#modal_plan_selector input').forEach(function(r) {
                r.checked = (r.value === d.plan);
            });

            document.getElementById('modal_usage_limit').value = d.usageLimit;
            document.getElementById('modal_valid_from').value = d.validFrom;
            document.getElementById('modal_valid_to').value = d.validTo;

            // Usage display
            var count = parseInt(d.usageCount) || 0;
            var limit = parseInt(d.usageLimit) || 0;
            document.getElementById('modal_usage_count').textContent = limit > 0 ? count + ' / ' + limit : count + ' (no limit)';
            var pct = limit > 0 ? Math.min(100, (count / limit) * 100) : 0;
            document.getElementById('modal_usage_bar').style.width = pct + '%';
            document.getElementById('modal_usage_bar').style.background = pct > 80 ? '#ef4444' : (pct > 60 ? '#f59e0b' : '#10b981');

            $('#editModuleModal').modal('show');
        });
    });

    // Save module settings
    document.getElementById('saveModuleBtn').addEventListener('click', function() {
        var btn = this;
        var icon = document.getElementById('saveBtnIcon');
        var text = document.getElementById('saveBtnText');
        btn.disabled = true;
        icon.className = 'fa fa-spinner fa-spin';
        text.textContent = 'Saving...';

        var selectedPlan = document.querySelector('#modal_plan_selector input:checked');

        var body = 'company_id=' + document.getElementById('modal_company_id').value
            + '&module_key=' + encodeURIComponent(document.getElementById('modal_module_key').value)
            + '&plan=' + encodeURIComponent(selectedPlan ? selectedPlan.value : '')
            + '&usage_limit=' + encodeURIComponent(document.getElementById('modal_usage_limit').value)
            + '&valid_from=' + encodeURIComponent(document.getElementById('modal_valid_from').value)
            + '&valid_to=' + encodeURIComponent(document.getElementById('modal_valid_to').value)
            + '&csrf_token=' + encodeURIComponent(csrfToken);

        fetch('index.php?page=module_manager_update', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            icon.className = 'fa fa-save';
            text.textContent = '<?= $xml->save ?? "Save" ?>';
            if (data.success) {
                $('#editModuleModal').modal('hide');
                Toast.success('Module settings saved');
                setTimeout(function() { location.reload(); }, 600);
            } else {
                Toast.error(data.error || 'Failed to save');
            }
        })
        .catch(function() {
            btn.disabled = false;
            icon.className = 'fa fa-save';
            text.textContent = '<?= $xml->save ?? "Save" ?>';
            Toast.error('Network error');
        });
    });
})();
</script>
