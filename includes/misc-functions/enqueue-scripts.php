<?php
/**
* Shortcode which shows event data on single event page
*/
function mp_events_enqueue_scripts(){
	
	wp_enqueue_style( 'mp_events_style', plugins_url( '/css/mp-events-style.css', dirname( __FILE__ ) ) );
}
add_action( 'wp_enqueue_scripts', 'mp_events_enqueue_scripts' );