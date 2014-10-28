<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if the user has changed their subscriptions
if(isset($_GET['tag']) && Link::clicked("microfaction-tag") && Me::$loggedIn)
{
	// Check if we're trying to remove this subscription
	if(isset($_GET['remove']))
	{
		AppHashtags::hashtagSubscribe(Me::$id, $_GET['tag'], false);
		Alert::success('Unsubscribed Successfully', 'You have successfully unsubscribed from #' . $_GET['tag']);
	}
	else // check if we're trying to add this subscription
	if(isset($_GET['add']))
	{
		AppHashtags::hashtagSubscribe(Me::$id, $_GET['tag'], true);
		Alert::success('Subscribed Successfully', 'You have successfully subscribed to #' . $_GET['tag']);
	}
	else
	{
		AppHashtags::hashtagVisible(Me::$id, $_GET['tag'], isset($_GET['vis']));
	}
}

// Prepare Values
$hashtagList = AppHashtags::getFullList();

// Hashtag names in the DB have their first letter capitalized.
// We have to make sure the site can return the correct
// hashtag regardless of case, i.e. /dRaWiNg checks database for Drawing
$activeHashtag = (isset($url[1]) ? ucwords(strtolower($url[1])) : "");

$threadList = array();
$voteList = array();

AppHashtags::getSubscriptions(Me::$id, $hashtagList);

$newThreads = AppThreads::listNew(1, 50, $activeHashtag);

if(Me::$loggedIn)
{
	// Check if the user has Voted
	if(isset($_POST['voteUp']))
	{
		AppThreads::vote(Me::$id, (int) $_POST['voteUp'], 1);
	}
	else if(isset($_POST['voteDown']))
	{
		AppThreads::vote(Me::$id, (int) $_POST['voteDown'], -1);
	}
	
	// Get Vote List
	foreach($newThreads as $threads)
	{
		$threadList[] = $threads['id'];
	}
	
	$voteList = AppThreads::getVoteList(Me::$id, $threadList);

	if($activeHashtag)
	{
		// Check if hashtag is legitimate
		if(in_array($activeHashtag, $hashtagList))
		{
			// Check if user is subscribed & add quick subscribe button if not
			// Button is added to the top of the Nav panel via WidgetLoader plugin
			// The subscription will be handled via AJAX
			if(in_array($activeHashtag, AppHashtags::$userSubs))
			{
				WidgetLoader::add('SidePanel', 1, 
					'<div class="panel-box" style="min-height: 30px;">
						<div style="padding:10px; text-align: center;">
							<a class="button" <a href="/' . $activeHashtag . '?tag=' . $activeHashtag . '&' . Link::prepare("microfaction-tag") . '&remove=1">Unsubscribe from #' . $activeHashtag . '</a>
						</div>
					</div>'
				);
			} else {
				WidgetLoader::add('SidePanel', 1, 
					'<div class="panel-box" style="min-height: 30px;">
						<div style="padding:10px; text-align: center;">
							<a class="button" <a href="/' . $activeHashtag . '?tag=' . $activeHashtag . '&' . Link::prepare("microfaction-tag") . '&add=1">Subscribe to #' . $activeHashtag . '</a>
						</div>
					</div>'
				);
			}

			// Check user privileges on this hashtag.
			// If user has clearance, a quick edit button
			// is added to the bottom of the Nav panel.
			$hashtagClearance = AppHashtags::checkPrivileges(Me::$id, $activeHashtag);
			if($hashtagClearance >= 6 || $hashtagClearance == true)
			{
				WidgetLoader::add('SidePanel', 50, 
					'<div class="panel-box" style="min-height: 30px;">
						<div style="padding:10px; text-align: center;">
							<a class="button" href="/edit-rules?hashtag=' . $activeHashtag . '">Edit Rules for ' . ucfirst($activeHashtag) . '</a>
						</div>
					</div>'
				);
			}
		}
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
<div id="content">' . Alert::display();

echo AppHashtags::drawSubscriptionList("new");

echo '
	<div id="ajax-subscribe-message"></div>
';

echo '
<div class="overwrap-box">
	<div class="inner-box">';
	
if($activeHashtag)
{
	echo '
	<div class="overwrap-line">' . $activeHashtag . '</div>';
}

if(!$newThreads)
{
	echo '<div style="padding:6px;">No posts can be found here at this time.</div>';
}

foreach($newThreads as $threads)
{
	$threadID = $threads['id'];
	$vUp = "voteUp";
	$vDown = "voteDown";
	
	if(isset($voteList[$threadID]))
	{
		if($voteList[$threadID] == 1)
		{
			$vUp .= " vStayUp";
		}
		else
		{
			$vDown .= " vStayDown";
		}
	}
	
	echo '
	<div class="inner-line">
		<div class="inner-score">
			<a id="upVote-' . $threadID . '" href="/?voteUp=' . $threadID . '" class="' . $vUp . '" onclick="runVote(' . $threadID . ', 1); return false;"><span class="icon-arrow-up"></span></a><br />
			<a id="downVote-' . $threadID . '" href="/?voteDown=' . $threadID . '" class="' . $vDown . '" onclick="runVote(' . $threadID . ', -1); return false;"><span class="icon-arrow-down"></span></a>
		</div>
		<div class="inner-name">
			<a href="' . $threads['url'] . '">' . $threads['title'] . '</a>
			<div class="inner-desc"><a href="/thread?id=' . $threadID . '">' . $threads['comments'] . ' comments</a> ' . ($activeHashtag == "" ? '&bull; <a href="/' . $threads['hashtag'] . '">/' . $threads['hashtag'] . '</a>' : '') . ' &bull; By <a href="/' . $threads['handle'] . '">' . $threads['display_name'] . '</a> &bull; ' . Time::fuzzy((int) $threads['date_created']) . ' ';
			if($activeHashtag)
			{
				if($hashtagClearance >= 6 || $hashtagClearance == true)
				{
					echo '<a class="red" href="/admin/delete-thread?id=' . $threadID . '&tag=' . $activeHashtag . '">Delete</a>'; 
				}
			}
			
	echo '
			</div>
		</div>
	</div>';
}

echo '
	</div>
</div>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
