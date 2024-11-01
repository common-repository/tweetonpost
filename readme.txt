=== TweetOnPost ===
Contributors: danperron
Donate link: http://danperron.com/
Tags: twitter, tweet, tweetonpost, publish, tweet on post
Requires at least: 2.0.2
Tested up to: 3.0
Stable tag: 1.9

This plugin will tweet from your Twitter account when you publish a new blog post.

== Description ==
This plugin will tweet from your Twitter account when you publish a new blog post.  It will also show those tweets as being from your site in the tweet's "source".  This plugin requires you to have the php_curl library on your server and requires you to create an OAuth client on twitter.com.  

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings->TweetOnPost
1. Follow the directions to create an OAuth client on twitter.
1. Copy your consumer key and consumer secret to the form.
1. click "Save Settings"
1. Make sure you are logged in with the twitter account you want to tweet from, and click "Add Account"
1. Done.  If you want to use your own bit.ly account to track your tweet links, overwrite the default bitly credentials.

== Frequently Asked Questions ==




== Screenshots ==

1. The TweetOnPost Settings Page

== Changelog ==

= 1.0 =
* First Version

= 1.1 = 
* Minor Fixes

= 1.2 =
* Specifiy absolute paths for includes

= 1.3 =
* Shorten title if tweet goes over 140 chars

= 1.4 =
fixed xmlrpc updating

= 1.5 =
Support for multiple twitter accounts

= 1.6 =
code clean up, screenshot added

= 1.7 =
added support for tags and categories.
added enable/disable feature.

= 1.8 = 
fixed tweet on scheduled post

= 1.81 = 
adds meta-data to tweeted posts to preventing tweeting on update via xmlrpc.

= 1.9 =
allows disabling of url shortening

== Upgrade Notice ==


