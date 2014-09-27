<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Load Forum-Specific Panel Links
$userPanel['Subscriptions']['Subscriptions'] = "/subscriptions";

// Reorder the Panel
$userPanel = array('Subscriptions' => $userPanel['Subscriptions']) + $userPanel;