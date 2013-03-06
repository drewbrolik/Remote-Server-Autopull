<?php
/*
Tractum.php
Version 1.3
Feburary 2013

Repository: https://github.com/jassok/Remote-Server-Autopull

Copyright 2013 Kyle Jasso

Service Agreenment and Liecences are provided below.
*/

/* == Setup Instructions ============================================ */
	// 1) Install and configure your remote server with Git and GitHub
	// 2) Clone or create a repository on your server
	// 3) Set $projectName, $branch, $emailTrigger, $team[]
	// 4) Navigate to http://www.yoursite.com/tractum.php?passgen=yourpassword
	// 5) Set $salt and $pass in this file.
	// 6) Add the URL provided to your repositorys Webhooks

	$team              = array(); // DOES NOT NEED TO BE CHANGED
	$secondaryBranch   = array(); // DOES NOT NEED TO BE CHANGED
	$secondaryURL      = array(); // DOES NOT NEED TO BE CHANGED

/* == Default Settings ============================================== */

	$projectName       = "";
	$branch            = "";
	$emailTrigger      = "";
	
	$secondaryBranch[] = "";
	$secondaryURL[]    = "";
	
	$team[]            = "";
	
	$salt              = "";
	$pass              = ""; 

//If you're having problems pulling, uncomment these two lines and check debug_log.txt
// echo `whoami`;
// echo `git pull`;

// Nothing below here needs to be modified.
/* == API Functions ================================================= */
$tractum = new tractum();
$html = "";

if(isset($_GET['update'])) {
	$incoming = $tractum->checkHashSSHA($salt, $_GET['update']);
	$debug = $tractum->writeDebug("Payload",$_POST['payload']);

	if($pass == $incoming) {
		if(isset($_POST['payload'])) {
			$email = implode(', ',$team);

			$json = str_replace('\\', '', $_POST['payload']);
			$obj = json_decode($json,true);
			$branch_name = $branch_name[2];

			$perform = $tractum->gitPull($email,$projectName,$emailTrigger,$branch,$obj);

			if($perform['error_code']) {

				$remote = $tractum->curlFunction($secondaryBranch,$secondaryURL,$projectName,$emailTrigger,$obj,$email);

				if($remote) {
					$html .= "<h1>Curl Resposne</h1>";
					$html .= "$remote";
				} else {
					$html .= "<h1 class='warning'>No cUrl Response</h1>";
					$html .= "A response was not recieved from the server.";
				}
			} else {
				$html .= "<h1>Pull Results</h1>";
				$html .= "<p>$perform</p>";
			}

		} else {
			$html .= "<h1 class='warning'>Warning!</h1>";
			$html .= "<p>No JSON data was received!</p>";
		}

	} else {
		$message = "This message is to alert you that an unauthorized attempt was made on $_SERVER[SCRIPT_FILENAME] at ".date().". The attempt came from $_SERVER[REMOTE_ADDR].";
		$icPWD = $tractum->emailNotification($email, "$_SERVER[SCRIPT_NAME]@$_SERVER[SERVER_NAME].com","$projectName - Unauthorized Access",$message);
	}
} else if(isset($_GET['passgen'])) {
	$hash = $tractum->hashSSHA($password = $_GET['passgen']);

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
		$html .= "<h1 class='warning'>Error</h1>";
		$html .= "<p>An error occurred while generating your password.</p>";
	}
} else {
	$html .= "<h1 class='warning'>Warning!</h1>";
	$html .= "<p>The parameters you included for this file were incorrect.</p>";
}


/* == Class Constructor ============================================= */
class tractum {
	public function hashSSHA($password) {
		$salt = sha1(rand());
		$salt = substr($salt,0,10);
		$encrypted = base64_encode(sha1($password . $salt, true) . $salt);

		return array("salt" => $salt, "encrypted" => $encrypted);
	}

	public function checkHashSSHA($salt, $password) {
		return base64_encode(sha1($password . $salt, true) . $salt);
	}

	public function gitPull($email,$projectName,$emailTrigger,$branch_name,$obj) {
		$repo_name = $obj['repository']['name'];
		$repo_branch = explode('/', $obj['ref']);
		$repo_branch = $repo_branch[2];

		$author_name = $obj['commits'][0]['author']['name'];
		$author_email = $obj['commits'][0]['author']['email'];

		$commit_message = $obj['commits'][0]['message'];

		if($branch_name == $repo_branch) {
			try {
				$pull = shell_exec('git pull');
			} catch(Exception $e) {
				// The shell_exec failed.
				$this->writeDebug('shell_exec failed',$e);
			}
			
			$this->writeDebug('Pull Results',$pull);

			if(strstr($commit_message,$emailTrigger)) {
				$subject = $projectName." - $author_name has made a change";

				$m = count($obj['commtis'][0]['modified']);
				$a = count($obj['commits'][0]['added']);
				$r = count($obj['commits'][0]['removed']);

				$message .= "<div style='width:100%; background-color:#fff; color:#000;'>";
				$message .= "<h1 style='display:block; background-color:#000; color:#fff; padding:25px;'>$projectName</h1>";
				
				$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
				$message .= "<p><strong>Greetings,</strong> <br /> A change was recently made to $branch_name by $author_name.</p>";
				$message .= "<p>$author_name has requested that you view the changes that they made to $branch_name in the $repo_name repository.".
							"If they made any errors, or you have a comment about the changes that they made, their contact".
							"information can be found below. </p>";
				$message .= "<h4>Happy Coding!</h4>";
				$message .= "</div>";

				$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
				$message .= "<p>$author_name <br /> $author_email <br /> $commit_message</p>";
				$message .= "</div>";

				if($m > 0) {
					$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
					$message .= "<h2>Modified</h2>";
					for($i=0;$i<$m;$i++) { $message .= $obj['commits'][0]['modified'][$i]."<br />"; }
					$message .= "</div>";
				}
				if($a > 0) {
					$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
					$message .= "<h2>Added</h2>";
					for($i=0;$i<$a;$i++) { $message .= $obj['commits'][0]['added'][$i]."<br />"; }
					$message .= "</div>";
				}
				if($r > 0) {
					$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
					$message .= "<h2>Removed</h2>";
					for($i=0;$i<$r;$i++) { $message .= $obj['commits'][0]['removed'][$i]."<br />"; }
					$message .= "</div>";
				}

				$message .= "<div style='background-color:rgba(190,200,230,.5); width:90%; margin:25px auto; padding:2%;'>";
				$message .= "<h2>Payload</h2>";
				$message .= "<p>$_POST[payload]</p>";
				$message .= "</div>";
				
				$message .= "<div style='display:block; text-align:center; width:100%;'><a href='https://github.com/jassok/Remote-Server-Autopull'>Remote Server Autopull</a></div>";
				$message .= "</div>";

				$email = $this->emailNotificaton($email,$author_email,$subject,$message);
				$this->writeDebug('Email Results',$email);
			}
			return $pull;
		} else {
			return array('error_code'=>1,'error_message'=>'No branch');
		}

	}

	public function curlFunction($secondaryBranch,$secondaryURL,$projectName,$emailTrigger,$obj,$email) {
		for($i = 0; $i < count($secondaryBranch); $i++ ) {

			$repo_branch = explode('/', $obj['ref']);
			$repo_branch = $repo_branch[2];
			
			if($secondaryBranch[$i] == $repo_branch) {
				if(!function_exists('curl_init')) {
					$this->writeDebug("curl error", "Function isnot installed");
				}

				$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $secondaryURL[$i]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);

				$data = array(
					'project' => $projectName,
					'emailTrigger' => $emailTrigger,
					'page' => basename($_SERVER['PHP_SELF']),
					'payload' => $_POST['payload'],
					'branch' => $secondaryBranch[$i],
					'team' => $email
				);

				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

				$output = curl_exec($ch);
				$info = curl_getinfo($ch);

				curl_close($ch);

				return $output;
			}
		}
	}

	private function emailNotificaton($to, $from, $subject, $message) {
		$headers = "From: $from \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		$mail = mail($to,$subject,$message,$headers);

		if(!$mail) {
			return $mail;
		}

		return "Email Successful";
	}

	public function writeDebug($title, $message) {
		$file = "debug_log.txt";

		$fh = fopen($file,'a') or die("Cannot open file");

		$string = "\n";

		$string .= date( 'm/j/Y :: g:i:s a' )." :: ".strtoupper($title)."\n";
		$string .= "============================================\n";
		$string .= "$message \n";
		$string .= "++++++++++++++++++++++++++++++++++++++++++++\n";

		fwrite($fh, $string);

		fclose($fh);

	}
}

/*
Licenced under the GNU licence: http://opensource.org/licenses/gpl-2.0.php

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
		a 			{ color:#fff; text-decoration:none; float:right; margin-top:10px; }
		a:hover 		{ text-decoration: none; color:#0080C0; }
		h1 			{ display:block; padding:20px; background-color:#0080C0; color:#fff; }
		input			{ display:block; padding:20px 10px; margin:10px 20px; width:540px; }
		textarea 		{ display:block; }
		label 			{ display:block; margin:10px 22px; }
		p 			{ margin:10 22px; }
		.container 		{ width:600px; margin:5% auto; background-color:#fff; padding:0 0 10px 0;}
		.warning 		{ background-color:#800000;}

	</style>
</head>
<body>

<div class="container">
	<?php echo $html; ?>

	<a href="https://github.com/jassok/Remote-Server-Autopull" target="_blank">Remote Server Autopull</a>

</div>
</body>
</html>
