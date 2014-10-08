<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Initialize and Test Active User's Behavior
if(Me::initialize())
{
	Me::runBehavior($url);
}

// Add Microfaction Scripts
Metadata::addHeader('<link rel="stylesheet" href="' . CDN . '/css/microfaction.css" />');
Metadata::addFooter('<script src="' . CDN . '/scripts/microfaction.js"></script>');

// Determine which page you should point to, then load it
require(SYS_PATH . "/routes.php");

/****** Dynamic URLs ******/
// If a page hasn't loaded yet, check if there is a dynamic load
if($url[0] != '')
{
	require(APP_PATH . '/controller/home.php'); exit;
}
//*/

/****** 404 Page ******/
// If the routes.php file or dynamic URLs didn't load a page (and thus exit the scripts), run a 404 page.
require(SYS_PATH . "/controller/404.php");