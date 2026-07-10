<?php
/**
 * Homepage — Instagram strip.
 *
 * Images come from the Customizer (newline-separated URLs). Without a feed
 * plugin this is a curated static strip — the recommended, dependency-free
 * approach. Each image links to the profile.
 *
 * @package Mathilde
 */

if ( ! mathilde_option( 'insta_enable', true ) ) {
	return;
}

$handle  = mathilde_option( 'insta_handle', '@mathildelacombe' );
$profile = mathilde_option( 'insta_url', 'https://instagram.com/' );
$raw     = mathilde_option( 'insta_images', '' );
$images  = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );

// Fallback: pull recent post thumbnails so the strip is never empty.
if ( empty( $images ) ) {
	$recent = get_posts( array( 'numberposts' => 6, 'fields' => 'ids' ) );
	foreach ( $recent as $pid ) {
		$url = get_the_post_thumbnail_url( $pid, 'mathilde-thumb' );
		if ( $url ) {
			$images[] = $url;
		}
	}
}
if ( empty( $images ) ) {
	return;
}
?>
<section class="section section--tight" aria-label="<?php esc_attr_e( 'Instagram', 'mathilde' ); ?>">
	<div class="container container--wide">
		<div class="insta__head reveal">
			<h2 class="m-section-title"><?php esc_html_e( 'Follow Me on Instagram', 'mathilde' ); ?></h2>
			<a class="insta__handle" href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $handle ); ?></a>
		</div>
		<div class="insta__grid">
			<?php foreach ( array_slice( $images, 0, 6 ) as $img ) : ?>
				<a class="insta__item reveal" href="<?php echo esc_url( $profile ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'View on Instagram', 'mathilde' ); ?>">
					<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
