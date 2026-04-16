<?php
/**
 * Module Manager - List View
 * Variables: $companies, $stats, $modules, $plans, $search, $xml
 */
?>
<div class="module-manager-page">

<!-- Page Header -->
<div class="master-data-header" data-theme="amber">
    <div class="header-content">
        <div class="header-title-section">
            <h2><i class="fa fa-cubes"></i> <?= $xml->modulemanager ?? 'Module Manager' ?></h2>
            <p><?= $xml->modulemanagerdesc ?? 'Enable/disable modules per company, manage plans and validity' ?></p>
        </div>
        <div class="header-stats">
            <div class="header-stat">
                <span class="stat-value"><?= intval($stats['total_companies']) ?></span>
                <span class="stat-label"><?= $xml->companies ?? 'Companies' ?></span>
            </div>
            <div class="header-stat">
                <span class="stat-value"><?= intval($stats['active_modules']) ?></span>
                <span class="stat-label"><?= $xml->activemodules ?? 'Active' ?></span>
            </div>
            <div class="header-stat">
                <span class="stat-value"><?= intval($stats['trial_count']) ?></span>
                <span class="stat-label"><?= $xml->trial ?? 'Trial' ?></span>
            </div>
            <div class="header-stat">
                <span class="stat-value"><?= intval($stats['expired_count']) ?></span>
                <span class="stat-label"><?= $xml->expired ?? 'Expired' ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Search -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-6">
        <form method="GET" class="input-group">
            <input type="hidden" name="page" value="module_manager">
            <input type="text" name="search" class="form-control" 
                   placeholder="<?= $xml->searchcompany ?? 'Search company...' ?>" 
                   value="<?= htmlspecialchars($search) ?>">
            <span class="input-group-btn">
                <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                <?php if ($search): ?>
                <a href="index.php?page=module_manager" class="btn btn-default"><i class="fa fa-times"></i></a>
                <?php endif; ?>
            </span>
        </form>
    </div>
</div>

<!-- Companies Table -->
<div class="panel panel-default">
    <div class="panel-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table table-striped table-hover" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th><?= $xml->companyname ?? 'Company' ?></th>
                        <?php foreach ($modules as $key => $mod): ?>
                        <th class="text-center" style="width: 180px;">
                            <i class="fa <?= $mod['icon'] ?>"></i> <?= $mod['name'] ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)): ?>
                    <tr>
                        <td colspan="<?= 2 + count($modules) ?>" class="text-center text-muted" style="padding: 40px;">
                            <i class="fa fa-building-o fa-2x"></i><br>
                            <?= $xml->nocompanyfound ?? 'No companies found' ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($companies as $company): ?>
                    <tr>
                        <td><?= intval($company['id']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($company['name_en'] ?: $company['name_th'] ?: '—') ?></strong>
                            <?php if ($company['email']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($company['email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($modules as $key => $mod): ?>
                        <?php $m = $company['modules'][$key] ?? null; ?>
                        <td class="text-center module-cell" data-company="<?= intval($company['id']) ?>" data-module="<?= htmlspecialchars($key) ?>">
                            <!-- Toggle Switch -->
                            <label class="module-toggle" title="<?= $m && $m['is_enabled'] ? 'Enabled' : 'Disabled' ?>">
                                <input type="checkbox" class="module-switch" 
                                       data-company="<?= intval($company['id']) ?>" 
                                       data-module="<?= htmlspecialchars($key) ?>"
                                       <?= $m && $m['is_enabled'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>

                            <?php if ($m): ?>
                            <!-- Plan Badge -->
                            <div style="margin-top: 4px;">
                                <?php
                                $planClass = match($m['plan']) {
                                    'trial' => 'label-warning',
                                    'basic' => 'label-info',
                                    'pro'   => 'label-success',
                                    'enterprise' => 'label-primary',
                                    default => 'label-default',
                                };
                                $isExpired = $m['valid_to'] && strtotime($m['valid_to']) < time();
                                ?>
                                <span class="label <?= $planClass ?>" style="font-size: 10px;"><?= htmlspecialchars($m['plan']) ?></span>
                                <?php if ($isExpired): ?>
                                <span class="label label-danger" style="font-size: 10px;">expired</span>
                                <?php endif; ?>
                            </div>
                            <!-- Edit Button -->
                            <button class="btn btn-xs btn-link edit-module-btn" 
                                    data-company="<?= intval($company['id']) ?>" 
                                    data-module="<?= htmlspecialchars($key) ?>"
                                    data-plan="<?= htmlspecialchars($m['plan']) ?>"
                                    data-usage-count="<?= intval($m['usage_count']) ?>"
                                    data-usage-limit="<?= $m['usage_limit'] ?? '' ?>"
                                    data-valid-from="<?= htmlspecialchars($m['valid_from'] ?? '') ?>"
                                    data-valid-to="<?= htmlspecialchars($m['valid_to'] ?? '') ?>"
                                    title="<?= $xml->editsettings ?? 'Edit settings' ?>">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /.module-manager-page -->

<!-- Edit Module Modal -->
<div class="modal fade" id="editModuleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-cog"></i> <?= $xml->modulesettings ?? 'Module Settings' ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_company_id">
                <input type="hidden" id="modal_module_key">

                <div class="form-group">
                    <label><?= $xml->plan ?? 'Plan' ?></label>
                    <select id="modal_plan" class="form-control">
                        <?php foreach ($plans as $p): ?>
                        <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= $xml->usagelimit ?? 'Usage Limit' ?> <small class="text-muted">(<?= $xml->emptyforunlimited ?? 'empty = unlimited' ?>)</small></label>
                    <input type="number" id="modal_usage_limit" class="form-control" min="0" placeholder="Unlimited">
                </div>

                <div class="form-group">
                    <label><?= $xml->usagecount ?? 'Current Usage' ?></label>
                    <input type="text" id="modal_usage_count" class="form-control" disabled>
                </div>

                <div class="form-group">
                    <label><?= $xml->validfrom ?? 'Valid From' ?></label>
                    <input type="date" id="modal_valid_from" class="form-control">
                </div>

                <div class="form-group">
                    <label><?= $xml->validto ?? 'Valid To' ?></label>
                    <input type="date" id="modal_valid_to" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= $xml->cancel ?? 'Cancel' ?></button>
                <button type="button" class="btn btn-primary" id="saveModuleBtn">
                    <i class="fa fa-save"></i> <?= $xml->save ?? 'Save' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.module-manager-page .module-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 22px;
    margin: 0;
    cursor: pointer;
}
.module-manager-page .module-toggle input { opacity: 0; width: 0; height: 0; }
.module-manager-page .toggle-slider {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #ccc;
    border-radius: 22px;
    transition: 0.3s;
}
.module-manager-page .toggle-slider:before {
    content: "";
    position: absolute;
    height: 16px; width: 16px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: 0.3s;
}
.module-manager-page .module-toggle input:checked + .toggle-slider { background: #5cb85c; }
.module-manager-page .module-toggle input:checked + .toggle-slider:before { transform: translateX(22px); }
.module-manager-page .module-cell { vertical-align: middle !important; }
</style>

<script>
(function() {
    var csrfToken = '<?= csrf_token() ?>';

    // Toggle module on/off
    document.querySelectorAll('.module-switch').forEach(function(sw) {
        sw.addEventListener('change', function() {
            var el = this;
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
                    alert(data.error || 'Failed');
                    el.checked = !el.checked;
                } else {
                    // Reload to update badges
                    location.reload();
                }
            })
            .catch(function() {
                alert('Network error');
                el.checked = !el.checked;
            });
        });
    });

    // Open edit modal
    document.querySelectorAll('.edit-module-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('modal_company_id').value = this.getAttribute('data-company');
            document.getElementById('modal_module_key').value = this.getAttribute('data-module');
            document.getElementById('modal_plan').value = this.getAttribute('data-plan');
            document.getElementById('modal_usage_limit').value = this.getAttribute('data-usage-limit');
            document.getElementById('modal_usage_count').value = this.getAttribute('data-usage-count');
            document.getElementById('modal_valid_from').value = this.getAttribute('data-valid-from');
            document.getElementById('modal_valid_to').value = this.getAttribute('data-valid-to');
            $('#editModuleModal').modal('show');
        });
    });

    // Save module settings
    document.getElementById('saveModuleBtn').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;

        var body = 'company_id=' + document.getElementById('modal_company_id').value
            + '&module_key=' + encodeURIComponent(document.getElementById('modal_module_key').value)
            + '&plan=' + encodeURIComponent(document.getElementById('modal_plan').value)
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
            if (data.success) {
                $('#editModuleModal').modal('hide');
                location.reload();
            } else {
                alert(data.error || 'Failed to save');
            }
        })
        .catch(function() {
            btn.disabled = false;
            alert('Network error');
        });
    });
})();
</script>
