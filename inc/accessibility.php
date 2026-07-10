<?php
/**
 * Accessibility enhancements.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a "Skip to content" link as the first focusable element.
 */
function mathilde_skip_link() {
	printf(
		'<a class="skip-link screen-reader-text" href="#main">%s</a>',
		esc_html__( 'Skip to content', 'mathilde' )
	);
}
add_action( 'mathilde_before_header', 'mathilde_skip_link' );

/**
 * Improve keyboard accessibility of sub-menus by adding aria attributes.
 *
 * @param string   $output Menu item output.
 * @param WP_Post  $item   Menu item.
 * @param int      $depth  Depth.
 * @param stdClass $args   Args.
 * @return string
 */
function mathilde_nav_submenu_aria( $output, $item, $depth, $args ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		if ( in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
			$output = str_replace(
				'<a ',
				'<a aria-haspopup="true" aria-expanded="false" ',
				$output
			);
		}
	}
	return $output;
}
add_filter( 'walker_nav_menu_start_el', 'mathilde_nav_submenu_aria', 10, 4 );

/**
 * Ensure images inserted into content get a sensible default alt fallback for
 * screen readers (uses the attachment title when alt is empty).
 *
 * @param array $attr Attributes.
 * @param WP_Post $attachment Attachment.
 * @return array
 */
function mathilde_image_alt_fallback( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) && $attachment instanceof WP_Post ) {
		$attr['alt'] = esc_attr( $attachment->post_title );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'mathilde_image_alt_fallback', 10, 2 );
