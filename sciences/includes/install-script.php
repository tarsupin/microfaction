<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Astronomy", 9);
AppHashtags::create("Biology", 9);
AppHashtags::create("Chemistry", 9);
AppHashtags::create("Engineering", 9);
AppHashtags::create("Green", 9);
AppHashtags::create("History", 9);
AppHashtags::create("Law", 9);
AppHashtags::create("Linguistics", 9);
AppHashtags::create("Medicine", 9);
AppHashtags::create("Philosophy", 9);
AppHashtags::create("Physics", 9);
AppHashtags::create("Psychology", 9);

// Default Subscriptions, Inactive
#AppHashtags::create("Example", 8);

// Available Subscriptions
AppHashtags::create("Theology", 6);
AppHashtags::create("Neuroscience", 6);
AppHashtags::create("Agriculture", 6);
AppHashtags::create("Math", 6);
AppHashtags::create("Sociology", 6);
AppHashtags::create("Economics", 6);
AppHashtags::create("Geology", 6);
AppHashtags::create("Ecology", 6);
AppHashtags::create("Archaeology", 6);
AppHashtags::create("Parapsychology", 6);
