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

/**
 * Get the MP Event Start Date from either the URL or the post itself - depending on what is available
 *
 * @since    1.0.0
 * @link     http://mintplugins.com/doc/
 * @see      function_name()
 * @param    $post_id - The id of the mp_event post in question for which we want the event date.
 * @return   The date string formatted.
 */
function mp_events_get_mp_event_start_date( $post_id ){

	$date_format = get_option( 'date_format' );

	//If there is a date in the URL - get it from there.
	if ( isset( $_GET['mp_event_start_date'] ) ){
		//Get the date from the URL
		$url_date = urldecode($_GET['mp_event_start_date']);
		$event_start_date = mp_events_format_mp_event_date( $url_date );
	}
	//Otherwise get the date from the post meta
	else{
		$event_start_date = mp_events_format_mp_event_date( get_post_meta( $post_id, 'event_start_date', true ) );
	}

	return $event_start_date;
}

/**
 * Get the MP Event End Date from either the URL or the post itself - depending on what is available
 *
 * @since    1.0.0
 * @link     http://mintplugins.com/doc/
 * @see      function_name()
 * @param    $post_id - The id of the mp_event post in question for which we want the event date.
 * @return   The date string formatted.
 */
function mp_events_get_mp_event_end_date( $post_id ){

	$date_format = get_option( 'date_format' );

	//If there is a date in the URL - get it from there.
	if ( isset( $_GET['mp_event_end_date'] ) ){
		//Get the date from the URL
		$url_date = urldecode($_GET['mp_event_end_date']);
		$event_end_date = mp_events_format_mp_event_date( $url_date );
	}
	//Otherwise get the date from the post meta
	else{
		$event_end_date = mp_events_format_mp_event_date( get_post_meta( $post_id, 'event_end_date', true ) );
	}

	return $event_end_date;
}

/**
 * Format the MP Event Date
 *
 * @since    1.0.0
 * @link     http://mintplugins.com/doc/
 * @see      function_name()
 * @param    $start_or_end_date_string - The start date string we wish to format according the to WP date format settings.
 * @return   The date string formatted.
 */
function mp_events_format_mp_event_date( $start_or_end_date_string ){

	$date_format = get_option( 'date_format' );

	$formatted_date = date($date_format, strtotime( $start_or_end_date_string ) );

	return $formatted_date;
}

/**
 * Typically MP Events are viewed using something like EventGrid my Mint Plugins. The permalink, while useful for some, is not relevant for most.
 * Since the basic permalink is not typically correct for event dates, we hide the permalink sample from the user here.
 *
 * @since    1.0.0
 * @link     http://mintplugins.com/doc/
 * @see      function_name()
 * @param    $permalink_html
 * @param    $post_id
 * @param    $new_title
 * @param    $new_slug
 * @param    $post
 * @return   $permalink_html
 */
function mp_events_remove_sample_permalink_html( $permalink_html, $post_id, $new_title, $new_slug, $post ){

	// Check the post type
	if ( $post->post_type == 'mp_event' ){
		return '';
	}

	return $permalink_html;

}
add_filter( 'get_sample_permalink_html', 'mp_events_remove_sample_permalink_html', 10, 5 );

/**
 * Typically MP Events are viewed using something like EventGrid my Mint Plugins. The admin bar "view" button, while useful for some, is not relevant for most.
 * Since the basic permalink is not typically correct for event dates, we hide the "view event" admin bar button from the user here.
 *
 * @since    1.0.0
 * @link     http://mintplugins.com/doc/
 * @see      function_name()
 * @param    $permalink_html
 * @return   void
 */
function mp_events_remove_view_event( $wp_admin_bar ) {
	global $post;

	if ( isset( $post->post_type ) && $post->post_type == 'mp_event' ){
		$wp_admin_bar->remove_node( 'view' );
	}
}
add_action( 'admin_bar_menu', 'mp_events_remove_view_event', 999 );


/**
 * Retrieve timezone
 *
 * @since 1.0.0
 * @return string $timezone The timezone ID
 */
function mp_events_get_timezone_id() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) )
		return $timezone;

	// get UTC offset, if it isn't set return UTC
	if ( ! ( $utc_offset = 3600 * get_option( 'gmt_offset', 0 ) ) )
		return 'UTC';

	// attempt to guess the timezone string from the UTC offset
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	// last try, guess timezone string manually
	if ( $timezone === false ) {

		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst &&  $city['offset'] == $utc_offset )
					return $city['timezone_id'];
			}
		}
	}

	// fallback
	return 'UTC';
}
