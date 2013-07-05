<?php
class TwitchTV {
  var $base_url = "https://api.twitch.tv/kraken/";
  var $client_id = 'INSERT CLIENT ID HERE';
  var $client_secret = "INSERT CLIENT SECRET HERE";
  var $redirect_url = 'INSERT REDIRECT URL HERE';
	var $scope_array = array('user_read','channel_read','chat_login','user_follows_edit','channel_editor','channel_commercial');
	
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
	
	// used to send the user to the authentication page for twitch once done we can use their channel data
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
	
	public function get_stream_title($username) {
		$channel_data = json_decode(file_get_contents($this->base_url.'channels/'.$username));
		$title = $channel_data->status;
		return $title;
	}
	
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
	
	public function stream_status($channel) {
		if($channel) {
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
					$online = "Stream Online";
					return $online;
				}
			}
		}
		curl_close($curl);
		} else {
			return;
		}
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
	
	public function stream_online_status($channel) {
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
					return false;
				} else {
					return true;
				}
			}
		}
		curl_close($curl);
	}
	
	public function load_video($channel,$height = null, $width = null) {
		//defaults for stream embed dimensions, set so you can pass in the height and width outside of this function
		if($height == null && $width == null) {
			$width = 640;
			$height = 360;
		}
		//make sure that a channel is passed in so that it doesn't return an invalid embed code
		if(!empty($channel)) {
			//embed code for the video thanks to twitch.tv
			//$embed_code = '<iframe width="'.$width.'" height="'.$height.'" src="http://www.twitch.tv/widgets/live_embed_player.swf?channel='.$channel.'&auto_play=true&start_volume=25" frameborder="0" allowfullscreen="true" auto_play="true" start_volume="25"></iframe>';
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
