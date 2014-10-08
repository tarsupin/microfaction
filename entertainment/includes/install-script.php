<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Ask", 9);
AppHashtags::create("Interviews", 9);
AppHashtags::create("Apps", 9);
AppHashtags::create("Books", 9);
AppHashtags::create("Movies", 9);
AppHashtags::create("Pictures", 9);
AppHashtags::create("Music", 9);
AppHashtags::create("Shows", 9);
AppHashtags::create("VideoGames", 9);
AppHashtags::create("TabletopGames", 9);
AppHashtags::create("Videos", 9);

// Default Subscriptions, Inactive
AppHashtags::create("Anime", 8);
AppHashtags::create("Cosplay", 8);
AppHashtags::create("Roleplaying", 8);

// Available Subscriptions
AppHashtags::create("ConsoleGaming", 6);
AppHashtags::create("PCGaming", 6);
AppHashtags::create("MobileGaming", 6);
AppHashtags::create("BoardGames", 6);
AppHashtags::create("FanTheories", 6);
AppHashtags::create("FanFiction", 6);
AppHashtags::create("ClassicalMusic", 6);
AppHashtags::create("IndieGaming", 6);
AppHashtags::create("CardGames", 6);
AppHashtags::create("WebGames", 6);
AppHashtags::create("Playstation", 6);
AppHashtags::create("Wii", 6);
AppHashtags::create("Xbox", 6);

AppHashtags::create("PopMusic", 6);
AppHashtags::create("CountryMusic", 6);
AppHashtags::create("RapMusic", 6);
AppHashtags::create("ChristianMusic", 6);
AppHashtags::create("HipHopMusic", 6);
AppHashtags::create("RBMusic", 6);
AppHashtags::create("ReggaeMusic", 6);
AppHashtags::create("RockNRollMusic", 6);