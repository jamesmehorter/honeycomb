<?php
	/*
		Plugin Name: Honeycomb Event Calendar
		Plugin URI: https://github.com/jamesmehorter/honeycomb
		Description: The Honeycomb Event Calendar plugin for WordPress 3.0+ helps you build and organize your events. 
		Version: 0.1 Beta
		Author: James Mehorter
		Author URI: http://www.jamesmehorter.com
		Copyright 2011 James Mehorter (email : jamesmehorter@gmail.com)
		
		For detailed documentation see the README.txt file bundled with the plugin. (smysite/wp-content/honeycomb/readme.txt
	*/
	
	//------------------------------  Honeycomb Plugin WordPress Hooks ---------------------------//
	/*
		CREATE THE HONEYCOMB
	*/
	//Create the 'Event' custom post type and 'Event Categories' taxonomy
	//Also create the 'Event Categories' custom meta data database table
	add_action('init', 'Create_Calendar_Custom_Post_Type', 0 );
	//Create the 'Calendar View' Sub-Menu Page under 'Events'
	add_action('admin_menu', 'Init_Honeycomb_Event_Calendar') ;	
	//Add a custom calendar icon to the 'Events' admin menu
	add_action('admin_head', 'Honeycomb_Admin_Menu_Icons');	
	/*
		EXTEND EDIT EVENTS POST TYPE
	*/
	//When editing the event via the post page we need to hook into the save action to store the custom meta data for each event
	add_action('save_post', 'save_meta_box_event_settings', 1, 2);
	//These two hooks extend the 'Events' list view to include an 'Event Category' column
	add_filter('manage_edit-hc_events_columns', 'Manage_Events_Admin_Columns');
	add_action('manage_posts_custom_column', 'Manage_Events_Admin_Column_Content');
	/*
		EXTEND EVENT CATEGORIES TAXONOMY
	*/
	//These two hooks extend the event_categories term edit page, by adding a 'color' row
	add_action('hc_event_categories_edit_form_fields', 'Manage_Event_Categories_Columns', 10, 2);
	add_action('edited_hc_event_categories', 'Save_Event_Categories', 10, 2);
	//Extend the 'Event Categories' list view to include a 'Color' column
    add_filter('manage_edit-hc_event_categories_columns', 'Event_Categories_List_Column_Headers');
	add_filter('manage_hc_event_categories_custom_column', 'Event_Categories_List_Column_Cells', 10, 3 );
	/*
		ENQUEUE SCRIPTS AND STYLES FOR DISPLAYING EVENTS
	*/
	//Add an action to WordPress init to run our script and style register function
	add_action('init', 'hc_events_register_scripts');
	//Add an action to WordPress footer output whichs prints our scripts and styles to wp_footer() in both admin and public page rendering
	add_action('wp_print_footer_scripts', 'hc_events_print_scripts');
	//add_action('admin_print_scripts ', 'hc_events_print_scripts');
	//We need to add some css to the admin editor
	add_action('admin_print_styles', 'admin_meta_css');
	/*
		SHORTCODES TO DISPLAY EVENTS
	*/
	//Add shortcode support to embed the calendar on any page or post
	add_shortcode('events-calendar-view', 'Display_Event_Calendar');
	//Add shortcode support to embed upcoming events on any page or post
	add_shortcode('events-list-view', 'Display_Events_List_Shortcode');
	//Add shortcode support to embed a list of event_category terms with their descriptions
	add_shortcode('event-categories-list', 'Display_Event_Categories_List_Shortcode');
	/*
		MISC HONEYCOMB INTEGRATION
	*/	
	//Allow full xhtml in our taxonomy descriptions
	remove_filter( 'pre_term_description', 'wp_filter_kses' );
	//Add the new events post type into the wordpress blog feed
	add_filter('request', 'Update_RSS_Feed');
	
	//We need to make sure events are local to the user creating them
	//This function ensures the server running this plugin is set to Universal Coordinate Time
	date_default_timezone_set('UTC');
	
	//Set a global flag that will be used to determine whether or not to display scripts and styles
	//This flag will be turned on when honeycomb displays events and needs to use some scripts and styles
	global $hc_events_add_scripts ;
	$hc_events_add_scripts = false ;
	
	//Set the plugin folder location for use throughout the plugin
   	$hc_plugin_url = plugins_url() . "/honeycomb"; 
	
	
	
	
	
	
	//------------------------------  Honeycomb Plugin Initilization Functions ---------------------------//
	
	//Create the 'Events' custom post type and 'Event Categories' Taxonomy
	//Also create the 'Event Categories' custom meta data database channel
	function Create_Calendar_Custom_Post_Type() {
		global $wpdb;
		//Create the Events post type, the Event Types taxonomy for the post type
		register_post_type('hc_events', 
			array(
				'labels' => array(
					'name' => _x('Events', 'post type general name'),
					'singular_name' => _x('Event', 'post type singular name'),
					'add_new' => _x('Add New', 'Event'),
					'add_new_item' => __('Add New Event'),
					'edit_item' => __('Edit Event'),
					'new_item' => __('New Event'),
					'view_item' => __('View Event'),
					'search_items' => __('Search Events'),
					'not_found' =>  __('No Events found'),
					'not_found_in_trash' => __('No Events found in Trash'), 
					'parent_item_colon' => '',
					'menu_name' => 'Events'		
				),
				'description' => __('Store Individual Events'),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'capability_type' => 'post',
				'hierarchical' => false,
				'rewrite' => array('slug' => 'dance-lessons'),
				'query_var' => true,
				'register_meta_box_cb' => 'init_metaboxes_events', //Function called to build the posts custom meta boxes
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
					'comments',
					'excerpt'),
				'taxonomies' => array('event_categories') //Register the Event Types taxonomy with the new post type
			)
		);
		// Add new taxonomy 'Event Categories' for sorting events
		register_taxonomy('hc_event_categories', array('hc_events'), //Attach the taxonomy strictly to the events custom posts
			array(
				'hierarchical' => true,
				'labels' => array(
					'name' => _x( 'Event Categories', 'taxonomy general name' ),
					'singular_name' => _x( 'Event Category', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Event Categories' ),
					'all_items' => __( 'All Event Categories' ),
					'parent_item' => __( 'Parent Event Category' ),
					'parent_item_colon' => __( 'Parent Event Category:' ),
					'edit_item' => __( 'Edit Event Category' ), 
					'update_item' => __( 'Update Event Category' ),
					'add_new_item' => __( 'Add New Event Category' ),
					'new_item_name' => __( 'New Event Category Name' ),
					'menu_name' => __( 'Event Categories' ),
				),
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'event-categories' ),
			)
		);	

		//Create our event-categories taxonomy meta db table, we'll use this table to store term meta, like color.
		//WordPress will automatically look for {table_prefix}event_categoriesmeta since we registered teh above taxonomy as 'event_categories'
		$wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."hc_event_categoriesmeta` (
		  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `hc_event_categories_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `meta_key` varchar(255) DEFAULT NULL,
		  `meta_value` longtext,
		  PRIMARY KEY (`meta_id`),
		  KEY `meta_key` (`meta_key`),
		  KEY `hc_event_categories_id` (`hc_event_categories_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=".DB_CHARSET." AUTO_INCREMENT=1 ;");
		
		//Let's go ahead and create a shortcut method of $wpdb to call upon this new table
		$wpdb->hc_event_categoriesmeta = $wpdb->prefix."hc_event_categoriesmeta";
	}
	
	//Create the 'Calendar View' 'Events' Sub Menu Page
	function Init_Honeycomb_Event_Calendar () {
		add_submenu_page('edit.php?post_type=hc_events', 'Event Calendar', 'Calendar View', 'manage_options', 'hc-event-calendar',  Display_Event_Calendar); 
	}
	
	
	//Add some style to the event calendar admin drop down menu icon
	//Support for the default left sidebar
	//Support for Ohz Horizontal Admin Bar
	function Honeycomb_Admin_Menu_Icons() {
		?>
			<style type="text/css" media="screen">
				#menu-posts-hcevents .wp-menu-image, 
				#oam_menu-posts-hcevents .wp-menu-image {
                    background: url(<?php echo get_bloginfo('url') ?>/wp-content/plugins/honeycomb/images/hc-admin-calendar-icon.png) no-repeat 6px -17px !important;
                }
                    #oam_menu-posts-hcevents .wp-menu-image {
                        margin: -3px 2px 0 0 ;
                    }
                #menu-posts-hcevents:hover .wp-menu-image, 
				#menu-posts-hcevents.wp-has-current-submenu .wp-menu-image,
                #oam_menu-posts-hcevents:hover.wp-menu-image, 
				#oam_menu-posts-hcevents.wp-has-current-submenu .wp-menu-image {
                    background-position:6px 7px!important;
                }
				<?php 
					//Force some coluymn widths when displaying the event categories page
					if ($_GET['taxonomy'] == 'hc_event_categories') {
				?>
					.column-hc_event_category_color { 
						width: 40px; 
					}
					.column-description {
						width: 240px ;
					}
					.column-posts {
						width: 75px !important ;
					}
				<?php
					}
				?>
            </style>
		<?php 
	}
	
	//This function is called by an action hook
	//Add our new custom post type to the primary wp rss feed
	//This seems to either show the custom post or the defaults posts
	//Not sure why this isn't working as documents yet
	function Update_RSS_Feed($qv) {
		if (isset($qv['feed']))
			$qv['post_type'] = get_post_types();
		return $qv;
	}	
	
	//This function is called by an action hook
	//Register scripts and styles that honeycomb will use to display events	
	function hc_events_register_scripts () {
		//echo "FOOOO";
		//Used to generate the admin and public views of the calendar
		wp_register_script('fullcalendar', plugins_url('jquery-plugins/fullcalendar-1.6.4/fullcalendar/fullcalendar.min.js', __FILE__), array('jquery'), '1.5.1', true);
		//Used to generate the public views of the calendar
		wp_register_script("simpletip", plugins_url('jquery-plugins/jquery.simpletip-1.3.1.min.js', __FILE__), array('jquery'), '1.3.1', true);	
		//This script fails when using the newer version of datepicker. Not sure why.. so we're sticking with the stable one
		wp_register_script("datepicker", plugins_url('/js/jquery-ui-datepicker-1.7.3.js', __FILE__), array('jquery', 'jquery-ui-core'), '1.7.3', true);
		//Provide the jQuery Plugin 'miniColors' color picker scri pt to the 'Event' custom write panel for use in the CPT meta box for event settings
		wp_register_script("minicolors", plugins_url('jquery-plugins/jquery.miniColors-0.1/jquery.miniColors.min.js', __FILE__), array('jquery'), '0.1', true);
	}
	
	//This function is called by an action hook
	//Display scripts and styles that honeycomb will use to display events via wp_footer()
	//Scripts and styles are only displayed if $hc_events_add_scripts has been set to true
	function hc_events_print_scripts () {
		/*
			The $hc_events_add_scripts global MUST be set to try in order for these scripts to display.
			This flag can be set to true in and function or action.
			Internally we enable this flag to true when displaying the calendar or list views in both the admin and public views
		*/
		global $hc_events_add_scripts ;
		if ($hc_events_add_scripts) {
			wp_print_scripts('fullcalendar');
			//Let's only load the scripts we need for the admin display of honeycomb and the public display (lists and grid form)
			global $post;
			//echo "FOOOO";
			if (is_admin()) {
				if($post->post_type == 'hc_events') {
					wp_print_scripts("jquery-ui-core", array('jquery'));  
					wp_print_scripts('datepicker');
					wp_print_scripts('minicolors');
				}
			} else {
				wp_print_scripts('simpletip');
			}
		}
	}
	
	//This function is called by an action hook
	//Display the event calendar
	//This is used to load the admin calendar
	//This is also called via a shortcode to embed the calendar on public pages
	function Display_Event_Calendar () {
		global $hc_events_add_scripts ;
		$hc_events_add_scripts = true ;
		include('php/hc-event-calendar.php');
	}
	
	//The following function is triggered when the admin views the list of 'Events' posts 
	//We want to tap in and add a column for our Event Categories taxonomy
	function Manage_Events_Admin_Columns ($columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = _x('Event Name', 'column name');
		$new_columns['hc_event_categories'] = __('Event Categories');
		$new_columns['hc_event_allow_paying'] = __('Paying Enabled');
		$new_columns['hc_event_repeat'] = __('Repeat');
		$new_columns['date'] = _x('Date', 'column name');
		return $new_columns;
	}
	
	//This function is triggered for each colomn cell in the 'Events' list
	//We want to tap in and display our Event Category in the appropriate cells, with links to edit them
	function Manage_Events_Admin_Column_Content ($column) {
		global $post;
		switch ($column) {
			case 'hc_event_categories':
				$terms = get_the_terms( $post->ID, 'hc_event_categories');
				if ($terms) {
					$count = 0;
					foreach ($terms as $term) {
						echo '<a href="'.get_bloginfo('url').'/wp-admin/edit-tags.php?action=edit&taxonomy=hc_event_categories&post_type=hc_events&tag_ID='.$term->term_id.'">'.$term->name.'</a>';
						if ($count != (count($terms) - 1)) {
							echo ", ";
						}
						$count++;
					}
				}
			break;
			case 'hc_event_allow_paying':
				$hc_event_prepay = get_post_meta($post->ID, 'hc_event_allow_paying', true);
				echo $hc_event_prepay;
			break;
			case 'hc_event_repeat': 
				$hc_event_recurrance = get_post_meta($post->ID, 'hc_event_recurrance', true);
				if (is_array($hc_event_recurrance)) {
					echo "x" . $hc_event_recurrance['hc_event_interval'];
				}
			break;
		} // end switch
	}
	
	//The following function is called in the admin when displaying event_categories edit form
	//The function adds a new row at the bottom of the event_category term edit form, for 'color'
	function Manage_Event_Categories_Columns ($tag, $taxonomy) {
		global $hc_plugin_url;
		/*
		//For some reason we're unable to set a global here to be called prior to page load and use the hc script engine
		//So we're just gonna print the colors script manually.
		global $hc_events_add_scripts ;
		$hc_events_add_scripts = true ;
		*/
		wp_print_scripts('minicolors');
		//wp_enqueue_style( 'miniColors', "$hc_plugin_url/jquery-plugins/jquery.miniColors-0.1/jquery.miniColors.css");
		$hc_event_category_color = get_metadata($tag->taxonomy, $tag->term_id, 'hc_event_category_color', true);
		?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="hc_event_category_color">Color</label></th>
            <td>
            	<link rel="stylesheet" href="<?php echo plugins_url('jquery-plugins/jquery.miniColors-0.1/jquery.miniColors.css', __FILE__) ?>" />
            	<style type="text/css">
					#hc_event_category_color {
						width: 75px ;
					}
				</style>
            	<input type="text" name="hc_event_category_color" id="hc_event_category_color" value="<?php echo $hc_event_category_color; ?>" />  <a href="#" id="hc_event_clear_color">Clear</a>
            	<script type="text/javascript">
					jQuery(function($) {
						$('#hc_event_category_color').miniColors({});
						$('#hc_event_clear_color').click(function(e){
							e.preventDefault()
							$('#hc_event_category_color').val('')
						})
					});
				</script>
                <br />
                <p class="description"><?php _e("This color will be applied to all events in this category. This color can be overridden by per-event color settings or by selecting multiple categories."); ?></p>
            </td>
	    </tr>
        <?php
	}
	//The following function is called when saving the event_category term edit form
	//We tap in and update our term color meta data, we have previous created a new db table to store the meta data
	function Save_Event_Categories ($term_id, $tt_id) {
		if (!$term_id) return;
		if (isset($_POST['hc_event_category_color'])) {
        	update_metadata($_POST['taxonomy'], $term_id, 'hc_event_category_color', $_POST['hc_event_category_color']);
		}
	}
	//Add a 'Color' column to the 'Event Categories' page
	function Event_Categories_List_Column_Headers ($columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['name'] = 'Name';
		$new_columns['description'] = 'Description';
		$new_columns['posts'] = 'Events';
		$new_columns['hc_event_category_color'] = "Color";
		return $new_columns;
	}
	
	//Add a color display in the 'Event Categories' page for each category
	function Event_Categories_List_Column_Cells ($row_content, $column_name, $term_id) {
		switch ($column_name) {
			case 'hc_event_category_color': 
				$color = get_metadata('hc_event_categories', $term_id, 'hc_event_category_color', true);
				//Returns some html to display a box with the event color
				return "<div style='width: 100%; height: 30px; background-color: $color; color: #ffffff; text-align: center; padding-top: 8px;'></div>";
			break;
		}
	}
	
	
	
	
	
	
	
	
	
	//------------------------------  Honeycomb Functions ---------------------------//
	/*
		These are functions used for the creation and update of events. They are located here because they are needed for both the calendar view and the wp-admin custom post type edit page. Functions required by template files for event display are also located here. 
	*/
	
	function create_form_time_options ($selected) {
		$times = array('12:00 am','12:30 am','1:00 am','1:30 am','2:00 am','2:30 am','3:00 am','3:30 am','4:00 am','4:30 am','5:00 am','5:30 am','6:00 am','6:30 am','7:00 am','7:30 am','8:00 am','8:30 am','9:00 am','9:30 am','10:00 am','10:30 am','11:00 am','11:30 am','12:00 pm','12:30 pm','1:00 pm','1:30 pm','2:00 pm','2:30 pm','3:00 pm','3:30 pm','4:00 pm','4:30 pm','5:00 pm','5:30 pm','6:00 pm','6:30 pm','7:00 pm','7:30 pm','8:00 pm','8:30 pm','9:00 pm','9:30 pm','10:00 pm','10:30 pm','11:00 pm','11:30 pm');
		if ($selected == "") { $selected = '7:00am'; } 
		foreach ($times as $time) {
			if ($time == $selected) {
				$html_str .= "<option value='$time' selected='selected'>$time</option>\n";
			} else {
				$html_str .= "<option value='$time'>$time</option>\n";
			}
		}
		return $html_str;
	}
	
	function create_form_recurrance_interval_options ($selected) {
		for($i = 1; $i < 31; $i++) {
			if ($i == $selected) {
				$html_str .= "<option value='$i' selected='selected'>$i</option>\n";
			} else {
				$html_str .= "<option value='$i'>$i</option>\n";
			}
		}
		return $html_str;
	}
	function store_meta_data ($post_id, $meta_data) {
		foreach ($meta_data as $key => $value) {
			//Store the start date
			if (get_post_meta($post_id, $key, false)) {
				//Update the meta value if it already exists
				update_post_meta($post_id, $key, $value);
			} else {
				//Store the meta data if it does not exist yet
				add_post_meta($post_id, $key, $value);
			}
		} //End Foreach $meta_Date
	}
	
	//Adding some custom event meta boxes to the edit event post type page
	function init_metaboxes_events () {
		add_meta_box('hc_event_settings', 'Event Settings', 'meta_box_event_settings', 'hc_events', 'normal', 'high');
	}
		function admin_meta_css () {
			global $post;
			global $hc_plugin_url;
			if($post->post_type == 'hc_events') {
				wp_enqueue_style( 'jquery-ui-smoothness', "$hc_plugin_url/css/jquery-ui-1.8.6.custom.css");
				wp_enqueue_style( 'miniColors', "$hc_plugin_url/jquery-plugins/jquery.miniColors-0.1/jquery.miniColors.css");
			}
		}
		//Event start date meta box
		function meta_box_event_settings () {
			global $hc_events_add_scripts ;
			$hc_events_add_scripts = true ;
			global $post;
			global $hc_plugin_url;
			
			//Select all current / saved meta data for this post
			$hc_event['hc_event_start'] 		= abs(get_post_meta($post->ID, 'hc_event_start', true));
			$hc_event['hc_event_end'] 			= abs(get_post_meta($post->ID, 'hc_event_end', true));
			$hc_event['hc_event_color'] 		= get_post_meta($post->ID, 'hc_event_color', true);
			$hc_event['hc_event_category_color'] = get_post_meta($post->ID, 'hc_event_category_color', true);
			$hc_event['hc_event_allday'] 		= get_post_meta($post->ID, 'hc_event_allday', true);
			$hc_event['hc_event_recurrance']	= get_post_meta($post->ID, 'hc_event_recurrance', true);
			if ($hc_event['hc_event_start'] == 0) {
				$hc_event['hc_event_start']	 = strtotime($post->post_date);
				$hc_event['hc_event_end']	 = strtotime($post->post_date);
				$hc_event['hc_event_allday'] = 1;
			}
			$hc_event['hc_event_allow_paying'] 					= get_post_meta($post->ID, 'hc_event_allow_paying', true);
			$hc_event['hc_event_amount'] 						= get_post_meta($post->ID, 'hc_event_amount', true);
			$hc_event['hc_event_allow_paying_until'] 			= get_post_meta($post->ID, 'hc_event_allow_paying_until', true);
			$hc_event['hc_event_second_amount'] 				= get_post_meta($post->ID, 'hc_event_second_amount', true);
			$hc_event['hc_event_second_allow_paying_until'] 	= get_post_meta($post->ID, 'hc_event_second_allow_paying_until', true);
			$hc_event['hc_event_paper_registration'] 			= get_post_meta($post->ID, 'hc_event_paper_registration', true);
			$hc_event['hc_event_sticky']						= get_post_meta($post->ID, 'hc_event_sticky', true);
			
			if ($hc_event['hc_event_allday'] == "" || $hc_event['hc_event_allday'] == 1) {
				$checked = " checked='checked'";
				$time_selection = "display: none;";
				$allday_question = "";
			} else {
				$checked = "";
				$time_selection = "";
				$allday_question = "?";
			}
			if (is_array($hc_event['hc_event_recurrance'])) {
				$hc_event_is_repeat_checked = " checked='checked'";
			}
			if ($hc_event['hc_event_allow_paying'] == "on") {
				$hc_event_allow_paying_checked = " checked='checked'";
			}
			if ($hc_event['hc_event_sticky'] == "on") {
				$hc_event_sticky_checked = " checked='checked'"; 
			}
			 //Output our meta box html
			?>
            	<script type="text/javascript">
					//jQuery(document).ready(function(){
					jQuery(document).ready(function($) {
						var dates = $( "#hc_event_start_date, #hc_event_end_date" ).datepicker({
							numberOfMonths: 2,
							onSelect: function( selectedDate ) {
								var option = this.id == "hc_event_start_date" ? "minDate" : "maxDate",
									instance = $( this ).data( "datepicker" ),
									date = $.datepicker.parseDate(
										instance.settings.dateFormat ||
										$.datepicker._defaults.dateFormat,
										selectedDate, instance.settings );
								dates.not( this ).datepicker( "option", option, date );
							}
						})
						
						var hc_event_selected_start = $('#hc_event_start_date').datepicker('getDate')
						var hc_event_selected_end = $('#hc_event_end_date').datepicker('getDate')
						
						//var hc_event_selected_start_str = (hc_event_selected_start.getMonth() + 1) + '/' + hc_event_selected_start.getDate() + '/' + hc_event_selected_start.getFullYear()
						
						
						$('#hc_event_allow_paying_unitl').datepicker({
							//minDate: hc_event_selected_start,
							//maxDate: hc_event_selected_end
						});
						
						$('#hc_event_second_allow_paying_until').datepicker({
							maxDate: hc_event_selected_end
						});
						
						/*
						//List of current event colors
						var hc_event_colors = [
							'#4e85ca', //Light Blue 
							'#7136e7', //Deep Blue
							'#6c00a4', //Purple
							'#9464e4', //Light Purple
							'#42202f', //Deep Purple
							'#920a2d', //Burgandy
							'#d06956', //Pink
							'#e44b00', //Dark Orange
							'#faa012', //Orange
							'#0dd703', //Green
							'#3b5b3c', //Dark Green		
							'#515151', //Charcoal						
							'#151515'  //Black
						];
						for (var i in hc_event_colors) {
							if (hc_event_colors[i] == '<?php echo $hc_event['hc_event_color'] ?>') {
								var hc_event_color_index = i;
							}
						}
						$('#hc_event_colors').colorPicker({			
							defaultColor: parseInt(hc_event_color_index), // index of the default color (optional)   
							color: hc_event_colors, 
							click: function(color){
								jQuery('#hc_event_color').attr('value', color);
							}
						});
						*/
						
						//
						//----- Evemt AllDay Form Controllers
						//
						//Enable or disable the all day  / timed event functions
						if($('#hc_event_allday').is(':checked')) {
							/*
							$('#hc_event_start_time').hide()
							$('#hc_event_end_date').hide()
							$('#hc_event_end_time').hide()		
							*/
							$('.hc_event_not_allday').hide()
						}
						$('#hc_event_allday').live('change', function(event) {
							if($(this).is(':checked')) {
								/*
								$('#hc_event_start_time').hide()
								$('#hc_event_end_time').hide()	
								$('#hc_event_end_date').hide();			
								*/
								$('.hc_event_not_allday').hide()								
							} else {			
								$('.hc_event_not_allday').show()							
								/*
								$('#hc_event_start_time').show()
								$('#hc_event_end_time').show()
								$('#hc_event_end_date').show();
								*/
							}
						});	
						
						
						//
						//----- Evemt Recurrance Form Controllers
						//
						//Enable or Disable event recurrance
						if ($('#hc_event_is_repeat').is(':checked')) {
							$('#hc_event_recurrance').show();
						} else {
							$('#hc_event_recurrance').hide();
						}
						$('#hc_event_is_repeat').live('change', function(event) {
							if ($(this).is(':checked')) {
								$('#hc_event_recurrance').show();
							} else {
								$('#hc_event_recurrance').hide();
							}
						});
						//Update the interval noun field for descriptive recurrance output
						$('#hc_event_frequency').live('change', function(event){
							$('#hc_event_frequency option:selected').each(function(){
								$('#hc_event_interval_frequency_noun').text($(this)[0].value.splice('ly').ucFirst() + '(s)')
							});
						});
						
						$('#clear-paying-until').click(function(e){
							e.preventDefault()
							$('#hc_event_allow_paying_unitl').val('')
						})
						
						if ($('#hc_event_allow_paying').is(':checked')) {
							$('.hc_paying_allowed').show()	
						} else {
							$('.hc_paying_allowed').hide()	
						}
						
						$('#hc_event_allow_paying').live('change', function(event) {
							if ($(this).is(':checked')) {
								$('.hc_paying_allowed').show()	
							} else {
								$('.hc_paying_allowed').hide()	
							}
						})
						
						$("#hc_event_color").miniColors({							
						});
						
						$('#hc_event_clear_color').click(function(e){
							e.preventDefault()
							$('#hc_event_color').val(' ')
						})
					});
				</script>
                <style type="text/css">
					#hc_event_meta_table {
						width: 100% ;
					}
						#hc_event_meta_table td {
							background-color: rgb(230,230,230) ;
						}
				</style>
                <table cellpadding="10" cellspacing="5" border="0" id="hc_event_meta_table">
                	<tr>
                    	<td>   
                            <table cellpadding="0" cellspacing="5" border="0">
                            	<tr>
                                	<td>
                                    	<label for="hc_event_start">Start</label> <input id="hc_event_start_date" type='text' name='hc_event_start' value='<?php echo date('n/j/Y', $hc_event['hc_event_start']) ?>' />
                                    </td><td class='hc_event_not_allday'>
                                        <select name="hc_event_start_time" id="hc_event_start_time">
                                            <?php echo create_form_time_options(date('g:i a', $hc_event['hc_event_start']));	?>
                                        </select>
                                   </td><td class='hc_event_not_allday'>
			                            To
                                   </td><td class='hc_event_not_allday'>
										<select name="hc_event_end_time" id="hc_event_end_time">
                                            <?php echo create_form_time_options(date('g:i a', $hc_event['hc_event_end']));	?>
                                        </select>
                                   </td><td class='hc_event_not_allday'>
                                        <input id="hc_event_end_date" type='text' name='hc_event_end' value='<?php echo date('n/j/Y', $hc_event['hc_event_end']) ?>' class='widefat' />
                                   </td>
                              </tr>
                           </table>
                           <table cellpadding="0" cellspacing="5" border="0">
                            	<tr>
                                	<td>
			                        	<input type="checkbox" name="hc_event_allday" id="hc_event_allday" value="1" <?php echo $checked; ?>/>
                                        <label for="hc_event_allday" id="hc_event_allday_label">All Day<?php echo $allday_question;?></label>
                                    </td><td>
			                            <input type="checkbox" name="hc_event_is_repeat" id="hc_event_is_repeat" value="1" <?php echo $hc_event_is_repeat_checked; ?> />
                                        <label for="hc_event_is_repeat">Repeats?</label>
									</td><td>
                                        <div id="hc_event_recurrance">
                                            <select name="hc_event_frequency" id="hc_event_pattern">
                                                <?php
                                                    $hc_event_frequencies = array('weekly', 'monthly', 'yearly');
                                                    foreach ($hc_event_frequencies as $hc_event_frequency) {
                                                        if ($hc_event_frequency == $hc_event['hc_event_recurrance']['hc_event_frequency']) {
                                                            $hc_event_selected_frequency = " selected='selected'";
                                                        }
                                                        echo "<option value='$hc_event_frequency'>".ucfirst($hc_event_frequency)."</option>\n";
                                                    }
                                                ?>
                                            </select>
                                            <select name="hc_event_interval" id="hc_event_interval">
                                                <?php echo create_form_recurrance_interval_options($hc_event['hc_event_recurrance']['hc_event_interval']); ?>
                                            </select>
                                            Time(s)
                                            <br />
                                            <?php
                                                //$hc_event_repeat_summary = ucfirst(str_replace('ly', '(s)', $frequency));
                                            ?>
                                            <span id="hc_event_summary"><?php echo $hc_event_repeat_summary; ?></span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                </tr>
                <tr>
					<td>
                    	<table cellpadding="0" border="0" cellspacing="5">
                        	<tr>
                            	<td valign="top">
                                	<input type="checkbox" id="hc_event_allow_paying" name="hc_event_allow_paying" <?php echo $hc_event_allow_paying_checked;?> />
                                    <label for="hc_event_allow_paying">Allow paying?</label> &nbsp; &nbsp;
                                </td>
                                <td class='hc_paying_allowed'>
                                	<table>
                                    	<tr>
                                        	<td>                                            
                                                <label for="hc_event_amount">for &nbsp; </label>
                                                $ <input type="text" name="hc_event_amount" id="hc_event_amount" value="<?php echo $hc_event['hc_event_amount']; ?>" size="10" />
                                            </td>
                                            <td class='hc_paying_allowed'>
                                                <label for="hc_event_allow_paying_unitl">until</label>
                                                <input type="text" name="hc_event_allow_paying_until" id="hc_event_allow_paying_unitl" value="<?php echo $hc_event['hc_event_allow_paying_until'] ?>" />
                                            </td>
                                            <td class='hc_paying_allowed'>
                                                &nbsp; <a href="" title="Clear Until Date" id="clear-paying-until">Clear</a>
                                            </td>
                                        </tr>
                                        <tr>
                                        	<td>
                                            	<label for="hc_event_second_amount">then</label>
                                                $ <input type="text" name="hc_event_second_amount" id="hc_event_second_amount" value="<?php echo $hc_event['hc_event_second_amount']; ?>" size="10" />
                                            </td>
                                            <td>
                                            	<label for="hc_event_second_allow_paying_until">until</label>
                                                <input type="text" name="hc_event_second_allow_paying_until" id="hc_event_second_allow_paying_until" value="<?php echo $hc_event['hc_event_second_allow_paying_until'] ?>" />
                                            </td>
                                            <td>
                                            	&nbsp; <a href="" title="Clear Until Date" id="clear-second-paying-until">Clear</a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
							<tr>
                            	<td class="hc_paying_allowed" colspan="2">
                                	<br />
                                	<label for="hc_event_paper_registration">Paper Registration Form Link</label>
                                    <input type="text" name="hc_event_paper_registration" id="hc_event_paper_registration" value="<?php echo $hc_event['hc_event_paper_registration'] ?>" size="80" />
                                </td>
                            </tr>
                        </table>
                    </td>    
                </tr>
                <tr>
                	<td>
                        <table>
                        	<tr>
								<?php
                                    //Gather the event categories for this event
									if ($terms = get_the_terms($post->ID, 'hc_event_categories')) {
	                                    //If there is more than one category give the user the option to choose which category color to inherit
										if (count($terms) > 1) {
											//Display a dropdown menu for the user to select which event category supplies the event color
											?>
											<td>
												Which category color?
											</td><td>
												<select name='hc_event_category_color' id='hc_event_category_color'>
													<?php
													//hc_event_category_color stores the term id of the color category
													if ($hc_event['hc_event_category_color'] == "") {
														$hc_event['hc_event_category_color'] = $terms[0]->term_id;
													}
													foreach ($terms as $term) {
														if ($term->term_id == $hc_event['hc_event_category_color']) {
															$selected_term = "selected='selected'";
														} else {
															$selected_term = "";
														}
														echo "<option value='{$term->term_id}' $selected_term>{$term->name} &nbsp;</option>";
													}
													$event_category_color = get_metadata('hc_event_categories', $hc_event['hc_event_category_color'], 'hc_event_category_color', true);
													?>
												</select>
											</td><td>
												<div style='width: 15px; height: 15px; background-color: <?php echo $event_category_color;?>'></div>									
											</td>
											<?php
										} else if (count($terms) > 0){
											//There is only one event category, only show that one - no dropdown for the user to select from
											$event_category_color = get_metadata('hc_event_categories', $terms[0]->term_id, 'hc_event_category_color', true);
											?>
											<td>
												Color inherited from <?php echo $terms[0]->name ?> category: 
											</td><td>
												<input type="hidden" name="hc_event_category_color" value="<?php echo $terms[0]->term_id;?>" />
												<div style='width: 15px; height: 15px; background-color: <?php echo $event_category_color; ?>'></div>
											</td>
											<?php
										} 
									?>
                                    	<td>
                                            <!-- Display an option for the user to override the event category color or select a color if no category is chosen -->
                                            OR override the category color: 
                                    <?php
									} else {
										?>
                                        <td>
                                        	Set a color for this event: 
                                        <?php
									}
                                ?>
	                                <input type="text" name="hc_event_color" id="hc_event_color" class="colors" size="7" value="<?php echo $hc_event['hc_event_color'] ?>" />  <a href="#" id="hc_event_clear_color">Clear</a>
                                </td>
                        	</tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td>
                    	<table cellpadding="0" cellspacing="5" border="0">
                        	<tr>
                            	<td>
                                	<label for="hc_event_sticky">Don't show this as an event, just a sticky post</label>
                                    <input type="checkbox" name="hc_event_sticky" <?php echo $hc_event_sticky_checked; ?> />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
               </table>
			<?php
		}
		
		function save_meta_box_event_settings ($post_id, $post) {
			
			//echo "<pre>".print_r($_POST, true)."</pre>";
			
			if ($post->post_type != 'revision') {
				//Lets store all our meta box event settigns in an array and loop through it to store / update data
				//echo $_POST['hc_event_start'] . "-" . $_POST['hc_event_end'];
				//01/06/1970-2/17/1970
				//echo strtotime($_POST['hc_event_start']) . "-" . strtotime($_POST['hc_event_end']);
				//This value is given sometimes by datepicker on the first use, leaving it stuck in 1970
				//432000-4060800
				//Correct Values
				//1294272000-1294272000
				
				//print_r($_POST);
				
				//Make sure to set allday to 0 if it has not been set during form input (inactive)
				if ($_POST['hc_event_allday'] == "1") {
					//All day has been unchecked, so the event will have a start and end time (hours)
					$meta_data['hc_event_allday'] = 1;
					$meta_data['hc_event_start'] = strtotime($_POST['hc_event_start']);
					$meta_data['hc_event_end'] = strtotime($_POST['hc_event_end']);					
				} else {
					$meta_data['hc_event_allday'] = 0;
					
					$meta_data['hc_event_start'] = strtotime(date('j F Y', strtotime($_POST['hc_event_start'])) . ' ' . $_POST['hc_event_start_time']);
					$meta_data['hc_event_end'] = strtotime(date('j F Y', strtotime($_POST['hc_event_end'])) . ' ' . $_POST['hc_event_end_time']);
					
					//echo date('j F Y', strtotime($_POST['hc_event_start'])) . ' ' . $_POST['hc_event_start_time'];
					//echo date('j F Y', strtotime($_POST['hc_event_end'])) . ' ' . $_POST['hc_event_end_time'];
				}
				
				if ($_POST['hc_event_is_repeat']) {
					//Generate the recurrance pattern
					$meta_data['hc_event_recurrance'] = array(
						"hc_event_frequency" => $_POST['hc_event_frequency'],
						"hc_event_interval" => $_POST['hc_event_interval']
					);			
				} else {
					$meta_data['hc_event_recurrance'] = "";			
				}
				
				$meta_data['hc_event_color'] = $_POST['hc_event_color'];
				$meta_data['hc_event_category_color'] = $_POST['hc_event_category_color'];
				$meta_data['hc_event_allow_paying'] = $_POST['hc_event_allow_paying'];
				$meta_data['hc_event_amount'] = $_POST['hc_event_amount'];
				$meta_data['hc_event_allow_paying_until'] = $_POST['hc_event_allow_paying_until'];
				$meta_data['hc_event_second_amount'] = $_POST['hc_event_second_amount'];
				$meta_data['hc_event_second_allow_paying_until'] = $_POST['hc_event_second_allow_paying_until'];
				$meta_data['hc_event_paper_registration'] = $_POST['hc_event_paper_registration'];
				$meta_data['hc_event_sticky'] = $_POST['hc_event_sticky'];
				//print_r($meta_data);
				
				foreach ($meta_data as $key => $value) {
					//Store the start date
					if (get_post_meta($post->ID, $key, false)) {
						//Update the meta value if it already exists
						update_post_meta($post->ID, $key, $value);
					} else {
						//Store the meta data if it does not exist yet
						add_post_meta($post->ID, $key, $value);
					}
				} //End Foreach $meta_Date
			} //end Revision check
		}		
	
	
	
	function get_hc_event ($hc_event_id) {
		global $wpdb ;
		global $blog_id;
		//Check if this plugin is being used in a multi-site blog
		//If it is we want to select event posts from the current blog only	
		if ( is_multisite() ) { $posts_table = "wp_" . $blog_id . "_posts"; } 
		else { $posts_table = "wp_posts"; }
		//Select all event posts
		$hc_event = $wpdb->get_results("SELECT * FROM " . $posts_table . " WHERE ID = $hc_event_id AND post_type = 'hc_events' AND post_status = 'publish'", ARRAY_A);
		$hc_event = $hc_event[0];
		$hc_event['hc_event_start'] = get_post_meta($hc_event_id, 'hc_event_start', true);
		$hc_event['hc_event_end'] = get_post_meta($hc_event_id, 'hc_event_end', true);
		$hc_event['hc_event_color'] = get_post_meta($hc_event_id, 'hc_event_color', true);
		//We need to make sure we're passing an int for the allday flag
		$hc_event['hc_event_allday'] = abs(get_post_meta($hc_event_id, 'hc_event_allday', true));
		$hc_event['hc_event_recurrance'] = get_post_meta($hc_event_id, 'hc_event_recurrance', true);
		$hc_event['hc_event_allow_paying'] = get_post_meta($hc_event_id, 'hc_event_allow_paying', true);
		$hc_event['hc_event_amount'] = get_post_meta($hc_event_id, 'hc_event_amount', true);
		$hc_event['hc_event_allow_paying_until'] = get_post_meta($hc_event_id, 'hc_event_allow_paying_until', true);
		$hc_event['hc_event_second_amount'] = get_post_meta($hc_event_id, 'hc_event_second_amount', true);
		$hc_event['hc_event_second_allow_paying_until'] = get_post_meta($hc_event_id, 'hc_event_second_allow_paying_until', true);
		$hc_event['hc_event_paper_registration'] = get_post_meta($hc_event_id, 'hc_event_paper_registration', true);
		$hc_event['hc_event_sticky'] = get_post_meta($hc_event_id, 'hc_event_sticky', true);
		
		
		
		if ($hc_event['post_excerpt'] != "") {
			$hc_event['hc_event_post_summary'] = $hc_event['post_excerpt'];
		} else {
			if ($hc_event['post_content'] != "") {
				$hc_event['hc_event_post_summary'] = substr(str_replace("\r\n", "", strip_tags($hc_event['post_content'])), 0, 100) . ' ...';
			} else {
				$hc_event['hc_event_post_summary'] = "";
			}
		}
		//Gather the event post url
		$hc_event['hc_event_url'] = get_permalink($hc_event_id);
		//Gather the event post terms
		$hc_event_terms_obj = wp_get_object_terms($hc_event_id, 'hc_event_categories');
		//We only want the term names
		$hc_event_terms = array();
		foreach ($hc_event_terms_obj as $hc_event_term) { $hc_event_terms[] = $hc_event_term->name; }
		//Lets complie our terms into a comma delimited string for transportation and presentation
		$hc_event['hc_event_terms'] = implode(', ', $hc_event_terms);
		//We need to add the event class name
		$hc_event['hc_event_classname'] = "hc_event_event-" . $hc_event_id;
		
		return $hc_event;
	}
	
	
	//REQUEST ALL EVENTS
	//Pre: none
	//POST: Return a multi-dimensional php array
	//Our event posts are stored singuarly, so an event that repeats 4 times is only one event post
	//The recurrance data is stored as a array in the event post meta data
	//pull in all post data
	//loop through and call post meta to retrieve our event settings
	//during the loop build an array of events
	//if the event has been set for recurrance we build the recurrance using the 'When' library
	//and generate the additional events in the array
	function request_all_events($filterTerms="") {
		global $wpdb ;
		global $blog_id;
		if ($filterTerms != "") {				
			$filterTerms = explode(', ', $filterTerms);
		}
		//Check if this plugin is being used in a multi-site blog
		//If it is we want to select event posts from the current blog only	
		if ( is_multisite() ) { $posts_table = "wp_" . $blog_id . "_posts"; } 
		else { $posts_table = "wp_posts"; }

		//Select all event posts
		//$hc_event_posts = $wpdb->get_results("SELECT * FROM " . $posts_table . " WHERE post_type = 'hc_events' AND post_status = 'publish'", ARRAY_A);

		$args =  [
			// fetch events up to a year old
			// and a year into the future
			'meta_query' => [
				[
					'key' => 'hc_event_start',
					'value' => strtotime( '-1 year' ),
					'compare' => '>=',
				],
				[
					'key' => 'hc_event_start',
					'value' => strtotime( '+1 year' ),
					'compare' => '<=',
				]
			],
			'post_type' => 'hc_events',
			'post_status' => 'publish',
			'posts_per_page' => 100,
		];
		$events = get_posts( $args );
		
		//echo "\nBEGIN COUNT:" . count($events);
		$hc_event_recurrance_posts = array();
		//Select all event post associated meta data (event settings like start, end, color, allday, and recurrance settings)
		foreach ($events as $key => $event) {

			$hc_event_posts[$key]['ID'] = $event->ID;
			$hc_event_posts[$key]['post_title'] = $event->post_title;
			$hc_event_posts[$key]['post_author'] = $event->post_author;

			//We will be compiling an array of all the event start dates
			$hc_event_posts[ $key ]['hc_event_start'] = intval( get_post_meta($event->ID, 'hc_event_start', true) );
			$hc_event_posts[ $key ]['hc_event_end'] = intval( get_post_meta($event->ID, 'hc_event_end', true) );
			$hc_event_posts[ $key ]['hc_event_category_color'] = get_post_meta($event->ID, 'hc_event_category_color', true);
			if ($hc_event_posts[ $key ]['hc_event_category_color'] == "" || !$hc_event_posts[ $key ]['hc_event_category_color']) {
				if ($terms = get_the_terms($event->ID, 'hc_event_categories')) {
					$hc_event_posts[ $key ]['hc_event_category_color'] = $terms[0]->term_id;
				}
			}
			$hc_event_posts[ $key ]['hc_event_color'] = get_post_meta($event->ID, 'hc_event_color', true);
			$hc_event_posts[ $key ]['hc_event_allday'] = get_post_meta($event->ID, 'hc_event_allday', true);
			$hc_event_posts[ $key ]['hc_event_recurrance'] = get_post_meta($event->ID, 'hc_event_recurrance', true);
			$hc_event_posts[ $key ]['hc_event_allow_paying'] = get_post_meta($event->ID, 'hc_event_allow_paying', true);
			$hc_event_posts[ $key ]['hc_event_amount'] = get_post_meta($event->ID, 'hc_event_amount', true);
			$hc_event_posts[ $key ]['hc_event_allow_paying_until'] = get_post_meta($event->ID, 'hc_event_allow_paying_until', true);
			$hc_event_posts[ $key ]['hc_event_second_amount'] = get_post_meta($event->ID, 'hc_event_second_amount', true);
			$hc_event_posts[ $key ]['hc_event_second_allow_paying_until'] = get_post_meta($event->ID, 'hc_event_second_allow_paying_until', true);
			$hc_event_posts[ $key ]['hc_event_paper_registration'] = get_post_meta($event->ID, 'hc_event_paper_registration', true);

			//We'll use this for key-matching below to sort our events array by start date
			$hc_event_start_dates[] = $hc_event_posts[ $key ]['hc_event_start'];
			
			if ($hc_event_posts[ $key ]['post_excerpt'] != "") {
				$hc_event_posts[ $key ]['hc_event_post_summary'] = $hc_event_posts[ $key ]['post_excerpt'];
			} else {
				if ($hc_event_posts[ $key ]['post_content'] != "") {
					$hc_event_posts[ $key ]['hc_event_post_summary'] = substr(str_replace("\r\n", "", strip_tags($hc_event_posts[ $key ]['post_content'])), 0, 100) . ' ...';
				} else {
					$hc_event_posts[ $key ]['hc_event_post_summary'] = "";
				}
			}
			//Gather the event post url
			$hc_event_posts[ $key ]['hc_event_url'] = get_permalink($event->ID);
			//Gather the event post terms
			$hc_event_terms_obj = wp_get_object_terms($event->ID, 'hc_event_categories');
			//We only want the term names
			$hc_event_terms = array();
			foreach ($hc_event_terms_obj as $hc_event_term) { $hc_event_terms[] = $hc_event_term->name; }
			//Lets complie our terms into a comma delimited string for transportation and presentation
			$hc_event_posts[ $key ]['hc_event_terms'] = implode(', ', $hc_event_terms);
			//We need to make sure we're passing an int for the allday flag
			$hc_event_posts[ $key ]['hc_event_allday'] = intval($hc_event_posts[ $key ]["hc_event_allday"]);
			//We need to add the event class name
			$hc_event_posts[ $key ]['hc_event_classname'] = "hc_event_event-" . $event->ID;
			
			//If the recurrance field holds a serialized array of recurrance data (interval, frequency, and offset)
			//This conditional will create new singular event for each recurrance date
			//Each recurring group will all have the same ID so the calendar can treat them as one and relocate the group is desired
			if (is_array($hc_event_posts[ $key ]['hc_event_recurrance'])) {
				//Use the 'When' library to generate our start and end recurrance dates				
				require_once "php/When v3/When.php";
				//Generate the recurring event start dates
				$hc_event_datetime_event_start = new DateTime(date("Y-m-d H:i:s", $hc_event_posts[ $key ]['hc_event_start']));
				$hc_event_start_datetimes = new When();
				$hc_event_start_datetimes->recur($hc_event_datetime_event_start, $hc_event_posts[ $key ]['hc_event_recurrance']['hc_event_frequency']);
				$hc_event_start_datetimes->count($hc_event_posts[ $key ]['hc_event_recurrance']['hc_event_interval'] + 1);
				$hc_event_start_date_timestamps = array();
				while($result = $hc_event_start_datetimes->next()) {
					//start_date_timestamps is now an array containing our new reccurrance date start times
					$hc_event_start_date_timestamps[] = $result->format('U');
				}
				//Generate the recurring event end dates
				$hc_event_datetime_event_end = new DateTime(date("Y-m-d H:i:s", $hc_event_posts[ $key ]['hc_event_end']));
				$hc_event_end_datetimes = new When();
				$hc_event_end_datetimes->recur($hc_event_datetime_event_end, $hc_event_posts[ $key ]['hc_event_recurrance']['hc_event_frequency']);
				$hc_event_end_datetimes->count($hc_event_posts[ $key ]['hc_event_recurrance']['hc_event_interval'] + 1);
				$hc_event_end_date_timestamps = array();
				while($result = $hc_event_end_datetimes->next()) {
					//end_date_timestamps is now an array containing our new reccurrance date end times
					$hc_event_end_date_timestamps[] = $result->format('U');
				}

				//Generate the new additional events to plot onto the calendar
				for ($i = 1; $i < $hc_event_posts[ $key ]['hc_event_recurrance']['hc_event_interval'] + 1; $i++) {
					//Add onto our rolling start date list with this new event
					$hc_event_start_dates[] = $hc_event_start_date_timestamps[$i];	
					//Build each new occurance of thsi event
					$hc_event_recurrance_posts[] = array(
						'ID' => $hc_event_posts[$key]['ID'],
						'post_title' => $hc_event_posts[$key]['post_title'],
						'post_author' => $hc_event_posts[$key]['post_author'],
						'hc_event_post_summary' => $hc_event_posts[$key]['hc_event_post_summary'],
						'hc_event_color' => $hc_event_posts[$key]['hc_event_color'],
						'hc_event_category_color' => $hc_event_posts[$key]['hc_event_category_color'],
						'hc_event_classname' => "hc_event_event-" . $hc_event_posts[$key]['ID'],
						'hc_event_start' => intval( $hc_event_start_date_timestamps[$i] ),
						'hc_event_end' => intval( $hc_event_end_date_timestamps[$i] ),
						'hc_event_allday' => $hc_event_posts[$key]['hc_event_allday'],
						'hc_event_recurrance' => "child-" . $i,
						'hc_event_url' => $hc_event_posts[$key]['hc_event_url']
					);
				}
			} 
		}//END FOREACH EVENT POSTS
		
		//Merge our new recurring events onto the repo event bin
		$hc_event_posts = array_merge( $hc_event_posts, $hc_event_recurrance_posts );
	
		/*
		//Filter our list of events by any terms passed into this functions
		if (is_array($filterTerms)) {
			$addedEvent = false;
			//Search the current terms for the filter terms
			foreach ($filterTerms as $hc_event_term) {
				//If our filtered term is in the events we'll add it to a temp arr of events
				//We will only add this event once by setting and checking a bool below
				if (is_int(array_search($hc_event_term, $hc_event_terms)) && !$addedEvent) {
					$e[] = $hc_event_post;
					$addedEvent = true;
				} 
			}
		}
		//If terms were used to filter the event results, we want to convert our $hc_event_posts arr to only contain filtered results
		if (is_array($filterTerms)) { $hc_event_posts = $e; }
		*/

		usort( $hc_event_posts, function( $a, $b ) {
			return ($a['hc_event_start'] < $b['hc_event_start']) ? -1 : (($a['hc_event_start'] > $b['hc_event_start']) ? 1 : 0);
		} );

		//Return all our individual events in a multi-dimensional array, sorted by their start dates DESC
		return $hc_event_posts;
	} // End function request_all_events
	
	//Function to display upcoming events via a shortcode in pages / posts
	function Display_Events_List_Shortcode($atts) {
		//Grab the limit field set in the shortcode and pass it to our Display_Upcoming_Events function below
		Display_List_Of_Events($atts['terms'], $atts['limit']);
	}
	
	//Generate and display a list of upcoming events
	//Display_List_Of_Events ( [string $term][, int $limit] )
	function Display_List_Of_Events ($hc_event_terms='', $hc_event_limit=1) {
		global $hc_plugin_url;
		$hc_events = request_all_events($hc_event_terms);

		//Filter our events list down to only future and happening events
		//Add a flag for currently happening events
		foreach($hc_events as $i => $hc_event) {
			if ($hc_event['hc_event_start'] > time()) {
				$hc_eventsArr[$i] = $hc_event;
			}
		}


		$hc_events = array_values( $hc_eventsArr );
		//Output our upcoming events container div
		?>
        <link rel='stylesheet' type='text/css' href='<?php echo $hc_plugin_url; ?>/css/honeycomb.1.0.0.css' />
    	<div id="hc_event_list">
            <?php 
				//Check for any events
				if (count($hc_events) > 0) {
					//If a limit has been set, lets make sure we dont output more containers than there are events
					//I.e. request 5 events, but there are only 3
					if (count($hc_events) < $hc_event_limit) { 
						$hc_event_limit = count($hc_events); 
					}
					for($i = 0; $i < $hc_event_limit; $i++) { 
						//Trucate the description
						/*
						$hc_event_description = $hc_events[$i]['post_content'];
						if (strlen($hc_event_description) > 100) {
							$hc_event_description = substr($hc_event_description, 0, 150)."...";
						}
						*/
						//print_r($hc_events);
						$hc_event_description = $hc_events[$i]['hc_event_post_summary'];
						
						if ($hc_events[$i]['hc_event_happening']) {
							$hc_event_class = " happening";
							//Show the event span date, 11/06 - 11/07
							$hc_event_date = date("m/d - ", $hc_events[$i]['hc_event_start']) . date("m/d - ", $hc_events[$i]['hc_event_end']);	
						} else {
							$hc_event_class = "";
							//Show the event star date, 11/06
							$hc_event_date = date("m/d - ", $hc_events[$i]['hc_event_start']);						
						}
						//Output each event item div / content
						?>
							<div class="hc_event_item<?php echo $hc_event_class ;?>">
								<span class='hc_event_item_date'><b><?php echo $hc_event_date ;?></b></span>
								<b><a href="<?php echo $hc_events[$i]['hc_event_url'];?>"><?php echo $hc_events[$i]['post_title'] ;?></a></b>
                                <br />
								<p><?php echo $hc_event_description ;?></p>
							</div>
						<?php 						
	                }//end events for loop 
				} else {
					//There are no upcoming events
					echo "<p class='hc_event_no_events'>Check back soon! There are no upcoming events at this time.</p>";
				}
            ?>
       </div>
       <?php
	} // End function Display_Upcoming_Events
	
	//Display a list of event types(taxonomy) with links to their pages
	function Display_Event_Categories_List_Shortcode ($atts) {
		//Determine if the user set a length
		if ( empty( $atts['length'] ) ) { $length = 255; }
		else { $length = $atts['length']; }
		return Display_Event_Type_List($length);
	}// End Display_Event_Type_List
		function Display_Event_Type_List ($length = 255) {
			foreach (get_terms('hc_event_categories', 'order_by=name&hide_empty=0') as $hc_event_categories) {
				$html_str .= "<h2><a href='" . get_bloginfo('url') . "/event-categories/{$hc_event_categories->slug}' title='See events in {$hc_event_categories->name}'>{$hc_event_categories->name}</a> -</h2> ";
				$html_str .= "<p>" . substr($hc_event_categories->description, 0, $length) . " ... <a href='" . get_bloginfo('url') . "/event-categories/{$hc_event_categories->slug}' title='Read more about {$hc_event_categories->name}'>more</a></p><br /><br />";
			}
			return $html_str;
		}
?>