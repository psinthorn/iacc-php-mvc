
<?php
	// require_once("inc/sys.configs.php");
	// require_once("inc/class.dbconn.php");
require_once("inc/security.php");
	// $db = new DbConn($config);
	// // Security already checked in index.php
?>

<h2><i class="fa fa-home fa-fw"></i> <?=$xml->company?></h2>
<?php
$com_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;

if($com_id > 0){
	// For logged-in company user: show self + business partners (vendors and customers from transactions)
	$sql = "SELECT DISTINCT c.id, c.name_en, c.contact, c.vender, c.customer, c.email, c.phone,
	        CASE 
	            WHEN c.id = $com_id THEN 'self'
	            WHEN EXISTS (SELECT 1 FROM pr WHERE pr.ven_id = c.id AND pr.cus_id = $com_id) THEN 'vendor'
	            WHEN EXISTS (SELECT 1 FROM pr WHERE pr.cus_id = c.id AND pr.ven_id = $com_id) THEN 'customer'
	            ELSE 'partner'
	        END as relationship
	        FROM company c
	        WHERE c.deleted_at IS NULL AND (
	            c.id = $com_id
	            OR c.id IN (SELECT DISTINCT ven_id FROM pr WHERE cus_id = $com_id)
	            OR c.id IN (SELECT DISTINCT cus_id FROM pr WHERE ven_id = $com_id)
	        )
	        ORDER BY 
	            CASE WHEN c.id = $com_id THEN 0 ELSE 1 END,
	            c.name_en ASC";
} else {
	// Admin/Super Admin: show all companies
	$sql = "SELECT id, name_en, vender, customer, contact, email, phone, 'all' as relationship 
	        FROM company 
	        WHERE deleted_at IS NULL
	        ORDER BY id DESC";
}

$query = mysqli_query($db->conn, $sql);
$owner = "";
$other = "";
?>

<div id="fetch_state"></div>
<table width="100%" class="table table-hover">
<tr>
    <th><?=$xml->name?></th>
    <th><?=$xml->contact?></th>
    <th><?=$xml->email?></th>
    <th><?=$xml->phone?></th>
    <?php if($com_id > 0): ?><th>Relationship</th><?php endif; ?>
    <th width="150">
        <?php if($com_id == 0):?>
        <a href="#" onclick="ajaxpagefetcher.load('fetch_state', 'company.php', true);"><span class="glyphicon glyphicon-plus"></span> <?=$xml->create?></a>
        <?php endif;?>
    </th>
</tr>
<?php while($data=mysqli_fetch_array($query)){
    $isSelf = ($com_id > 0 && $data['id'] == $com_id);
    $relationship = isset($data['relationship']) ? $data['relationship'] : '';
    
    // Relationship badge
    $relationBadge = '';
    if ($com_id > 0) {
        switch($relationship) {
            case 'self':
                $relationBadge = '<span class="label label-primary">Your Company</span>';
                break;
            case 'vendor':
                $relationBadge = '<span class="label label-info">Vendor</span>';
                break;
            case 'customer':
                $relationBadge = '<span class="label label-success">Customer</span>';
                break;
            default:
                $relationBadge = '<span class="label label-default">Partner</span>';
        }
    }
    
    $row = "<tr" . ($isSelf ? " class='info'" : "") . "><td>" . htmlspecialchars($data['name_en']) . "</td>
<td>" . htmlspecialchars($data['contact']) . "</td>	
<td>" . htmlspecialchars($data['email']) . "</td>	
<td>" . htmlspecialchars($data['phone']) . "</td>";
    
    if ($com_id > 0) {
        $row .= "<td>$relationBadge</td>";
    }
    
    $row .= "<td>";
    if($com_id == 0) { 
        $row .= "<a href='remoteuser.php?id=".$data['id']."'><i class=\"fa fa-share-square\"></i></a>&nbsp;&nbsp;&nbsp;";
    }
    $row .= "<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company.php?id=".$data['id']."', true);\"><i class=\"glyphicon glyphicon-pencil\"></i></a>&nbsp;&nbsp;&nbsp;";
    $row .= "<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'company-addr.php?id=".$data['id']."', true);\"><i class=\"fa fa-truck\"></i></a>&nbsp;&nbsp;&nbsp;";
    $row .= "<a href=\"#\" onclick=\"ajaxpagefetcher.load('fetch_state', 'credit-list.php?id=".$data['id']."', true);\"><i class=\"fa fa-credit-card\"></i></a>";
    
    // Only show delete for admin
    if ($com_id == 0) {
        $row .= "&nbsp;&nbsp;&nbsp;<a onClick='return Conf(this)' href=\"#\"><span class=\"glyphicon glyphicon-trash\"></span></a>";
    }
    $row .= "</td></tr>";
    
    if(($data['vender']=="1")&&($data['customer']=="1")){
        $owner .= $row;
    } else {
        $other .= $row;
    }
}?>
<?=$owner?>
<tr><td colspan="<?= $com_id > 0 ? '6' : '5' ?>" height="30"><br><b><?=$xml->other?></b></td></tr>
<?=$other?>
</table>