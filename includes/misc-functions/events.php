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
			
			if ( !is_admin() ){
				
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
			
		if ( !is_admin() ){
			
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
		$url_date = !empty( $_GET['mp_event_date'] ) ? $_GET['mp_event_date'] : $mp_events_custom_query->posts[0]->post_date;
		$mp_events_custom_query->posts[0]->post_date = $url_date;
		
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
				array_push( $daily_posts, $post_id ); 
			
			}
			
			//Set weekly repeat array
			if ( $event_repeat == 'weekly' ){
								
				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);
				
				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($weekly_posts[$post_date] ) ){ $weekly_posts[$post_date] = array(); }
				
				//Push this post id into the array for this weedday
				array_push( $weekly_posts[$post_date], $post_id ); 
			}
			
			
			//Set fortnightly repeat array
			if ( $event_repeat == 'fortnightly' ){
									
				//Set to name of day of week- EG "Monday"
				$post_date = date("l", $post_date);
				
				//If this array has not yet been set up for this week day, set it up.
				if ( !isset($fortnightly_posts[$post_date] ) ){ $fortnightly_posts[$post_date] = array(); }
				
				//Push this post id into the array for this weedday
				array_push( $fortnightly_posts[$post_date], $post_id ); 
				
			}
			
			//Set monthly repeat array
			if ( $event_repeat == 'monthly' ){
				
				//Set to name of day of month- EG "01"
				$post_date = date("d", $post_date);
				
				//If this array has not yet been set up for this month day, set it up.
				if ( !isset($monthly_posts[$post_date] ) ){ $monthly_posts[$post_date] = array(); }
				
				//Push this post id into the array for this month day
				array_push( $monthly_posts[$post_date], $post_id ); 
			}
			
			//Set yearly repeat array
			if ( $event_repeat == 'yearly' ){ 
				
				//Set to name of day of month- EG "Dec 25"
				$post_date = date("M j", $post_date);
				
				//If this array has not yet been set up for this year day, set it up.
				if ( !isset($yearly_posts[$post_date] ) ){ $yearly_posts[$post_date] = array(); }
				
				//Push this post id into the array for this month day
				array_push( $yearly_posts[$post_date], $post_id ); 
			}
			
			
		//End Loop through mp_events
		}
		
		/**
		* Rebuild the posts array
		*/
		$rebuilt_posts_array = array();
		
		//Get user-selected, default timezone
		$default_timezone = mp_core_get_option( 'mp_events_settings_general',  'mp_events_default_timezone' );
		$default_timezone = !empty( $default_timezone ) ? $default_timezone : 'America/Los_Angeles';
		
		//Default Timezone - if events have no timezone selected, it will use this one
		date_default_timezone_set($default_timezone);
				
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
		
		/**
		* If there are NO repeating posts in this query
		* Loop through posts
		*/
		if ( empty( $yearly_posts ) &&  empty( $monthly_posts ) && empty( $weekly_posts ) && empty( $daily_posts ) ) {
			
			//Loop through each post
			foreach ( $mp_events as $mp_event ){
				
				//get start date of this post
				$start_date =  get_post_meta( $mp_event->ID, 'event_start_date', true );	
				
				//Get the start time for this event from the meta
				$start_time =  get_post_meta( $mp_event->ID, 'event_start_time', true );
				$start_time = !empty ( $start_time ) ? $start_time . ':00' : NULL;
				
				//End Time 
				$end_time =  get_post_meta( $post_id, 'event_start_time', true );
				$end_time = !empty ( $end_time ) ? $end_time . ':00' : $start_time;									
				
				//Timezone
				$time_zone =  get_post_meta( $mp_event->ID, 'event_time_zone', true );
				
				//Get number of seconds difference between the event's timezone and the PHP current timezone
				$time_zone_offset = !empty($time_zone) ? mp_events_get_timezone_offset($time_zone) : NULL;
								
				//If this event is in the future according to "now" and this is a posts per page
				if ( strtotime( $start_date . ' ' . $end_time . ' ' . $time_zone ) > strtotime( 'now' ) ){
												
					//Reset the date
					$mp_event->post_date = $start_date . ' ' . $start_time;
					
					//Add this post into the return array of posts to show
					array_push( $rebuilt_posts_array, $mp_event );
					
				}
			}
			
			//Set number of pages
			$mp_events_custom_query->max_num_pages = ceil( count( $rebuilt_posts_array ) / $posts_per_page );
			
			//Set offset from the URL using the mp_events_custom_query.
			$event_offset = $paged != 0 ? $paged * $posts_per_page - $posts_per_page  : 0;
			
			//If paged == 0, set it to be 1			
			$paged = $paged == 0 ? 1 : $paged;
			
			//Offset posts based on page number and amount of posts per page
			$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $event_offset, $posts_per_page * $paged );
		
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
								
		//Set type of loop cut off
		$loop_cutoff_type = isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? 'days_per_page' : 'posts_per_page';
		
		//If the cutoff type has been passed to the query, use it, instead use the above setting
		$loop_cutoff_type = isset( $mp_events_custom_query->query_vars['mp_events_days_per_page'] ) ? 'days_per_page' : $loop_cutoff_type;
		
		//Get the time right "now"		
		$utc_right_now = strtotime("now");
		
		/**
		* If there ARE repeating posts in this query
		* Loop through dates starting at current_day (set above)
		*/
		while ( $counter < $posts_per_page ){
			
			//Add correct amount to counter based on cutoff type 
			$counter = $loop_cutoff_type == 'days_per_page' ? $counter + 1 : count( $rebuilt_posts_array );
															
			$current_time = strtotime($current_day);
			
			$full_date = date("Y_m_d", $current_time);
			$day_of_week = date("l", $current_time); //Monday
			$day_of_month = date("d", $current_time); //01
			$day_of_month_of_year = date("M j", $current_time); //01
					
			//Add all no repeat posts
			//If there are any one-off posts
			if (!empty( $single_events ) ) {
				//Loop through each single event
				foreach ($single_events as $fulldate_num => $single_events_array){
					//If this fulldate_num is the same as the current day we are looping through
					if ( $fulldate_num == $full_date ){	
															
						//Loop through each post set for this full_date
						foreach( $single_events_array as $single_event ){
							
							//Change date to correct date and make other modifications				
							$this_event = mp_events_modify_event( $single_event, $current_day, $loop_cutoff_type );
							
							//Add this event to the array if it isn't returned as NULL
							if ( !empty ($this_event) ) { array_push( $rebuilt_posts_array, $this_event ); }
	
						}
					}
				}
			}
			
			
			//Add all daily posts
			if (!empty( $daily_posts ) ) {
				foreach ($daily_posts as $daily_post){
					
					//Change date to correct date and make other modifications				
					$this_event = mp_events_modify_event( $daily_post, $current_day, $loop_cutoff_type );
					
					//Add this event to the array if it isn't returned as NULL
					if ( !empty ($this_event) ) { array_push( $rebuilt_posts_array, $this_event ); }
					
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
							
							//Add this event to the array if it isn't returned as NULL
							if ( !empty ($this_event) ) { array_push( $rebuilt_posts_array, $this_event ); }
					
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
							
							//Add this event to the array if it isn't returned as NULL
							if ( !empty ($this_event) ) { array_push( $rebuilt_posts_array, $this_event ); }
							
						}
					}
				}
			}
			
			//If there are any yearly posts
			if (!empty( $yearly_posts ) ) {
				//Loop through each set of weekdays
				foreach ($yearly_posts as $monthday_date => $monthday_array){
					//If this monthday is the same as the current day we are looping through
					if ( $monthday_date == $day_of_month_of_year ){
						//Loop through each post set for this year day
						foreach( $monthday_array as $monthday_post ){
							
							//Change date to correct date and make other modifications				
							$this_event = mp_events_modify_event( $monthday_post, $current_day, $loop_cutoff_type );
							
							//Add this event to the array if it isn't returned as NULL
							if ( !empty ($this_event) ) { array_push( $rebuilt_posts_array, $this_event ); }
							
						}
					}
				}
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
			$rebuilt_posts_array = array_slice( $rebuilt_posts_array, 0, $posts_per_page );
		}
		
		//Offset posts based on previous offset number
		$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $day_offset );
		
		//Set the max number of pages in the Wp_query variable to 5 pages - if we are not showing a full month
		!isset( $mp_events_custom_query->query_vars['mp_events_month'] ) ? $mp_events_custom_query->max_num_pages = 5 : 0;
														
		//If there is nothing in the $rebuilt_posts_array
		if ( empty( $rebuilt_posts_array ) ){
			
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
				
		if ( $post->ID == $this->_args['post_id'] ){
			return add_query_arg( array('mp_event_date' => urlencode($post->post_date) ), $url);
		}else{
			return $url;	
		}
	}
	
}

/**    Returns the offset from the origin timezone to the remote timezone, in seconds.
*    @param $remote_tz;
*    @param $origin_tz; If null the servers current timezone is used as the origin.
*    @return int;
*/
function mp_events_get_timezone_offset($remote_tz, $origin_tz = null) {
    if($origin_tz === null) {
        if(!is_string($origin_tz = date_default_timezone_get())) {
            return false; // A UTC timestamp was returned -- bail out!
        }
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime("now", $origin_dtz);
    $remote_dt = new DateTime("now", $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
    return $offset;
}

/**    
*   Modifies the event, adds the date from the date loop using the $current_date variable
*/
function mp_events_modify_event( $post_id, $current_day, $loop_cutoff_type ){
	
	//Get this post
	$this_event = get_post( $post_id );
	
	//Start Time 
	$start_time =  get_post_meta( $post_id, 'event_start_time', true );
	$start_time = !empty ( $start_time ) ? $start_time . ':00' : NULL;
	
	//End Time 
	$end_time =  get_post_meta( $post_id, 'event_start_time', true );
	$end_time = !empty ( $end_time ) ? $end_time . ':00' : $start_time;				
	
	//Timezone
	$time_zone =  get_post_meta( $post_id, 'event_time_zone', true );
	
	//If this query is a posts per page query
	if ( $loop_cutoff_type == 'posts_per_page' ){
		
		//If this event is in the future according to "now" and this is a posts per page
		if ( strtotime( $current_day . ' ' . $end_time . ' ' . $time_zone ) > strtotime( 'now' ) ){
									
			//Reset the date
			$this_event->post_date = apply_filters( 'mp_event_loop_date', $current_day . ' ' . $start_time, $time_zone );
			
			//Add the date to the permalink
			new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
			
			return $this_event;
			
		}
		//If this event is not in the future, return NULL
		else{
			return NULL;	
		}
	}
	//If this is a days per page query
	else{
		
		//Reset the date
		$this_event->post_date = apply_filters( 'mp_event_loop_date', $current_day . ' ' . $start_time, $time_zone );
		
		//Add the date to the permalink
		new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
		
		return $this_event;
	}
								
}