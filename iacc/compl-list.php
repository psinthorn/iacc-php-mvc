<?php
	// require_once("inc/sys.configs.php");
	// require_once("inc/class.dbconn.php");
	// $dbconn = new DbConn($config);
<script type="text/javascript" language="javascript" src="TableFilter/tablefilter.js"></script>  <h2><i class="fa fa-thumbs-up"></i> <?=$xml->invoice?></h2>
<?php
	//$users->checkSecurity();
<table width="100%" id="table1" class="table table-hover">

<tr><th width="24%"><?=$xml->customer?></th><th width="10%"><?=$xml->inno?></th><th width="20%"><?=$xml->name?></th><th width="13%"><?=$xml->duedate?></th><th width="13%"><?=$xml->deliverydate?></th><th width="20%" colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id, iv.countmailinv, po.name as name, taxrw as tax, status_iv, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, pr.status from po join pr on po.ref=pr.id join company on pr.cus_id=company.id join iv on po.id=iv.tex where po_id_new='' and pr.ven_id='".mysqli_real_escape_string($db->conn, $_SESSION['com_id'] ?? '')."' and pr.status>='4' order by iv.id desc ");

if (!$query) {
    error_log("Database error in compl-list.php: " . mysqli_error($db->conn));
    echo "<tr><td colspan='7'>Error loading data</td></tr>";
} else {
    $cot=0;
    while($data=mysqli_fetch_array($query)){
	  $cot++;
	 if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
	
	 if($data['status']==2)$pg="po_deliv";else $pg="po_edit";
	 if(($data['status_iv']=="2")&&($data['status']=="4")){$statusiv="void";}
	 else if(($data['status']=="4")&&($data['valid_pay']<date("d-m-Y")))
	 {$statusiv="overdue";}else{$statusiv=decodenum($data['status']);}
echo "<tr ".$color."><td>".$data['name_en']."</td><td>INV-".$data['tax']."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$statusiv."</td><td width='10%' align='right'>";
if($data['status']!="5") echo "
<a href='index.php?page=compl_view&id=".$data['id']."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;";
echo "<a href='inv.php?id=".$data['id']."' target='blank'>IV</a>&nbsp;&nbsp;&nbsp;<a data-toggle='modal' href='model_mail.php?page=inv&id=".$data['id']."'   data-target='.bs-example-modal-lg'><i class='glyphicon glyphicon-envelope'></i><span class='badge'>".(isset($data['countmailinv']) ? $data['countmailinv'] : 0)."</span></a></td>
</tr>";
	
    }
}
<tr><th><?=$xml->vender?></th><th><?=$xml->inno?></th><th><?=$xml->name?></th><th><?=$xml->duedate?></th><th><?=$xml->deliverydate?></th><th colspan="2"><?=$xml->status?></th></tr>
<?php
$query=mysqli_query($db->conn, "select po.id as id, po.name as name, taxrw as tax, DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, pr.status from po join pr on po.ref=pr.id join company on pr.ven_id=company.id join iv on po.id=iv.tex where po_id_new='' and pr.cus_id='".$_SESSION['com_id']."' and pr.status>='4' order by iv.id desc ");

if ($query && mysqli_num_rows($query) > 0) {
    $cot=0;
    while($data=mysqli_fetch_array($query)){
        $cot++;
        if($cot%2)$color=" bgcolor='#eee'";else $color=" bgcolor='#fff'";
        $var=decodenum($data['status']);
        
        echo "<tr ".$color."><td>".$data['name_en']."</td><td>INV-".$data['tax']."</td><td>".$data['name']."</td><td>".$data['valid_pay']."</td><td>".$data['deliver_date']."</td><td>".$xml->$var."</td><td align='right'>";
        
        if($data['status']!="5") echo "
<a href='index.php?page=compl_view&id=".$data['id']."'><i class=\"fa fa-search-plus\"></i></a>&nbsp;&nbsp;&nbsp;";
        echo "<a href='inv.php?id=".$data['id']."' target='blank'>IV</a></td>
</tr>";	
    }
}
</table>

 <script type="text/javascript">
 
  var table2_Props = {
    col_0: "select",
	col_5: "select",
	col_6: "none",
     col_date_type: [null,null,null,'dmy','dmy',null],  
    display_all_text: " [ Show all ] ",
    on_filters_loaded: function(o){   
        o.SetFilterValue(3,'>01-09-2014');  
		o.SetFilterValue(4,'>01-09-2014');  
        o.Filter();  
    } ,
	sort_select: true
};
var tf2 = setFilterGrid("table1", table2_Props,2);</script>
<div id="fetch_state"></div>