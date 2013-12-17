<?php			
/**
 * This is the code that will create a new tab of settings for your page.
 * To create a new tab and set up this page:
 * Step 1. Duplicate this page and include it in the "class initialization function".
 * Step 1. Do a find-and-replace for the term 'mp_events_settings' and replace it with the slug you set when initializing this class
 * Step 2. Do a find and replace for 'general' and replace it with your desired tab slug
 * Step 3. Go to line 17 and set the title for this tab.
 * Step 4. Begin creating your custom options on line 30
 * Go here for full setup instructions: 
 * http://moveplugins.com/settings-class/
 */

/**
* Create new tab
*/
function mp_events_settings_general_new_tab( $active_tab ){
	
	//Create array containing the title and slug for this new tab
	$tab_info = array( 'title' => __('Event Settings' , 'mp_events'), 'slug' => 'general' );
	
	global $mp_events_settings; $mp_events_settings->new_tab( $active_tab, $tab_info );
		
}
//Hook into the new tab hook filter contained in the settings class in the Move Plugins Core
add_action('mp_events_settings_new_tab_hook', 'mp_events_settings_general_new_tab');

/**
* Create settings
*/
function mp_events_settings_general_create(){
	
	//This variable must be the name of the variable that stores the class.
	global $mp_events_settings_class;
	
	register_setting(
		'mp_events_settings_general',
		'mp_events_settings_general',
		'mp_core_settings_validate'
	);
	
	add_settings_section(
		'general_settings',
		__( 'General Settings', 'mp_events' ),
		'__return_false',
		'mp_events_settings_general'
	);
	
	//Get all Timezones
	$timezone_identifiers = DateTimeZone::listAbbreviations();
	
	//Default empty option at the top 
	$timezone_select_array[NULL] = 'None';
	foreach( $timezone_identifiers as $abbr => $timezone ){
		$timezone_select_array[$timezone[0]['timezone_id']] = $timezone[0]['timezone_id'];
	
	}
	
	add_settings_field(
		'mp_events_default_timezone',
		__( 'Default Time Zone', 'mp_events' ), 
		'mp_core_select',
		'mp_events_settings_general',
		'general_settings',
		array(
			'name'        => 'mp_events_default_timezone',
			'value'       => mp_core_get_option( 'mp_events_settings_general',  'mp_events_default_timezone' ),
			'description' => __( 'When events have no timezone selected, this will be the timezone used.', 'mp_events' ),
			'registration'=> 'mp_events_settings_general',
			'options' => $timezone_select_array
		)
	);	
		
	//additional general settings
	do_action('mp_events_settings_additional_general_settings_hook');
}
add_action( 'admin_init', 'mp_events_settings_general_create' );