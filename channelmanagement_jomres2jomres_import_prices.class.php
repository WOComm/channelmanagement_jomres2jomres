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

        jr_import('channelmanagement_jomres2jomres_communication');
        $channelmanagement_jomres2jomres_communication = new channelmanagement_jomres2jomres_communication();


		$remote_prices = $channelmanagement_jomres2jomres_communication->communicate( 'GET' , 'cmf/property/list/prices/'.$remote_property_id ,  array() , true  );



		$primary_price_set	= array();

		if ( !empty($remote_prices->data->response->tariff_sets)) {
			foreach ($remote_prices->data->response->tariff_sets as $tariff_set ) {
				$basic_post_data = array (
					"property_uid"					=> $property_uid ,
					"tarifftypeid"					=> 0 , // Create a new micromanage tariff
					"rate_title"					=> "Tariff" ,
					"rate_description"				=> "Tariff description" ,
					"maxdays"						=> 364 ,
					"roomclass_uid"					=> $room_type_id ,
					"dayofweek"						=> 7 , // Every day
					"ignore_pppn"					=> 0 , // Ignore per person per night flag in property config set to No.
					"allow_we"						=> 1 , // Allow bookings to span weekends
					"weekendonly"					=> 0 , // Bookings for this tariff only allowed if all days in the booking are on the weekend = No
					"minrooms_alreadyselected"		=> 0 , // Specialised setting, do not change unless you understand the consequences
					"maxrooms_alreadyselected"		=> 1000 , // Specialised setting, do not change unless you understand the consequences


				);
				$channelmanagement_framework_singleton = jomres_singleton_abstract::getInstance('channelmanagement_framework_singleton');

				if (!empty($tariff_set)) {
					$post_data =$basic_post_data;
					$counter = 0;
					foreach ($tariff_set as $key => $vals ) {
						$post_data["tariffinput"][$key] = $vals['price'];
						$post_data["mindaysinput"][$key] = $vals['mindays'];
						$post_data['minpeople'] = $vals['minpeople'];
						$post_data['maxpeople'] = $vals['maxpeople'];
						$counter++;
						if ($counter == 365 ) {
							break;
						}
					}

					$primary_tariff_response = $channelmanagement_framework_singleton->rest_api_communicate( $channel , 'PUT' , 'cmf/property/tariff/' , $post_data );

			}
			
			

			


			}
		}
	}
	
	

}

