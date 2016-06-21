twitchtv-oauth
==============
A [Live Example](http://sandbox.eliasranzschleifer.com/twitch-oauth/examples/) can be found on my website. Currently this doesn't have all of the methods, but you can see what I'm doing with the code and how I'm implementing it. I will do further documentation in the future.

A PHP class that allows you to do various features via the [TwitchTV API](https://github.com/justintv/twitch-api), including getting stats on your stream, updating your stream title/game, and running commercials.

To get started simply modify the following lines in TwitchTV.php to reflect your application. Please note that this information is pulled directly from your Twitch.TV application that you've set up. If you haven't set up a [Twitch.TV application](http://www.twitch.tv/settings/applications) yet then please do so before downloading this repository. The reason for this is because all of the requests made from this class are done via the API, and you will gain access to the API through your application.

     var $base_url = "https://api.twitch.tv/kraken/";
     var $client_id = 'INSERT CLIENT ID HERE';
	 var $client_secret = "INSERT CLIENT SECRET HERE";
	 var $redirect_url = 'INSERT REDIRECT URL HERE';
	 var $scope_array = array('user_read','channel_read','chat_login','user_follows_edit','channel_editor','channel_commercial');

If you've failed to do that then you'll get the following response from the API.

    {"error":"Bad Request","status":400,"message":"client does not exist"}
   
If you would like to get additional scopes in this code then you can simply go to the [Scopes on TwitchTV's API](https://github.com/justintv/Twitch-API/blob/master/authentication.md#scopes). If you have any questions on how to modify this please let me know. All of the scopes will be values of the array, so make sure each on is seperated by commas and have single quotes in them.

The code will automatically generate the url based on the scopes that you have requested and assuming that you've filled in the values listed above. Do not change the `$base_url` as this never changes. Only modify the ones that state "INSERT...HERE".

Now that you have your application configured you need to initilaze it. I've been using the PHP Framework CodeIgniter for this, but it can easily be implemented via raw PHP. To do so make sure that you initialize the PHP class.

    $twitchtv = new TwitchTV;
Once you've initialized it now you should be able to use all of the various classes within the class. The first step that you will need to take is to generate the link for the user to click to authenticate their stream. To do so you would do something like the following:
    `<a href="<?php echo $twitchtv->authenticate() ?>">Authenticate Me</a>`   
Once you're returned to your site from the Twitch.TV website you'll receive a url like the following `http://example.com/callback?code=CODE_HERE&scope=SCOPE_PASSED_IN_FROM_AUTHENTICATE_FUNCTION`. You'll now want to get the value from the code paramater on the URL. I would insert this value into a database so you can use it in the future. You can do something like `$ttv_code = $_GET['code'];` and then you'll want to insert that value into the get_access_token function. `$access_token = $twitchtv->get_access_token($ttv_code);`. Once you've completed that you will have an access token. I would advice that you insert this into the database as well because you can use the value in the database to make all your authenticated requests.

You should now be setup and ready to go! If you have any questions feel free to let me know and I'll be happy to answer them. Also if you are looking for additional functionalities that you would like then let me know as well and I would be happy to update the code accordingly. If you use this code make sure to give me credit for the work as well as let me know that you are using it so that I can check out what you have been working on. I hope this helps and allows you to make fun and creative applications!

I was reading through the twitch.tv developer forums and came across [this post](https://discuss.dev.twitch.tv/t/getting-twitch-username-php-and-oauth/1239/5). Devil makes several good points. If you're wanting to verify that you have an authenticated username then you can do so by the folllowing:

    $user_name = $twitchtv->authenticated_user($access_token);
    echo 'Thank you '.$user_name.'!  Authentication Completed!';

If the authorization didn't work then you should see `Unauthorized`.  See the below for a completed setup..

    <?php
    include 'twitchtv.php';
    $twitchtv = new TwitchTV;
    echo '<a href="'.$twitchtv->authenticate().'">Authenticate Me</a><br/>';
    $ttv_code = $_GET['code'];
    $access_token = $twitchtv->get_access_token($ttv_code);
    $user_name = $twitchtv->authenticated_user($access_token);
    if(isset($user_name)) {
        echo 'Thank you '.$user_name.'!  Authentication Completed!';
    }
    ?>

Note: I'm not actively developing on this project anymore, but if you have some work that you think could be migrated into this then please feel free to share. I just haven't had the desire to really add much to the script as it takes a lot of time to make sure things are situated. If you have questions about the script please post it in the issues and I'll get to it when I can.