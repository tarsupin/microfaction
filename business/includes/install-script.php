<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Create the hashtag list for this MicroFaction

// Default Subscriptions, Active
AppHashtags::create("Ask", 9);
AppHashtags::create("Interviews", 9);
AppHashtags::create("Finance", 9);
AppHashtags::create("BusinessNews", 9);
AppHashtags::create("GoodDeals", 9);
AppHashtags::create("GoodCompanies", 9);
AppHashtags::create("Investing", 9);
AppHashtags::create("JobAdvice", 9);
AppHashtags::create("Startups", 9);
AppHashtags::create("Budgeting", 9);
AppHashtags::create("BizReviews", 9);
AppHashtags::create("ProductReviews", 9);

// Default Subscriptions, Inactive
//AppHashtags::create("Example", 8);

// Available Subscriptions
AppHashtags::create("Cryptocurrencies", 6);
AppHashtags::create("Bitcoin", 6);
AppHashtags::create("Dogecoin", 6);
AppHashtags::create("Marketing", 6);
AppHashtags::create("Advertising", 6);
AppHashtags::create("Management", 6);
AppHashtags::create("Stocks", 6);
AppHashtags::create("ResumeBuilding", 6);
AppHashtags::create("LifeAtWork", 6);