<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Check the Thread
if(!$threadData = AppThreads::threadData((int) $_GET['id'], "*"))
{
	header("Location: /"); exit;
}

// Prepare Values
$threadID = (int) $threadData['id'];
$threadData['uni_id'] = (int) $threadData['uni_id'];

$userData = User::get($threadData['uni_id'], "handle, display_name");

if(Me::$loggedIn)
{
	// Update the Comment Priority Function a few times per hour
	// This updates the position of the comments based on their rating
	if(!$lastUpdate = Cache::get("date_comRating_" . $threadID))
	{
		AppComment::runPriority($threadID);
		
		Cache::set("date_comRating_" . $threadID, "1", 60 * 8);
	}
	
	// Run Form
	if(Form::submitted("comment-thread-" . $threadID))
	{
		FormValidate::text("Comment", $_POST['comment'], 1, 3000);
		
		if(FormValidate::pass())
		{
			if($commentID = AppComment::create($threadID, Me::$id, $threadID, $_POST['comment'], 1, "/thread?id=" . $threadID, $threadData['uni_id']))
			{
				Alert::success("Posted Comment", "You have posted a comment to this thread!");
			}
		}
	}
	
	// Reply To Comment
	else if(Form::submitted("reply-comment"))
	{
		FormValidate::text("Reply", $_POST['reply'], 1, 3000);
		
		if(FormValidate::pass())
		{
			if($commentID = AppComment::create($threadID, Me::$id, (int) $_POST['reply_to'], $_POST['reply'], 0, "/thread?id=" . $threadID . "&toCom=" . $_POST['reply_to'], $threadData['uni_id']))
			{
				Alert::success("Posted Comment", "You have replied to a comment!");
			}
		}
	}
	
	// Moderator Functions
	if(Me::$clearance >= 6)
	{
		if(isset($_POST['action']))
		{
			// Delete Comment
			if($_POST['action'] == "deleteComment" && isset($_POST['commentID']) && Link::clicked("sub-mod-comDel"))
			{
				if(isset($_POST['parentID']))
				{
					AppComment::deleteReplies($_POST['parentID'], $_POST['commentID']);
				}
				else
				{
					AppComment::deleteMain($threadID, $_POST['commentID']);
				}
			}
		}
	}
}

// Get Comment List
$comments = AppComment::getThreadList($threadID, 0, 50, "DESC");

// Run Global Script
require(APP_PATH . "/includes/global.php");

// Display the Header
require(SYS_PATH . "/controller/includes/metaheader.php");
require(SYS_PATH . "/controller/includes/header.php");

// Display Side Panel
require(SYS_PATH . "/controller/includes/side-panel.php");

echo '
<div id="panel-right"></div>
<div id="content" class="content-open">' . Alert::display();

echo '
<div>
	<h3><a href="' . $threadData['url'] . '">' . $threadData['title'] . '</a></h3>
	<p style="padding:0px;">
		<a href="' . URL::unifaction_social() . '/' . $userData['handle'] . '">' . $userData['display_name'] . '</a>
		&bull; <a href="/user-panel/reports/thread?thread=' . $threadData['id'] . '">Report</a>
		&bull; <a href="/">Tip</a>';
		
		// Moderator has the ability to delete the thread
		if(Me::$clearance >= 6)
		{
			echo '
			&bull; <a href="/admin/delete-thread?id=' . $threadData['id'] . '">Delete</a>';
		}
		
echo '
</div>';

echo '
<form action="/thread?id=' . $threadID . '" method="post">' . Form::prepare("comment-thread-" . $threadID) . '
	<textarea name="comment"></textarea>
	<input type="submit" name="submit" value="Submit" />
</form>';

AppComment::display($threadData, $comments);

echo '
<form id="comment-post" class="uniform" action="/thread?id=' . $threadID . '" method="post">' . Form::prepare('reply-comment') . '
	<input id="comment-post-num" type="hidden" name="reply_to" value="1" />
	<textarea name="reply"></textarea>
	<input type="submit" name="submit" value="Submit" />
</form>';

echo '
<script>';

// Prepare the Vote List (if logged in)
if(Me::$loggedIn)
{
	echo '
	var voteList = new Array();
	';
	
	// Get the user's Vote List for this thread
	$voteList = AppComment::getVoteList(Me::$id, $threadID);
	
	foreach($voteList as $key => $votes)
	{
		echo '
		voteList.push([' . $key . ', ' . $votes . ']);';
	}
	
	echo '
	function setUserVotes()
	{
		var len = voteList.length;
		
		for(var a = 0;a < len;a++)
		{
			var vID = voteList[a][0];
			var vote = voteList[a][1];
			
			// Use a try/catch to prevent any issues with unshown / missing comments
			try
			{
				if(vote == 1)
				{
					document.getElementById("upVote-" + vID).className = "voteUp vStayUp";
					document.getElementById("downVote-" + vID).className = "voteDown";
				}
				else if(vote == -1)
				{
					document.getElementById("upVote-" + vID).className = "voteUp";
					document.getElementById("downVote-" + vID).className = "voteDown vStayDown";
				}
			}
			catch(error) { }
		}
	}
	
	function voteCom(commentID, vote)
	{
		var voteLink = document.getElementById((vote == 1 ? "up" : "down") + "Vote-" + commentID);
		var voteLinkOther = document.getElementById((vote == 1 ? "down" : "up") + "Vote-" + commentID);
		
		// Handle Upvotes
		if(vote == 1)
		{
			loadAjax("", "vote-comment", "", "threadID=' . $threadID . '", "commentID=" + commentID, "vote=1");
			
			voteLinkOther.className = "voteDown";
			
			if(voteLink.className.match(/\bvStayUp\b/))
			{
				voteLink.className = "voteUp";
			}
			else
			{
				voteLink.className = "voteUp vStayUp";
			}
		}
		
		// Handle Downvotes
		else if(vote == -1)
		{
			loadAjax("", "vote-comment", "", "threadID=' . $threadID . '", "commentID=" + commentID, "vote=-1");
			
			voteLinkOther.className = "voteUp";
			
			if(voteLink.className.match(/\bvStayDown\b/))
			{
				voteLink.className = "voteDown";
			}
			else
			{
				voteLink.className = "voteDown vStayDown";
			}
		}
	}
	
	setUserVotes();';
}
else
{
	echo '
	function setUserVotes() {}
	function voteCom(a, b) {}
	';
}

echo '
</script>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
