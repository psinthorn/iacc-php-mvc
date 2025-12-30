<?PHP
// Legacy database connection file - Updated to use mysqli
// Note: Consider using class.dbconn.php instead which is the primary connection class

if(isset($_SESSION['lang']) && $_SESSION['lang']=="1")$lg="th";else $lg="us";
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
	public $conn;
	
	function __construct($config){
		$this->conn = mysqli_connect($config['hostname'], $config['username'], $config['password'], $config['dbname']) or die(mysqli_connect_error());
		mysqli_set_charset($this->conn, "utf8");
		mysqli_query($this->conn, "SET NAMES utf8");
	}
	
	function closeDb() {
		if($this->conn) {
			mysqli_close($this->conn);
		}
	}

	
	function checkSecurity(){ 
		if (!isset($_SESSION['usr_id']) || $_SESSION['usr_id']=="") {
			exit("<script>alert('Please Login');window.location='login.php';</script>");
		}
	}
	
	
	}
	

			

?>