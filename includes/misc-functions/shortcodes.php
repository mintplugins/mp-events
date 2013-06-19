<?php
/**
* Shortcode which shows event data on single event page
*/
function mp_events_single_event_shortcode(){
	
	global $post;
	
	$post_id = get_the_ID();
	
	$event_end_time = date('g:i A', strtotime( get_post_meta( $post_id, 'event_end_time', true ) ) );
	
	$output_html = '<div class="mp-events-holder">';
	
	$output_html .= '<ul class="mp-events-ul">';
		
		$output_html .=  '<li class="mp-events-li">' . get_the_date('D, F j, Y') . ' @ ' .  get_the_date('g:i A') .  ' - ' . $event_end_time . '</li>';
		
		$output_html .=  '<li class="mp-events-li"><a target="_blank" href="' . get_post_meta( $post_id, 'event_map_url', true ) . '">' . get_post_meta( $post_id, 'event_location_name', true ) .', ' . get_post_meta( $post_id, 'event_city_country', true ) . '</a></li>';
			
	$output_html .= '</ul>';
	
	$output_html .= '</div>';
		
	return $output_html;
}
add_shortcode( 'mp_event', 'mp_events_single_event_shortcode' );