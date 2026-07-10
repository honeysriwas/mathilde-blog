<?php
/**
 * Helpers — small, reusable utility functions.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The theme's default design tokens.
 *
 * @return array
 */
function mathilde_design_defaults() {
	return array(
		'accent'    => '#c0807c', // Dusty rose used for eyebrows / labels / links.
		'ink'       => '#1c1c1c', // Primary text & buttons.
		'soft'      => '#595959', // Secondary text (WCAG AA ≥4.5:1 on white).
		'blush'     => '#fae9e6', // Soft pink panels (announcement, newsletter).
		'cream'     => '#fbf7f4', // Warm surface for alternating sections.
		'border'    => '#ece7e3', // Hairline borders.
		'serif'     => '"Playfair Display"',
		'sans'      => '"Inter"',
		'base_size' => 17,
		'radius'    => 4,
	);
}

/**
 * Convert a hex colour to an rgba() string.
 *
 * @param string $hex   Hex colour (#rrggbb).
 * @param float  $alpha Alpha 0–1.
 * @return string
 */
function mathilde_hex_to_rgba( $hex, $alpha = 1 ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return 'rgba(0,0,0,' . $alpha . ')';
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return "rgba($r,$g,$b,$alpha)";
}

/**
 * Estimate reading time for a piece of content.
 *
 * @param int|null $post_id Post ID (defaults to current).
 * @return int Minutes (minimum 1).
 */
function mathilde_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$content = get_post_field( 'post_content', $post_id );
	$words   = str_word_count( wp_strip_all_tags( (string) $content ) );
	$minutes = (int) ceil( $words / 200 );
	return max( 1, $minutes );
}

/**
 * Return the primary category for a post (uses Yoast primary term if present).
 *
 * @param int|null $post_id Post ID.
 * @return WP_Term|null
 */
function mathilde_primary_category( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$primary = (int) get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
	if ( $primary ) {
		$term = get_term( $primary, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0] : null;
}

/**
 * Whether dark mode should default to on (Customizer).
 *
 * @return bool
 */
function mathilde_dark_mode_enabled() {
	return (bool) get_theme_mod( 'mathilde_dark_mode_toggle', true );
}

/**
 * Get a Customizer setting with a default fallback in one call.
 *
 * @param string $key     Setting key (without the mathilde_ prefix).
 * @param mixed  $default Default value.
 * @return mixed
 */
function mathilde_option( $key, $default = '' ) {
	return get_theme_mod( 'mathilde_' . $key, $default );
}

/**
 * Render a small inline SVG icon from the theme's icon set.
 *
 * @param string $name  Icon name.
 * @param int    $size  Pixel size.
 * @return string SVG markup.
 */
function mathilde_icon( $name, $size = 20 ) {
	$icons = mathilde_icon_set();
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="m-icon m-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$icons[ $name ]
	);
}

/**
 * Echo helper for mathilde_icon().
 */
function mathilde_the_icon( $name, $size = 20 ) {
	echo mathilde_icon( $name, $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted SVG.
}

/**
 * The theme's SVG icon path library.
 *
 * @return array
 */
function mathilde_icon_set() {
	return array(
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
		'menu'      => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
		'close'     => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
		'arrow-left'=> '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
		'arrow-right'=> '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
		'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
		'chevron-up'   => '<polyline points="18 15 12 9 6 15"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'      => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>',
		'play'      => '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>',
		'check'     => '<polyline points="20 6 9 17 4 12"/>',
		'plus'      => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
		'minus'     => '<line x1="5" y1="12" x2="19" y2="12"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/>',
		'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6" fill="currentColor"/>',
		'pinterest' => '<path d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.1-2 0-2.9l1.2-5.1s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.6 2.2-.9 3.4-.2.9.5 1.7 1.4 1.7 1.7 0 2.9-2.2 2.9-4.7 0-2-1.3-3.4-3.7-3.4a4.2 4.2 0 0 0-4.4 4.2c0 .8.3 1.4.6 1.8.1.1.1.2.1.4l-.2.8c0 .2-.2.3-.4.2-1.2-.5-1.8-1.9-1.8-3.4 0-2.5 2.1-5.5 6.3-5.5 3.4 0 5.6 2.4 5.6 5 0 3.4-1.9 6-4.7 6-1 0-1.9-.5-2.2-1.1l-.6 2.4c-.2.8-.7 1.7-1.1 2.3A10 10 0 1 0 12 2z" stroke="none" fill="currentColor"/>',
		'tiktok'    => '<path d="M16 3v3a5 5 0 0 0 4 4M16 3v9.5a4.5 4.5 0 1 1-4.5-4.5"/>',
		'twitter'   => '<path d="M22 4a9 9 0 0 1-2.6.9A4.3 4.3 0 0 0 21.3 2a8.6 8.6 0 0 1-2.7 1A4.3 4.3 0 0 0 11 6.8 12.2 12.2 0 0 1 3 2.7a4.3 4.3 0 0 0 1.3 5.7A4.2 4.2 0 0 1 2.4 8v.1a4.3 4.3 0 0 0 3.4 4.2 4.3 4.3 0 0 1-1.9.1 4.3 4.3 0 0 0 4 3A8.6 8.6 0 0 1 2 17.6 12.1 12.1 0 0 0 8.6 19.5c7.9 0 12.2-6.5 12.2-12.2v-.6A8.7 8.7 0 0 0 23 4.6 8.5 8.5 0 0 1 22 4z" stroke="none" fill="currentColor"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><polygon points="10 9 15 12 10 15 10 9"/>',
		'share'     => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/>',
		'link'      => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>',
		'arrow-up'  => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
		'shield'    => '<path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'heart'     => '<path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21l8.8-8.3a5 5 0 0 0 0-7.1z"/>',
		'eye'       => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
		'sparkle'   => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z" fill="currentColor" stroke="none"/>',
		'list'      => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1" fill="currentColor"/><circle cx="3.5" cy="12" r="1" fill="currentColor"/><circle cx="3.5" cy="18" r="1" fill="currentColor"/>',
	);
}

/**
 * Map a social network slug to its icon name.
 *
 * @param string $url URL to inspect.
 * @return string Icon name.
 */
function mathilde_social_icon_for_url( $url ) {
	$map = array(
		'facebook'  => 'facebook',
		'instagram' => 'instagram',
		'pinterest' => 'pinterest',
		'tiktok'    => 'tiktok',
		'twitter'   => 'twitter',
		'x.com'     => 'twitter',
		'youtube'   => 'youtube',
	);
	foreach ( $map as $needle => $icon ) {
		if ( false !== strpos( $url, $needle ) ) {
			return $icon;
		}
	}
	return 'link';
}
