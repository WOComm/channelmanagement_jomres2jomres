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

class jomres2jomres_changelog_item_process_property_settings_updated
{
    function __construct($componetArgs)
	{
		$item = unserialize($componetArgs->item);

		if ( isset($item->data->property_uid) ) {
			// So far, so good. Let's find the remote site's booking to import it into this system

			jr_import('channelmanagement_jomres2jomres_communication');
			$remote_server_communication = new channelmanagement_jomres2jomres_communication();

			$response = $remote_server_communication->communicate("GET", '/cmf/property/settings/' . $item->data->property_uid, [], true);

			jr_import('jomres_call_api');
			$jomres_call_api = new jomres_call_api('system');

			$success = true;

			if (is_object($response) ) {

				$manager_id = channelmanagement_framework_utilities :: get_manager_id_for_property_uid ( $componetArgs->property_uid );

				$response = json_decode(json_encode($response), true);

				if (is_array($response)) {

					// One setting that we will not inherit from the parent is api_privacy_off because that's one that allows a parent to share with the child, but the child shouldn't share itself with anybody else
					// If a channel wants to enable it, they can still use the PUT property/settings endpoint and enable it that way
					$response['api_privacy_off'] = 0;

					$put_data = array (
						"property_uid" 			=> $componetArgs->property_uid,
						"params"				=> json_encode($response)
					);

					$settings_response = $jomres_call_api->send_request(
						"PUT"  ,
						"cmf/property/settings" ,
						$put_data ,
						array (	"X-JOMRES-channel-name: "."jomres2jomres", "X-JOMRES-proxy_id: ".$manager_id )
					);

					if (!isset($settings_response->data->response->success) && $settings_response->data->response->success != true ) {
						$success = false;
						$failed_on = "cmf/property/settings";
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
