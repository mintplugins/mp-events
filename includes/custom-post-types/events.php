<?php
/**
 * Custom Post Types
 *
 * @package mp_events
 * @since mp_events 1.0
 */

/**
 * Event Custom Post Type
 */
function mp_events_post_type() {
	
		$events_labels =  apply_filters( 'mp_events_events_labels', array(
			'name' 				=> 'Events',
			'singular_name' 	=> 'Event',
			'add_new' 			=> __('Add New', 'mp_events'),
			'add_new_item' 		=> __('Add New Event', 'mp_events'),
			'edit_item' 		=> __('Edit Event', 'mp_events'),
			'new_item' 			=> __('New Event', 'mp_events'),
			'all_items' 		=> __('All Events', 'mp_events'),
			'view_item' 		=> __('View Event', 'mp_events'),
			'search_items' 		=> __('Search Events', 'mp_events'),
			'not_found' 		=>  __('No Events found', 'mp_events'),
			'not_found_in_trash'=> __('No Events found in Trash', 'mp_events'), 
			'parent_item_colon' => '',
			'menu_name' 		=> __('Events', 'mp_events')
		) );
		
			
		$events_args = array(
			'labels' 			=> $events_labels,
			'public' 			=> true,
			'publicly_queryable'=> true,
			'show_ui' 			=> true, 
			'show_in_nav_menus' => true,
			'show_in_menu' 		=> true, 
			'menu_position'		=> 5,
			'query_var' 		=> true,
			'rewrite' 			=> array( 'slug' => 'events' ),
			'capability_type' 	=> 'post',
			'has_archive' 		=> true, 
			'hierarchical' 		=> false,
			'supports' 			=> apply_filters('mp_events_supports', array( 'title', 'editor', 'thumbnail' ) ),
		); 
		register_post_type( 'mp_event', apply_filters( 'mp_events_post_type_args', $events_args ) );
		
		//new MP_Core_Custom_Post_Type_With_Dates('mp_event', apply_filters( 'mp_events_post_type_args', $events_args ) );
}
add_action( 'init', 'mp_events_post_type', 0 );

/**
 * Calendars Taxonomy
 */
 
function mp_events_person_group_taxonomy() {  
		
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'                => __( 'Calendars', 'mp_events' ),
			'singular_name'       => __( 'Calendar', 'mp_events' ),
			'search_items'        => __( 'Search Calendars', 'mp_events' ),
			'all_items'           => __( 'All Calendars', 'mp_events' ),
			'parent_item'         => __( 'Parent Calendar', 'mp_events' ),
			'parent_item_colon'   => __( 'Parent Calendar:', 'mp_events' ),
			'edit_item'           => __( 'Edit Calendar', 'mp_events' ), 
			'update_item'         => __( 'Update Calendar', 'mp_events' ),
			'add_new_item'        => __( 'Add New Calendar', 'mp_events' ),
			'new_item_name'       => __( 'New Calendar Name', 'mp_events' ),
			'menu_name'           => __( 'Calendars', 'mp_events' ),
		); 	
  
		register_taxonomy(  
			'mp_calendars',  
			'mp_event',  
			array(  
				'hierarchical' => true,  
				'label' => 'Calendars',  
				'labels' => $labels,  
				'query_var' => true,  
				'with_front' => false, 
				'rewrite' => array('slug' => 'calendars')  
			)  
		);  
}  
add_action( 'init', 'mp_events_person_group_taxonomy' );  

/**
 * Change default title
 */
function mp_events_change_default_title( $title ){
     $screen = get_current_screen();
 
     if  ( 'mp_event' == $screen->post_type ) {
          $title = __('Enter the Event\'s Name', 'mp_events');
     }
 
     return $title;
}
add_filter( 'enter_title_here', 'mp_events_change_default_title' );

function mp_events_convert_id_to_term_in_query($query) {
	global $pagenow;
	$post_type = 'mp_event'; // change HERE
	$taxonomy = 'mp_calendars'; // change HERE
	$q_vars = &$query->query_vars;
	if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
		$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
		$q_vars[$taxonomy] = $term->slug;
	}
}

add_filter('parse_query', 'mp_events_convert_id_to_term_in_query');

/**
 * Add each calendar to the events button in the WP menu
 */
function mp_events_show_each_calendar_in_menu(){
	
	$calendars = mp_core_get_all_terms_by_tax('mp_calendars');
 	
	foreach( $calendars as $id => $calendar){
	
		add_submenu_page( 'edit.php?post_type=mp_event', $calendar, $calendar, 'manage_options', add_query_arg( array('mp_calendars' => $id), 'edit.php?post_type=mp_event' ) );
	}	
}
add_action('admin_menu', 'mp_events_show_each_calendar_in_menu');

/**
 * Rewrite rules for dates
 */
function mp_events_rewrites($rules){

    	$post_type = 'mp_event';
		
		$slug = 'events';
		
		$new_rules = array(
			//feeds
			"{$slug}/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&mp_events_day=$matches[3]&feed=$matches[4]' . '&post_type=' .  $post_type,
			"{$slug}/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&mp_events_day=$matches[3]&feed=$matches[4]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/page/1/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&mp_events_day=$matches[3]&paged=$matches[4]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&mp_events_day=$matches[3]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/feed/rss2/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&feed=$matches[3]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/feed/rss2/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&feed=$matches[3]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/page/1/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]&paged=$matches[3]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/page/1/
			"{$slug}/([0-9]{4})/([0-9]{1,2})/?$" => 'index.php?mp_events_year=$matches[1]&mp_events_month=$matches[2]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/feed/rss2/
			"{$slug}/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&feed=$matches[2]' . '&post_type=' .  $post_type,
			
			//events/2013/05/23/feed/rss2/
			"{$slug}/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$" => 'index.php?mp_events_year=$matches[1]&feed=$matches[2]' . '&post_type=' .  $post_type,
			
			//events/2013/page/1
			"{$slug}/([0-9]{4})/page/?([0-9]{1,})/?$" => 'index.php?mp_events_year=$matches[1]&paged=$matches[2]' . '&post_type=' .  $post_type,
			
			//events/2013/
			"{$slug}/([0-9]{4})/?$" => 'index.php?mp_events_year=$matches[1]' . '&post_type=' .  $post_type,
			
			//events/page/1
			"{$slug}/page/?([0-9]{1,})/?$" => 'index.php?paged=$matches[1]' . '&post_type=' .  $post_type,
			
			//calendars/custom-slug/page/1
			"calendars/([^/]*)/page/([0-9]{1,})/?$" => 'index.php?mp_calendars=$matches[1]&paged=$matches[2]',
			
			//calendars/custom-slug/2013/01/
			"calendars/([^/]*)/([0-9]{4})/([0-9]{1,2})/?$" => 'index.php?mp_calendars=$matches[1]&mp_events_year=$matches[2]&mp_events_month=$matches[3]',
		);
		
		$new_rules = array_merge($new_rules, $rules);
		
		return $new_rules;
}
add_filter('rewrite_rules_array', 'mp_events_rewrites');

function mp_events_rewrite_tags(){
	add_rewrite_tag('%mp_events_year%','([^&]+)');
	add_rewrite_tag('%mp_events_month%','([^&]+)');
	add_rewrite_tag('%paged%','([^&]+)');
}
add_action('init', 'mp_events_rewrite_tags');

