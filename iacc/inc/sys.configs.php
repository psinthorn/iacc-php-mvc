<?PHP
// SERVER : MYSQL Cnfiguration
$config["hostname"] = "mysql";
$config["username"] = "root";
//$config["username"] = "theiconn_cms";
$config["password"] = "root";
// $config["dbname"]   = "root";
$config["dbname"]   = "iacc";

// Sets the default timezone
date_default_timezone_set("Asia/Bangkok"); 

// Backward compatibility wrappers for deprecated mysql_* functions
// These redirect to the DbConn class methods
if (!function_exists('mysql_query')) {
	function mysql_query($sql) {
		return DbConn::query($sql);
	}
}

if (!function_exists('mysql_fetch_array')) {
	function mysql_fetch_array($result) {
		return DbConn::fetch_array($result);
	}
}

if (!function_exists('mysql_num_rows')) {
	function mysql_num_rows($result) {
		return DbConn::num_rows($result);
	}
}

if (!function_exists('mysql_error')) {
	function mysql_error() {
		return DbConn::error();
	}
}

// SERVER : MYSQL Cnfiguration
//$config["hostname"] = "localhost";
//$config["username"] = "root";
//$config["username"] = "theiconn_cms";
//$config["password"] = ")q#gLfESG;M(";
//$config["dbname"]   = "ngt-admin";
//$config["dbname"]   = "theiconn_cms";

?>