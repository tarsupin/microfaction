<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check if the user has changed their subscriptions
if(isset($_GET['tag']) && Link::clicked("microfaction-tag") && Me::$loggedIn)
{
	// Check if we're trying to remove this subscription
	if(isset($_GET['remove']))
	{
		AppThreads::hashtagSubscribe(Me::$id, $_GET['tag'], false);
	}
	else
	{
		AppThreads::hashtagVisible(Me::$id, $_GET['tag'], isset($_GET['vis']));
	}
}

// Prepare Values
$threadList = array();
$voteList = array();

AppThreads::getSubscriptions(Me::$id, $microfaction);

if(Me::$loggedIn)
{
	// Update the Priority List a few times per hour
	if(!$lastUpdate = Cache::get("date_priorityList"))
	{
		AppThreads::runPriority($microfaction);
		
		Cache::set("date_priorityList", "1", 60 * 8);
	}
	
	$priorityThreads = AppThreads::listPriority(1, 50, AppThreads::$activeHashtag);
	
	// Check if the user has Voted
	if(isset($_GET['voteUp']))
	{
		AppThreads::vote(Me::$id, $_GET['voteUp'], 1);
	}
	else if(isset($_GET['voteDown']))
	{
		AppThreads::vote(Me::$id, $_GET['voteDown'], -1);
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
	$priorityThreads = AppThreads::listPriority(1, 50, AppThreads::$activeHashtag);
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

echo AppThreads::drawSubscriptionList($microfaction);

echo '
<div class="overwrap-box">
	<div class="overwrap-line">' . $config['site-name'] . '</div>
	<div class="inner-box">';
	
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
			<a id="upVote-' . $threadID . '" href="/?voteUp=' . $threadID . '" class="' . $vUp . '" onclick="runVote(' . $threadID . ', 1); return false;"><span class="icon-thumbs-up"></span></a><br />
			<a id="downVote-' . $threadID . '" href="/?voteDown=' . $threadID . '" class="' . $vDown . '" onclick="runVote(' . $threadID . ', -1); return false;"><span class="icon-thumbs-down"></span></a>
		</div>
		<div class="inner-name">
			<a href="' . $threads['url'] . '">' . $threads['title'] . '</a>
			<div class="inner-desc"><a href="/thread?id=' . $threadID . '">' . $threads['comments'] . ' comments</a> &bull; By <a href="/' . $threads['handle'] . '">' . $threads['display_name'] . '</a> &bull; ' . Time::fuzzy($threads['date_created']) . '</div>
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
