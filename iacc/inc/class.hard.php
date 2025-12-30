<?PHP 
class HardClass { 
	private $conn;
	
	function __construct($conn = null) {
		$this->conn = $conn;
	}
	
	function setConnection($conn) {
		$this->conn = $conn;
	}
	
	function keeplog($data){
		$query_string = '';
		foreach($data as $key => $val)
		{
			$query_string .=$key."|".$val.":";
		}
		$syslog['table']="keep_log";
		$syslog['value']="'".htmlspecialchars($query_string)."'";
		$this->insertDb($syslog);
		
		}
	function insertDbMax($args){
		$id=$this->Maxid($args['table']);
		mysqli_query($this->conn, "INSERT INTO ".$args['table']." VALUES ('".$id."',".$args['value'].")") ;
		return $id;
		}
	function insertDb($args){
		if($this->conn) {
			mysqli_query($this->conn, "INSERT INTO ".$args['table']." VALUES (".$args['value'].")") ;
		}
		//echo "INSERT INTO ".$args['table']." VALUES (".$args['value'].")";
		}
	
	
	function updateDb($args){
		mysqli_query($this->conn, "UPDATE ".$args['table']." SET ".$args['value']." WHERE ".$args['condition']);
	//echo "UPDATE ".$args['table']." SET ".$args['value']." WHERE ".$args['condition'];
		}
		
	function deleteDb($args){
		mysqli_query($this->conn, "Delete FROM ".$args['table']." WHERE ".$args['condition']);
		//echo "Delete FROM ".$args['table']." WHERE ".$args['condition'];
		}	
	
	function Maxid($args){
		//echo "select max(id) as id FROM ".$args;
		$row=mysqli_fetch_array(mysqli_query($this->conn, "select max(id) as id FROM ".$args));
		
		if($row['id']=="") return 1; else return $row['id']+1;
		}
		
	function uploadpic($args){
		$prop_thumb	=	$args['pic'];
		if($prop_thumb['error']==0){
			$info 		= pathinfo($prop_thumb['name']);
			$filetype	= strtolower($info['extension']);
				if(($filetype=='gif'||$filetype=='jpg'||$filetype=='png')&&($info!="")){
					$tmppic = time().rand().".$filetype";
					move_uploaded_file($prop_thumb['tmp_name'],"../upload/$tmppic");		
					return $tmppic;	
				}
			}	
	}
		
	function uploadpic2($args){
		$prop_thumb	=	$args['pic2'];
		if($prop_thumb['error']==0){
			$info 		= pathinfo($prop_thumb['name']);
			$filetype	= strtolower($info['extension']);
				if(($filetype=='gif'||$filetype=='jpg'||$filetype=='png')&&($info!="")){
					$tmppic = time().rand().".$filetype";
					move_uploaded_file($prop_thumb['tmp_name'],"../upload/$tmppic");		
					return $tmppic;					
				}
			}	
	}
		
	}	
?>