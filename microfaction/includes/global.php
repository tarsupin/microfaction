<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Main Navigation
$urlActive = (isset($url[0]) && $url[0] != "" ? $url[0] : "home");

$html = '
<div class="panel-box">
	<ul class="panel-slots">
		<li class="nav-slot' . ($urlActive == "new" ? " nav-active" : "") . '"><a href="/new">Recent Posts<span class="icon-circle-right nav-arrow"></span></a></li>
		<li class="nav-slot' . ($urlActive == "post" ? " nav-active" : "") . '"><a href="/post">Create Post<span class="icon-circle-right nav-arrow"></span></a></li>
	</ul>
</div>';

WidgetLoader::add("SidePanel", 5, $html);

// Prepare Values
$uniURL = str_replace("http://", "", URL::microfaction_com());
$siteType = strtolower(str_replace("micro_", "", SITE_HANDLE));

$html = str_replace("is-" . $siteType, "nav-active", '
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
</div>');

WidgetLoader::add("SidePanel", 10, $html);
