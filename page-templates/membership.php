<?php
/**
 * Template Name: Membership / Become a Contributor
 * Template Post Type: page
 *
 * Renders any page content (intro) followed by the pricing + signup UI.
 *
 * @package Mathilde
 */

get_header();
?>
<div class="container container--narrow single-article-wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		if ( trim( get_the_content() ) ) :
			?>
			<div class="m-prose" style="text-align:center;margin-bottom:var(--space-7);">
				<?php the_content(); ?>
			</div>
			<?php
		endif;
	endwhile;
	?>
</div>

<div class="container container--wide">
	<?php get_template_part( 'template-parts/membership/pricing' ); ?>
</div>

<?php
get_footer();
