<?php
/**
 * AI Trust & Review section — four E-E-A-T trust pillars.
 *
 * @package Mathilde
 */

$items = array(
	array(
		'icon'  => 'sparkle',
		'title' => mathilde_option( 'ai_t1_title', __( 'AI-Supported Content', 'mathilde' ) ),
		'text'  => mathilde_option( 'ai_t1_text', __( 'We use AI tools to enhance research, writing and editing for accurate, helpful content.', 'mathilde' ) ),
	),
	array(
		'icon'  => 'user',
		'title' => mathilde_option( 'ai_t2_title', __( 'Human-Curated', 'mathilde' ) ),
		'text'  => mathilde_option( 'ai_t2_text', __( 'Every article is carefully reviewed and refined to ensure quality and authenticity.', 'mathilde' ) ),
	),
	array(
		'icon'  => 'shield',
		'title' => mathilde_option( 'ai_t3_title', __( 'Transparent &amp; Honest', 'mathilde' ) ),
		'text'  => mathilde_option( 'ai_t3_text', __( 'We are committed to transparency, honesty and providing real value to our readers.', 'mathilde' ) ),
	),
	array(
		'icon'  => 'heart',
		'title' => mathilde_option( 'ai_t4_title', __( 'Your Trust Matters', 'mathilde' ) ),
		'text'  => mathilde_option( 'ai_t4_text', __( 'Your trust is our priority. We are always improving to bring you the best experience.', 'mathilde' ) ),
	),
);
?>
<div class="ai-trust__grid">
	<?php foreach ( $items as $i => $item ) : ?>
		<div class="ai-trust__item reveal reveal-delay-<?php echo (int) ( $i + 1 ); ?>">
			<span class="ai-trust__icon"><?php mathilde_the_icon( $item['icon'], 22 ); ?></span>
			<h3 class="ai-trust__title"><?php echo wp_kses_post( $item['title'] ); ?></h3>
			<p class="ai-trust__text"><?php echo wp_kses_post( $item['text'] ); ?></p>
		</div>
	<?php endforeach; ?>
</div>
