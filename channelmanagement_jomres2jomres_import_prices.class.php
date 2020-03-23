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


class channelmanagement_jomres2jomres_import_prices
{
	
	public static function import_prices(  $manager_id , $channel , $remote_property_id = 0 , $property_uid = 0 , $sleeps = 0 , $room_type_id = 0  )
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
		
		if ( (int)$room_type_id == 0 ) {
			throw new Exception( "Room type id is not set " );
		}

        jr_import('channelmanagement_jomres2jomres_communication'); // channelmanagement_jomres2jomres_communication is unique to this thin plugin and is the mechanism for talking to the remote service
        $channelmanagement_jomres2jomres_communication = new channelmanagement_jomres2jomres_communication();

		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton'); // channelmanagement_framework_singleton is used by all thin plugins for talking to this local Jomres installation

		// Before we can set more detailed tariffs, we need to set the base price
		$base_price = $channelmanagement_jomres2jomres_communication->communicate( 'GET' , 'cmf/property/base/price/'.$remote_property_id ,  array() , true  );

		$post_data = array (
				"property_uid"					=> $property_uid ,
				"base_price"					=> $base_price->price_excluding_vat , // Create a new micromanage tariff
				"rate_title"					=> "Tariff" ,
				"max_people"					=> $sleeps
			);

		$base_price_set_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/base/price/' , $post_data );

		$remote_prices = $channelmanagement_jomres2jomres_communication->communicate( 'GET' , 'cmf/property/list/prices/'.$remote_property_id ,  array() , true  );

		$remote_prices = json_decode(json_encode($remote_prices), true);
		$arr = array();
		if ( !empty($remote_prices['tariff_sets'])) {

			foreach ($remote_prices['tariff_sets'] as $tariff_sets ) {
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

			$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/prices/' , $post_data );
		}
	}
	
	

}

