<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
require_once("inc/security.php");
require_once("inc/class.company_filter.php");
$db = new DbConn($config);
$db -> checkSecurity();

// Company filter for multi-tenant data isolation
$companyFilter = CompanyFilter::getInstance();
?>
<!DOCTYPE html>
<html>

<head>
<?php include_once "css.php";?>
    <script type="text/javascript">
    function makeSelection(ptxt,ptxt2,ptxt3) {      
    if(!ptxt)        
      return;      
        opener.targetElement.value = ptxt;
        opener.targetElement2.value = ptxt2;
        opener.targetElement3.value = ptxt3;
        opener.targetElement5.value = opener.targetElement4.value*ptxt3;
        opener.sumall();
        this.close();   
        }    
    </script>
    
</head>
<body style="background-color:#FFF;">
<ul class="nav nav-tabs">
  <?php 
  $querycat = mysqli_query($db->conn, "select * from category WHERE 1=1" . $companyFilter->andCompanyFilter('category'));
  $configs=' class="active"';
  $configs2=' active';
  $data2="";
  while($datacat=mysqli_fetch_array($querycat)){
    echo '
    <li '.$configs.'><a href="#'.$datacat['id'].'" data-toggle="tab">'.$datacat['cat_name'].'</a></li>';
    $query_type=mysqli_query($db->conn, "select * from type where cat_id='".$datacat['id']."'" . $companyFilter->andCompanyFilter('type'));
    $dataall="";
  while($datatype=mysqli_fetch_array($query_type)){
    // SECURITY FIX: Add company filter to product aggregation query via type relationship
    $sql = "select sum(p.price)/sum(p.quantity) as net from product p 
            join type t on p.type = t.id 
            where p.type='".$datatype['id']."'" . $companyFilter->andCompanyFilter('t');
    $query = mysqli_query($db->conn, $sql);
	  $netpr = mysqli_fetch_array($query);
	  $dataall.="<a href=\"javascript:makeSelection('".$datatype['name']."','".$datatype['id']."','".floor($netpr['net'])."');\"><div style='width:230px;  float:left;  border-radius:5px; border:solid thin #ddd; padding:8px; margin:3px;'>".$datatype['name']."</div></a>";
    }
      $data2.='<div class="tab-pane '.$configs2.'" id="'.$datacat['id'].'">'.$dataall.'</div>';
      $configs="";
      $configs2="";
    }
    ?>
  </ul>

  <div class="tab-content"  >
    <?php echo $data2;?>
  </div>

  <script>
    $(function () {
      $('#myTab a:last').tab('show')
    })
  </script>
  <?php include_once "script.php";?>  
</body>
</html>
