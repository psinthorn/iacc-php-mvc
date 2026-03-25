<?php session_start();

// Debug: Log all incoming requests to core-function.php
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
$logFile = $logDir . '/app.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " CORE-FUNCTION: page=" . ($_REQUEST['page'] ?? 'NOT SET') . ", method=" . ($_REQUEST['method'] ?? 'NOT SET') . "\n", FILE_APPEND);

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");

// CSRF protection for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " CORE-FUNCTION: CSRF FAILED\n", FILE_APPEND);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}

// Initialize company filter for multi-tenant queries
$companyFilter = CompanyFilter::getInstance();

$users=new DbConn($config);
$db = $users; // Alias for compatibility with legacy code
// Security already checked in index.php

$har=new HardClass();
$har->setConnection($db->conn); // Explicitly set connection
$har->keeplog($_REQUEST);
switch($_REQUEST['page']){	
	
// case "company" — MIGRATED to App\Controllers\CompanyController (Phase 2E)
// case "type" — MIGRATED to App\Controllers\TypeController (Phase 2C)	
// case "category" — MIGRATED to App\Controllers\CategoryController (Phase 2B)


case "compl_list" : {
	if($_REQUEST['method']=="C"){
		$args['table']="pay";
	// pay table columns: id, company_id, po_id, method, value, volumn, date, deleted_at
	$args['value']="NULL,'".$_SESSION['com_id']."','".sql_int($_REQUEST['po_id'])."','".sql_escape($_REQUEST['payment'])."','".sql_escape($_REQUEST['remark'])."','".sql_escape($_REQUEST['volumn'])."','".date("Y-m-d")."',NULL";
	
	$har->insertDB($args);	
		}
		exit("<script>window.location = 'index.php?page=compl_view&id=".sql_int($_REQUEST['po_id'])."'</script>");break;
}break;

case "compl_view" : {
	if($_REQUEST['method']=="S"){
		$args['table']="pr";
	$args['value']="payby='".sql_escape($_REQUEST['payby'])."'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);
		}
		exit("<script>window.location = 'index.php?page=compl_view&id=".sql_int($_REQUEST['id'])."'</script>");break;
}break;



case "compl_list2" : {
	
	if($_REQUEST['method']=="V"){	
	$po_id=mysqli_fetch_array(mysqli_query($db->conn, "select po.id as po_id,ven_id from pr join po on pr.id=po.ref where po_id_new='' and pr.id='".sql_int($_REQUEST['id'])."'"));
		$args2['table']="iv";
	$args2['value']="status_iv='2'";
	$args2['condition']="tex='".sql_int($po_id['po_id'])."'";
	
	$har->updateDb($args2);
		}
	if($_REQUEST['method']=="C"){
	$args['table']="pr";
	$args['value']="status='5'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."'";
	$har->updateDb($args);
	
	 $argsiv['table']="iv";
	$po_id=mysqli_fetch_array(mysqli_query($db->conn, "select po.id as po_id,ven_id from pr join po on pr.id=po.ref where po_id_new='' and pr.id='".sql_int($_REQUEST['id'])."'"));
	 $maxtaxiv=mysqli_fetch_array(mysqli_query($db->conn, "select max(texiv) as max_id from iv where cus_id='".$po_id['ven_id']."'"));
	
	
	$args2['table']="iv";
	$args2['value']="texiv='".(number_format($maxtaxiv['max_id'])+1)."',texiv_rw='".(date("y")+43).str_pad(($maxtaxiv['max_id']+1), 6, '0', STR_PAD_LEFT)."',texiv_create='".date("Y-m-d")."',status_iv='1'";
	$args2['condition']="tex='".$po_id['po_id']."'";
	$har->updateDb($args2);
		}
}break;


		
		
		
case "payment" : {
	if($_REQUEST['method']=="A"){
		$args['table']="payment";
	$args['value']="NULL,'".sql_escape($_REQUEST['payment_name'])."','".sql_escape($_REQUEST['payment_des'])."','".$_SESSION['com_id']."',NULL";
	$har->insertDB($args);	
		}
	else if($_REQUEST['method']=="E"){
	$args['table']="payment";
	$args['value']="payment_name='".sql_escape($_REQUEST['payment_name'])."',payment_des='".sql_escape($_REQUEST['payment_des'])."'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."'";
	$har->updateDb($args);	
		}
}break;


	
		
// case "mo_list" — MIGRATED to App\Controllers\ModelController (Phase 2C)

// case "brand" — MIGRATED to App\Controllers\BrandController (Phase 2C)
case "pr_list" : {
	$args['table']="pr";
	// Debug logging (uses $logFile defined at top of core-function.php)
	file_put_contents($logFile, date('Y-m-d H:i:s') . " PR_LIST: method=" . ($_REQUEST['method'] ?? 'NOT SET') . "\n", FILE_APPEND);
	
	if($_REQUEST['method']=="D"){
		$args['table']="pr";
		$args['value']="cancel='1'";
		$pr_id = intval($_REQUEST['id']);
		$com_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;
		
		// Admin (com_id=0) can delete any PR, regular users can only delete their own
		if ($com_id > 0) {
			$args['condition']="id='".$pr_id."' and (ven_id='".$com_id."' or cus_id='".$com_id."')";
		} else {
			// Admin mode - allow delete without company restriction
			$args['condition']="id='".$pr_id."'";
		}
		
		file_put_contents($logFile, date('Y-m-d H:i:s') . " PR DELETE: id=$pr_id, com_id=$com_id, condition=" . $args['condition'] . "\n", FILE_APPEND);
		$har->updateDb($args);
		// Redirect back to pr_list after delete
		header("Location: index.php?page=pr_list");
		exit;
		
	}else
	if($_REQUEST['method']=="A"){
	// Include company_id for multi-tenant and deleted_at as NULL
	$owner_company_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;
	// Ensure ven_id is properly set - use session company if form value is empty
	$ven_id = isset($_REQUEST['ven_id']) && $_REQUEST['ven_id'] !== '' ? intval($_REQUEST['ven_id']) : $owner_company_id;
	// Escape user inputs for safety
	$pr_name = sql_escape($_REQUEST['name']);
	$pr_des = sql_escape($_REQUEST['des']);
	$cus_id = intval($_REQUEST['cus_id']);
	$user_id = intval($_SESSION['user_id']);
	$args['value']="'".$owner_company_id."','".$pr_name."','".$pr_des."','".$user_id."','".$cus_id."','".$ven_id."','".date('Y-m-d')."','0','0','0','0',NULL";
	
	file_put_contents($logFile, date('Y-m-d H:i:s') . " PR INSERT: table=" . $args['table'] . ", value=" . $args['value'] . "\n", FILE_APPEND);
	
	 $pr_id=$har->insertDbMax($args);
	 
	 file_put_contents($logFile, date('Y-m-d H:i:s') . " PR INSERT RESULT: pr_id=" . $pr_id . "\n", FILE_APPEND);
	 
	 // Debug: Log what's being received for product rows
	 error_log("PR INSERT: pr_id=$pr_id");
	 for($i=0;$i<9;$i++){
		 $type_id = isset($_REQUEST['id'.$i]) ? $_REQUEST['id'.$i] : 'NOT_SET';
		 $qty = isset($_REQUEST['quantity'.$i]) ? $_REQUEST['quantity'.$i] : 'NOT_SET';
		 $price = isset($_REQUEST['price'.$i]) ? $_REQUEST['price'.$i] : 'NOT_SET';
		 error_log("  Row $i: type_id=$type_id, qty=$qty, price=$price");
		 
		 if(($_REQUEST['id'.$i]!="0")&&($_REQUEST['id'.$i]!="")&&($_REQUEST['quantity'.$i]!="0")){
		 	$args['table']="tmp_product";
		// Use NULL for auto-increment id instead of empty string
		$args['value']="NULL,'".$pr_id."','".$_REQUEST['id'.$i]."','".$_REQUEST['quantity'.$i]."','".$_REQUEST['price'.$i]."'";
		error_log("  INSERTING: " . $args['value']);
		$har->insertDB($args);
		 }
		 }
	 
	 
		}
}break;
case "po_list" : {
	$args['table']="po";
	if($_REQUEST['method']=="D"){
		$po_id = sql_int($_REQUEST['id']);
		$dataref=mysqli_fetch_array(mysqli_query($db->conn, "select ref,status from po join pr on po.ref=pr.id where po.id='".$po_id."'"));
		$args['table']="pr";
		$args['value']="cancel='1'";
		$com_id = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' ? intval($_SESSION['com_id']) : 0;
		
		// Admin (com_id=0) can delete any, regular users can only delete their own
		if ($com_id > 0) {
			$args['condition']="id='".$dataref['ref']."' and (ven_id='".$com_id."' or cus_id='".$com_id."')";
		} else {
			$args['condition']="id='".$dataref['ref']."'";
		}
		$har->updateDb($args);
		if($dataref['status']=="1"){
			header("Location: index.php?page=qa_list");
			exit;
		}
		
		}else
	if($_REQUEST['method']=="A"){
	// ============================================================
	// PO CREATE (method=A) - Create new PO with products
	// Each operation uses isolated $args to prevent state leakage
	// ============================================================
	
	// 1. Create new PO record
	$argsPO = array();
	$argsPO['table'] = "po";
	$newPoId = $har->Maxid($argsPO['table']);
	$taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);
	
	$argsPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
	$argsPO['value'] = "'" . intval($_SESSION['com_id']) . "', '', '" . mysqli_real_escape_string($db->conn, $_REQUEST['name']) . "', '" . intval($_REQUEST['ref']) . "', '" . $taxNumber . "', '" . date('Y-m-d') . "', '" . date("Y-m-d", strtotime($_REQUEST['valid_pay'])) . "', '" . date("Y-m-d", strtotime($_REQUEST['deliver_date'])) . "', '', '', '" . floatval($_REQUEST['dis'] ?? 0) . "', '" . intval($_REQUEST['brandven'] ?? 0) . "', '" . floatval($_REQUEST['vat'] ?? 0) . "', '" . floatval($_REQUEST['over'] ?? 0) . "', NULL";
	
	$createdPoId = $har->insertDbMax($argsPO);
	
	// 2. Update PR status
	$argsPR = array();
	$argsPR['table'] = "pr";
	$argsPR['value'] = "status='1'";
	$argsPR['condition'] = "id='" . intval($_REQUEST['ref']) . "'";
	$har->updateDb($argsPR);
	
	// 3. Insert products for new PO
	if(isset($_REQUEST['type']) && is_array($_REQUEST['type']) && count($_REQUEST['type']) > 0) {
		$i = 0;
		foreach ($_REQUEST['type'] as $typeValue) {
			$argsProduct = array(); // Fresh array for each product
			$argsProduct['table'] = "product";
			
			$a_labour = !empty($_REQUEST['a_labour'][$i]) ? intval($_REQUEST['a_labour'][$i]) : 0;
			$v_labour = !empty($_REQUEST['v_labour'][$i]) ? floatval($_REQUEST['v_labour'][$i]) : 0;
			$des = isset($_REQUEST['des'][$i]) ? mysqli_real_escape_string($db->conn, $_REQUEST['des'][$i]) : '';
			$price = !empty($_REQUEST['price'][$i]) ? floatval($_REQUEST['price'][$i]) : 0;
			$model = !empty($_REQUEST['model'][$i]) ? intval($_REQUEST['model'][$i]) : 0;
			$quantity = !empty($_REQUEST['quantity'][$i]) ? floatval($_REQUEST['quantity'][$i]) : 1;
			$ban_id = !empty($_REQUEST['ban_id'][$i]) ? intval($_REQUEST['ban_id'][$i]) : 0;
			
			$argsProduct['value'] = "NULL, '" . intval($_SESSION['com_id']) . "', '" . intval($createdPoId) . "', '" . $price . "', '0', '" . $ban_id . "', '" . $model . "', '" . intval($typeValue) . "', '" . $quantity . "', '1', '0', '" . $des . "', '" . $a_labour . "', '" . $v_labour . "', '0', '1970-01-01', '0', NULL";
			$har->insertDB($argsProduct);
			$i++;
		}
	}
	
	$_REQUEST['page']="qa_list";
		}else if($_REQUEST['method']=="E"){
	// ============================================================
	// PO EDIT (method=E) - Create new PO version with products
	// Each operation uses isolated $args to prevent state leakage
	// ============================================================
	
	$_REQUEST['page']="qa_list";
	
	// 1. Update PR with customer ID
	$argsPR = array();
	$argsPR['table'] = "pr";
	$argsPR['value'] = "cus_id='" . mysqli_real_escape_string($db->conn, $_REQUEST['cus_id'] ?? '') . "'";
	$argsPR['condition'] = "id='" . intval($_REQUEST['ref']) . "' AND ven_id='" . intval($_SESSION['com_id']) . "'";
	$har->updateDb($argsPR);
	
	// 2. Create new PO record
	$argsPO = array();
	$argsPO['table'] = "po";
	$newPoId = $har->Maxid($argsPO['table']);
	$taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);
	
	$argsPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
	$argsPO['value'] = "'" . intval($_SESSION['com_id']) . "', '', '" . mysqli_real_escape_string($db->conn, $_REQUEST['name']) . "', '" . intval($_REQUEST['ref']) . "', '" . $taxNumber . "', '" . date("Y-m-d", strtotime($_REQUEST['create_date'])) . "', '" . date("Y-m-d", strtotime($_REQUEST['valid_pay'])) . "', '" . date("Y-m-d", strtotime($_REQUEST['deliver_date'])) . "', '', '', '" . floatval($_REQUEST['dis'] ?? 0) . "', '" . intval($_REQUEST['brandven'] ?? 0) . "', '" . floatval($_REQUEST['vat'] ?? 0) . "', '" . floatval($_REQUEST['over'] ?? 0) . "', NULL";
	
	$createdPoId = $har->insertDbMax($argsPO);
	
	// 3. Update old PO to point to new version
	$argsOldPO = array();
	$argsOldPO['table'] = "po";
	$argsOldPO['value'] = "po_id_new='" . intval($createdPoId) . "'";
	$argsOldPO['condition'] = "id='" . intval($_REQUEST['id']) . "'";
	$har->updateDb($argsOldPO);
	
	// 4. Insert products for new PO
	if(isset($_REQUEST['type']) && is_array($_REQUEST['type']) && count($_REQUEST['type']) > 0) {
		foreach ($_REQUEST['type'] as $key => $typeValue) {
			$argsProduct = array(); // Fresh array for each product
			$argsProduct['table'] = "product";
			
			$price = !empty($_REQUEST['price'][$key]) ? floatval($_REQUEST['price'][$key]) : 0;
			$discount = !empty($_REQUEST['discount'][$key]) ? floatval($_REQUEST['discount'][$key]) : 0;
			$ban_id = !empty($_REQUEST['ban_id'][$key]) ? intval($_REQUEST['ban_id'][$key]) : 0;
			$model = !empty($_REQUEST['model'][$key]) ? intval($_REQUEST['model'][$key]) : 0;
			$quantity = !empty($_REQUEST['quantity'][$key]) ? floatval($_REQUEST['quantity'][$key]) : 1;
			$pack_quantity = !empty($_REQUEST['pack_quantity'][$key]) ? floatval($_REQUEST['pack_quantity'][$key]) : 1;
			$des = isset($_REQUEST['des'][$key]) ? mysqli_real_escape_string($db->conn, $_REQUEST['des'][$key]) : '';
			$a_labour = !empty($_REQUEST['a_labour'][$key]) ? intval($_REQUEST['a_labour'][$key]) : 0;
			$v_labour = !empty($_REQUEST['v_labour'][$key]) ? floatval($_REQUEST['v_labour'][$key]) : 0;
			
			$argsProduct['value'] = "NULL, '" . intval($_SESSION['com_id']) . "', '" . intval($createdPoId) . "', '" . $price . "', '" . $discount . "', '" . $ban_id . "', '" . $model . "', '" . intval($typeValue) . "', '" . $quantity . "', '" . $pack_quantity . "', '0', '" . $des . "', '" . $a_labour . "', '" . $v_labour . "', '0', '1970-01-01', '0', NULL";
			$har->insertDB($argsProduct);
		}
	}
	
		}else if($_REQUEST['method']=="C"){
			
	$namefile = '';
	$type = '';
	
	// Handle file upload if file was provided
	if(!empty($_FILES["file"]["name"]) && $_FILES["file"]["error"] == 0) {
		$temp = explode(".", $_FILES["file"]["name"]);
		$extension = strtolower(end($temp));
		$allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
		
		if(in_array($extension, $allowed_types) && $_FILES["file"]["size"] < 10000000) {
			$type = $extension;
			$namefile = md5(date("Y:m:d:h:m:s").rand()); 
			move_uploaded_file($_FILES["file"]["tmp_name"], "upload/".$namefile.".".$type);
		}
	}
	
	// Update PO record with file and po_ref
	$args['table']="po";
	$po_ref = isset($_REQUEST['po_ref']) ? mysqli_real_escape_string($db->conn, $_REQUEST['po_ref']) : '';
	
	if(!empty($namefile)) {
		$args['value']="pic='".$namefile.".".$type."', po_ref='".$po_ref."'";
	} else {
		$args['value']="po_ref='".$po_ref."'";
	}
	$args['condition']="po_id_new='' and ref='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);			
	
	$args['table']="pr";
	$args['value']="status='2'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);	
		}
		
}break;


case "receipt_list" : {
	$args['table']="receipt";
	if($_REQUEST['method']=="D"){
		
		
		
		}else
	if($_REQUEST['method']=="A"){
	$id=$har->Maxid($args['table']);
	$max_no=mysqli_fetch_array(mysqli_query($db->conn, "select max(rep_no) as maxrep from receipt where vender='".$_SESSION['com_id']."'"));
	$new_rw=$max_no['maxrep']+1;
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_val = $invoice_id === NULL ? 'NULL' : "'".$invoice_id."'";
	
	// New fields for quotation support
	$quotation_id = isset($_REQUEST['quotation_id']) && !empty($_REQUEST['quotation_id']) ? intval($_REQUEST['quotation_id']) : NULL;
	$quotation_id_val = $quotation_id === NULL ? 'NULL' : "'".$quotation_id."'";
	$source_type = isset($_REQUEST['source_type']) ? mysqli_real_escape_string($db->conn, $_REQUEST['source_type']) : 'manual';
	$include_vat = isset($_REQUEST['include_vat']) ? 1 : 0;
	$payment_ref = isset($_REQUEST['payment_ref']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_ref']) : '';
	$payment_date = isset($_REQUEST['payment_date']) && !empty($_REQUEST['payment_date']) ? date('Y-m-d', strtotime($_REQUEST['payment_date'])) : date('Y-m-d');
	
	// Build insert query with all fields
	$sql = "INSERT INTO receipt (name, phone, email, createdate, description, payment_method, payment_ref, payment_date, status, invoice_id, quotation_id, source_type, include_vat, vender, rep_no, rep_rw, brand, vat, dis, deleted_at) 
	        VALUES ('".sql_escape($_REQUEST['name'])."', '".sql_escape($_REQUEST['phone'])."', '".sql_escape($_REQUEST['email'])."', '".date("Y-m-d")."', '".sql_escape($_REQUEST['des'])."', '".$payment_method."', '".$payment_ref."', '".$payment_date."', '".$status."', ".$invoice_id_val.", ".$quotation_id_val.", '".$source_type."', '".$include_vat."', '".$_SESSION['com_id']."', '".$new_rw."', '".(date("y")+43).str_pad($new_rw, 6, '0', STR_PAD_LEFT)."', '".sql_int($_REQUEST['brandven'])."', '".sql_escape($_REQUEST['vat'])."', '".sql_escape($_REQUEST['dis'])."', NULL)";
	mysqli_query($db->conn, $sql);
	$rep_id = mysqli_insert_id($db->conn);
	
	// Only insert products if not linked to invoice/quotation (manual entry)
	if ($source_type == 'manual' && isset($_REQUEST['type']) && is_array($_REQUEST['type'])) {
		$args['table']="product";
		$i=0;
		foreach ($_REQUEST['type'] as $type) {
			$args['value']="NULL,'".$_SESSION['com_id']."','0','".floatval($_REQUEST['price'][$i])."','0','".intval($_REQUEST['ban_id'][$i])."','".intval($_REQUEST['model'][$i])."','".intval($type)."','".floatval($_REQUEST['quantity'][$i])."','1','0','".sql_escape($_REQUEST['des'][$i])."','".intval($_REQUEST['a_labour'][$i])."','".floatval($_REQUEST['v_labour'][$i])."','0','".date("Y-m-d",strtotime($_REQUEST['warranty'][$i]))."','".$rep_id."',NULL";
			$har->insertDB($args);	
			$i++;
		}
	}
	
		}else if($_REQUEST['method']=="E"){
		
		$args['table']="receipt";
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_sql = $invoice_id === NULL ? 'invoice_id=NULL' : "invoice_id='".$invoice_id."'";
	
	// New fields for quotation support (edit mode)
	$quotation_id = isset($_REQUEST['quotation_id']) && !empty($_REQUEST['quotation_id']) ? intval($_REQUEST['quotation_id']) : NULL;
	$quotation_id_sql = $quotation_id === NULL ? 'quotation_id=NULL' : "quotation_id='".$quotation_id."'";
	$source_type = isset($_REQUEST['source_type']) ? mysqli_real_escape_string($db->conn, $_REQUEST['source_type']) : 'manual';
	$include_vat = isset($_REQUEST['include_vat']) ? 1 : 0;
	$payment_ref = isset($_REQUEST['payment_ref']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_ref']) : '';
	$payment_date = isset($_REQUEST['payment_date']) && !empty($_REQUEST['payment_date']) ? date('Y-m-d', strtotime($_REQUEST['payment_date'])) : date('Y-m-d');
	
	$sql = "UPDATE receipt SET 
		name='".sql_escape($_REQUEST['name'])."',
		phone='".sql_escape($_REQUEST['phone'])."',
		email='".sql_escape($_REQUEST['email'])."',
		description='".sql_escape($_REQUEST['des'])."',
		brand='".sql_int($_REQUEST['brandven'])."',
		vat='".sql_escape($_REQUEST['vat'])."',
		dis='".sql_escape($_REQUEST['dis'])."',
		payment_method='".$payment_method."',
		payment_ref='".$payment_ref."',
		payment_date='".$payment_date."',
		status='".$status."',
		".$invoice_id_sql.",
		".$quotation_id_sql.",
		source_type='".$source_type."',
		include_vat='".$include_vat."'
		WHERE id='".sql_int($_REQUEST['id'])."' AND vender='".$_SESSION['com_id']."'";
	mysqli_query($db->conn, $sql);
	
	// Only update products for manual entry
	if ($source_type == 'manual' && isset($_REQUEST['type']) && is_array($_REQUEST['type'])) {
		$args['table']="product";
		mysqli_query($db->conn, "delete from product where re_id='".sql_int($_REQUEST['id'])."' and po_id='0' and so_id='0'");
		foreach ($_REQUEST['type'] as $key => $type) {
			$args['value']="'','0','".$_REQUEST['price'][$key]."','0','".$_REQUEST['ban_id'][$key]."','".$_REQUEST['model'][$key]."','".$type."','".$_REQUEST['quantity'][$key]."','1','','".$_REQUEST['des'][$key]."','".$_REQUEST['a_labour'][$key]."','".$_REQUEST['v_labour'][$key]."','','".date("Y-m-d",strtotime($_REQUEST['warranty'][$key]))."','".$_REQUEST['id']."'";
			$har->insertDB($args);	
		}
	}
		


		}
		
	
		
		
		}break;

case "voucher_list" : {
	$args['table']="voucher";
	if($_REQUEST['method']=="D"){
		
		
		
		}else
	if($_REQUEST['method']=="A"){
	$id=$har->Maxid($args['table']);
	$max_no=mysqli_fetch_array(mysqli_query($db->conn, "select max(vou_no) as maxvou from voucher where vender='".$_SESSION['com_id']."'"));
	$new_rw=$max_no['maxvou']+1;
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_val = $invoice_id === NULL ? 'NULL' : "'".$invoice_id."'";
	
	// Column order: id, name, phone, email, createdate, description, payment_method, status, invoice_id, vender, vou_no, vou_rw, brand, vat, discount, deleted_at
	$args['value']="'".sql_escape($_REQUEST['name'])."','".sql_escape($_REQUEST['phone'])."','".sql_escape($_REQUEST['email'])."','".date("Y-m-d")."','".sql_escape($_REQUEST['des'])."','".$payment_method."','".$status."',".$invoice_id_val.",'".$_SESSION['com_id']."','".$new_rw."','".(date("y")+43).str_pad($new_rw, 6, '0', STR_PAD_LEFT)."','".sql_int($_REQUEST['brandven'])."','".sql_escape($_REQUEST['vat'])."','".sql_escape($_REQUEST['dis'])."',NULL";
	 
	$vou_id=$har->insertDbMax($args);
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST['type'] as $type) {
		
		$args['value']="'','0','".$_REQUEST['price'][$i]."','0','".$_REQUEST['ban_id'][$i]."','".$_REQUEST['model'][$i]."','".$type."','".$_REQUEST['quantity'][$i]."','1','','".$_REQUEST['des'][$i]."','".$_REQUEST['a_labour'][$i]."','".$_REQUEST['v_labour'][$i]."','".$vou_id."','".date("Y-m-d",strtotime($_REQUEST['warranty'][$i]))."',''";
		$har->insertDB($args);	
		$i++;
		}
	
		}else if($_REQUEST['method']=="E"){
		
		$args['table']="voucher";
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_sql = $invoice_id === NULL ? 'invoice_id=NULL' : "invoice_id='".$invoice_id."'";
	
	$args['value']="name='".sql_escape($_REQUEST['name'])."',phone='".sql_escape($_REQUEST['phone'])."',email='".sql_escape($_REQUEST['email'])."',description='".sql_escape($_REQUEST['des'])."',brand='".sql_int($_REQUEST['brandven'])."',vat='".sql_escape($_REQUEST['vat'])."',discount='".sql_escape($_REQUEST['dis'])."',payment_method='".$payment_method."',status='".$status."',".$invoice_id_sql;
			
			
	$args['condition']="id='".sql_int($_REQUEST['id'])."' and vender='".$_SESSION['com_id']."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
	mysqli_query($db->conn, "delete from product where vo_id='".sql_int($_REQUEST['id'])."' and po_id='0' and so_id='0'");
	foreach ($_REQUEST['type'] as $type) {
		
		$args['value']="'','0','".$_REQUEST['price'][$i]."','0','".$_REQUEST['ban_id'][$i]."','".$_REQUEST['model'][$i]."','".$type."','".$_REQUEST['quantity'][$i]."','1','','".$_REQUEST['des'][$i]."','".$_REQUEST['a_labour'][$i]."','".$_REQUEST['v_labour'][$i]."','".$_REQUEST['id']."','".date("Y-m-d",strtotime($_REQUEST['warranty'][$i]))."',''";
		$har->insertDB($args);	
		$i++;
		}break;
	
	
		}else if($_REQUEST['method']=="C"){
			
			
   $temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
if (( ($_FILES["file"]["type"] == "application/pdf")
|| ($_FILES["file"]["type"] == "image/jpg"))
&& ($_FILES["file"]["size"] < 5000000))
  {
	if($_FILES["file"]["type"] == "application/pdf")$type="pdf";else $type="jpg"; 
	$namefile=md5(date("Y:m:d:h:m:s").rand()); 
  move_uploaded_file($_FILES["file"]["tmp_name"],
      "upload/".$namefile.".".$type);
    
  }
	$args['table']="po";
	$args['value']="pic='". $namefile.".".$type."'";
	$args['condition']="po_id_new='' and ref='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);			
	
	$args['table']="pr";
	$args['value']="status='2'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);	
		}
		
}break;

case "deliv_list" : {
	$args['table']="store";
	$args2['table']="store_sale";
	$args3['table']="deliver";
	$ctsn=count($_REQUEST['sn'] ?? []);
$flag=0;
	for($i=0;$i<$ctsn;$i++){
		for($j=$i;$j<$ctsn;$j++){
		if($i!=$j){
		
		if($_REQUEST['sn'][$i]==$_REQUEST['sn'][$j]){
		$flag++;}
		
		}
		}
		}
		
	if(($_REQUEST['method']=="c")&&($flag==0)){
	$ci=0;
	
		
	foreach ($_REQUEST['sn'] as $sn) {	
	if($sn==""){
		$ms=mysqli_fetch_array(mysqli_query($db->conn, "select max(id) as ms from gen_serial"));
		$sn=$ms['ms']+1;
		}
	
	// SECURITY FIX: Add company filter via store_sale owner check
$maxno=mysqli_fetch_array(mysqli_query($db->conn, "select max(s.no) as maxno from store s join product p on s.pro_id=p.pro_id join store_sale ss on s.id=ss.st_id where ss.own_id='".$_SESSION['com_id']."' and p.model in (select model from product where pro_id='".sql_int($_REQUEST['pro_id'][$ci])."')"));


	// store table: id, company_id, pro_id, s_n, no
	$args['value']="'".$_SESSION['com_id']."','".$_REQUEST['pro_id'][$ci]."','".$sn."','".($maxno['maxno']+1)."'";
	 $po_id=$har->insertDbMax($args);


	// store_sale table: id, st_id, warranty, sale, own_id
	$args2['value']="NULL,'".$po_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$ci]))."','0','".$_SESSION['com_id']."'";
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
	// deliver table: id, company_id, po_id, deliver_date, out_id, deleted_at
	$args3['value']="NULL,'".$_SESSION['com_id']."','".sql_int($_REQUEST['po_id'])."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','0',NULL";	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3',payby='".sql_int($_REQUEST['payby'])."'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);
	
	// Redirect to delivery list after creating delivery note
	exit("<script>window.location = 'index.php?page=deliv_list'</script>");	
	 
	 
	 

	 
		}else  	if(($_REQUEST['method']=="m")&&($flag==0)){
	$ci=0;
	foreach ($_REQUEST['sn'] as $sn) {	
	 $datacheck=mysqli_fetch_array(mysqli_query($db->conn, "select store_sale.id as id,s_n from store_sale join store on store_sale.st_id = store.id where own_id='".$_SESSION['com_id']."' and store.id='".$sn."'"));
	 
	 
	 	
	// SECURITY FIX: Add company filter via store_sale owner check
$maxno=mysqli_fetch_array(mysqli_query($db->conn, "select max(s.no) as maxno from store s join product p on s.pro_id=p.pro_id join store_sale ss on s.id=ss.st_id where ss.own_id='".$_SESSION['com_id']."' and p.model in (select model from product where pro_id='".sql_int($_REQUEST['pro_id'][$ci])."')"));


	// store table: id, company_id, pro_id, s_n, no
	$args['value']="'".$_SESSION['com_id']."','".$_REQUEST['pro_id'][$ci]."','".$datacheck['s_n']."','".($maxno['maxno']+1)."'";
	 $po_id=$har->insertDbMax($args);
	
	
	$args4['table']="store_sale";
	$args4['value']="sale='1'";
	$args4['condition']="id='".$datacheck['id']."'";
	
	$har->updateDb($args4);	
	
	$args2['value']="NULL,'".$po_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$ci]))."','0','".$_REQUEST['cus_id']."'";
	
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
		// deliver table: id, company_id, po_id, deliver_date, out_id, deleted_at
	$args3['value']="NULL,'".$_SESSION['com_id']."','".sql_int($_REQUEST['po_id'])."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','0',NULL";	
	
	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);	
	 
	 
		}
		
		else if($_REQUEST['method']=="ED"){
			$fetoutid=mysqli_fetch_array(mysqli_query($db->conn, "select out_id from deliver where id='".sql_int($_REQUEST['deliv_id'])."'"));
	$args4['table']="sendoutitem";	 
	$args4['value']="tmp='".$_REQUEST['des']."',cus_id='".$_REQUEST['cus_id']."'";	
	$args4['condition']="id='".$fetoutid['out_id']."'";
	$har->updateDb($args4);
	$args3['table']="deliver";	 
	$args3['value']="deliver_date='".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."'";	
	$args3['condition']="id='".sql_int($_REQUEST['deliv_id'])."'";
	$har->updateDb($args3);
	 
	 $query_proid=mysqli_query($db->conn, "select pro_id from product where so_id='".$fetoutid['out_id']."'");
	while($fet_proid= mysqli_fetch_array($query_proid)){
		 $query_st_id=mysqli_query($db->conn, "select id from store where pro_id='".$fet_proid['pro_id']."'");
	while($fet_st_id= mysqli_fetch_array($query_st_id)){
	
	
	 mysqli_query($db->conn, "delete from store_sale where st_id='".$fet_st_id['id']."'");
	}
	 
	  mysqli_query($db->conn, "delete from store where pro_id='".$fet_proid['pro_id']."'");

	 }
	 
	 	  
	 mysqli_query($db->conn, "delete from product where so_id='".$fetoutid['out_id']."'");
	 
	 
	 	$args5['table']="product";
	$i=0;
	foreach ($_REQUEST['type'] as $type) {
		
		$m_pro=mysqli_fetch_array(mysqli_query($db->conn, "select max(pro_id) as pro_id from product"));
		$max_pro=$m_pro['pro_id']+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST['price'][$i]."','".$_REQUEST['discount'][$i]."','".$_REQUEST['ban_id'][$i]."','".$_REQUEST['model'][$i]."','".$type."','".$_REQUEST['quantity'][$i]."','".$_REQUEST['pack_quantity'][$i]."','".$fetoutid['out_id']."','".$_REQUEST['des'][$i]."','0','0000-00-00',''";
		$har->insertDB($args5);	
		
	$args['value']="'".$max_pro."','".$_REQUEST['s_n'][$i]."'";
	 $st_id=$har->insertDbMax($args);
	
	
	$args2['value']="'','".$st_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$i]))."','0','".$_SESSION['com_id']."'";
		$har->insertDB($args2);	
	
		
		
		$i++;
		}
	
	
		}
		
		else 
		if($_REQUEST['method']=="R"){
	$args3['table']="receive";	 
	$args3['value']="NULL,'".$_SESSION['com_id']."','".$_REQUEST['po_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
	
	 $argsiv['table']="iv";
	 $veniv=mysqli_fetch_array(mysqli_query($db->conn, "select ven_id from pr join po on pr.id=po.ref where po.id='".sql_int($_REQUEST['po_id'])."'"));
	 $maxiv=mysqli_fetch_array(mysqli_query($db->conn, "select max(id) as max_id from iv where cus_id='".$veniv['ven_id']."'"));
	 // iv table columns: id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date
	 $argsiv['value']="'".(($maxiv['max_id']*1)+1)."','".$_SESSION['com_id']."','".$_REQUEST['po_id']."','".$veniv['ven_id']."','".date("Y-m-d")."','".(date("y")+43).str_pad(($maxiv['max_id']+1), 6, '0', STR_PAD_LEFT)."','0','0','".date("Y-m-d")."','0','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
		$har->insertDB($argsiv);	
	 
		
	$args['table']="pr";
	$args['value']="status='4'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);
	
	exit("<script>window.location = 'index.php?page=compl_list'</script>");
	} else 
	if($_REQUEST['method']=="R2"){
	$args3['table']="receive";	 
	$args3['value']="NULL,'".$_SESSION['com_id']."','ou".$_REQUEST['po_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
		} else
		if($_REQUEST['method']=="AD"){
	
	$args4['table']="sendoutitem";	 
	$args4['value']="'".$_SESSION['com_id']."','".$_REQUEST['cus_id']."','".$_REQUEST['des']."'";	
	 $op_id=$har->insertDbMax($args4);
	
		$args5['table']="product";
	$i=0;
	foreach ($_REQUEST['type'] as $type) {
		
		$m_pro=mysqli_fetch_array(mysqli_query($db->conn, "select max(pro_id) as pro_id from product"));
		$max_pro=$m_pro['pro_id']+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST['price'][$i]."','".$_REQUEST['discount'][$i]."','".$_REQUEST['ban_id'][$i]."','".$_REQUEST['model'][$i]."','".$type."','".$_REQUEST['quantity'][$i]."','".$_REQUEST['pack_quantity'][$i]."','".$op_id."','".$_REQUEST['des'][$i]."','0','0','0','0000-00-00',''";
		//echo $args5['table']." | ".$args5['value']."<br>";
		$har->insertDB($args5);	
	$args['value']="'".$max_pro."','".$_REQUEST['s_n'][$i]."',''";
	 $st_id=$har->insertDbMax($args);
	//echo $args['table']." | ".$args['value']."<br>";
	$args2['value']="'','".$st_id."','".date("Y-m-d",strtotime($_REQUEST['warranty'][$i]))."','0','".$_SESSION['com_id']."'";
	//echo $args2['table']." | ".$args2['value']."<br>";
	
		$har->insertDB($args2);	
	
		
		
		$i++;
		}
	

	 
	 
	$args3['value']="'','','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','". $op_id."'";
		$har->insertDB($args3);	
	
	
	}else {exit("<script>alert('window.location = 'index.php?page=".$_REQUEST['page']."'</script>");
    }

}

case "billing" : {
	if($_REQUEST['method']=="A"){
		// Insert new billing note with multi-invoice support
		$des = sql_escape($_REQUEST['des']);
		$price = floatval(str_replace(',', '', $_REQUEST['price']));
		$customer_id = sql_int($_REQUEST['customer_id'] ?? 0);
		$invoices = isset($_REQUEST['invoices']) ? $_REQUEST['invoices'] : [];
		
		// Handle legacy single invoice mode
		if (empty($invoices) && !empty($_REQUEST['inv_id'])) {
			$invoices = [sql_int($_REQUEST['inv_id'])];
		}
		
		if (!empty($invoices)) {
			// Get first invoice id for legacy compatibility
			$first_inv_id = sql_int($invoices[0]);
			
			// Insert billing note
			$sql = "INSERT INTO billing (bil_id, des, inv_id, customer_id, price, created_at) 
			        VALUES (NULL, '".$des."', '".$first_inv_id."', '".$customer_id."', '".$price."', NOW())";
			mysqli_query($db->conn, $sql);
			$bil_id = mysqli_insert_id($db->conn);
			
			// Insert billing items for each invoice
			foreach ($invoices as $inv_id) {
				$inv_id = sql_int($inv_id);
				// Calculate individual invoice amount
				$inv_sql = "SELECT 
					(SELECT SUM(
						(product.price * product.quantity) + 
						(product.valuelabour * product.activelabour * product.quantity) -
						(product.discount * product.quantity)
					) FROM product WHERE product.po_id = po.id) as subtotal,
					po.vat, po.dis as discount, po.over as withholding
					FROM iv JOIN po ON iv.tex = po.id
					WHERE iv.id = '".$inv_id."' AND po.po_id_new = ''
					ORDER BY iv.createdate DESC LIMIT 1";
				$inv_result = mysqli_query($db->conn, $inv_sql);
				$inv_data = mysqli_fetch_assoc($inv_result);
				
				$subtotal = floatval($inv_data['subtotal'] ?? 0);
				$vat_percent = floatval($inv_data['vat'] ?? 0);
				$discount = floatval($inv_data['discount'] ?? 0);
				$withholding = floatval($inv_data['withholding'] ?? 0);
				$after_discount = $subtotal - $discount;
				$vat_amount = $after_discount * ($vat_percent / 100);
				$withholding_amount = $after_discount * ($withholding / 100);
				$amount = $after_discount + $vat_amount - $withholding_amount;
				
				$item_sql = "INSERT INTO billing_items (bil_id, inv_id, amount) VALUES ('".$bil_id."', '".$inv_id."', '".$amount."')";
				mysqli_query($db->conn, $item_sql);
			}
		}
		
		// Redirect back to billing list
		exit("<script>window.location = 'index.php?page=billing'</script>");
	}
	
	if($_REQUEST['method']=="E"){
		// Update existing billing note
		$bil_id = sql_int($_REQUEST['bil_id']);
		$des = sql_escape($_REQUEST['des']);
		$price = floatval(str_replace(',', '', $_REQUEST['price']));
		
		$sql = "UPDATE billing SET des='".$des."', price='".$price."' WHERE bil_id='".$bil_id."'";
		mysqli_query($db->conn, $sql);
		
		// Redirect back to billing list
		exit("<script>window.location = 'index.php?page=billing'</script>");
	}
	
	if($_REQUEST['method']=="D"){
		// Delete billing note and its items
		$bil_id = sql_int($_REQUEST['bil_id']);
		
		// Delete billing items first
		$sql = "DELETE FROM billing_items WHERE bil_id='".$bil_id."'";
		mysqli_query($db->conn, $sql);
		
		// Delete billing note
		$sql = "DELETE FROM billing WHERE bil_id='".$bil_id."'";
		mysqli_query($db->conn, $sql);
		
		// Redirect back to billing list
		exit("<script>window.location = 'index.php?page=billing'</script>");
	}
}

}
exit("<script>window.location = 'index.php?page=".$_REQUEST['page']."'</script>");

?>