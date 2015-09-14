<?php
/**
 * This file contains the function keeps the MP Events plugin up to date.
 *
 * @since 1.0.0
 *
 * @package    MP Events
 * @subpackage Functions
 *
 * @copyright  Copyright (c) 2015, Mint Plugins
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @author     Philip Johnston
 */
 
/**
 * Check for updates for the MP Events Plugin by creating a new instance of the MP_CORE_Plugin_Updater class.
 *
 * @access   public
 * @since    1.0.0
 * @return   void
 */
 if (!function_exists('mp_events_update')){
	function mp_events_update() {
		$args = array(
			'software_name' => 'MP Events', //<- The exact name of this Plugin. Make sure it matches the title in your mp_repo, edd, and the WP.org repo
			'software_api_url' => 'http://mintplugins.com',//The URL where EDD and mp_repo are installed and checked
			'software_filename' => 'mp-events.php',
			'software_licensed' => false, //<-Boolean
		);
		
		//Since this is a plugin, call the Plugin Updater class
		$mp_events_plugin_updater = new MP_CORE_Plugin_Updater($args);
	}
 }
add_action( 'init', 'mp_events_update' );
