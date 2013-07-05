<?php
/*
 * TwitchTV API code by Elias Ranz-Schleifer
 * Thank you for using my code please refer to
 * https://github.com/Xxplosions/twitchtv-oauth for future updates
 * Have questions? Contact Elias on Twitter (https://twitter.com/xxplosions)
 * Check out my livestream at http://twitch.tv/xxplosions (It would be amazing to chat with you about future updates)
 */
 
class TwitchTV {
  var $base_url = "https://api.twitch.tv/kraken/";
  var $client_id = 'INSERT CLIENT ID HERE'; //change this value, should be your TwitchTV Application Client ID
  var $client_secret = "INSERT CLIENT SECRET HERE"; //change this value, should be your TwitchTV Application Client Secret 
  var $redirect_url = 'INSERT REDIRECT URL HERE'; //change this value, should be your TwitchTV Application Rerdirect URL
  var $scope_array = array('user_read','channel_read','chat_login','user_follows_edit','channel_editor','channel_commercial');
	
	//generates the url based on the scopes that have been given.
	public function authenticate() {	
		$i = 0;
		$return = '';
		$len = count($this->scope_array);
		//search through the scope array and append a + foreach all but the last element
		foreach ($this->scope_array as $scope) {
		    if ($i == $len - 1) {
		        $scope .= "";
		        $return .= $scope;
		    } else {
		    	$scope .= "+";
		    	$return .= $scope;
		    }
		    
		    $i++;
		}
		//initiate connection to the twitch.tv servers
		$scope = $return;
		$authenticate_url = $this->base_url.'oauth2/authorize?response_type=code&client_id=' . $this->client_id . '&redirect_uri=' . $this->redirect_url . '&scope=' . $scope;
		return $authenticate_url;
	}
	
	//Get the Access Token, expects the code value returned from the TwitchTV callback
	function get_access_token($code) {
		$ch = curl_init($this->base_url . "oauth2/token");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		$fields = array(
			 'client_id' => $this->client_id,
			 'client_secret' => $this->client_secret,
			 'grant_type' => 'authorization_code',
			 'redirect_uri' => $this->redirect_url,
			 'code' => $code
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		$data = curl_exec($ch);
		$response = json_decode($data, true);
		return $response["access_token"];
	}
	
	//Gets the username based on an access token. This will let you know who's Authenticated, expects Access Token
	function authenticated_user($access_token) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->base_url . "user");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		         'Authorization: OAuth '.$access_token
		));
		$output = curl_exec($ch);
		$response = json_decode($output, true);
		curl_close($ch);
		
		if(isset($response['error'])) {
			$error = 'Unauthorized';
			return $error;
		} else {
			$username = $response['name'];
			return $username;
		}
		
	}
	
	// CHecks to see if the user exists on TwitchTV, if it doesn't exist then it will return false, expects a TwitchTV username
	public function validate_stream($username) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $this->base_url.'users/'.$username
					      )
		);
		
		$result = curl_exec($curl);
		//makes sure that the cURL was excuted if not it generates the error stating that it didn't succeed.
		if(!curl_exec($curl)){
		    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
		} else {
			$decoded = json_decode($result);
			if(isset($decoded->error)) {
				return false;
			} else {
				return true;
			}
			print_r($decoded);
		}
	}
	
	//Loads in the Display Name, Stream Status, and Banner, expects TwitchTV username
	public function load_channel($channel) {
		//initiate connection to the twitch.tv servers
		$curl = curl_init();
		curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $this->base_url . 'channels/'. $channel .'?client_id=' . $this->client_id
				 )
		);
		$result = curl_exec($curl);
		//makes sure that the cURL was excuted if not it generates the error stating that it didn't succeed.
		if(!curl_exec($curl)){
		    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
		} else {
			//cURL Response worked
			if(!empty($channel)) {
				$return = json_decode($result);
				$stream_details = array('display_name' => $return->display_name,
							'status' => $return->status,
							'chat' => $return->_links->chat,
							'banner' => $return->banner);
				return $stream_details;
			}
		}
		curl_close($curl);
	}
	
	//Returns the Stream Title based on what the user currently has on their stream, expects TwitchTV username.
	public function get_stream_title($username) {
		$channel_data = json_decode(file_get_contents($this->base_url.'channels/'.$username));
		$title = $channel_data->status;
		return $title;
	}
	
	//Updates a the Authenticated users stream title, expects Access Token, optional values are Stream Title and Stream Game
	public function update_stream_title($access_token,$title = null,$game = null) {
		$username = $this->authenticated_user($access_token);
		if($username != 'Unauthorized') {
			//get channel data so that you can make sure a value is being passed in and not setting it as an empty request
			$channel_data = json_decode(file_get_contents($this->base_url.'channels/'.$username));
			//no game? set it to the value that is stored in the API
			if($game == null) {
				$game = $channel_data->game;
			}
			//no title? set it to the value in the API
			if ($title == null) {
				$title = $channel_data->status;
			}
			
			// make the API call and update stream information
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->base_url . "channels/".$username);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POST, 1);
			$fields = array('channel[status]' => $title, 'channel[game]' => $game);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			         'Authorization: OAuth '.$access_token
			));
			$output = curl_exec($ch);
			$response = json_decode($output, true);
			curl_close($ch);
			
			return true;
		}
	}
	
	//This loads the viewer count for a stream, expects TwitchTV channel.
	public function load_stream_stats($channel) {
		//initiate connection to the twitch.tv servers
		$curl = curl_init();
		curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
				        	CURLOPT_URL => $this->base_url . 'streams/'. $channel .'?client_id=' . $this->client_id
				 )
		);
		$result = curl_exec($curl);
		//makes sure that the cURL was excuted if not it generates the error stating that it didn't succeed.
		if(!curl_exec($curl)){
		    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
		} else {
			//cURL Response worked
			if(!empty($channel)) {
				$return = json_decode($result);
				if($return->stream == null) {
					$offline = "Stream Offline";
					return $offline;
				} else {
					$stream_details = array('viewers' => $return->stream->viewers);
					return $stream_details;
				}
			}
		}
		curl_close($curl);
	}
	


	
	public function follower_count($channel) {
		//initiate connection to the twitch.tv servers
		$curl = curl_init();
		curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
										CURLOPT_URL => $this->base_url . 'channels/'. $channel .'/follows?client_id=' . $this->client_id
									  )
		);
		$result = curl_exec($curl);
		//makes sure that the cURL was excuted if not it generates the error stating that it didn't succeed.
		if(!curl_exec($curl)){
		    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
		} else {
			//cURL Response worked
			if(!empty($channel)) {
				$return = json_decode($result);
				$followers = $return->_total;
				return $followers;
			}
		}
		curl_close($curl);
	}
	
	//Loads the Online or Offline status of a stream, expect TwitchTV Username
	public function stream_online_status($channel) {
		//initiate connection to the twitch.tv servers
		$curl = curl_init();
		curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_URL => $this->base_url . 'streams/'. $channel .'?client_id=' . $this->client_id
				              )
		);
		$result = json_decode(curl_exec($curl));
		
		//makes sure that the cURL was excuted if not it generates the error stating that it didn't succeed.
		if(!curl_exec($curl)){
		    die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
		} else {
			//cURL Response worked
			if(!empty($channel)) {
				if($result->stream == null) {
					return false;
				} else {
					return true;
				}
			} else {
				echo "shit";
			}
		}
		curl_close($curl);
	}
	
	//Loads the streams video, expects TwitchTV Username, additional paramaters allow for customizing the height and width of the embed
	public function load_video($channel,$height = null, $width = null) {
		//defaults for stream embed dimensions, set so you can pass in the height and width outside of this function
		if($height == null && $width == null) {
			$width = 640;
			$height = 360;
		}
		//make sure that a channel is passed in so that it doesn't return an invalid embed code
		if(!empty($channel)) {
			//embed code for the video thanks to twitch.tv
			$embed_code = '<object type="application/x-shockwave-flash" height="'.$height.'" width="'.$width.'" id="live_embed_player_flash" data="http://www.twitch.tv/widgets/live_embed_player.swf?channel='.$channel.'" bgcolor="#000000">
								<param name="allowFullScreen" value="true" />
								<param name="allowScriptAccess" value="always" />
								<param name="allowNetworking" value="all" />
								<param name="movie" value="http://www.twitch.tv/widgets/live_embed_player.swf" />
								<param name="flashvars" value="hostname=www.twitch.tv&channel='.$channel.'&auto_play=true&start_volume=25" />
							</object>';
			return $embed_code;
		} else {
			return;
		}
	}
	
	//Loads the streams chat, expects TwitchTV Username, additional paramaters allow for customizing the height and width of the embed
	public function load_chat($channel,$height = null, $width = null) {
		//defaults for stream embed dimensions, set so you can pass in the height and width outside of this function
		if($height == null && $width == null) {
			$width = 350;
			$height = 500;
		}
		//make sure that a channel is passed in so that it doesn't return an invalid embed code
		if(!empty($channel)) {
			//embed code thanks to twitch.tv
			return $embed_code;
		} else {
			return;
		}
	}
	
	//Gets the current directory of all the games on TwitchTV currently streaming. This is returned in JSON to allow for an autocomplete functionality
	public function get_games() {
		$game = array();
		for($i = 0; $i < 5; $i++){
			$offset = 100 * $i;
			$obj = json_decode(file_get_contents('https://api.twitch.tv/kraken/games/top?limit=100&offset='.$offset));
			if($obj) {
				foreach($obj->top as $top){
					$game[] = $top;
				}
			}
		}
		$games = array();
		foreach($game as $game) {
			$games[] = $game->game->name;
		}
		return json_encode($games);
	}
	
	//Call this function if you want to run a commercial on the stream. Expects a access token, but can take a time
	//for information about the length value please see https://github.com/justintv/Twitch-API/blob/master/v2_resources/channels.md#post-channelschannelcommercial
	public function run_commercial($access_token, $length = 30) {
		$username = $this->authenticated_user($access_token);
		$ch = curl_init($this->base_url . "channels/".$username.'/commercial');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.twitchtv.v3+json','Authorization: OAuth '.$access_token));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		$fields = array(
			 'client_id' => $this->client_id,
			 'length' => $length
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		$data = curl_exec($ch);
		$response = json_decode($data, true);
		return true;
	}
}
?>
