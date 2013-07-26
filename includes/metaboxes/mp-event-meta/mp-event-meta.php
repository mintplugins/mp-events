<?php
/**
 * Function which creates new Meta Box
 *
 */
function mp_events_create_meta_box(){	
	/**
	 * Array which stores all info about the new metabox
	 *
	 */
	$mp_events_add_meta_box = array(
		'metabox_id' => 'mp_events_metabox', 
		'metabox_title' => __( 'Event Data', 'mp_events'), 
		'metabox_posttype' => 'mp_event', 
		'metabox_context' => 'advanced', 
		'metabox_priority' => 'low' 
	);
	
	//Get all Timezones
	$timezone_identifiers = DateTimeZone::listAbbreviations();
	
	//Default empty option at the top 
	$timezone_select_array[NULL] = 'None';
	foreach( $timezone_identifiers as $abbr => $timezone ){
		$timezone_select_array[strtoupper($abbr)] = $timezone[0]['timezone_id'];
	
	}
		
	/**
	 * Array which stores all info about the options within the metabox
	 *
	 */
	$mp_events_items_array = array(
		array(
			'field_id'			=> 'event_start_date',
			'field_title' 	=> __( 'Event Start Date  (Required)', 'mp_events'),
			'field_description' 	=> 'The date of this event. Format: yyyy-mm-dd',
			'field_type' 	=> 'date',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_start_time',
			'field_title' 	=> __( 'Event Start Time (Required)', 'mp_events'),
			'field_description' 	=> 'The start time for this event. Format: 05:30:PM',
			'field_type' 	=> 'time',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_end_time',
			'field_title' 	=> __( 'Event End Time', 'mp_events'),
			'field_description' 	=> 'The end time for this event. Format: 05:30:PM',
			'field_type' 	=> 'time',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_time_zone',
			'field_title' 	=> __( 'Event Time Zone', 'mp_events'),
			'field_description' 	=> 'The Time Zone for this event.',
			'field_type' 	=> 'select',
			'field_value' => '',
			'field_select_values' => $timezone_select_array
		),
		array(
			'field_id'			=> 'event_repeat',
			'field_title' 	=> __( 'Event Repeat', 'mp_events'),
			'field_description' 	=> 'Does this event repeat?',
			'field_type' 	=> 'select',
			'field_value' => '',
			'field_select_values' => array( 'none' => 'None', 'daily' => 'Every Day', 'weekly' => 'Every Week', 'monthly' => 'Every Month', 'yearly' => 'Every Year' )
		),
		array(
			'field_id'			=> 'event_location_name',
			'field_title' 	=> __( 'Location Name', 'mp_events'),
			'field_description' 	=> 'The name of location of this event.',
			'field_type' 	=> 'textbox',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_street_address',
			'field_title' 	=> __( 'Street Address', 'mp_events'),
			'field_description' 	=> 'The street address of this event.',
			'field_type' 	=> 'textbox',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_city_country',
			'field_title' 	=> __( 'City/Country', 'mp_events'),
			'field_description' 	=> 'EG: Toronto, Canada',
			'field_type' 	=> 'textbox',
			'field_value' => ''
		),
		array(
			'field_id'			=> 'event_map_url',
			'field_title' 	=> __( 'Map URL', 'mp_events'),
			'field_description' 	=> 'Enter a link to a map URL (EG: Google Maps)',
			'field_type' 	=> 'url',
			'field_value' => ''
		),
		
	);
	
	
	/**
	 * Custom filter to allow for add-on plugins to hook in their own data for add_meta_box array
	 */
	$mp_events_add_meta_box = has_filter('mp_events_meta_box_array') ? apply_filters( 'mp_events_meta_box_array', $mp_events_add_meta_box) : $mp_events_add_meta_box;
	
	/**
	 * Custom filter to allow for add on plugins to hook in their own extra fields 
	 */
	$mp_events_items_array = has_filter('mp_events_items_array') ? apply_filters( 'mp_events_items_array', $mp_events_items_array) : $mp_events_items_array;
	
	
	/**
	 * Create Metabox class
	 */
	global $mp_events_meta_box;
	$mp_events_meta_box = new MP_CORE_Metabox($mp_events_add_meta_box, $mp_events_items_array);
}
add_action('plugins_loaded', 'mp_events_create_meta_box');