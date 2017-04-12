<?php 
	get_header(); 
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
                <a href="<?php bloginfo('url') ?>/dance-lessons/" title="Dance Lessons">Dance Lessons</a>
                <!--&gt;
                <?php echo get_the_term_list( $post->ID, 'event_categories', '', ', ', '' ); ?>-->
                &gt;
                <?php echo $post->post_title ?>
            </div>
            <br />
			<?php while (have_posts()): the_post(); ?>
                <div class="Blog_Post">
                    <h1><?php echo the_title('','',0); ?></h1>
                    <br />
                    <small>Posted on <?php the_time('F jS, Y') ?></small>
                    <br /><br />
                    <div class="Blog_Post_Content">
                        <?php the_content(__('(more...)')); ?>
                    </div>
                    <br /><br />
                    <small>Posted in:</small>
                    <br />
                    <?php 
						$terms = get_the_terms($post->ID, 'event_categories');		
						foreach ($terms as $term) {
							?>
							<div class='button'>
                            	<a href="<?php echo get_bloginfo('url') . "/event-categories/" . $term->slug?>" class="Link-Fill-Container">
									<?php echo $term->name ?>
                                </a>
							</div>
                            <?php
						}
					?>
                </div>
            <?php endwhile; ?>       
        </div>
        <div id="Sidebar">
        	<div class="button">EVENT TYPES</div>
            <ul>
				<?php wp_list_categories(array('taxonomy' => 'event_categories', 'orderby' => 'name', 'show_count' => 0, 'pad_counts'   => 0, 'hierarchical' => 1,'title_li' => '')); ?>
            </ul>
        </div> 
    </div>
<?php get_footer(); ?>