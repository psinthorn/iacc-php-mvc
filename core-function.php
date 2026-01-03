<?php session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");

// CSRF protection for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
}

// Initialize company filter for multi-tenant queries
$companyFilter = CompanyFilter::getInstance();

$users=new DbConn($config);
$db = $users; // Alias for compatibility with legacy code
// Security already checked in index.php

$har=new HardClass();
$har->keeplog($_REQUEST);
switch($_REQUEST['page']){	
	
case "company" : {
	$owner_company_id = isset($_SESSION['com_id']) ? intval($_SESSION['com_id']) : 0;
	if($_REQUEST['method']=="A"){
		$args['table']="company";
		$args2['table']="company_addr";
		//$args3['table']="company_credit";
		// Include company_id for multi-tenant: assign new customer/vendor to logged-in company
		$id = $har->Maxid('company');
		$sql = "INSERT INTO company (id, name_en, name_th, name_sh, contact, email, phone, fax, tax, customer, vender, logo, term, company_id) 
		        VALUES ('".$id."', '".sql_escape($_REQUEST['name_en'])."','".sql_escape($_REQUEST['name_th'])."','".sql_escape($_REQUEST['name_sh'])."','".sql_escape($_REQUEST['contact'])."','".sql_escape($_REQUEST['email'])."','".sql_escape($_REQUEST['phone'])."','".sql_escape($_REQUEST['fax'])."','".sql_escape($_REQUEST['tax'])."','".sql_escape($_REQUEST['customer'])."','".sql_escape($_REQUEST['vender'])."','','".sql_escape($_REQUEST['term'])."','".$owner_company_id."')";
		mysqli_query($db->conn, $sql);
	$tmpid = $id;	
	
	if($_REQUEST['adr_bil']=="")$_REQUEST['adr_bil']=$_REQUEST['adr_tax'];
	if($_REQUEST['city_bil']=="")$_REQUEST['city_bil']=$_REQUEST['city_tax'];
	if($_REQUEST['district_bil']=="")$_REQUEST['district_bil']=$_REQUEST['district_tax'];
	if($_REQUEST['province_bil']=="")$_REQUEST['province_bil']=$_REQUEST['province_tax'];
	if($_REQUEST['zip_bil']=="")$_REQUEST['zip_bil']=$_REQUEST['zip_tax'];
	$args2['value']="'','".$tmpid."','".sql_escape($_REQUEST['adr_tax'])."','".sql_escape($_REQUEST['city_tax'])."','".sql_escape($_REQUEST['district_tax'])."','".sql_escape($_REQUEST['province_tax'])."','".sql_escape($_REQUEST['zip_tax'])."','".sql_escape($_REQUEST['adr_bil'])."','".sql_escape($_REQUEST['city_bil'])."','".sql_escape($_REQUEST['district_bil'])."','".sql_escape($_REQUEST['province_bil'])."','".sql_escape($_REQUEST['zip_bil'])."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDB($args2);	
		}
	else if($_REQUEST['method']=="E"){
		
	if (($_FILES["logo"] != "") && 
		(($_FILES["logo"]["type"] == "image/jpg")|| 
		($_FILES["logo"]["type"] == "image/jpeg") ||
		($_FILES["logo"]["type"] == "image/JPG") || 
		($_FILES["logo"]["type"] == "image/pjpeg"))) {
		$filepath = "logo".md5(rand().$_REQUEST['name_en']).".jpg";
		copy($_FILES["logo"]["tmp_name"], "upload/".$filepath);
		$tmpupdate=",logo='".$filepath."'";
	}else{$tmpupdate="";}
		
	$args['table']="company";
	$args['value']="name_en='".sql_escape($_REQUEST['name_en'])."',name_th='".sql_escape($_REQUEST['name_th'])."',name_sh='".sql_escape($_REQUEST['name_sh'])."',contact='".sql_escape($_REQUEST['contact'])."',email='".sql_escape($_REQUEST['email'])."',phone='".sql_escape($_REQUEST['phone'])."',fax='".sql_escape($_REQUEST['fax'])."',tax='".sql_escape($_REQUEST['tax'])."',customer='".sql_escape($_REQUEST['customer'])."',vender='".sql_escape($_REQUEST['vender'])."'".$tmpupdate.",term='".sql_escape($_REQUEST['term'])."'";
	
	$args['condition']="id='".sql_int($_REQUEST['id'])."'";
	$har->updateDb($args);	
		}
		else if($_REQUEST['method']=="A2"){
	$args['table']="company_addr";
	$args['value']="valid_end='".date('Y-m-d')."'";
	$args['condition']="com_id='".sql_int($_REQUEST['id'])."' and valid_end='0000-00-00'";
	$har->updateDb($args);
		$args['value']="'','".sql_int($_REQUEST['com_id'])."','".sql_escape($_REQUEST['adr_tax'])."','".sql_escape($_REQUEST['city_tax'])."','".sql_escape($_REQUEST['district_tax'])."','".sql_escape($_REQUEST['province_tax'])."','".sql_escape($_REQUEST['zip_tax'])."','".sql_escape($_REQUEST['adr_bil'])."','".sql_escape($_REQUEST['city_bil'])."','".sql_escape($_REQUEST['district_bil'])."','".sql_escape($_REQUEST['province_bil'])."','".sql_escape($_REQUEST['zip_bil'])."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
	else if($_REQUEST['method']=="A3"){
	$args['table']="company_credit";
		$args['value']="'','".sql_int($_REQUEST['cus_id'])."','".sql_int($_REQUEST['ven_id'])."','".sql_escape($_REQUEST['limit_credit'])."','".sql_escape($_REQUEST['limit_day'])."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
		else if($_REQUEST['method']=="A4"){
	$args['table']="company_credit";
	$args['value']="valid_end='".date('Y-m-d')."'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."'";
	$har->updateDb($args);
	$args['table']="company_credit";
	$args['value']="'','".sql_int($_REQUEST['cus_id'])."','".sql_int($_REQUEST['ven_id'])."','".sql_escape($_REQUEST['limit_credit'])."','".sql_escape($_REQUEST['limit_day'])."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
	
	
}break;		
case "type" : {
	$company_id = $companyFilter->getSafeCompanyId();
	if($_REQUEST['method']=="A"){
		$args['table']="type";
		
	
	$args['value']="'','".$company_id."','".sql_escape($_REQUEST['type_name'])."','".sql_escape($_REQUEST['des'])."','".sql_int($_REQUEST['cat_id'])."'";
	$max_id=$har->insertDbMax($args);	
	while(list($key, $val) = each($_POST))
		{
			if(!(($key=="type_name")||($key=="cat_id")||($key=="des")||($key=="method")||($key=="page")||($key=="id"))){
			mysqli_query($db->conn, "INSERT INTO map_type_to_brand VALUES('','".$company_id."','".sql_int($max_id)."','".sql_int($key)."')");
		}}
		}else if($_REQUEST['method']=="D"){
			mysqli_query($db->conn, "DELETE FROM type WHERE id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter());
			mysqli_query($db->conn, "DELETE FROM map_type_to_brand WHERE type_id='".sql_int($_REQUEST['id'])."'");
			
			
		
			}
	else if($_REQUEST['method']=="E"){
		
		mysqli_query($db->conn, "DELETE FROM map_type_to_brand WHERE type_id='".sql_int($_POST['id'])."'");
		while(list($key, $val) = each($_POST))
		{
			if(!(($key=="type_name")||($key=="cat_id")||($key=="des")||($key=="method")||($key=="page")||($key=="id"))){
			mysqli_query($db->conn, "INSERT INTO map_type_to_brand VALUES('','".$company_id."','".sql_int($_POST['id'])."','".sql_int($key)."')");
		}
		}	
		
	$args['table']="type";

	
	$args['value']="name='".sql_escape($_REQUEST['type_name'])."',cat_id='".sql_int($_REQUEST['cat_id'])."',des='".sql_escape($_REQUEST['des'])."'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter();
	$har->updateDb($args);	
		}
	header("Location: index.php?page=type");
	exit;
}break;	
case "category" : {
	$company_id = $companyFilter->getSafeCompanyId();
	if($_REQUEST['method']=="A"){
		$args['table']="category";
	$args['value']="'','".$company_id."','".sql_escape($_REQUEST['cat_name'])."','".sql_escape($_REQUEST['des'])."'";
	$har->insertDB($args);	
		}else if($_REQUEST['method']=="D"){
			mysqli_query($users->conn, "DELETE FROM category WHERE id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter());
		
			}
	else if($_REQUEST['method']=="E"){
	$args['table']="category";
	$args['value']="cat_name='".sql_escape($_REQUEST['cat_name'])."',des='".sql_escape($_REQUEST['des'])."'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter();
	$har->updateDb($args);	
		}
	header("Location: index.php?page=category");
	exit;
}break;


case "compl_list" : {
	if($_REQUEST['method']=="C"){
		$args['table']="pay";
	$args['value']="'','".sql_int($_REQUEST['po_id'])."','".sql_escape($_REQUEST['payment'])."','".sql_escape($_REQUEST['remark'])."','".sql_escape($_REQUEST['volumn'])."','".date("Y-m-d")."'";
	
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
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);
	
	 $argsiv['table']="iv";
	$po_id=mysqli_fetch_array(mysqli_query($db->conn, "select po.id as po_id,ven_id from pr join po on pr.id=po.ref where po_id_new='' and pr.id='".sql_int($_REQUEST['id'])."'"));
	 $maxtaxiv=mysqli_fetch_array(mysqli_query($db->conn, "select max(texiv) as max_id from iv where cus_id='".$po_id[ven_id]."'"));
	
	
	$args2['table']="iv";
	$args2['value']="texiv='".(number_format($maxtaxiv[max_id])+1)."',texiv_rw='".(date("y")+43).str_pad(($maxtaxiv[max_id]+1), 6, '0', STR_PAD_LEFT)."',texiv_create='".date("Y-m-d")."',status_iv='1'";
	$args2['condition']="tex='".$po_id[po_id]."'";
	$har->updateDb($args2);
		}
}break;


		
		
		
case "payment" : {
	if($_REQUEST['method']=="A"){
		$args['table']="payment";
	$args['value']="'','".sql_escape($_REQUEST['payment_name'])."','".sql_escape($_REQUEST['payment_des'])."','".$_SESSION['com_id']."'";
	$har->insertDB($args);	
		}
	else if($_REQUEST['method']=="E"){
	$args['table']="payment";
	$args['value']="payment_name='".sql_escape($_REQUEST['payment_name'])."',payment_des='".sql_escape($_REQUEST['payment_des'])."'";
	$args['condition']="id='".sql_int($_REQUEST['id'])."'";
	$har->updateDb($args);	
		}
}break;


	
		
case "mo_list" : {
		$args['table']="model";
	$company_id = $companyFilter->getSafeCompanyId();

	if($_REQUEST['method']=="A"){
		$args['value']="'','".$company_id."','".sql_int($_REQUEST['type'])."','".sql_int($_REQUEST['brand'])."','".sql_escape($_REQUEST['model_name'])."','".sql_escape($_REQUEST['des'])."','".sql_escape($_REQUEST['price'])."'";
	$har->insertDB($args);	
		}
	if($_REQUEST['method']=="E"){
	$args['value']="model_name='".sql_escape($_REQUEST['model_name'])."',des='".sql_escape($_REQUEST['des'])."',price='".sql_escape($_REQUEST['price'])."'";
		$args['condition']="id='".sql_int($_REQUEST['p_id'])."' " . $companyFilter->andCompanyFilter();

	$har->updateDb($args);	
	
	
		}	
		
		
			if(($_REQUEST['method']=="D")&&(mysqli_num_rows(mysqli_query($users->conn, "select * from product where model='".sql_int($_REQUEST['p_id'])."'"))==0)){
	mysqli_query($users->conn, "delete from model where id='".sql_int($_REQUEST['p_id'])."' " . $companyFilter->andCompanyFilter());
		}
	header("Location: index.php?page=mo_list");
	exit;
}break;

case "brand" : {
	$args['table']="brand";
	$company_id = $companyFilter->getSafeCompanyId();
	
	if($_REQUEST['method']=="A"){
		if (($_FILES["logo"] != "") && 
		(($_FILES["logo"]["type"] == "image/jpg")|| 
		($_FILES["logo"]["type"] == "image/jpeg") ||
		($_FILES["logo"]["type"] == "image/JPG") || 
		($_FILES["logo"]["type"] == "image/pjpeg"))) {
		$filepath = "logo".md5(rand().$_REQUEST['type_name']).".jpg";
		copy($_FILES["logo"]["tmp_name"], "upload/".$filepath);
		$tmpupdate=",'".$filepath."'";
	}else{$tmpupdate=",''";}
	
	$args['value']="'','".$company_id."','".sql_escape($_REQUEST['brand_name'])."','".sql_escape($_REQUEST['des'])."'".$tmpupdate.",'".sql_int($_REQUEST['ven_id'])."'";
	$har->insertDB($args);	
		}else if($_REQUEST['method']=="D"){
			mysqli_query($db->conn, "DELETE FROM brand WHERE id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter());
			mysqli_query($db->conn, "DELETE FROM map_type_to_brand WHERE brand_id='".sql_int($_REQUEST['id'])."'");
			}
	else if($_REQUEST['method']=="E"){
			if (($_FILES["logo"] != "") && 
		(($_FILES["logo"]["type"] == "image/jpg")|| 
		($_FILES["logo"]["type"] == "image/jpeg") ||
		($_FILES["logo"]["type"] == "image/JPG") || 
		($_FILES["logo"]["type"] == "image/pjpeg"))) {
		$filepath = "logo".md5(rand().$_REQUEST['type_name']).".jpg";
		copy($_FILES["logo"]["tmp_name"], "upload/".$filepath);
		$tmpupdate=",logo='".$filepath."'";
	}else{$tmpupdate="";}
		
	$args['value']="brand_name='".sql_escape($_REQUEST['brand_name'])."',des='".sql_escape($_REQUEST['des'])."'".$tmpupdate.",ven_id='".sql_int($_REQUEST['ven_id'])."'";
	
	$args['condition']="id='".sql_int($_REQUEST['id'])."' " . $companyFilter->andCompanyFilter();
	$har->updateDb($args);	
		}
	header("Location: index.php?page=brand");
	exit;
}break;
case "pr_list" : {
	$args['table']="pr";
	if($_REQUEST['method']=="D"){
			$args['table']="pr";
	$args['value']="cancel='1'";
	$args['condition']="id='".$_REQUEST['id']."' and (ven_id='".$_SESSION['com_id']."' or cus_id='".$_SESSION['com_id']."')";
	$har->updateDb($args);
		
		}else
	if($_REQUEST['method']=="A"){
	$args['value']="'".$_REQUEST['name']."','".$_REQUEST['des']."','".$_SESSION['user_id']."','".$_REQUEST['cus_id']."','".$_REQUEST['ven_id']."','".date('Y-m-d')."','0','0','0',''";
	
	 $pr_id=$har->insertDbMax($args);
	 for($i=0;$i<9;$i++){
		 
		// echo "<br>".$_REQUEST[id.$i]."|".$_REQUEST[quantity.$i];
		 if(($_REQUEST[id.$i]!="0")&&($_REQUEST[quantity.$i]!="0")){
		 	$args['table']="tmp_product";
		$args['value']="'','".$pr_id."','".$_REQUEST[id.$i]."','".$_REQUEST[quantity.$i]."','".$_REQUEST[price.$i]."'";
		$har->insertDB($args);
		 }
		 }
	 
	 
		}
}break;
case "po_list" : {
	$args['table']="po";
	if($_REQUEST['method']=="D"){
		$dataref=mysqli_fetch_array(mysqli_query($db->conn, "select ref,status from po join pr on po.ref=pr.id where po.id='".sql_int($_REQUEST['id'])."'"));
			$args['table']="pr";
	$args['value']="cancel='1'";
	$args['condition']="id='".$dataref[ref]."' and (ven_id='".$_SESSION['com_id']."' or cus_id='".$_SESSION['com_id']."')";
	$har->updateDb($args);
	if($dataref[status]=="1"){exit("<script>window.location = 'index.php?page=qa_list'</script>");break;}
	
		
		}else
	if($_REQUEST['method']=="A"){
	$id=$har->Maxid($args['table']);
	$args['value']="'','".$_REQUEST['name']."','".$_REQUEST['ref']."','".(date("y")+43).str_pad($id, 6, '0', STR_PAD_LEFT)."','".date('Y-m-d')."','".date("Y-m-d",strtotime($_REQUEST['valid_pay']))."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','','".$_REQUEST['dis']."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[over]."'";
	 
	$po_id=$har->insertDbMax($args);
	$args['table']="pr";
	$args['value']="status='1'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','".$po_id."','".$_REQUEST[price][$i]."','0','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','0','0000-00-00',''";
		$har->insertDB($args);	
		$i++;
		}
	
	$_REQUEST['page']="qa_list";
		}else if($_REQUEST['method']=="E"){
				$argspr['table']="pr";
	$argspr['value']="cus_id='".$_REQUEST[cus_id]."'";
	$argspr['condition']=" id='".$_REQUEST['ref']."' and ven_id='".$_SESSION['com_id']."'";
	$har->updateDb($argspr);	
			
			$_REQUEST['page']="qa_list";
	$id=$har->Maxid($args['table']);
	$args['value']="'','".$_REQUEST['name']."','".$_REQUEST['ref']."','".(date("y")+43).str_pad($id, 6, '0', STR_PAD_LEFT)."','".date("Y-m-d",strtotime($_REQUEST[create_date]))."','".date("Y-m-d",strtotime($_REQUEST['valid_pay']))."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','','".$_REQUEST['dis']."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[over]."'";
	 
	$po_id=$har->insertDbMax($args);
	
	$args['value']="po_id_new='".$id."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
		
	foreach ($_REQUEST[type] as $key => $type ) {
		
		$args['value']="'','".$po_id."','".$_REQUEST[price][$key]."','".$_REQUEST[discount][$key]."','".$_REQUEST[ban_id][$key]."','".$_REQUEST[model][$key]."','".$type."','".$_REQUEST[quantity][$key]."','".$_REQUEST[pack_quantity][$key]."','','".$_REQUEST[des][$key]."','".$_REQUEST[a_labour][$key]."','".$_REQUEST[v_labour][$key]."','0','0000-00-00',''";
		$har->insertDB($args);	

		}
	break;
	
	
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
	$args['condition']="po_id_new='' and ref='".$_REQUEST['ref']."'";
	$har->updateDb($args);			
	
	$args['table']="pr";
	$args['value']="status='2'";
	$args['condition']="id='".$_REQUEST['ref']."'";
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
	$new_rw=$max_no[maxrep]+1;
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_val = $invoice_id === NULL ? 'NULL' : "'".$invoice_id."'";
	
	// Column order: id, name, phone, email, createdate, description, payment_method, status, invoice_id, vender, rep_no, rep_rw, brand, vat, dis, deleted_at
	$args['value']="'".sql_escape($_REQUEST['name'])."','".sql_escape($_REQUEST['phone'])."','".sql_escape($_REQUEST['email'])."','".date("Y-m-d")."','".sql_escape($_REQUEST['des'])."','".$payment_method."','".$status."',".$invoice_id_val.",'".$_SESSION['com_id']."','".$new_rw."','".(date("y")+43).str_pad($new_rw, 6, '0', STR_PAD_LEFT)."','".sql_int($_REQUEST['brandven'])."','".sql_escape($_REQUEST['vat'])."','".sql_escape($_REQUEST['dis'])."',NULL";
	 
	$rep_id=$har->insertDbMax($args);
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."','".$rep_id."'";
		$har->insertDB($args);	
		$i++;
		}
	
		}else if($_REQUEST['method']=="E"){
		
		$args['table']="receipt";
	
	// Get new fields with defaults
	$payment_method = isset($_REQUEST['payment_method']) ? mysqli_real_escape_string($db->conn, $_REQUEST['payment_method']) : 'cash';
	$status = isset($_REQUEST['status']) ? mysqli_real_escape_string($db->conn, $_REQUEST['status']) : 'confirmed';
	$invoice_id = isset($_REQUEST['invoice_id']) && !empty($_REQUEST['invoice_id']) ? intval($_REQUEST['invoice_id']) : NULL;
	$invoice_id_sql = $invoice_id === NULL ? 'invoice_id=NULL' : "invoice_id='".$invoice_id."'";
	
	$args['value']="name='".sql_escape($_REQUEST['name'])."',phone='".sql_escape($_REQUEST['phone'])."',email='".sql_escape($_REQUEST['email'])."',description='".sql_escape($_REQUEST['des'])."',brand='".sql_int($_REQUEST['brandven'])."',vat='".sql_escape($_REQUEST['vat'])."',dis='".sql_escape($_REQUEST['dis'])."',payment_method='".$payment_method."',status='".$status."',".$invoice_id_sql;
				
	$args['condition']="id='".sql_int($_REQUEST['id'])."' and vender='".$_SESSION['com_id']."'";
	$har->updateDb($args);	
	
		$args['table']="product";
	$i=0;
	mysqli_query($db->conn, "delete from product where re_id='".sql_int($_REQUEST['id'])."' and po_id='0' and so_id='0'");
	foreach ($_REQUEST[type] as $key => $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$key]."','0','".$_REQUEST[ban_id][$key]."','".$_REQUEST[model][$key]."','".$type."','".$_REQUEST[quantity][$key]."','1','','".$_REQUEST[des][$key]."','".$_REQUEST[a_labour][$key]."','".$_REQUEST[v_labour][$key]."','','".date("Y-m-d",strtotime($_REQUEST[warranty][$key]))."','".$_REQUEST['id']."'";
		$har->insertDB($args);	
	
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
	$new_rw=$max_no[maxvou]+1;
	
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
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','".$vou_id."','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."',''";
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
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','".$_REQUEST['id']."','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."',''";
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
	$args['condition']="po_id_new='' and ref='".$_REQUEST['ref']."'";
	$har->updateDb($args);			
	
	$args['table']="pr";
	$args['value']="status='2'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
		}
		
}break;

case "deliv_list" : {
	$args['table']="store";
	$args2['table']="store_sale";
	$args3['table']="deliver";
	$ctsn=count($_REQUEST['sn']);
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
		$sn=$ms[ms]+1;
		}
	
	
$maxno=mysqli_fetch_array(mysqli_query($db->conn, "select max(no) as maxno from store join product on store.pro_id=product.pro_id where model in (select model from product where pro_id='".sql_int($_REQUEST['pro_id'][$ci])."')"));


	$args['value']="'".$_REQUEST['pro_id'][$ci]."','".$sn."','".($maxno[maxno]+1)."'";
	 $po_id=$har->insertDbMax($args);
	
	
	$args2['value']="'','".$po_id."','".strtotime($_REQUEST['exp'][$ci])."','0','".$_SESSION['com_id']."'";
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
	$args3['value']="'','".sql_int($_REQUEST['po_id'])."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."',''";	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3',payby='".sql_int($_REQUEST['payby'])."'";
	$args['condition']="id='".sql_int($_REQUEST['ref'])."'";
	$har->updateDb($args);	
	 
	 
	 

	 
		}else  	if(($_REQUEST['method']=="m")&&($flag==0)){
	$ci=0;
	foreach ($_REQUEST['sn'] as $sn) {	
	 $datacheck=mysqli_fetch_array(mysqli_query($db->conn, "select store_sale.id as id,s_n from store_sale join store on store_sale.st_id = store.id where own_id='".$_SESSION['com_id']."' and store.id='".$sn."'"));
	 
	 
	 	
	
$maxno=mysqli_fetch_array(mysqli_query($db->conn, "select max(no) as maxno from store join product on store.pro_id=product.pro_id where model in (select model from product where pro_id='".sql_int($_REQUEST['pro_id'][$ci])."')"));


	
	$args['value']="'".$_REQUEST['pro_id'][$ci]."','".$datacheck[s_n]."','".($maxno[maxno]+1)."'";
	 $po_id=$har->insertDbMax($args);
	
	
	$args4['table']="store_sale";
	$args4['value']="sale='1'";
	$args4['condition']="id='".$datacheck[id]."'";
	
	$har->updateDb($args4);	
	
	$args2['value']="'','".$po_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$ci]))."','0','".$_REQUEST[cus_id]."'";
	
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
	$args3['value']="'','".$_REQUEST['po_id']."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."'";	
	
	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
	 
	 
		}
		
		else if($_REQUEST['method']=="ED"){
			$fetoutid=mysqli_fetch_array(mysqli_query($db->conn, "select out_id from deliver where id='".sql_int($_REQUEST['deliv_id'])."'"));
	$args4['table']="sendoutitem";	 
	$args4['value']="tmp='".$_REQUEST['des']."',cus_id='".$_REQUEST[cus_id]."'";	
	$args4['condition']="id='".$fetoutid[out_id]."'";
	$har->updateDb($args4);
	$args3['table']="deliver";	 
	$args3['value']="deliver_date='".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."'";	
	$args3['condition']="id='".$_REQUEST['deliv_id']."'";
	$har->updateDb($args3);
	 
	 $query_proid=mysqli_query($db->conn, "select pro_id from product where so_id='".$fetoutid[out_id]."'");
	while($fet_proid= mysqli_fetch_array($query_proid)){
		 $query_st_id=mysqli_query($db->conn, "select id from store where pro_id='".$fet_proid[pro_id]."'");
	while($fet_st_id= mysqli_fetch_array($query_st_id)){
	
	
	 mysqli_query($db->conn, "delete from store_sale where st_id='".$fet_st_id[id]."'");
	}
	 
	  mysqli_query($db->conn, "delete from store where pro_id='".$fet_proid[pro_id]."'");

	 }
	 
	 	  
	 mysqli_query($db->conn, "delete from product where so_id='".$fetoutid[out_id]."'");
	 
	 
	 	$args5['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$m_pro=mysqli_fetch_array(mysqli_query($db->conn, "select max(pro_id) as pro_id from product"));
		$max_pro=$m_pro[pro_id]+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST[price][$i]."','".$_REQUEST[discount][$i]."','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','".$_REQUEST[pack_quantity][$i]."','".$fetoutid[out_id]."','".$_REQUEST[des][$i]."','0','0000-00-00',''";
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
	$args3['value']="'','".$_REQUEST['po_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
	
	 $argsiv['table']="iv";
	 $veniv=mysqli_fetch_array(mysqli_query($db->conn, "select ven_id from pr join po on pr.id=po.ref where po.id='".sql_int($_REQUEST['po_id'])."'"));
	 $maxiv=mysqli_fetch_array(mysqli_query($db->conn, "select max(id) as max_id from iv where cus_id='".$veniv[ven_id]."'"));
		$argsiv['value']="'".(($maxiv[max_id]*1)+1)."','".$_REQUEST['po_id']."','".$veniv[ven_id]."','".date("Y-m-d")."','".(date("y")+43).str_pad(($maxiv[max_id]+1), 6, '0', STR_PAD_LEFT)."','','','','0','',''";
		$har->insertDB($argsiv);	
	 
		
	$args['table']="pr";
	$args['value']="status='4'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);
	
	exit("<script>window.location = 'index.php?page=compl_list'</script>");
	} else 
	if($_REQUEST['method']=="R2"){
	$args3['table']="receive";	 
	$args3['value']="'','ou".$_REQUEST['po_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
		} else
		if($_REQUEST['method']=="AD"){
	
	$args4['table']="sendoutitem";	 
	$args4['value']="'".$_SESSION['com_id']."','".$_REQUEST['cus_id']."','".$_REQUEST['des']."'";	
	 $op_id=$har->insertDbMax($args4);
	
		$args5['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$m_pro=mysqli_fetch_array(mysqli_query($db->conn, "select max(pro_id) as pro_id from product"));
		$max_pro=$m_pro[pro_id]+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST[price][$i]."','".$_REQUEST[discount][$i]."','".$_REQUEST[ban_id][$i]."','".$_REQUEST[model][$i]."','".$type."','".$_REQUEST[quantity][$i]."','".$_REQUEST[pack_quantity][$i]."','".$op_id."','".$_REQUEST[des][$i]."','0','0','0','0000-00-00',''";
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


}
exit("<script>window.location = 'index.php?page=".$_REQUEST['page']."'</script>");

?>