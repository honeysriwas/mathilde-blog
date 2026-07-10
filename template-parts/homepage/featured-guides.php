<?php
/**
 * Homepage — "Featured Guides" strip showcasing ebooks for sale.
 *
 * Renders only when the ebooks feature exists and at least one product is
 * published. Order is controlled by each ebook's Page Attributes "Order"
 * (menu_order), then newest first.
 *
 * @package Mathilde
 */

// Bail if the ebooks module isn't loaded.
if ( ! post_type_exists( 'mathilde_ebook' ) || ! mathilde_option( 'guides_enable', true ) ) {
	return;
}

$count = (int) mathilde_option( 'guides_count', 4 );

$guides = new WP_Query(
	array(
		'post_type'      => 'mathilde_ebook',
		'posts_per_page' => max( 1, $count ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	)
);

if ( ! $guides->have_posts() ) {
	wp_reset_postdata();
	return;
}

$shop_url = get_post_type_archive_link( 'mathilde_ebook' );
$title    = mathilde_option( 'guides_title', __( 'Shop the Guides', 'mathilde' ) );
$subtitle = mathilde_option( 'guides_subtitle', __( 'Beautifully crafted digital guides — instant download, yours forever.', 'mathilde' ) );
?>
<section class="section section--cream featured-guides" aria-label="<?php echo esc_attr( $title ); ?>">
	<div class="container container--wide">
		<div class="featured-guides__head reveal">
			<div>
				<span class="m-eyebrow text-accent"><?php esc_html_e( 'From the Shop', 'mathilde' ); ?></span>
				<h2 class="featured-guides__title m-display"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $subtitle ) : ?>
					<p class="featured-guides__sub"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $shop_url ) : ?>
				<a class="btn btn--outline featured-guides__all" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Visit the Shop', 'mathilde' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="grid grid--<?php echo esc_attr( min( 4, max( 1, $guides->post_count ) ) ); ?> ebook-grid">
			<?php
			while ( $guides->have_posts() ) :
				$guides->the_post();
				get_template_part( 'template-parts/ebooks/card' );
			endwhile;
			?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
