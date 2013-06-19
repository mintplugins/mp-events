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
			$query->set( 'orderby',  'meta_key' );
			
			//Set meta Key to start date
			$query->set( 'meta_key',  'event_start_date' );
			
			
			if ( !is_admin() ){
				
				//Make the filters not suppressed in the Wp_Query class
				$query->set('suppress_filters', false);
				
				//Set filter to create repeaters
				add_filter ( 'the_posts', 'mp_events_post' );
			
			}	
							
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
		$query->set( 'orderby',  'meta_key' );
		
		//Set meta Key to start date
		$query->set( 'meta_key',  'event_start_date' );
			
		if ( !is_admin() ){
			
			//Make the filters not suppressed in the Wp_Query class
			$query->set('suppress_filters', false);
			
			//Set filter to create repeaters
			add_filter ( 'the_posts', 'mp_events_post' );
		
		}	
	}
	
	return;
}
add_action ( 'pre_get_posts', 'mp_events', 1 );

/**
* Filter which inserts pseudo events into posts array before "the loop"
*/
function mp_events_post( $mp_events ){
	
	global $wp_query;
	
	//Since $query->set is not working for secondary loops, we'll use a global variable for now :( <-- sad face
	global $actual_posts_per_page;
	
	//Set the date for single pages to the URL passed date
	if ( is_single() ){
		
		//Get date from URL
		$url_date = !empty( $_GET['mp_event_date'] ) ? $_GET['mp_event_date'] : $wp_query->posts[0]->post_date;
		$wp_query->posts[0]->post_date = $url_date;
		
		return $mp_events;
				
	}
		
	if ( !is_single() ){
					
		//Loop through mp_events
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
				$post_date = date("m_d_Y", $post_date);
				
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
		
		//Set Timezone
		date_default_timezone_set('America/Los_Angeles');
		
		//Set posts per page to number of days in current month, or to the number set in settings > reading
		$posts_per_page = isset( $wp_query->query_vars['mp_events_month'] ) ? date('t') : $actual_posts_per_page; //$wp_query->query_vars['actual_posts_per_page']
		
		//Get year from the URL using the wp_query
		$year = isset( $wp_query->query_vars['mp_events_year'] ) ? $wp_query->query_vars['mp_events_year'] : date('Y');
		
		//Get month from the URL using the wp_query
		$month = isset( $wp_query->query_vars['mp_events_month'] ) ? $wp_query->query_vars['mp_events_month'] : date('m');
		
		//Set day from the URL using the wp_query. If the month is set, set it to 1, if not, set it to today
		$day = isset( $wp_query->query_vars['mp_events_month'] ) ? 1 : date('d');
		
		//Get page number - and make sure the page isn't anything higher than 500 - to keep servers from crashing
		$paged = $wp_query->query_vars['paged'] > 500 ? 500 : $wp_query->query_vars['paged'];
		
		//Set offset from the URL using the wp_query.
		$day_offset = $wp_query->query_vars['paged'] != 0 ? $paged * $posts_per_page - $posts_per_page  : 0;
				
		//Add day offset to posts per page - we will subtract the day offset from the array after it is built (below the loop)
		$posts_per_page = $posts_per_page + $day_offset;
		
		//If there are no repeating posts in this query
		if ( empty( $yearly_posts ) &&  empty( $monthly_posts ) && empty( $weekly_posts ) && empty( $daily_posts ) ) {
			
			//set the posts per page to be the lesser of posts per page or single_events 
			$posts_per_page = $posts_per_page > count( $single_events ) ? count( $single_events ) : $posts_per_page;
			
			//Temporarily set the oldest date to something crazily high we'll never live to see
			$oldestDate = 9999999999999999;
			
			//Find which date is the oldest one in the single array
			foreach($single_events as $curDate => $single_event){
			  if ($curDate < $oldestDate) {
				 $oldestDate = $curDate;
			  }
			}
					
			//Set starting date to first post's date
			$current_day =  get_post_meta( $single_events[$oldestDate][0], 'event_start_date', true );
															
		}
		else{
			
			//Set starting date
			$current_day = $year .'-' . $month . '-' . $day;
		
		}
						
		//Set counter
		$counter = 0;
								
		//Set type of loop cut off
		$loop_cutoff_type = isset( $wp_query->query_vars['mp_events_month'] ) ? 'month' : 'postsperpage';
				
		//Loop through dates
		while ( $counter < $posts_per_page ){
			
			//echo $counter;
												
			$current_time = strtotime($current_day);
			
			$full_date = date("m_d_Y", $current_time);
			$day_of_week = date("l", $current_time); //Monday
			$day_of_month = date("d", $current_time); //01
			$day_of_month_of_year = date("M j", $current_time); //01
					
			//Add all no repeat posts
			//If there are any one-off posts
			if (!empty( $single_events ) ) {
				//Loop through each set of weekdays
				foreach ($single_events as $fulldate_num => $single_events_array){
					//If this fulldate_num is the same as the current day we are looping through
					if ( $fulldate_num == $full_date ){	
															
						//Loop through each post set for this full_date
						foreach( $single_events_array as $single_event ){
														
							//Get this post
							$this_event = get_post( $single_event );
							
							$start_time =  get_post_meta( $single_event, 'event_start_time', true );
							$start_time = !empty ( $start_time ) ? $start_time : '12:00';
														
							//Reset the date
							$this_event->post_date = $current_day . ' ' . $start_time . ':00';
							
							//Add the date to the permalink
							new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
							
							array_push( $rebuilt_posts_array, $this_event );
						}
					}
				}
			}
			
			
			//Add all daily posts
			if (!empty( $daily_posts ) ) {
				foreach ($daily_posts as $daily_post){
					
					//Get this post
					$this_event = get_post( $daily_post );
							
					$start_time =  get_post_meta( $daily_post, 'event_start_time', true );
					$start_time = !empty ( $start_time ) ? $start_time : '12:00';
												
					//Reset the date
					$this_event->post_date = $current_day . ' ' . $start_time . ':00';
					
					//Add the date to the permalink
					new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
							
					array_push( $rebuilt_posts_array, $this_event );
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
							
							//Get this post
							$this_event = get_post( $weekday_post );
							
							$start_time =  get_post_meta( $weekday_post, 'event_start_time', true );
							$start_time = !empty ( $start_time ) ? $start_time : '12:00';
														
							//Reset the date
							$this_event->post_date = $current_day . ' ' . $start_time . ':00';
														
							//Add the date to the permalink
							new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
							
							array_push( $rebuilt_posts_array, $this_event );
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
							
							//Get this post
							$this_event = get_post( $monthday_post );
							
							$start_time =  get_post_meta( $monthday_post, 'event_start_time', true );
							$start_time = !empty ( $start_time ) ? $start_time : '12:00';
														
							//Reset the date
							$this_event->post_date = $current_day . ' ' . $start_time . ':00';
							
							//Add the date to the permalink
							new mp_events_set_permalink_filter( array( 'post_id' => $this_event->ID ) );
														
							array_push( $rebuilt_posts_array, $this_event );
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
							
							//Get this post
							$this_event = get_post( $monthday_post );
							
							$start_time =  get_post_meta( $monthday_post, 'event_start_time', true );
							$start_time = !empty ( $start_time ) ? $start_time : '12:00';
														
							//Reset the date
							$this_event->post_date = $current_day . ' ' . $start_time . ':00';
							
							array_push( $rebuilt_posts_array, $this_event );
						}
					}
				}
			}
			
			//Add one day to the current day to prepare for the next iteration			
			$current_day = mp_events_add_date( $current_day, $day=1, $mth=0, $yr=0 );
			
			//Add correct amount to counter based on cutoff type 
			$counter = $loop_cutoff_type == 'month' ? $counter+1 : count( $rebuilt_posts_array );
			
		//End loop through date range
		}
		
		//Offset posts based on previous offset number
		$rebuilt_posts_array = array_slice( $rebuilt_posts_array, $day_offset );
		
		//Set the max number of pages in the Wp_query variable to 5 pages - if we are not showing a full month
		!isset( $wp_query->query_vars['mp_events_month'] ) ? $wp_query->max_num_pages = 5 : 0;
														
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
		//Make sure this doesn't affect other loops on this page
		remove_filter ( 'the_posts', 'mp_events_post' );
		
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
			return add_query_arg( array('mp_event_date' => $post->post_date ), $url);
		}else{
			return $url;	
		}
	}
	
}