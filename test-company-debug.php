<?php
require_once('inc/sys.configs.php');
require_once('inc/class.dbconn.php');
require_once('inc/security.php');
$db = new DbConn($config);

$id = 156;
echo "Testing company ID: $id\n";

$query = mysqli_query($db->conn, "SELECT * FROM company WHERE id='$id'");
echo 'Company rows: ' . mysqli_num_rows($query) . "\n";

if(mysqli_num_rows($query)==1){
    $data = mysqli_fetch_array($query);
    echo 'Company: ' . $data['name_en'] . "\n";
}

$addr_all = mysqli_query($db->conn, "SELECT id, com_id, adr_tax, valid_start, valid_end FROM company_addr WHERE com_id='$id'");
echo "\nAll addresses for company $id: " . mysqli_num_rows($addr_all) . " records\n";
while($a = mysqli_fetch_assoc($addr_all)) {
    echo "  ID: {$a['id']}, valid_end: {$a['valid_end']}, adr_tax: " . substr($a['adr_tax'],0,30) . "\n";
}

$addr_query = mysqli_query($db->conn, 
    "SELECT * FROM company_addr WHERE com_id='$id' AND deleted_at IS NULL ORDER BY (valid_end = '0000-00-00') DESC, valid_start DESC LIMIT 1"
);
echo "\nAddress query result: " . mysqli_num_rows($addr_query) . " rows\n";
if(mysqli_num_rows($addr_query) > 0) {
    $addr = mysqli_fetch_assoc($addr_query);
    echo "Address ID: " . $addr['id'] . "\n";
    echo "adr_tax: " . $addr['adr_tax'] . "\n";
}
