<?hh if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

abstract class AppComment {

/****** AppComment Class ******
* This class will handle comments on the microfaction site.
* 
****** Examples of using this class ******


****** Methods Available ******
* $comments = AppComment::getThreadList($threadID, $startNum, $showNum, "DESC");
* $comments = AppComment::getReplies($commentID, $startNum, $showNum, "DESC");
* $commentData = AppComment::getData($commentID, [$columns]);
* 
* $commentID = AppComment::create($threadID, $uniID, $objectID, "Wow! Awesome!", $replyThread, $linkToComment, $toUniID);
* 
* AppComment::edit($commentID, $comment);			// Edits a comment
* 
* AppComment::deleteAll($threadID);
* AppComment::deleteMain($threadID, $commentID);		// Deletes a top-level comment (parent is thread)
* AppComment::deleteReplies($parentID, $commentID);
* 
* $voteList = AppComment::getVoteList($uniID, $threadID);
* $vote = AppComment::getUserVote($uniID, $threadID, $commentID);
* AppComment::vote($uniID, $threadID, $commentID, $vote);
* 
* AppComment::runPriority($threadID);
* AppComment::runReplyPriority($parentID);
* 
* AppComment::display($threadData, $comments);
* AppComment::displayRecursive($threadData, $commentID, $step, $extra = 0);
*/
	
	
/****** Class Variables ******/
	public static $stepsAllowed = 7;
	
	
/****** Get Comments of an Object ******/
	public static function getThreadList
	(
		int $threadID			// <int> The ID of the thread.
	,	int $startNum = 0		// <int> The starting number to get comments from.
	,	int $showNum = 10		// <int> The number of comments to return.
	,	string $order = "ASC"		// <str> The direction to sort by "ASC" or "DESC"
	): array <int, array<str, mixed>>						// RETURNS <int:[str:mixed]> comments for the object, FALSE on failure.
	
	// $comments = AppComment::getThreadList($threadID, $startNum, $showNum, "DESC");
	{
		return Database::selectMultiple("SELECT c.id, c.uni_id, c.has_child, c.comment, c.date_posted, u.display_name, u.handle FROM comments_threads ct INNER JOIN comments c ON c.id=ct.id INNER JOIN users u ON u.uni_id=c.uni_id WHERE ct.thread_id=? ORDER BY ct.rating " . ($order == "ASC" ? "ASC" : "DESC") . " LIMIT " . ($startNum + 0) . ", " . ($showNum + 0), array($threadID));
	}
	
	
/****** Get Replies of a Comment, Recursively ******/
	public static function getReplies
	(
		int $commentID			// <int> The ID of the comment to get replies from.
	,	int $startNum = 0		// <int> The starting number to get comments from.
	,	int $showNum = 10		// <int> The number of comments to return.
	,	string $order = "ASC"		// <str> The direction to sort by "ASC" or "DESC"
	): array <int, array<str, mixed>>						// RETURNS <int:[str:mixed]> comments for the object, FALSE on failure.
	
	// $comments = AppComment::getReplies($commentID, $startNum, $showNum, "DESC");
	{
		return Database::selectMultiple("SELECT c.id, c.uni_id, c.has_child, c.comment, c.date_posted, u.display_name, u.handle FROM comments_replies cr INNER JOIN comments c ON c.id=cr.id INNER JOIN users u ON u.uni_id=c.uni_id WHERE cr.parent_id=? ORDER BY cr.rating " . ($order == "ASC" ? "ASC" : "DESC") . " LIMIT " . ($startNum + 0) . ", " . ($showNum + 0), array($commentID));
	}
	
	
/****** Get Comment Data ******/
	public static function getData
	(
		int $commentID			// <int> The ID of the comment.
	,	string $columns = "*"		// <str> The columns to retrieve from the comment.
	): array <str, mixed>						// RETURNS <str:mixed> comments for the object, FALSE on failure.
	
	// $commentData = AppComment::getData($commentID, [$columns]);
	{
		return Database::selectOne("SELECT " . Sanitize::variable($columns, " ,`*") . " FROM comments WHERE id=? LIMIT 1", array($commentID));
	}
	
	
/****** Create a Comment ******/
	public static function create
	(
		int $threadID				// <int> The thread ID that you're posting in.
	,	int $uniID					// <int> The Uni-Account of the user that is commenting.
	,	int $objectID				// <int> The ID of the object being commented on (parent commentID or threadID).
	,	string $comment				// <str> The comment to post.
	,	int $replyThread = 1		// <int> 1 if replying to thread, 0 if replying to comment.
	,	string $link = ""				// <str> The link to this particular comment.
	,	int $toUniID = 0			// <int> The UniID of the target being commented to.
	): int							// RETURNS <int> ID of the new comment, 0 if failed.
	
	// $commentID = AppComment::create($threadID, $uniID, $objectID, "Wow! Awesome!", $replyThread, $linkToComment, $toUniID);
	{
		Database::startTransaction();
		
		// Insert the comment and structure
		if($pass = Database::query("INSERT INTO `comments` (`uni_id`, `comment`, `date_posted`) VALUES (?, ?, ?)", array($uniID, $comment, time())))
		{
			$commentID = Database::$lastID;
			
			// Update the comment count of a thread
			$pass = Database::query("UPDATE threads SET comments=comments+1 WHERE id=? LIMIT 1", array($threadID));
		}
		
		if(!$pass) { Database::endTransaction($pass); return 0; }
		
		// If responding to a comment
		if($replyThread != 1)
		{
			Database::query("UPDATE comments SET has_child=? WHERE id=? LIMIT 1", array(1, $objectID));
			
			$pass = Database::query("INSERT INTO comments_replies (parent_id, id) VALUES (?, ?)", array($objectID, $commentID));
		}
		
		// If responding to a thread
		else
		{
			$pass = Database::query("INSERT INTO comments_threads (thread_id, id) VALUES (?, ?)", array($threadID, $commentID));
		}
		
		// Upvote this Comment
		if($pass)
		{
			self::vote($uniID, $threadID, $commentID, 1);
		}
		
		if(Database::endTransaction($pass))
		{
			// Process the Comment (Hashtag, Credits, Notifications, etc)
			Comment::process($uniID, $comment, $link, $toUniID);
			
			return $commentID;
		}
		
		return 0;
	}
	
	
/****** Edit a Comment ******/
	public static function edit
	(
		int $commentID		// <int> The ID of the comment to edit.
	,	string $comment		// <str> The new comment that you're switching it to.
	): bool					// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::edit($commentID, "Thanks guys! Edit: Sorry, fixed grammar.");
	{
		return Database::query("UPDATE `comments` SET `comment`=? WHERE id=? LIMIT 1", array($comment, $commentID));
	}
	
	
/****** Delete all comments in a thread ******/
	public static function deleteAll
	(
		int $threadID		// <int> The ID of the thread that contains all comments to delete.
	): bool					// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::deleteAll($threadID);
	{
		Database::startTransaction();
		
		$comments = Database::selectMultiple("SELECT id FROM comments_threads WHERE thread_id=?", array($threadID));
		
		foreach($comments as $comment)
		{
			self::deleteMain($threadID, (int) $comment['id']);
		}
		
		return Database::endTransaction();
	}
	
	
/****** Delete a Comment ******/
	public static function deleteMain
	(
		int $threadID		// <int> The ID of the thread.
	,	int $commentID		// <int> The ID of the comment to delete.
	): bool					// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::deleteMain($threadID, $commentID);
	{
		Database::startTransaction();
		
		$getReplies = Database::selectMultiple("SELECT c.id, c.has_child FROM comments_replies cr INNER JOIN comments c ON c.id=cr.id WHERE cr.parent_id=?", array($commentID));
		
		foreach($getReplies as $reply)
		{
			// Recognize Integers
			$reply['id'] = (int) $reply['id'];
			$reply['has_child'] = (int) $reply['has_child'];
			
			if($reply['has_child'] == 1)
			{
				self::deleteReplies($commentID, $reply['id']);
			}
			else
			{
				// Delete any "hanging children" (children with no further children)
				Database::query("DELETE FROM comments_threads WHERE thread_id=? AND id=? LIMIT 1", array($commentID, $reply['id']));
				Database::query("DELETE FROM comments WHERE id=? LIMIT 1", array($reply['id']));
			}
		}
		
		// Delete original
		Database::query("DELETE FROM comments_threads WHERE thread_id=? AND id=? LIMIT 1", array($threadID, $commentID));
		Database::query("DELETE FROM comments WHERE id=? LIMIT 1", array($commentID));
		
		return Database::endTransaction();
	}
	
	
/****** Delete all replies to a comment (recursive) ******/
	public static function deleteReplies
	(
		int $parentID				// <int> The ID of the parent comment.
	,	int $commentID				// <int> The ID of the comment to delete the replies of.
	): bool							// RETURNS <bool> TRUE if successful, FALSE otherwise.
	
	// AppComment::deleteReplies($parentID, $commentID, [$deleteVotes]);
	{
		Database::startTransaction();
		
		$getReplies = Database::selectMultiple("SELECT c.id, c.has_child FROM comments_replies cr INNER JOIN comments c ON c.id=cr.id WHERE cr.parent_id=?", array($commentID));
		
		foreach($getReplies as $reply)
		{
			// Recognize Integers
			$reply['id'] = (int) $reply['id'];
			$reply['has_child'] = (int) $reply['has_child'];
			
			if($reply['has_child'] == 1)
			{
				self::deleteReplies($commentID, $reply['id']);
			}
			else
			{
				// Delete any "hanging children" (children with no further children)
				Database::query("DELETE FROM comments_replies WHERE parent_id=? AND id=? LIMIT 1", array($commentID, $reply['id']));
				Database::query("DELETE FROM comments WHERE id=? LIMIT 1", array($reply['id']));
			}
		}
		
		// Delete original
		Database::query("DELETE FROM comments_replies WHERE parent_id=? AND id=? LIMIT 1", array($parentID, $commentID));
		Database::query("DELETE FROM comments WHERE id=? LIMIT 1", array($commentID));
		
		return Database::endTransaction();
	}
	
	
/****** Get a list of User Votes (for comments) ******/
	public static function getVoteList
	(
		int $uniID			// <int> The UniID of the user.
	,	int $threadID		// <int> The thread ID to search through.
	): array <int, int>					// RETURNS <int:int> a list of your votes, FALSE on failure.
	
	// $voteList = AppComment::getVoteList($uniID, $threadID);
	{
		$voteList = array();
		$votes = Database::selectMultiple("SELECT comment_id, vote FROM votes_comments WHERE uni_id=? AND thread_id=?", array($uniID, $threadID));
		
		foreach($votes as $vote)
		{
			$voteList[(int) $vote['comment_id']] = (int) $vote['vote'];
		}
		
		return $voteList;
	}
	
	
/****** Get a User's vote on a comment ******/
	public static function getUserVote
	(
		int $uniID			// <int> The UniID of the user.
	,	int $threadID		// <int> The thread ID the comment is in.
	,	int $commentID		// <int> The comment ID to check the vote of.
	): int					// RETURNS <int> The vote value.
	
	// $vote = AppComment::getUserVote($uniID, $threadID, $commentID);
	{
		return (int) Database::selectValue("SELECT vote FROM votes_comments WHERE uni_id=? AND thread_id=? AND comment_id=? LIMIT 1", array($uniID, $threadID, $commentID));
	}
	
	
/****** Vote on a Comment ******/
	public static function vote
	(
		int $uniID			// <int> The UniID voting on the thread.
	,	int $threadID		// <int> The thread ID the comment is in.
	,	int $commentID		// <int> The ID of the comment to vote on.
	,	int $vote = 1		// <int> 1 if voted up, -1 if voted down.
	): bool					// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppComment::vote($uniID, $threadID, $commentID, $vote);
	{
		// Get the Thread Post Time
		$datePosted = (int) Database::selectValue("SELECT date_created FROM threads WHERE id=? LIMIT 1", array($threadID));
		
		// Users are not allowed to vote in threads that are older than three weeks
		if(!$datePosted or $datePosted < time() - (60 * 60 * 24 * 21))
		{
			return false;
		}
		
		Database::startTransaction();
		
		// Check if the user has already voted
		if(!$userVote = self::getUserVote($uniID, $threadID, $commentID))
		{
			// Add the New Vote
			if($pass = Database::query("INSERT INTO votes_comments (uni_id, thread_id, comment_id, vote) VALUES (?, ?, ?, ?)", array($uniID, $threadID, $commentID, $vote)))
			{
				$pass = Database::query("UPDATE comments SET " . ($vote == 1 ? 'vote_up=vote_up+1' : 'vote_down=vote_down+1') . " WHERE id=? LIMIT 1", array($commentID));
			}
		}
		
		// Remove the existing vote (if vote was identical)
		else if($vote == $userVote)
		{
			if($pass = Database::query("DELETE FROM votes_comments WHERE uni_id=? AND thread_id=? AND comment_id=? LIMIT 1", array($uniID, $threadID, $commentID)))
			{
				$pass = Database::query("UPDATE comments SET " . ($vote == 1 ? 'vote_up=vote_up-1' : 'vote_down=vote_down-1') . " WHERE id=? LIMIT 1", array($commentID));
			}
		}
		
		// Switch the Vote
		else
		{
			if($pass = Database::query("UPDATE votes_comments SET vote=? WHERE uni_id=? AND thread_id=? AND comment_id=? LIMIT 1", array($vote, $uniID, $threadID, $commentID)))
			{
				$pass = Database::query("UPDATE comments SET " . ($vote == 1 ? 'vote_up=vote_up+1, vote_down=vote_down-1' : 'vote_down=vote_down+1, vote_up=vote_up-1') . " WHERE id=? LIMIT 1", array($commentID));
			}
		}
		
		return Database::endTransaction($pass);
	}
	
	
/****** Update Priority Comments ******/
	public static function runPriority
	(
		int $threadID	// <int> The thread to run comment prioritization on.
	): bool				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppComment::runPriority($threadID);
	{
		Database::startTransaction();
		
		$commentFetch = Database::selectMultiple("SELECT id FROM comments_threads WHERE thread_id=?", array($threadID));
		
		// Reload priorities based on ratings in new
		foreach($commentFetch as $com)
		{
			$com['id'] = (int) $com['id'];
			
			if(!$commentData = Database::selectOne("SELECT has_child, vote_up, vote_down, date_posted FROM comments WHERE id=? LIMIT 1", array($com['id'])))
			{
				continue;
			}
			
			$rating = Ranking::fast((int) $commentData['vote_up'], (int) $commentData['vote_down'], 0, time() - (int) $commentData['date_posted']);
			
			Database::query("UPDATE comments_threads SET rating=? WHERE thread_id=? AND id=? LIMIT 1", array($rating, $threadID, $com['id']));
			
			// If there are any children of this comment, run prioritization on them as well
			if((int) $commentData['has_child'])
			{
				self::runReplyPriority($com['id']);
			}
		}
		
		return Database::endTransaction();
	}
	
	
/****** Update Reply Priority Comments (recursively, where applicable) ******/
	public static function runReplyPriority
	(
		int $parentID	// <int> The comment ID to run comment prioritization on.
	): bool				// RETURNS <bool> TRUE on success, FALSE on failure.
	
	// AppComment::runReplyPriority($parentID);
	{
		Database::startTransaction();
		
		$commentFetch = Database::selectMultiple("SELECT id FROM comments_replies WHERE parent_id=?", array($parentID));
		
		// Reload priorities based on ratings in new
		foreach($commentFetch as $com)
		{
			$com['id'] = (int) $com['id'];
			
			if(!$commentData = Database::selectOne("SELECT has_child, vote_up, vote_down, date_posted FROM comments WHERE id=? LIMIT 1", array($com['id'])))
			{
				continue;
			}
			
			$rating = Ranking::fast((int) $commentData['vote_up'], (int) $commentData['vote_down'], 0, time() - (int) $commentData['date_posted']);
			
			Database::query("UPDATE comments_replies SET rating=? WHERE parent_id=? AND id=? LIMIT 1", array($rating, $parentID, $com['id']));
			
			// If there are any children of this comment, run prioritization on them as well
			if((int) $commentData['has_child'])
			{
				self::runReplyPriority($com['id']);
			}
		}
		
		return Database::endTransaction();
	}
	
	
/****** Display Comments ******/
	public static function display
	(
		array $threadData		// <array> The data of the thread.
	,	array $comments		// <array> The comments to display.
	): void					// RETURNS <void> OUTPUTS HTML of comments
	
	// AppComment::display($threadData, $comments);
	{
		foreach($comments as $comment)
		{
			// Prepare Values
			$cID = (string) $comment['id'];
			$comment['date_posted'] = (int) $comment['date_posted'];
			
			echo '
			<div id="comment-' . $cID . '" class="comment-wrap">
			<div class="vote-box">
				<a id="upVote-' . $cID . '" href="javascript:voteCom(' . $cID . ', 1);" class="voteUp"><span class="icon-arrow-up"></span></a><br />
				<a id="downVote-' . $cID . '" href="javascript:voteCom(' . $cID . ', -1);" class="voteDown"><span class="icon-arrow-down"></span></a>
			</div>
			<div class="reply-box">
				<p>' . Comment::showSyntax($comment['comment']) . '</p>
				<div class="comment-options">
					<a href="/' . $comment['handle'] . '">' . $comment['display_name'] . '</a>
					&bull; ' . Time::fuzzy($comment['date_posted']) . '
					&bull; <a href="/user-panel/reports/comment?thread=' . $threadData['id'] . '&id=' . $cID . '">Report</a>
					&bull; <a href="javascript:hitReply(' . $cID . ')">Reply</a>';
					
					if($comment['has_child'])
					{
						echo '
						&bull; <a id="min-' . $cID . '" href="javascript:minimize(' . $cID . ')">Minimize</a>';
					}
					
					// Moderators
					if(Me::$clearance >= 6)
					{
						echo '
						&bull; <a id="min-' . $cID . '" href="/thread?id=' . $threadData['id'] . '&action=deleteComment&commentID=' . $cID . '&' . Link::prepare("sub-mod-comDel") . '">Delete</a>';
					}
					
					echo '
				</div>
			</div>';
			
			echo '
			<div id="comment-' . $cID . '-form"></div>
				<div id="reply-box-' . $cID . '">';
			
			if($comment['has_child'] > 0)
			{
				if($cont = self::displayRecursive($threadData, (int) $comment['id'], 1))
				{
					echo '<div id="reply-more-' . $cID . '" style="margin-left:30px;"><a href="javascript:showMore(' . $cID . ', 1)">Show More...</a></div>';
				}
			}
			
			echo '
				</div>
			</div>';
		}
	}
	
	
/****** Display Comments (Recursive Loop) ******/
	public static function displayRecursive
	(
		array $threadData	// <array> The data of the thread.
	,	int $commentID	// <int> The comment ID to recursively loop through.
	,	int $step		// <int> The current step that you're looping through.
	,	int $extra = 0	// <int> The amount of extra steps to falsify (for ajax purposes).
	): bool				// RETURNS <bool>
	
	// echo AppComment::displayRecursive($threadData, $commentID, $step, $extra = 0);
	{
		$continue = false;
		$stepComments = self::getReplies($commentID, 0, max(1, self::$stepsAllowed + 1 - $step + $extra), "DESC");
		
		if(count($stepComments) >= self::$stepsAllowed + 1 - $step + $extra)
		{
			array_pop($stepComments);
			
			$continue = true;
		}
		
		foreach($stepComments as $comment)
		{
			// Prepare Values
			$cID = $comment['id'];
			
			echo '
			<div id="comment-' . $cID . '" class="comment-wrap comment-reply">
			<div class="vote-box">
				<a id="upVote-' . $cID . '" href="javascript:voteCom(' . $cID . ', 1);" class="voteUp"><span class="icon-arrow-up"></span></a><br />
				<a id="downVote-' . $cID . '" href="javascript:voteCom(' . $cID . ', -1);" class="voteDown"><span class="icon-arrow-down"></span></a>
			</div>
			<div class="reply-box">
				<p>' . Comment::showSyntax($comment['comment']) . '</p>
				<div class="comment-options">
					<a href="/' . $comment['handle'] . '">' . $comment['display_name'] . '</a>
					&bull; ' . Time::fuzzy((int) $comment['date_posted']) . '
					&bull; <a href="/user-panel/reports/comment?thread=' . $threadData['id'] . '&id=' . $cID . '">Report</a>
					' . (self::$stepsAllowed > $step + 1 + $extra ? '&bull; <a href="javascript:hitReply(' . $cID . ')">Reply</a>' : '');
					
					if($comment['has_child'])
					{
						echo '
						&bull; <a id="min-' . $cID . '" href="javascript:minimize(' . $cID . ')">Minimize</a>';
					}
					
					// Moderators
					if(Me::$clearance >= 6)
					{
						echo '
						&bull; <a id="min-' . $cID . '" href="/thread?id=' . $threadData['id'] . '&action=deleteComment&parentID=' . $commentID . '&commentID=' . $cID . '&' . Link::prepare("sub-mod-comDel") . '">Delete</a>';
					}
					
					echo '
				</div>
			</div>';
			
			echo '
			<div id="comment-' . $cID . '-form"></div>';
			
			echo '
			<div id="reply-box-' . $cID . '">';
			
			if($step < self::$stepsAllowed && $comment['has_child'] > 0)
			{
				if($cont = self::displayRecursive($threadData, (int) $comment['id'], $step + 1))
				{
					echo '<div style="margin-left:30px; margin-top:10px;"><a href="javascript:showMore(' . $cID . ', ' . $step . ')">Show More...</a></div>';
				}
			}
			
			echo '
			</div>';
			
			echo '
			</div>';
		}
		
		return $continue;
	}
}