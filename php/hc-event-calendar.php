	<!--
    
    	This page generates the actual Honeycomb Calendar. Much of the page has conditionals to determine if the calendar is being view in wp-admin or on the public website - the admin view receives several additional scripts for management purposes. The public view also runs a tooltip script for quickview of event info.
    
    -->
	<?php 
		$hc_plugin_url = get_bloginfo('url') . "/wp-content/plugins/honeycomb"; 
	?>
    <link rel='stylesheet' type='text/css' href='<?php echo $hc_plugin_url; ?>/jquery-plugins/fullcalendar-1.6.4/fullcalendar/fullcalendar.css' />
    <style type='text/css'>
		#hc_loading {
			position: absolute;
			top: 1px ;
			left: 1px ;
		}
		#hc_calendar {
			position: relative ;
			<?php 
				if (is_admin()) {
					//The admin view feels cramped, so let's make it a little smaller
					echo "margin: 0px 25px 20px 0;\n";
				} else {
					echo "margin: 20px 0 20px 0;\n";
				}
			?>
			overflow: visible ;
		}
		/* Make sure our tooltips can pop out of the calendar container */
		.fcdiv, .fc-contentdiv, .fc-view {
			overflow: visible !important ;
		}
			.fc-event {
				margin-bottom: 3px !important;
			}
			<?php 
				if (is_admin()) {
			?>
			.hc_event-controller-dialog {
				position: absolute ;
				z-index: 999999999 ;
				top: 12% ;
				left: 28% ;
				padding: 20px ;
				background-color: rgb(255,255,255) ;
				-moz-border-radius: 15px;
				-webkit-border-radius: 15px;
				border-radius: 15px;
				-moz-box-shadow: 0px 0px 25px rgb(150,150,150) ;
				-webkit-box-shadow: 0px 0px 25px rgb(150,150,150) ;
				box-shadow: 0px 0px 25px rgb(150,150,150) ;
			}
				.hc_event-controller-dialog table td {
					padding: 5px ;
				}
				#hc_time_selection {
					display: none ;
				}				
				.hc_event_form_success {
					background-color: #defeb7 ;
					padding: 5px ;
					font-weight: bold ;
					color: rgb(90,90,90) ;
					border: 1px solid #cff4a3 ;
				}
				<?php
				} //End if is_admin() 
				else {
				?>
					/* Tooltip Styles */
					.tooltip {
						font-family: Georgia, "Times New Roman", Times, serif ;
						font-size: 12px ;
						color: rgb(255,255,255) !important ;
						font-weight: normal ;
						padding: 5px ;
						border-top: 1px solid rgb(255,255,255) ;
						-moz-box-shadow: 0px 0px 5px #000000;
						-webkit-box-shadow: 0px 0px 5px #000000;
						box-shadow: 0px 0px 5px #000000;
					}
					.tooltip .tooltip { 
						position: absolute; 
						top: 0; 
						left: 0; 
						z-index: 3; 
					}
					.tooltip a {
						color: rgb(255,255,255) ;
					}
				<?php
				}
				?>	 
				
	</style>
    <script type='text/javascript' src='<?php echo $hc_plugin_url; ?>/js/jquery-ui-1.8.6.custom.min.js'></script>
	<?php 
		//echo "$hc_plugin_url/jquery-plugins/fullcalendar-1.5.1/fullcalendar/fullcalendar.min.js";
		//wp_enqueue_style( 'jquery-ui-smoothness', "$hc_plugin_url/css/jquery-ui-1.8.6.custom.css");
		/*
		add_action('wp_print_scripts', 'hc_events_load_scripts');
		function hc_events_load_scripts () {
			global $hc_plugin_url;
			//if (is_page(4)) {
				wp_enqueue_script("fullcalendar", "$hc_plugin_url/jquery-plugins/fullcalendar-1.5.1/fullcalendar/fullcalendar.min.js", array('jquery'), '1.5.1');
			//}
		}
		*/
		//We only need the color picker script and css for the admin page
		if(is_admin()) {
			?>
			<?php
		} else {
			
			?>
            
            <!--<script type="text/javascript" src="<?php echo $hc_plugin_url; ?>/js/jquery.simpletip-1.3.1.min.js"></script>-->
            <?php
		}
	?>
    <!--<script type='text/javascript' src='<?php echo $hc_plugin_url; ?>/jquery-plugins/fullcalendar-1.5.1/fullcalendar/fullcalendar.min.js'></script>-->
    <script type='text/javascript'>
		jQuery(function($) {
		//$(document).ready(function() {
			String.prototype.ucFirst = function() {
				return this.charAt(0).toUpperCase() + this.substring(1);
			}
			//Initilize our calendar with a reference back to itself, e.g. var calendar
			var calendar = $('#hc_calendar').fullCalendar({
				ignoreTimezone: true,
				<?php 
					//If this page is being viewed by the admin add support for administrative controls
					//These controlls add functionality to move, resize, and edit events. 
					if (is_admin()) {
				?>
				//Add Support for adding events by either clicking a day or dragging out / selecting multiple days
				selectable: true,
				selectHelper: true,
				select: function(start, end, allday) {
					if ($(".hc_event-controller-dialog").length == 0) {
						create_event_creation_form(start, end, allday);
					}
				},
				//Add support for dragging event positions on the calendar
				editable: true,
				eventRender: function (event) {
					//Add some css to our newly created event(s) to reflect the chosen color
					var hc_event_styles = {backgroundColor: event.color, borderColor: event.color}
					$('.event-' + event.id).css(hc_event_styles)
					$('.fc-agenda .event-' + event.id + ' .fc-event-time').css(hc_event_styles)
					$('.event-' + event.id + ' a').css(hc_event_styles)
				},
				//Action to be preformed upon clicking an event by the admin
				eventClick: function(event, e) {
					//edit event
					e.preventDefault();
					
					console.log(event)
					if ($(".hc_event-controller-dialog").length == 0) {
						create_event_edit_form(event.id, event.recurrance);
					}
				},
				eventDrop: function(event, delta) {
					if (event.end == null || event.end == 'null') {
						hc_event_end = event.start.getTime() / 1000
					} else {
						hc_event_end = event.end.getTime() / 1000
					}
					var data_out = {
						hc_ajax_request: 'update_moved_event',
						hc_event_id: event.id,
						hc_event_start: event.start.getTime() / 1000,
						hc_event_end: hc_event_end,
						hc_event_allday: event.allDay
					}
					//console.log(data_out)
					$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", data_out, function(data_in) {
						//event moved 
					});
				},
				eventResize: function(event,dayDelta,minuteDelta,revertFunc) {
					var data_out = {
						hc_ajax_request: 'update_resized_event',
						hc_event_id: event.id,
						hc_event_end: event.end.getTime() / 1000,
					}
					$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", data_out, function(data_in) {
						//event resized
					});
				},
				<?php
					} //end check for admin page, now display settings only specific to the public view
					else {	
				?>
				//Function used to add anything to event when they are rendered onto the calendar view
				eventRender: function(event, element) {
					
					
					//Add some css to our newly created event(s) to reflect the chosen color
					var event_styles = {backgroundColor: event.color, borderColor: event.color}
					$('.event-' + event.id).css(event_styles)
					$('.fc-agenda .event-' + event.id + ' .fc-event-time').css(event_styles)
					$('.event-' + event.id + ' a').css(event_styles)
					
					
					//Bind our simpletip tooltip to each event, but only if the event has a summary
					if (event.summary != '' && event.summary != null) {				
						var tooltipContent = event.summary
						//Check if registration is still open, and display a signup link if it is
						var now = new Date()
							now = Math.round(now.getTime() / 1000)
						var hc_event_start = Math.round(event.start.getTime() / 1000)
						
						//only allow prepay for future events
						if (hc_event_start > now) {
							//only allow prepay if its been set
							if (event.hc_event_allow_paying == 'on') {
								//only allow prepay if an amount has been set
								if (event.hc_event_amount != '') {
									tooltipContent += "<br /><br /><a href='" + event.url + "' title='Pre-Pay for'" + event.title + "'><b>Pre-Pay Now!</b></a>"
								}
							}
						}
						
						element.simpletip({
							content: tooltipContent,
							//We want our tip to remain fixed to the element which calls it
							fixed: true
						});
					}
				},
				eventClick: function (event) {
					//When visitors click an event on the calendar let's redirect them to the event page
					//This url uses the default wp permalinks, so mysite.com/events/my-cool-event
					window.location(event.url)
				},
				eventMouseover: function (event) {
					$(this).css('z-index', '99999999');
				},
				eventMouseout: function (event) {
					$(this).css('z-index', '3');
				},
				<?php
					}
				?>
				//Add themeing support
				//theme: true,	
				header: {
					left: 'month,agendaWeek,agendaDay',
					center: 'title',
					right: 'prev,next today'
				},
				//Load in our events feed via a json dataset; provided by php pulling content from mysql
				events: "<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php?hc_ajax_request=request_all_JSON_events",
				//events: {"id":"163","title":"Danceathon 2010 2","summary":"Integer at nisi metus, vitae sodales massa. Phasellus placerat tempor  quam id adipiscing. Nulla luctus elementum velit nec ornare. Praesent  vehicula venenatis lorem in euismod. Donec tempus, eros","start":"","end":"","classname":"hc_event_event-163","allDay":0,"recurrance":"","color":"","url":"http:\/\/localhost\/Dancechatter_v2\/events\/danceathon-2010\/","terms":""},
				
				//Display a loading dialog for heavy calendar weight
				loading: function(bool) {
					if (bool) $('#hc_loading').show();
					else $('#hc_loading').hide();
				}
			});//END CREATE FULL CALENDAR
			<?php 
				//If this page is being viewed by the admin add support for administrative controls
				//We only wany authenticated users to has access to these function / know they even exist
				if (is_admin()) {
			?>
			//List of current event colors
			var hc_colors = [
				'#4e85ca', //Light Blue - DEFAULT COLOR
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
			
			//Create the event createtion form
			function create_event_creation_form (hc_event_start, hc_event_end, hc_event_allday) {
				//var startDateObj = new Date(hc_new_event.hc_event_start * 1000)
				//console.log(hc_event_start)
				var hc_event_start_ts = hc_event_start.getTime() / 1000
				//console.log(hc_event_start_ts)
				var hc_event_start_ts_offset = hc_event_start.getTimezoneOffset() * 60
				//console.log(hc_event_start_ts_offset)
				hc_event_start = hc_event_start_ts + hc_event_start_ts_offset
				//console.log(hc_event_start)
				//console.log('---------------------------');
				//var endDateObj = new Date(hc_new_event.hc_event_end * 1000)
				var hc_event_end_ts = hc_event_end.getTime() / 1000
				//console.log(hc_event_end_ts)
				var hc_event_end_offset = hc_event_end.getTimezoneOffset() * 60
				//console.log(hc_event_start_ts_offset)
				hc_event_end = hc_event_end_ts + hc_event_end_offset
				//console.log(hc_event_end)
				
				
				hc_ajax_request = "create_event_creation_form";
				var event_data = {hc_event_start: hc_event_start, hc_event_end: hc_event_end, hc_event_allday: hc_event_allday, hc_ajax_request: hc_ajax_request};
				
				console.log(event_data);
				
				
				$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", event_data, function(create_event_form_html) {
					$('#hc_calendar').append(create_event_form_html);	
					$(".hc_event-controller-dialog").fadeTo(250, 1.0);
				});
				
				
			} //End create event creation form
			
			function create_event_edit_form (hc_event_id, hc_event_recurrance) {
				data_out = {
					hc_ajax_request: 'create_edit_event_form',
					hc_event_id: hc_event_id,
					hc_event_recurrance: hc_event_recurrance
				};
				console.log(data_out)
				$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", data_out, function(edit_event_form_html) {
					$('#hc_calendar').append(edit_event_form_html);

					if($('#hc_event_allday').is(':checked')) {
						$('#hc_event_time_selection').hide();			
					} else {			
						$('#hc_event_time_selection').css('display', 'inline');
					}
					
					if ($('#hc_event_is_repeat').is(':checked')) {
						$('#hc_event_recurrance').css('display', 'inline');
					} else {
						$('#hc_event_recurrance').hide();
					}
					
					$(".hc_event-controller-dialog").fadeTo(250, 1.0);
					//Create the color picker jquery object 
					//console.log(hc_colors)
					for (var i in hc_colors) {
						if (hc_colors[i] == $('#hc_event_color').val()) {
							var hc_color_index = i;
						}
					}
					/*
					$('#hc_event_colors').colorPicker({			
						defaultColor: parseInt(hc_color_index), // index of the default color (optional)  
						color: hc_colors, 
						click: function(color){
							$('#hc_event_color').attr('value', color);
						}
					});
					*/
				});
			}
				//Proccess the create event form			
				$('#hc_new_event_form').live('submit', function(event) {
					event.preventDefault();
					//Send Request by converting this forms field data into a &pair=value string format
					$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", $(this).serialize(), function(hc_new_event) {
						//Process the returned data
						if (hc_new_event.hc_event_created == true) {
							$(".hc_event_form_feedback").addClass('hc_event_form_success');	
							$(".hc_event_form_feedback").html('Event created successfully :)');
							$('.hc_event_close').html('Close');
							$('.hc_event_new_edit_url').html("<a href='<?php bloginfo('url') ?>/wp-admin/post.php?post=" + hc_new_event.hc_event_id + "&action=edit' title='Edit in full editor'>Edit Event Details</a>");
							
							calendar.fullCalendar('renderEvent',
								{
									id: hc_new_event.hc_event_id,
									title: hc_new_event.hc_event_title,
									start: hc_new_event.hc_event_start,
									end: hc_new_event.hc_event_end,
									allday: hc_new_event.hc_event_allday,
									className: hc_new_event.hc_event_className
								},
								true // make the event "stick"
							);
							calendar.fullCalendar('unselect');
							//Add some css to our newly created event(s) to reflect the chosen color
							var hc_new_event_styles = {backgroundColor: hc_new_event.hc_event_color, borderColor: hc_new_event.hc_event_color}
							$('.event-' + hc_new_event.id).css(hc_new_event_styles)
							$('.fc-agenda .event-' + hc_new_event.id + ' .fc-event-time').css(hc_new_event_styles)
							$('.event-' + hc_new_event.id + ' a').css(hc_new_event_styles)
							
							//Fade and remove the create event form
							//$(".hc_event-controller-dialog").fadeTo(250,0.0);
							//$(".hc_event-controller-dialog").remove();
						} else {
							$(".hc_event_form_feedback").addClass('hc_form_failure');				
							$(".hc_event_form_feedback").html('There was an error creating the event :(');
						}
					}, "json");//End Ajax Query
				});
				
				//Update an event
				//Proccess the create event form			
				$('#hc_event_edit_event_form').live('submit', function(event) {
					event.preventDefault();
					//Send Request by converting this forms field data into a &pair=value string format
					$.post("<?php echo $hc_plugin_url; ?>/php/hc-event-functions.php", $(this).serialize(), function(hc_updated_event) {
						//Show success confirmation
						if (hc_updated_event.hc_event_updated == true) {
							$(".hc_event_form_feedback").addClass('hc_event_form_success');	
							$(".hc_event_form_feedback").html('Event updated successfully :)');
							//Update the client side calendar event to reflect the update
							$('#hc_calendar').fullCalendar('clientEvents', function(event) {
								if (event.id == hc_updated_event.hc_event_id) {
									event.start = hc_updated_event.hc_event_start;
									event.end = hc_updated_event.hc_event_end;
									event.allday = hc_updated_event.hc_event_allday;
									event.title = hc_updated_event.hc_event_title;
									event.color = hc_updated_event.hc_event_color;
									$('#hc_calendar').fullCalendar('updateEvent', event);
								}
							});
							//Add some css to our newly created event(s) to reflect the chosen color
							var hc_updated_event_styles = {backgroundColor: hc_updated_event.color, borderColor: hc_updated_event.color}
							$('.event-' + hc_updated_event.hc_event_id).css(hc_updated_event_styles)
							$('.fc-agenda .event-' + hc_updated_event.hc_event_id + ' .fc-event-time').css(hc_updated_event_styles)
							$('.event-' + hc_updated_event.hc_event_id + ' a').css(hc_updated_event_styles)
						} else {
							//Show any errors 
							$(".hc_event_form_feedback").addClass('hc_form_failure');				
							$(".hc_event_form_feedback").html('There was an error updating the event :(');
						}
						$(".hc_event_form_feedback").show();
						//Fade and remove the create event form
						/*
						$(".hc_event-controller-dialog").delay(2500).fadeTo(250,0.0).queue(function() {
							$(this).remove();
						});
						*/
					}, "json");//End Ajax Query
				});
				
				//Functions for the create event form
				//Close / Cancel the create event form
				$(".hc_event_close").live('click', function(event) {
					event.preventDefault();
					$(".hc_event-controller-dialog").fadeTo(250,0.0);
					$(".hc_event-controller-dialog").remove();
				});
				
				//Delete an event
				$('.hc_event_delete').live('click', function(event) {
					event.preventDefault();
					if(confirm('Are you sure you want to delete this event?')) {
						var data_out = {
							hc_ajax_request: 'delete_event',
							hc_event_id: $('#hc_event_id').val()
						}
						$.post("<?php echo $hc_plugin_url;?>/php/hc-event-functions.php", data_out, function(hc_event_id) {						
							$(".hc_event-controller-dialog").fadeTo(250,0.0);
							$(".hc_event-controller-dialog").remove();
							calendar.fullCalendar( 'removeEvents', hc_event_id);
						});
					} else {
						//If the user cancels deleting the event lets close the edit dialog
						$(".hc_event-controller-dialog").fadeTo(250,0.0);
						$(".hc_event-controller-dialog").remove();
					}
				});
				//Enable or disable the all day  / timed event functions
				$('#hc_allday').live('change', function(event) {
					if($(this).is(':checked')) {
						$('#hc_time_selection').hide();			
					} else {			
						$('#hc_time_selection').css('display', 'inline');
					}
				});
				
				//
				//----- Evemt Recurrance Form Controllers
				//
				//Enable or Disable event recurrance
				$('#hc_is_repeat').live('change', function(event) {
					if ($(this).is(':checked')) {
						$('#hc_recurrance').fadeTo(250, 1.0);
					} else {
						$('#hc_recurrance').hide();
					}
				});
				
				//Update the interval noun field for descriptive recurrance output
				$('#hc_frequency').live('change', function(event){
					$('#hc_frequency option:selected').each(function(){
						$('#hc_interval_frequency_noun').text($(this)[0].value.splice('ly').ucFirst() + '(s)')
					});
				});
			<?php
				} //END IS ADMIN
			?>
		}); //End Document Ready jQuery Init
    </script>
    <?php 
		if (is_admin()) {
			echo "<br /><br />\n";
		}
	?>
	<div id='hc_calendar'>
    	<div id='hc_loading' style='display:none'>loading...</div>
    </div>