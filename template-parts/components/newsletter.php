<?php
/**
 * Newsletter component. Renders in three styles via $args['style']:
 *   'band'  — full-width pink band (homepage / footer)
 *   'card'  — compact sidebar card
 *   'inline'— mid-article inline prompt
 *
 * @package Mathilde
 */

$args  = wp_parse_args( $args ?? array(), array( 'style' => 'band' ) );
$style = $args['style'];

$title = mathilde_option( 'newsletter_title', __( 'Join 25,000+ Readers', 'mathilde' ) );
$text  = mathilde_option( 'newsletter_text', __( 'Get weekly fashion, beauty & lifestyle inspiration straight to your inbox.', 'mathilde' ) );

$form = '<form data-newsletter class="' . ( 'band' === $style ? 'newsletter-band__form' : 'field-inline' ) . '">'
	. '<label class="screen-reader-text" for="nl-' . esc_attr( $style ) . '">' . esc_html__( 'Email address', 'mathilde' ) . '</label>'
	. '<input type="email" id="nl-' . esc_attr( $style ) . '" name="email" required placeholder="' . esc_attr__( 'Your email address', 'mathilde' ) . '">'
	. '<button type="submit" class="btn' . ( 'card' === $style ? ' btn--block' : '' ) . '">' . esc_html__( 'Subscribe', 'mathilde' ) . '</button>'
	. '</form>';

if ( 'card' === $style ) : ?>
	<div class="newsletter-card widget">
		<h3 class="newsletter-card__title"><?php echo esc_html( $title ); ?></h3>
		<p class="newsletter-card__text"><?php echo esc_html( $text ); ?></p>
		<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<p class="newsletter-msg"></p>
	</div>
<?php elseif ( 'inline' === $style ) : ?>
	<aside class="newsletter-band" style="margin-block:var(--space-6);">
		<div>
			<h3 class="newsletter-band__title"><?php echo esc_html( $title ); ?></h3>
			<p class="newsletter-band__text"><?php echo esc_html( $text ); ?></p>
		</div>
		<div style="flex:1 1 360px;max-width:480px;">
			<?php echo $form; // phpcs:ignore ?>
			<p class="newsletter-msg"></p>
		</div>
	</aside>
<?php else : ?>
	<section class="newsletter-band reveal" aria-label="<?php esc_attr_e( 'Newsletter', 'mathilde' ); ?>">
		<div>
			<h2 class="newsletter-band__title"><?php echo esc_html( $title ); ?></h2>
			<p class="newsletter-band__text"><?php echo esc_html( $text ); ?></p>
		</div>
		<div style="flex:1 1 360px;max-width:480px;">
			<?php echo $form; // phpcs:ignore ?>
			<p class="newsletter-msg"></p>
		</div>
	</section>
<?php endif; ?>
