<?php
/**
* Filter function which inserts the shortcode into the content
*/
function mp_events_the_content_filter( $content ){
	
	//Main query (page if page.php, archive if archive.php etc)
	global $wp_query;
	
	//if this is a single page
	if ( is_single() ){
		
		//If this is a post_type
		if ( isset( $wp_query->query['post_type'] ) ) {
		
			//If that post type is mp_event
			if( $wp_query->query['post_type'] == 'mp_event' ) {
		
				//If this post's content does NOT contains the shortcode to show event info already
				if ( strpos( $content, '[mp_event]' ) === false  && !current_theme_supports( 'mp_events' )) {
					
					//attach the mp_event shortcode to this content	 
					 return mp_events_single_event_shortcode() . $content;
					 
				}
			}
		}
	
	}
	
	//if not a single events page
	return $content;	
	
		
}
add_filter( 'the_content', 'mp_events_the_content_filter' );