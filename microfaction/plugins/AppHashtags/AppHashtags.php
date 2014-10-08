<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppHashtags Plugin ------
-----------------------------------------

This plugin allows interaction with the hashtags saved on each MicroFaction site.


----------------------------
------ Hashtag Values ------
----------------------------

Priority (9)		- Default Hashtag, Active
Priority (8)		- Default Hashtag, Inactive
Priority (6)		- Priority Listed
Priority (4)		- Listed

-------------------------------
------ Methods Available ------
-------------------------------

*/

abstract class AppHashtags {
	
	
/****** Class Variables ******/
	public static $userSubs = array();			// <array>
	public static $userActiveSubs = array();	// <array>
	public static $userSubsSQL = "";			// <str>
	
	
/****** Get the subscriptions for the user ******/
	public static function getSubscriptions
	(
		$uniID			// <int> The UniID to get subscriptions for.
	)					// RETURNS <void>
	
	// AppHashtags::getSubscriptions($uniID);
	{
		self::$userActiveSubs = array();
		self::$userSubsSQL = "";
		
		// If the user isn't logged in, just provide the full list
		if($uniID == 0)
		{
			// Get the site's priority hashtags
			$priorityTags = Database::selectMultiple("SELECT hashtag, priority FROM micro_hashtags WHERE priority >= ? ORDER BY hashtag", array(8));
			
			foreach($priorityTags as $tag)
			{
				self::$userSubs[] = $tag['hashtag'];
				
				if($tag['priority'] == 9)
				{
					self::$userActiveSubs[] = $tag['hashtag'];
					self::$userSubsSQL .= (self::$userSubsSQL == "" ? "" : ", ") . "?";
				}
			}
			
			return;
		}
		
		// Get the list of user's active subs
		$subs = Database::selectMultiple("SELECT hashtag, active FROM user_subscriptions WHERE uni_id=?", array($uniID));
		
		if(count($subs) == 0)
		{
			// Get the site's priority hashtags
			$priorityTags = Database::selectMultiple("SELECT hashtag, priority FROM micro_hashtags WHERE priority >= ? ORDER BY hashtag", array(8));
			
			Database::startTransaction();
			
			// Add all subscriptions to the user
			foreach($priorityTags as $tag)
			{
				$active = ($tag['priority'] == 9 ? 1 : 0);
				
				Database::query("INSERT INTO user_subscriptions (uni_id, hashtag, active) VALUES (?, ?, ?)", array($uniID, $tag['hashtag'], $active));
			}
			
			Database::endTransaction();
			
			$subs = Database::selectMultiple("SELECT hashtag, active FROM user_subscriptions WHERE uni_id=?", array($uniID));
		}
		
		foreach($subs as $sub)
		{
			self::$userSubs[] = $sub['hashtag'];
			
			// Check if the value is active
			if($sub['active'] == 1)
			{
				self::$userActiveSubs[] = $sub['hashtag'];
				self::$userSubsSQL .= (self::$userSubsSQL == "" ? "" : ", ") . "?";
			}
		}
	}
	
	
/****** Check if a hashtag exists ******/
	public static function exists
	(
		$hashtag		// <str> The hashtag to verify if it exists or not.
	)					// RETURNS <bool>
	
	// AppHashtags::exists($hashtag);
	{
		if($check = Database::selectValue("SELECT hashtag FROM micro_hashtags WHERE hashtag=? LIMIT 1", array($hashtag)))
		{
			return true;
		}
		
		return false;
	}
	
	
/****** Get the full list of hashtags ******/
	public static function getFullList (
	)					// RETURNS <int:[str:mixed]>
	
	// $hashtagList = AppHashtags::getFullList();
	{
		$hashtags = array();
		
		$list = Database::selectMultiple("SELECT * FROM micro_hashtags", array());
		
		foreach($list as $l)
		{
			$hashtags[] = $l['hashtag'];
		}
		
		return $hashtags;
	}
	
	
/****** Get a list of hashtags based on priority ******/
	public static function getByPriority
	(
		$priorityLevel = 6	// <int> The minimum priority level to return.
	)						// RETURNS <int:str>
	
	// $hashtags = AppHashtags::getByPriority([$priorityLevel]);
	{
		$hashtags = array();
		
		$list = Database::selectMultiple("SELECT hashtag FROM micro_hashtags WHERE priority >= ? ORDER BY hashtag", array(6));
		
		foreach($list as $l)
		{
			$hashtags[] = $l['hashtag'];
		}
		
		return $hashtags;
	}
	
	
/****** Create a MicroFaction Hashtag ******/
	public static function create
	(
		$hashtag		// <str> The hashtag to create.
	,	$priority		// <int> The priority integer (9 = default active, 8 = default inactive, 6 = listed, 4 = available)
	)					// RETURNS <void>
	
	// AppHashtags::create($hashtag, $priority);
	{
		Database::query("REPLACE INTO micro_hashtags (hashtag, priority) VALUES (?, ?)", array($hashtag, $priority));
	}
	
	
/****** Return Category Scan Data for a Thread ******/
	public static function filterHashtag
	(
		$hashtag = ""	// <str> The hashtag to restrict this search to.
	)					// RETURNS <int:mixed> a filtered list for SQL.
	
	// list($sqlQ, $sqlArray) = AppHashtags::filterHashtag($hashtag);
	{
		// If we're searching a specific hashtag
		if($hashtag != "")
		{
			$sqlQ = "?";
			$sqlArray = array($hashtag);
		}
		
		// If we're searching the standard home page (all active subs)
		else
		{
			$sqlQ = Sanitize::whitelist(self::$userSubsSQL, ", ?");
			$sqlArray = self::$userActiveSubs;
		}
		
		return array($sqlQ, $sqlArray);
	}
	
	
/****** Add a hashtag subscription to a user ******/
	public static function hashtagSubscribe
	(
		$uniID				// <int> The uniID of the user.
	,	$hashtag			// <str> The hashtag.
	,	$subscribe = true	// <bool> TRUE if adding the subscription, FALSE if removing it.
	)						// RETURNS <bool> TRUE if exists, FALSE on failure.
	
	// AppHashtags::hashtagSubscribe($uniID, $hashtag, $subscribe);
	{
		// Make sure that hashtag exists
		if(!self::exists($hashtag))
		{
			return false;
		}
		
		// Check if the user has the subscription listed
		if(Database::selectValue("SELECT hashtag FROM user_subscriptions WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag)))
		{
			if(!$subscribe)
			{	
				return Database::query("DELETE FROM user_subscriptions WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag));
			}
		}
		else if($subscribe)
		{
			return Database::query("INSERT INTO user_subscriptions (uni_id, hashtag, active) VALUES (?, ?, ?)", array($uniID, $hashtag, 1));
		}
		
		return true;
	}
	
	
/****** Add a hashtag subscription to a user ******/
	public static function hashtagVisible
	(
		$uniID				// <int> The uniID of the user.
	,	$hashtag			// <str> The hashtag.
	,	$visible = true		// <bool> TRUE to make it visible, FALSE if hiding it.
	)						// RETURNS <bool> TRUE if exists, FALSE on failure.
	
	// AppHashtags::hashtagVisible($uniID, $hashtag, $visible);
	{
		// Make sure that hashtag exists
		if(!self::exists($hashtag))
		{
			return false;
		}
		
		// Make sure the user is subscribed
		if(!Database::selectValue("SELECT uni_id FROM user_subscriptions WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag)))
		{
			return false;
		}
		
		return Database::query("UPDATE user_subscriptions SET active=? WHERE uni_id=? AND hashtag=? LIMIT 1", array(($visible === true ? 1 : 0), $uniID, $hashtag));
	}
	
	
/****** Draw your hashtag subscriptions ******/
	public static function drawSubscriptionList
	(
		$page = ""		// <str> The page that you're currently on (e.g. "new");
	)					// RETURNS <void> OUTPUTS HTML for the subscription list, FALSE on failure.
	
	// AppHashtags::drawSubscriptionList($page);
	{
		$page = ($page == "" ? "" : $page . "/");
		
		echo '
		<div>';
		
		foreach(self::$userSubs as $hashtag)
		{
			if(in_array($hashtag, self::$userActiveSubs))
			{
				echo '
				<div class="s-tag s-tag-on"><a href="/' . $page . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&remove=1"><span class="icon-circle-close"></span></a> <a href="/' . $page . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '"><span class="icon-eye"></span></a> <a href="/' . $page . urlencode($hashtag) . '">' . $hashtag . '</a></div>';
			}
			else
			{
				echo '
				<div class="s-tag s-tag-off"><a href="/' . $page . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&remove=1"><span class="icon-circle-close"></span></a> <a href="/' . $page . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&vis=1"><span class="icon-eye"></span></a> <a href="/' . $page . urlencode($hashtag) . '">' . $hashtag . '</a></div>';
			}
		}
		
		echo '
			<div class="s-tag s-tag-off" style="background-color:#eea0a0;"><a href="/subscriptions"><span class="icon-plus"></span> Add More</a></div>
		</div>';
	}
	
}
