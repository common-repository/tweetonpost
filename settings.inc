<?php
error_reporting(E_ALL);


if (!$this->curl_installed()) {
    $this->error_msg = "This plugin requires the php_curl library to be installed.";
}


if (isset($_POST["formatsettingsformsent"])) {
	
	if(isset($_GET['oauth_token']))
	{
		$_GET['oauth_token'] = false;
	}
	
	$this->options['consumer_key'] = trim($_POST['consumer_key']);
	$this->options['consumer_secret'] = trim($_POST['consumer_secret']);
	
    $this->tweetformat = $_POST["tweetformat"];
    $this->options['tweet_format'] = $this->tweetformat;

    $this->bitly_login = trim($_POST['bitly_login']);
    $this->options['bitly_login'] = $this->bitly_login;

    $this->bitly_apikey = trim($_POST['bitly_apikey']);
    $this->options['bitly_apikey'] = $this->bitly_apikey;
    
    if(isset($_POST["tweetonpost_enable"]))
    {
    	$this->options['enabled'] = true;
    	$this->enabled = true;
    }
    else
    {
    	$this->options['enabled'] = false;
    	$this->enabled = false;
    }
    
    if(isset($_POST['enable_urlshortening'])){
    	$this->options['enable_urlshortening'] = true;
    } else {
    	$this->options['enable_urlshortening'] = false;
    }

    $this->save_options();
}




$consumer_key = $this->options['consumer_key'];
$consumer_secret = $this->options['consumer_secret'];

$twitterapi = new TwitterAPI();
$twitterapi->consumer_key = $consumer_key;
$twitterapi->consumer_secret = $consumer_secret;

if(!$twitterapi->valid_consumer_keypair() && $this->error_msg == null)
{
	$this->error_msg = "Consumer Key and Consumer Secret are invalid.  Double Check and try again.";
	$this->keys_valid = false;
}
else
{
	$this->keys_valid = true;
}

unset($twitterapi);

$wpurl = get_bloginfo("wpurl");


?>

<style>

.note
{
	padding: 30px;
	margin: 20px;
	border-style: solid;
	border-width: 1px;
	border-color: #000;
	font-size: 1em;
	font-weight: bold; 
}

.note-table th
{
	vertical-align:top;
	text-align: left;
}
.note-table td
{
	vertical-align:top;
	text-align: left;
}

.error_msg
{
	margin: 20px;
	padding: 20px;
	border-style: solid;
	border-width: 2px;
	background-color: #FFCCCC;
	font-weight: bold;
}

#tweetformat_legend
{
	border-style: solid;
	border-width: 1px;
	border-color: #000;
	width: 100%;
	padding: 5px;
}

</style>



<div class="wrap">

<div id="icon-options-general" class="icon32"></div>

<h2>Tweet On Post Settings</h2>

<?php

if($this->error_msg != null)
{
	?>
	<div class="error_msg">ERROR: <?php echo $this->error_msg; ?></div>
	<?php
}

?>

<?php



?>


<div class="note">

<p>This plug requires you to register a new application on Twitter.  You can do this at <a target="_new" href="http://twitter.com/oauth_clients">http://twitter.com/oauth_clients</a>.  When you register a new application, use the following settings:</p>


<table class="note-table">

<tr>
<th>Application Name:</th>
<td>Use whatever name you would like to show as your tweets origin.</td>
</tr>

<tr>
<th>Application Website:</th>
<td>Must be the same domain as your Wordpress install.</td>
</tr>

<tr>
<th>Application Type:</th>
<td>Browser</td>
</tr>

<tr>
<th>Callback URL:</th>
<td><?php echo $wpurl; ?>/wp-admin/options-general.php?page=TweetOnPost</td>
</tr>

<tr>
<th>Default Access type:</th>
<td>Read &amp; Write</td>
</tr>

</table>

</div>
<br />





<div style="height:30px;"></div>
<form method="POST">
<input type="hidden" name="formatsettingsformsent" value="true" />

<table class="form-table">
<tr>
	<th>Enable TweetOnPost:</th>
	<td><input type="checkbox" name="tweetonpost_enable" <?php if($this->enabled){echo "checked";} ?> /></td>
</tr>
<tr>
	<th>Twitter Consumer Key</th>
	<td><input type="text" size="100" name="consumer_key" value="<?php echo $consumer_key; ?>" /></td>
</tr>
<tr>
	<th>Twitter Consumer Secret</th>
	<td><input type="text" size="100" name="consumer_secret" value="<?php echo $consumer_secret; ?>" /></td>
</tr>


<tr>
<th>Tweet Format:</th>
<td><textarea name="tweetformat" style="width:100%;height:100px"><?php echo $this->tweetformat; ?></textarea>
	<div id="tweetformat_legend">
		<b>Legend</b><br />
		[title] - Your post's title (shortened if necessary).<br />
		[link] - A bit.ly link to your post.<br />
		[category#] - The categories of your post as hash-tags.(ex: [category1] [category2] ...)<br />
		[tag#] - tags of your post as hash tags. (ex: [tag1] [tag2] [tag3] ...).<br />
	</div>
</td>
</tr>

<tr>
<th>Enable Url Shortening</th>
<td><input type="checkbox" name="enable_urlshortening" <?php if($this->options['enable_urlshortening']){echo "checked";} ?>/></td>
</tr>

<tr>
<th>Bit.ly Login:</th>
<td><input type="text" name="bitly_login" size="100" value="<?php echo $this->bitly_login; ?>" /></td>
</tr>
<tr>
<th>Bit.ly API Key:</th>
<td><input type="text" name="bitly_apikey" size="100" value="<?php echo $this->bitly_apikey; ?>" /></td>
</tr>
</table>

<input class="button-primary" type="submit" value="Save Settings" />
</form>


<?php //START IF

if($this->keys_valid)
{

?>

<h2>Accounts</h2>

<form method="POST">
	<input type="hidden" name="tweetonpost_auth_formsent" value="true" />
	<input type="submit" class="button-primary" value="Add Account" />
</form>


<table class="widefat">

<tr>
<th>Account Name</th>
<th>Twitter Name</th>
<th></th>

</tr>

<?php

$twitterapi = new TwitterAPI();
$twitterapi->consumer_key = $this->options['consumer_key'];
$twitterapi->consumer_secret = $this->options['consumer_secret'];


if(isset($this->options['accounts']))
{
	foreach($this->options['accounts'] as $key => $account)
	{

		if(!isset($account['screen_name']) || !isset($account['name']))
		{
			$twitterapi->oauth_token = $account['oauth_token'];
			$twitterapi->oauth_token_secret = $account['oauth_token_secret'];
			try{
			$userarray = $twitterapi->get_user();
			
			$account['screen_name'] = $userarray['screen_name'];
			$account['name'] = $userarray['name'];
			
			$this->options['accounts'][$key] = $account;
			$this->save_options();
			
			}catch(Exception $e){echo "<b>".$twitterapi->response."</b>";}
		}
		
		?>
		<form method="POST">
		<input type="hidden" name="tweetonpost_remove_account" value="true" />
		<input type="hidden" name="remove_account" value="<?php echo $key; ?>" />
		
		
		<tr>
			<td><?php echo $account['name'];?></td>
			<td><?php echo $account['screen_name'];?></td>
			<td><input class="secondary-button" type="submit" value="Remove Account" /></td>
		</tr>
		</form>
		
		<?php

		
	}
}

?>
</table>

<?php
} //END IF
?>


</div>
