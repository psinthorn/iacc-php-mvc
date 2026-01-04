<?php
/**
 * Invoice Debug Tool
 * Debug invoice access and permissions
 * Redesigned with modern UI
 */

session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/dev-tools-style.php");

// Check access
check_dev_tools_access();

$db = new DbConn($config);

// Get invoice/PO ID from request
$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 1923;

// Fetch PO and related data
$po_data = null;
$access_check = null;
$session_com = $_SESSION['com_id'] ?? 0;

$q = mysqli_query($db->conn, "SELECT po.id as po_id, po.name as po_name, po.date as po_date, 
    pr.id as pr_id, pr.cus_id, pr.ven_id, pr.payby,
    cus.name_en as cus_name, ven.name_en as ven_name, paycom.name_en as payby_name
    FROM po 
    JOIN pr ON pr.id = po.ref 
    LEFT JOIN company cus ON cus.id = pr.cus_id
    LEFT JOIN company ven ON ven.id = pr.ven_id
    LEFT JOIN company paycom ON paycom.id = pr.payby
    WHERE po.id = '$po_id'");

if ($q && mysqli_num_rows($q) > 0) {
    $po_data = mysqli_fetch_assoc($q);
    
    // Check access
    $has_access = ($session_com == $po_data['cus_id'] || 
                   $session_com == $po_data['ven_id'] || 
                   $session_com == $po_data['payby'] ||
                   ($_SESSION['user_level'] ?? 0) >= 2);
    
    $access_check = [
        'has_access' => $has_access,
        'session_com_id' => $session_com,
        'required_ids' => [
            'cus_id' => $po_data['cus_id'],
            'ven_id' => $po_data['ven_id'],
            'payby' => $po_data['payby']
        ],
        'is_super_admin' => ($_SESSION['user_level'] ?? 0) >= 2
    ];
}

// Get recent POs for testing
$recent_pos = mysqli_query($db->conn, "SELECT po.id, po.name, po.date FROM po ORDER BY po.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Debug - Developer Tools</title>
    <?php echo get_dev_tools_css(); ?>
</head>
<body>
    <div class="dev-tools-container">
        <?php echo get_dev_tools_header('Invoice Debug', 'Debug invoice access permissions and data relationships', 'fa-file-text-o', '#27ae60'); ?>
        
        <!-- Session Info -->
        <div class="stats-grid">
            <div class="stat-card <?php echo isset($_SESSION['user_id']) ? 'success' : 'danger'; ?>">
                <div class="stat-icon"><i class="fa fa-user"></i></div>
                <div class="stat-value"><?php echo $_SESSION['user_id'] ?? 'N/A'; ?></div>
                <div class="stat-label">Your User ID</div>
            </div>
            <div class="stat-card <?php echo $session_com ? 'success' : 'warning'; ?>">
                <div class="stat-icon"><i class="fa fa-building"></i></div>
                <div class="stat-value"><?php echo $session_com ?: 'NOT SET'; ?></div>
                <div class="stat-label">Your Company ID</div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fa fa-shield"></i></div>
                <div class="stat-value"><?php 
                    $level = $_SESSION['user_level'] ?? 0;
                    echo $level == 2 ? 'Super Admin' : ($level == 1 ? 'Admin' : 'User');
                ?></div>
                <div class="stat-label">Access Level</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-file-text"></i></div>
                <div class="stat-value"><?php echo $po_id; ?></div>
                <div class="stat-label">Testing PO ID</div>
            </div>
        </div>
        
        <!-- PO ID Selector -->
        <div class="test-section">
            <h2><i class="fa fa-search"></i> Select PO to Test</h2>
            <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="number" name="po_id" value="<?php echo $po_id; ?>" 
                       style="padding: 10px 15px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 14px; width: 150px;"
                       placeholder="Enter PO ID">
                <button type="submit" class="btn-dev btn-primary"><i class="fa fa-search"></i> Check Access</button>
            </form>
            
            <div style="margin-top: 15px;">
                <strong style="color: #666;">Recent POs:</strong>
                <?php while ($rpo = mysqli_fetch_assoc($recent_pos)): ?>
                    <a href="?po_id=<?php echo $rpo['id']; ?>" 
                       class="btn-dev btn-outline" style="padding: 5px 10px; margin: 2px;">
                        #<?php echo $rpo['id']; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        
        <?php if ($po_data): ?>
        <!-- Access Check Result -->
        <div class="test-section">
            <h2><i class="fa fa-lock"></i> Access Check Result</h2>
            
            <?php if ($access_check['has_access']): ?>
                <div class="info-box success">
                    <i class="fa fa-check-circle"></i>
                    <div>
                        <strong>ACCESS GRANTED</strong><br>
                        Your company ID (<?php echo $session_com; ?>) has access to this invoice.
                        <?php if ($access_check['is_super_admin']): ?>
                            <br><em>Note: You have Super Admin access.</em>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-box danger">
                    <i class="fa fa-times-circle"></i>
                    <div>
                        <strong>ACCESS DENIED</strong><br>
                        Your company ID (<?php echo $session_com ?: 'NOT SET'; ?>) does not have access to this invoice.
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <h3 style="margin-bottom: 15px;">Required Company IDs:</h3>
                <div class="stats-grid">
                    <div class="stat-card <?php echo $session_com == $po_data['cus_id'] ? 'success' : ''; ?>">
                        <div class="stat-icon"><i class="fa fa-user"></i></div>
                        <div class="stat-value"><?php echo $po_data['cus_id'] ?: 'N/A'; ?></div>
                        <div class="stat-label">Customer ID</div>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;"><?php echo htmlspecialchars($po_data['cus_name'] ?? ''); ?></div>
                    </div>
                    <div class="stat-card <?php echo $session_com == $po_data['ven_id'] ? 'success' : ''; ?>">
                        <div class="stat-icon"><i class="fa fa-truck"></i></div>
                        <div class="stat-value"><?php echo $po_data['ven_id'] ?: 'N/A'; ?></div>
                        <div class="stat-label">Vendor ID</div>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;"><?php echo htmlspecialchars($po_data['ven_name'] ?? ''); ?></div>
                    </div>
                    <div class="stat-card <?php echo $session_com == $po_data['payby'] ? 'success' : ''; ?>">
                        <div class="stat-icon"><i class="fa fa-credit-card"></i></div>
                        <div class="stat-value"><?php echo $po_data['payby'] ?: 'N/A'; ?></div>
                        <div class="stat-label">Pay By ID</div>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;"><?php echo htmlspecialchars($po_data['payby_name'] ?? ''); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PO Details -->
        <div class="test-section">
            <h2><i class="fa fa-info-circle"></i> PO Details</h2>
            <ul class="kv-list">
                <li class="kv-item">
                    <span class="kv-key">PO ID</span>
                    <span class="kv-value"><?php echo $po_data['po_id']; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">PO Name</span>
                    <span class="kv-value"><?php echo htmlspecialchars($po_data['po_name']); ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">PO Date</span>
                    <span class="kv-value"><?php echo $po_data['po_date']; ?></span>
                </li>
                <li class="kv-item">
                    <span class="kv-key">PR Reference</span>
                    <span class="kv-value"><?php echo $po_data['pr_id']; ?></span>
                </li>
            </ul>
        </div>
        
        <!-- Raw Data -->
        <div class="test-section">
            <h2><i class="fa fa-code"></i> Raw Data</h2>
            <pre class="code-block"><?php echo format_json_html($po_data); ?></pre>
        </div>
        
        <?php else: ?>
        <div class="info-box warning">
            <i class="fa fa-exclamation-triangle"></i>
            <div><strong>PO #<?php echo $po_id; ?> not found.</strong> Please enter a valid PO ID.</div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="?" class="btn-dev btn-primary"><i class="fa fa-refresh"></i> Refresh</a>
            <a href="index.php?page=dashboard" class="btn-dev btn-outline"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
