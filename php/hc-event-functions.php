<?php
	/*
	
		This page is contains functions called via ajax from scripts in hc-event-calendar.php
	
	*/


	//Reference wp-load to provide wordpress functions / globals to this external script
	//This should point to your wordpress root/wp-load.php file
	require( '../../../../wp-load.php' );
	
	//date_default_timezone_set('America/New_York');
	
	date_default_timezone_set('UTC');
	
	//************* AJAX CALLS ***************//
	
	//CREATE NEW EVENT CREATION FORM
	if ($_REQUEST['hc_ajax_request'] == "create_event_creation_form") {
		create_event_creation_form();
	}
	
	//CREATE NEW EVENT
	if ($_REQUEST['hc_ajax_request'] == "process_event_creation_form") {
		echo json_encode(process_new_event_form($_REQUEST['hc_event_title'], $_REQUEST['hc_event_color'], $_REQUEST['hc_event_event_categories'], $_REQUEST['hc_event_start'], $_REQUEST['hc_event_end'], $_REQUEST['hc_event_start_time'], $_REQUEST['hc_event_end_time'], $_REQUEST['hc_event_allday'], $_REQUEST['hc_event_is_repeat'], $_REQUEST['hc_event_frequency'], $_REQUEST['hc_event_interval'], $_REQUEST['hc_event_offset']));
	}	
	
	//EDIT EVENT FORM 
	if ($_REQUEST['hc_ajax_request'] == "create_edit_event_form") {
		create_edit_event_form($_REQUEST['hc_event_id'], $_REQUEST['hc_event_recurrance']);
	}

	//EDIT EVENT FORM 
	if ($_REQUEST['hc_ajax_request'] == "process_event_edit_form") {
		echo json_encode(process_event_edit_form($_REQUEST['hc_event_id'], $_REQUEST['hc_event_title'], $_REQUEST['hc_event_color'], $_REQUEST['hc_event_categories'], $_REQUEST['hc_event_start'], $_REQUEST['hc_event_end'], $_REQUEST['hc_event_start_time'], $_REQUEST['hc_event_end_time'], $_REQUEST['hc_event_allday'], $_REQUEST['hc_event_is_repeat'], $_REQUEST['hc_event_frequency'], $_REQUEST['hc_event_interval']));
	}
	
	//DELETE EVENT
	if ($_REQUEST['hc_ajax_request'] == "delete_event") {
		delete_event($_REQUEST['hc_event_id']);
	}
	
	//Request all events in an JSON array
	if ($_REQUEST['hc_ajax_request'] == "request_all_JSON_events") {
		echo json_encode(request_calendar_events());
	}
	
	//Move an event
	if ($_REQUEST['hc_ajax_request'] == "update_moved_event") {
		echo json_encode(update_moved_event($_REQUEST['hc_event_id'], $_REQUEST['hc_event_start'], $_REQUEST['hc_event_end'], $_REQUEST['hc_event_allday']));
	}
	
	//Resize an event
	if ($_REQUEST['hc_ajax_request'] == "update_resized_event") {
		echo json_encode(update_resized_event($_REQUEST['hc_event_id'], $_REQUEST['hc_event_end']));
	}
	
	//************* FUNCTIONS ***************//
	
	function request_calendar_events() {
		//Our calendar needs a slimed down array, with on specific event data. 
		$hc_events = request_all_events();
		
		foreach ($hc_events as $i => $hc_event) {
			$hc_events_out[$i]['id'] 			= $hc_event['ID'];
			$hc_events_out[$i]['title'] 		= $hc_event['post_title'];
			$hc_events_out[$i]['summary'] 		= $hc_event['hc_event_post_summary'];
			$hc_events_out[$i]['start']			= date('c', intval($hc_event['hc_event_start']));
			$hc_events_out[$i]['end'] 			= date('c', intval($hc_event['hc_event_end']));
			$hc_events_out[$i]['className'] 	= $hc_event['hc_event_classname'];
			$hc_events_out[$i]['allDay'] 		= $hc_event['hc_event_allday'];
			$hc_events_out[$i]['recurrance'] 	= $hc_event['hc_event_recurrance'];
			
			//var_dump($hc_event['hc_event_category_color']);
			
			
			
	
			if ($hc_event['hc_event_color'] == "" || $hc_event['hc_event_color'] == " ") {
				//echo "[" . $hc_event['hc_event_category_color'] . "]";
				
				$hc_events_out[$i]['color'] = get_metadata('hc_event_categories', $hc_event['hc_event_category_color'], 'hc_event_category_color', true);
				
			} else {
				//echo "blah";
				$hc_events_out[$i]['color'] = $hc_event['hc_event_color'];
			}

			$hc_events_out[$i]['url'] 			= $hc_event['hc_event_url'];
			$hc_events_out[$i]['terms'] 		= $hc_event['hc_event_terms'];
			$hc_events_out[$i]['hc_event_allow_paying'] 	  = $hc_event['hc_event_allow_paying'];
			$hc_events_out[$i]['hc_event_amount'] 			  = $hc_event['hc_event_amount'];
			$hc_events_out[$i]['hc_event_allow_paying_until'] = $hc_event['hc_event_allow_paying_until'];
		}
		return $hc_events_out;
	}
	
	//CREATE EVENT CREATION FORM
	function create_event_creation_form () {
		?>
        <div class="hc_event-controller-dialog">
            <form id="hc_new_event_form">
                <table cellpadding="5" cellspacing="5">
                    	<tr>
                            <td colspan="2"><div class="hc_event_form_feedback"></div></td>
                        </tr><tr>
                        	<td><label for="hc_event_title">When</label></td>
                            <td>
                            	<b>
                        	<?php 
								echo date('D, F d', $_REQUEST['hc_event_start']); 
								//need to check start / end day, not timestamp (we dont want to count hours / minutes
								if (date('D, F d', $_REQUEST['hc_event_start']) != date('D, F d', $_REQUEST['hc_event_end'])) {
									echo ' - ' . date('D, F d', $_REQUEST['hc_event_end']); 
								}
							?></b>
                            </td>
                        </tr><tr>
                            <td><label for="hc_event_title">What</label></td>
                            <td><input type="text" size="30" name="hc_event_title" id="hc_event_title" value="<?php echo $hc_event['post_title']?>"/></td>
                        </tr><tr>
                            <td colspan="2" class='hc_event_new_edit_url'>
                            </td>
                        </tr><tr>
                        	<td>
                            	<input type="hidden" name="hc_event_start" value="<?php echo $_REQUEST['hc_event_start']; ?>" id="hc_event_start"/>
                                <input type="hidden" name="hc_event_end" value="<?php echo $_REQUEST['hc_event_end']; ?>" id="hc_event_end"/>
                                <input type="hidden" name="hc_event_allday" value="1" id="hc_event_allday"/>
                                <input type="hidden" name="hc_ajax_request" value="process_event_creation_form" />
                                <input type="submit" value="Create" />
                            </td>
                        	<td>
	                            or <a class="hc_event_close" href="#">Cancel</a>
                            </td>
                        </tr>
                    </table>
             </form>
         </div>
        <?php
	}
	
	//CREATE EVENT EDIT FORM
	function create_edit_event_form ($hc_event_id, $hc_event_recurrance) {
		global $wpdb;
		global $blog_id;
		//Check if this plugin is being used in a multi-site blog
		//If it is we want to select event posts from the current blog only	
		if ( is_multisite() ) {
			$posts_table = "wp_" . $blog_id . "_posts";
		} else {
			$posts_table = "wp_posts";
		}
		//echo "ID: " . $hc_event_id;
		//print_r($hc_event_recurrance);
		//Select all event posts
		$hc_event = $wpdb->get_results("SELECT * FROM " . $posts_table . " WHERE ID = $hc_event_id AND post_status = 'publish'", ARRAY_A);
		
		//print_r($hc_event);
		
		$hc_event = $hc_event[0];
		$hc_event['hc_event_start'] 	= get_post_meta($hc_event['ID'], 'hc_event_start', true);
		$hc_event['hc_event_end'] 		= get_post_meta($hc_event['ID'], 'hc_event_end', true);
		$hc_event['hc_event_allday'] 	= get_post_meta($hc_event['ID'], 'hc_event_allday', true);
		$hc_event['hc_event_color'] 	= get_post_meta($hc_event['ID'], 'hc_event_color', true);
		//$hc_event['recurrance'] = get_post_meta($hc_event['ID'], '_recurrance', true);
		$hc_event['hc_event_recurrance'] = $hc_event_recurrance;
		?>
            <div class="hc_event-controller-dialog">
                <form id="hc_event_edit_event_form">
                    <table cellpadding="5" cellspacing="5">
                    	<tr>
                            <td colspan="2"><div class="hc_event_form_feedback"></div></td>
                        </tr><tr>
                        	<td><label for="hc_event_title">When</label></td>
                            <td>
                            	<b>
                        	<?php 
								echo date('D, F d', $hc_event['hc_event_start']); 
								
								if ($hc_event['hc_event_allday'] == 0) {
									echo "<br /><small>" . date('ga', $hc_event['hc_event_start']);
									echo " - " . date('ga', $hc_event['hc_event_end']);
									echo "</small>";
								}
								
								//need to check start / end day, not timestamp (we dont want to count hours / minutes
								if (date('D, F d', $hc_event['hc_event_start']) != date('D, F d', $hc_event['hc_event_end'])) {
									echo ' - ' . date('D, F d', $hc_event['hc_event_end']); 
								}
							?></b>
                            </td>
                        </tr><tr>
                            <td><label for="hc_event_title">What</label></td>
                            <td><input type="text" size="30" name="hc_event_title" id="hc_event_title" value="<?php echo $hc_event['post_title']?>"/></td>
                        </tr><tr>
                            <td colspan="2">
                            	<a href="<?php bloginfo('url') ?>/wp-admin/post.php?post=<?php echo $hc_event_id?>&action=edit" title="Edit in full editor">Edit Event Details</a>
                            </td>
                        </tr><tr>
                        	<td>
                            	<input type="hidden" name="hc_event_id" value="<?php echo $hc_event['ID']; ?>" id="hc_event_id"/>
                            	<input type="hidden" name="hc_event_start" value="<?php echo $hc_event['hc_event_start']; ?>" id="hc_event_start"/>
                                <input type="hidden" name="hc_event_end" value="<?php echo $hc_event['hc_event_end']; ?>" id="hc_event_end"/>
                                <input type="hidden" name="hc_ajax_request" value="process_event_edit_form" />
                                <input type="submit" value="Update" />
                            </td>
                        	<td>
	                            <a href="#" class="hc_event_delete">Delete</a> or <a class="hc_event_close" href="#">Close</a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
		<?php
	}
	
	//CREATE NEW EVENT
	//Pre: (string event title, int start timestamp, int end timestamp, bool if the event is all day
	//Post: Return JSON formated string of the newly created event
	function process_new_event_form ($hc_event_title, $hc_event_color, $hc_event_categories, $hc_event_start, $hc_event_end, $hc_event_start_time, $hc_event_end_time, $hc_event_allday, $hc_event_is_repeat, $hc_event_frequency, $hc_event_interval) {
		
		//print_r($_REQUEST);
		//print_r(func_get_args());
		
		global $wpdb;
		/*
		$hc_event_category = array(0 => $hc_event_category);
		
		if ($hc_event_is_repeat == 1) {
			//Generate the recurrance pattern
			$hc_event_recurrance = array(
				"hc_event_frequency" => $fhc_event_requency,
				"hc_event_interval" => $hc_event_interval
			);			
			$hc_event_recurrance_serialized = serialize($hc_event_recurrance);	
		} else {
			$hc_event_recurrance = "";			
		}
		*/
		
			//$hc_event_start = strtotime(date('j F Y', $hc_event_start));
			//$hc_event_end = strtotime(date('j F Y', $hc_event_end));
		
		
		//Create a new wordpress post in the dance lessons custom post type
		// Create post object
		$hc_event_post = array(
			'post_title' => $hc_event_title,
			'post_type' => 'hc_events',
			'post_content' => $hc_event_description,
			'post_status' => 'publish',
			'post_author' => 1
		);
		// Insert the post into the wp database
		$hc_event_post_id = wp_insert_post($hc_event_post);
		
		//Attach the custom taxonomy to our new post
		//wp_set_object_terms($hc_event_post_id, $hc_event_categories,'hc_event_categories',false);
		
		//Lets store all our meta box event settigns in an array and loop through it to store / update data
		$meta_data['hc_event_start'] 	= $hc_event_start;
		$meta_data['hc_event_end'] 		= $hc_event_end;
		//$meta_data['hc_event_color'] 	= $color;
		$meta_data['hc_event_allday'] 	= $hc_event_allday;
		//$meta_data['hc_event_recurrance'] = $recurrance;
		store_meta_data($hc_event_post_id, $meta_data);

		//Return an array of the event data
		
		//NEED TO DO!
		
		//we must return an array of all recurrance items if there are any in this request
		//then we need to process those on ajax return to create all the new events		
		return array(
			'hc_event_created' => true,
			'hc_event_id' => $hc_event_post_id,
			'hc_event_title' => $hc_event_title,
			'hc_event_start' => $hc_event_start,
			'hc_event_end' => $hc_event_end,
			'hc_event_allday' => $hc_event_allday,
			'hc_event_classname' => 'event-' . $hc_event_post_id/*,
			'hc_event_color' => $hc_event_color*/
		);
	}
	
	//UPDATE EVENT 
	function process_event_edit_form ($hc_event_id, $hc_event_title, $hc_event_color, $hc_event_hc_eventtypes, $hc_event_start, $hc_event_end, $hc_event_start_time, $hc_event_end_time, $hc_event_allday, $hc_event_is_repeat, $hc_event_frequency, $hc_event_interval) {
		//print_r(func_get_args());
		
		$hc_event_updated_post = array();
		$hc_event_updated_post['ID'] = $hc_event_id;
		$hc_event_updated_post['post_title'] = $hc_event_title;
		$hc_event_updated_post['post_content'] = $hc_event_content;
		
		wp_update_post($hc_event_updated_post);
		
		/*
		//Make sure to set allday to 0 if it has not been set during form input (inactive)
		if ($hc_event_allday == 1) {
			$hc_event_allday = 1;
		} else {
			//All day has been unchecked, so the event will have a start and end time (hours)
			$hc_event_allday = 0;
			$hc_event_start = strtotime(date('j F Y', $hc_event_start) . ' ' . $hc_event_start_time);
			$hc_event_end = strtotime(date('j F Y', $hc_event_end) . ' ' . $hc_event_end_time);
		}
		*/
		
		$hc_event_allday = 1;
		
		if ($hc_event_is_repeat == 1) {
			//Generate the recurrance pattern
			$hc_event_recurrance = array(
				"hc_event_frequency" => $hc_event_frequency,
				"hc_event_interval" => $hc_event_interval
			);			
			$hc_event_recurrance_serialized = serialize($hc_event_recurrance);	
		} else {
			$hc_event_recurrance = "";			
		}
		
		//update terms
		$meta_data['hc_event_start'] 	= $hc_event_start;
		$meta_data['hc_event_end'] 		= $hc_event_end;
		$meta_data['hc_event_color'] 	= $hc_event_color;
		$meta_data['hc_event_allday'] 	= $hc_event_allday;
		$meta_data['hc_event_recurrance'] = $hc_event_recurrance;
		//update recurrance	
		
		store_meta_data($hc_event_id, $meta_data);
		
		return array(
			'hc_event_updated' => true,
			'hc_event_id' => $hc_event_id,
			'hc_event_title' => $hc_event_title,
			'hc_event_start' => $hc_event_start,
			'hc_event_end' => $hc_event_end,
			'hc_event_allday' => $hc_event_allday,
			'hc_event_className' => 'event-' . $hc_event_id,
			'hc_event_color' => $hc_event_color
		);
	}
	
	//UPDATE MOVED EVENT
	//Pre: int event id, int start timestamp, int end timestamp
	//Post: BOOL true on success, false on failure
	function update_moved_event ($hc_event_id, $hc_event_start, $hc_event_end, $hc_event_allday) {
		if ($hc_event_allday == "true") {
			$hc_event_allday = 1;
		} 
		if ($hc_event_allday == "false") {
			$hc_event_allday = 0;
		}
		$hc_event_allday = abs($hc_event_allday);
		if ($hc_event_end == 'null' || $hc_event_end == "") {
			$hc_event_end = $hc_event_start;
		}
		update_post_meta($hc_event_id, 'hc_event_start', $hc_event_start);
		update_post_meta($hc_event_id, 'hc_event_end', $hc_event_end);
		update_post_meta($hc_event_id, 'hc_event_allday', $hc_event_allday);
		return true;
	}
	
	//UPDATE RESIZED EVENT
	function update_resized_event ($hc_event_id, $hc_event_end) {
		update_post_meta($hc_event_id, 'hc_event_end', $hc_event_end);
		return true;
	}
	
	//DELETE EVENT 
	function delete_event ($hc_event_id) {
		wp_delete_post( $hc_event_id, $force_delete = true );
		//Pass back the event id so it can be removed from the calendar
		echo $hc_event_id;
	}
?>