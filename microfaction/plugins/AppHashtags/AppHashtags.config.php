<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

class AppHashtags_config {
	
	
/****** Plugin Variables ******/
	public $pluginType = "standard";
	public $pluginName = "AppHashtags";
	public $title = "MicroFaction Hashtag Handler";
	public $version = 1.0;
	public $author = "Brint Paris";
	public $license = "UniFaction License";
	public $website = "http://unifaction.com";
	public $description = "Provides handling for hashtags on MicroFaction sites.";
	
	public $data = array();
	
	
/****** Install this plugin ******/
	public function install (
	)			// <bool> RETURNS TRUE on success, FALSE on failure.
	
	// $plugin->install();
	{
		Database::exec("
		CREATE TABLE IF NOT EXISTS `micro_hashtags`
		(
			`hashtag`				varchar(22)					NOT NULL	DEFAULT '',
			`priority`				tinyint(1)					NOT NULL	DEFAULT '0',
			
			UNIQUE (`hashtag`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		return $this->isInstalled();
	}
	
	
/****** Check if the plugin was successfully installed ******/
	public static function isInstalled (
	)			// <bool> TRUE if successfully installed, FALSE if not.
	
	// $plugin->isInstalled();
	{
		// Make sure the newly installed tables exist
		return DatabaseAdmin::columnsExist("micro_hashtags", array("hashtag", "priority"));
	}
	
}