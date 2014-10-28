<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Force Login
if(!Me::$loggedIn)
{
	Me::redirectLogin("/post", "/");
}

// If the name of the current hashtag isn't passed, exit
if(!isset($_GET['hashtag'])) { exit; }
// Check for clearance
$hashtagClearance = AppHashtags::checkPrivileges(Me::$id, $_GET['hashtag']);


if(!($hashtagClearance >= 6 || $hashtagClearance == true))
{
	Alert::saveError("Not Admin", "Editing hashtag rules requires administrative privileges");
	header("Location: /new/" . $_GET['hashtag']); exit;
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

// Our APP_PATH will end in microfaction/microfaction. We need to be in
// the folder for our specific microfaction, so we go up one level by
// grabbing the dirname(APP_PATH). What we will end up with is:
// /microfaction/_____/includes/rules/_____.php
echo '<h3>Edit rules for ' . $_GET['hashtag'] . ':</h3>
<form class="uniform" action="/post" method="post">' . Form::prepare("hashtag-edit-rules-" . SITE_HANDLE). '
	<p>
		<textarea name="rules" style="width: 400px; max-width: 100%; box-sizing: border-box; -webkit-box-sizing: border-box; -moz-box-sizing: border-box;">';
			if(file_exists(CONF_PATH . "/includes/rules/rules-" . $_GET['hashtag'] . ".php"))
			{
				require(CONF_PATH . "/includes/rules/rules-" . $_GET['hashtag'] . ".php");
			} else {
				echo 'There are currently no rules for this hashtag!';
			}
			
		echo '</textarea>
		<input type="text" name="hashtag" style="display: none;" value="' . $_GET['hashtag'] . '">
	</p>
	<p>
		<input type="submit" name="submit" value="Save">
	</p>
</form>
';

// Display the Footer
require(SYS_PATH . "/controller/includes/footer.php");
