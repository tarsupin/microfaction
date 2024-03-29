<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if the user has changed their subscriptions
if(isset($_GET['tag']) && Link::clicked("microfaction-tag") && Me::$loggedIn)
{
	// Check if we're trying to remove this subscription
	if(isset($_GET['remove']))
	{
		AppHashtags::hashtagSubscribe(Me::$id, $_GET['tag'], false);
	}
	else
	{
		AppHashtags::hashtagVisible(Me::$id, $_GET['tag'], isset($_GET['vis']));
	}
}

// Prepare Values
$hashtagList = AppHashtags::getFullList();
$activeHashtag = $url[0];

$threadList = array();
$voteList = array();

AppHashtags::getSubscriptions(Me::$id, $hashtagList);

if(Me::$loggedIn)
{
	// Update the Priority List a few times per hour
	if(!$lastUpdate = (int) Cache::get("update_" . SITE_HANDLE))
	{
		AppThreads::runPriority($hashtagList);
		
		Cache::set("update_" . SITE_HANDLE, time(), 60 * 5);
	}
	
	$priorityThreads = AppThreads::listPriority(1, 50, $activeHashtag);
	
	// Check if the user has Voted
	if(isset($_GET['voteUp']))
	{
		AppThreads::vote(Me::$id, (int) $_GET['voteUp'], 1);
	}
	else if(isset($_GET['voteDown']))
	{
		AppThreads::vote(Me::$id, (int) $_GET['voteDown'], -1);
	}
	
	// Get Vote List
	foreach($priorityThreads as $threads)
	{
		$threadList[] = $threads['id'];
	}
	
	$voteList = AppThreads::getVoteList(Me::$id, $threadList);
}
else
{
	$priorityThreads = AppThreads::listPriority(1, 50, $activeHashtag);
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

echo AppHashtags::drawSubscriptionList();

echo '
<div class="overwrap-box">
	<div class="inner-box">';

if($activeHashtag)
{
	echo '
	<div class="overwrap-line">' . $activeHashtag . '</div>';
}

if(!$priorityThreads)
{
	echo '<div style="padding:6px;">No posts can be found here at this time.</div>';
}

foreach($priorityThreads as $threads)
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
			<div class="inner-desc"><a href="/thread?id=' . $threadID . '">' . $threads['comments'] . ' comments</a> ' . ($activeHashtag == "" ? '&bull; <a href="/' . $threads['hashtag'] . '">/' . $threads['hashtag'] . '</a>' : '') . ' &bull; By <a href="/' . $threads['handle'] . '">' . $threads['display_name'] . '</a> &bull; ' . Time::fuzzy((int) $threads['date_created']) . '</div>
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
