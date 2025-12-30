<?PHP
error_reporting(E_ALL & ~E_NOTICE);
//error_reporting(E_ALL);
mb_internal_encoding("UTF-8");
if(isset($_SESSION['lang']) && $_SESSION['lang']=="1")$lg="th";else $lg="us";
$xml=simplexml_load_file("inc/string-".$lg.".xml", "SimpleXMLElement", LIBXML_NOCDATA);
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
class DbConn { 
	public $conn;
	private $config;

	function __construct($config) {
		$this->config = $config;
		$this->conn = mysqli_connect($config['hostname'], $config['username'], $config['password'], $config["dbname"]) or die(mysqli_error());
		
		// Set charset using the proper mysqli function
		if (!mysqli_set_charset($this->conn, "utf8mb4")) {
			die("Error: Unable to set utf8mb4 charset: " . mysqli_error($this->conn));
		}
		
		// Also execute SET NAMES as backup
		mysqli_query($this->conn, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
		mysqli_query($this->conn, "SET CHARACTER SET utf8mb4");
		mysqli_query($this->conn, "SET COLLATION_CONNECTION = utf8mb4_unicode_ci");
	}
	
	function closeDb() {
		if($this->conn) {
			mysqli_close($this->conn);
		}
	}

	function checkSecurity(){ 
		if (!isset($_SESSION['usr_id']) || $_SESSION['usr_id'] === "") {
			exit("<script>alert('Please Login');window.location='login.php';</script>");
		}
	}
}
	

			

?>