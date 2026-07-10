<?php
/**
 * Homepage — "As Featured In" logo carousel.
 *
 * Logos are entered in the Customizer as a newline-separated list of either
 * plain names (rendered as serif wordmarks) or image URLs.
 *
 * @package Mathilde
 */

if ( ! mathilde_option( 'featuredin_enable', true ) ) {
	return;
}

$raw   = mathilde_option( 'featuredin_logos', "Vogue\nElle\nByrdie\nForbes\nCosmopolitan\nGlamour" );
$logos = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );
if ( empty( $logos ) ) {
	return;
}
?>
<section class="section section--tight" aria-label="<?php esc_attr_e( 'As featured in', 'mathilde' ); ?>">
	<div class="container container--wide">
		<div class="featured-in reveal section">
			<p class="featured-in__head"><?php esc_html_e( 'As Featured In', 'mathilde' ); ?></p>
			<div class="carousel" data-slider>
				<button class="carousel__btn carousel__btn--prev" type="button" data-prev aria-label="<?php esc_attr_e( 'Previous', 'mathilde' ); ?>"><?php mathilde_the_icon( 'arrow-left', 18 ); ?></button>
				<div class="featured-in__track" data-track>
					<?php foreach ( $logos as $logo ) : ?>
						<div class="featured-in__logo" style="text-align:center;">
							<?php if ( filter_var( $logo, FILTER_VALIDATE_URL ) ) : ?>
								<img src="<?php echo esc_url( $logo ); ?>" alt="">
							<?php else : ?>
								<?php echo esc_html( $logo ); ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<button class="carousel__btn carousel__btn--next" type="button" data-next aria-label="<?php esc_attr_e( 'Next', 'mathilde' ); ?>"><?php mathilde_the_icon( 'arrow-right', 18 ); ?></button>
			</div>
		</div>
	</div>
</section>
