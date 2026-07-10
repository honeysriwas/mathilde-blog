<?php
/**
 * Category card — image with gradient overlay + label (homepage 5-up row).
 *
 * Expects $args: 'term' (WP_Term), 'image' (url), 'desc' (string).
 *
 * @package Mathilde
 */

$args = wp_parse_args( $args ?? array(), array( 'term' => null, 'image' => '', 'desc' => '' ) );
$term = $args['term'];
if ( ! $term instanceof WP_Term ) {
	return;
}

$image = $args['image'];
if ( ! $image ) {
	// Use the most recent post's thumbnail in this category as a fallback.
	$recent = get_posts( array( 'category' => $term->term_id, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $recent ) {
		$image = get_the_post_thumbnail_url( $recent[0], 'mathilde-category' );
	}
}
$desc = $args['desc'] ? $args['desc'] : wp_trim_words( term_description( $term ), 8 );
?>
<a class="cat-card reveal" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
	<?php if ( $image ) : ?>
		<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy">
	<?php endif; ?>
	<span class="cat-card__plus"><?php mathilde_the_icon( 'plus', 14 ); ?></span>
	<span class="cat-card__body">
		<span class="cat-card__title"><?php echo esc_html( $term->name ); ?></span>
		<?php if ( $desc ) : ?>
			<span class="cat-card__desc"><?php echo esc_html( $desc ); ?></span>
		<?php endif; ?>
	</span>
</a>
