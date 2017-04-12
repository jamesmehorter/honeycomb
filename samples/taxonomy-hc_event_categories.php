<?php 
	get_header(); 
	
	//Gather the current taxonomy term object being viewed	
	$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) ); 
?>
    <div id="Content">
        <?php
			if ( has_post_thumbnail(9) ) {
				// the current page has a feature image
				echo get_the_post_thumbnail(9,'post-thumbnail','');
			} 
		?>
     	<div class="clear"></div>
        <br /><br />
    	<div id="Sub-Content">
			<div class="breadcrumbs">
				<?php if(function_exists('bcn_display')) { bcn_display();} ?>
            </div>
            <br />
			<?php 		
                $loop = query_posts("post_type=hc_events&posts_per_page=10&posts_per_page=10&hc_event_categories=".$term->slug."");
				if (have_posts()) {
					while (have_posts()): the_post(); 
			?>
                <div class="Blog_Post">
                    <h1>
                    	<a href="<?php the_permalink() ?>" title="<?php echo the_title('','',0); ?>"><?php echo the_title('','',0); ?></a>
                    </h1>
                    <br />
                    <small>Posted on <?php the_time('F jS, Y') ?></small>
                    <br /><br />
                    <div class="Blog_Post_Content">
                        <?php the_excerpt(__('(more...)')); ?>
                    </div>
                </div>
            <?php 
					endwhile; 
				} else {
					echo "There are currently no {$term->name} events. Check back soon! In the meantime take a look at events in some of these categories:<br /><br />";
					
					wp_list_categories(array('taxonomy' => 'hc_event_categories', 'orderby' => 'name', 'show_count' => 1, 'pad_counts'   => 0, 'hierarchical' => 1,'title_li' => ''));
				}
			?> 
        </div>
        
        <style type="text/css">
			#TermList {
				text-align: right ;
			}
		</style>
        
        <div id="Sidebar">
            <div class="button"><?php echo $term->name ?></div>
            <!--<div id="TermList">-->
	            <?php 
					//This value is populated via the wp query above
					echo term_description( '', get_query_var( 'taxonomy' ) );
				?>
            <!--</div>-->
        </div>
        
    </div>
<?php get_footer(); ?>