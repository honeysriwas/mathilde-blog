<?php
/**
 * Related posts — "You Might Also Like" (4-up).
 *
 * Related by shared category, excluding the current post.
 *
 * @package Mathilde
 */

$cats = wp_get_post_categories( get_the_ID() );
$args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 4,
	'post__not_in'        => array( get_the_ID() ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'orderby'             => 'rand',
);
if ( $cats ) {
	$args['category__in'] = $cats;
}

$related = new WP_Query( $args );

// Top up with recent posts if the category is thin.
if ( $related->post_count < 4 ) {
	$related = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 4,
			'post__not_in'        => array( get_the_ID() ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
}

if ( ! $related->have_posts() ) {
	return;
}
?>
<section class="related" aria-label="<?php esc_attr_e( 'You might also like', 'mathilde' ); ?>">
	<div class="section__head">
		<h2 class="m-section-title"><?php esc_html_e( 'You Might Also Like', 'mathilde' ); ?></h2>
	</div>
	<div class="grid grid--4">
		<?php
		while ( $related->have_posts() ) :
			$related->the_post();
			get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'default', 'badge' => false ) );
		endwhile;
		?>
	</div>
</section>
<?php
wp_reset_postdata();
