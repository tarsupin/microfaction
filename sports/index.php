<?php

/****** Preparation ******/
define("CONF_PATH",		dirname(__FILE__));
define("SYS_PATH", 		dirname(dirname(CONF_PATH)) . "/system");

// Load phpTesla
require(SYS_PATH . "/phpTesla.php");
require(APP_PATH . "/index.php");