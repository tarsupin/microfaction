<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

/*
	/user-panel/reports/thread
	
	This page is used to create site reports about a thread (flag as spam, offensive, etc).
*/

// Make sure you have a valid thread
if(!isset($_POST['thread']))
{
	header("Location: /user-panel/reports"); exit;
}

if(!$threadData = AppThreads::threadData($_POST['thread'] + 0, "id, uni_id, title, category_id, url, date_created"))
{
	header("Location: /user-panel/reports"); exit;
}

// Make sure you're logged in
if(!Me::$loggedIn)
{
	Me::redirectLogin("/user-panel/reports");
}

// Get the Author's Data
if(!$authorData = User::get($threadData['uni_id'], "handle, display_name"))
{
	header("Location: /user-panel/reports"); exit;
}

// Form to Submit User Report
if(Form::submitted("report-thread"))
{
	// Form Validations
	FormValidate::variable("Reason for Report", $_POST['action'], 1, 22, " ");
	FormValidate::text("Explanation of Report", $_POST['details'], 1, 3500);
	
	if(FormValidate::pass())
	{
		// Add Essential Details
		$_POST['details'] .= '

[Title of Thread] => ' . $threadData['title'] . '
[Thread Category] => ' . $microfaction['categories'][$threadData['category_id']] . '
[Date Posted] => ' . date("F jS, Y", $threadData['date_created']);
		
		// File the Report
		if(SiteReport::create($_POST['action'], "/thread?id=" . $threadData['id'], Me::$id, $threadData['uni_id'], $_POST['details']))
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
<h2 style="margin-top:20px;">Report a Thread</h2>

<form class="uniform" action="/user-panel/reports/thread?thread=' . ($_POST['thread'] + 0) . '" method="post">' . Form::prepare("report-thread") . '
	<p>
		Reason for Report:<br />
		<select name="action">' . str_replace('value="' . $_POST['action'] . '"', 'value="' . $_POST['action'] . '" selected', '
			<option value="">-- Please select an option --</option>
			<option value="Spam">Thread is Spam</option>
			<option value="Offensive Thread">Thread is Offensive</option>
			<option value="Wrong Location">Thread in the Wrong Location</option>
			<option value="Other">Other Reason</option>') . '
		</select>
	</p>
	<p>
		Thread Details:
		<br /> &nbsp; &bull; Title: ' . $threadData['title'] . '
		<br /> &nbsp; &bull; Category: ' . $microfaction['categories'][$threadData['category_id']] . '
		<br /> &nbsp; &bull; Poster: ' . $authorData['display_name'] . ' (@' . $authorData['handle'] . ')
		<br /> &nbsp; &bull; Posted: ' . Time::fuzzy($threadData['date_created']) . '
	</p>
	<p>
		Please Explain this Report (be specific):<br />
		<textarea name="details" style="min-width:350px; min-height:120px;">' . htmlspecialchars($_POST['details']) . '</textarea>
	</p>
	<p><input type="submit" name="submit" value="Submit Report" /></p>
</form>';

// Display the Footer
require(SYS_PATH . "/controller/includes/user_panel_footer.php");

