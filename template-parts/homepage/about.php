<?php
/**
 * Homepage — "About Mathilde" introduction band.
 *
 * @package Mathilde
 */

if ( ! mathilde_option( 'about_enable', true ) ) {
	return;
}

$eyebrow = mathilde_option( 'about_eyebrow', __( 'About Mathilde Lacombe', 'mathilde' ) );
$title   = mathilde_option( 'about_title', __( 'Passionate About Beauty, Fashion &amp; Meaningful Living', 'mathilde' ) );
$text    = mathilde_option( 'about_text', __( 'A fashion, beauty & lifestyle enthusiast sharing timeless style inspiration, honest reviews, travel adventures, and everyday tips for living beautifully and intentionally.', 'mathilde' ) );
$image   = mathilde_option( 'about_image' );
$link    = mathilde_option( 'about_link', '' );
?>
<section class="section section--tight" aria-label="<?php esc_attr_e( 'About', 'mathilde' ); ?>">
	<div class="container container--wide">
		<div class="about reveal">
			<div class="about__body">
				<span class="m-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<h2 class="about__title"><?php echo wp_kses_post( $title ); ?></h2>
				<p class="about__text"><?php echo wp_kses_post( $text ); ?></p>
				<a class="btn btn--outline" href="<?php echo esc_url( $link ? $link : home_url( '/about/' ) ); ?>"><?php esc_html_e( 'More About Me', 'mathilde' ); ?></a>
			</div>
			<div class="about__media">
				<?php if ( $image ) : ?>
					<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $eyebrow ); ?>" loading="lazy">
				<?php else : ?>
					<span class="m-thumb-placeholder"><?php mathilde_the_icon( 'sparkle', 40 ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
