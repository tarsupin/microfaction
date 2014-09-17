<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	****** ABOUT THIS API ******
	This API is used to gather the top threads from this site. It returns data for the the top 100 threads, ordered
	by the priority list.
	
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
	// Get the list of threads
	$threads = Database::selectMultiple("SELECT t.uni_id, t.category_id, t.title, t.url, t.rating, t.vote_up, t.vote_down, t.comments, t.date_created, u.handle, u.display_name FROM threads_priority tp INNER JOIN threads t ON t.id=tp.thread_id INNER JOIN users u ON u.uni_id=t.uni_id ORDER BY tp.rating DESC LIMIT 100", array());
	
	// Recognize Integer
	$threads['uni_id'] = (int) $threads['uni_id'];
	$threads['category_id'] = (int) $threads['category_id'];
	$threads['rating'] = (int) $threads['rating'];
	$threads['vote_up'] = (int) $threads['vote_up'];
	$threads['vote_down'] = (int) $threads['vote_down'];
	$threads['comments'] = (int) $threads['comments'];
	$threads['date_created'] = (int) $threads['date_created'];
	
	echo json_encode($threads);
}