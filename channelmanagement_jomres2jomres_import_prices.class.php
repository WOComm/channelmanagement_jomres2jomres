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
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################


class channelmanagement_jomres2jomres_import_prices
{
	
	public static function import_prices(  $manager_id , $channel , $remote_property_id = 0 , $property_uid = 0 , $sleeps = 0 , $local_room_type_id = 0 , $remote_room_type_id = 0  )
	{

		if ( (int)$remote_property_id == 0 ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_PROPERTYID_NOTSET','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_PROPERTYID_NOTSET',false) );
		}
		
		if ( (int)$property_uid == 0 ) {
			throw new Exception( "Property uid is not set " );
		}
		
		if ( (int)$sleeps == 0 ) {
			throw new Exception( "Number of persons property sleeps is not set " );
		}

		if ( (int)$local_room_type_id == 0 ) {
			throw new Exception( "Local room type id is not set " );
		}

		if ( (int)$remote_room_type_id == 0 ) {
			throw new Exception( "Remote room type id is not set " );
		}

        jr_import('channelmanagement_jomres2jomres_communication'); // channelmanagement_jomres2jomres_communication is unique to this thin plugin and is the mechanism for talking to the remote service
        $remote_server_communication = new channelmanagement_jomres2jomres_communication();

		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton'); // channelmanagement_framework_singleton is used by all thin plugins for talking to this local Jomres installation

		// Before we can set more detailed tariffs, we need to set the base price
		$base_price = $remote_server_communication->communicate( 'GET' , 'cmf/property/base/price/'.$remote_property_id ,  array() , true  );

		$post_data = array (
				"property_uid"					=> $property_uid ,
				"base_price"					=> $base_price->price_excluding_vat , // Create a new micromanage tariff
				"rate_title"					=> "Tariff" ,
				"max_people"					=> $sleeps
			);

		$base_price_set_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/base/price/' , $post_data );

		$remote_prices = $remote_server_communication->communicate( 'GET' , 'cmf/property/list/prices/'.$remote_property_id ,  array() , true  );

		$remote_prices = json_decode(json_encode($remote_prices), true);

		$arr = array();
		if ( !empty($remote_prices['tariff_sets'])) {
			$sets_for_this_remote_room_type_id = array();
			foreach ($remote_prices['tariff_sets'] as $tariff_sets ) {
				foreach ($tariff_sets as $tariff_set ) {
					if ($tariff_set['room_type_id'] == $remote_room_type_id ) {
						$sets_for_this_remote_room_type_id [] = $tariff_set;
					}
				}
			}
			unset($remote_prices['tariff_sets']); // We'll free up some memory as this wont be used again

			if (!empty($sets_for_this_remote_room_type_id)) {
				$post_data = array();
				foreach ($sets_for_this_remote_room_type_id as $set ) { // We can cycle through all of the sets without creating multiple mirroring sets here as the tariffinput and mindaysinput arrays should be cumulativiely added to for all the sets in one go

					$post_data['property_uid']				= $property_uid;
					$post_data['tarifftypeid']				= 0;
					$post_data['rate_title']				= filter_var($set['rate_title'], FILTER_SANITIZE_SPECIAL_CHARS);
					$post_data['rate_description']			= '';
					$post_data['maxdays']					= (int)$set['max_days'];
					$post_data['minpeople']					= (int)$set['minpeople'];
					$post_data['maxpeople']					= (int)$set['maxpeople'];
					$post_data['roomclass_uid']				= $local_room_type_id;
					$post_data['dayofweek']					= (int)$set['dayofweek'];
					$post_data['ignore_pppn']				= (int)$set['ignore_pppn'];
					$post_data['allow_we']					= (int)$set['allow_we'];
					$post_data['weekendonly']				= (int)$set['weekendonly'];
					$post_data['minrooms_alreadyselected']	= 0;
					$post_data['maxrooms_alreadyselected']	= 100;

					foreach ($set['dates'] as $date ) {
						$epoch = strtotime($date);
						$post_data['tariffinput'][$epoch] = (float)$set["rate_per_night"]["price_excluding_vat"];
						$post_data['mindaysinput'][$epoch] = (int)$set['min_days'];
					}
				}
			}

			$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/tariff/' , $post_data );
/*			foreach ($remote_prices['tariff_sets'] as $tariff_sets ) {
				foreach ($tariff_sets as $tariff_set) {

						$set				= new stdClass();
						$set->date_from		= $tariff_set ["date_range"] ["start"];
						$set->date_to		= $tariff_set ["date_range"] ["end"];
						$set->ratepernight	= $tariff_set ["rate_per_night"]["price_excluding_vat"];
						$arr[] = $set;
				}
			}
			$post_data = array (
				"property_uid" => $property_uid,
				"ratepernight" => json_encode($arr)
 			);

			$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/prices/' , $post_data );*/
		}
	}
}

/*property_uid
tarifftypeid
rate_title
rate_description
maxdays
minpeople
maxpeople
roomclass_uid
dayofweek
ignore_pppn
allow_we
weekendonly
minrooms_alreadyselected
maxrooms_alreadyselected
tariffinput[1577059200]
mindaysinput[1577059200]*/

