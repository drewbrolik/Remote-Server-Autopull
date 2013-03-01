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
	$tractum = new tractum();

	$projectName = $_POST['project'];
	$branch = $_POST['branch'];

	$json = str_replace('\\', '', $_POST['payload']);
	$obj = json_decode($json,true);

	$email = $_POST['team'];
	$emailTrigger = $_POST['emailTrigger'];

	$pull = $tractum->gitPull($email,$projectName,$emailTrigger,$branch,$obj);

	echo $pull;

	if($pull) {
		$tractum->writeDebug("Pull Results", $pull);
	}
}

/* == Class Constructor ============================================= */
class tractum {

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

				$this->emailNotificaton($email,$author_email,$subject,$message);
			}
			return $pull;
		} else {
			return array('error_code'=>1,'error_message'=>'No branch');
		}

	}

	private function emailNotificaton($to, $from, $subject, $message) {
		$headers = "From: $from \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		$mail = mail($to,$subject,$message,$headers);

		if(!$mail) {
			return false;
		}

		return true;
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

?>