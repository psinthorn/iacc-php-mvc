<?php
/**
 * Profile View — MVC version
 * Variables: $user, $message, $messageType
 */
$xml = $xml ?? (object)[];

// Generate avatar initials
$initials = 'U';
if (!empty($user['name'])) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $initials .= strtoupper(substr($parts[1], 0, 1));
} elseif (!empty($user['email'])) {
    $initials = strtoupper(substr($user['email'], 0, 1));
}

// Role label
$roleLabels = [0 => ['label'=>'User','class'=>'success'], 1 => ['label'=>'Admin','class'=>'primary'], 2 => ['label'=>'Super Admin','class'=>'danger']];
$userRole = $roleLabels[$user['level']] ?? $roleLabels[0];

// Handle error params
$errMap = ['empty'=>'All password fields are required.','mismatch'=>'New passwords do not match.','short'=>'Password must be at least 6 characters.'];
$err = isset($_GET['err']) ? ($errMap[$_GET['err']] ?? '') : '';
if ($err) { $message = $err; $messageType = 'danger'; }
?>
<style>
.profile-container { max-width:900px; margin:0 auto; }
.profile-header { background:linear-gradient(135deg,#8e44ad,#9b59b6); color:#fff; padding:40px; border-radius:12px; margin-bottom:30px; display:flex; align-items:center; gap:30px; }
.profile-avatar { width:100px; height:100px; background:rgba(255,255,255,.2); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:700; border:4px solid rgba(255,255,255,.3); }
.profile-info h1 { font-size:28px; margin:0 0 8px; }
.profile-info p { margin:0; opacity:.9; font-size:16px; }
.profile-role { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; background:rgba(255,255,255,.2); margin-top:10px; }
.profile-cards { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:768px){ .profile-header{flex-direction:column;text-align:center;padding:30px 20px;} .profile-cards{grid-template-columns:1fr;} }
.profile-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); overflow:hidden; }
.profile-card-header { background:#f8f9fa; padding:15px 20px; border-bottom:1px solid #e9ecef; display:flex; align-items:center; gap:10px; }
.profile-card-header i { color:#8e44ad; font-size:18px; }
.profile-card-header h3 { margin:0; font-size:16px; font-weight:600; }
.profile-card-body { padding:20px; }
.form-group { margin-bottom:20px; }
.form-group:last-child { margin-bottom:0; }
.form-group label { display:block; margin-bottom:8px; font-weight:500; color:#333; font-size:14px; }
.form-control { width:100%; padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:14px; transition:border-color .3s,box-shadow .3s; }
.form-control:focus { outline:none; border-color:#8e44ad; box-shadow:0 0 0 3px rgba(142,68,173,.1); }
.form-control:disabled,.form-control[readonly]{ background:#f8f9fa; color:#6c757d; }
.btn-save { background:linear-gradient(135deg,#8e44ad,#9b59b6); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:600; cursor:pointer; transition:transform .2s,box-shadow .2s; width:100%; margin-top:10px; }
.btn-save:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(142,68,173,.3); }
.info-row { display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f0f0f0; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#6c757d; font-size:14px; }
.info-value { font-weight:500; color:#333; font-size:14px; }
.alert { padding:15px 20px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.password-toggle { position:relative; }
.password-toggle .toggle-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#6c757d; cursor:pointer; padding:5px; }
.password-toggle .toggle-btn:hover { color:#8e44ad; }
.password-toggle input { padding-right:45px; }
</style>

<div class="profile-container">
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fa fa-<?= $messageType==='success'?'check-circle':'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="profile-header">
        <div class="profile-avatar"><?= $initials ?></div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($user['name'] ?: $user['email']) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="profile-role"><?= $userRole['label'] ?></span>
        </div>
    </div>

    <div class="profile-cards">
        <!-- Personal Information -->
        <div class="profile-card">
            <div class="profile-card-header"><i class="fa fa-user"></i><h3><?= isset($xml->personal_info) ? $xml->personal_info : 'Personal Information' ?></h3></div>
            <div class="profile-card-body">
                <form method="POST" action="index.php?page=account_store">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label><?= isset($xml->fullname) ? $xml->fullname : 'Full Name' ?></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label><?= isset($xml->email) ? $xml->email : 'Email' ?></label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label><?= isset($xml->phone) ? $xml->phone : 'Phone' ?></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your phone number">
                    </div>
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> <?= isset($xml->save) ? $xml->save : 'Save Changes' ?></button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="profile-card">
            <div class="profile-card-header"><i class="fa fa-lock"></i><h3><?= isset($xml->change_password) ? $xml->change_password : 'Change Password' ?></h3></div>
            <div class="profile-card-body">
                <form method="POST" action="index.php?page=account_store">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label><?= isset($xml->current_password) ? $xml->current_password : 'Current Password' ?></label>
                        <div class="password-toggle">
                            <input type="password" name="current_password" class="form-control" id="currentPassword" required>
                            <button type="button" class="toggle-btn" onclick="togglePassword('currentPassword')"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= isset($xml->new_password) ? $xml->new_password : 'New Password' ?></label>
                        <div class="password-toggle">
                            <input type="password" name="new_password" class="form-control" id="newPassword" required minlength="6">
                            <button type="button" class="toggle-btn" onclick="togglePassword('newPassword')"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= isset($xml->confirm_password) ? $xml->confirm_password : 'Confirm New Password' ?></label>
                        <div class="password-toggle">
                            <input type="password" name="confirm_password" class="form-control" id="confirmPassword" required minlength="6">
                            <button type="button" class="toggle-btn" onclick="togglePassword('confirmPassword')"><i class="fa fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn-save"><i class="fa fa-key"></i> <?= isset($xml->update_password) ? $xml->update_password : 'Update Password' ?></button>
                </form>
            </div>
        </div>

        <!-- Account Information -->
        <div class="profile-card">
            <div class="profile-card-header"><i class="fa fa-info-circle"></i><h3><?= isset($xml->account_info) ? $xml->account_info : 'Account Information' ?></h3></div>
            <div class="profile-card-body">
                <div class="info-row">
                    <span class="info-label"><?= isset($xml->role) ? $xml->role : 'Role' ?></span>
                    <span class="info-value"><span class="label label-<?= $userRole['class'] ?>"><?= $userRole['label'] ?></span></span>
                </div>
                <?php if ($user['company_name']): ?>
                <div class="info-row">
                    <span class="info-label"><?= isset($xml->company) ? $xml->company : 'Company' ?></span>
                    <span class="info-value"><?= htmlspecialchars($user['company_name']) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label"><?= isset($xml->language) ? $xml->language : 'Language' ?></span>
                    <span class="info-value"><?= $user['lang'] == 1 ? 'ไทย' : 'English' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?= isset($xml->account_id) ? $xml->account_id : 'Account ID' ?></span>
                    <span class="info-value">#<?= $user['id'] ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="profile-card">
            <div class="profile-card-header"><i class="fa fa-link"></i><h3><?= isset($xml->quick_links) ? $xml->quick_links : 'Quick Links' ?></h3></div>
            <div class="profile-card-body">
                <div class="info-row"><a href="index.php?page=settings" style="color:#8e44ad;text-decoration:none;"><i class="fa fa-cog"></i> <?= isset($xml->settings) ? $xml->settings : 'Account Settings' ?></a></div>
                <div class="info-row"><a href="index.php?page=help" style="color:#8e44ad;text-decoration:none;"><i class="fa fa-question-circle"></i> <?= isset($xml->help) ? $xml->help : 'Help & Support' ?></a></div>
                <div class="info-row"><a href="index.php?page=dashboard" style="color:#8e44ad;text-decoration:none;"><i class="fa fa-tachometer"></i> <?= isset($xml->dashboard) ? $xml->dashboard : 'Dashboard' ?></a></div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id){var i=document.getElementById(id),ic=i.parentElement.querySelector('.toggle-btn i');if(i.type==='password'){i.type='text';ic.classList.remove('fa-eye');ic.classList.add('fa-eye-slash');}else{i.type='password';ic.classList.remove('fa-eye-slash');ic.classList.add('fa-eye');}}
</script>
