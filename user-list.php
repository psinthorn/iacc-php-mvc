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
                
                if (empty($email) || empty($password)) {
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
                        $stmt = $db->conn->prepare("INSERT INTO authorize (email, password, level, lang, password_migrated) VALUES (?, ?, ?, 0, 1)");
                        $stmt->bind_param("ssi", $email, $hash, $level);
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
                    $stmt = $db->conn->prepare("UPDATE authorize SET level = ? WHERE id = ?");
                    $stmt->bind_param("ii", $level, $userId);
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

// Fetch all users
$sql = "SELECT id, email, level, lang, password_migrated, locked_until, failed_attempts FROM authorize ORDER BY id ASC";
$result = $db->conn->query($sql);

// Role labels
$roles = [
    0 => ['label' => 'User', 'class' => 'default'],
    1 => ['label' => 'Admin', 'class' => 'info'],
    2 => ['label' => 'Super Admin', 'class' => 'danger']
];
?>

<h2><i class="fa fa-users fa-fw"></i> <?= isset($xml->user) ? $xml->user : 'User Management' ?></h2>

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
                        <select class="form-control" id="level" name="level">
                            <option value="0">User</option>
                            <option value="1">Admin</option>
                            <option value="2">Super Admin</option>
                        </select>
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
