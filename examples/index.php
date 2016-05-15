<?php if(isset($_COOKIE["return_url"])) {
    // This file acts as a callback in addition to a menu to access the different examples.
    include 'twitchtv.php';
    $twitchtv = new TwitchTv;
    $ttv_code = $_GET['code'];
    $access_token = $twitchtv->get_access_token($ttv_code);
    // set a cookie for the access token to be used 
    if(isset($access_token)) {
        setcookie("access_token", $access_token, time() + 180000);
    }
    http_redirect($_COOKIE["return_url"]."?".$_SERVER["QUERY_STRING"]);
} else {
    unset($_COOKIE['access_token']);
    setcookie('access_token', '', 1, '/');
}




?>

<a href="initial_setup/">Initial Setup</a><br />
<a href="run_commercial/">Run Commercial</a>
