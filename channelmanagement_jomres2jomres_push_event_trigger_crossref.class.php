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
* Returns an array of Jomres webhooks and the Jomres2jomres scripts that will be called when those webhooks have been triggered
* For completion I'll record most webhook events as empty for now.
*
* Where array elements have more than one item, then the index is used to group webhook events together so that the parent can handle the element's webhook (e.g. booking_added can also handle blackbooking_added)
*
*/

class channelmanagement_jomres2jomres_push_event_trigger_crossref
{
    function __construct()
	{
		$this->events = array (
			'blackbooking_added'		=> [  ],
			'blackbooking_deleted'		=> [  ],
			'booking_added'				=> [ 'booking_added' , 'blackbooking_added'],
			'booking_cancelled'			=> [  ],
			'booking_marked_noshow'		=> [  ],
			'booking_modified'			=> [  ],
			'booking_note_deleted'		=> [  ],
			'booking_note_saved'		=> [  ],
			'changeover_day_enabled'	=> [  ],
			'changeover_day_disabled'	=> [  ],
			'coupon_saved'				=> [ 'coupon_saved' , 'coupon_updated' ],
			'coupon_deleted'			=> [  ],
			'deposit_saved'				=> [  ],
			'extra_deleted'				=> [  ],
			'extra_saved'				=> [  ],
			'guest_checkedin'			=> [  ],
			'guest_checkedin_undone'	=> [  ],
			'guest_checkedout'			=> [  ],
			'guest_checkedout_undone'	=> [  ],
			'guest_deleted'				=> [  ],
			'guest_saved'				=> [  ],
			'guest_type_deleted'		=> [  ],
			'guest_type_saved'			=> [  ],
			'image_added'				=> [  ],
			'image_deleted'				=> [  ],
			'invoice_created'			=> [  ],
			'invoice_cancelled'			=> [  ],
			'invoice_saved'				=> [  ],
			'property_added'			=> [  ],   
			'property_deleted'			=> [ 'property_deleted' ],
			'property_published'		=> [  ],
			'property_settings_updated'	=> [  ],
			'plugin_settings_saved'		=> [  ],
			'property_unpublished'		=> [  ],
			'property_approved'			=> [  ],
			'property_unapproved'		=> [  ],
			'property_updated'			=> [  ],
			'property_completed'		=> [  ],
			'property_incompleted'		=> [  ],
			'review_deleted'			=> [  ],
			'review_published'			=> [  ],
			'review_saved'				=> [  ], 
			'review_unpublished'		=> [  ],
			'room_added'				=> [  ],
			'room_deleted'				=> [  ],
			'room_updated'				=> [  ],
			'rooms_multiple_added'		=> [  ],
			'tariff_cloned'				=> [  ],
			'tariffs_updated'			=> [  ],

		);
	}

}




