<?php
/**
* Shortcode which shows event data on single event page
*/
function mp_events_calendar_shortcode_insert(){
	
	//Set args for new shortcode for events calendar
	$args = array(
		'shortcode_id' => 'mp_calendar',
		'shortcode_title' => __('Calendar', 'mp_events'),
		'shortcode_description' => __( 'Use the form below to insert the shortcode for a Slider ', 'mp_events' ),
		'shortcode_options' => array(
			array(
				'option_id' => 'source',
				'option_title' => 'Calendar',
				'option_description' => 'Choose a Calendar to show',
				'option_type' => 'select',
				'option_value' => mp_core_get_all_terms_by_tax('mp_calendars'),
			)
		)
	); 
	
	//Shortcode args filter - This will be used by addons to include slider source options like "downloads" (CPT) or "stack groups" (Taxonomy)
	$args = has_filter('mp_events_calendar_insert_shortcode_args') ? apply_filters('mp_events_calendar_insert_shortcode_args', $args) : $args;
	
	new MP_CORE_Shortcode_Insert($args);	
}
//add_action( 'init', 'mp_events_calendar_shortcode_insert' );