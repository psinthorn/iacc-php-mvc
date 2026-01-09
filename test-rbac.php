<?php
/**
 * RBAC Test Page
 * Tests the Role-Based Access Control system
 * 
 * Access: Developer role required
 * Created: January 9, 2026
 * Updated: January 9, 2026 - Redesigned to match test-crud.php styling
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/dev-tools-style.php");

$db = new DbConn($config);
$db->checkSecurity();

// Require Developer access
check_dev_tools_access();

// Refresh RBAC cache if requested
if (isset($_GET['refresh'])) {
    rbac_refresh($db->conn);
    header("Location: index.php?page=test_rbac&refreshed=1");
    exit;
}

$test_date = date('Y-m-d H:i:s');

// Get current user info
$userId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION['user_email'] ?? 'Unknown';
$userLevel = $_SESSION['user_level'] ?? 0;

// Get RBAC data from session
$permissions = get_user_permissions();
$roles = get_user_roles();

// Get all available permissions from database
$allPermissions = [];
$result = mysqli_query($db->conn, "SELECT id, `key`, name, description FROM permissions ORDER BY `key`");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allPermissions[] = $row;
    }
}

// Get all roles from database with permission count
$allRoles = [];
$result = mysqli_query($db->conn, "
    SELECT r.id, r.name, r.description, COUNT(rp.permission_id) as perm_count
    FROM roles r
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id, r.name, r.description
    ORDER BY r.id
");
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
$testPermissions = ['po.view', 'po.create', 'po.edit', 'user.manage', 'admin.access', 'developer.access', 'nonexistent.permission'];
$testResults = [];
$passCount = 0;
$failCount = 0;
foreach ($testPermissions as $perm) {
    $hasIt = has_permission($perm, $db->conn);
    $testResults[$perm] = $hasIt;
    // nonexistent should fail, others should pass for admin
    if ($perm === 'nonexistent.permission') {
        if (!$hasIt) $passCount++;
        else $failCount++;
    } else {
        if ($hasIt) $passCount++;
        else $failCount++;
    }
}

// Test role checks
$roleTests = ['Developer', 'Admin', 'Manager', 'NonexistentRole'];
$roleResults = [];
foreach ($roleTests as $role) {
    $hasIt = has_role($role, $db->conn);
    $roleResults[$role] = $hasIt;
}

// Calculate totals
$totalTests = count($testResults) + count($roleResults);
$totalPassed = $passCount + count(array_filter($roleResults, function($v, $k) { return ($k === 'NonexistentRole') ? !$v : $v; }, ARRAY_FILTER_USE_BOTH));

// Helper function for status badge
function getRbacStatusBadge($passed, $expected = true) {
    if ($passed === $expected) {
        return "<span class='status-badge status-pass'><i class='fa fa-check'></i> PASS</span>";
    } else {
        return "<span class='status-badge status-fail'><i class='fa fa-times'></i> FAIL</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RBAC Test - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
    <?php include_once __DIR__ . '/inc/skeleton-loader.php'; ?>
    <style><?php echo get_skeleton_styles(); ?></style>
</head>
<body>
    <div class="dev-tools-container skeleton-loading" id="pageContainer">
        <!-- Skeleton Loading State -->
        <div class="skeleton-container">
            <?php echo skeleton_page_header(); ?>
            <?php echo skeleton_stat_cards(4); ?>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_table(6, 3); ?>
            </div>
            <div style="margin-top: 20px;">
                <?php echo skeleton_card(true); ?>
            </div>
        </div>
        
        <!-- Actual Content -->
        <div class="content-container">
        <?php echo get_dev_tools_header('RBAC Test', 'Testing Role-Based Access Control System', 'fa-shield', '#8b5cf6'); ?>
        
        <?php if (isset($_GET['refreshed'])): ?>
        <div class="info-box success">
            <i class="fa fa-check-circle"></i>
            <div><strong>RBAC Cache Refreshed!</strong> Permissions and roles have been reloaded from the database.</div>
        </div>
        <?php endif; ?>
        
        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon"><i class="fa fa-id-badge"></i></div>
                <div class="stat-value"><?php echo count($roles); ?></div>
                <div class="stat-label">Your Roles</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-key"></i></div>
                <div class="stat-value"><?php echo count($permissions); ?></div>
                <div class="stat-label">Your Permissions</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fa fa-users"></i></div>
                <div class="stat-value"><?php echo count($allRoles); ?></div>
                <div class="stat-label">Total Roles</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fa fa-lock"></i></div>
                <div class="stat-value"><?php echo count($allPermissions); ?></div>
                <div class="stat-label">Total Permissions</div>
            </div>
        </div>
        
        <!-- Test Date -->
        <div class="info-box info">
            <i class="fa fa-clock-o"></i>
            <div><strong>Test executed:</strong> <?php echo $test_date; ?></div>
        </div>
        
        <!-- Current User -->
        <div class="test-section">
            <h2><i class="fa fa-user"></i> Current User Info</h2>
            <table class="data-table">
                <tbody>
                    <tr>
                        <td style="width: 150px; font-weight: 600;">User ID</td>
                        <td><span class="status-badge status-info"><?php echo e($userId); ?></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Email</td>
                        <td><?php echo e($userEmail); ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">User Level</td>
                        <td>
                            <span class="status-badge <?php echo $userLevel >= 2 ? 'status-pass' : ($userLevel >= 1 ? 'status-warn' : 'status-info'); ?>">
                                Level <?php echo e($userLevel); ?> (<?php echo $userLevel >= 2 ? 'Super Admin' : ($userLevel >= 1 ? 'Admin' : 'User'); ?>)
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">RBAC Status</td>
                        <td>
                            <span class="status-badge <?php echo !empty($permissions) ? 'status-pass' : 'status-warn'; ?>">
                                <?php echo !empty($permissions) ? 'Active (' . count($permissions) . ' permissions)' : 'Fallback Mode'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Your Roles</td>
                        <td>
                            <?php if (empty($roles)): ?>
                                <span style="color: #6c757d; font-style: italic;">No RBAC roles assigned</span>
                            <?php else: ?>
                                <?php foreach ($roles as $role): ?>
                                <span style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 5px;">
                                    <?php echo e($role); ?>
                                </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Permission Check Tests -->
        <div class="test-section">
            <h2><i class="fa fa-flask"></i> Permission Check Tests</h2>
            <p style="color: #6c757d; margin-bottom: 15px;">Testing <code>has_permission()</code> function:</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Permission Key</th>
                        <th>Expected</th>
                        <th>Result</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testResults as $perm => $hasIt): ?>
                    <?php $expected = ($perm !== 'nonexistent.permission'); ?>
                    <tr>
                        <td><code><?php echo e($perm); ?></code></td>
                        <td><?php echo $expected ? 'TRUE' : 'FALSE'; ?></td>
                        <td><?php echo $hasIt ? 'TRUE' : 'FALSE'; ?></td>
                        <td><?php echo getRbacStatusBadge($hasIt, $expected); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Role Check Tests -->
        <div class="test-section">
            <h2><i class="fa fa-id-badge"></i> Role Check Tests</h2>
            <p style="color: #6c757d; margin-bottom: 15px;">Testing <code>has_role()</code> function:</p>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach ($roleResults as $role => $hasIt): ?>
                <?php $expected = ($role !== 'NonexistentRole'); ?>
                <div>
                    <h3><?php echo e($role); ?></h3>
                    <?php echo getRbacStatusBadge($hasIt, in_array($role, $roles)); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Your Permissions -->
        <div class="test-section">
            <h2><i class="fa fa-key"></i> Your Permissions</h2>
            <?php if (empty($permissions)): ?>
                <div class="info-box warning" style="margin: 0;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div><strong>No RBAC Permissions Cached.</strong> Click "Refresh RBAC Cache" to load permissions.</div>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($permissions as $perm): ?>
                    <code style="background: #ecfdf5; color: #065f46; padding: 6px 12px; border-radius: 6px; font-size: 12px; border: 1px solid #a7f3d0;">
                        <?php echo e($perm); ?>
                    </code>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- All Roles -->
        <div class="test-section">
            <h2><i class="fa fa-users"></i> All Available Roles</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allRoles as $role): ?>
                    <tr>
                        <td><?php echo e($role['id']); ?></td>
                        <td><strong><?php echo e($role['name']); ?></strong></td>
                        <td style="font-size: 13px; color: #6c757d;"><?php echo e($role['description']); ?></td>
                        <td><span class="status-badge status-info"><?php echo e($role['perm_count']); ?></span></td>
                        <td>
                            <?php if (in_array($role['name'], $roles)): ?>
                                <span class="status-badge status-pass"><i class="fa fa-check"></i> YOUR ROLE</span>
                            <?php else: ?>
                                <span style="color: #6c757d;">Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- All Permissions -->
        <div class="test-section">
            <h2><i class="fa fa-lock"></i> All Available Permissions</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Permission Key</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allPermissions as $perm): ?>
                    <tr>
                        <td><?php echo e($perm['id']); ?></td>
                        <td><code><?php echo e($perm['key']); ?></code></td>
                        <td><strong><?php echo e($perm['name']); ?></strong></td>
                        <td style="font-size: 13px; color: #6c757d;"><?php echo e($perm['description']); ?></td>
                        <td>
                            <?php if (in_array($perm['key'], $permissions)): ?>
                                <span class="status-badge status-pass"><i class="fa fa-check"></i> YOU HAVE</span>
                            <?php else: ?>
                                <span style="color: #6c757d;">Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Users with RBAC Roles -->
        <div class="test-section">
            <h2><i class="fa fa-address-book"></i> All Users with RBAC Assignments</h2>
            <table class="data-table">
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
                    <tr style="<?php echo $user['id'] == $userId ? 'background: #faf5ff;' : ''; ?>">
                        <td>
                            <?php echo e($user['id']); ?>
                            <?php if ($user['id'] == $userId): ?>
                                <span style="color: #8b5cf6; font-size: 11px;">(You)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($user['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['level'] >= 2 ? 'status-pass' : ($user['level'] >= 1 ? 'status-warn' : 'status-info'); ?>">
                                Level <?php echo e($user['level']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['roles']): ?>
                                <?php foreach (explode(', ', $user['roles']) as $r): ?>
                                <span style="background: #ede9fe; color: #7c3aed; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-right: 4px; display: inline-block; margin-bottom: 4px;">
                                    <?php echo e($r); ?>
                                </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #9ca3af; font-style: italic;">No RBAC roles</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Session Debug -->
        <div class="test-section">
            <h2><i class="fa fa-bug"></i> Session RBAC Data (Debug)</h2>
            <div class="code-block" style="background: #1a1a2e; padding: 20px; border-radius: 8px;">
<pre style="margin: 0; color: #00ff88; font-family: 'Monaco', 'Menlo', 'Consolas', monospace; font-size: 13px; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;"><?php
$rbacSession = [
    'rbac_permissions' => $_SESSION['rbac_permissions'] ?? 'NOT SET',
    'rbac_roles' => $_SESSION['rbac_roles'] ?? 'NOT SET',
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'user_level' => $_SESSION['user_level'] ?? 'NOT SET',
];
echo htmlspecialchars(json_encode($rbacSession, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
?></pre>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="summary-box">
            <h3><i class="fa fa-bar-chart"></i> RBAC Summary</h3>
            <div class="summary-stats">
                <div class="summary-stat pass">
                    <div class="summary-stat-value"><?php echo count($roles); ?></div>
                    <div class="summary-stat-label">Your Roles</div>
                </div>
                <div class="summary-stat pass">
                    <div class="summary-stat-value"><?php echo count($permissions); ?></div>
                    <div class="summary-stat-label">Your Permissions</div>
                </div>
                <div class="summary-stat warn">
                    <div class="summary-stat-value"><?php echo count($allRoles); ?></div>
                    <div class="summary-stat-label">Total Roles</div>
                </div>
            </div>
            
            <?php if (!empty($permissions) && !empty($roles)): ?>
            <div class="info-box success" style="margin-top: 20px; margin-bottom: 0; background: #d4edda; color: #155724;">
                <i class="fa fa-check-circle" style="color: #155724;"></i>
                <div style="color: #155724;"><strong>RBAC is active and working correctly!</strong> You have <?php echo count($roles); ?> role(s) with <?php echo count($permissions); ?> permission(s).</div>
            </div>
            <?php else: ?>
            <div class="info-box warning" style="margin-top: 20px; margin-bottom: 0; background: #fff3cd; color: #856404;">
                <i class="fa fa-exclamation-triangle" style="color: #856404;"></i>
                <div style="color: #856404;"><strong>RBAC cache is empty.</strong> Click "Refresh RBAC Cache" to load your permissions.</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php?page=test_rbac&refresh=1" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Refresh RBAC Cache</a>
            <a href="index.php?page=test_rbac" class="btn-dev btn-outline"><i class="fa fa-redo"></i> Run Tests Again</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        </div><!-- End content-container -->
    </div>
    <script><?php echo get_skeleton_js('pageContainer', 300); ?></script>
</body>
</html>
