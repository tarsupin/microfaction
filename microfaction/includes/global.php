<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
if(isset($url[0]))
{
	switch($url[0])
	{
		case "":
			$urlActive = "";
			break;
		case "new":
			$urlActive = (isset($url[1]) ? $url[1] : "");
			break;
		default:
			$urlActive = $url[0];
			break;
	}
}

// Prepare Values
$uniURL = str_replace("http://", "", URL::microfaction_com()) . Me::$slg;
$siteType = strtolower(str_replace("micro_", "", SITE_HANDLE));

WidgetLoader::add("SidePanel", 8, str_replace("is-" . $siteType, "nav-active", '<div class="panel-box">
	<ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "new" ? " nav-active" : "") . '"><a href="/new' . ($urlActive !== "" ? "/" . $urlActive : "") . '">Recent Posts<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "post" ? " nav-active" : "") . '"><a href="/post' . ($urlActive !== "" ? "?hashtag=" . $urlActive : "") . '">Create Post<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul>
</div>'));

WidgetLoader::add("SidePanel", 10, str_replace("is-" . $siteType, "nav-active", '
<div class="panel-box">
	<ul class="panel-slots">
		<li class="nav-slot is-bliss"><a href="http://bliss.' . $uniURL . '">Bliss<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-business"><a href="http://business.' . $uniURL . '">Business<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-community"><a href="http://community.' . $uniURL . '">Community<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-create"><a href="http://create.' . $uniURL . '">Create<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-entertainment"><a href="http://entertainment.' . $uniURL . '">Entertainment<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-life"><a href="http://life.' . $uniURL . '">Life &amp; Home<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-news"><a href="http://news.' . $uniURL . '">News<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-sciences"><a href="http://sciences.' . $uniURL . '">Sciences<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot is-sports"><a href="http://sports.' . $uniURL . '">Sports &amp; Rec<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul>
</div>'));
