<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Cuteness", 9);
AppHashtags::create("CuteAnimals", 9);
AppHashtags::create("AnimalsInClothes", 9);
AppHashtags::create("FunnyImages", 9);
AppHashtags::create("FunnyVideos", 9);
AppHashtags::create("Jokes", 9);
AppHashtags::create("AdviceAnimals", 9);
AppHashtags::create("Memes", 9);
AppHashtags::create("BeautifulScenes", 9);

// Default Subscriptions, Inactive
AppHashtags::create("BeautifulMen", 8);
AppHashtags::create("BeautifulWomen", 8);
AppHashtags::create("Photoshopped", 8);

// Available Subscriptions
AppHashtags::create("CuteBabies", 6);
AppHashtags::create("WTF", 6);
AppHashtags::create("Facepalm", 6);
AppHashtags::create("Fails", 6);
AppHashtags::create("HappyNews", 6);
AppHashtags::create("PhotoshopBattles", 6);
