<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppComment_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppComment";
	public $title = "Microfaction Comment Handler";
	public $version = 0.5;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Handles comments on microfaction sites.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `comments`
		(
			`id`					int(10)			unsigned	NOT NULL	AUTO_INCREMENT,
			
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`has_child`				tinyint(1)		unsigned	NOT NULL	DEFAULT '0',
			`comment`				text						NOT NULL	DEFAULT '',
			
			`vote_up`				smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			`vote_down`				smallint(5)		unsigned	NOT NULL	DEFAULT '0',
			
			`date_posted`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(id) PARTITIONS 31;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `comments_threads`
		(
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`id`					int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`rating`				float(10,4)					NOT NULL	DEFAULT '0.0000',
			
			INDEX (`thread_id`, `rating`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(thread_id) PARTITIONS 31;
		");
		
		Database::exec("
		CREATE TABLE IF NOT EXISTS `comments_replies`
		(
			`parent_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`id`					int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`rating`				float(10,4)					NOT NULL	DEFAULT '0.0000',
			
			INDEX (`parent_id`, `rating`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(parent_id) PARTITIONS 31;
		");
		
		// Also need to track user's votes (at least until the thread ID is stale)
		Database::exec("
		CREATE TABLE IF NOT EXISTS `votes_comments`
		(
			`uni_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`thread_id`				int(10)			unsigned	NOT NULL	DEFAULT '0',
			`comment_id`			int(10)			unsigned	NOT NULL	DEFAULT '0',
			
			`vote`					tinyint(1)					NOT NULL	DEFAULT '0',
			
			UNIQUE (`uni_id`, `thread_id`, `comment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY KEY(uni_id) PARTITIONS 31;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		$pass = DatabaseAdmin::columnsExist("comments", array("id", "uni_id", "date_posted"));
		$pass2 = DatabaseAdmin::columnsExist("comments_threads", array("thread_id", "id"));
		$pass3 = DatabaseAdmin::columnsExist("comments_replies", array("parent_id", "id"));
		$pass4 = DatabaseAdmin::columnsExist("votes_comments", array("uni_id", "thread_id"));
		
		return ($pass and $pass2 and $pass3 and $pass4);
	}
	
}