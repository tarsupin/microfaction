<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); } /*

-----------------------------------------
------ About the AppThreads Plugin ------
-----------------------------------------

This plugin provides handling for the threads on MicroFaction sites.


-------------------------------
------ Methods Available ------
-------------------------------

$priorityThreads = AppThreads::listPriority([$page], [$showNum]);
$newThreads = AppThreads::listNew([$page], [$showNum]);
$bestThreads = AppThreads::listBest([$page], [$showNum]);

$voteList = AppThreads::getVoteList($uniID, $threadList);
$vote = AppThreads::getUserVote($uniID, $threadID);
AppThreads::vote($uniID, $threadID, $vote);

AppThreads::voteUp($threadID);
AppThreads::clickThread($threadID);

$threadID = AppThreads::createThread($hashtag, $title, [$url], [$thumbnail]);
AppThreads::pruneNew();

$threadData = AppThreads::threadData($threadID, $columns);
$rating = AppThreads::getRatingByID($threadID);
$rating = AppThreads::updateRating($threadID);

AppThreads::delete($hashtag, $threadID);

AppThreads::runPriority();

AppHashtags::hashtagSubscribe($uniID, $hashtag, $subscribe = true);
AppHashtags::hashtagVisible($uniID, $hashtag, $visible);

AppHashtags::drawSubscriptionList([$page]);

*/

abstract class AppThreads {
	
	
/****** Get a list of Prioritized Threads ******/
	public static function listPriority
	(
		int $page = 1		// <int> The page to return.
	,	int $showNum = 50	// <int> The number of threads to show.
	,	string $hashtag = ""	// <str> The hashtag to restrict this search to.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $priorityThreads = AppThreads::listPriority([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppHashtags::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_priority tp INNER JOIN threads t ON t.id=tp.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tp.hashtag IN (" . $sqlQ . ") ORDER BY tp.rating DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Get a list of New Threads ******/
	public static function listNew
	(
		int $page = 1		// <int> The page to return.
	,	int $showNum = 50	// <int> The number of threads to show.
	,	string $hashtag = ""	// <str> The hashtag to restrict this search to.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $newThreads = AppThreads::listNew([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppHashtags::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_new tn INNER JOIN threads t ON t.id=tn.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tn.hashtag IN (" . $sqlQ . ") ORDER BY tn.thread_id DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Get a list of the Best All-Time Threads ******/
	public static function listBest
	(
		int $page = 1		// <int> The page to return.
	,	int $showNum = 50	// <int> The number of threads to show.
	,	string $hashtag = ""	// <str> The hashtag to restrict this search to.
	): array <int, array<str, mixed>>					// RETURNS <int:[str:mixed]> an array of threads.
	
	// $bestThreads = AppThreads::listBest([$page], [$showNum], [$hashtag]);
	{
		// Check if we're filtering results to specific hashtag
		list($sqlQ, $sqlArray) = AppHashtags::filterHashtag($hashtag);
		
		if(!$sqlQ) { return array(); }
		
		return Database::selectMultiple("SELECT t.*, u.handle, u.display_name FROM threads_best tb INNER JOIN threads t ON t.id=tb.thread_id INNER JOIN users u ON u.uni_id=t.uni_id WHERE tb.hashtag IN (" . $sqlQ . ") ORDER BY tb.rating DESC LIMIT " . (($page - 1) * $showNum) . ", " . ($showNum + 0), $sqlArray);
	}
	
	
/****** Get a list of User Votes ******/
	public static function getVoteList
	(
		int $uniID			// <int> The UniID of the user.
	,	array $threadList		// <array> The list of thread ID's to check if you've voted on.
	): array <int, int>					// RETURNS <int:int> a list of your votes, array() on failure.
	
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
		int $uniID			// <int> The UniID of the user.
	,	int $threadID		// <int> The thread to check the vote of.
	): int					// RETURNS <int> the user's vote, or 0 on failure.
	
	// $vote = AppThreads::getUserVote($uniID, $threadID);
	{
		return (int) Database::selectValue("SELECT vote FROM votes_thread WHERE uni_id=? AND thread_id=? LIMIT 1", array($uniID, $threadID));
	}
	
	
/****** Vote on a Thread ******/
	public static function vote
	(
		int $uniID			// <int> The UniID voting on the thread.
	,	int $threadID		// <int> The thread to vote on.
	,	int $vote = 1		// <int> 1 if voted up, -1 if voted down.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
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
		int $threadID		// <int> The thread to click.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::clickThread($threadID);
	{
		return Database::query("UPDATE threads SET views=views+1, actions=actions+1 WHERE id=? LIMIT 1", array($threadID));
	}
	
	
/****** Create Thread ******/
	public static function createThread
	(
		string $hashtag		// <str> The hashtag to assign the thread to.
	,	string $title			// <str> The thread title.
	,	string $url = ""		// <str> The URL that the thread directs to (empty if a self-post).
	,	string $thumbnail = ""	// <str> The thumbnail for the thread, if applicable.
	): int					// RETURNS <int> the thread ID, 0 on failure.
	
	// $threadID = AppThreads::createThread($hashtag, $title, [$url], [$thumbnail]);
	{
		Database::startTransaction();
		
		if($pass = Database::query("INSERT INTO threads (uni_id, hashtag, title, thumbnail, url, date_created) VALUES (?, ?, ?, ?, ?, ?)", array(Me::$id, $hashtag, $title, $thumbnail, $url, time())))
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
		int $threadID		// <int> The thread ID.
	,	string $title			// <str> The thread title.
	,	string $url = ""		// <str> The URL that the thread directs to (empty if a self-post).
	,	string $thumbnail = ""	// <str> The thumbnail for the thread, if applicable.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::updateThread($threadID, $title, [$url], [$thumbnail]);
	{
		return Database::query("UPDATE threads SET title=?, thumbnail=?, url=? WHERE id=? LIMIT 1", array($title, $thumbnail, $url, $threadID));
	}
	
	
/****** Prune New Thread List ******/
	public static function pruneNew (
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::pruneNew();
	{
		return Database::query("DELETE FROM threads_new WHERE thread_id <= (SELECT thread_id FROM (SELECT thread_id FROM threads_new ORDER BY thread_id DESC LIMIT 1 OFFSET 10000) foo)", array());
	}
	
	
/****** Get Thread Data ******/
	public static function threadData
	(
		int $threadID		// <int> The ID of the thread to retrieve.
	,	string $columns = "*"	// <str> The columns to retrieve.
	): array <str, mixed>					// RETURNS <str:mixed> the data of the desired thread, FALSE on failure.
	
	// $threadData = AppThreads::threadData($threadID, $columns);
	{
		return Database::selectOne("SELECT " . Sanitize::variable($columns, " ,`*") . " FROM threads WHERE id=? LIMIT 1", array($threadID));
	}
	
	
/****** Get the Rating of a Thread ******/
	public static function getRatingByID
	(
		int $threadID	// <int> The ID of the thread to retrieve the rating for.
	): mixed				// RETURNS <mixed> the rating of the thread, FALSE on failure.
	
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
		int $threadID	// <int> The ID of the thread to update the rating of.
	): mixed				// RETURNS <mixed> the rating of the thread, FALSE on failure.
	
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
		int $threadID		// <int> The ID of the thread to delete.
	): bool					// RETURNS <bool> TRUE if the thread is gone, FALSE on failure.
	
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
		array <int, str> $hashtags		// <int:str> The list of hashtags.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppThreads::runPriority($hashtags);
	{
		Database::startTransaction();
		
		// Truncate the threads_priority table
		Database::query("DELETE FROM threads_priority", array());
		
		$pass = true;
		
		foreach($hashtags as $hashtag)
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
	
	
/****** Get the Thumbnail of the Page ******/
	public static function getThumbnail
	(
		string $html		// <str> The html of the page.
	): mixed				// RETURNS <mixed>
	
	// $thumbnail = AppThreads::getThumbnail($html);
	{
		$thumbnail = "";
		
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
				$thumbnail = $meta->getAttribute('content');
				break;
			}
			
			else if($thumbnail == "")
			{
				if($property == "og:image:url")
				{
					$thumbnail = $meta->getAttribute('content');
				}
				else if($property == "og:image:secure_url")
				{
					$thumbnail = $meta->getAttribute('content');
				}
			}
			
			// $content = $meta->getAttribute('content');
			// $rmetas[$property] = $content;
		}
		
		return $thumbnail;
	}
	
	
/****** Get the URL for a thumbnail ******/
	public static function thumbnailURL
	(
		int $threadID		// <int> The page to return.
	): string					// RETURNS <str> the URL to the thumbnail.
	
	// $thumbnailURL = AppThreads::thumbnailURL($threadID);
	{
		return APP_PATH . "/images/thumbs/" . ceil($threadID / 10000) . "/" . $threadID . ".jpg";
	}
	
}