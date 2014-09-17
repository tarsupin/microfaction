<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/user-panel/reports/comment
	
	This page is used to create site reports about comments (flag as spam, offensive, etc).
*/

// Make sure you have a valid thread
if(!isset($_POST['thread']) or !isset($_POST['id']))
{
	header("Location: /user-panel/reports"); exit;
}

if(!$commentData = AppComment::getData($_POST['id'] + 0, "id, uni_id, comment, date_posted"))
{
	header("Location: /user-panel/reports"); exit;
}

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/user-panel/reports");
}

// Get the Author's Data
if(!$authorData = User::get($commentData['uni_id'], "handle, display_name"))
{
	header("Location: /user-panel/reports"); exit;
}

// Form to Submit User Report
if(Form::submitted("report-comment"))
{
	// Form Validations
	FormValidate::variable("Reason for Report", $_POST['action'], 1, 22, " ");
	FormValidate::text("Explanation of Report", $_POST['details'], 1, 3500);
	
	if(FormValidate::pass())
	{
		// Add Essential Details
		$_POST['details'] .= '

[Comment] => ' . $commentData['comment'] . '
[Date Posted] => ' . date("F jS, Y", $commentData['date_posted']);
		
		// File the Report
		if(SiteReport::create($_POST['action'], "/thread?id=" . $_POST['thread'] . "#comment-" . $commentData['id'], Me::$id, $commentData['uni_id'], $_POST['details']))
		{
			Alert::saveSuccess("Report Submitted", "Your report has been submitted! Thank you!");
			
			header("Location: /user-panel/reports"); exit;
		}
	}
}
else
{
	// Sanitize Data
	$_POST['action'] = Sanitize::variable($_POST['action'], " ");
	$_POST['details'] = Sanitize::text($_POST['details']);
}

// Run Header
require(SYS_PATH . "/controller/includes/user_panel_header.php");

// Display Form
echo '
<h2 style="margin-top:20px;">Report a Comment</h2>

<form class="uniform" action="/user-panel/reports/comment?thread=' . ($_POST['thread'] + 0) . '&id=' . ($_POST['id'] + 0) . '" method="post">' . Form::prepare("report-comment") . '
	<p>
		Reason for Report:<br />
		<select name="action">' . str_replace('value="' . $_POST['action'] . '"', 'value="' . $_POST['action'] . '" selected', '
			<option value="">-- Please select an option --</option>
			<option value="Spam">Comment is Spam</option>
			<option value="Offensive Comment">Comment is Offensive</option>
			<option value="Other">Other Reason</option>') . '
		</select>
	</p>
	<p>
		Thread Details:
		<br /> &nbsp; &bull; Comment: ' . $commentData['comment'] . '
		<br /> &nbsp; &bull; Poster: ' . $authorData['display_name'] . ' (@' . $authorData['handle'] . ')
		<br /> &nbsp; &bull; Posted: ' . Time::fuzzy($commentData['date_posted']) . '
	</p>
	<p>
		Please Explain this Report (be specific):<br />
		<textarea name="details" style="min-width:350px;min-height:120px;">' . htmlspecialchars($_POST['details']) . '</textarea>
	</p>
	<p><input type="submit" name="submit" value="Submit Report" /></p>
</form>';

// Display the Footer
require(SYS_PATH . "/controller/includes/user_panel_footer.php");

