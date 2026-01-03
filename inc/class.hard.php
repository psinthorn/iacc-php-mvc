<?PHP 
/**
 * HardClass - Database abstraction layer
 * 
 * Contains both legacy string-based methods and new safe prepared statement methods.
 * New code should use the *Safe methods for better security.
 */
class HardClass { 
	
	/** @var mysqli Database connection */
	private $conn;
	
	/**
	 * Set the database connection for prepared statements
	 */
	public function setConnection($conn) {
		$this->conn = $conn;
	}
	
	/**
	 * Get connection (uses global $db if not set)
	 */
	private function getConn() {
		if ($this->conn) {
			return $this->conn;
		}
		global $db;
		if (isset($db) && isset($db->conn)) {
			return $db->conn;
		}
		return null;
	}
	
	// =====================================================
	// SAFE PREPARED STATEMENT METHODS (USE THESE FOR NEW CODE)
	// =====================================================
	
	/**
	 * Safe INSERT using prepared statements
	 * 
	 * @param string $table Table name
	 * @param array $data Associative array of column => value pairs
	 * @return int|false Insert ID or false on failure
	 * 
	 * Usage: $hard->insertSafe('company', ['name_en' => $name, 'phone' => $phone]);
	 */
	public function insertSafe($table, $data) {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$columns = array_keys($data);
		$values = array_values($data);
		$placeholders = str_repeat('?,', count($values) - 1) . '?';
		$types = $this->getTypes($values);
		
		$sql = "INSERT INTO `" . $this->escapeIdentifier($table) . "` (`" . 
			implode('`, `', array_map([$this, 'escapeIdentifier'], $columns)) . 
			"`) VALUES ($placeholders)";
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		$stmt->bind_param($types, ...$values);
		$result = $stmt->execute();
		$insertId = $stmt->insert_id;
		$stmt->close();
		
		return $result ? $insertId : false;
	}
	
	/**
	 * Safe INSERT with auto-increment ID
	 * 
	 * @param string $table Table name
	 * @param array $data Associative array of column => value pairs (without id)
	 * @return int The generated ID
	 */
	public function insertSafeMax($table, $data) {
		$id = $this->Maxid($table);
		$data = array_merge(['id' => $id], $data);
		$result = $this->insertSafe($table, $data);
		return $result !== false ? $id : false;
	}
	
	/**
	 * Safe UPDATE using prepared statements
	 * 
	 * @param string $table Table name
	 * @param array $data Associative array of column => value pairs to update
	 * @param array $where Associative array of column => value pairs for WHERE clause
	 * @return bool Success
	 * 
	 * Usage: $hard->updateSafe('company', ['name_en' => $name], ['id' => $id]);
	 */
	public function updateSafe($table, $data, $where) {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$setParts = [];
		$values = [];
		foreach ($data as $col => $val) {
			$setParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
			$values[] = $val;
		}
		
		$whereParts = [];
		foreach ($where as $col => $val) {
			$whereParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
			$values[] = $val;
		}
		
		$types = $this->getTypes($values);
		$sql = "UPDATE `" . $this->escapeIdentifier($table) . "` SET " . 
			implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		$stmt->bind_param($types, ...$values);
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	/**
	 * Safe DELETE using prepared statements
	 * 
	 * @param string $table Table name
	 * @param array $where Associative array of column => value pairs for WHERE clause
	 * @return bool Success
	 * 
	 * Usage: $hard->deleteSafe('company', ['id' => $id]);
	 */
	public function deleteSafe($table, $where) {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$whereParts = [];
		$values = [];
		foreach ($where as $col => $val) {
			$whereParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
			$values[] = $val;
		}
		
		$types = $this->getTypes($values);
		$sql = "DELETE FROM `" . $this->escapeIdentifier($table) . "` WHERE " . 
			implode(' AND ', $whereParts);
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		$stmt->bind_param($types, ...$values);
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	/**
	 * Safe SELECT using prepared statements
	 * 
	 * @param string $table Table name
	 * @param array $where Associative array of column => value pairs for WHERE clause
	 * @param string $columns Columns to select (default *)
	 * @return array|false Array of rows or false on failure
	 * 
	 * Usage: $rows = $hard->selectSafe('company', ['id' => $id]);
	 */
	public function selectSafe($table, $where = [], $columns = '*') {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$sql = "SELECT $columns FROM `" . $this->escapeIdentifier($table) . "`";
		$values = [];
		
		if (!empty($where)) {
			$whereParts = [];
			foreach ($where as $col => $val) {
				$whereParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
				$values[] = $val;
			}
			$sql .= " WHERE " . implode(' AND ', $whereParts);
		}
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		if (!empty($values)) {
			$types = $this->getTypes($values);
			$stmt->bind_param($types, ...$values);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = [];
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		$stmt->close();
		
		return $rows;
	}
	
	/**
	 * Get single row using prepared statements
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions
	 * @param string $columns Columns to select
	 * @return array|null Single row or null
	 */
	public function selectOneSafe($table, $where, $columns = '*') {
		$rows = $this->selectSafe($table, $where, $columns);
		return (!empty($rows)) ? $rows[0] : null;
	}
	
	/**
	 * Determine parameter types for mysqli prepared statements
	 */
	private function getTypes($values) {
		$types = '';
		foreach ($values as $val) {
			if (is_int($val)) {
				$types .= 'i';
			} elseif (is_float($val) || is_double($val)) {
				$types .= 'd';
			} else {
				$types .= 's';
			}
		}
		return $types;
	}
	
	/**
	 * Escape identifier (table/column name) to prevent SQL injection
	 */
	private function escapeIdentifier($identifier) {
		return str_replace('`', '``', $identifier);
	}
	
	// =====================================================
	// SOFT DELETE METHODS (FOR AUDIT COMPLIANCE)
	// =====================================================
	
	/**
	 * Soft delete a record (sets deleted_at timestamp instead of removing)
	 * 
	 * Requires table to have a `deleted_at` DATETIME column (nullable).
	 * Run this migration on tables you want soft delete:
	 * ALTER TABLE `tablename` ADD `deleted_at` DATETIME NULL DEFAULT NULL;
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions
	 * @return bool Success
	 * 
	 * Usage: $hard->softDelete('company', ['id' => $id]);
	 */
	public function softDelete($table, $where) {
		return $this->updateSafe($table, ['deleted_at' => date('Y-m-d H:i:s')], $where);
	}
	
	/**
	 * Restore a soft-deleted record
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions
	 * @return bool Success
	 */
	public function restore($table, $where) {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$whereParts = [];
		$values = [];
		foreach ($where as $col => $val) {
			$whereParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
			$values[] = $val;
		}
		
		$sql = "UPDATE `" . $this->escapeIdentifier($table) . "` SET `deleted_at` = NULL WHERE " . 
			implode(' AND ', $whereParts);
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		$types = $this->getTypes($values);
		$stmt->bind_param($types, ...$values);
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	/**
	 * Select records excluding soft-deleted ones
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions
	 * @param string $columns Columns to select
	 * @return array|false Array of non-deleted rows
	 */
	public function selectActiveSafe($table, $where = [], $columns = '*') {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$sql = "SELECT $columns FROM `" . $this->escapeIdentifier($table) . "` WHERE `deleted_at` IS NULL";
		$values = [];
		
		if (!empty($where)) {
			foreach ($where as $col => $val) {
				$sql .= " AND `" . $this->escapeIdentifier($col) . "` = ?";
				$values[] = $val;
			}
		}
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		if (!empty($values)) {
			$types = $this->getTypes($values);
			$stmt->bind_param($types, ...$values);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = [];
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		$stmt->close();
		
		return $rows;
	}
	
	/**
	 * Select only soft-deleted records (for recovery/audit)
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions
	 * @param string $columns Columns to select
	 * @return array|false Array of deleted rows
	 */
	public function selectDeletedSafe($table, $where = [], $columns = '*') {
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$sql = "SELECT $columns FROM `" . $this->escapeIdentifier($table) . "` WHERE `deleted_at` IS NOT NULL";
		$values = [];
		
		if (!empty($where)) {
			foreach ($where as $col => $val) {
				$sql .= " AND `" . $this->escapeIdentifier($col) . "` = ?";
				$values[] = $val;
			}
		}
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		if (!empty($values)) {
			$types = $this->getTypes($values);
			$stmt->bind_param($types, ...$values);
		}
		
		$stmt->execute();
		$result = $stmt->get_result();
		$rows = [];
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		$stmt->close();
		
		return $rows;
	}
	
	/**
	 * Permanently delete a soft-deleted record (hard delete)
	 * Use with caution - this is permanent!
	 * 
	 * @param string $table Table name
	 * @param array $where WHERE conditions (must include deleted_at check)
	 * @return bool Success
	 */
	public function forceDelete($table, $where) {
		// Only allow deleting records that are already soft-deleted
		$conn = $this->getConn();
		if (!$conn) return false;
		
		$whereParts = ["`deleted_at` IS NOT NULL"];
		$values = [];
		foreach ($where as $col => $val) {
			$whereParts[] = "`" . $this->escapeIdentifier($col) . "` = ?";
			$values[] = $val;
		}
		
		$types = $this->getTypes($values);
		$sql = "DELETE FROM `" . $this->escapeIdentifier($table) . "` WHERE " . 
			implode(' AND ', $whereParts);
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
		
		if (!empty($values)) {
			$stmt->bind_param($types, ...$values);
		}
		$result = $stmt->execute();
		$stmt->close();
		
		return $result;
	}
	
	// =====================================================
	// LEGACY METHODS (KEPT FOR BACKWARD COMPATIBILITY)
	// =====================================================
	
	function keeplog($data){
		while(list($key, $val) = each($data))
		{
			$query_string .=$key."|".$val.":";
		}
		$syslog['table']="keep_log";
		$syslog['value']="'".htmlspecialchars($query_string)."'";
		$this->insertDb($syslog);
		
		}
	/**
	 * @deprecated Use insertSafeMax() instead for prepared statements
	 * Legacy method kept for backward compatibility - now uses MySQLi
	 */
	function insertDbMax($args){
		$id=$this->Maxid($args['table']);
		$conn = $this->getConn();
		// Note: This still uses string concat for backward compatibility
		// New code should use insertSafeMax() instead
		$sql = "INSERT INTO ".$conn->real_escape_string($args['table'])." VALUES ('".$id."',".$args['value'].")";
		$conn->query($sql);
		return $id;
		}
	
	/**
	 * @deprecated Use insertSafe() instead for prepared statements
	 * Legacy method kept for backward compatibility - now uses MySQLi
	 */
	function insertDb($args){
		$conn = $this->getConn();
		// Note: This still uses string concat for backward compatibility
		// New code should use insertSafe() instead
		$sql = "INSERT INTO ".$conn->real_escape_string($args['table'])." VALUES (".$args['value'].")";
		$conn->query($sql);
		//echo "INSERT INTO ".$args['table']." VALUES (".$args['value'].")";
		}
	
	
	/**
	 * @deprecated Use updateSafe() instead for prepared statements
	 * Legacy method kept for backward compatibility - now uses MySQLi
	 */
	function updateDb($args){
		$conn = $this->getConn();
		// Note: This still uses string concat for backward compatibility
		// New code should use updateSafe() instead
		$sql = "UPDATE ".$conn->real_escape_string($args['table'])." SET ".$args['value']." WHERE ".$args['condition'];
		$conn->query($sql);
	//echo "UPDATE ".$args['table']." SET ".$args['value']." WHERE ".$args['condition'];
		}
	
	/**
	 * @deprecated Use deleteSafe() instead for prepared statements
	 * Legacy method kept for backward compatibility - now uses MySQLi
	 */
	function deleteDb($args){
		$conn = $this->getConn();
		// Note: This still uses string concat for backward compatibility
		// New code should use deleteSafe() instead
		$sql = "DELETE FROM ".$conn->real_escape_string($args['table'])." WHERE ".$args['condition'];
		$conn->query($sql);
		//echo "Delete FROM ".$args['table']." WHERE ".$args['condition'];
		}	
	
	/**
	 * Get max ID from table and return next ID
	 * Updated to use MySQLi
	 */
	function Maxid($args){
		$conn = $this->getConn();
		$table = $conn->real_escape_string($args);
		$result = $conn->query("SELECT MAX(id) as id FROM ".$table);
		$row = $result->fetch_assoc();
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