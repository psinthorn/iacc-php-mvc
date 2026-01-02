<?PHP

if($_SESSION[lang]=="1")$lg="th";else $lg="us";
$xml=simplexml_load_file("inc/string-".$lg.".xml");
function decodestatus($num){
	if($num==1)return "yes";
	else return "no";
	}
	
function decodenum($num){
	if($num==0)return "processpr";
	else if($num==1)return "processquo";
	else if($num==2)return "processpo";
	else if($num==3)return "processdeli";
	else if($num==4)return "processpaid";
	else if($num==5)return "success";
	}
class DbConn{ 
	var $conn;
	
	function DbConn($config){

		$this->conn = mysql_connect($config['hostname'], $config['username'], $config['password']) or die(mysql_error());
		mysql_query("SET NAMES utf8");
		//หากเชื่อมต่อด้วย mysqli_connect ยกเลิกบรรทัดนี้ mysql_select_database เพราะ mysqli_connect ให้กำหนดใน DSN อยู่แล้ว
		mysql_select_db($config['dbname']) or die(mysql_error());
	}
	
	function closeDb() {
		mysql_close($this->conn);
	}

	
	function checkSecurity(){ 
		if ($_SESSION['user_id']=="") {
			exit("<script>alert('Please Login');window.location='login.php';</script>");
		}
	}
	
	
	}
	

			

?>