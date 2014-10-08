<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Make sure the user is logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/subscriptions", "/");
}

// Check if the user has changed their subscriptions
if(isset($_GET['tag']) && Link::clicked("edit-tag-microfaction"))
{
	AppHashtags::hashtagSubscribe(Me::$id, $_GET['tag'], isset($_GET['sub']));
}


// Get Full List of available Subscriptions
$active = array();
$inactive = array();

// Prepare Values
$hashtags = AppHashtags::getByPriority(6);
AppHashtags::getSubscriptions(Me::$id, $hashtags);

foreach($hashtags as $hashtag)
{
	if(in_array($hashtag, AppHashtags::$userSubs))
	{
		$active[$hashtag] = $hashtag;
	}
	else
	{
		$inactive[$hashtag] = $hashtag;
	}
}

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content" style="overflow:hidden;">' . Alert::display();

echo '
<div style="float:left; width:43%; overflow:hidden;">
<div class="overwrap-box">
	<div class="overwrap-line">Active Hashtags (click to remove)</div>
	<div class="inner-box">';

foreach($active as $hashtag => $bool)
{
	echo '
	<div class="tag-line">
		<a href="/subscriptions?tag=' . $hashtag . '&' . Link::prepare("edit-tag-microfaction") . '">#' . $hashtag . '</a>
	</div>';
}
	
echo '
	</div>
</div>
</div>

<div style="float:left; margin-left:2%; width:55%; overflow:hidden;">
<div class="overwrap-box">
	<div class="overwrap-line">Featured Hashtags (click to subscribe)</div>
	<div class="inner-box">';
	
	foreach($inactive as $hashtag => $bool)
	{
		echo '
		<div class="tag-line">
			<a href="/subscriptions?tag=' . $hashtag . '&sub=1&' . Link::prepare("edit-tag-microfaction") . '">#' . $hashtag . '</a>
		</div>';
	}
	
echo '
	</div>
</div>
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
