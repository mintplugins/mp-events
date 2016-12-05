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
			
	/**
	 * Array which stores all info about the options within the metabox
	 *
	 */
	$mp_events_items_array = array(
		'event_start_date' => array(
			'field_id'			=> 'event_start_date',
			'field_title' 	=> __( 'Event Start Date  (Required)', 'mp_events'),
			'field_description' 	=> 'The date of this event. Format: yyyy-mm-dd',
			'field_type' 	=> 'date',
			'field_value' => '',
			'field_required' => true
		),
		'event_start_time' => array(
			'field_id'			=> 'event_start_time',
			'field_title' 	=> __( 'Event Start Time', 'mp_events'),
			'field_description' 	=> 'Enter a description for the start time. Can be multiple times if needed. EG: 9:00am, 10:30am, and 7:30PM EST.',
			'field_type' 	=> 'textarea',
			'field_value' => '',
		),
		'event_end_date' => array(
			'field_id'			=> 'event_end_date',
			'field_title' 	=> __( 'Event End Date', 'mp_events'),
			'field_description' 	=> 'The date when this event ends. Format: yyyy-mm-dd',
			'field_type' 	=> 'date',
			'field_value' => '',
		),
		'event_end_time' => array(
			'field_id'			=> 'event_end_time',
			'field_title' 	=> __( 'Event End Time', 'mp_events'),
			'field_description' 	=> 'Enter a description for the end time.',
			'field_type' 	=> 'textarea',
			'field_value' => '',
		),
		'event_repeat' => array(
			'field_id'			=> 'event_repeat',
			'field_title' 	=> __( 'Event Repeat', 'mp_events'),
			'field_description' 	=> 'Does this event repeat?',
			'field_type' 	=> 'select',
			'field_value' => '',
			'field_select_values' => array( 'none' => 'None', 'daily' => 'Every Day', 'weekly' => 'Every Week', 'monthly' => 'Every Month', 'yearly' => 'Every Year' )
		),
		'event_repeat_end_date' => array(
			'field_id'			=> 'event_repeat_end_date',
			'field_title' 	=> __( 'Event Repeat End Date', 'mp_events'),
			'field_description' 	=> 'When does this event stop repeating? Leave blank for infinite repeating.',
			'field_type' 	=> 'date',
			'field_value' => '',
			'field_conditional_id' => 'event_repeat',
			'field_conditional_values' => array( 'daily', 'weekly', 'monthly', 'yearly' ),
		),
		'event_address' => array(
			'field_id'			=> 'event_address',
			'field_title' 	=> __( 'Address', 'mp_events'),
			'field_description' 	=> 'The address of this event.',
			'field_type' 	=> 'textarea',
			'field_value' => ''
		),
		'event_map_url' => array(
			'field_id'			=> 'event_map_url',
			'field_title' 	=> __( 'Map URL', 'mp_events'),
			'field_description' 	=> 'Enter a link to a map URL (EG: Google Maps)',
			'field_type' 	=> 'url',
			'field_value' => ''
		),
		'event_video' => array(
			'field_id'			=> 'event_video',
			'field_title' 	=> __( 'Event Video?', 'mp_events'),
			'field_description' 	=> 'Do you have a video for this event that you\'d like to use instead of the Event Featured Image? Enter embed code or a link to YouTube/Vimeo.',
			'field_type' 	=> 'textarea',
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