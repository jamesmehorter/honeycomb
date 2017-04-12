Plugin Name: Honeycomb Event Calendar
Plugin URI: http://www.jamesmehorter.com
Description: The Honeycomb Event Calendar plugin for WordPress 3.0+ helps you build and organize your events. 
Version: 0.1
Author: James Mehorter
Author URI: http://www.Jamesmehorter.com
Copyright 2011 James Mehorter (email : jamesmehorter@gmail.com)
	

ABOUT 
The plugin uses WordPress' built-in custom post types, post meta data, and custom taxonomies - so it plays real nice with tamplate files and permalinks. There are no extra database tables created, and there is no extra data placed in the wp-options table. Events can be displayed in a category view or list view, and both are easily incorporated in your template files or page/posts with shortcodes. You can sort events by hierarchical categories, just like you would blog posts. Since events are created as posts, and categories as custom taxonomies you can easily build template files like single-events.php or taxonomy-event-types.php which will leverage WordPress' permalinks so you can immediately can go to www.mysite.com/events/my-sweet-event. 

This plugin creates custom posts 'Events' with a hierarchical taxonomy 'Event Types' for sorting. All event data; like start, end, allDay, and recurrance are stored as meta data on the post for each event. This way there is not extra db table for storing events. This also means we're leverging the built-in post type, taxonomy, and permalinks. So when an event is created, you can go to www.mysite.com/events/my-sweet-event. If you create a taxonomy term(category) for this post, like: 'Concerts'; you can then go to www.mysite.com/concerts/my-sweet-event. See usage info below for tips on styling the output

This plugin essentially mashes several jQuery plugins together. We obviously use the core jQuery library. We then use the jQuery-UI core, widget, mouse, draggable, resizeable, and datepicker plugins. 

For the primary calendar view functionality we chose to use the Full Calendar jQuery plugin. http://arshaw.com/fullcalendar/ For the calendar view tooltip popups we chose to use simpletip http://craigsworks.com/projects/simpletip/. We also used the Syronex Colorpicker jQuery plugin for choosing event colors http://www.syronex.com/software/jquery-color-picker. The 'When' PHP library is also used to calculate date recurrance events https://github.com/tplaner/When.



UPDATES
We have not touched/hacked any of these dependant plugins, so they can *usually* be updated independently when new versions are released. Though, We will do our best to release updates for this plugin when we feel those dependant plugin updates are ready to be released. As of WordPress 3.0.4 the use of enqueue_script still does not work correctly to use the WordPress jQuery/jQuery-UI libraries, or maybe we're just being dumb - so for now they are bundled with this plugin. 



INSTALLATION
Install the plugin as you would any other
After installation:
	+ A custom post type 'Events' and a custom taxonomy 'Event Types' will be created (You can alter these names below)
	+ You will see a 'Events' menu in the WordPress admin to manage your new events posts
	+ Under the 'Events' menu you will see the 'Event Types' taxonomy we created to manage your categories
	+ Under the 'Events' menu you will also see a 'Calendar View' menu to manage your events in a drag and drop calendar view. 



USAGE 
You can create events in the 'wp-admin > Events' page, this is default custom post type page. You can also edit events here as well. Though, the real gem of this plugin lay in the 'Calendar View'. Here you can intuitively create events by clicking on days or by dragging out over several days. You can resize events by dragging on their corners. You can move events by click-dragging them around the calendar. You can drag a series of events (recurring events) as a set. And You can edit events by clicking on them.

You can display a list of events with a template tag. You can also filter by a certain event type (term), and/or limit the event list items shown. Note: to filter by term you must of assigned (event type) terms to your events. You can do this while editing each event in wp-admin > Events



REFERENCE & OPTIONS
You can display a public version of the calendar view in a page or post with the shortcode: 
[events-calendar-view]
OR
By calling the PHP function directly 
<?php Display_Event_Calendar() ;?>

You can display a list of events in a page or post with the shortcode:
ex 1: Show all upcoming events
[events-list-view] 
OR
<?php Display_List_Of_Events('', 1) ;?>

ex 2: Show 3 upcoming events
[events-list-view limit=3] (The limit argument is optional)
OR
<?php Display_List_Of_Events('', 3) ;?>

ex 3: Show 8 results with the term 'Concert'
[events-list-view terms='Concert' limit=8] (The terms argument is optional)
OR
<?php Display_List_Of_Events('Concert', 8) ;?>

ex 4: Show 12 results with any/all of the terms 'Concert, Live Music, Acoustic', note: order does not matter
[events-list-view limit=12 terms='Concert, Live Music, Acoustic']
OR
<?php Display_List_Of_Events('Concert, Live Music, Acoustic', 12) ;?>


You can display a list of terms in the Event Types taxonomy with either a shortcode or directly calling the php function
ex 1: Show a list of event type terms
[event-type-list]
OR
Display_Event_Type_List();

By default this list will show all terms and their descriptions. I.e:

"
CONCERTS
Our concert listings cover punk, reggae, metal, and jazz concerts in the greater Boston area...
"

You can specify a limit for the term description. By default this is set to 255 characters. This is helpful for seo, so your list is just an excerpt, with a link to the full content (no duplicate content). 
[event-type-list limit=400]
OR
Display_Event_Type_List(400);

The term title will be a link to the term page (This is where the bundled template dile 'taxonomy-event-types.php' comes in handy. This template will be used for pages like: 
www.mysite.com/event-types/concerts

This page is used to display a list of all events in the 'concerts' term. 

THEMPLATING & PERMALINKS
You may want to make use of the post type / permalinks mentioned in the Notes above. To do this you will want
two new files for your wordpress theme. See the samples bundled with this plugin. You can just copy the files mentioned below into your active template folder, they will immediately work. Then you can customize the markup and style of each to match your template. 
	+ single-events.php  //This page can be used to output a single event as a post
	+ taxonomy-event-types.php //This page can be used to show a specific event type 'Concerts' page

You may need to reset your permalink structure if the above doesn't work right off