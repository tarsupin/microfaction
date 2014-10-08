<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Beauty", 9);
AppHashtags::create("Clothing", 9);
AppHashtags::create("Cosmetics", 9);
AppHashtags::create("Decor", 9);
AppHashtags::create("DIY", 9);
AppHashtags::create("Furniture", 9);
AppHashtags::create("Gadgets", 9);
AppHashtags::create("Health", 9);
AppHashtags::create("InteriorDesign", 9);
AppHashtags::create("Recipes", 9);
AppHashtags::create("WallArt", 9);

// Default Subscriptions, Inactive
AppHashtags::create("Fitness", 8);
AppHashtags::create("Travel", 8);

// Available Subscriptions
AppHashtags::create("Appliances", 6);
AppHashtags::create("Architecture", 6);
AppHashtags::create("BodyCare", 6);
AppHashtags::create("FemaleFashion", 6);
AppHashtags::create("Fragrance", 6);
AppHashtags::create("Landscaping", 6);
AppHashtags::create("Lighting", 6);
AppHashtags::create("MaleFashion", 6);
AppHashtags::create("Meditation", 6);
AppHashtags::create("Sex", 6);
AppHashtags::create("SkinCare", 6);
AppHashtags::create("Yoga", 6);
AppHashtags::create("Vegan", 6);
AppHashtags::create("Vegetarian", 6);
AppHashtags::create("PaleoDiet", 6);
AppHashtags::create("GlutenFree", 6);
