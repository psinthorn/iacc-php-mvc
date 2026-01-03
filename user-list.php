<?php
/**
 * User Management Page
 * Super Admin only (level 2)
 */
require_once("inc/security.php");

// Require Super Admin access
$db->requireLevel(2);

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        switch ($action) {
            case 'add':
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $password = isset($_POST['password']) ? $_POST['password'] : '';
                $level = isset($_POST['level']) ? intval($_POST['level']) : 0;
                $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : null;
                
                // Normal users (level 0) must have a company
                if ($level == 0 && empty($companyId)) {
                    $message = 'Normal users must be assigned to a company.';
                    $messageType = 'danger';
                } elseif (empty($email) || empty($password)) {
                    $message = 'Email and password are required.';
                    $messageType = 'danger';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Invalid email format.';
                    $messageType = 'danger';
                } else {
                    // Check if email exists
                    $stmt = $db->conn->prepare("SELECT id FROM authorize WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        $message = 'Email already exists.';
                        $messageType = 'danger';
                    } else {
                        // Insert new user
                        $hash = password_hash_secure($password);
                        // Admin/Super Admin don't need company_id
                        $finalCompanyId = ($level >= 1) ? null : $companyId;
                        $stmt = $db->conn->prepare("INSERT INTO authorize (email, password, level, company_id, lang, password_migrated) VALUES (?, ?, ?, ?, 0, 1)");
                        $stmt->bind_param("ssii", $email, $hash, $level, $finalCompanyId);
                        if ($stmt->execute()) {
                            $message = 'User created successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create user.';
                            $messageType = 'danger';
                        }
                    }
                    $stmt->close();
                }
                break;
                
            case 'update_level':
                $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                $level = isset($_POST['level']) ? intval($_POST['level']) : 0;
                
                // Prevent self-demotion
                if ($userId === intval($_SESSION['user_id'])) {
                    $message = 'You cannot change your own role.';
                    $messageType = 'warning';
                } else {
                    // If demoting to user level, keep existing company_id
                    // If promoting to admin/super admin, clear company_id
                    if ($level >= 1) {
                        $stmt = $db->conn->prepare("UPDATE authorize SET level = ?, company_id = NULL WHERE id = ?");
                        $stmt->bind_param("ii", $level, $userId);
                    } else {
                        $stmt = $db->conn->prepare("UPDATE authorize SET level = ? WHERE id = ?");
                        $stmt->bind_param("ii", $level, $userId);
                    }
                    if ($stmt->execute()) {
                        $message = 'User role updated successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update user role.';
                        $messageType = 'danger';
                    }
                    $stmt->close();
                }
                break;
            
            case 'update_company':
                $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                $companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : null;
                
                if (empty($companyId)) {
                    $companyId = null;
                }
                
                $stmt = $db->conn->prepare("UPDATE authorize SET company_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $companyId, $userId);
                if ($stmt->execute()) {
                    $message = 'User company updated successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update user company.';
                    $messageType = 'danger';
                }
                $stmt->close();
                break;
                
            case 'reset_password':
                $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
                
                if (strlen($newPassword) < 6) {
                    $message = 'Password must be at least 6 characters.';
                    $messageType = 'danger';
                } else {
                    $hash = password_hash_secure($newPassword);
                    $stmt = $db->conn->prepare("UPDATE authorize SET password = ?, password_migrated = 1 WHERE id = ?");
                    $stmt->bind_param("si", $hash, $userId);
                    if ($stmt->execute()) {
                        $message = 'Password reset successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to reset password.';
                        $messageType = 'danger';
                    }
                    $stmt->close();
                }
                break;
                
            case 'unlock':
                $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                $stmt = $db->conn->prepare("UPDATE authorize SET locked_until = NULL, failed_attempts = 0 WHERE id = ?");
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $message = 'User account unlocked.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to unlock user.';
                    $messageType = 'danger';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                
                // Prevent self-deletion
                if ($userId === intval($_SESSION['user_id'])) {
                    $message = 'You cannot delete your own account.';
                    $messageType = 'warning';
                } else {
                    $stmt = $db->conn->prepare("DELETE FROM authorize WHERE id = ?");
                    $stmt->bind_param("i", $userId);
                    if ($stmt->execute()) {
                        $message = 'User deleted successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete user.';
                        $messageType = 'danger';
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$company_filter = isset($_GET['company_id']) ? $_GET['company_id'] : '';

// Build search condition
$search_cond = '';
if (!empty($search)) {
    $search_escaped = sql_escape($search);
    $search_cond = " AND (a.email LIKE '%$search_escaped%' OR c.name_en LIKE '%$search_escaped%')";
}

// Build role filter
$role_cond = '';
if ($role_filter !== '') {
    $role_cond = " AND a.level = " . intval($role_filter);
}

// Build company filter
$company_cond = '';
if ($company_filter !== '') {
    $company_cond = " AND a.company_id = " . intval($company_filter);
}

// Fetch all users with company info
$sql = "SELECT a.id, a.email, a.level, a.company_id, a.lang, a.password_migrated, a.locked_until, a.failed_attempts, 
        c.name_en as company_name 
        FROM authorize a 
        LEFT JOIN company c ON a.company_id = c.id 
        WHERE 1=1 $search_cond $role_cond $company_cond
        ORDER BY a.id ASC";
$result = $db->conn->query($sql);

// Fetch all companies for dropdown
$companiesResult = $db->conn->query("SELECT id, name_en FROM company ORDER BY name_en ASC");
$companies = [];
while ($row = $companiesResult->fetch_assoc()) {
    $companies[] = $row;
}

// Role labels
$roles = [
    0 => ['label' => 'User', 'class' => 'default'],
    1 => ['label' => 'Admin', 'class' => 'info'],
    2 => ['label' => 'Super Admin', 'class' => 'danger']
];
?>

<h2><i class="fa fa-users fa-fw"></i> <?= isset($xml->user) ? $xml->user : 'User Management' ?></h2>

<!-- Search and Filter Panel -->
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fa fa-filter"></i> <?=$xml->search ?? 'Search'?> & <?=$xml->filter ?? 'Filter'?>
    </div>
    <div class="panel-body">
        <form method="get" action="" class="form-inline">
            <input type="hidden" name="page" value="user">
            
            <div class="form-group" style="margin-right: 15px;">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?=$xml->search ?? 'Search'?> Email, Company..." 
                       value="<?=htmlspecialchars($search)?>" style="width: 200px;">
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">Role:</label>
                <select name="role" class="form-control">
                    <option value="">All Roles</option>
                    <option value="0" <?=$role_filter==='0'?'selected':''?>>User</option>
                    <option value="1" <?=$role_filter==='1'?'selected':''?>>Admin</option>
                    <option value="2" <?=$role_filter==='2'?'selected':''?>>Super Admin</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-right: 10px;">
                <label style="margin-right: 5px;">Company:</label>
                <select name="company_id" class="form-control">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>" <?=$company_filter==$company['id']?'selected':''?>>
                        <?= e($company['name_en']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?=$xml->search ?? 'Search'?></button>
            <a href="?page=user" class="btn btn-default"><i class="fa fa-refresh"></i> <?=$xml->clear ?? 'Clear'?></a>
        </form>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <?= e($message) ?>
</div>
<?php endif; ?>

<!-- Add User Button -->
<div class="mb-3" style="margin-bottom: 15px;">
    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addUserModal">
        <i class="fa fa-plus"></i> Add New User
    </button>
</div>

<!-- Users Table -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Role</th>
                <th>Company</th>
                <th>Password</th>
                <th>Status</th>
                <th width="200">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): 
                $isLocked = $user['locked_until'] && strtotime($user['locked_until']) > time();
                $isSelf = intval($user['id']) === intval($_SESSION['user_id']);
            ?>
            <tr <?= $isSelf ? 'class="info"' : '' ?>>
                <td><?= e($user['id']) ?></td>
                <td>
                    <?= e($user['email']) ?>
                    <?php if ($isSelf): ?>
                        <span class="label label-primary">You</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$isSelf): ?>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_level">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <select name="level" class="form-control input-sm" style="width: auto; display: inline;" onchange="this.form.submit()">
                            <option value="0" <?= $user['level'] == 0 ? 'selected' : '' ?>>User</option>
                            <option value="1" <?= $user['level'] == 1 ? 'selected' : '' ?>>Admin</option>
                            <option value="2" <?= $user['level'] == 2 ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                    </form>
                    <?php else: 
                        $role = isset($roles[$user['level']]) ? $roles[$user['level']] : $roles[0];
                    ?>
                        <span class="label label-<?= $role['class'] ?>"><?= $role['label'] ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($user['level'] == 0): // Only show company for normal users ?>
                        <?php if (!$isSelf): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_company">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="company_id" class="form-control input-sm" style="width: 150px; display: inline;" onchange="this.form.submit()">
                                <option value="">-- Select --</option>
                                <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['id'] ?>" <?= $user['company_id'] == $company['id'] ? 'selected' : '' ?>>
                                    <?= e($company['name_en']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php else: ?>
                            <?= e($user['company_name'] ?? '-') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">All Companies</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($user['password_migrated']): ?>
                        <span class="label label-success" title="Using bcrypt">Secure</span>
                    <?php else: ?>
                        <span class="label label-warning" title="Using legacy MD5 - will migrate on next login">Legacy</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isLocked): ?>
                        <span class="label label-danger">Locked</span>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="unlock">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-warning" title="Unlock account">
                                <i class="fa fa-unlock"></i>
                            </button>
                        </form>
                    <?php elseif ($user['failed_attempts'] > 0): ?>
                        <span class="label label-warning"><?= $user['failed_attempts'] ?> failed</span>
                    <?php else: ?>
                        <span class="label label-success">Active</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$isSelf): ?>
                    <!-- Reset Password -->
                    <button type="button" class="btn btn-xs btn-info" data-toggle="modal" 
                            data-target="#resetPasswordModal" 
                            data-userid="<?= $user['id'] ?>" 
                            data-email="<?= e($user['email']) ?>">
                        <i class="fa fa-key"></i> Reset
                    </button>
                    
                    <!-- Delete -->
                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-xs btn-danger">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </form>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-user-plus"></i> Add New User</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="level">Role</label>
                        <select class="form-control" id="level" name="level" onchange="toggleCompanyField()">
                            <option value="0">User</option>
                            <option value="1">Admin</option>
                            <option value="2">Super Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="companyField">
                        <label for="company_id">Company <span class="text-danger">*</span></label>
                        <select class="form-control" id="company_id" name="company_id">
                            <option value="">-- Select Company --</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['id'] ?>"><?= e($company['name_en']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-block">Normal users must be assigned to a company.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-key"></i> Reset Password</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    
                    <p>Reset password for: <strong id="reset_user_email"></strong></p>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle company field visibility based on role selection
function toggleCompanyField() {
    var level = document.getElementById('level').value;
    var companyField = document.getElementById('companyField');
    var companySelect = document.getElementById('company_id');
    
    if (level == '0') {
        companyField.style.display = 'block';
        companySelect.required = true;
    } else {
        companyField.style.display = 'none';
        companySelect.required = false;
        companySelect.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCompanyField();
});

// Populate Reset Password modal with user data
$('#resetPasswordModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var userId = button.data('userid');
    var email = button.data('email');
    
    var modal = $(this);
    modal.find('#reset_user_id').val(userId);
    modal.find('#reset_user_email').text(email);
});
</script>
