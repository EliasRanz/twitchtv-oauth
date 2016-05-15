<?php setcookie("return_url", $_SERVER["REQUEST_URI"], time() + 60000, '/');
include '../twitchtv.php'; // replace this with the path to your file.
$twitchtv = new TwitchTv; // replace this with TwitchTv (I changed it to avoid errors in my IDE for duplicates)
/* if we don't have an access token then print the Authenticate Me link, since I'm using a callback file
 * I am getting the access token from a cookie, this would probably be better to get it from a database and store it
 * encrypted and then decrypt it when you need to use it.
 */
if(!isset($_COOKIE["access_token"])) {
    echo '<a href="' . $twitchtv->authenticate() . '">Authenticate Me</a><br/>';
    $ttv_code = $_GET['code'];
    $access_token = $twitchtv->get_access_token($ttv_code);
} else {
    $access_token = $_COOKIE["access_token"];
}
$user_name = $twitchtv->authenticated_user($access_token);

if (isset($user_name)) {
    echo 'Thank you ' . $user_name . '!  Authentication Completed!';

    ?>
    <p>Choose the length of the commercial (in seconds). The below are the available options based on what Twitch has stated.</p>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"];?>">
        Select value:
        <select name="length">
            <option value="30">30</option>
            <option value="60">60</option>
            <option value="90">90</option>
            <option value="120">120</option>
            <option value="150">150</option>
            <option value="180">180</option>
        </select>
        <input type="submit" name="submit" value="Run commercial"/>
    </form>
    <?php
    if ($_POST) {
        if (isset($_POST['length'])) {
            $length = (int) $_POST['length']; // Twitch expects an integer to be passed into the function so type cast it.
            $run_commercial = $twitchtv->run_commercial($access_token, $length);
        } else {
            $run_commercial = $twitchtv->run_commercial($access_token);
        }
        // You can do whatever you want to do here, note Twitch only allows you to run an ad every 8 minutes.
        if ($run_commercial) {
            echo "Ran commercial for $length seconds.";
        } else {
            echo "Failed to run commercial.";
        }
    }
    // reset the return_url cookie so we can access the main page with links to other examples.
    unset($_COOKIE['return_url']);
    setcookie('return_url', '', time() - 1, '/');
}
?>

<br><br><a href="../">Return to examples</a>