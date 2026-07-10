<?php
/**
 * Mathilde Blog — theme bootstrap.
 *
 * Loads the theme's modular includes. Each concern lives in its own file
 * inside /inc/ so this bootstrap stays readable.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Theme constants.
 */
define( 'MATHILDE_VERSION', '1.0.0' );
define( 'MATHILDE_DIR', trailingslashit( get_template_directory() ) );
define( 'MATHILDE_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * Load a file from the /inc directory.
 *
 * @param string $file Relative path inside /inc (without leading slash).
 */
function mathilde_require( $file ) {
	$path = MATHILDE_DIR . 'inc/' . $file;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

mathilde_require( 'setup.php' );          // Theme supports, menus, image sizes.
mathilde_require( 'helpers.php' );        // Small utility functions.
mathilde_require( 'template-tags.php' );  // Output helpers used in templates.
mathilde_require( 'enqueue.php' );        // Styles & scripts pipeline.
mathilde_require( 'widgets.php' );        // Sidebar & footer widget areas.
mathilde_require( 'customizer.php' );     // Appearance → Customize controls.
mathilde_require( 'schema.php' );         // SEO / AEO structured data.
mathilde_require( 'accessibility.php' );  // Skip links, a11y enhancements.
mathilde_require( 'meta-boxes.php' );      // Post extras + author fields.
mathilde_require( 'ajax.php' );           // AJAX: load-more, newsletter, search.
mathilde_require( 'membership.php' );      // Paid contributor memberships (core).
mathilde_require( 'membership-paypal.php' ); // PayPal REST + account provisioning.
mathilde_require( 'membership-admin.php' );  // Users → Membership settings UI.
mathilde_require( 'ebooks.php' );           // Sell digital ebooks/guides.
mathilde_require( 'ebooks-paypal.php' );     // Ebook checkout + secure downloads.

/**
 * GitHub-powered theme updates. Publish a new release (with a bumped "Version:"
 * in style.css) at github.com/honeysriwas/mathilde-blog and the live site will
 * offer it under Dashboard → Updates → Themes.
 */
require_once MATHILDE_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	$mathilde_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/honeysriwas/mathilde-blog/',
		get_template_directory(),
		'mathilde-blog'
	);
}
