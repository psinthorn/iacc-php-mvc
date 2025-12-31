<?php session_start();

require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/class.hard.php");
$users=new DbConn($config);
DbConn::setGlobalConnection($users->conn);
$users->checkSecurity();

$har=new HardClass($users->conn);
$har->keeplog($_REQUEST);
switch($_REQUEST['page']){	
	
case "company" : {
	if($_REQUEST['method']=="A"){
		$args['table']="company";
		$args2['table']="company_addr";
		//$args3['table']="company_credit";
	$args['value']="'".$_REQUEST['name_en']."','".$_REQUEST['name_th']."','".$_REQUEST['name_sh']."','".$_REQUEST['contact']."','".$_REQUEST['email']."','".$_REQUEST['phone']."','".$_REQUEST['fax']."','".$_REQUEST['tax']."','".$_REQUEST['customer']."','".$_REQUEST['vender']."','','".$_REQUEST['term']."'";

	$tmpid=$har->insertDbMax($args);	
	
	if($_REQUEST['address_billing']=="")$_REQUEST['address_billing']=$_REQUEST['address_tax'];
	if($_REQUEST['city_bil']=="")$_REQUEST['city_bil']=$_REQUEST['city_tax'];
	if($_REQUEST['district_bil']=="")$_REQUEST['district_bil']=$_REQUEST['district_tax'];
	if($_REQUEST['province_bil']=="")$_REQUEST['province_bil']=$_REQUEST['province_tax'];
	if($_REQUEST['zip_bil']=="")$_REQUEST['zip_bil']=$_REQUEST['zip_tax'];
	$args2['value']="'','".$tmpid."','".$_REQUEST['address_tax']."','".$_REQUEST['city_tax']."','".$_REQUEST['district_tax']."','".$_REQUEST['province_tax']."','".$_REQUEST['zip_tax']."','".$_REQUEST['address_billing']."','".$_REQUEST['city_bil']."','".$_REQUEST['district_bil']."','".$_REQUEST['province_bil']."','".$_REQUEST['zip_bil']."','".date('Y-m-d')."','0000-00-00'";
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
	$args['value']="name_en='".$_REQUEST['name_en']."',name_th='".$_REQUEST['name_th']."',name_sh='".$_REQUEST['name_sh']."',contact='".$_REQUEST['contact']."',email='".$_REQUEST['email']."',phone='".$_REQUEST['phone']."',fax='".$_REQUEST['fax']."',tax='".$_REQUEST['tax']."',customer='".$_REQUEST['customer']."',vender='".$_REQUEST['vender']."'".$tmpupdate.",term='".$_REQUEST['term']."'";
	
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
		}
		else if($_REQUEST['method']=="A2"){
	$args['table']="company_addr";
	$args['value']="valid_end='".date('Y-m-d')."'";
	$args['condition']="company_id='".$_REQUEST['id']."' and valid_end='0000-00-00'";
	$har->updateDb($args);
		$args['value']="'','".$_REQUEST['company_id']."','".$_REQUEST['address_tax']."','".$_REQUEST['city_tax']."','".$_REQUEST['district_tax']."','".$_REQUEST['province_tax']."','".$_REQUEST['zip_tax']."','".$_REQUEST['address_billing']."','".$_REQUEST['city_bil']."','".$_REQUEST['district_bil']."','".$_REQUEST['province_bil']."','".$_REQUEST['zip_bil']."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
	else if($_REQUEST['method']=="A3"){
	$args['table']="company_credit";
		$args['value']="'','".$_REQUEST['customer_id']."','".$_REQUEST['vendor_id']."','".$_REQUEST['limit_credit']."','".$_REQUEST['limit_day']."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
		else if($_REQUEST['method']=="A4"){
	$args['table']="company_credit";
	$args['value']="valid_end='".date('Y-m-d')."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);
	$args['table']="company_credit";
	$args['value']="'','".$_REQUEST['customer_id']."','".$_REQUEST['vendor_id']."','".$_REQUEST['limit_credit']."','".$_REQUEST['limit_day']."','".date('Y-m-d')."','0000-00-00'";
	$har->insertDb($args);	
		}
	
	
}break;		
case "type" : {
	if($_REQUEST['method']=="A"){
		$args['table']="type";
		
	
	$args['value']="'".htmlspecialchars($_REQUEST['type_name'])."','".$_REQUEST['des']."','".$_REQUEST['category_id']."'";
	$max_id=$har->insertDbMax($args);	
	foreach($_POST as $key => $val)
		{
			if(!(($key=="type_name")||($key=="category_id")||($key=="des")||($key=="method")||($key=="page")||($key=="id"))){
			mysql_query("insert into map_type_to_brand values('','".$max_id."','".$key."')");
		}}
		}else if($_REQUEST['method']=="D"){
			mysql_query("delete from type where id='".$_REQUEST['id']."'");
			mysql_query("delete from map_type_to_brand where product_type_id='".$_REQUEST['id']."'");
			
			
		
			}
	else if($_REQUEST['method']=="E"){
		
		mysql_query("delete from map_type_to_brand where product_type_id='".$_POST[id]."'");
		foreach($_POST as $key => $val)
		{
			if(!(($key=="type_name")||($key=="category_id")||($key=="des")||($key=="method")||($key=="page")||($key=="id"))){
			mysql_query("insert into map_type_to_brand values('','".$_POST[id]."','".$key."')");
		}
		}	
		
	$args['table']="type";

	
	$args['value']="name='".htmlspecialchars($_REQUEST['type_name'])."',category_id='".$_REQUEST['category_id']."',des='".$_REQUEST['des']."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
		}
}break;	
case "category" : {
	if($_REQUEST['method']=="A"){
		$args['table']="category";
	$args['value']="'','".$_REQUEST['cat_name']."','".$_REQUEST['des']."'";
	$har->insertDB($args);	
		}else if($_REQUEST['method']=="D"){
			mysql_query("delete from category where id='".$_REQUEST['id']."'");
		
			}
	else if($_REQUEST['method']=="E"){
	$args['table']="category";
	$args['value']="cat_name='".$_REQUEST['cat_name']."',des='".$_REQUEST['des']."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
		}
}break;


case "compl_list" : {
	if($_REQUEST['method']=="C"){
		$args['table']="pay";
	$args['value']="'','".$_REQUEST['purchase_order_id']."','".$_REQUEST['payment']."','".$_REQUEST['remark']."','".$_REQUEST['volumn']."','".date("Y-m-d")."'";
	
	$har->insertDB($args);	
		}
		exit("<script>window.location = 'index.php?page=compl_view&id=".$_REQUEST['purchase_order_id']."'</script>");break;
}break;

case "compl_view" : {
	if($_REQUEST['method']=="S"){
		$args['table']="pr";
	$args['value']="payby='".$_REQUEST['payby']."'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);
		}
		exit("<script>window.location = 'index.php?page=compl_view&id=".$_REQUEST['id']."'</script>");break;
}break;



case "compl_list2" : {
	
	if($_REQUEST['method']=="V"){	
	$purchase_order_id=mysql_fetch_array(mysql_query("select purchase_order.id as purchase_order_id,vendor_id from pr join purchase_order on purchase_request.id=purchase_order.ref where po_id_new='' and purchase_request.id='".$_REQUEST['id']."'"));
		$args2['table']="iv";
	$args2['value']="status_iv='2'";
	$args2['condition']="tex='".$purchase_order_id[purchase_order_id]."'";
	
	$har->updateDb($args2);
		}
	if($_REQUEST['method']=="C"){
	$args['table']="pr";
	$args['value']="status='5'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);
	
	 $argsiv['table']="iv";
	$purchase_order_id=mysql_fetch_array(mysql_query("select purchase_order.id as purchase_order_id,vendor_id from pr join purchase_order on purchase_request.id=purchase_order.ref where po_id_new='' and purchase_request.id='".$_REQUEST['id']."'"));
	 $maxtaxiv=mysql_fetch_array(mysql_query("select max(texiv) as max_id from iv where customer_id='".$purchase_order_id[vendor_id]."'"));
	
	
	$args2['table']="iv";
	$args2['value']="texiv='".(number_format($maxtaxiv[max_id])+1)."',texiv_rw='".(date("y")+43).str_pad(($maxtaxiv[max_id]+1), 6, '0', STR_PAD_LEFT)."',texiv_create='".date("Y-m-d")."',status_iv='1'";
	$args2['condition']="tex='".$purchase_order_id[purchase_order_id]."'";
	$har->updateDb($args2);
		}
}break;


		
		
		
case "payment" : {
	if($_REQUEST['method']=="A"){
		$args['table']="payment";
	$args['value']="'','".$_REQUEST['payment_name']."','".$_REQUEST['payment_des']."','".$_SESSION[company_id]."'";
	$har->insertDB($args);	
		}
	else if($_REQUEST['method']=="E"){
	$args['table']="payment";
	$args['value']="payment_name='".$_REQUEST['payment_name']."',payment_des='".$_REQUEST['payment_des']."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
		}
}break;


	
		
case "mo_list" : {
		$args['table']="model";

	if($_REQUEST['method']=="A"){
		$args['value']="'','".$_REQUEST['type']."','".$_REQUEST['brand']."','".$_REQUEST['model_name']."','".$_REQUEST['des']."','".$_REQUEST['price']."'";
	$har->insertDB($args);	
		}
	if($_REQUEST['method']=="E"){
	$args['value']="model_name='".$_REQUEST['model_name']."',des='".$_REQUEST['des']."',price='".$_REQUEST['price']."'";
		$args['condition']="id='".$_REQUEST['p_id']."'";

	$har->updateDb($args);	
	
	
		}	
		
		
			if(($_REQUEST['method']=="D")&&(mysql_num_rows(mysql_query("select * from product where model='".$_REQUEST['p_id']."'"))==0)){
	mysql_query("delete from model where id='".$_REQUEST['p_id']."'");
		}

}break;

case "brand" : {
	$args['table']="brand";
	
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
	
	$args['value']="'','".$_REQUEST['name']."','".$_REQUEST['des']."'".$tmpupdate.",'".$_REQUEST['company_id']."'";
	$har->insertDB($args);	
		}else if($_REQUEST['method']=="D"){
			mysql_query("delete from brand where id='".$_REQUEST['id']."'");
			mysql_query("delete from  map_type_to_brand where 	brand_id='".$_REQUEST['id']."'");
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
		
	$args['value']="name='".$_REQUEST['name']."',des='".$_REQUEST['des']."'".$tmpupdate.",company_id='".$_REQUEST['company_id']."'";
	
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
		}
}break;
case "pr_list" : {
	$args['table']="pr";
	if($_REQUEST['method']=="D"){
			$args['table']="pr";
	$args['value']="cancel='1'";
	$args['condition']="id='".$_REQUEST['id']."' and (vendor_id='".$_SESSION[company_id]."' or customer_id='".$_SESSION[company_id]."')";
	$har->updateDb($args);
		
		}else
	if($_REQUEST['method']=="A"){
	$args['value']="'".$_REQUEST['name']."','".$_REQUEST['des']."','".$_SESSION['user_id']."','".$_REQUEST['customer_id']."','".$_REQUEST['vendor_id']."','".date('Y-m-d')."','0','0','0',''";
	
	 $purchase_request_id=$har->insertDbMax($args);
	 for($i=0;$i<9;$i++){
		 
		// echo "<br>".$_REQUEST[id.$i]."|".$_REQUEST[quantity.$i];
		 if(($_REQUEST[id.$i]!="0")&&($_REQUEST[quantity.$i]!="0")){
		 	$args['table']="tmp_product";
		$args['value']="'','".$purchase_request_id."','".$_REQUEST[id.$i]."','".$_REQUEST[quantity.$i]."','".$_REQUEST[price.$i]."'";
		$har->insertDB($args);
		 }
		 }
	 
	 
		}
}break;
case "po_list" : {
	$args['table']="po";
	if($_REQUEST['method']=="D"){
		$dataref=mysql_fetch_array(mysql_query("select ref,status from purchase_order join purchase_request on purchase_order.ref=purchase_request.id where purchase_order.id='".$_REQUEST['id']."'"));
			$args['table']="pr";
	$args['value']="cancel='1'";
	$args['condition']="id='".$dataref[ref]."' and (vendor_id='".$_SESSION[company_id]."' or customer_id='".$_SESSION[company_id]."')";
	$har->updateDb($args);
	if($dataref[status]=="1"){exit("<script>window.location = 'index.php?page=qa_list'</script>");break;}
	
		
		}else
	if($_REQUEST['method']=="A"){
	$id=$har->Maxid($args['table']);
	$args['value']="'','".$_REQUEST['name']."','".$_REQUEST['ref']."','".(date("y")+43).str_pad($id, 6, '0', STR_PAD_LEFT)."','".date('Y-m-d')."','".date("Y-m-d",strtotime($_REQUEST['valid_pay']))."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','','".$_REQUEST['dis']."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[over]."'";
	 
	$purchase_order_id=$har->insertDbMax($args);
	$args['table']="pr";
	$args['value']="status='1'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','".$purchase_order_id."','".$_REQUEST[price][$i]."','0','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','0','0000-00-00',''";
		$har->insertDB($args);	
		$i++;
		}
	
	$_REQUEST['page']="qa_list";
		}else if($_REQUEST['method']=="E"){
				$argspr['table']="pr";
	$argspr['value']="customer_id='".$_REQUEST[customer_id]."'";
	$argspr['condition']=" id='".$_REQUEST['ref']."' and vendor_id='".$_SESSION[company_id]."'";
	$har->updateDb($argspr);	
			
			$_REQUEST['page']="qa_list";
	$id=$har->Maxid($args['table']);
	$args['value']="'','".$_REQUEST['name']."','".$_REQUEST['ref']."','".(date("y")+43).str_pad($id, 6, '0', STR_PAD_LEFT)."','".date("Y-m-d",strtotime($_REQUEST[create_date]))."','".date("Y-m-d",strtotime($_REQUEST['valid_pay']))."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','','".$_REQUEST['dis']."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[over]."'";
	 
	$purchase_order_id=$har->insertDbMax($args);
	
	$args['value']="po_id_new='".$id."'";
	$args['condition']="id='".$_REQUEST['id']."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
		
	foreach ($_REQUEST[type] as $key => $type ) {
		
		$args['value']="'','".$purchase_order_id."','".$_REQUEST[price][$key]."','".$_REQUEST[discount][$key]."','".$_REQUEST[brand_id][$key]."','".$_REQUEST[model][$key]."','".$product_type."','".$_REQUEST[quantity][$key]."','".$_REQUEST[pack_quantity][$key]."','','".$_REQUEST[des][$key]."','".$_REQUEST[a_labour][$key]."','".$_REQUEST[v_labour][$key]."','0','0000-00-00',''";
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
	$args['value']="pic='". $namefile.".".$product_type."'";
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
	$max_no=mysql_fetch_array(mysql_query("select max(rep_no) as maxrep from receipt where vender='".$_SESSION[company_id]."'"));
	$new_rw=$max_no[maxrep]+1;
	
	$args['value']="'".$_REQUEST['name']."','".$_REQUEST['phone']."','".$_REQUEST['email']."','".date("Y-m-d")."','".$_REQUEST['des']."','".$_SESSION[company_id]."','".$new_rw."','".(date("y")+43).str_pad($new_rw, 6, '0', STR_PAD_LEFT)."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[dis]."'";
	 
	$rep_id=$har->insertDbMax($args);
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."','".$rep_id."'";
		$har->insertDB($args);	
		$i++;
		}
	
		}else if($_REQUEST['method']=="E"){
		
		$args['table']="receipt";	
	$args['value']="name='".$_REQUEST['name']."',phone='".$_REQUEST['phone']."',email='".$_REQUEST['email']."',description='".$_REQUEST['des']."',brand='".$_REQUEST[brandven]."',vat='".$_REQUEST[vat]."',dis='".$_REQUEST[dis]."'";
				
	$args['condition']="id='".$_REQUEST['id']."' and vender='".$_SESSION[company_id]."'";
	$har->updateDb($args);	
	
		$args['table']="product";
	$i=0;
	mysql_query("delete from product where receipt_id='".$_REQUEST['id']."' and purchase_order_id='0' and send_out_id='0'");
	foreach ($_REQUEST[type] as $key => $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$key]."','0','".$_REQUEST[brand_id][$key]."','".$_REQUEST[model][$key]."','".$product_type."','".$_REQUEST[quantity][$key]."','1','','".$_REQUEST[des][$key]."','".$_REQUEST[a_labour][$key]."','".$_REQUEST[v_labour][$key]."','','".date("Y-m-d",strtotime($_REQUEST[warranty][$key]))."','".$_REQUEST['id']."'";
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
	$max_no=mysql_fetch_array(mysql_query("select max(vou_no) as maxvou from voucher where vender='".$_SESSION[company_id]."'"));
	$new_rw=$max_no[maxvou]+1;
	
	$args['value']="'".$_REQUEST['name']."','".$_REQUEST['phone']."','".$_REQUEST['email']."','".date("Y-m-d")."','".$_REQUEST['des']."','".$_SESSION[company_id]."','".$new_rw."','".(date("y")+43).str_pad($new_rw, 6, '0', STR_PAD_LEFT)."','".$_REQUEST[brandven]."','".$_REQUEST[vat]."','".$_REQUEST[dis]."'";
	 
	$vou_id=$har->insertDbMax($args);
	
	
	
	$args['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','".$vou_id."','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."',''";
		$har->insertDB($args);	
		$i++;
		}
	
		}else if($_REQUEST['method']=="E"){
		
		$args['table']="voucher";	
	$args['value']="name='".$_REQUEST['name']."',phone='".$_REQUEST['phone']."',email='".$_REQUEST['email']."',description='".$_REQUEST['des']."',brand='".$_REQUEST[brandven]."',vat='".$_REQUEST[vat]."',discount='".$_REQUEST[dis]."'";
			
			
	$args['condition']="id='".$_REQUEST['id']."' and vender='".$_SESSION[company_id]."'";
	$har->updateDb($args);	
	
	
	
	$args['table']="product";
	$i=0;
	mysql_query("delete from product where voucher_id='".$_REQUEST['id']."' and purchase_order_id='0' and send_out_id='0'");
	foreach ($_REQUEST[type] as $type) {
		
		$args['value']="'','0','".$_REQUEST[price][$i]."','0','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','1','','".$_REQUEST[des][$i]."','".$_REQUEST[a_labour][$i]."','".$_REQUEST[v_labour][$i]."','".$_REQUEST['id']."','".date("Y-m-d",strtotime($_REQUEST[warranty][$i]))."',''";
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
	$args['value']="pic='". $namefile.".".$product_type."'";
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
		$ms=mysql_fetch_array(mysql_query("select max(id) as ms from gen_serial"));
		$sn=$ms[ms]+1;
		}
	
	
$maxno=mysql_fetch_array(mysql_query("select max(no) as maxno from store join product on store.product_id=product.product_id where model in (select model from product where product_id='".$_REQUEST['product_id'][$ci]."')"));


	$args['value']="'".$_REQUEST['product_id'][$ci]."','".$sn."','".($maxno[maxno]+1)."'";
	 $purchase_order_id=$har->insertDbMax($args);
	
	
	$args2['value']="'','".$purchase_order_id."','".strtotime($_REQUEST['exp'][$ci])."','0','".$_SESSION[company_id]."'";
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
	$args3['value']="'','".$_REQUEST['purchase_order_id']."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."',''";	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3',payby='".$_REQUEST['payby']."'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
	 
	 
	 

	 
		}else  	if(($_REQUEST['method']=="m")&&($flag==0)){
	$ci=0;
	foreach ($_REQUEST['sn'] as $sn) {	
	 $datacheck=mysql_fetch_array(mysql_query("select store_sale.id as id,s_n from store_sale join store on store_sale.store_id = store.id where owner_id='".$_SESSION[company_id]."' and store.id='".$sn."'"));
	 
	 
	 	
	
$maxno=mysql_fetch_array(mysql_query("select max(no) as maxno from store join product on store.product_id=product.product_id where model in (select model from product where product_id='".$_REQUEST['product_id'][$ci]."')"));


	
	$args['value']="'".$_REQUEST['product_id'][$ci]."','".$datacheck[s_n]."','".($maxno[maxno]+1)."'";
	 $purchase_order_id=$har->insertDbMax($args);
	
	
	$args4['table']="store_sale";
	$args4['value']="sale='1'";
	$args4['condition']="id='".$datacheck[id]."'";
	
	$har->updateDb($args4);	
	
	$args2['value']="'','".$purchase_order_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$ci]))."','0','".$_REQUEST[customer_id]."'";
	
		$har->insertDB($args2);	
	
	 $ci++;
	 
	 }
	 
	 
	$args3['value']="'','".$_REQUEST['purchase_order_id']."','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."'";	
	
	$har->insertDB($args3);	
	
	$args['table']="pr";
	$args['value']="status='3'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);	
	 
	 
		}
		
		else if($_REQUEST['method']=="ED"){
			$fetoutid=mysql_fetch_array(mysql_query("select output_id from deliver where id='".$_REQUEST['deliv_id']."'"));
	$args4['table']="send_out_item";	 
	$args4['value']="tmp='".$_REQUEST['des']."',customer_id='".$_REQUEST[customer_id]."'";	
	$args4['condition']="id='".$fetoutid[output_id]."'";
	$har->updateDb($args4);
	$args3['table']="deliver";	 
	$args3['value']="deliver_date='".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."'";	
	$args3['condition']="id='".$_REQUEST['deliv_id']."'";
	$har->updateDb($args3);
	 
	 $query_proid=mysql_query("select product_id from product where send_out_id='".$fetoutid[output_id]."'");
	while($fet_proid= mysql_fetch_array($query_proid)){
		 $query_st_id=mysql_query("select id from store where product_id='".$fet_proid[product_id]."'");
	while($fet_st_id= mysql_fetch_array($query_st_id)){
	
	
	 mysql_query("delete from store_sale where store_id='".$fet_st_id[id]."'");
	}
	 
	  mysql_query("delete from store where product_id='".$fet_proid[product_id]."'");

	 }
	 
	 	  
	 mysql_query("delete from product where send_out_id='".$fetoutid[output_id]."'");
	 
	 
	 	$args5['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$m_pro=mysql_fetch_array(mysql_query("select max(product_id) as product_id from product"));
		$max_pro=$m_pro[product_id]+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST[price][$i]."','".$_REQUEST[discount][$i]."','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','".$_REQUEST[pack_quantity][$i]."','".$fetoutid[output_id]."','".$_REQUEST[des][$i]."','0','0000-00-00',''";
		$har->insertDB($args5);	
		
	$args['value']="'".$max_pro."','".$_REQUEST['s_n'][$i]."'";
	 $store_id=$har->insertDbMax($args);
	
	
	$args2['value']="'','".$store_id."','".date("Y-m-d",strtotime($_REQUEST['exp'][$i]))."','0','".$_SESSION[company_id]."'";
		$har->insertDB($args2);	
	
		
		
		$i++;
		}
	
	
		}
		
		else 
		if($_REQUEST['method']=="R"){
	$args3['table']="receive";	 
	$args3['value']="'','".$_REQUEST['purchase_order_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
	
	 $argsiv['table']="iv";
	 $veniv=mysql_fetch_array(mysql_query("select vendor_id from pr join purchase_order on purchase_request.id=purchase_order.ref where purchase_order.id='".$_REQUEST['purchase_order_id']."'"));
	 $maxiv=mysql_fetch_array(mysql_query("select max(id) as max_id from iv where customer_id='".$veniv[vendor_id]."'"));
		$argsiv['value']="'".(($maxiv[max_id]*1)+1)."','".$_REQUEST['purchase_order_id']."','".$veniv[vendor_id]."','".date("Y-m-d")."','".(date("y")+43).str_pad(($maxiv[max_id]+1), 6, '0', STR_PAD_LEFT)."','','','','0','',''";
		$har->insertDB($argsiv);	
	 
		
	$args['table']="pr";
	$args['value']="status='4'";
	$args['condition']="id='".$_REQUEST['ref']."'";
	$har->updateDb($args);
	
	exit("<script>window.location = 'index.php?page=compl_list'</script>");
	} else 
	if($_REQUEST['method']=="R2"){
	$args3['table']="receive";	 
	$args3['value']="'','ou".$_REQUEST['purchase_order_id']."','".$_REQUEST['deliv_id']."','".date('Y-m-d')."'";	
	$har->insertDB($args3);		
		} else
		if($_REQUEST['method']=="AD"){
	
	$args4['table']="send_out_item";	 
	$args4['value']="'".$_SESSION[company_id]."','".$_REQUEST['customer_id']."','".$_REQUEST['des']."'";	
	 $op_id=$har->insertDbMax($args4);
	
		$args5['table']="product";
	$i=0;
	foreach ($_REQUEST[type] as $type) {
		
		$m_pro=mysql_fetch_array(mysql_query("select max(product_id) as product_id from product"));
		$max_pro=$m_pro[product_id]+1;
		$args5['value']="'".$max_pro."','','".$_REQUEST[price][$i]."','".$_REQUEST[discount][$i]."','".$_REQUEST[brand_id][$i]."','".$_REQUEST[model][$i]."','".$product_type."','".$_REQUEST[quantity][$i]."','".$_REQUEST[pack_quantity][$i]."','".$op_id."','".$_REQUEST[des][$i]."','0','0','0','0000-00-00',''";
		//echo $args5['table']." | ".$args5['value']."<br>";
		$har->insertDB($args5);	
	$args['value']="'".$max_pro."','".$_REQUEST['s_n'][$i]."',''";
	 $store_id=$har->insertDbMax($args);
	//echo $args['table']." | ".$args['value']."<br>";
	$args2['value']="'','".$store_id."','".date("Y-m-d",strtotime($_REQUEST['warranty'][$i]))."','0','".$_SESSION[company_id]."'";
	//echo $args2['table']." | ".$args2['value']."<br>";
	
		$har->insertDB($args2);	
	
		
		
		$i++;
		}
	

	 
	 
	$args3['value']="'','','".date("Y-m-d",strtotime($_REQUEST['deliver_date']))."','". $op_id."'";
		$har->insertDB($args3);	
	
	
	}else {exit("<script>alert('window.location = 'index.php?page=".$_REQUEST['page']."'</script>");
    }

}break;
}

// =====================================================================
// AUDIT TRAIL HELPER FUNCTIONS - Phase 3 Step 4
// =====================================================================

/**
 * Set audit context before database operations
 * Call this at the start of each request to track user and IP
 */
function set_audit_context() {
    global $users;
    
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Set MySQL session variables that triggers will use
    try {
        mysqli_query(DbConn::getGlobalConnection(), "SET @audit_user_id = $user_id;");
        mysqli_query(DbConn::getGlobalConnection(), "SET @audit_ip_address = '$ip_address';");
    } catch (Exception $e) {
        error_log("Error setting audit context: " . $e->getMessage());
    }
}

/**
 * Get audit history for a specific record
 * 
 * @param string $table_name - Table being audited
 * @param int $record_id - ID of the record
 * @param int $limit - Max records to return
 * @return array - Audit log entries
 */
function get_audit_history($table_name, $record_id, $limit = 50) {
    $conn = DbConn::getGlobalConnection();
    $table_name = mysqli_real_escape_string($conn, $table_name);
    $record_id = (int)$record_id;
    
    $sql = "SELECT * FROM audit_log 
            WHERE table_name = '$table_name' AND record_id = $record_id 
            ORDER BY created_at DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $history = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return $history;
}

/**
 * Get audit log for a specific table
 * 
 * @param string $table_name - Table name to filter by
 * @param int $limit - Max records to return
 * @return array - Audit log entries
 */
function get_table_audit_log($table_name, $limit = 100) {
    $conn = DbConn::getGlobalConnection();
    $table_name = mysqli_real_escape_string($conn, $table_name);
    
    $sql = "SELECT * FROM audit_log 
            WHERE table_name = '$table_name' 
            ORDER BY created_at DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * Get audit log for a specific user
 * 
 * @param int $user_id - User ID to filter by
 * @param int $limit - Max records to return
 * @return array - Audit log entries
 */
function get_user_audit_log($user_id, $limit = 100) {
    $conn = DbConn::getGlobalConnection();
    $user_id = (int)$user_id;
    
    $sql = "SELECT * FROM audit_log 
            WHERE user_id = $user_id 
            ORDER BY created_at DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * Get recent audit log entries
 * 
 * @param int $hours - Get entries from last N hours
 * @param int $limit - Max records to return
 * @return array - Audit log entries
 */
function get_recent_audit_log($hours = 24, $limit = 100) {
    $conn = DbConn::getGlobalConnection();
    $hours = (int)$hours;
    
    $sql = "SELECT * FROM audit_log 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)
            ORDER BY created_at DESC LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    $logs = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    return $logs;
}

/**
 * Get audit statistics for dashboard
 * 
 * @return array - Statistics including counts by operation and table
 */
function get_audit_statistics() {
    $conn = DbConn::getGlobalConnection();
    
    $stats = array();
    
    // Total audit entries
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM audit_log");
    $row = mysqli_fetch_assoc($result);
    $stats['total_entries'] = $row['total'];
    
    // By operation
    $result = mysqli_query($conn, "SELECT operation, COUNT(*) as count FROM audit_log GROUP BY operation");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['by_operation'][$row['operation']] = $row['count'];
    }
    
    // By table
    $result = mysqli_query($conn, "SELECT table_name, COUNT(*) as count FROM audit_log GROUP BY table_name ORDER BY count DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['by_table'][$row['table_name']] = $row['count'];
    }
    
    // Last 24 hours
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $row = mysqli_fetch_assoc($result);
    $stats['last_24_hours'] = $row['count'];
    
    return $stats;
}

/**
 * Format audit log entry for display
 * 
 * @param array $entry - Audit log entry from database
 * @return string - Formatted HTML for display
 */
function format_audit_entry($entry) {
    $timestamp = date('Y-m-d H:i:s', strtotime($entry['created_at']));
    $table = htmlspecialchars($entry['table_name']);
    $operation = htmlspecialchars($entry['operation']);
    $description = htmlspecialchars($entry['description']);
    $user_id = $entry['user_id'] ? "User ID: {$entry['user_id']}" : 'System';
    $ip = htmlspecialchars($entry['ip_address']);
    
    return "
        <tr>
            <td>$timestamp</td>
            <td>$table</td>
            <td><span class='label label-{$operation}'>{$operation}</span></td>
            <td>#{$entry['record_id']}</td>
            <td>$description</td>
            <td>$user_id</td>
            <td>$ip</td>
        </tr>
    ";
}

?>
