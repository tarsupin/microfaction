<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppThreads {

/****** AppThreads Class ******
This class provides handling for a microfaction site.

****** Examples of using this class ******



****** Methods Available ******

$priorityThreads = AppThreads::listPriority([$page], [$showNum]);
$newThreads = AppThreads::listNew([$page], [$showNum]);
$bestThreads = AppThreads::listBest([$page], [$showNum]);

$voteList = AppThreads::getVoteList($uniID, $threadList);
$vote = AppThreads::getUserVote($uniID, $threadID);
AppThreads::vote($uniID, $threadID, $vote);

AppThreads::voteUp($threadID);
AppThreads::clickThread($threadID);

$threadID = AppThreads::createThread($hashtag, $title, [$url], [$photo]);
AppThreads::pruneNew();

$threadData = AppThreads::threadData($threadID, $columns);
$rating = AppThreads::getRatingByID($threadID);
$rating = AppThreads::updateRating($threadID);

AppThreads::delete($hashtag, $threadID);

AppThreads::runPriority();

AppThreads::hashtagSubscribe($uniID, $hashtag, $subscribe = true);
AppThreads::hashtagVisible($uniID, $hashtag, $visible);

AppThreads::drawSubscriptionList();

*/
	
	
/****** Class Variables ******/
	public static $userSubs = array();			// <array>
	public static $userActiveSubs = array();	// <array>
	public static $userSubsSQL = "";			// <str>
	public static $activeHashtag = "";			// <str> The active hashtag being used.
	
	
/****** Get the subscriptions for the user ******/
	public static function getSubscriptions
	(
		$uniID			// <int> The UniID to get subscriptions for.
	,	$microfaction	// <str:mixed> The site's microfaction data.
	)					// RETURNS <void>
	
	// AppThreads::getSubscriptions($uniID, $microfaction);
	{
		self::$userActiveSubs = array();
		self::$userSubsSQL = "";
		
		// If the user isn't logged in, just provide the full list
		if($uniID == 0)
		{
			foreach($microfaction['hashtags'] as $hashtag => $bool)
			{
				self::$userActiveSubs[] = $hashtag;
				self::$userSubsSQL .= (self::$userSubsSQL == "" ? "" : ", ") . "?";
			}
			
			return;
		}
		
		// Get the list of user's active subs
		$subs = Database::selectMultiple("SELECT hashtag, active FROM user_category_subs WHERE uni_id=?", array($uniID));
		
		if(count($subs) == 0)
		{
			Database::startTransaction();
			
			// Add all subscriptions to the user
			foreach($microfaction['hashtags'] as $hashtag => $bool)
			{
				Database::query("INSERT INTO user_category_subs (uni_id, hashtag, active) VALUES (?, ?, ?)", array($uniID, $hashtag, 1));
			}
			
			Database::endTransaction();
			
			$subs = Database::selectMultiple("SELECT hashtag, active FROM user_category_subs WHERE uni_id=?", array($uniID));
		}
		
		foreach($subs as $sub)
		{
			if(isset($microfaction['hashtags'][$sub['hashtag']]))
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
	}
	
	
/****** Get a list of Prioritized Threads ******/
	public static function listPriority
	(
		$page = 1		// <int> The page to return.
	,	$showNum = 50	// <int> The number of threads to show.
	,	$hashtag = ""	// <str> The hashtag to restrict this search to.
	)					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $priorityThreads = AppThreads::listPriority([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppThreads::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_priority tp INNER JOIN threads t ON t.id=tp.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tp.hashtag IN (" . $sqlQ . ") ORDER BY tp.rating DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Get a list of New Threads ******/
	public static function listNew
	(
		$page = 1		// <int> The page to return.
	,	$showNum = 50	// <int> The number of threads to show.
	,	$hashtag = ""	// <str> The hashtag to restrict this search to.
	)					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $newThreads = AppThreads::listNew([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppThreads::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_new tn INNER JOIN threads t ON t.id=tn.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tn.hashtag IN (" . $sqlQ . ") ORDER BY tn.thread_id DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Get a list of the Best All-Time Threads ******/
	public static function listBest
	(
		$page = 1		// <int> The page to return.
	,	$showNum = 50	// <int> The number of threads to show.
	,	$hashtag = ""	// <str> The hashtag to restrict this search to.
	)					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $bestThreads = AppThreads::listBest([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppThreads::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_best tb INNER JOIN threads t ON t.id=tb.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tb.hashtag IN (" . $sqlQ . ") ORDER BY tb.rating DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Return Category Scan Data for a Thread ******/
	private static function filterHashtag
	(
		$hashtag = ""	// <str> The hashtag to restrict this search to.
	)					// RETURNS <int:mixed> a filtered list for SQL.
	
	// list($sqlQ, $sqlArray) = AppThreads::filterHashtag($hashtag);
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
	
	
/****** Get a list of User Votes ******/
	public static function getVoteList
	(
		$uniID			// <int> The UniID of the user.
	,	$threadList		// <array> The list of thread ID's to check if you've voted on.
	)					// RETURNS <int:int> a list of your votes, array() on failure.
	
	// $voteList = AppThreads::getVoteList($uniID, $threadList);
	{
		$sql = "";
		
		foreach($threadList as $threadID)
		{
			$sql .= ($sql != "" ? ", " : "") . "?";
		}
		
		if(!$sql) { return false; }
		
		array_unshift($threadList, $uniID);
		
		$voteList = array();
		$votes = Database::selectMultiple("SELECT thread_id, vote FROM votes_thread WHERE uni_id=? AND thread_id IN (" . $sql . ")", $threadList);
		
		foreach($votes as $vote)
		{
			$voteList[(int) $vote['thread_id']] = (int) $vote['vote'];
		}
		
		return $voteList;
	}
	
	
/****** Get a User's vote on a thread ******/
	public static function getUserVote
	(
		$uniID			// <int> The UniID of the user.
	,	$threadID		// <int> The thread to check the vote of.
	)					// RETURNS <int> the user's vote, or 0 on failure.
	
	// $vote = AppThreads::getUserVote($uniID, $threadID);
	{
		return (int) Database::selectValue("SELECT vote FROM votes_thread WHERE uni_id=? AND thread_id=? LIMIT 1", array($uniID, $threadID));
	}
	
	
/****** Vote on a Thread ******/
	public static function vote
	(
		$uniID			// <int> The UniID voting on the thread.
	,	$threadID		// <int> The thread to vote on.
	,	$vote = 1		// <int> 1 if voted up, -1 if voted down.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::vote($uniID, $threadID, $vote);
	{
		// Get the Thread Post Time
		$datePosted = (int) Database::selectValue("SELECT date_created FROM threads WHERE id=? LIMIT 1", array($threadID));
		
		// Users are not allowed to vote on threads that are older than three weeks
		if(!$datePosted or $datePosted < time() - (60 * 60 * 24 * 21))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Check if the user has already voted
		if(!$userVote = self::getUserVote($uniID, $threadID))
		{
			// Add the New Vote
			if($pass = Database::query("INSERT INTO votes_thread (uni_id, thread_id, vote) VALUES (?, ?, ?)", array($uniID, $threadID, $vote)))
			{
				$pass = Database::query("UPDATE threads SET " . ($vote == 1 ? 'vote_up=vote_up+1' : 'vote_down=vote_down+1') . " WHERE id=? LIMIT 1", array($threadID));
			}
		}
		
		// Remove the existing vote (if vote was identical)
		else if($vote == $userVote)
		{
			if($pass = Database::query("DELETE FROM votes_thread WHERE uni_id=? AND thread_id=? LIMIT 1", array($uniID, $threadID)))
			{
				$pass = Database::query("UPDATE threads SET " . ($vote == 1 ? 'vote_up=vote_up-1' : 'vote_down=vote_down-1') . " WHERE id=? LIMIT 1", array($threadID));
			}
		}
		
		// Switch the Vote
		else
		{
			if($pass = Database::query("UPDATE votes_thread SET vote=? WHERE uni_id=? AND thread_id=? LIMIT 1", array($vote, $uniID, $threadID)))
			{
				$pass = Database::query("UPDATE threads SET " . ($vote == 1 ? 'vote_up=vote_up+1, vote_down=vote_down-1' : 'vote_down=vote_down+1, vote_up=vote_up-1') . " WHERE id=? LIMIT 1", array($threadID));
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Click a Thread ******/
	public static function clickThread
	(
		$threadID		// <int> The thread to click.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::clickThread($threadID);
	{
		return Database::query("UPDATE threads SET clicks=clicks+1, actions=actions+1 WHERE id=? LIMIT 1", array($threadID));
	}
	
	
/****** Create Thread ******/
	public static function createThread
	(
		$hashtag		// <str> The hashtag to assign the thread to.
	,	$title			// <str> The thread title.
	,	$url = ""		// <str> The URL that the thread directs to (empty if a self-post).
	,	$photo = ""		// <str> The photo for the thread, if applicable.
	)					// RETURNS <int> the thread ID, 0 on failure.
	
	// $threadID = AppThreads::createThread($hashtag, $title, [$url], [$photo]);
	{
		Database::startTransaction();
		
		if($pass = Database::query("INSERT INTO threads (uni_id, hashtag, title, photo, url, date_created) VALUES (?, ?, ?, ?, ?, ?)", array(Me::$id, $hashtag, $title, $photo, $url, time())))
		{
			$threadID = Database::$lastID;
			
			$pass = Database::query("INSERT INTO threads_new (hashtag, thread_id, date_created) VALUES (?, ?, ?)", array($hashtag, $threadID, time()));
		}
		
		if(Database::endTransaction($pass))
		{
			// Prune list of new threads once in a while
			if(mt_rand(0, 40) == 5)
			{
				self::pruneNew();
			}
			
			return $threadID;
		}
		
		return 0;
	}
	
	
/****** Update Thread ******/
	public static function updateThread
	(
		$threadID		// <int> The thread ID.
	,	$title			// <str> The thread title.
	,	$url = ""		// <str> The URL that the thread directs to (empty if a self-post).
	,	$photo = ""		// <str> The photo for the thread, if applicable.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::updateThread($threadID, $title, [$url], [$photo]);
	{
		return Database::query("UPDATE threads SET title=?, photo=?, url=? WHERE id=? LIMIT 1", array($title, $photo, $url, $threadID));
	}
	
	
/****** Prune New Thread List ******/
	public static function pruneNew (
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::pruneNew();
	{
		return Database::query("DELETE FROM threads_new WHERE thread_id <= (SELECT thread_id FROM (SELECT thread_id FROM threads_new ORDER BY thread_id DESC LIMIT 1 OFFSET 10000) foo)", array());
	}
	
	
/****** Get Thread Data ******/
	public static function threadData
	(
		$threadID		// <int> The ID of the thread to retrieve.
	,	$columns = "*"	// <str> The columns to retrieve.
	)					// RETURNS <str:mixed> the data of the desired thread, FALSE on failure.
	
	// $threadData = AppThreads::threadData($threadID, $columns);
	{
		return Database::selectOne("SELECT " . Sanitize::variable($columns, " ,`*") . " FROM threads WHERE id=? LIMIT 1", array($threadID));
	}
	
	
/****** Get the Rating of a Thread ******/
	public static function getRatingByID
	(
		$threadID	// <int> The ID of the thread to retrieve the rating for.
	)				// RETURNS <mixed> the rating of the thread, FALSE on failure.
	
	// $rating = AppThreads::getRatingByID($threadID);
	{
		if($threadData = AppThreads::threadData($threadID, "vote_up, vote_down, actions, date_created"))
		{
			return Ranking::fast($threadData['vote_up'], $threadData['vote_down'], $threadData['actions'], time() - $threadData['date_created']);
		}
		
		return false;
	}
	
	
/****** Update the Rating of a Thread ******/
	public static function updateRating
	(
		$threadID	// <int> The ID of the thread to update the rating of.
	)				// RETURNS <mixed> the rating of the thread, FALSE on failure.
	
	// $rating = AppThreads::updateRating($threadID);
	{
		if($threadData = AppThreads::threadData($threadID, "votes_up, votes_down, actions, date_created"))
		{
			if($rating = Ranking::fast($threadData['votes_up'], $threadData['votes_down'], $threadData['actions'], time() - $threadData['date_created']))
			{
				if(Database::query("UPDATE threads SET rating=? WHERE id=? LIMIT 1", array($rating, $threadID)))
				{
					return (float) $rating;
				}
			}
		}
		
		return false;
	}
	
	
/****** Delete a Thread ******/
	public static function delete
	(
		$threadID		// <int> The ID of the thread to delete.
	)					// RETURNS <bool> TRUE if the thread is gone, FALSE on failure.
	
	// AppThreads::delete($threadID);
	{
		// Get the Hashtag
		if($hashtag = Database::selectValue("SELECT hashtag FROM threads WHERE id=? LIMIT 1", array($threadID)))
		{
			// Delete from the thread lists
			Database::query("DELETE FROM threads_new WHERE hashtag=? AND thread_id=? LIMIT 1", array($hashtag, $threadID));
			Database::query("DELETE FROM threads_best WHERE hashtag=? AND thread_id=? LIMIT 1", array($hashtag, $threadID));
			Database::query("DELETE FROM threads_priority WHERE hashtag=? AND thread_id=? LIMIT 1", array($hashtag, $threadID));
			
			// Delete all of the comments
			AppComment::deleteAll($threadID);
			
			// Delete the actual thread
			Database::query("DELETE FROM threads WHERE id=? LIMIT 1", array($threadID));
			
			return true;
		}
		
		return false;
	}
	
	
/****** Update Priority Threads ******/
	public static function runPriority
	(
		$microfaction	// <str:mixed> The site's microfaction data.
	)					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::runPriority($microfaction);
	{
		Database::startTransaction();
		
		// Truncate the threads_priority table
		Database::query("DELETE FROM threads_priority", array());
		
		$pass = true;
		
		foreach($microfaction['hashtags'] as $hashtag => $bool)
		{
			// Gather list of new threads, ordered by most recent
			$fullListNew = Database::selectMultiple("SELECT hashtag, thread_id FROM threads_new WHERE hashtag=? ORDER BY thread_id DESC LIMIT 100", array($hashtag));
			
			// Reload priorities based on ratings in new
			foreach($fullListNew as $new)
			{
				// Recognize Integers
				$new['thread_id'] = (int) $new['thread_id'];
				
				$rating = self::getRatingByID($new['thread_id']);
				
				if(!$pass = Database::query("INSERT INTO threads_priority (hashtag, rating, thread_id) VALUES (?, ?, ?)", array($new['hashtag'], $rating, $new['thread_id'])))
				{
					break;
				}
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Get the Photo of the Page ******/
	public static function getPhoto
	(
		$html		// <str> The html of the page.
	)				// RETURNS <mixed>
	
	// $photo = AppThreads::getPhoto($html);
	{
		$photo = "";
		
		// Get the OG values
		libxml_use_internal_errors(true);
		$doc = new DomDocument();
		$doc->loadHTML($html);
		$xpath = new DOMXPath($doc);
		$query = '//*/meta[starts-with(@property, \'og:\')]';
		$metas = $xpath->query($query);
		
		foreach($metas as $meta)
		{
			$property = $meta->getAttribute('property');
			
			if($property == "og:image")
			{
				$photo = $meta->getAttribute('content');
				break;
			}
			
			else if($photo == "")
			{
				if($property == "og:image:url")
				{
					$photo = $meta->getAttribute('content');
				}
				else if($property == "og:image:secure_url")
				{
					$photo = $meta->getAttribute('content');
				}
			}
			
			// $content = $meta->getAttribute('content');
			// $rmetas[$property] = $content;
		}
		
		return $photo;
	}
	
	
/****** Get the URL for a photo ******/
	public static function photoURL
	(
		$threadID		// <int> The page to return.
	)					// RETURNS <str> the URL to the photo.
	
	// $photoURL = AppThreads::photoURL($threadID);
	{
		return APP_PATH . "/images/thumbs/" . ceil($threadID / 10000) . "/" . $threadID . ".jpg";
	}
	
	
/****** Add a hashtag subscription to a user ******/
	public static function hashtagSubscribe
	(
		$uniID				// <int> The uniID of the user.
	,	$hashtag			// <str> The hashtag.
	,	$subscribe = true	// <bool> TRUE if adding the subscription, FALSE if removing it.
	)						// RETURNS <bool> TRUE if exists, FALSE on failure.
	
	// AppThreads::hashtagSubscribe($uniID, $hashtag, $subscribe);
	{
		global $microfaction;
		
		// Make sure that hashtag exists
		if(!isset($microfaction['hashtags'][$hashtag]))
		{
			return false;
		}
		
		// Check if the user has the subscription listed
		if(Database::selectValue("SELECT hashtag FROM user_category_subs WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag)))
		{
			if(!$subscribe)
			{	
				return Database::query("DELETE FROM user_category_subs WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag));
			}
		}
		else if($subscribe)
		{
			return Database::query("INSERT INTO user_category_subs (uni_id, hashtag, active) VALUES (?, ?, ?)", array($uniID, $hashtag, 1));
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
	
	// AppThreads::hashtagVisible($uniID, $hashtag, $visible);
	{
		global $microfaction;
		
		// Make sure that category exists
		if(!isset($microfaction['hashtags'][$hashtag]))
		{
			return false;
		}
		
		// Make sure the user is subscribed
		if(!Database::selectValue("SELECT uni_id FROM user_category_subs WHERE uni_id=? AND hashtag=? LIMIT 1", array($uniID, $hashtag)))
		{
			return false;
		}
		
		return Database::query("UPDATE user_category_subs SET active=? WHERE uni_id=? AND hashtag=? LIMIT 1", array(($visible === true ? 1 : 0), $uniID, $hashtag));
	}
	
	
/****** Draw your hashtag subscriptions ******/
	public static function drawSubscriptionList
	(
		$microfaction	// <str:mixed> The site's microfaction data.
	)					// RETURNS <void> OUTPUTS HTML for the subscription list, FALSE on failure.
	
	// echo AppThreads::drawSubscriptionList($microfaction);
	{
		global $url;
		
		$new = ($url[0] == "new" ? "new/" : "");
		
		echo '
		<div>';
		
		foreach($microfaction['hashtags'] as $hashtag => $bool)
		{
			if(in_array($hashtag, self::$userSubs))
			{
				if(in_array($hashtag, self::$userActiveSubs))
				{
					echo '
					<div class="s-tag s-tag-on"><a href="/' . $new . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&remove=1"><span class="icon-circle-close"></span></a> <a href="/' . $new . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '"><span class="icon-eye"></span></a> <a href="/' . $new . urlencode($hashtag) . '">' . $hashtag . '</a></div>';
				}
				else
				{
					echo '
					<div class="s-tag s-tag-off"><a href="/' . $new . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&remove=1"><span class="icon-circle-close"></span></a> <a href="/' . $new . '?tag=' . $hashtag . '&' . Link::prepare("microfaction-tag") . '&vis=1"><span class="icon-eye"></span></a> <a href="/' . $new . urlencode($hashtag) . '">' . $hashtag . '</a></div>';
				}
			}
		}
		
		echo '
			<div class="s-tag s-tag-off" style="background-color:#eea0a0;"><a href="/subscriptions"><span class="icon-plus"></span> Edit</a></div>
		</div>';
	}
	
}
