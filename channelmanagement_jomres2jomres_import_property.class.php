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


class channelmanagement_jomres2jomres_import_property
{
	
	public static function import_property( $channel , $remote_property_id = 0 , $proxy_id = 0 )
	{
		$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');
		$JRUser									= jomres_singleton_abstract::getInstance( 'jr_user' );

		$mapped_dictionary_items = channelmanagement_framework_utilities :: get_mapped_dictionary_items ( $channel , $mapped_to_jomres_only = true );

        jr_import('channelmanagement_jomres2jomres_communication');
        $remote_server_communication = new channelmanagement_jomres2jomres_communication();

        set_showtime("property_managers_id" , $JRUser->id );

		$remote_url = $remote_server_communication->communicate( "GET" , '/cmf/url' , [] , true );

		$remote_property = $remote_server_communication->communicate( "GET" , '/cmf/property/'.$remote_property_id  , [] , true );

		// We don't want to import unpublished properties, they're not ready (I suspect I'll need to change this, but for now we'll stick with it)

		if ( $remote_property != false && (int)$remote_property->published == 1 ) {

			$room_info = json_decode(json_encode($remote_property->room_info), true);

			// room types
			$property_room_types = array();
			$max_guests_in_property = 0;
			foreach ($room_info['room_types'] as $remote_type_id => $remote_type_details ) {
				if ( isset($mapped_dictionary_items['_cmf_list_room_types']) && !empty($mapped_dictionary_items['_cmf_list_room_types']) ) {
					foreach ($mapped_dictionary_items['_cmf_list_room_types'] as $mapped_item) {
						if ( $mapped_item->remote_item_id == $remote_type_id ) {
							$arr = $mapped_dictionary_items['_cmf_list_room_types'][$remote_type_id];
							$count = 0;

							foreach ($room_info['rooms_max_people'][$remote_type_id] as $a) { // Not sure yet if I need count
								$count = $count + $a;
								$max_guests = $a;
								$max_guests_in_property = $max_guests_in_property + $a;
							}
							$property_room_types[] = array("amenity" => $arr, "count" => $count, "max_guests" => $max_guests);
						}
					}
				}
			}

			$property_room_types = array_unique($property_room_types, SORT_REGULAR);

			$room_types = array();
			foreach ($property_room_types as $prt ) {

				$room_types[] = json_encode($prt);
			}

		}  else {
			return (object)array("success" => false, "message" => "Could not get property from remote server ");
		}

            // room features
			// Not supported yet
			$property_room_features = array();
			/*
 			if (!empty($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities']) && !empty($mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'] )){
				foreach ($remote_property['Property']['CompositionRoomsAmenities']['CompositionRoomAmenities'] as $amenity) {
					$amenity_id = $amenity[$atts]['CompositionRoomID'];
					
					if ( isset($mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ']) && array_key_exists ( $amenity_id , $mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'] ) ) {
						$arr = $mapped_dictionary_items['Pull_ListAmenitiesAvailableForRooms_RQ'][$amenity_id];
						unset($arr->item);
						$property_room_features[] = $arr;
					}
						
				}
				$property_room_features = array_unique($property_room_features, SORT_REGULAR);
			}*/

			// Property features
			$property_features = array();

			if ( isset($remote_property->property_features) && $remote_property->property_features != '' ) {

				$bang = explode(",", $remote_property->property_features);

				if (!empty($bang)) {
					foreach ($bang as $remote_property_feature_id) {
						foreach ($mapped_dictionary_items['_cmf_list_property_features'] as $mapped_property_feature) {
							if ($mapped_property_feature->jomres_id == $remote_property_feature_id) {
								$property_features[] = $mapped_property_feature; // Don't really need all of this var's details, but it makes tracing it thru the system heckin' easier because my brain is fried by the coronavirus worries
							}
						}
					}
				}
			}

			// Find the local property type for this property


 			if (isset($remote_property->ptype_id)){
				$local_property_type = 0;
				foreach ($mapped_dictionary_items['_cmf_list_property_types'] as $mapped_property_type) {
					if ($remote_property->ptype_id == $mapped_property_type->remote_item_id) {
						$local_property_type = $mapped_property_type->jomres_id;
						$mrp_or_srp = channelmanagement_jomres2jomres_import_property::get_property_type_booking_model( $local_property_type ); // Is this an MRP or SRP?
					}
				}

				// local property type was never found for this property. Throw an error and stop trying as we can't create the property
				if ( $local_property_type == 0 ) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_PROPERTYTYPE_NOTFOUND',false)." Remote property type ".$remote_property['Property']['ObjectTypeID'] );
				}
				
				if (!isset($mrp_or_srp)) {
					throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_BOOKING_MODEL_NOT_FOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_BOOKING_MODEL_NOT_FOUND',false) );
				}
			} else {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REMOTEPROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REMOTEPROPERTYTYPE_NOTFOUND',false) );
			}

			$image_info = json_decode(json_encode($remote_property->images), true);

			// Images to be imported
			$image_urls = array();

			if (!empty($image_info['property'])) {
				foreach ($image_info['property'] as $images ) {
					if ( !empty($images) ) {
						foreach ( $images as $image ) {
							$image_urls['property'][] = $image_info['image_relative_path'].$image['large'];
						}
					}
				}
			}

			if (!empty($image_info['slideshow'])) {
				foreach ($image_info['slideshow'] as $images ) {
					if ( !empty($images) ) {
						foreach ( $images as $image ) {
							$image_urls['slideshow'][] = $image_info['image_relative_path'].$image['large'];
						}
					}
				}
			}

			$new_property = new stdclass();
						
			$new_property->property_details['property_id']				= $remote_property->propertys_uid;
			$new_property->property_details['remote_ptype_id']			= $remote_property->ptype_id;
			$new_property->property_details['local_ptype_id']			= $local_property_type;
			
			$new_property->property_details['name']						= $remote_property->property_name;

			$new_property->property_details['street']					= $remote_property->property_street;
			$new_property->property_details['postcode']					= $remote_property->property_postcode;
			$new_property->property_details['email']					= $remote_property->property_email;
			$new_property->property_details['tel']						= $remote_property->property_tel;
			$new_property->property_details['licensenumber']			= $remote_property->permit_number;
			$new_property->property_details['lat']						= $remote_property->lat;
			$new_property->property_details['long']						= $remote_property->long;
			$new_property->property_details['image_urls']				= $image_urls;
			
			$new_property->property_details['property_checkin_times']	= $remote_property->property_checkin_times;
			
			$new_property->property_details['property_description']		= $remote_property->property_description;

			// Get the deposit type
			$remote_deposit_type = $remote_server_communication->communicate( "GET" , '/cmf/property/deposit/type/'.$remote_property_id  , [] , true );

			if ( !isset($remote_deposit_type) || (int)$remote_deposit_type == 0 ) {
				throw new Exception( "Cannot determine deposit type" );
			}

			$new_property->deposits['remote_deposit_type_id']			= $remote_deposit_type;

			// Get the deposit value
			$remote_settings = $remote_server_communication->communicate( "GET" , '/cmf/property/settings/'.$remote_property_id  , [] , true );

			if ( !isset($remote_settings) || !isset( $remote_settings->depositValue ) ) {
				throw new Exception( "Cannot determine deposit value" );
			}

			$new_property->deposits['remote_deposit_value'] 			= $remote_settings->depositValue ;


			$new_property->property_details['max_guests']				= $max_guests_in_property;

			// Not supported (yet?)
			//$new_property->remote_room_features						= $property_room_features;
			$new_property->remote_property_features						= $property_features;


			// New we'll pull location information for this property. The location/information endpoint will try to fuzzy guess the region id if it can't find an exact match
			$response_location_information = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'GET' , 'cmf/location/information/'.$new_property->property_details['lat'].'/'.$new_property->property_details['long'].'/' );

			if (!isset($response_location_information->data->response->country_code) || trim($response_location_information->data->response->country_code) == '' ) {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_COUNTRY_CODE_NOT_FOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_COUNTRY_CODE_NOT_FOUND',false) );
			}

			if (!isset($response_location_information->data->response->region_id) || trim($response_location_information->data->response->region_id) == '' ) {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REGION_ID_NOT_FOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_REGION_ID_NOT_FOUND',false) );
			}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Ok, we've collected the information we need to start building our property from the available information from the channel, let's start creating our new property

		$new_property_basics_array =  array (
				"property_name" => $new_property->property_details['name'] , 
				"remote_uid" => $new_property->property_details['property_id'] ,  
				"country" => $response_location_information->data->response->country_code ,  
				"region_id" => $response_location_information->data->response->region_id ,  
				"ptype_id" => $local_property_type
				);

			// Create the new property 
			$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'POST' , 'cmf/property/' , $new_property_basics_array );

			if (!isset($response->data->response)) {
				throw new Exception( "response->data->response not set, failed to create property." );
			}
			
			$new_property_id = $response->data->response;

			set_showtime('new_property_id' , $new_property_id );
			
			// Management url
			$data_array = array (
				"property_uid"			=> $new_property_id,  
				"management_url"			=> $remote_url.'/index.php?option=com_jomres&task=dashboard&thisProperty='. $new_property_id
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/management/url' , $data_array );
			
			
			if ( !empty($new_property->property_details['image_urls']) ) {
				foreach ($new_property->property_details['image_urls'] as $image_url ) {
					if ( isset($new_property->property_details['image_urls']['property'])) {
						foreach ($new_property->property_details['image_urls']['property'] as $image_url ) {
							channelmanagement_framework_utilities :: get_image ( $image_url ,$new_property_id , 'property' , 0 );
						}
					}

					if ( isset($new_property->property_details['image_urls']['slideshow'])) {
						foreach ($new_property->property_details['image_urls']['slideshow'] as $image_url ) {
							channelmanagement_framework_utilities :: get_image ( $image_url ,$new_property_id , 'slideshow' , 0 );
						}
					}
				}
			}
			
			if ($new_property_id < 1 ) {
				throw new Exception( "Did not receive new property uid, failed to create property." );
			}
			
			// Check and create settings
			$settings = array (
				"property_currencycode"		=> $remote_settings->property_currencycode, // The property's currency code
				"singleRoomProperty"		=> $mrp_or_srp, // Is the property an MRP or an SRP?
				"tariffmode" 				=> '2'  // Micromanage automatically
			);
			
			// Room prices
			
			jr_import('channelmanagement_jomres2jomres_import_prices');

			// $mrp_or_srp
			foreach ($property_room_types as $room_type ) {

				// First we need rooms, once they are added we can create tariffs
				foreach ( $room_types as $room_type ) {

					$data_array = array (
						"property_uid"	=> $new_property_id,
						"rooms"			=> json_encode( array( json_decode($room_type)))
					);

					$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/rooms/' , $data_array );

					$rt = json_decode($room_type);

					// now that we have rooms in the system, we can set the base price for each room type
					channelmanagement_jomres2jomres_import_prices::import_prices( $JRUser->id , $channel , $remote_property_id , $new_property_id , $max_guests_in_property , $rt->amenity->jomres_id );
				}

			}
			
			/* 
			<DepositType DepositTypeID="1">No deposit</DepositType>
			<DepositType DepositTypeID="2">Percentage of total price (without cleaning)</DepositType>
			<DepositType DepositTypeID="3">Percentage of total price</DepositType>
			<DepositType DepositTypeID="4">Fixed amount per day</DepositType>
			<DepositType DepositTypeID="5">Flat amount per stay</DepositType> 
			*/



			$deposit_type	= $new_property->deposits['remote_deposit_type_id'];
			$deposit_value	= $new_property->deposits['remote_deposit_value'] ;
			
			switch ($deposit_type) {
				case 1:
					$settings['chargeDepositYesNo'] = "0";
					break;
				case 2:
					$settings['depositIsPercentage'] = "1";
					$settings['depositValue'] = $deposit_value;
					break;
				case 3:
					$settings['depositIsPercentage'] = "1";
					$settings['depositValue'] = $deposit_value;
					break;
				case 4:
					$settings['depositIsPercentage'] = "0"; // Type 4 in Jomres is not supported, so we will go with fixed amount per stay instead
					$settings['chargeDepositYesNo'] = $deposit_value;
					break;
				case 5:
					$settings['depositIsPercentage'] = "0";
					$settings['chargeDepositYesNo'] = $deposit_value;
					break;
			}
			
			$post_data = array ( "property_uid"		=> $new_property_id , "params" => json_encode($settings) ); // mrConfig array values are property specific settings
			$response_validated = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'POST' , 'cmf/property/validate/settings/keys' , $post_data );
			
			if (!isset($response_validated->data->response->valid) || $response_validated->data->response->valid == false ) {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_VALIDATE_SETTINGS_FAILED','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_VALIDATE_SETTINGS_FAILED',false) );
			}

			if ($response_validated == true) {
				$response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/settings' , $post_data );
			}

			// Now that we have a new property setup, let's start adding it's various information items.
			
			// Location
			$data_array = array (
				"property_uid"		=> $new_property_id,  
				"country_code"		=> $new_property_basics_array['country'],
				"region_id" 		=> $new_property_basics_array['region_id'],
				"lat" 				=> $new_property->property_details['lat'],
				"long" 				=> $new_property->property_details['long'],
				
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/location/' , $data_array );
			
			// Contacts
			$data_array = array (
				"property_uid"		=> $new_property_id,  
				"telephone"			=>$new_property->property_details['tel'],
				"fax" 				=> '',
				"email" 			=> $new_property->property_details['email']
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/contacts/' , $data_array );

			// Descriptive texts
			$data_array = array (
				"property_uid"			=> $new_property_id,  
				"description"			=> $new_property->property_details['property_description'],
				"checkin_times" 		=> $new_property->property_details['property_checkin_times'],
				"area_activities" 		=> '',
				"driving_directions"	=> '',
				"airports" 				=> '',
				"othertransport" 		=> '',
				"terms" 				=> '',
				"fax" 					=> '',
				"permit_number" 		=> $new_property->property_details['licensenumber']
			);

			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/text/' , $data_array );
			
			// Address
			$data_array = array (
				"property_uid"			=> $new_property_id,  
				"house"			=> $new_property_basics_array['property_name'],  // The RU data I'm working with doesn't have address details, so to prevent the system from complaining that the property address details are incomplete, we'll set this to blank for now
				"street" 		=> ' ',
				"town" 			=> ' ',
				"postcode"		=> ' '
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/address/' , $data_array );

			// Stars
			$data_array = array (
				"property_uid"			=> $new_property_id,  
				"stars"			=> 0,
				"superior" 		=> 0
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/stars/' , $data_array );
			
			// Property Features
			$features_str = '';
			if (!empty($property_features)) {
				foreach ( $property_features as $feature ) {
					$features_str .= $feature->jomres_id.",";
				}
			}
			
			$data_array = array (
				"property_uid"			=> $new_property_id,  
				"features"			=> $features_str
			);
			$channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/features/' , $data_array );
			
			// Publishing

			// We need to force a status review, where the system will see if the property is complete. If it is, we can publish it

			$data_array = array (
				"property_uid"			=> $new_property_id
			);

			$property_status_review_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/status/review/', $data_array );

			if ($property_status_review_response->data->response->property_complete == true ) {

				$property_status_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'GET' , 'cmf/property/status/'.$new_property_id , array() );

				if (isset($property_status_response->data->response) && (int)$property_status_response->data->response->status_code == 2 ) {
					$data_array = array (
						"property_uid"			=> $new_property_id
					);
					$property_publish_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/publish' , $data_array );
				}

				return (object) array ( "success" => true , "new_property_id" =>  $new_property_id );
			} else {
				return (object) array ( "success" => false , "message" =>  "Property appeared to be created but is incomplete." );
			}
		}

	
	
 	// Pass the OTA prop type, 
	public static function get_property_type_booking_model( $local_property_type = 0 )
	{

		if ( $local_property_type == 0 ) {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND',false) );
		}
		
		jr_import('jomres_property_types');
		$jomres_property_types = new jomres_property_types();
		$jomres_property_types->get_all_property_types();
		
		if (array_key_exists( $local_property_type , $jomres_property_types->property_types ) ) {
			if (isset($jomres_property_types->property_types [$local_property_type]['mrp_srp_flag'])) {
				return $jomres_property_types->property_types [$local_property_type]['mrp_srp_flag']; // 0 = mrp 1 = srp
			} else {
				throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_BOOKING_MODEL_NOT_FOUND_IN_PROPERTY_TYPE','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_BOOKING_MODEL_NOT_FOUND_IN_PROPERTY_TYPE',false) );
			}
		} else {
			throw new Exception( jr_gettext('CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND','CHANNELMANAGEMENT_JOMRES2JOMRES_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND',false) );
		}
	
	}
}

