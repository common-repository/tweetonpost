<?php
/**
 * @author Daniel Perron
 * @copyright 2010
 */

class OAuthConsumer
{

    var $oauth_token = null;
    var $oauth_token_secret = null;
    var $request_url = null; //the url of the current request
    var $signature = null;
    var $sig_string;
    var $signature_method = "HMAC-SHA1";
    var $nonce = null;
    var $timestamp;
    var $http_method = "POST";
    var $oauth_version = "1.0";
    var $response;
    var $response_code;
    var $curl_info;

    //Service related variables
    var $consumer_key = null;
    var $consumer_secret = null;

    var $request_token_url = ""; //URL provided by service for obtaining a request token
    var $access_token_url = ""; //URL provided by service for obtaining an access token
    var $authorize_url = ""; //URL for Authorizing a request token in exchange for an access token
    var $callback_url = ""; //URL to return to after authorization

	function OAuthConsumer($requestTokenUrl,$accessTokenUrl,$authorizeUrl,$callbackUrl)
	{
		$this->request_token_url = $requestTokenUrl;
		$this->access_token_url = $accessTokenUrl;
		$this->authorize_url = $authorizeUrl;
		$this->callback_url = $callbackUrl;
		
		if(!function_exists("curl_init"))
		{
			throw new Exception("No Curl");
		}
	}

    //abstract function save_token();
    //abstract function load_token();

	function set_token_pair($token, $secret)
	{
		$this->oauth_token = $token;
		$this->oauth_token_secret = $secret;
	}

    function get_access_token($customParams = null)
    {
        $this->timestamp = time();
        $this->nonce = $this->generate_nonce(16);
        $this->request_url = $this->access_token_url;

        $this->signature = base64_encode(hash_hmac('sha1', $this->signature_base_string
            ($customParams), $this->oauth_urlencode($this->consumer_secret) . "&" . $this->
            oauth_urlencode($this->oauth_token_secret), true));

        $postArray['oauth_consumer_key'] = $this->consumer_key;
        $postArray['oauth_token'] = $this->oauth_token;
        $postArray['oauth_signature_method'] = $this->signature_method;
        $postArray['oauth_signature'] = $this->signature;
        $postArray['oauth_timestamp'] = $this->timestamp;
        $postArray['oauth_nonce'] = $this->nonce;
        $postArray['oauth_version'] = $this->oauth_version;

        if (is_array($customParams)) {
            foreach ($customParams as $key => $value) {
                $postArray[$key] = $value;
            }
        }

        if (!$response = $this->process($postArray))
            return false;


        $valueSets = explode("&", $response);
        foreach ($valueSets as $set) {
            $pair = explode("=", $set);

            if ($pair[0] == "oauth_token")
                $this->oauth_token = rawurldecode($pair[1]);

            if ($pair[0] == "oauth_token_secret")
                $this->oauth_token_secret = rawurldecode($pair[1]);
        }
        return true;

    }


    function request($resource_url, $customPostFields = null)
    {
        $this->timestamp = time();
        $this->nonce = $this->generate_nonce(16);
        $this->request_url = $resource_url;
        $this->signature = base64_encode(hash_hmac('sha1', $this->signature_base_string
            ($customPostFields), $this->oauth_urlencode($this->consumer_secret) . "&" . $this->
            oauth_urlencode($this->oauth_token_secret), true));

        $postArray['oauth_consumer_key'] = $this->consumer_key;
        $postArray['oauth_token'] = $this->oauth_token;
        $postArray['oauth_signature_method'] = $this->signature_method;
        $postArray['oauth_signature'] = $this->signature;
        $postArray['oauth_timestamp'] = $this->timestamp;
        $postArray['oauth_nonce'] = $this->nonce;
        $postArray['oauth_version'] = $this->oauth_version;


        if (is_array($customPostFields)) {
            foreach ($customPostFields as $key => $value) {
                $postArray[$key] = $value;
            }
        }

        if ($response = $this->process($postArray))
            return $response;
        else
            return false;


    }

    function get_request_token($customArray = null)
    {

        $this->timestamp = time();
        $this->nonce = $this->generate_nonce(16);
        $this->request_url = $this->request_token_url;

        $this->signature = base64_encode(hash_hmac('sha1', $this->signature_base_string
            ($customArray), $this->oauth_urlencode($this->consumer_secret) . "&", true));


        $postArray['oauth_consumer_key'] = $this->consumer_key;
        $postArray['oauth_signature_method'] = $this->signature_method;
        $postArray['oauth_signature'] = $this->signature;
        $postArray['oauth_timestamp'] = $this->timestamp;
        $postArray['oauth_nonce'] = $this->nonce;
        $postArray['oauth_version'] = $this->oauth_version;

        if (is_array($customArray)) {
            foreach ($customArray as $key => $value) {
                $postArray[$key] = $value;
            }
        }


        if (!$this->process($postArray))
            return false;


        $valueSets = explode("&", $this->response);
        foreach ($valueSets as $set) {
            $pair = explode("=", $set);

            if ($pair[0] == "oauth_token")
                $this->oauth_token = rawurldecode($pair[1]);

            if ($pair[0] == "oauth_token_secret")
                $this->oauth_token_secret = rawurldecode($pair[1]);
        }
        //$this->save_token();
        return true;
    }

    private function make_post_string($postArray)
    {
        $returnString = "";
        $count = 0;
        foreach ($postArray as $key => $value) {
            $rkey = urlencode($key);
            $rvalue = urlencode($value);
            $returnString .= "$rkey=$rvalue";
            $count++;
            if ($count < count($postArray)) {
                $returnString .= '&';
            }
        }
        return $returnString;
    }


    private function signature_base_string($custom = null)
    {
        $returnString = $this->http_method;
 

        $returnString .= "&" . $this->oauth_urlencode($this->request_url) . "&";


        $stringVars['oauth_consumer_key'] = $this->consumer_key;
        if ($this->oauth_token != null)
            $stringVars['oauth_token'] = $this->oauth_token;
        $stringVars['oauth_nonce'] = $this->nonce;
        $stringVars['oauth_signature_method'] = $this->signature_method;
        $stringVars['oauth_timestamp'] = $this->timestamp;
        $stringVars['oauth_version'] = "1.0";

        if (is_array($custom)) {
            foreach ($custom as $key => $val) {
                $stringVars[$key] = $val;
            }
        }

        ksort($stringVars);

        $appendString = "";
        $count = 0;

        foreach ($stringVars as $key => $value) {
            $value = $this->oauth_urlencode($value);
            $appendString .= "$key=$value";
            $count++;
            if ($count < count($stringVars))
                $appendString .= "&";
        }


        $returnString .= $this->oauth_urlencode($appendString);
        $this->sig_string = $returnString;

        return $returnString;
    }

    function get_authorize_url() //seems to be the same as construct_authorize_url?
    {
        $returnString = $this->authorize_url;
        $returnString .= "?oauth_token=" . $this->oauth_urlencode($this->oauth_token);

        if ($this->callback_url != null) {
            $returnString .= "&oauth_callback=" . $this->callback_url;
        }

        return $returnString;
    }


    private function generate_nonce($length = 16)
    {
        $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $returnString = "";

        for ($i = 0; $i < $length; $i++) {
            $returnString .= substr($characters, rand(0, strlen($characters)), 1);
        }

        return $returnString;
    }

    private function oauth_urlencode($string)
    {
        $returnString = str_replace("%7E", "~", rawurlencode($string));
        $returnString = str_replace("+", " ", rawurlencode($string));
        return $returnString;
    }

    private function oauth_urldecode($string)
    {
        $returnString = str_replace("~", "%7E", rawurldecode($string));
        $returnString = str_replace(" ", "+", rawurlencode($string));
        return $returnString;
    }


    private function process($paramArray)
    {
        $ch = curl_init();

        if ($this->http_method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->make_post_string($paramArray));

        } elseif ($this->http_method == "GET") {
            $this->request_url .= '?' . $this->make_post_string($paramArray);
        }
        curl_setopt($ch, CURLOPT_URL, $this->request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $this->response = curl_exec($ch);


        $this->curl_info = curl_getinfo($ch);
        curl_close($ch);

        //check the http_response code to make sure everything went ok, return false if it didn't.
        if ($this->curl_info['http_code'] != 200) {
            $this->response_code = $this->curl_info['http_code'];
            throw new Exception("OAuth Error:" . $this->response_code);
            return false;
        } else {
            return $this->response;
        }


    }


}





?>