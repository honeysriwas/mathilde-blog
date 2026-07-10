<?php
/**
 * Homepage — reusable category posts slider.
 *
 * $args:
 *   'title'    string  Section heading (e.g. "Fashion Posts")
 *   'category' string  Category slug to pull from ('' = latest)
 *   'count'    int     Number of posts
 *   'all_url'  string  "View all" link (defaults to the category archive)
 *
 * @package Mathilde
 */

$args = wp_parse_args(
	$args ?? array(),
	array(
		'title'    => __( 'Latest Posts', 'mathilde' ),
		'category' => '',
		'count'    => 5,
		'all_url'  => '',
	)
);

$query_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => (int) $args['count'],
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( $args['category'] ) {
	$query_args['category_name'] = $args['category'];
}

$q = new WP_Query( $query_args );
if ( ! $q->have_posts() ) {
	return;
}

$all_url = $args['all_url'];
if ( ! $all_url && $args['category'] ) {
	$term = get_category_by_slug( $args['category'] );
	if ( $term ) {
		$all_url = get_term_link( $term );
	}
}
?>
<section class="section section--tight" aria-label="<?php echo esc_attr( $args['title'] ); ?>">
	<div class="container container--wide">
		<div class="section__head">
			<h2 class="m-section-title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php if ( $all_url ) : ?>
				<a class="m-link-all" href="<?php echo esc_url( $all_url ); ?>"><?php esc_html_e( 'View All', 'mathilde' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="post-slider carousel" data-slider>
			<button class="carousel__btn carousel__btn--prev" type="button" data-prev aria-label="<?php esc_attr_e( 'Previous', 'mathilde' ); ?>"><?php mathilde_the_icon( 'arrow-left', 18 ); ?></button>
			<div class="h-scroll h-scroll--5" data-track>
				<?php
				while ( $q->have_posts() ) :
					$q->the_post();
					get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'default' ) );
				endwhile;
				?>
			</div>
			<button class="carousel__btn carousel__btn--next" type="button" data-next aria-label="<?php esc_attr_e( 'Next', 'mathilde' ); ?>"><?php mathilde_the_icon( 'arrow-right', 18 ); ?></button>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
