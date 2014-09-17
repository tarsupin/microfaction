<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/admin/delete-thread
	
	This page allows you to delete threads so they're no longer visible.
*/

// Run Permissions
require(SYS_PATH . "/controller/includes/admin_perm.php");

// Must be a moderator to access this page
if(Me::$clearance < 6)
{
	header("Location: /admin"); exit;
}

// Make sure that we have an accurate thread
if(!isset($_POST['id']))
{
	header("Location: /admin"); exit;
}

// Get Thread Data
if(!$threadData = AppThreads::threadData($_POST['id'], 'id, uni_id, title, url, vote_up, vote_down, date_created'))
{
	header("Location: /admin"); exit;
}

if(!$authorData = User::get($threadData['uni_id'], "handle, display_name"))
{
	header("Location: /admin"); exit;
}

// Submit Form
if(Link::clicked("sub-mod-delete-thread"))
{
	// Delete the Thread
	AppThreads::delete($threadData['id']);
}

// Run Header
require(SYS_PATH . "/controller/includes/admin_header.php");

echo '
<h2>Are you sure you want to delete this thread?</h2>
<p>
	Thread Details:
	<br /> &nbsp; &bull; Title: ' . $threadData['title'] . '
	<br /> &nbsp; &bull; Votes: +' . $threadData['vote_up'] . ' -' . $threadData['vote_down'] . '
	<br /> &nbsp; &bull; Author: ' . $authorData['display_name'] . ' (@' . $authorData['handle'] . ')
	<br /> &nbsp; &bull; Posted: ' . Time::fuzzy($threadData['date_created']) . '
</p>
<p><a class="button" href="/admin/delete-thread?id=' . ($threadData['id']) . '&' . Link::prepare("sub-mod-delete-thread") . '">Delete the Thread</a></p>';

// Display the Footer
require(SYS_PATH . "/controller/includes/admin_footer.php");
