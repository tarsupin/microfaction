<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the attempt is valid
if(!isset($_POST['vote']) or !isset($_POST['threadID']) or !Me::$loggedIn)
{
	exit;
}

if($_POST['vote'] == 1)
{
	AppThreads::vote(Me::$id, (int) $_POST['threadID'], 1);
}
else if($_POST['vote'] == -1)
{
	AppThreads::vote(Me::$id, (int) $_POST['threadID'], -1);
}