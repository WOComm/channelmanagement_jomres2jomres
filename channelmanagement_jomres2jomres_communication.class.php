<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright 2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

class channelmanagement_jomres2jomres_communication
	{


	function __construct( $user_id = 0 )
	{
		$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
		$jrConfig = $siteConfig->get();

		if ( trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_parent_site"]) == '' ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_PARENT_NOT_SET','CHANNELMANAGEMENT_JOMRES2JOMRES_PARENT_NOT_SET',false) );
		}

		$url = trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_parent_site"] , '/' );
		$url = parse_url($url);
		$this->url = $url['scheme'].'://'.$url['host'];
		$this->path = trim( $url['path'] , "_" );

		$plugin_settings = cmf_jomres2jomres_get_plugin_setting( $user_id , $url['host']);

		if (isset($plugin_settings->token)) {
			$this->token = $plugin_settings->token;
		} else {
			$this->token = $this->get_token();
			cmf_jomres2jomres_save_plugin_setting( $user_id , $url['host'] , 'token' , $this->token );
		}
		if (!isset($plugin_settings->channel_id)) {
			$channel_id = $this->announce();
			cmf_jomres2jomres_save_plugin_setting( $user_id , $url['host'] , 'channel_id' , $channel_id );
		}
	}



	
	public function communicate( $method = 'GET' , $endpoint = '' , $putpost = [] , $clear_cache = false )
	{
		if (substr($endpoint , 0, 1) !== '/') { // I got fed up with endpoints failing because I confused myself with slashes not being in place
			$endpoint = "/".$endpoint;
		}

		// Webhook events will use this method, but we don't (?) want to cache the messages so we'll not cache them
		$method_can_be_cached = true;

/*        jr_import('channelmanagement_jomres2jomres_push_event_trigger_crossref');
        $event_trigger_crossref = new channelmanagement_jomres2jomres_push_event_trigger_crossref();
		foreach ( $event_trigger_crossref->events as $event_type ) {
            if ( in_array( $method , $event_type )) {
                $method_can_be_cached = false;
            }
        }*/

/*		if ($method == 'Pull_ListPropertiesChangeLog_RQ' ) {
			$method_can_be_cached = false;
		}*/

		if ($clear_cache == true ) {
			$method_can_be_cached = false;
		}

		if ( $method_can_be_cached ) {
            $data_hash = md5(serialize($endpoint));
            $filename = $method."_".$data_hash.".php";

            if (!is_dir(JOMRES_TEMP_ABSPATH."cm_jomres2jomres_data_cache")) {
                mkdir(JOMRES_TEMP_ABSPATH."cm_jomres2jomres_data_cache");
            }

            if (file_exists( JOMRES_TEMP_ABSPATH."cm_jomres2jomres_data_cache".JRDS.$filename )) {
                require_once(JOMRES_TEMP_ABSPATH."cm_jomres2jomres_data_cache".JRDS.$filename);
                $class_name = $method."_".$data_hash;
                $ru_data_cache = new $class_name();
                return unserialize($ru_data_cache->data);
            }
        }

 		try {
			$uri = $this->url.$this->path.$endpoint.'/';

			$headers = [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' .$this->token,
				'Accept'        => 'application/json',
				'X-JOMRES-channel-name' => 'jomres2jomres',
			];

			$client = new GuzzleHttp\Client(['timeout' => 6, 'connect_timeout' => 6 , 'headers' => $headers ]);

			logging::log_message('Starting guzzle call to '.$uri, 'Guzzle', 'DEBUG');

			if ( $method == 'POST' ||  $method == 'PUT' ) {
				$options = [
					'form_params' => $putpost,
				];
			} else {
				$options = [];
			}

			$response = $client->request($method, $uri, $options);

		}
		catch (Exception $e) {
			echo "Failed to get response from channel
			";

            var_dump($e->getMessage());exit;
			logging::log_message("Failed to get response from channel manager. Message ".$e->getMessage(), 'CMF', 'ERROR' , "rentalsunited" );
			return false;
		}

		if (!isset($response)) {
			return false;
		}

		$response_str = (string)$response->getBody();
		$response_json_decoded = json_decode($response_str);

		if ( $method_can_be_cached && isset($response_json_decoded->data->response) ) {

        	$cache_data = "<?php
	        defined(\"_JOMRES_INITCHECK\" ) or die( \"\" );
			class " . $method . "_" . $data_hash . " 
				{
					public function __construct()
						{
							\$this->method =  '" . $method . "';
							\$this->data = '" . serialize($response_json_decoded->data->response) . "';
						}
 				}
				";

				file_put_contents(JOMRES_TEMP_ABSPATH . "cm_jomres2jomres_data_cache" . JRDS . $filename, $cache_data);
			}

		if (isset($response_json_decoded->data->response)) {
			return $response_json_decoded->data->response;
		} else {
			return false;
			}
		}


	/*
	*
	* Get the token from the remote service
	*
	*/
		private function get_token()
		{
			$siteConfig = jomres_singleton_abstract::getInstance('jomres_config_site_singleton');
			$jrConfig = $siteConfig->get();

			if ( trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_id"]) == '' ) {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_USERNAME_NOT_SET','CHANNELMANAGEMENT_JOMRES2JOMRES_USERNAME_NOT_SET',false) );
			}

			if ( trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_secret"]) == '' ) {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_PASSWORD_NOT_SET','CHANNELMANAGEMENT_JOMRES2JOMRES_PASSWORD_NOT_SET',false) );
			}


			try {
				// Guzzle giving me problems, switching to curl for now but this needs to be refactored. Todo.
				$data =  [
					'grant_type' => 'client_credentials',
					'client_id' => trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_id"]),
					'client_secret' => trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_secret"])
				];

				$ch = curl_init($this->url.$this->path.'/');

				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
				curl_setopt($ch,CURLOPT_VERBOSE,true);
				//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

				$result = curl_exec($ch);
				$status = curl_getinfo($ch);

				$response = json_decode($result);

				if ( isset($response->access_token)) {
					return $response->access_token;
				} else {
					throw new Exception( "Access token not received" );
				}

				/*$options = [
					'form_params' => [
						'grant_type' => 'client_credentials',
						'client_id' => trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_id"]),
						'client_secret' => trim($jrConfig['channel_manager_framework_user_accounts']['jomres2jomres']["channel_management_jomres2jomres_client_secret"])
					],
					'debug' => true,
					'allow_redirects' => true
				];
				$client = new \GuzzleHttp\Client(["base_uri" => $this->url]);

				$response = $client->post($this->path.'/', $options);*/

			}
			catch (Exception $e) {
				logging::log_message("Failed to get response from channel manager ".$this->url.$this->path." Message ".$e->getMessage(), 'CMF', 'ERROR' , "rentalsunited" );
				return false;
			}
		}

	/*
	*
	* Announce this server to the remote service.
	*
	* Every connection to an instance of a cmf rest api installation must be "announced" (registered as a channel) to use the cmf features.
	*
	*/
	private function announce()
	{
		$endpoint = '/cmf/channel/announce/jomres2jomres/Jomres%202%20Jomres';
		try {
			$uri = $this->url.$this->path.$endpoint;

			$headers = [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' .$this->token,
				'Accept'        => 'application/json'
			];

			$options = 	[
				'form_params' => [
					'params' => '{"has_dictionaries":true}' // For now, this is nonsense and will probably need to be removed
				]
			];

			$client = new GuzzleHttp\Client(['timeout' => 6, 'connect_timeout' => 6 , 'headers' => $headers ]);

			logging::log_message('Starting guzzle call to '.$uri, 'Guzzle', 'DEBUG');

			$response = $client->request("POST", $uri , $options );

			$reply = json_decode((string)$response->getBody());

			if (isset($reply->data->response) && (int)$reply->data->response > 0 ) {
				return (int)$reply->data->response;
			} else {
				throw new Exception( "Couldn't get the channel id" );
			}
		}
		catch (Exception $e) {
			logging::log_message("Failed to get response from channel manager ".$this->url.$this->path.$endpoint.". Message ".$e->getMessage(), 'CMF', 'ERROR' , "rentalsunited" );
			return false;
		}
	}
	}
