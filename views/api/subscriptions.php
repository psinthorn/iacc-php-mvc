<?php
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
                    <th>Enabled</th>
                    <th>Expires</th>
                    <th>Created</th>
                    <th style="width:220px;">Actions</th>
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
                    <td><?= intval($sub['bookings_limit']) == 0 ? 'Unlimited' : number_format($sub['bookings_limit']) ?></td>
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
                        <form method="post" action="index.php?page=api_subscription_plan" style="display:inline-flex; gap:5px; align-items:center;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                            <select name="plan" class="form-control form-control-sm" style="width:140px;">
                                <?php foreach (['trial','starter','professional','enterprise'] as $p): ?>
                                <option value="<?= $p ?>" <?= $sub['plan'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Change</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

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
                    'trial' => ['limit' => 10, 'keys' => 1, 'channels' => 'website', 'ai' => '-'],
                    'starter' => ['limit' => 100, 'keys' => 2, 'channels' => 'website, email', 'ai' => '-'],
                    'professional' => ['limit' => 500, 'keys' => 5, 'channels' => 'website, email, line, facebook', 'ai' => 'openai, anthropic'],
                    'enterprise' => ['limit' => 0, 'keys' => 10, 'channels' => 'All', 'ai' => 'All']
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
