<?php
/*
Tractum.php
Version 1.3
Feburary 2013

Documentation: 
Repository: 

Copyright 2013 Kyle Jasso

Service Agreenment and Liecences are provided below.
*/

/* == Setup Instructions ============================================ */
	// Steps and procedues
	// Required to install
	// This software

	$team            = array();
	$secondaryBranch = array();
	$secondaryURL    = array();
	
/* == Default Settings ============================================== */
	$projectName     = "";
	$branch          = "";
	$emailTrigger    = "";
	
	$team[]          = "";
	
	$salt            = "";
	$pass            = "";

/* == API Functions ================================================= */
$tractum = new tractum();

if(isset($_GET['update'])) {
	$incoming = $tractum->chechHashSSHA($salt, $_GET['update']);

	if($pass == $incoming) {
		$json = str_replace('\\', '', $_POST['payload']);
		$obj = json_decode($json,true);

		$branch_name = $branch_name[2];

		$perform = $tractum->gitPull($branch_name,$obj);

	} else {
		$message = "This message is to alert you that an unauthorized attempt was made on $_SERVER[SCRIPT_FILENAME] at ".date().". The attempt came from $_SERVER[REMOTE_ADDR].";
		$icPWD = $tractum->emailNotification($email, "$_SERVER[SCRIPT_NAME]@$_SERVER[SERVER_NAME].com","$projectName - Unauthorized Access",$message);
	}
} else if(isset($_GET['passgen'])) {
	$hash = $tractum->hashSSHA($password = $_GET['passgen']);
	$html = "";
	if($hash) {
		$callURL = "http";

		if(isset($_SERVER['HTTPS'])) $callURL .= "s";

		$callURL .= "://$_SERVER[SERVER_NAME].$_SERVER[SCRIPT_NAME]?update=$_GET[passgen]";

		$html .= "<h1>Remote Server Settings</h1>";
		$html .= "<label>Salt</label>";
		$html .= "<input type='text' value='$hash[salt]' />";
		$html .= "<label>Password</label>";
		$html .= "<input type='text' value='$hash[encrypted]' >";
		$html .= "<label>Webhook URL</label>";
		$html .= "<input type='text' value='$callURL' />";


	} else {
		echo "No Hash";
	}
} else {
	echo "Incorrect Parameters";
}


/* == Class Constructor ============================================= */
class tractum {
	public function hashSSHA($password) {
		$salt = sha1(rand());
		$salt = substr($salt,0,10);
		$encrypted = base64_encode(sha1($password . $salt, true) . $salt);

		return array("salt" => $salt, "encrypted" => $encrypted);
	}

	public function checkhashSSHA($salt, $password) {
		return base64_encode(sha1($password . $salt, true) . $salt);
	}

	public function gitPull($branch_name,$obj) {

	}

	private function emailNotificaton($to, $from, $subject, $message) {

	}

	private function writeDebug($title, $message) {

	}
}





/*
Licenced under the MIT licence:

This file is part of Remote-Server-Autopull (RSA).

RSA is a free software that may be redistributed, and/or modified
under the terms of the GNU General Public Licence as published by
the Free Software Foundation.

RSA is distributed WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public Licence for more details.

The creators of this software are not responsible for any security flaws
or corruption made to servers and files during setup. A working knowledge of
the bash language and Lunix server structure is recommended before attempting
to set up this application.
*/


?>
<!doctype html>
<html>
<head>
	<title>Remote Server Autopull</title>

	<style type="text/css">
		@import url(http://fonts.googleapis.com/css?family=Titillium+Web:400,300,600);
		body 			{ background-color:#000040; font-family:'Titillium Web', sans-serif; }
		a 				{ color:#fff; text-decoration:none; float:right; margin-top:10px; }
		a:hover 		{ text-decoration: none; color:#0080C0; }
		h1 				{ display:block; padding:20px; background-color:#0080C0; color:#fff; }
		input			{ display:block; padding:20px 10px; margin:10px 20px; width:540px; }
		textarea 		{ display:block; }
		label 			{ display:block; margin:10px 22px; }
		.container 		{ width:600px; margin:5% auto; background-color:#fff; padding:0 0 10px 0;}

	</style>
</head>
<body>

<div class="container">
	<?php echo $html; ?>

	<a href="https://github.com/jassok/Remote-Server-Autopull" target="_blank">Remote Server Autopull</a>
</div>

</body>
</html>