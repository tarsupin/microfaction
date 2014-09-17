<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Prepare Values
$threadList = array();
$voteList = array();

AppThreads::getSubscriptions(Me::$id, $microfaction);

$newThreads = AppThreads::listNew(1, 50, AppThreads::$activeHashtag);

if(Me::$loggedIn)
{
	// Check if the user has Voted
	if(isset($_POST['voteUp']))
	{
		AppThreads::vote(Me::$id, $_POST['voteUp'], 1);
	}
	else if(isset($_POST['voteDown']))
	{
		AppThreads::vote(Me::$id, $_POST['voteDown'], -1);
	}
	
	// Get Vote List
	foreach($newThreads as $threads)
	{
		$threadList[] = $threads['id'];
	}
	
	$voteList = AppThreads::getVoteList(Me::$id, $threadList);
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
			<a id="upVote-' . $threadID . '" href="/new?voteUp=' . $threadID . '" class="' . $vUp . '" onclick="runVote(' . $threadID . ', 1); return false;"><span class="icon-thumbs-up"></span></a><br />
			<a id="downVote-' . $threadID . '" href="/new?voteDown=' . $threadID . '" class="' . $vDown . '" onclick="runVote(' . $threadID . ', -1); return false;"><span class="icon-thumbs-down"></span></a>
		</div>
		<div class="inner-name">
			<a href="/thread?id=' . $threadID . '">' . $threads['title'] . '</a>
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
