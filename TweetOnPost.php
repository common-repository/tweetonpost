<?php

/*
Plugin Name: Tweet On Post
Plugin URI: http://danperron.com/tag/tweetonpost/
Description: Tweet when you post, and have it come from your site.
Version: 1.9
Author: Dan Perron
Author URI: http://danperron.com
License: GNU General Public License 2
*/

/*  Copyright 2010  Dan Perron  (email : danp3rr0n@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
DEFINE(THISPLUGINID, "TweetOnPost");

$tweetonpost_basename = plugin_basename(__file__);
$tweetonpost_basename = str_replace(basename(__file__), "", $tweetonpost_basename);

if (!class_exists("OAuthConsumer") && !class_exists("TwitterAPI")) {
    require_once (WP_PLUGIN_DIR . '/' . $tweetonpost_basename . '/' .
        "TwitterAPI.php");
}


if (!class_exists("TweetOnPost")) {
    class TweetOnPost
    {

        var $options = null;
        var $enabled = true;

        var $consumer_key = null;
        var $consumer_secret = null;
        var $bitly_login = null;
        var $bitly_apikey = null;
        var $tweetformat = "";
        var $keys_valid = false;
        var $error_msg = null;

        function TweetOnPost()
        {
            $this->__construct();
        }

        function __construct()
        {

            if (get_option("tweet_on_post_options")) {
                $this->options = get_option("tweet_on_post_options");
            }

            if (isset($this->options['enabled'])) {
                $this->enabled = (bool)$this->options['enabled'];
            }

            if (isset($this->options['consumer_key'])) {
                $this->consumer_key = $this->options['consumer_key'];
            }

            if (isset($this->options['consumer_secret'])) {
                $this->consumer_secret = $this->options['consumer_secret'];
            }


            //remove oauth_token and oauth_token_secret and put them in an account (convertion from 1.4 to 1.5)
            if (isset($this->options['oauth_token']) && isset($this->options['oauth_token_secret'])) {

                $newaccount['oauth_token'] = $this->options['oauth_token'];
                $newaccount['oauth_token_secret'] = $this->options['oauth_token_secret'];

                $this->options['accounts'][] = $newaccount;

                unset($this->options['oauth_token']);
                unset($this->options['oauth_token_secret']);
                $this->save_options();

            }

            if (isset($this->options['bitly_login'])) {
                $this->bitly_login = $this->options['bitly_login'];
            } else {
                $this->bitly_login = "tweetonpost";
            }

            if (isset($this->options['bitly_apikey'])) {
                $this->bitly_apikey = $this->options['bitly_apikey'];
            } else {
                $this->bitly_apikey = "R_2566ee87c6a4b99bec293a75b7940e8c";
            }

            if (isset($this->options['tweet_format'])) {
                $this->tweetformat = $this->options['tweet_format'];
            } else {
                $this->tweetformat = "[title] - [link]";
            }
            
            if(isset($this->options['enable_urlshortening'])){
            	$this->options['enable_urlshortening'] = (bool)$this->options['enable_urlshortening'];
            } else {
            	$this->options['enable_urlshortening'] = true;
            }

        }

        function save_options()
        {
            update_option("tweet_on_post_options", $this->options);
        }

        function curl_installed()
        {
            if (in_array('curl', get_loaded_extensions())) {
                return true;
            } else {
                return false;
            }
        }

        function add_admin_page()
        {
            add_options_page('Tweet On Post', 'TweetOnPost', 10, THISPLUGINID, array($this,
                'make_admin_page'));
        }

        function make_admin_page()
        {
            include ("settings.inc");
        }

        function bitly_shorten($long_url)
        {
            $api_url = "http://api.bit.ly/v3/shorten?";

            $api_url .= "login=" . $this->bitly_login;
            $api_url .= "&apiKey=" . $this->bitly_apikey;
            $api_url .= "&longUrl=" . $long_url;
            $api_url .= "&format=json";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            //echo $response;

            curl_close($ch);

            $jsonResponse = json_decode($response, true);
            return $jsonResponse['data']['url'];

        }

        function on_init()
        {

            if (isset($_POST['tweetonpost_remove_account'])) {
                $removeAccountIndex = $_POST['remove_account'];

                if (isset($this->options['accounts'][$removeAccountIndex])) {
                    unset($this->options['accounts'][$removeAccountIndex]);
                    $this->save_options();
                }

            }


            if (isset($this->options['waiting_for_auth']) && isset($_GET['oauth_token'])) {

                unset($this->options['waiting_for_auth']);

                $newtoken = trim($_GET['oauth_token']);

                $twitterapi = new TwitterAPI();
                $twitterapi->consumer_key = $this->consumer_key;
                $twitterapi->consumer_secret = $this->consumer_secret;
                $twitterapi->oauth_token = $newtoken;
                $twitterapi->oauth_token_secret = $this->options['current_oauth_token_secret'];

                try {
                    //Get an Access Token and save it
                    $twitterapi->get_access_token();
                    //echo "got access token!: " . $twitterapi->oauth_token;
                    //$this->options['oauth_token'] = $twitterapi->oauth_token;


                    $this->oauth_token = $twitterapi->oauth_token;

                    $newaccount['oauth_token'] = $twitterapi->oauth_token;
                    $newaccount['oauth_token_secret'] = $twitterapi->oauth_token_secret;

                    try {
                        $userinfoArray = $twitterapi->get_user();
                        $newaccount['screen_name'] = $userinfoArray['screen_name'];
                        $newaccount['name'] = $userinfoArray['name'];
                    }
                    catch (exception $e) {
                        $error_msg = $e->getMessage();
                    }

                    $this->options['accounts'][] = $newaccount;


                    //Set Authenticated to true
                    $this->options['authenticated'] = true;
                    $this->authenticated = true;

                    $this->save_options();

                }
                catch (exception $e) {
                    $this->error_msg = "Could not get access token.";
                }
                unset($this->options['current_oauth_token']);
                unset($this->options['current_oauth_token_secret']);

                $this->save_options();


            }

            if (isset($_POST["tweetonpost_auth_formsent"])) {


                $twitterapi = new TwitterAPI();
                $twitterapi->consumer_key = $this->options['consumer_key'];
                $twitterapi->consumer_secret = $this->options['consumer_secret'];

                try {
                    //Try to get a request token
                    $twitterapi->get_request_token();

                    //Save the request token and secret
                    $this->options['current_oauth_token'] = $twitterapi->oauth_token;
                    $this->options['current_oauth_token_secret'] = $twitterapi->oauth_token_secret;
                    $this->options['waiting_for_auth'] = true;
                    $this->save_options();

                    //Go to authorization URL
                    $newurl = $twitterapi->get_authorize_url();
                    wp_redirect($newurl);

                }
                catch (exception $e) {
                    $this->error_msg = "Could not gain request token.";
                }

            }
        }


        function on_publish_post($post_id)
        {
			
            //Exit if no Twitter Accounts are stored
            if (!isset($this->options['accounts']) || count($this->options['accounts']) <= 0)
                return;

            //check if POST vars are available, if they are, check if the post is new
            if (isset($_POST['original_post_status']) && $_POST['original_post_status'] ==
                'publish') {
                //doing an update, don't tweet
                return;

            }
            
            //check post metadata to see if post was tweeted
            if(get_post_meta($post_id, '_tweetonposttweeted', true) == 'true')
            {
            	return;
            }
            
            //if we can't see POST vars, tweet as long as the post is published

            $postinfo = get_post($post_id);

            $postdatetime = $postinfo->post_date;

            if ($postinfo->post_status == 'publish' || $postinfo->post_status == 'future') {

                $post_title = $postinfo->post_title;
                
                if($this->options['enable_urlshortening']){
                	$post_link = $this->bitly_shorten(get_permalink($post_id));
                } else {
                	$post_link = get_permalink($post_id);
                }
				
				$post_tags = get_the_tags($post_id);
                $post_categories = get_the_category($post_id);

                $tweet = $this->tweetformat;
                //$tweet = str_replace("[title]", $post_title, $tweet);
                $tweet = str_replace("[link]", $post_link, $tweet);

                if ($post_tags) {
                    $counter = 1;
                    foreach ($post_tags as $tag) {
                        $replace_str = "[tag" . $counter . "]";
                        $tweet = str_replace($replace_str, "#" . $tag->name, $tweet);
                        $counter++;
                    }
                }

                if ($post_categories) {
                    $counter = 1;
                    foreach ($post_categories as $category) {
                        $replace_str = "[category" . $counter . "]";
                        $tweet = str_replace($replace_str, "#" . $category->cat_name, $tweet);
                        $counter++;
                    }
                }

                //remove unused [tag#] and [category#] placeholders
                $tweet = preg_replace("/\[tag[0-9]+\]/", "", $tweet);
                $tweet = preg_replace("/\[category[0-9]+\]/", "", $tweet);


                //Shorten the title to make the tweet fit 140 chars

                $test_tweet = str_replace("[title]", $post_title, $tweet);
                if (strlen($test_tweet) > 140) {
                    $overage = strlen($test_tweet) - 140;
                    $originallength = strlen($post_title);
                    $newlength = $originallength - $overage - 3;
                    $new_title = substr($post_title, 0, $newlength) . "...";
                    $tweet = str_replace("[title]", $new_title, $tweet);
                } else {
                    $tweet = $test_tweet;
                }


                $twitterapi = new TwitterAPI();
                $twitterapi->consumer_key = $this->consumer_key;
                $twitterapi->consumer_secret = $this->consumer_secret;


                //tweet from all stored accounts
                foreach ($this->options['accounts'] as $account) {

                    $twitterapi->set_token_pair($account['oauth_token'], $account['oauth_token_secret']);

                    try {
                        $twitterapi->update_status($tweet);
                        update_post_meta($post_id,'_tweetonposttweeted','true');
                    }
                    catch (exception $e) {
                        echo $e->getMessage();
                    }
                }


            }

        }

    }

}


if (class_exists("TweetOnPost")) {
    $tweetonpost = new TweetOnPost();
}

if (isset($tweetonpost)) {

    add_action('init', array(&$tweetonpost, 'on_init'));
    add_action('admin_menu', array(&$tweetonpost, 'add_admin_page'));
    if ($tweetonpost->enabled) {
        add_action('publish_post', array(&$tweetonpost, 'on_publish_post'));
    }
    register_deactivation_hook(__file__, 'tweetonpost_deactivate');

}

function tweetonpost_deactivate()
{
    delete_option("tweet_on_post_options");
}

?>