<?PHP 
class HardClass { 
	function keeplog($data){
		while(list($key, $val) = each($data))
		{
			$query_string .=$key."|".$val.":";
		}
		$syslog['table']="keep_log";
		$syslog['value']="'".htmlspecialchars($query_string)."'";
		$this->insertDb($syslog);
		
		}
	function insertDbMax($args){
		$id=$this->Maxid($args['table']);
		mysql_query("INSERT INTO ".$args['table']." VALUES ('".$id."',".$args['value'].")") ;
		return $id;
		}
	function insertDb($args){
		mysql_query("INSERT INTO ".$args['table']." VALUES (".$args['value'].")") ;
		//echo "INSERT INTO ".$args['table']." VALUES (".$args['value'].")";
		}
	
	
	function updateDb($args){
		mysql_query("UPDATE ".$args['table']." SET ".$args['value']." WHERE ".$args['condition']);
	//echo "UPDATE ".$args['table']." SET ".$args['value']." WHERE ".$args['condition'];
		}
		
	function deleteDb($args){
		mysql_query("Delete FROM ".$args['table']." WHERE ".$args['condition']);
		//echo "Delete FROM ".$args['table']." WHERE ".$args['condition'];
		}	
	
	function Maxid($args){
		//echo "select max(id) as id FROM ".$args;
		$row=mysql_fetch_array(mysql_query("select max(id) as id FROM ".$args));
		
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