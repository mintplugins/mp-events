<?php
/**
* Order events baed on their set date in the event_start_date meta field
*/
function mp_events( $query ) {

	//If this is a post_type
	if ( isset( $query->query['post_type'] ) ) {

		//If that post type is mp_event
		if( $query->query['post_type'] == 'mp_event' ) {

			//Since $query->set is not working for secondary loops, we'll use a global variable for now :( <-- sad face
			global $actual_posts_per_page;

			//Set actual posts per page to posts_per_page for use later
			$actual_posts_per_page = $query->get( 'posts_per_page' );
			$actual_posts_per_page = !empty( $actual_posts_per_page ) ? $actual_posts_per_page  : get_option( 'posts_per_page' );

			//This part isn't working for secondary loops - so we are using a global variable as described above
			$query->set( 'actual_posts_per_page',  $actual_posts_per_page );

			//Set posts per page to unlimited - so that we loop through all WordPress created mp_event posts
			$query->set( 'posts_per_page', -1 );

			//Order by meta_key
			$query->set( 'orderby', 'meta_value' );

			//Set meta Key to start date
			$query->set( 'meta_key',  'event_start_date' );

			//Order by meta_key
			$query->set( 'order', 'ASC' );

			if ( !is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) ){

				//Make the filters not suppressed in the Wp_Query class
				$query->set('suppress_filters', false);

				//Set filter to create repeaters
				add_filter ( 'the_posts', 'mp_events_post' );

			}

			//Set our custom query to be the same as this query
			global $mp_events_custom_query;
			$mp_events_custom_query = $query;
		}

	}

	//if this is a mp_events taxonomy
	if ( isset( $query->query['mp_calendars'] ) ){

		//Since $query->set is not working for secondary loops, we'll use a global variable for now :( <-- sad face
		global $actual_posts_per_page;

		//Set posts per page to unlimited - so that we loop through all WordPress created mp_event posts
		$actual_posts_per_page = $query->get( 'posts_per_page' );
		$actual_posts_per_page = !empty( $actual_posts_per_page ) ? $actual_posts_per_page  : get_option( 'posts_per_page' );

		//This part isn't working for secondary loops - so we are using a global variable as described above
		$query->set( 'actual_posts_per_page',  $actual_posts_per_page );

		//Set posts per page to unlimited - so that we loop through all WordPress created mp_event posts
		$query->set( 'posts_per_page', -1 );

		//Order by meta_key
		$query->set( 'orderby', 'meta_value' );

		//Set meta Key to start date
		$query->set( 'meta_key',  'event_start_date' );

		//Order by meta_key
		$query->set( 'order', 'ASC' );

		if ( !is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) ){

			//Make the filters not suppressed in the Wp_Query class
			$query->set('suppress_filters', false);

			//Set filter to create repeaters
			add_filter ( 'the_posts', 'mp_events_post' );

		}

		//Set our custom query to be the same as this query
		global $mp_events_custom_query;
		$mp_events_custom_query = $query;
	}

	return;
}
add_action ( 'pre_get_posts', 'mp_events', 1 );

/**
* Filter which inserts pseudo events into posts array before "the loop"
*/
function mp_events_post( $mp_events ){

	//Make sure this doesn't affect other loops on this page
	remove_filter ( 'the_posts', 'mp_events_post' );

	//Main query (page if page.php, archive if archive.php etc)
	global $wp_query;

	//Events Query Only (If this is an archive page for events, this will match the wp_query var. If it is a custom query, it will not
	global $mp_events_custom_query;

	//Since $query->set is not working for secondary loops, we'll use a global variable for now :( <-- sad face
	global $actual_posts_per_page;

	//Set the date for single event pages to the URL passed date
	if ( is_single() && !isset( $wp_query->queried_object->post_type ) ){ //$wp_query->queried_object->post_type is NOT set on single event pages

		//Get date from URL
		$url_start_date = !empty( $_GET['mp_event_start_date'] ) ? $_GET['mp_event_start_date'] : $mp_events_custom_query->posts[0]->post_date;
		$url_end_date = !empty( $_GET['mp_event_end_date'] ) ? $_GET['mp_event_end_date'] : $mp_events_custom_query->posts[0]->post_date;
		$mp_events_custom_query->posts[0]->post_date = $url_start_date;
		$mp_events_custom_query->posts[0]->mp_events_end_date = $url_end_date;

		new mp_events_set_permalink_filter( array(
			'post_id' => $wp_query->queried_object_id,
			'force_date_override' => true

		) );


		return $mp_events;

	}
	elseif( count( $mp_events ) == 0 ){
		return $mp_events;
	}
	else{

		//Loop through mp_events - which is the passed in, processed post array
		foreach ( $mp_events as $key => $mp_event ){

			//Set Post ID
			$post_id = $mp_event->ID;

			//Set Post Date
			$post_date = get_post_meta( $post_id, 'event_start_date', true );

			//Get date of event
			$post_date = strtotime( $post_date );

			//Get Repeat Setting
			$event_repeat = get_post_meta( $post_id, 'event_repeat', true );

			//Set no repeat array
			if ( $event_repeat == 'none' ){

				//Set to name of day of month- EG "March 16, 1986"
				$post_date = date("Y_m_d", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset( $single_events[$post_date] ) ){  $single_events[$post_date] = array(); }

				array_push( $single_events[$post_date], $post_id );
			}

			//Set daily repeat array
			if ( $event_repeat == 'daily' ){

				if ( !isset( $daily_posts ) ){  $daily_posts = array(); }
				$daily_posts[$post_date][$post_id] = $post_id;

			}

			//Set weekly repeat array
			if ( $event_repeat == 'weekly' ){

				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($weekly_posts[$post_date] ) ){ $weekly_posts[$post_date] = array(); }

				//Push this post id into the array for this weedday
				$weekly_posts[$post_date][$post_id] = $post_id;
			}


			//Set fortnightly repeat array
			if ( $event_repeat == 'fortnightly' ){

				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($fortnightly_posts[$post_date] ) ){ $fortnightly_posts[$post_date] = array(); }

				//Push this post id into the array for this weedday
				$fortnightly_posts[$post_date][$post_id] = $post_id;

			}

			//Set monthly repeat array
			if ( $event_repeat == 'monthly' ){

				//Set to name of day of month- EG "01"
				$post_date = date("d", $post_date);

				//If this array has not yet been set up for this month day, set it up.
				if ( !isset($monthly_posts[$post_date] ) ){ $monthly_posts[$post_date] = array(); }

				//Push this post id into the array for this month day
				$monthly_posts[$post_date][$post_id] = $post_id;
			}

			//Set yearly repeat array
			if ( $event_repeat == 'yearly' ){

				//Set to name of day of month- EG "Dec 25"
				$post_date = date("M j", $post_date);

				//If this array has not yet been set up for this year day, set it up.
				if ( !isset($yearly_posts[$post_date] ) ){ $yearly_posts[$post_date] = array(); }

				//Push this post id into the array for this month day
				$yearly_posts[$post_date][$post_id] = $post_id;
			}


		//End Loop through mp_events
		}


		/**
		* Rebuild the posts array
		*/
		$rebuilt_posts_array = array();

		//Get year from the URL using the mp_events_custom_query
		$year = isset( $mp_events_custom_query->query_vars['mp_events_year'] ) ? $mp_events_custom_query->query_vars['mp_events_year'] : date('Y');

		//Get month from the URL using the mp_events_custom_query
		$month = isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? $mp_events_custom_query->query_vars['mp_events_month'] : date('m');

		//Set day from the URL using the mp_events_custom_query. If the month is set, set it to 1, if not, set it to yesterday
		$day = !isset( $mp_events_custom_query->query_vars['mp_events_day'] ) ? isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? 1 : date('d', strtotime('yesterday' ) ) : $mp_events_custom_query->query_vars['mp_events_day'];

		//Get page number - and make sure the page isn't anything higher than 500 - to keep servers from crashing
		$paged = $mp_events_custom_query->query_vars['paged'] > 500 ? 500 : $mp_events_custom_query->query_vars['paged'];

		//Set starting date
		$current_day = $year .'-' . $month . '-' . $day;

		//Set posts per page to number of days in current month, or to the number set in settings > reading
		if ( isset( $mp_events_custom_query->query_vars['mp_events_days_per_page'] ) ){

			$posts_per_page = $mp_events_custom_query->query_vars['mp_events_days_per_page'];

		}elseif ( isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ){

			$posts_per_page = date('t', strtotime( $current_day ) );

		}else{

			$posts_per_page = $actual_posts_per_page; //$mp_events_custom_query->query_vars['actual_posts_per_page']
		}

		//Set type of loop cut off
		$loop_cutoff_type = isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? 'days_per_page' : 'posts_per_page';

		//If the cutoff type has been passed to the query, use it, instead use the above setting
		$loop_cutoff_type = isset( $mp_events_custom_query->query_vars['mp_events_days_per_page'] ) ? 'days_per_page' : $loop_cutoff_type;

		//Get the offset from the query
		if ( isset( $mp_events_custom_query->query['offset'] ) ){
			$offset = $mp_events_custom_query->query['offset'];
		}
		else{
			$offset = 0;
		}

		/**
		* If there are NO repeating posts in this query
		* Loop through posts
		*/
		if ( empty( $yearly_posts ) &&  empty( $monthly_posts ) && empty( $weekly_posts ) && empty( $daily_posts ) ) {

			//Loop through each post
			foreach ( $mp_events as $mp_event ){

				//get start date of this post
				$start_date = strtotime( get_post_meta( $mp_event->ID, 'event_start_date', true ) );

				//If this event is in the future according to "yesterday" and this is a posts per page
				if ( $start_date > strtotime( 'yesterday' ) ){

					$this_event = mp_events_modify_event( $mp_event, date( 'Y-m-d', $start_date ), $loop_cutoff_type );

					//Add this post into the return array of posts to show
						array_push( $rebuilt_posts_array, $this_event['event'] );

				}
			}

			//Set number of pages
			$mp_events_custom_query->max_num_pages = ceil( count( $rebuilt_posts_array ) / $posts_per_page );

			//Offset posts based on page number and amount of posts per page
			$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $offset, $posts_per_page );

			//Return
			return $rebuilt_posts_array;

		}

		/**
		* If we made it this far, there ARE repeating posts in this query
		*/

		//Set offset from the URL using the mp_events_custom_query.
		$day_offset = $paged != 0 ? $paged * $posts_per_page - $posts_per_page  : 0;

		//Add day offset to posts per page - we will subtract the day offset from the array after it is built (below the loop)
		$posts_per_page = $posts_per_page + $day_offset;

		//Set counter
		$counter = 0;

		//Get the offset from the query
		if ( isset( $mp_events_custom_query->query['offset'] ) ){
			$offset = $mp_events_custom_query->query['offset'];
		}
		else{
			$offset = 0;
		}

		//Get the time for "yesterday"
		$utc_yesterday = strtotime("yesterday");

		$total_loops_needed = $offset + $posts_per_page;

		// Prevent infinite loops
		if ( empty( $total_loops_needed ) || $total_loops_needed > 9999 ){
			$total_loops_needed = 0;
		}

		/**
		* If there ARE repeating posts in this query
		* Loop through dates starting at current_day (set above)
		*/
		while ( $counter < $total_loops_needed ){

			//Add correct amount to counter based on cutoff type
			$counter = $loop_cutoff_type == 'days_per_page' ? $counter + 1 : ( count( $rebuilt_posts_array ) - $offset );

			$current_time = strtotime($current_day);

			$full_date = date("Y_m_d", $current_time);
			$day_of_week = date("l", $current_time); //Monday
			$day_of_month = date("d", $current_time); //01
			$day_of_month_of_year = date("M j", $current_time); //01

			//Add all no repeat posts
			//If there are any one-off posts
			if ( !empty( $single_events ) ) {

				//Loop through each single event
				foreach ($single_events as $fulldate_num => $single_events_array){
					//If this fulldate_num is the same as the current day we are looping through
					if ( $fulldate_num == $full_date ){

						//Loop through each post set for this full_date
						foreach( $single_events_array as $single_event ){

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $single_event, $current_day, $loop_cutoff_type );

							//Add this event to the array if it isn't returned as NULL
							if ( isset( $this_event['failure'] ) ) {

								if ( 'single_event_has_ended' == $this_event['failure_id'] ){
									//Remove this event from the list of single events since it is in the past
									unset( $single_events_array[$single_event] );
								}

								if ( $loop_cutoff_type == 'days_per_page' ){
									$total_loops_needed = $total_loops_needed -1;
								}
							}
							else{
									array_push( $rebuilt_posts_array, $this_event['event'] );
							}

						}
					}
				}
			}

			//Add all daily posts
			if (!empty( $daily_posts ) ) {
				foreach ($daily_posts as $daily_post){

					//Change date to correct date and make other modifications
					$this_event = mp_events_modify_event( $daily_post, $current_day, $loop_cutoff_type );

					//Add this event to the array if it qualifies
					if ( isset( $this_event['failure'] ) ) {

						if ( 'recurring_event_has_ended' == $this_event['failure_id'] ){

							// Remove this event from the list of yearly events as it is not valid (recurring end date is in the past.)
							unset( $daily_posts[$daily_post] );

							if ( $loop_cutoff_type == 'days_per_page' ){
								$total_loops_needed = $total_loops_needed -1;
							}

							if ( $loop_cutoff_type == 'days_per_page' ){
								$total_loops_needed = $total_loops_needed -1;
							}
						}

					}
					else{

							array_push( $rebuilt_posts_array, $this_event['event'] );

					}

				}
			}

			//If there are any weekly posts
			if (!empty( $weekly_posts ) ) {
				//Loop through each set of weekdays
				foreach ($weekly_posts as $weekday_name => $weekday_array){
					//If this weekday is the same as the current day we are looping through
					if ( $weekday_name == $day_of_week ){
						//Loop through each post set for this weekday
						foreach( $weekday_array as $weekday_post ){

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $weekday_post, $current_day, $loop_cutoff_type );

							//Add this event to the array if it qualifies
							if ( isset( $this_event['failure'] ) ) {

								if ( 'recurring_event_has_ended' == $this_event['failure_id'] ){

									// Remove this event from the list of yearly events as it is not valid (recurring end date is likely in the past.)
									unset( $weekly_posts[$weekday_name][$weekday_post] );

									// If this is also the very last item scheduled for this day in yearly recurring posts, remove that day array as well
									if (empty( $weekly_posts[$weekday_name] ) ){
										unset( $weekly_posts[$weekday_name] );
									}

									if ( $loop_cutoff_type == 'days_per_page' ){
										$total_loops_needed = $total_loops_needed -1;
									}

								}

							}
							else{

									array_push( $rebuilt_posts_array, $this_event['event'] );

							}

						}
					}
				}
			}

			//If there are any monthly posts
			if (!empty( $monthly_posts ) ) {
				//Loop through each set of weekdays
				foreach ($monthly_posts as $monthday_num => $monthday_array){
					//If this monthday is the same as the current day we are looping through
					if ( $monthday_num == $day_of_month ){
						//Loop through each post set for this weekday
						foreach( $monthday_array as $monthday_post ){

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $monthday_post, $current_day, $loop_cutoff_type );

							//Add this event to the array if it qualifies
							if ( isset( $this_event['failure'] ) ) {

								if ( 'recurring_event_has_ended' == $this_event['failure_id'] ){

									// Remove this event from the list of yearly events as it is not valid (recurring end date is likely in the past.)
									unset( $monthly_posts[$monthday_num][$monthday_post] );

									// If this is also the very last item scheduled for this day in yearly recurring posts, remove that day array as well
									if (empty( $monthly_posts[$monthday_num] ) ){
										unset( $monthly_posts[$monthday_num] );
									}

									if ( $loop_cutoff_type == 'days_per_page' ){
										$total_loops_needed = $total_loops_needed -1;
									}

								}

							}
							else{

									array_push( $rebuilt_posts_array, $this_event['event'] );

							}

						}
					}
				}
			}

			//If there are any yearly posts
			if ( !empty( $yearly_posts ) ) {

				//Loop through each set of weekdays
				foreach ($yearly_posts as $monthday_date => $monthday_array){
					//If this monthday is the same as the current day we are looping through
					if ( $monthday_date == $day_of_month_of_year ){
						//Loop through each post set for this year day
						foreach( $monthday_array as $monthday_post ){

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $monthday_post, $current_day, $loop_cutoff_type );

							//Add this event to the array if it qualifies
							if ( isset( $this_event['failure'] ) ) {

								if ( 'recurring_event_has_ended' == $this_event['failure_id'] ){

									// Remove this event from the list of yearly events as it is not valid (recurring end date is likely in the past.)
									unset( $yearly_posts[$monthday_date][$monthday_post] );

									// If this is also the very last item scheduled for this day in yearly recurring posts, remove that day array as well
									if (empty( $yearly_posts[$monthday_date] ) ){
										unset( $yearly_posts[$monthday_date] );
									}

									if ( $loop_cutoff_type == 'days_per_page' ){
										$total_loops_needed = $total_loops_needed -1;
									}

								}

							}
							else{

									array_push( $rebuilt_posts_array, $this_event['event'] );

							}

						}
					}
				}
			}

			// If, at this point, there are no more events to loop through (perhaps they've all been recurring events in the past
			if ( empty( $yearly_posts ) && empty( $monthly_posts ) && empty( $weekly_posts ) && empty( $daily_posts ) ){
				break;
			}

			//Add one day to the current day to prepare for the next iteration
			$current_day = mp_events_add_date( $current_day, $day=1, $mth=0, $yr=0 );

		//End loop through date range
		}

		//If this is not a days per page query - meaning it is a posts per page query
		if ( $loop_cutoff_type != 'days_per_page' ){
			//If the length of $rebuilt_posts_array is longer than $posts_per_page
			//(This happens if there is more than 1 event per day
			//Make sure we cut the array length off at the posts per page

			$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $offset, $posts_per_page );

		}

		//Offset posts based on previous offset number
		$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $day_offset );

		//Set number of pages
		$mp_events_custom_query->max_num_pages = ceil( count( $rebuilt_posts_array ) / $posts_per_page );

		if ( count( $rebuilt_posts_array ) > $posts_per_page ){
			//Set the max number of pages in the Wp_query variable to 5 pages - if we are not showing a full month
			$mp_events_custom_query->max_num_pages = !isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? 5 : 0;
		}else{
			$mp_events_custom_query->max_num_pages = 1;
		}

		//If there is nothing in the $rebuilt_posts_array
		if ( empty( $rebuilt_posts_array ) ){

			return NULL;

			$empty_post[0] = new stdClass();
			$empty_post[0]->ID= -50;
			$empty_post[0]->post_category= array('uncategorized'); //Add some categories. an array()???
			$empty_post[0]->post_content=''; //The full text of the post.
			$empty_post[0]->post_excerpt= ''; //For all your post excerpt needs.
			$empty_post[0]->post_status='publish'; //Set the status of the new post.
			$empty_post[0]->post_title= 'No Events for this Date Range'; //The title of your post.
			$empty_post[0]->post_type='post'; //Sometimes you might want to post a page.
			$empty_post[0]->post_date=''; //Sometimes you might want to post a page.

			//Put empty post in the $rebuilt_posts_array
			$rebuilt_posts_array = empty( $rebuilt_posts_array ) ? $empty_post : $rebuilt_posts_array;

		}

		return $rebuilt_posts_array;
	}

}

/**
* Increment the date by number of days passed into function
*/
function mp_events_add_date($givendate,$day=0,$mth=0,$yr=0) {
	$cd = strtotime($givendate);
	$newdate = date('Y-m-d', mktime(date('h',$cd),
	date('i',$cd), date('s',$cd), date('m',$cd)+$mth,
	date('d',$cd)+$day, date('Y',$cd)+$yr));
	return $newdate;
}


/**
* This class adds the date to the permalink of each fake event post we created
*/
class mp_events_set_permalink_filter{

	protected $_args;

	public function __construct($args){

		$this->_args = $args;

		add_filter('post_type_link', array( $this, 'append_query_string') );

	}

	function append_query_string($url) {

		global $post;

		if ( isset( $post->ID ) && isset( $this->_args['post_id'] ) || isset( $this->_args['force_date_override'] ) ){
			if ( $post->ID == $this->_args['post_id'] || isset( $this->_args['force_date_override'] )){
				return add_query_arg( array(
					'mp_event_start_date' => urlencode($post->post_date) ,
					'mp_event_end_date' => urlencode( $post->mp_events_end_date ),
				), $url);
			}else{
				return $url;
			}
		}
		else{
			return $url;
		}
	}

}

/**
 * Modifies the event, adds the date from the date loop using the $current_date variable
 *
 * @since   1.0.0
 * @link    http://mintplugins.com/doc/
 * @see     function_name()
 * @param   str $post_id The ID of the mp_event post in question
 * @param   str $current_day This is a date string for the current date in the loop. It is formatted like this: $year .'-' . $month . '-' . $day (1999-01-31)
 * @param   str $loop_cutoff_type This is a string that tells us how this loop is being ended. Either "posts_per_page" or "days_per_page".
 * @return  object The event object which will be used in the query.
 */
function mp_events_modify_event( $post_id, $current_day, $loop_cutoff_type ){

	if ( $post_id instanceof WP_Post ){
		$this_event = get_post( $post_id->ID );
		$post_id = $post_id->ID;
	}else{
		//Get this post
		$this_event = get_post( $post_id );
	}

	//get start date of this post
	$start_date = strtotime( get_post_meta( $post_id, 'event_start_date', true ) );

	//get end date of this post
	$end_date = get_post_meta( $post_id, 'event_end_date', true );

	// Does this event repeat?
	$event_repeat = mp_core_get_post_meta( $post_id, 'event_repeat', 'none' );

	//Check if there is an end date for repeating (if this event repeats)
	$end_repeat_date = mp_core_get_post_meta( $post_id, 'event_repeat_end_date', 'infinite' );

	//Get the number of seconds between the start date and the end date
	if ( !empty( $end_date ) ){
		$seconds_between_start_and_end = strtotime( $end_date ) - $start_date;
	}
	else{
		$seconds_between_start_and_end = 0;
	}

	//If this query is a posts per page query
	if ( $loop_cutoff_type == 'posts_per_page' ){

		$current_time = strtotime( $current_day );

		//If this event is in the past according to "yesterday", don't add it to the list of upcoming events.
		if ( $current_time < strtotime( 'yesterday' ) ){
			return array(
				'failure' => true,
				'failure_id' => 'single_event_has_ended'
			);
		}

		// If the recurring end date has "passed" (in the current loop) for this repeating event, don't add it to the list of upcoming events.
		if ( $end_repeat_date != 'infinite' && $current_time > strtotime( $end_repeat_date ) ){
			return array(
				'failure' => true,
				'failure_id' => 'recurring_event_has_ended'
			);
		}

		// If the recurring start date has not yet "passed" (in the current loop) for this repeating event, don't add it to the list of upcoming events yet.
		if ( $event_repeat != 'none' && $current_time < $start_date ){
			return array(
				'failure' => true,
				'failure_id' => 'recurring_event_has_not_started'
			);
		}

		//Reset the date of this phantom event "post" in the query
		$this_event->post_date = apply_filters( 'mp_event_loop_date', $current_day );
		$this_event->mp_events_end_date = date( 'Y-m-d', strtotime( $this_event->post_date ) + $seconds_between_start_and_end );

		//Add the date to the permalink
		new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );

		return array(
				'event' => $this_event
		);


	}
	//If this is a days per page query
	else{

		//Reset the date
		$this_event->post_date = apply_filters( 'mp_event_loop_date', $current_day );
		$this_event->mp_events_end_date = date( 'Y-m-d', strtotime( $this_event->post_date ) + $seconds_between_start_and_end );

		//Add the date to the permalink
		new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );

		return $this_event;
	}

}
