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

class plugin_info_channelmanagement_jomres2jomres
	{
	function __construct()
		{
		$this->data=array(
			"name"=>"channelmanagement_jomres2jomres",
			"category"=>"Integration",
			"marketing"=>"Plugin that allows child installations of Jomres to import properties from parent installations",
			"version"=>(float)"0.4",
			"description"=> "Thin plugin for the Jomres CMF that allows child installations of Jomres to import properties from parent installations",
			"lastupdate"=>"2020/05/20",
			"min_jomres_ver"=>"9.21.4",
			"manual_link"=>'',
			'change_log'=>'v0.2 Bunches of changes regarding booking export. v0.3 Property export improvements. v0.4 Added webhook processing',
			'highlight'=>'',
			'image'=>'',
			'demo_url'=>''
			);
		}
	}
