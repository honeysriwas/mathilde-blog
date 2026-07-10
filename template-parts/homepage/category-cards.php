<?php
/**
 * Homepage — "Explore by Category" 5-up card row.
 *
 * Editors pick which categories appear (Customizer comma list of slugs),
 * otherwise the top categories by post count are used.
 *
 * @package Mathilde
 */

if ( ! mathilde_option( 'catcards_enable', true ) ) {
	return;
}

$slugs = array_filter( array_map( 'trim', explode( ',', (string) mathilde_option( 'catcards_slugs', '' ) ) ) );

if ( $slugs ) {
	$terms = array();
	foreach ( $slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'category' );
		if ( $t && ! is_wp_error( $t ) ) {
			$terms[] = $t;
		}
	}
} else {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 5,
			'hide_empty' => true,
		)
	);
}

if ( empty( $terms ) || is_wp_error( $terms ) ) {
	return;
}
?>
<section class="section section--tight" aria-label="<?php esc_attr_e( 'Explore by category', 'mathilde' ); ?>">
	<div class="container container--wide">
		<div class="section__head">
			<h2 class="m-section-title"><?php esc_html_e( 'Explore by Category', 'mathilde' ); ?></h2>
		</div>
		<div class="grid grid--5 cat-cards-grid">
			<?php
			foreach ( $terms as $term ) {
				get_template_part( 'template-parts/components/category-card', null, array( 'term' => $term ) );
			}
			?>
		</div>
	</div>
</section>
