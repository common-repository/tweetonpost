<?php

/**
 * @author Daniel Perron
 * @copyright 2010
 */

require_once (WP_PLUGIN_DIR . '/' . $tweetonpost_basename . '/' . 'OAuthConsumer.php');

class TwitterAPI extends OAuthConsumer
{
	var $format = "json";
	
	function TwitterAPI()
	{
		$this->__construct();
	}
	
	function get_user()
	{
		$this->http_method = "GET";
		$url = "http://api.twitter.com/1/account/verify_credentials.json";
		$json = $this->request($url);
		$userArray = json_decode($json,true);
		
		return $userArray;
		
	}
	
	function __construct()
	{
		
		$requestURL = "http://twitter.com/oauth/request_token";
		$accessURL = "http://twitter.com/oauth/access_token";
		$authorizeURL = "http://twitter.com/oauth/authorize";
		$callback = null;
		parent::OAuthConsumer($requestURL,$accessURL,$authorizeURL,$callback);
	}
	
	function valid_consumer_keypair()
	{
		//check for valid comsumer key and secret by asking for a request token and checking the http response
		try
		{
			$this->get_request_token();
			return true;
		}
		catch(Exception $e)
		{
			$this->response_code;
			return false;
		}
	}
	
	
	function update_status($status)
	{
		$this->http_method = "POST";
		$request_url = "http://api.twitter.com/1/statuses/update.xml";
		$paramArray = array("status" => $status);
		
		$this->request($request_url,$paramArray);
	}
	
	
}

?>