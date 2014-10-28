<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// If the names of the current microfaction and hashtag aren't passed, exit
if(!isset($_POST['microfaction'], $_POST['hashtag'])) { exit; }

// Our APP_PATH will end in microfaction/microfaction. We need to be in
// the folder for our specific microfaction, so we go up one level by
// grabbing the dirname(APP_PATH). What we will end up with is:
// /microfaction/_____/includes/rules/_____.php

// Call the functions that return the rules for the corresponding microfaction/hashtag.
// There are two exceptions that don't use the full microfaction name so to display them
// properly we have to check for them and respond accordingly:
switch($_POST['microfaction']){
	case "life":
		echo '<h3>Rules for Life & Home:</h3>';
		break;
	case "sports":
		echo '<h3>Rules for Sports & Rec:</h3>';
		break;
	default:
		echo '<h3>Rules for ' . ucfirst($_POST['microfaction']) . ':</h3>';
}
if(file_exists(CONF_PATH . "/includes/rules/rules-" . $_POST['microfaction'] . ".php"))
{
	require(CONF_PATH . "/includes/rules/rules-" . $_POST['microfaction'] . ".php");
} else {
	echo 'There are currently no rules for this MicroFaction!';
}

echo '<h3>Rules for #' . $_POST['hashtag'] . ':</h3>';
if(file_exists(CONF_PATH . "/includes/rules/rules-" . $_POST['hashtag'] . ".php"))
{
	require(CONF_PATH . "/includes/rules/rules-" . $_POST['hashtag'] . ".php");
} else {
	echo 'There are currently no rules for this hashtag!';
}
