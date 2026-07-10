<?php
/**
 * FAQ accordion component. Pulls questions from the Customizer repeater
 * (stored as a JSON string) or accepts $args['items'] directly.
 *
 * @package Mathilde
 */

$args = wp_parse_args( $args ?? array(), array( 'items' => null, 'image' => '' ) );

$items = $args['items'];
if ( null === $items ) {
	$json  = mathilde_option( 'faq_items', '' );
	$items = $json ? json_decode( $json, true ) : array();
}

if ( empty( $items ) ) {
	// Sensible defaults so the section is never empty on a fresh install.
	$items = array(
		array( 'q' => __( 'Who is behind this blog?', 'mathilde' ), 'a' => __( 'A fashion, beauty & lifestyle enthusiast sharing honest reviews and everyday inspiration.', 'mathilde' ) ),
		array( 'q' => __( 'What topics do you cover?', 'mathilde' ), 'a' => __( 'Fashion, beauty, jewelry, wellness, travel and intentional living.', 'mathilde' ) ),
		array( 'q' => __( 'How often is new content published?', 'mathilde' ), 'a' => __( 'New articles are published several times a week.', 'mathilde' ) ),
		array( 'q' => __( 'How can I collaborate or work with you?', 'mathilde' ), 'a' => __( 'Reach out via the contact page — we love thoughtful partnerships.', 'mathilde' ) ),
	);
}

$image = $args['image'] ? $args['image'] : mathilde_option( 'faq_image' );
?>
<div class="faq">
	<div class="faq__list">
		<?php foreach ( $items as $i => $item ) : ?>
			<div class="faq-item reveal">
				<button class="faq-item__q" type="button" aria-expanded="false">
					<span><?php echo esc_html( $item['q'] ); ?></span>
					<?php mathilde_the_icon( 'plus', 18 ); ?>
				</button>
				<div class="faq-item__a">
					<div class="faq-item__a-inner"><?php echo wp_kses_post( wpautop( $item['a'] ) ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $image ) : ?>
		<div class="faq__media reveal"><img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy"></div>
	<?php endif; ?>
</div>
