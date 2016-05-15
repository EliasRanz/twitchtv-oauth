<?php setcookie("return_url", $_SERVER["REQUEST_URI"], time() + 60000, '/');
include '../twitchtv.php'; // replace this with the path to your file.
$twitchtv = new TwitchTv; // replace this with TwitchTv (I changed it to avoid errors in my IDE for duplicates)
// if we don't have an access token then print the Authenticate Me link
if (!isset($_COOKIE["access_token"])) {
    echo '<a href="' . $twitchtv->authenticate() . '">Authenticate Me</a><br/>';
    $ttv_code = $_GET['code'];
    $access_token = $twitchtv->get_access_token($ttv_code);
} else {
    $access_token = $_COOKIE["access_token"];
}
$user_name = $twitchtv->authenticated_user($access_token);

if (isset($user_name)) {
    // reset the cookies for other all of the other examples.
    unset($_COOKIE['return_url']);
    setcookie('return_url', '', time() - 1, '/');
    unset($_COOKIE['access_token']);
// empty value and expiration one hour before
    setcookie('access_token', '', time() - 1);
    echo 'Thank you ' . $user_name . '!  Authentication Completed!';
}
?>

<br><br><a href="../">Return to examples</a>
