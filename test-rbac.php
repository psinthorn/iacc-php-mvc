<?php
/**
 * RBAC Test Page
 * Tests the Role-Based Access Control system
 * 
 * Access: Super Admin only (user_level >= 2)
 * Created: January 9, 2026
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");

$db = new DbConn($config);
$db->checkSecurity();

// Require Super Admin access
check_dev_tools_access();

// Refresh RBAC cache if requested
if (isset($_GET['refresh'])) {
    rbac_refresh($db->conn);
    header("Location: test-rbac.php?refreshed=1");
    exit;
}

// Get current user info
$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? 'Unknown';
$userLevel = $_SESSION['user_level'] ?? 0;

// Get RBAC data from session
$permissions = get_user_permissions();
$roles = get_user_roles();

// Get all available permissions from database
$allPermissions = [];
$result = mysqli_query($db->conn, "SELECT `key`, name, description FROM permissions ORDER BY `key`");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allPermissions[] = $row;
    }
}

// Get all roles from database
$allRoles = [];
$result = mysqli_query($db->conn, "SELECT id, name, description FROM roles ORDER BY id");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allRoles[] = $row;
    }
}

// Get all users with their roles
$usersWithRoles = [];
$sql = "SELECT a.id, a.email, a.level, GROUP_CONCAT(r.name SEPARATOR ', ') as roles
        FROM authorize a
        LEFT JOIN user_roles ur ON a.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY a.id, a.email, a.level
        ORDER BY a.id";
$result = mysqli_query($db->conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $usersWithRoles[] = $row;
    }
}

// Test specific permissions
$testPermissions = ['po.view', 'po.create', 'po.edit', 'user.manage', 'admin.access', 'nonexistent.permission'];
$testResults = [];
foreach ($testPermissions as $perm) {
    $testResults[$perm] = has_permission($perm, $db->conn);
}

echo get_dev_tools_header('RBAC Test', 'Testing Role-Based Access Control System');
?>

<style>
    .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
    .test-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .test-card h3 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; color: #1f2937; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .permission-list { list-style: none; padding: 0; margin: 0; }
    .permission-list li { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
    .permission-list li:last-child { border-bottom: none; }
    .refresh-btn { display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 20px; }
    .refresh-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
    .user-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .user-table th, .user-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    .user-table th { background: #f9fafb; font-weight: 600; color: #374151; }
    .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
</style>

<?php if (isset($_GET['refreshed'])): ?>
<div class="alert-success">
    ✅ RBAC cache refreshed successfully!
</div>
<?php endif; ?>

<a href="test-rbac.php?refresh=1" class="refresh-btn">🔄 Refresh RBAC Cache</a>
<a href="index.php?page=dev_tools" class="refresh-btn" style="background: linear-gradient(135deg, #6b7280, #4b5563); margin-left: 10px;">← Back to Dev Tools</a>

<div class="test-grid">
    <!-- Current User Info -->
    <div class="test-card">
        <h3>👤 Current User</h3>
        <ul class="permission-list">
            <li>
                <span>User ID</span>
                <span class="badge badge-info"><?= e($userId) ?></span>
            </li>
            <li>
                <span>Email</span>
                <span><?= e($userEmail) ?></span>
            </li>
            <li>
                <span>User Level</span>
                <span class="badge badge-<?= $userLevel >= 2 ? 'success' : ($userLevel >= 1 ? 'warning' : 'info') ?>">
                    Level <?= e($userLevel) ?> (<?= $userLevel >= 2 ? 'Super Admin' : ($userLevel >= 1 ? 'Admin' : 'User') ?>)
                </span>
            </li>
        </ul>
    </div>
    
    <!-- User's Roles -->
    <div class="test-card">
        <h3>🎭 Your Roles (from RBAC)</h3>
        <?php if (empty($roles)): ?>
            <p style="color: #6b7280; font-style: italic;">No RBAC roles assigned</p>
        <?php else: ?>
            <ul class="permission-list">
                <?php foreach ($roles as $role): ?>
                <li>
                    <span><?= e($role) ?></span>
                    <span class="badge badge-success">Active</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <!-- User's Permissions -->
    <div class="test-card">
        <h3>🔑 Your Permissions (from RBAC)</h3>
        <?php if (empty($permissions)): ?>
            <p style="color: #6b7280; font-style: italic;">No RBAC permissions cached (using user_level fallback)</p>
        <?php else: ?>
            <ul class="permission-list">
                <?php foreach ($permissions as $perm): ?>
                <li>
                    <span><code><?= e($perm) ?></code></span>
                    <span class="badge badge-success">✓</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <!-- Permission Tests -->
    <div class="test-card">
        <h3>🧪 Permission Check Tests</h3>
        <p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">
            Testing <code>has_permission()</code> function:
        </p>
        <ul class="permission-list">
            <?php foreach ($testResults as $perm => $hasIt): ?>
            <li>
                <span><code>has_permission('<?= e($perm) ?>')</code></span>
                <span class="badge badge-<?= $hasIt ? 'success' : 'danger' ?>">
                    <?= $hasIt ? '✓ TRUE' : '✗ FALSE' ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- All Available Permissions -->
    <div class="test-card">
        <h3>📋 All Available Permissions</h3>
        <ul class="permission-list">
            <?php foreach ($allPermissions as $perm): ?>
            <li>
                <span>
                    <code><?= e($perm['key']) ?></code>
                    <small style="color: #6b7280; display: block;"><?= e($perm['description']) ?></small>
                </span>
                <span class="badge badge-<?= in_array($perm['key'], $permissions) ? 'success' : 'info' ?>">
                    <?= in_array($perm['key'], $permissions) ? 'You Have' : 'Available' ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- All Roles -->
    <div class="test-card">
        <h3>🏷️ All Available Roles</h3>
        <ul class="permission-list">
            <?php foreach ($allRoles as $role): ?>
            <li>
                <span>
                    <strong><?= e($role['name']) ?></strong>
                    <small style="color: #6b7280; display: block;"><?= e($role['description']) ?></small>
                </span>
                <span class="badge badge-<?= in_array($role['name'], $roles) ? 'success' : 'info' ?>">
                    <?= in_array($role['name'], $roles) ? 'Your Role' : 'ID: ' . $role['id'] ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Users with Roles Table -->
<div class="test-card" style="margin-top: 20px;">
    <h3>👥 All Users with RBAC Roles</h3>
    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>User Level</th>
                <th>RBAC Roles</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usersWithRoles as $user): ?>
            <tr>
                <td><?= e($user['id']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td>
                    <span class="badge badge-<?= $user['level'] >= 2 ? 'success' : ($user['level'] >= 1 ? 'warning' : 'info') ?>">
                        Level <?= e($user['level']) ?>
                    </span>
                </td>
                <td><?= e($user['roles'] ?: 'No RBAC roles') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Session Debug -->
<div class="test-card" style="margin-top: 20px;">
    <h3>🔍 Session RBAC Data (Debug)</h3>
    <pre style="background: #1f2937; color: #10b981; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px;"><?php
        $rbacSession = [
            'rbac_permissions' => $_SESSION['rbac_permissions'] ?? 'NOT SET',
            'rbac_roles' => $_SESSION['rbac_roles'] ?? 'NOT SET',
        ];
        echo htmlspecialchars(json_encode($rbacSession, JSON_PRETTY_PRINT));
    ?></pre>
</div>

<?php include("inc/footer.php"); ?>
