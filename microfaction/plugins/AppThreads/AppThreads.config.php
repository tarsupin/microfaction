<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppThreads_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppThreads";
	public $title = "Microfaction Thread Handler";
	public $version = 0.5;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Handles threads, votes, and subscriptions on microfaction sites.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		// Thread List
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`title`					varchar(72)					NOT NULL	DEFAULT '',
			`photo`					varchar(148)				NOT NULL	DEFAULT '',
			`url`					varchar(148)				NOT NULL	DEFAULT '',
			
			`rating`				float(6,4)					NOT NULL	DEFAULT '0.0000',
			
			`clicks`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`actions`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			`vote_up`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			`vote_down`				mediumint(6)	unsigned	NOT NULL	DEFAULT '0',
			
			`comments`				smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			
			`date_created`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(id) PARTITIONS 13;
		");
		
		// New Threads (save last week of new entries, or 2500, whichever is higher)
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads_new`
		(
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`date_created`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`hashtag`, `thread_id`, `date_created`),
			UNIQUE (`thread_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		// Save 2500 threads for the priority list
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads_priority`
		(
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`rating`				float(6,4)					NOT NULL	DEFAULT '0.0000',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`thread_id`),
			INDEX (`hashtag`, `rating`, `thread_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		// Best Threads (of all time)
		// Save Top 5000
		Database::exec("
		CREATE TABLE IF NOT EXISTS `threads_best`
		(
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`rating`				float(6,4)					NOT NULL	DEFAULT '0.0000',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (`thread_id`),
			INDEX (`hashtag`, `rating`, `thread_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		// Also need to track user's votes (at least until the thread ID is stale)
		Database::exec("
		CREATE TABLE IF NOT EXISTS `votes_thread`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`vote`					tinyint(1)					NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `thread_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 31;
		");
		
		// User's Subscription to Categories
		Database::exec("
		CREATE TABLE IF NOT EXISTS `user_category_subs`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`active`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			
			UNIQUE (uni_id, hashtag, active)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 23;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass = DatabaseAdmin::columnsExist("threads", array("id", "uni_id", "date_created"));
		$pass2 = DatabaseAdmin::columnsExist("threads_new", array("hashtag", "thread_id"));
		$pass3 = DatabaseAdmin::columnsExist("threads_priority", array("hashtag", "thread_id"));
		$pass4 = DatabaseAdmin::columnsExist("threads_best", array("hashtag", "thread_id"));
		$pass5 = DatabaseAdmin::columnsExist("votes_thread", array("uni_id", "thread_id"));
		$pass6 = DatabaseAdmin::columnsExist("user_category_subs", array("uni_id", "hashtag"));
		
		return ($pass and $pass2 and $pass3 and $pass4 and $pass5 and $pass6);
	}
	
}