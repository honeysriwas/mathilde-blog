<?php
/**
 * Template Name: Full Width
 * Template Post Type: page
 *
 * A page with no sidebar and a wide content container — ideal for landing
 * pages built with the block editor.
 *
 * @package Mathilde
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'single-article-wrap' ); ?>>
		<div class="container container--wide">
			<div class="m-prose" data-article-body style="max-width:none;">
				<?php the_content(); ?>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
