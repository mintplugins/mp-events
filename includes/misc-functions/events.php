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

	// Get the timezone set for the WordPress
	$wp_timezone = new DateTimeZone( mp_events_get_timezone_id() );
	$utc_timezone = new DateTimeZone( 'UTC' );
	$yesterday = new DateTime( 'yesterday', $wp_timezone );

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

			$event_start_date_meta = get_post_meta( $post_id, 'event_start_date', true );

			//Set Post Date to the date of the event
			$date_object = new DateTime();
			$date_object = $date_object->createFromFormat( 'Y-m-d', $event_start_date_meta, $wp_timezone );
			$time_of_event = '00:00:00';

			// If we were not able to create a datetime object (probably the wrong format), skip this event
			if ( ! ( $date_object instanceof DateTime ) ) {

				// Check to see if they entered the time of the event in the date field
				$date_object = new DateTime();
				$date_object = $date_object->createFromFormat( 'Y-m-d H:i:s', $event_start_date_meta );

				$time_of_event = explode( ' ', $event_start_date_meta );
				if ( isset( $time_of_event[1] ) ) {
					$time_of_event = $time_of_event[1];
				}

				// If the format of the date is still no good, skip this event
				if ( ! ( $date_object instanceof DateTime ) ) {
					continue;
				}
			}

			//$date_object = new DateTime( $event_start_date_meta, $wp_timezone );
			$post_date = $date_object->getTimestamp();

			//Get Repeat Setting
			$event_repeat = mp_core_get_post_meta( $post_id, 'event_repeat', 'none' );

			//Set no repeat array
			if ( $event_repeat == 'none' ){

				//Set to name of day of month- EG "March 16, 1986"
				$post_date = date("Y_m_d", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset( $single_events[$post_date] ) ){
					$single_events[$post_date] = array();
				}

				$single_events[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);

			}

			//Set daily repeat array
			if ( $event_repeat == 'daily' ){

				if ( !isset( $daily_posts ) ){  $daily_posts = array(); }
				$daily_posts[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);

			}

			//Set weekly repeat array
			if ( $event_repeat == 'weekly' ){

				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($weekly_posts[$post_date] ) ){ $weekly_posts[$post_date] = array(); }

				//Push this post id into the array for this weedday
				$weekly_posts[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);
			}


			//Set fortnightly repeat array
			if ( $event_repeat == 'fortnightly' ){

				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);

				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($fortnightly_posts[$post_date] ) ){ $fortnightly_posts[$post_date] = array(); }

				//Push this post id into the array for this weedday
				$fortnightly_posts[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);

			}

			//Set monthly repeat array
			if ( $event_repeat == 'monthly' ){

				//Set to name of day of month- EG "01"
				$post_date = date("d", $post_date);

				//If this array has not yet been set up for this month day, set it up.
				if ( !isset($monthly_posts[$post_date] ) ){ $monthly_posts[$post_date] = array(); }

				//Push this post id into the array for this month day
				$monthly_posts[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);
			}

			//Set yearly repeat array
			if ( $event_repeat == 'yearly' ){

				//Set to name of day of month- EG "Dec 25"
				$post_date = date("M j", $post_date);

				//If this array has not yet been set up for this year day, set it up.
				if ( !isset($yearly_posts[$post_date] ) ){ $yearly_posts[$post_date] = array(); }

				//Push this post id into the array for this month day
				$yearly_posts[$post_date][$post_id] = array(
					'post_id' => $post_id,
					'event_time' => $time_of_event
				);
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
		if ( !isset( $mp_events_custom_query->query_vars['mp_events_day'] ) ) {
			if ( isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ) {
				$day = 1;
			} else {
				$day = date('d', $yesterday->getTimestamp() );
			}
		} else {
			$mp_events_custom_query->query_vars['mp_events_day'];
		}

		//Get page number - and make sure the page isn't anything higher than 500 - to keep servers from crashing
		$paged = $mp_events_custom_query->query_vars['paged'] > 500 ? 500 : $mp_events_custom_query->query_vars['paged'];

		//Set starting date
		$current_day = new DateTime();
		$current_day = $current_day->createFromFormat( 'Y-m-d', $year .'-' . $month . '-' . $day, $wp_timezone );

		//Set posts per page to number of days in current month, or to the number set in settings > reading
		if ( isset( $mp_events_custom_query->query_vars['mp_events_days_per_page'] ) ){

			$posts_per_page = $mp_events_custom_query->query_vars['mp_events_days_per_page'];

		}elseif ( isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ){

			$posts_per_page = date('t', $current_day->getTimestamp() );

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

				$the_start_date = mp_core_get_post_meta( $mp_event->ID, 'event_start_date' );
				$the_start_time = mp_core_get_post_meta( $mp_event->ID, 'event_start_time' );
				$start_date = mp_events_get_event_datetime_object( $the_start_date );

				// If we were not able to create a datetime object (probably the wrong format), skip this event
				if ( ! ( $start_date instanceof DateTime ) ) {
					continue;
				}

				//If this event is in the future according to "yesterday" and this is a posts per page
				if ( $start_date->getTimestamp() > $yesterday->getTimestamp() ){

					if ( ! $start_date ) {
						echo '1';
						die();
					}

					$this_event = mp_events_modify_event( $mp_event, $start_date, $loop_cutoff_type );

					if ( isset( $this_event['event'] ) ) {
						//Add this post into the return array of posts to show
						array_push( $rebuilt_posts_array, $this_event['event'] );
					}

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

		$total_loops_needed = $offset + $posts_per_page;

		// Prevent infinite loops
		if ( empty( $total_loops_needed ) || $total_loops_needed > 9999 ){
			$total_loops_needed = 0;
		}

		$first_date_in_loop = $current_day->getTimestamp();

		if ( !empty( $single_events ) ) {

			//Loop through each single event, and eliminate any that are older than this loop's beginning date
			foreach ($single_events as $fulldate_num => $single_events_array){

				//Loop through each post set for this full_date
				foreach( $single_events_array as $single_event_id => $single_event_data ){

					//get start date of this post
					$the_start_date = mp_core_get_post_meta( $single_event_id, 'event_start_date' );
					$the_start_time = mp_core_get_post_meta( $single_event_id, 'event_start_time' );
					$single_event_start_date = mp_events_get_event_datetime_object( $the_start_date );

					// If we were not able to create a datetime object (probably the wrong format), skip this event
					if ( ! ( $single_event_start_date instanceof DateTime ) ) {
						continue;
					}

					//If this event is older than this loop's beginning date
					if ( $first_date_in_loop > $single_event_start_date->getTimestamp() ){

						//Remove this event from the list of single events since it is in the past
						unset( $single_events[$fulldate_num][$single_event_id] );

						if ( empty( $single_events[$fulldate_num] ) ) {
							unset( $single_events[$fulldate_num] );
						}

					}

				}
			}
		}

		/**
		* If there ARE repeating posts in this query
		* Loop through dates starting at current_day (set above)
		*/
		while ( $counter < $total_loops_needed ){

			//Add correct amount to counter based on cutoff type
			$counter = $loop_cutoff_type == 'days_per_page' ? $counter + 1 : ( count( $rebuilt_posts_array ) - $offset );

			$full_date = date( "Y_m_d", $current_day->getTimestamp() );
			$day_of_week = date( "l", $current_day->getTimestamp() ); //Monday
			$day_of_month = date( "d", $current_day->getTimestamp() ); //01
			$day_of_month_of_year = date( "M j", $current_day->getTimestamp() ); //01

			//Add all no repeat posts
			//If there are any one-off posts
			if ( !empty( $single_events ) ) {

				//Loop through each single event
				foreach ($single_events as $fulldate_num => $single_events_array){

					//If this fulldate_num is the same as the current day we are looping through
					if ( $fulldate_num == $full_date ){

						//Loop through each post set for this full_date
						foreach( $single_events_array as $single_event_id => $single_event_data ){

							$date_event_should_be = mp_events_get_event_datetime_object( $current_day->format('Y-m-d') . ' ' .$single_event_data['event_time'] );

							if ( false == $date_event_should_be ) {
								echo '2';
								die();
							}

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $single_event_id, $date_event_should_be, $loop_cutoff_type );

							//Add this event to the array if it isn't returned as NULL
							if ( isset( $this_event['failure'] ) ) {

								if ( 'single_event_has_ended' == $this_event['failure_id'] ){
									//Remove this event from the list of single events since it is in the past
									unset( $single_events[$fulldate_num][$single_event_id] );

									if ( empty( $single_events[$fulldate_num] ) ) {
										unset( $single_events[$fulldate_num] );
									}
								}

								if ( $loop_cutoff_type == 'days_per_page' ){
									$total_loops_needed = $total_loops_needed -1;
								}
							}
							else{
									array_push( $rebuilt_posts_array, $this_event['event'] );

									//Remove this event from the list of single events since it is in the past
									unset( $single_events[$fulldate_num][$single_event_id] );

									if ( empty( $single_events[$fulldate_num] ) ) {
										unset( $single_events[$fulldate_num] );
									}
							}

						}
					}
				}
			}

			//Add all daily posts
			if (!empty( $daily_posts ) ) {
				foreach ($daily_posts as $day_key => $daily_post){
					foreach( $daily_post as $daily_post_id => $daily_post_data ){

						$date_event_should_be = mp_events_get_event_datetime_object( $current_day->format('Y-m-d') . ' ' .$daily_post_data['event_time'] );

						if ( false == $date_event_should_be ) {
							echo '3';
							die();
						}

						//Change date to correct date and make other modifications
						$this_event = mp_events_modify_event( $daily_post_id, $date_event_should_be, $loop_cutoff_type );

						//Add this event to the array if it qualifies
						if ( isset( $this_event['failure'] ) ) {

							if ( 'recurring_event_has_ended' == $this_event['failure_id'] ){

								// Remove this event from the list of yearly events as it is not valid (recurring end date is likely in the past.)
								unset( $daily_posts[$day_key][$daily_post_id] );

								// If this is also the very last item scheduled for this day in daily recurring posts, remove that day array as well
								if (empty( $daily_posts[$day_key] ) ){
									unset( $daily_posts[$day_key] );
								}

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
			}

			//If there are any weekly posts
			if (!empty( $weekly_posts ) ) {

				//Loop through each set of weekdays
				foreach ($weekly_posts as $weekday_name => $weekday_array){
					//If this weekday is the same as the current day we are looping through
					if ( $weekday_name == $day_of_week ){
						//Loop through each post set for this weekday
						foreach( $weekday_array as $weekday_post => $weekly_post_data ){

							$date_event_should_be = mp_events_get_event_datetime_object( $current_day->format('Y-m-d') . ' ' .$weekly_post_data['event_time'] );

							if ( false == $date_event_should_be ) {
								echo '4';
								die();
							}

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $weekday_post, $date_event_should_be, $loop_cutoff_type );

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
						foreach( $monthday_array as $monthday_post => $monthly_post_data ){

							$date_event_should_be = mp_events_get_event_datetime_object( $current_day->format('Y-m-d') . ' ' .$monthly_post_data['event_time'] );

							if ( false == $date_event_should_be ) {
								echo '5';
								die();
							}

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $monthday_post, $date_event_should_be, $loop_cutoff_type );

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
						foreach( $monthday_array as $monthday_post => $monthday_post_data ){

							$date_event_should_be = mp_events_get_event_datetime_object(  $current_day->format('Y-m-d') . ' ' .$monthday_post_data['event_time']  );

							if ( false == $date_event_should_be ) {
								echo '6';
								die();
							}

							//Change date to correct date and make other modifications
							$this_event = mp_events_modify_event( $monthday_post, $date_event_should_be, $loop_cutoff_type );

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
			if ( empty( $yearly_posts ) && empty( $monthly_posts ) && empty( $weekly_posts ) && empty( $daily_posts ) && empty( $single_events ) ){
				break;
			}

			//Add one day to the current day to prepare for the next iteration
			$current_day->modify( '+1 day' );

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

		// Re-sort the posts being shown by their post_date

		$dates = array();
		foreach ($rebuilt_posts_array as $key => $row) {
		    $dates[$key] = $row->post_date;
		}
		array_multisort($dates, SORT_ASC, $rebuilt_posts_array);

		return $rebuilt_posts_array;
	}

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

	// Create a UTC timezone version
	$current_date_utc_timezone = new DateTime( '@' . $current_day->getTimestamp() );

	$wp_timezone = new DateTimeZone( mp_events_get_timezone_id() );

	$yesterday = new DateTime( 'yesterday', $wp_timezone );

	//get start date of this post
	$start_date = new DateTime();

	// Try to create the date object, checking if they entered the date and the time in the event start date field
	$start_date = $start_date->createFromFormat( 'Y-m-d H:i:s', get_post_meta( $post_id, 'event_start_date', true ), $wp_timezone );

	// If we got an invalid date time object, try without the time added
	if ( ! ( $start_date instanceof DateTime ) ) {
		$start_date = new DateTime();
		$start_date = $start_date->createFromFormat( 'Y-m-d', get_post_meta( $post_id, 'event_start_date', true ), $wp_timezone );
	}

	// If we were not able to create a datetime object (probably the wrong format), skip this event
	if ( ! ( $start_date instanceof DateTime ) ) {
		return array(
			'failure' => true,
			'failure_id' => 'invalid_start_date'
		);
	}

	//get end date of this post
	$end_date = new DateTime();
	$end_date = $end_date->createFromFormat( 'Y-m-d', get_post_meta( $post_id, 'event_end_date', true ), $wp_timezone );

	// If we were not able to create a datetime object (probably the wrong format), skip this event
	if ( ! ( $end_date instanceof DateTime ) ) {
		$valid_end_date = false;
	} else {
		$valid_end_date = true;
	}


	// Does this event repeat?
	$event_repeat = mp_core_get_post_meta( $post_id, 'event_repeat', 'none' );

	//Check if there is an end date for repeating (if this event repeats)
	$end_repeat_date = mp_core_get_post_meta( $post_id, 'event_repeat_end_date', 'infinite' );

	if ( 'infinite' != $end_repeat_date ) {
		$end_repeat_date = new DateTime();
		$end_repeat_date = $end_repeat_date->createFromFormat( 'Y-m-d', get_post_meta( $post_id, 'event_repeat_end_date', true ), $wp_timezone );

		// If we were not able to create a datetime object (probably the wrong format), skip this event
		if ( ! ( $end_repeat_date instanceof DateTime ) ) {
			$valid_end_repeat_date = false;
		} else {
			$valid_end_repeat_date = true;
		}

		$end_repeat_date_infinite = false;

	} else {
		$end_repeat_date_infinite = true;
	}

	//Get the number of seconds between the start date and the end date
	if ( $valid_end_date ){
		$seconds_between_start_and_end = $end_date->getTimestamp() - $start_date->getTimestamp();
	}
	else{
		$seconds_between_start_and_end = 0;
	}

	$current_time = $current_day->getTimestamp();

	$end_date_time = new DateTime( '@' . ( $current_time + $seconds_between_start_and_end ) );

	//If this event is in the past according to "yesterday", don't add it to the list of upcoming events.
	if ( $current_time < $yesterday->getTimestamp() ){
		return array(
			'failure' => true,
			'failure_id' => 'single_event_has_ended'
		);
	}

	// If the recurring end date has "passed" (in the current loop) for this repeating event, don't add it to the list of upcoming events.
	if ( $event_repeat != 'none' && ! $end_repeat_date_infinite ) {
	 	if( $current_time > $end_repeat_date->getTimestamp() ){
			return array(
				'failure' => true,
				'failure_id' => 'recurring_event_has_ended'
			);
		}
	}

	// If the recurring start date has not yet "passed" (in the current loop) for this repeating event, don't add it to the list of upcoming events yet.
	if ( $event_repeat != 'none' && $current_time < $start_date->getTimestamp() ){
		return array(
			'failure' => true,
			'failure_id' => 'recurring_event_has_not_started'
		);
	}

	//Reset the date of this phantom event "post" in the query
	$this_event->post_date = $current_day->format('Y-m-d H:i:s');
	$this_event->post_date_gmt = $current_date_utc_timezone->format('Y-m-d H:i:s');

	$this_event->mp_events_end_date = $end_date_time->format('Y-m-d H:i:s');

	//Add the date to the permalink
	new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );

	//If this query is a posts per page query
	if ( $loop_cutoff_type == 'posts_per_page' ){

		return array(
				'event' => $this_event
		);

	}
	//If this is a days per page query
	else{

		return $this_event;
	}

}

// Get the date object for an event based on its date and time entered in wp-admin
function mp_events_get_event_datetime_object( $day_of_event ) {

	// If the start date is not valid, we don't have a valid entered date
	if ( ! $day_of_event ) {
		return false;
	}

	$wp_timezone = new DateTimeZone( mp_events_get_timezone_id() );

	// Try to create the date object, checking if they entered the date and the time in the event start date field
	$date_object = new DateTime();
	$date_object = $date_object->createFromFormat( 'Y-m-d H:i:s', $day_of_event, $wp_timezone );

	// If we got a valid date time object
	if ( $date_object instanceof DateTime ) {
		return $date_object;
	}

	// if we did not get a valid date time object, try without the time
	$date_object = new DateTime();
	$date_object = $date_object->createFromFormat( 'Y-m-d', $day_of_event, $wp_timezone );

	// If we got a valid date time object
	if ( $date_object instanceof DateTime ) {

		return $date_object;

	}

	// If we were not able to create a datetime object (probably the wrong format), return false
	return false;

}
