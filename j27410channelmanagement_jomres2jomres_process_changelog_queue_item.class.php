<?php
/**
 * Core file.
 *
 * @author Vince Wooll <sales@jomres.net>
 *
 * @version Jomres 9.8.21
 *
 * @copyright	2005-2017 Vince Wooll
 * Jomres (tm) PHP, CSS & Javascript files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project, and use it accordingly
 **/

// ################################################################
defined('_JOMRES_INITCHECK') or die('');
// ################################################################

/**
 * @package Jomres\Core\Minicomponents
 *
 * When a webhook is passed to the channel management framework the cmf webhook authmethod then hands the webhook to this script to allow this script to perform any actions required
 *
 */

class j27410channelmanagement_jomres2jomres_process_changelog_queue_item
{
    /**
     *
     * Constructor
     *
     * Main functionality of the Minicomponent
     *
     */

    public function __construct($componentArgs)
    {
        // Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
        $MiniComponents = jomres_singleton_abstract::getInstance('mcHandler');
        if ($MiniComponents->template_touch) {
            $this->template_touchable = false;
            return;
        }

		$ePointFilepath = get_showtime('ePointFilepath');


        $item = unserialize($componentArgs->item);

		if (isset($item->webhook_event) && $item->webhook_event != '' ) {
			$new_class_name = 'jomres2jomres_changelog_item_process_'.strtolower($item->webhook_event);
			if (file_exists( $ePointFilepath.$new_class_name.'.php') ) {
				require_once ($ePointFilepath.$new_class_name.'.php');
				if (class_exists($new_class_name)) {
					$thing_class_result = new $new_class_name($componentArgs);
					$this->retVals = $thing_class_result->success;
				} else {
					logging::log_message("Cannot process webhook ".$item->webhook_event." because no item processing task exists for the event.", 'CMF', 'INFO' , serialize($send_response) );
				}
			}
		}
		if (!isset($this->retVals)) {
			$this->retVals = false;
		}

    }

    public function getRetVals()
    {
        return $this->retVals;
    }
}
