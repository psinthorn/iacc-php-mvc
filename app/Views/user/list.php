<?php
/**
 * User Management List View — MVC version
 * Variables: $usersByRole, $companies, $search, $roleFilter, $companyFilter, $message, $messageType
 */
$xml = $xml ?? (object)[];
$currentUserId = intval($_SESSION['user_id'] ?? 0);
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/master-data.css">
<style>
.user-container { font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; max-width:1400px; margin:0 auto; padding:20px; }
.page-header-user { background:linear-gradient(135deg,#4f46e5,#4338ca); color:#fff; padding:28px 32px; border-radius:16px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 10px 40px rgba(79,70,229,.3); }
.page-header-user .header-content { display:flex; align-items:center; gap:16px; }
.page-header-user .header-icon { width:56px; height:56px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; }
.page-header-user h2 { margin:0; font-size:26px; font-weight:700; }
.page-header-user .subtitle { margin:4px 0 0; opacity:.9; font-size:14px; }
.btn-add-user { background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.3); color:#fff; padding:12px 24px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; transition:all .2s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
.btn-add-user:hover { background:rgba(255,255,255,.25); color:#fff; transform:translateY(-2px); }
.filter-card-user { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px; border:1px solid #e5e7eb; overflow:hidden; }
.filter-card-user .card-header { background:linear-gradient(135deg,#f8fafc,#f1f5f9); padding:16px 24px; border-bottom:1px solid #e5e7eb; font-weight:600; color:#374151; display:flex; align-items:center; gap:10px; font-size:15px; }
.filter-card-user .card-header i { color:#4f46e5; }
.filter-card-user .card-body { padding:20px 24px; display:flex; flex-wrap:wrap; align-items:center; gap:16px; }
.filter-card-user .form-control { border-radius:10px; border:2px solid #e5e7eb; height:46px; padding:10px 16px; font-size:14px; }
.filter-card-user .form-control:focus { border-color:#4f46e5; box-shadow:0 0 0 4px rgba(79,70,229,.1); outline:none; }
.filter-card-user .btn-primary { background:linear-gradient(135deg,#4f46e5,#4338ca); border:none; padding:12px 24px; border-radius:10px; font-weight:600; }
.filter-card-user .btn-default { background:#fff; border:2px solid #e5e7eb; padding:12px 24px; border-radius:10px; font-weight:600; color:#64748b; }
.role-section { margin-bottom:24px; background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; border:1px solid #e5e7eb; }
.role-header { padding:20px 24px; display:flex; justify-content:space-between; align-items:center; }
.role-header.super-admin { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; }
.role-header.admin { background:linear-gradient(135deg,#0ea5e9,#38bdf8); color:#fff; }
.role-header.user { background:linear-gradient(135deg,#10b981,#34d399); color:#fff; }
.role-title { display:flex; align-items:center; gap:16px; }
.role-title .role-icon { width:48px; height:48px; background:rgba(255,255,255,.2); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
.role-title h4 { margin:0; font-size:18px; font-weight:700; color:#fff; }
.role-title .role-desc { font-size:13px; opacity:.9; margin-top:4px; }
.role-count { background:rgba(255,255,255,.2); padding:8px 18px; border-radius:20px; font-weight:600; font-size:14px; border:2px solid rgba(255,255,255,.3); }
.role-body .table { margin:0; }
.role-body .table th { background:#f9fafb; font-weight:600; color:#374151; border-top:none; padding:14px 16px; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
.role-body .table td { padding:14px 16px; vertical-align:middle; border-color:#f3f4f6; }
.role-body .table tr:hover { background:#f9fafb; }
.empty-role { padding:40px; text-align:center; color:#6b7280; }
.empty-role i { font-size:48px; margin-bottom:12px; opacity:.4; }
</style>

<div class="user-container">
<div class="page-header-user">
    <div class="header-content">
        <div class="header-icon"><i class="fa fa-users"></i></div>
        <div><h2><?=$xml->user_management ?? 'User Management'?></h2><p class="subtitle"><?=$xml->user_management_subtitle ?? 'Manage system users, roles and permissions'?></p></div>
    </div>
    <button type="button" class="btn-add-user" data-toggle="modal" data-target="#addUserModal"><i class="fa fa-plus"></i> <?=$xml->add_new_user ?? 'Add New User'?></button>
</div>

<div class="filter-card-user">
    <div class="card-header"><i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?></div>
    <div class="card-body">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <input type="hidden" name="page" value="user">
            <input type="text" class="form-control" name="search" placeholder="<?=$xml->search ?? 'Search'?> Email, Company..." value="<?=htmlspecialchars($search)?>" style="width:200px;">
            <select name="role" class="form-control" style="width:130px;">
                <option value="">All Roles</option>
                <option value="0" <?=$roleFilter==='0'?'selected':''?>>User</option>
                <option value="1" <?=$roleFilter==='1'?'selected':''?>>Admin</option>
                <option value="2" <?=$roleFilter==='2'?'selected':''?>>Super Admin</option>
            </select>
            <select name="company_id" class="form-control" style="width:180px;">
                <option value="">All Companies</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?=$co['id']?>" <?=$companyFilter==$co['id']?'selected':''?>><?=e($co['name_en'])?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=user" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?=$messageType?> alert-dismissible" style="border-radius:10px;border:none;"><button type="button" class="close" data-dismiss="alert">&times;</button><?=e($message)?></div>
<?php endif; ?>

<?php foreach ($usersByRole as $level => $roleData):
    $roleClass = ($level==2) ? 'super-admin' : (($level==1) ? 'admin' : 'user');
    $cnt = count($roleData['users']);
    if ($roleFilter !== '' && intval($roleFilter) !== $level) continue;
?>
<div class="role-section">
    <div class="role-header <?=$roleClass?>">
        <div class="role-title"><div class="role-icon"><i class="fa <?=$roleData['icon']?>"></i></div><div><h4><?=$roleData['label']?></h4><div class="role-desc"><?=$roleData['desc']?></div></div></div>
        <span class="role-count"><?=$cnt?> <?=$cnt==1?'user':'users'?></span>
    </div>
    <div class="role-body">
    <?php if ($cnt > 0): ?>
        <div class="table-responsive"><table class="table table-hover">
            <thead><tr>
                <th width="60">ID</th><th>Email</th>
                <?php if($level==0):?><th>Company</th><?php endif;?>
                <th width="100">Password</th><th width="100">Status</th><th width="250">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($roleData['users'] as $u):
                $isLocked = $u['locked_until'] && strtotime($u['locked_until']) > time();
                $isSelf   = intval($u['id']) === $currentUserId;
            ?>
            <tr <?=$isSelf?'style="background:#fffde7;"':''?>>
                <td><?=e($u['id'])?></td>
                <td><?=e($u['email'])?> <?php if($isSelf):?><span class="label label-primary">You</span><?php endif;?></td>
                <?php if($level==0):?>
                <td>
                    <?php if(!$isSelf):?>
                    <form method="post" action="index.php?page=user_store" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
                        <input type="hidden" name="action" value="update_company">
                        <input type="hidden" name="user_id" value="<?=$u['id']?>">
                        <select name="company_id" class="form-control input-sm" style="width:150px;display:inline;" onchange="this.form.submit()">
                            <option value="">-- Select --</option>
                            <?php foreach($companies as $co):?>
                            <option value="<?=$co['id']?>" <?=$u['company_id']==$co['id']?'selected':''?>><?=e($co['name_en'])?></option>
                            <?php endforeach;?>
                        </select>
                    </form>
                    <?php else:?><?=e($u['company_name'] ?? '-')?><?php endif;?>
                </td>
                <?php endif;?>
                <td><?php if($u['password_migrated']):?><span class="label label-success" title="bcrypt">Secure</span><?php else:?><span class="label label-warning" title="Legacy MD5">Legacy</span><?php endif;?></td>
                <td>
                    <?php if($isLocked):?>
                        <span class="label label-danger">Locked</span>
                        <form method="post" action="index.php?page=user_store" style="display:inline;"><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><input type="hidden" name="action" value="unlock"><input type="hidden" name="user_id" value="<?=$u['id']?>"><button type="submit" class="btn btn-xs btn-warning" title="Unlock"><i class="fa fa-unlock"></i></button></form>
                    <?php elseif($u['failed_attempts']>0):?>
                        <span class="label label-warning"><?=$u['failed_attempts']?> failed</span>
                    <?php else:?>
                        <span class="label label-success">Active</span>
                    <?php endif;?>
                </td>
                <td>
                    <?php if(!$isSelf):?>
                    <form method="post" action="index.php?page=user_store" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><input type="hidden" name="action" value="update_level"><input type="hidden" name="user_id" value="<?=$u['id']?>">
                        <select name="level" class="form-control input-sm" style="width:100px;display:inline;" onchange="this.form.submit()">
                            <option value="0" <?=$u['level']==0?'selected':''?>>User</option>
                            <option value="1" <?=$u['level']==1?'selected':''?>>Admin</option>
                            <option value="2" <?=$u['level']==2?'selected':''?>>Super Admin</option>
                        </select>
                    </form>
                    <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#resetPasswordModal" data-userid="<?=$u['id']?>" data-email="<?=e($u['email'])?>" title="Reset Password"><i class="fa fa-key"></i></button>
                    <form method="post" action="index.php?page=user_store" style="display:inline;" onsubmit="return confirm('<?=$xml->confirm_delete_user ?? 'Delete this user?'?>');"><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?=$u['id']?>"><button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash"></i></button></form>
                    <?php else:?><span class="text-muted">-</span><?php endif;?>
                </td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table></div>
    <?php else:?>
        <div class="empty-role"><i class="fa <?=$roleData['icon']?>"></i><p>No <?=strtolower($roleData['label'])?> found</p></div>
    <?php endif;?>
    </div>
</div>
<?php endforeach;?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="index.php?page=user_store">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-user-plus"></i> <?=$xml->add_new_user ?? 'Add New User'?></h4></div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label><?=$xml->email ?? 'Email'?></label><input type="email" class="form-control" name="email" required></div>
            <div class="form-group"><label><?=$xml->password ?? 'Password'?></label><input type="password" class="form-control" name="password" required minlength="6"></div>
            <div class="form-group"><label><?=$xml->role ?? 'Role'?></label><select class="form-control" id="level" name="level" onchange="toggleCompanyField()"><option value="0">User</option><option value="1">Admin</option><option value="2">Super Admin</option></select></div>
            <div class="form-group" id="companyField"><label><?=$xml->company ?? 'Company'?> <span class="text-danger">*</span></label>
                <select class="form-control" id="company_id" name="company_id"><option value="">-- Select --</option>
                    <?php foreach($companies as $co):?><option value="<?=$co['id']?>"><?=e($co['name_en'])?></option><?php endforeach;?>
                </select><p class="help-block">Normal users must be assigned to a company.</p>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=$xml->cancel ?? 'Cancel'?></button><button type="submit" class="btn btn-success"><?=$xml->create_user ?? 'Create User'?></button></div>
    </form>
</div></div></div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="index.php?page=user_store">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-key"></i> <?=$xml->reset_password ?? 'Reset Password'?></h4></div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id">
            <p>Reset password for: <strong id="reset_user_email"></strong></p>
            <div class="form-group"><label><?=$xml->new_password ?? 'New Password'?></label><input type="password" class="form-control" name="new_password" required minlength="6"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=$xml->cancel ?? 'Cancel'?></button><button type="submit" class="btn btn-info"><?=$xml->reset_password ?? 'Reset Password'?></button></div>
    </form>
</div></div></div>
</div>

<script>
function toggleCompanyField(){var l=document.getElementById('level').value,f=document.getElementById('companyField'),s=document.getElementById('company_id');if(l=='0'){f.style.display='block';s.required=true;}else{f.style.display='none';s.required=false;s.value='';}}
document.addEventListener('DOMContentLoaded',toggleCompanyField);
$('#resetPasswordModal').on('show.bs.modal',function(e){var b=$(e.relatedTarget);$(this).find('#reset_user_id').val(b.data('userid'));$(this).find('#reset_user_email').text(b.data('email'));});
</script>
