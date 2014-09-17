<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	****** ABOUT THIS API ******
	This API is used to gather a user's subscriptions for this microfaction site.
	
	// Requirements for Posting
	$apiData = array(
		'is_valid'				// Just a simple value set to TRUE to confirm the API is valid
	);
	
	// Call This API
	$packet = array("is_valid" => true);
	$siteData = Network::get("microfaction_EDIT");
	$response = Connect::call($siteData['site_url'] . "/api/site-data", $packet, $siteData['site_key']);
	var_dump($response);
*/

// If the proper information wasn't sent, exit the page
if(!isset($_GET['api']) or !$key = Network::key($_GET['site'])) { exit; }

// Interpret the data sent
$apiData = API::interpret($_GET['api'], $key, $_GET['salt'], $_GET['conf']);

// Process the API & Call Data
if(is_array($apiData) && isset($apiData['is_valid']))
{
	echo json_encode($microfaction);
}