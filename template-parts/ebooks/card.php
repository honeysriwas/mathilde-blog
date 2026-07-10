<?php
/**
 * Ebook card — used in the shop grid.
 *
 * @package Mathilde
 */

$ebook_id = get_the_ID();
$price    = mathilde_ebook_price( $ebook_id );
$subtitle = get_post_meta( $ebook_id, '_mathilde_ebook_subtitle', true );
$s        = mathilde_membership_settings();
?>
<article <?php post_class( 'ebook-card reveal' ); ?>>
	<a class="ebook-card__cover" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'mathilde-card', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<span class="m-thumb-placeholder"><?php mathilde_the_icon( 'sparkle', 30 ); ?></span>
		<?php endif; ?>
		<?php if ( $price ) : ?>
			<span class="ebook-card__price"><?php echo esc_html( mathilde_membership_format_price( $price, $s['currency'] ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="ebook-card__body">
		<span class="m-eyebrow text-accent"><?php esc_html_e( 'Digital Guide', 'mathilde' ); ?></span>
		<h3 class="ebook-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $subtitle ) : ?>
			<p class="ebook-card__sub"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
		<a class="link-arrow" href="<?php the_permalink(); ?>">
			<?php echo mathilde_ebook_user_owns( $ebook_id ) ? esc_html__( 'Download', 'mathilde' ) : esc_html__( 'Get it', 'mathilde' ); ?>
			<?php mathilde_the_icon( 'arrow-right', 16 ); ?>
		</a>
	</div>
</article>
