<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if the user has Voted
if(!isset($_POST['vote']) or !isset($_POST['threadID']) or !isset($_POST['commentID']) or !Me::$loggedIn)
{
	exit;
}

if($_POST['vote'] == 1)
{
	AppComment::vote(Me::$id, $_POST['threadID'], $_POST['commentID'], 1);
}
else if($_POST['vote'] == -1)
{
	AppComment::vote(Me::$id, $_POST['threadID'], $_POST['commentID'], -1);
}