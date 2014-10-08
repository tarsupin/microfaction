<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Art", 9);
AppHashtags::create("Audio", 9);
AppHashtags::create("Collaborate", 9);
AppHashtags::create("Crafts", 9);
AppHashtags::create("Design", 9);
AppHashtags::create("Ideas", 9);
AppHashtags::create("GraphicDesign", 9);
AppHashtags::create("Photography", 9);
AppHashtags::create("Programming", 9);
AppHashtags::create("ShowAndTell", 9);
AppHashtags::create("Video", 9);
AppHashtags::create("WebDev", 9);
AppHashtags::create("Writing", 9);

// Default Subscriptions, Inactive
//AppHashtags::create("Example", 8);

// Available Subscriptions
AppHashtags::create("WebDesign", 6);
AppHashtags::create("GameDev", 6);
AppHashtags::create("Drawing", 6);
AppHashtags::create("Painting", 6);