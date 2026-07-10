<?php
/**
 * Homepage hero — rotating featured posts (split image + editorial copy).
 *
 * Each slide is a self-contained `.hero` grid; carousel.js toggles which one
 * is visible. Pulls from a chosen category or the latest/sticky posts.
 *
 * @package Mathilde
 */

$hero_cat   = mathilde_option( 'hero_category', '' );
$hero_count = (int) mathilde_option( 'hero_count', 3 );

$query_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => max( 1, $hero_count ),
	'ignore_sticky_posts' => false,
	'no_found_rows'       => true,
);
if ( $hero_cat ) {
	$query_args['category_name'] = $hero_cat;
}

$hero = new WP_Query( $query_args );
if ( ! $hero->have_posts() ) {
	return;
}
$total = $hero->post_count;
$index = 0;
?>
<section class="section section--tight" aria-label="<?php esc_attr_e( 'Featured', 'mathilde' ); ?>">
	<div class="container container--wide">
		<div class="hero-rotator reveal" data-hero>
			<?php
			while ( $hero->have_posts() ) :
				$hero->the_post();
				$index++;
				?>
				<article class="hero" data-hero-slide>
					<div class="hero__media">
						<?php
						// The first slide is the LCP element: load it eagerly with high
						// priority instead of lazily (big mobile LCP win).
						$hero_attr = array( 'class' => 'no-dim' );
						if ( 1 === $index ) {
							$hero_attr['loading']       = 'eager';
							$hero_attr['fetchpriority']  = 'high';
						}
						mathilde_post_thumbnail( 'mathilde-hero', $hero_attr );
						?>
					</div>
					<div class="hero__body">
						<?php mathilde_category_eyebrow(); ?>
						<h2 class="hero__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="hero__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
						<div>
							<a class="btn" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read Article', 'mathilde' ); ?></a>
						</div>
						<?php if ( $total > 1 ) : ?>
							<div class="hero__slider-nav" aria-hidden="true">
								<?php for ( $d = 1; $d <= $total; $d++ ) : ?>
									<span class="<?php echo ( $d === $index ) ? 'is-active' : ''; ?>"><?php echo esc_html( sprintf( '%02d', $d ) ); ?></span>
								<?php endfor; ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>

			<?php if ( $total > 1 ) : ?>
				<div class="hero-rotator__dots">
					<?php for ( $d = 0; $d < $total; $d++ ) : ?>
						<button type="button" data-hero-dot class="<?php echo 0 === $d ? 'is-active' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Featured slide %d', 'mathilde' ), $d + 1 ) ); ?>"></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
