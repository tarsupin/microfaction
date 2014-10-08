<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Force Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/post", "/");
}

// Prepare Values
$hashtagList = AppHashtags::getFullList();
$_GET['hashtag'] = isset($_GET['hashtag']) ? Sanitize::variable($_GET['hashtag']) : "";

AppHashtags::getSubscriptions(Me::$id, $hashtagList);

// Run the Form
if(Form::submitted("post-microfaction-" . SITE_HANDLE))
{
	FormValidate::text("Title", $_POST['title'], 1, 72);
	FormValidate::url("URL", $_POST['url'], 1, 148);
	
	if(!$parsedURL = URL::parse($_POST['url']))
	{
		Alert::error("Malformed URL", "That URL appears to be malformed.", 1);
	}
	
	if(!in_array($_POST['hashtag'], $hashtagList))
	{
		Alert::error("Hashtag", "That tag cannot be accessed at this time.", 3);
	}
	
	if(FormValidate::pass())
	{
		// Scrape the page that you sent
		if($html = Download::get($parsedURL['full']))
		{
			// $photo = AppThreads::getPhoto($html);
			
			// Post the Thread
			if($threadID = AppThreads::createThread($_POST['hashtag'], $_POST['title'], $parsedURL['full']))
			{
				Alert::saveSuccess("Link Posted", "You have successfully posted a link!");
				
				header("Location: /new"); exit;
			}
			else
			{
				Alert::error("No Submission", "An error occurred that prevented this post from submitting.", 1);
			}
		}
		else
		{
			Alert::error("Page Inactive", "The URL that you provided cannot be reached right now.", 2);
		}
	}
}
else
{
	$_POST['title'] = (isset($_POST['title']) ? Sanitize::text($_POST['title']) : "");
	$_POST['url'] = (isset($_POST['url']) ? Sanitize::url($_POST['url']) : "");
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
<div id="content" class="content-open">' . Alert::display();

echo '
<h2>Submit a New Post</h2>
<form class="uniform" action="/post" method="post">' . Form::prepare("post-microfaction-" . SITE_HANDLE) . '
	<p>
		Category:
		<select name="hashtag">';
		
		foreach($hashtagList as $hashtag)
		{
			echo '
			<option value="' . $hashtag . '"' . ($_GET['hashtag'] == $hashtag ? ' selected' : '') . '>#' . $hashtag . '</option>';
		}
		
		echo '
		</select>
	</p>
	<p>Title: <input type="text" name="title" value="' . htmlspecialchars($_POST['title']) . '" maxlength="72" autocomplete="off" /></p>
	<p>URL: <input type="text" name="url" value="' . htmlspecialchars($_POST['url']) . '" maxlength="148" autocomplete="off" /></p>
	<p><input type="submit" name="submit" value="Create Post" /></p>
</form>';

echo '
</div>';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
