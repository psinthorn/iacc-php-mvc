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
		
		// Register for compatibility layer
		__registerMySQLCompatConnection($this);
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

// ============================================================================
// Global Database Instance for Compatibility Functions
// This allows the legacy mysql_* functions to work
// ============================================================================
global $__MYSQL_COMPAT_CONNECTION;
$__MYSQL_COMPAT_CONNECTION = null;

/**
 * Register a database connection for use by compatibility functions
 * Called automatically by DbConn constructor
 */
function __registerMySQLCompatConnection($dbconn_object){
	global $__MYSQL_COMPAT_CONNECTION;
	if($dbconn_object && is_object($dbconn_object) && isset($dbconn_object->conn)){
		$__MYSQL_COMPAT_CONNECTION = $dbconn_object;
	}
}

// ============================================================================
// COMPATIBILITY LAYER: Old mysql_* function emulation for MySQLi
// These functions provide backward compatibility for legacy code
// ============================================================================

/**
 * Emulates mysql_query() using MySQLi
 * Usage: $result = mysql_query($sql, $connection);
 */
if(!function_exists('mysql_query')){
	function mysql_query($query, $link = null){
		global $__MYSQL_COMPAT_CONNECTION;
		$conn = null;
		
		// Use provided link if it's a DbConn object
		if($link && is_object($link) && isset($link->conn)){
			$conn = $link->conn;
		}
		// Otherwise try global connection
		else if($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)){
			$conn = $__MYSQL_COMPAT_CONNECTION->conn;
		}
		
		if(!$conn) return false;
		
		$result = $conn->query($query);
		if($result === false){
			error_log("mysql_query() Error: " . $conn->error . " | Query: " . $query);
			return false;
		}
		return $result;
	}
}

/**
 * Emulates mysql_fetch_array() using MySQLi
 * Usage: $row = mysql_fetch_array($result);
 */
if(!function_exists('mysql_fetch_array')){
	function mysql_fetch_array($result, $result_type = MYSQLI_BOTH){
		if(!$result) return false;
		return $result->fetch_array($result_type);
	}
}

/**
 * Emulates mysql_fetch_assoc() using MySQLi
 * Usage: $row = mysql_fetch_assoc($result);
 */
if(!function_exists('mysql_fetch_assoc')){
	function mysql_fetch_assoc($result){
		if(!$result) return false;
		return $result->fetch_assoc();
	}
}

/**
 * Emulates mysql_fetch_row() using MySQLi
 * Usage: $row = mysql_fetch_row($result);
 */
if(!function_exists('mysql_fetch_row')){
	function mysql_fetch_row($result){
		if(!$result) return false;
		return $result->fetch_row();
	}
}

/**
 * Emulates mysql_num_rows() using MySQLi
 * Usage: $count = mysql_num_rows($result);
 */
if(!function_exists('mysql_num_rows')){
	function mysql_num_rows($result){
		if(!$result) return 0;
		return $result->num_rows;
	}
}

/**
 * Emulates mysql_insert_id() using MySQLi
 * Usage: $id = mysql_insert_id();
 */
if(!function_exists('mysql_insert_id')){
	function mysql_insert_id(){
		global $__MYSQL_COMPAT_CONNECTION;
		if($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)){
			return $__MYSQL_COMPAT_CONNECTION->conn->insert_id;
		}
		return 0;
	}
}

/**
 * Emulates mysql_affected_rows() using MySQLi
 * Usage: $count = mysql_affected_rows();
 */
if(!function_exists('mysql_affected_rows')){
	function mysql_affected_rows(){
		global $__MYSQL_COMPAT_CONNECTION;
		if($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)){
			return $__MYSQL_COMPAT_CONNECTION->conn->affected_rows;
		}
		return 0;
	}
}

/**
 * Emulates mysql_error() using MySQLi
 * Usage: $error = mysql_error();
 */
if(!function_exists('mysql_error')){
	function mysql_error(){
		global $__MYSQL_COMPAT_CONNECTION;
		if($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)){
			return $__MYSQL_COMPAT_CONNECTION->conn->error;
		}
		return "";
	}
}

/**
 * Emulates mysql_real_escape_string() using MySQLi
 * Usage: $escaped = mysql_real_escape_string($string);
 */
if(!function_exists('mysql_real_escape_string')){
	function mysql_real_escape_string($unescaped_string){
		global $__MYSQL_COMPAT_CONNECTION;
		if($__MYSQL_COMPAT_CONNECTION && isset($__MYSQL_COMPAT_CONNECTION->conn)){
			return $__MYSQL_COMPAT_CONNECTION->conn->real_escape_string($unescaped_string);
		}
		return $unescaped_string;
	}
}

/**
 * Emulates mysql_data_seek() using MySQLi
 * Usage: mysql_data_seek($result, $row_number);
 */
if(!function_exists('mysql_data_seek')){
	function mysql_data_seek($result, $row_number){
		if(!$result) return false;
		return $result->data_seek($row_number);
	}
}
?>
	

			

?>