<?php
session_start();
require_once("inc/sys.configs.php");
require_once("inc/class.dbconn.php");
$users=new DbConn($config);
$users->checkSecurity();
$html = '
<img src="images/logo.png" style="float:left;">
<h2>'.$travelag[name].'</h2>
Adr.: '.$travelag[address].',Phone: '.$travelag[telephone].'<br>Fax: '.$travelag[fax].', Email: '.$travelag[email].', Web: '.$travelag[web].'<hr />
<p>Dear '.$booking[f_name].' '.$booking[l_name].'<br>
Thank you for making a reservation at '.$booking[dealer_name].'<br>
Please send fax your payin slip to the hotel.
Kindly fax back to '.$booking[dealer_fax].'<br>or scan and e-mail to '.$booking[dealer_email].'<br>(If you are not able to fax us the form, you can scan the payin slip and attach with e-mail instead).
<br><br>Best Regards,
<br>
Reservation Manager</p>
<hr />

<h2>HOTEL BANK ACCOUNT DETAILS</h2>
<h3>Bank Account</h3>
Bank: Bangkok Bank<br>
Branch: Buddy Samui<br>
Account: type Saving<br>
Account: name Somsak Sukasem<br>
Account no.: 9580028976<br>
SWIFT Code: BBL<br>
Deposit Required: '.$booking[price].'<br>
<h3>CONTACT PERSON DETAILS</h3>
Guest name: '.$booking[f_name].' '.$booking[l_name].'<br>
Email: '.$booking[email].'<br>
Phone: '.$booking[phone].'<br>
<h3>BOOKING INFORMATION</h3>
Package Name: '.$booking[pac_name].'<br>
Booking date: '.$booking[bookdate].'<br>
Tour Date: '.$booking[date_tour].'<br>
Booking no. '.str_pad($booking[id], 7, '0', STR_PAD_LEFT).'<br>
Adult: '.$booking[adult].'<br>
Child: '.$booking[child].'<br>
';



//==============================================================
//==============================================================
//==============================================================

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
include("MPDF/mpdf.php");
error_reporting(E_ALL);

$mpdf=new mPDF(); 

$mpdf->WriteHTML($html);
$mpdf->Output();
exit;

//==============================================================
//==============================================================
//==============================================================


?>