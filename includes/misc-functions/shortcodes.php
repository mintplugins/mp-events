<?php
/**
* Shortcode which shows event data on single event page
*/
function mp_events_single_event_shortcode(){
	
	global $post;
	
	$post_id = get_the_ID();
	
	//Set Timezone to last timezone on earth so events stay 
	date_default_timezone_set('America/New_York');
	
	//Check if the ?mp_event_date url variable is set
	if ( isset( $_GET['mp_event_date'] ) ) {
		
		//Get the date from the URL
		$url_date =  $_GET['mp_event_date'];
		echo $url_date;
		$url_date = strtotime(  $url_date );
		$event_start_date = mysql2date('D, F j, Y', $url_date );
				
	}
	//Otherwise get the date from the post meta
	else{
		
		$event_start_date = date('D, F j, Y', strtotime( get_post_meta( $post_id, 'event_start_date', true ) ) );
		
	}
	
	//Event start Time
	$event_start_time = date('g:i A', strtotime( get_post_meta( $post_id, 'event_start_time', true ) ) );
	
	//Event End Time
	$event_end_time = get_post_meta( $post_id, 'event_end_time', true ); 
	$event_end_time = !empty( $event_end_time ) ? date('g:i A', strtotime( $event_end_time ) ) : NULL;	
	
	//Map URL
	$event_map_url = get_post_meta( $post_id, 'event_map_url', true );
	
	//Location name
	$event_location_name = get_post_meta( $post_id, 'event_location_name', true );
	
	//Event City and Country
	$event_city_country = get_post_meta( $post_id, 'event_city_country', true );
	
	//Create output for shortcode
	$output_html = '<div class="mp-events-holder">';
	
	$output_html .= '<ul class="mp-events-ul">';
		
		//Start time
		$output_html .=  '<li class="mp-events-li">' . $event_start_date . ' @ ' .  $event_start_time;
		
		//End Time
		$output_html .= !empty( $event_end_time ) ? ' - ' . $event_end_time . '</li>' : '</li>';
		
		//Map and location
		if ( !empty( $event_location_name ) && !empty( $event_city_country ) ){
			
			//Map link available
			if ( !empty( $event_map_url ) ) { 
				$output_html .= '<li class="mp-events-li"><a target="_blank" href="' . $event_map_url . '">' . $event_location_name .', ' . $event_city_country . '</a></li>';
			} 
			//No map link available
			else{ 
				$output_html .= '<li class="mp-events-li">' . $event_location_name .', ' . $event_city_country . '</li>' ;
			}
		}
			
	$output_html .= '</ul>';
	
	$output_html .= '</div>';
		
	//Return
	return $output_html;
}
add_shortcode( 'mp_event', 'mp_events_single_event_shortcode' );

/**
* Shortcode which shows event data on single event page
*/
function mp_events_calendar_shortcode( $atts ){
	
	global $wp_query;
		
	//shortcode vars passed-in
	$vars =  shortcode_atts( array('source' => NULL ), $atts );
	
	//Start Year
	$year = isset( $_GET['mp_events_year'] ) ? $_GET['mp_events_year'] : date( 'Y' );
	
	//Start Month
	$month = isset( $_GET['mp_events_month'] ) ? $_GET['mp_events_month'] : date( 'm' );
	
	//Start Day
	$day = isset( $_GET['mp_events_day'] ) ? $_GET['mp_events_day'] : 1;
	
	//Get current month from above settings
	$current_month = date( 'Y-m-1', ( strtotime( $year . '-' . $month . '-' . $day ) ) );
			
	//Set starting date to be the sunday before the first day of the month
	$current_day = date('Y-m-d', strtotime( 'last Sunday', strtotime( $current_month ) ) );
	
	//Get number of days from start of month to previous sunday
	$prevdatediff = strtotime ( $current_month ) - strtotime( $current_day );
	$prevdatediff = floor($prevdatediff/( 60*60*24 ) );
	
	//Last day of current month
	$last_day_of_current_month = date( 'Y-m-t', strtotime( $current_month ) );
		
	//Set starting date to be the sunday before the first day of the month
	$ending_day = date('Y-m-d', strtotime( 'next Saturday', strtotime( $last_day_of_current_month ) ) );
	
	//Get number of days from end of month to next saturday
	$afterdatediff = strtotime ( $ending_day ) - strtotime( $last_day_of_current_month );	
	$afterdatediff = floor( $afterdatediff/( 60*60*24 ) );
	
	//Get the number of days in this month plus the number of days in the previous week and the following week
	$days_in_month = date( 't', strtotime( $current_month ) ) + $prevdatediff + $afterdatediff;
	
	//Set args for new query
	$args = array(
		'mp_events_year' => date( 'Y', strtotime( $current_day ) ),
		'mp_events_day' =>  date( 'd', strtotime( $current_day ) ),
		'mp_events_month' =>  date( 'm', strtotime( $current_day ) ),
		'mp_events_days_per_page' => $days_in_month,
		'post_type' => "mp_event",
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' =>  'mp_calendars',
				'field'    => 'id',
				'terms'    => array( $vars['source'] ),
				'operator' => 'IN'
			)
		)
	);
	
	// New WP Query
	$calendar_posts = new WP_Query( $args );
	
	if ( $calendar_posts->have_posts() ) {
		
		//Link to previous month
		$output_html = '<a href="' . add_query_arg( array( 'mp_events_month' => date( 'm', strtotime( 'first day of last month', strtotime( $current_month ) ) ), 'mp_events_year' => date( 'Y', strtotime( 'first day of last month', strtotime( $current_month ) ) ) ), get_permalink() ) . '" >← </a>';
		
		//Show current month
		$output_html .= date( 'F Y', strtotime( $current_month ) );
		
		//Used in Link to next month
		$first_day_of_next_month = strtotime( 'first day of next month', strtotime( $current_month ) );
		
		//Link to next month
		$output_html .= '<a href="' . add_query_arg( array( 'mp_events_month' => date( 'm', $first_day_of_next_month ), 'mp_events_year' => date( 'Y', $first_day_of_next_month ) ), get_permalink() ) . '" > →</a>';
					 			
		//Set counter
		$counter = 0;
		
		//Create output for shortcode
		$output_html .= '<div class="mp-events-holder">';
		
		//Unordered list which holds calendar
		$output_html .= '<ul class="mp-events-ul">';
		
		//One iteration for each day of the month
		while ( $counter != $days_in_month ){
			
			//Set current time
			$current_time = strtotime($current_day);
			
			//Set weekday variable
			$weekday_name = strtolower( date( 'l', strtotime($current_day) ) );
			
			
			if ( $counter >= $prevdatediff && $counter < $days_in_month - $afterdatediff  ){		
				//New HTML for day box
				$li_class_output =  'mp-events-month-grid-li mp-events-' . $weekday_name . '';
			}
			else{
				//New HTML for day box
				$li_class_output =  'mp-events-month-grid-li mp-events-non-current-month mp-events-' . $weekday_name;

			}
			
			//First week class
			if ($current_time == strtotime( date( 'Y-m-d' ) ) ){
				$li_class_output .=  ' mp-events-today';
			}
			
			//Today's class
			if ($counter <= 6 ){
				$li_class_output .=  ' mp-events-first-week';
			}
			
			
			//New HTML for day box
			$output_html .=  '<li class="' . $li_class_output . '">';
			
			//If this is the first day of a month
			if ( date( 'j', strtotime( $current_day ) )  == 1 ){
				
				//SHow the name of the month and the day
				$output_html .= '<div class="mp-events-day-number">' . date( 'M j', strtotime( $current_day ) ) . '</div>';
				
			}
			else{
				
				//Otherwise just show the day
				$output_html .= '<div class="mp-events-day-number">' . date( 'j', strtotime( $current_day ) ) . '</div>';
			
			}
			
			//Loop through each day of the month in our custom query
			while ( $calendar_posts->have_posts() ) {
			
				$calendar_posts->the_post();
				
				//If the date of the event matches the date of the loops, show the event
				if ($current_day == get_the_date( 'Y-m-d' ) ) {
					$output_html .= '<a class="mp-events-event-link" href="' . get_permalink() . '">' . get_the_title() . '</a>';
						
				}
			}
							
			//End Month Grid
			$output_html .=  '</li>';
			
			//Add one day to the current day to prepare for the next iteration			
			$current_day = mp_events_add_date( $current_day, $day=1, $mth=0, $yr=0 );
			
			//Increment counter
			$counter = $counter+1;
			
		}
				
		$output_html .= '</ul>';
		
		$output_html .= '</div>';
		
	}
		
	//Return
	return $output_html;
}
add_shortcode( 'mp_calendar', 'mp_events_calendar_shortcode' );