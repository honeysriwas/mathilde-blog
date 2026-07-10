<?php
/**
 * Recommended guide card — an inline ebook promo shown after post content.
 *
 * Accepts $args['ebook_id'] or auto-selects via mathilde_recommended_ebook().
 *
 * @package Mathilde
 */

if ( ! post_type_exists( 'mathilde_ebook' ) || ! mathilde_option( 'recommend_guide_enable', true ) ) {
	return;
}

$args     = wp_parse_args( $args ?? array(), array( 'ebook_id' => 0 ) );
$ebook_id = $args['ebook_id'] ? (int) $args['ebook_id'] : mathilde_recommended_ebook();

if ( ! $ebook_id || 'publish' !== get_post_status( $ebook_id ) ) {
	return;
}

$price    = mathilde_ebook_price( $ebook_id );
$subtitle = get_post_meta( $ebook_id, '_mathilde_ebook_subtitle', true );
$s        = mathilde_membership_settings();
$owned    = mathilde_ebook_user_owns( $ebook_id );
$url      = get_permalink( $ebook_id );
$label    = mathilde_option( 'recommend_guide_title', __( 'Recommended Guide', 'mathilde' ) );
?>
<aside class="recommend-guide reveal" aria-label="<?php echo esc_attr( $label ); ?>">
	<a class="recommend-guide__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail( $ebook_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $ebook_id, 'mathilde-thumb', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<span class="m-thumb-placeholder"><?php mathilde_the_icon( 'sparkle', 28 ); ?></span>
		<?php endif; ?>
	</a>
	<div class="recommend-guide__body">
		<span class="m-eyebrow text-accent"><?php mathilde_the_icon( 'sparkle', 14 ); ?> <?php echo esc_html( $label ); ?></span>
		<h3 class="recommend-guide__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $ebook_id ) ); ?></a></h3>
		<?php if ( $subtitle ) : ?>
			<p class="recommend-guide__sub"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
	<div class="recommend-guide__cta">
		<?php if ( $price ) : ?>
			<span class="recommend-guide__price"><?php echo esc_html( mathilde_membership_format_price( $price, $s['currency'] ) ); ?></span>
		<?php endif; ?>
		<a class="btn btn--sm <?php echo $owned ? '' : 'btn--rose'; ?>" href="<?php echo esc_url( $owned ? mathilde_ebook_download_url( $owned ) : $url ); ?>">
			<?php echo $owned ? esc_html__( 'Download', 'mathilde' ) : esc_html__( 'Get the guide', 'mathilde' ); ?>
		</a>
	</div>
</aside>
