<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// If the user is viewing a specific category
if(isset($url[0]) && !in_array($url[0], array("post", "thread")))
{
	if($url[0] == "new")
	{
		if(isset($url[1]))
		{
			if($fetchID = array_search(urldecode($url[1]), $microfaction['hashtags']))
			{
				AppThreads::$activeHashtag = $fetchID + 0;
			}
		}
	}
	else if($fetchID = array_search(urldecode($url[0]), $microfaction['hashtags']))
	{
		AppThreads::$activeHashtag = $fetchID + 0;
	}
}

// Update User Activity
// Analytics::userActive(Me::$id);

// Prepare Notifications (if available)
if(Me::$loggedIn)
{
	WidgetLoader::add("SidePanel", 1, Notifications::sideWidget());
}

// Display Menu Options
/*
$menus = Site::menus();

foreach($menus as $menu)
{
	$html .= '
	<a class="panel-link' . (isset($menu['active']) ? " panel-active" : "") . '" href="' . $menu['url'] . '"><span class="icon-' . $menu['icon'] . ' panel-icon"></span><span class="panel-title">' . $menu['title'] . '</span></a>';
}

// If you are a staff member
if(Me::$clearance >= 5)
{
	$html .= '
	<a class="panel-link' . ($urlActive == "admin" ? " panel-active" : "") . '" href="/admin"><span class="icon-image panel-icon"></span><span class="panel-title">Staff Panel</span></a>';
}

$html .= '
</div>';

WidgetLoader::add("SidePanel", 10, $html);
*/

// Navigation
/*
$html = '
<div class="panel-box">
	<a href="#" class="panel-head">Related Sites<span class="icon-circle-right nav-arrow"></span></a>
	<ul class="panel-slots">';

foreach($microfaction['related'] as $title => $subdomain)
{
	$html .= '
	<li class="nav-slot"><a href="http://' . $subdomain . '.' . $_SERVER['siteCoreDomain'] . '">' . $title . '<span class="icon-circle-right nav-arrow"></span></a></li>';
}

$html .= '
	</ul>
</div>';
*/

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

$html = '
<div class="panel-box">
	<ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "home" ? " nav-active" : "") . '"><a href="/">Home<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "new" ? " nav-active" : "") . '"><a href="/new">Recent Posts<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "post" ? " nav-active" : "") . '"><a href="/post">Create Post<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul>
</div>';

WidgetLoader::add("SidePanel", 30, $html);
