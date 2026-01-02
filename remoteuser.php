<?php 
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
$db = new DbConn($config);

// Only Admin and Super Admin can switch companies
$userLevel = isset($_SESSION['user_level']) ? intval($_SESSION['user_level']) : 0;
if ($userLevel < 1) {
    // Normal users cannot switch companies
    echo "<script>alert('Access denied. You can only access your assigned company.');window.location='index.php';</script>";
    exit;
}

// Handle clear company (back to admin panel)
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    $_SESSION['com_id'] = "";
    $_SESSION['com_name'] = "";
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
}

// Handle quick company selection from dashboard
if (isset($_GET['select_company'])) {
    $company_id = sql_int($_GET['select_company']);
    $sql = "SELECT name_en, name_sh FROM company WHERE id='" . $company_id . "' AND deleted_at IS NULL";
    $result = mysqli_query($db->conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $comname = mysqli_fetch_array($result);
        $_SESSION['com_id'] = $company_id;
        $_SESSION['com_name'] = $comname['name_en'] ?: $comname['name_sh'];
    }
    echo "<script>window.location='index.php?page=dashboard';</script>";
    exit;
}

// Toggle company (legacy behavior) or set from id parameter
if ($_SESSION['com_id'] == "") {
    $company_id = sql_int($_GET['id']);
    $sql = "SELECT name_en, name_sh FROM company WHERE id='" . $company_id . "'";
    $comname = mysqli_fetch_array(mysqli_query($db->conn, $sql));
    $_SESSION['com_id'] = $company_id;
    $_SESSION['com_name'] = $comname['name_en'];
} else {
    $_SESSION['com_id'] = "";
    $_SESSION['com_name'] = "";
}

echo "<script>window.location='index.php';</script>";
?>
