<?php

/*************************************
* - Setup
*************************************/
//-- Set the project Name & Email to receive notification about failed pulls
//-- Clone the repo, or upload this file to the server
//-- Navigate to this file on the sever followed by "?passgen=PASSWORD"
//-- Add the Password and Salt values to this file
//-- Add the link the file gives you from the second field to the github web hooks.


//Defaults
$projectName = "";
$branch = "";
$emailTrigger = " ";

$secondaryBranch = array();
$secondaryURL = array();

//Secondary Servers
$secondaryBranch[] = "";
$secondaryURL[] = "http://yourdomain.com/"."capere.php"; //Set this to the location of capere.php

$team = array();

// Members of the project to alert that the site was updated.
$team[] = '';

//Default Headers
$headers = "From: "."\r\n"; // set this to noreply@yourdomain.com

$email	= implode(", ", $team);
remoteIP;

//Copy and paste results from passgen
$salt = '';
$pass = '';

if (isset($_GET['update'])) {

	//Update the folder with the lastest from the repo
	$check = md5(crypt($_GET['update'],$salt));

	if($pass == $check) {

		$json = str_replace('\\','', $_POST['payload']);
		$obj = json_decode($json,true);

		//Name of the Repo
		$repo_name = $obj['repository']['name'];

		//Branch of the Repo
		$repo_branch = explode("/", $obj['ref']);
		$branch_name = $repo_branch[2];

		//Who made the commit
		$author_name = $obj['commits'][0]['author']['name'];
		$author_email = $obj['commits'][0]['author']['email'];

		//The message they commited
		$commit_message = $obj['commits'][0]['message'];

		//Number of files
		$m = count($obj['commits'][0]['modified']);
		$a = count($obj['commits'][0]['added']);
		$r = count($obj['commits'][0]['removed']);

		//The branch this is for.
		if($branch_name == $branch) { //Limit the git pull to only a specific branch

			//Pull the files (Shell Command)
			try {
				shell_exec('git pull');
			} catch (Exception $e) {
				$msg = "This message is to alert you that a pull has failed on the ".$projectName." site.";
				mail($email, '[',$projectName.'] Git pull Failed',$msg." - GIT PULL did not execute.",$headers);
			}

			$message = "";
			$message .= "A change was recently made to ".$repo_name." by ".$author_name.".<br />";
			$message .= $author_name." requests that you check out the change on the remote server. If they made a mistake, or you have something to say to them. Their contact info is listed below. <br /><br />";
			$message .= "Happy Coding! <br /><br />";
			$message .= "Branch: ".$branch_name."<br />";
			$message .= $commit_message."<br/>";
			$message .= $author_name."<br />";
			$message .= $author_email;
			
			//List files that were added
			$message .= "<br /><br /><br /><strong>Files Added:</strong><br />";
			for($i=0;$i<$a;$i++) {
				$message .= $obj['commits'][0]['added'][$i]."<br />";
			}

			//List files that were Modified
			$message .= "<br /><br /><strong>Files Modified:</strong><br />";
			for($i=0;$i<$m;$i++) {
				$message .= $obj['commits'][0]['modified'][$i]."<br />";
			}

			//List files that were removed
			$message .= "<br /><br /><strong>Files Removed:</strong><br />";
			for($i=0;$i<$r;$i++) {
				$message .= $obj['commits'][0]['removed'][$i]."<br />";
			}

			$message .= "<br /><br />Payload:<br />".$json;

			//Limit email send
			if(strstr($commit_message, $emailTrigger)) {
				$headers = "From: ".$author_email."\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

				mail($email, '['.$projectName.'] '.$author_name.' made a change you should look at',$message,$headers);
			}

		} else {
			for($i = 0; $i < count($secondaryBranch); $i++ ) { 
				if ($secondaryBranch[$i] == $branch_name) {
					if(!function_exists('curl_init')) {
						die('cURL is not installed.');
					}

					$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $secondaryURL[$i]);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_HEADER, 0);

					$data = array(
						'project' => $projectName,
						'emailTrigger' => $emailTrigger,
						'page' => basename($_SERVER['PHP_SELF']), 
						'payload' => $json, 
						'branch' => $secondaryBranch[$i],
						'team' => $email);

					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

					$output = curl_exec($ch);
					$info = curl_getinfo($ch);

					curl_close($ch);

				}
			}	
		}
		
	} else {
		$msg = "This email is to alert you that a pull has failed on the ".$porjectName." site due to an incorrect password. :: ".$pass." :: ".$check;
		//Email fail (Wrong password)
		mail($email,'['.$projectName.'] GIT PULL failed [Incorrect Password]', $msg, $headers);
	}
} elseif (isset($_GET['passgen'])) {

	//Save lines
	$remoteIP	= $_SERVER['REMOTE_ADDR'];

	//Generate salt and password
	$password	= $_GET['passgen'];
	$randSalt	= (string)rand();
	$generate 	= crypt($password, $randSalt);
	$genPass	= md5($generate);

	$html = '<body style="width:70%; margin:20px auto; text-align:center;">';
	$html .= '<p><label>Add the following code to <code>'.$_SERVER['SCRIPT_FILENAME'].'</code><br /><textarea cols="50" rows="2" style="padding: 10px">';
	$html .= '$salt = \''.$randSalt.'\';'."\n";
	$html .= '$pass = \''.$genPass.'\';';
	$html .= '</textarea></label></p>';

	$callURL	= 'http';

	if(isset($_SERVER['HTTPS'])) $callURL .= 's';

	$callURL .= '://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'?update='.$_GET['passgen'];

	$html .= '<p><label>Add this URL your project\'s "Post-Recieve URL\'s"'.'<br />';
	$html .= '<input type="text" value="'.$callURL.'" style ="width:500px; text-align: center;" /></label></p>';

	echo $html;
} else {
	$msg = "Access to tractum.php was attempted without the proper credentials.";
	mail($email, '['.$projectName.'] GIT PULL failed',$msg,$headers);
}

?>
