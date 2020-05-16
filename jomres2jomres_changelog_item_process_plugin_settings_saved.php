<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2020 Vince Wooll
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( 'Direct Access to this file is not allowed.' );
// ################################################################

/**
*
* @package Jomres\CMF
*
* Handles webhook events on the parent server
*
*
*/

class jomres2jomres_changelog_item_process_plugin_settings_saved
{
    function __construct($componetArgs)
	{
		$item = unserialize($componetArgs->item);

		if ( isset($item->data->property_uid) ) {
			jr_import('channelmanagement_jomres2jomres_communication');
			$remote_server_communication = new channelmanagement_jomres2jomres_communication();

			$response = $remote_server_communication->communicate( "GET" , '/cmf/property/plugin/settings/'.$item->data->property_uid , [] , true );

			jr_import('jomres_call_api');
			$jomres_call_api = new jomres_call_api('system');

			$success = true;

			if (is_object($response) ) {

				$manager_id = channelmanagement_framework_utilities :: get_manager_id_for_property_uid ( $componetArgs->property_uid );

				$plugin_settings = json_decode(json_encode($response), true);

				if (is_array($plugin_settings)) {
					foreach ($plugin_settings as $plugin=>$settings) {
						// Plugin -----------------------------------------------------------------------------

						if(is_array($settings)) {
							$sets = array();
							foreach ($settings as $k=>$v) {
								if ($k != 'jomres_csrf_token') {
									$sets[$k] = $v;
								}
							}
							$settings = $sets;
						}

						$put_data = array (
							"property_uid" 			=> $componetArgs->property_uid,
							"plugin" 				=> $plugin,
							"params"				=> json_encode($settings)
						);

						$plugin_settings_response = $jomres_call_api->send_request(
							"PUT"  ,
							"cmf/property/plugin/settings" ,
							$put_data ,
							array (	"X-JOMRES-channel-name: "."jomres2jomres", "X-JOMRES-proxy_id: ".$manager_id )
						);

						if (!isset($plugin_settings_response->data->response->success) && $plugin_settings_response->data->response->success != true ) {
							$success = false;
							$failed_on = "cmf/property/plugin/settings";
						}
					}
					if ($success) {
						logging::log_message("Updated property ".$componetArgs->property_uid, 'CMF', 'DEBUG' , '' );

						$this->success = true;
					} else {
						logging::log_message("Failed to update property. Failed on ".$failedon, 'CMF', 'ERROR' , '' );
						$this->success = false;

					}
				}
			} else {
				logging::log_message("Did not get a valid response from parent server", 'CMF', 'ERROR' , serialize($response) );
			}
		} else {
			logging::log_message("Property not set", 'CMF', 'INFO' , '' );
		}
		if (!isset($this->success)) {
			$this->success = false;
		}

	}
}
