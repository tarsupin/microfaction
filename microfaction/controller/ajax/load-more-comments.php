<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the proper values are sent
if(!isset($_POST['id']) or !isset($_POST['step'])) { exit; }

$_POST['id'] = max(1, $_POST['id'] + 0);
$_POST['step'] = $_POST['step'] + 0;

if($check = AppComment::displayRecursive($_POST['id'], $_POST['step'], 3))
{
	echo '<div style="margin-left:30px; margin-top:10px;"><a href="javascript:showMore(' . $_POST['id'] . ', ' . ($_POST['step'] - 3) . ')">Show More...</a></div>';
}