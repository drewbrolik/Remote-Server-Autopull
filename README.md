Remote Server Auto-pull
======================

A script that can be uploaded to a remote server and combined with GitHub's [webhooks](https://help.github.com/articles/post-receive-hooks) to keep your server
up-to-date with the most current version of your repo. Takes away the need to ftp in to a server and builds that 
function into your current work-flow.

How It works
------------
Any time a push is made to your remote Repository, GitHub will send out json data to this file, which will read and
parse the data out. If everything checks out, the server will attempt to execute a single shell command (git pull).
As long as the pull is successful, the remote server will sync up to your repository and all changes will go live.

Instillation
------------
  * Add this file to your git ignore
  * Configure the file to your liking
  * Enable SSH on your remote server
  * SSH to your server and set up git and your repo (may require root access)
  * Clone your repo to your remote server
  * Upload this file to the server
  * Navigate to this file on the sever followed by "?passgen=PASSWORD" where PASSWORD is your chosen password
  * Add the salt and pass to the file
  * Add the link generated to your webhooks

Setup
-----
Set the default settings

First, lets set the project name. This will appear in the email heading.
````PHP
$projectName = ""; 
````

Next, set the branch name. This restricts the server from pulling down changes ONLY when this branch is edited.
That way, if you have a Development branch, the server will remain idle when changes are made there if it should
only be updating when a master branch is changed
````PHP
$branch = "";
````

Set this to something in order to restrict when emails are sent out. For example, mine is set to (email) that way
if my commit message includes (email) in it, then an email will go out to my team alerting them of the changes.
````PHP
$emailTrigger = " "; 
````

Add the email addresses of your team
````PHP
// Members of the project to alert that the site was updated.
$team[] = 'email1@mail.com';
$team[] = 'email2@mail.com';
````

Set the salt and pass once generated
````PHP
//Copy and paste results from passgen
$salt = '';
$pass = '';
````

Example Email
-------------
When a mail is sent out, it will look something like this:
````
A change was recently made to <repository> by <author>.
<author> requests that you check out the change on the remote server. If they made a mistake, or 
you have something to say to them. Their contact info is listed below.

Happy Coding!

Branch: master
(email) Nothing to actually look at. I just want to check the payload
<author>
<authors email>

Files Added:
html/js/jquery.min.js

Files Edited:
html/js/jquery.cSlider.js

Files Removed:
html/js/jquery.js


Payload:
{"hook_callpath":"new","pusher": [....]
````

Multiple Servers
================
If you have a development environment that utilizes multiple servers, such as a testing server
a demo server, and a master server you can make use of the capere.php file. capere.php works in
much the same way that tractum.php does only instead of receiving a request from GitHub, it receives
a cURL request from tractum.php


Setup
-----

For every additional server you set up, add them to tractum.php
The file will loop through each of these secondary branches and send a cURL request to each of them
with the payload sent by GitHub.

````PHP
//Secondary Servers
$secondaryBranch[] = "";
$secondaryURL[] = "http://yourdomain.com/"."capere.php"; //Set this to the location of capere.php
````

SSH into that server and clone your GitHub repo down using the specific branch

````BASH
git clone -b development [git@github.com:]
````


Based off WolfieZeros's [github-auto-pull](https://github.com/WolfieZero/github-auto-pull/blob/master/github.php)
