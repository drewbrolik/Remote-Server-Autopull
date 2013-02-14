<?php
/*************************************
* - WARNING
*************************************/
//-- This file is used in conjunction with tractum.php
//-- No settings in this file must be edited for it to work.
//-- Simply upload this file to the server it needs to run on
//-- and tractum.php will handle the rest
//-- https://github.com/jassok/Remote-Server-Autopull

if($_POST['page']) {
	// Gather setings
	$emailTrigger = $_POST['emailTrigger'];
	$projectName = $_POST['project'];
	$email = $_POST['team'];

	//Get the payload as jSON and parse it out
	$json = $_POST['payload'];
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

	if($branch_name == $_POST['branch']) {
		// We are dealing with the right branch
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
	}
} else {
	echo "Hidden post missing";
}

?>