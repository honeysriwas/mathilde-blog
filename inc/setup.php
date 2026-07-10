<?php
/**
 * Theme setup — supports, menus, image sizes, content width.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme features with WordPress.
 */
function mathilde_setup() {

	// Make theme available for translation.
	load_theme_textdomain( 'mathilde', MATHILDE_DIR . 'languages' );

	// Core feed links in <head>.
	add_theme_support( 'automatic-feed-links' );

	// Let WordPress manage the document <title>.
	add_theme_support( 'title-tag' );

	// Featured images.
	add_theme_support( 'post-thumbnails' );

	// Custom logo (the serif "MATHILDE / LACOMBE" lockup).
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
			'unlink-homepage-logo' => false,
		)
	);

	// HTML5 markup.
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	// Selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Gutenberg / block editor support.
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor-style.css' );

	// Editor color palette mirrors the design tokens.
	add_theme_support(
		'editor-color-palette',
		array(
			array( 'name' => __( 'Ink', 'mathilde' ),        'slug' => 'ink',    'color' => '#1c1c1c' ),
			array( 'name' => __( 'Soft Text', 'mathilde' ),  'slug' => 'soft',   'color' => '#6e6e6e' ),
			array( 'name' => __( 'Rose', 'mathilde' ),       'slug' => 'rose',   'color' => '#c0807c' ),
			array( 'name' => __( 'Blush', 'mathilde' ),      'slug' => 'blush',  'color' => '#fae9e6' ),
			array( 'name' => __( 'Cream', 'mathilde' ),      'slug' => 'cream',  'color' => '#fbf7f4' ),
			array( 'name' => __( 'White', 'mathilde' ),      'slug' => 'white',  'color' => '#ffffff' ),
		)
	);

	add_theme_support(
		'editor-font-sizes',
		array(
			array( 'name' => __( 'Small', 'mathilde' ),  'slug' => 'small',  'size' => 15 ),
			array( 'name' => __( 'Normal', 'mathilde' ), 'slug' => 'normal', 'size' => 18 ),
			array( 'name' => __( 'Large', 'mathilde' ),  'slug' => 'large',  'size' => 26 ),
			array( 'name' => __( 'Huge', 'mathilde' ),   'slug' => 'huge',   'size' => 40 ),
		)
	);

	// Navigation menus.
	register_nav_menus(
		array(
			'primary'   => __( 'Primary Menu', 'mathilde' ),
			'footer'    => __( 'Footer Menu', 'mathilde' ),
			'social'    => __( 'Social Links', 'mathilde' ),
			'legal'     => __( 'Legal / Bottom Menu', 'mathilde' ),
		)
	);

	// Editorial image sizes used across cards & heroes.
	add_image_size( 'mathilde-hero', 1280, 860, true );        // Homepage / single hero.
	add_image_size( 'mathilde-card', 640, 800, true );         // Portrait post cards.
	add_image_size( 'mathilde-card-wide', 720, 480, true );    // Landscape cards.
	add_image_size( 'mathilde-thumb', 200, 200, true );        // Sidebar thumbnails.
	add_image_size( 'mathilde-category', 480, 600, true );     // Category cards.
}
add_action( 'after_setup_theme', 'mathilde_setup' );

/**
 * Set the content width.
 */
function mathilde_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'mathilde_content_width', 760 );
}
add_action( 'after_setup_theme', 'mathilde_content_width', 0 );

/**
 * Surface the custom image sizes in the editor's size dropdown.
 *
 * @param array $sizes Existing sizes.
 * @return array
 */
function mathilde_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'mathilde-hero'      => __( 'Editorial Hero', 'mathilde' ),
			'mathilde-card'      => __( 'Portrait Card', 'mathilde' ),
			'mathilde-card-wide' => __( 'Landscape Card', 'mathilde' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'mathilde_image_size_names' );

/**
 * Add a body class describing the current layout so CSS can adapt.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function mathilde_body_classes( $classes ) {
	if ( ! is_singular() || is_front_page() ) {
		$classes[] = 'has-sidebar';
	}
	if ( is_singular( 'post' ) ) {
		$classes[] = 'single-article';
	}
	if ( is_front_page() ) {
		$classes[] = 'is-homepage';
	}
	$classes[] = 'no-js'; // Swapped to "js" by theme.js as soon as it runs.
	return $classes;
}
add_filter( 'body_class', 'mathilde_body_classes' );

/**
 * Strip the "Category:" / "Tag:" prefix from archive titles for a cleaner,
 * magazine-style heading (matches the mockup).
 *
 * @param string $title Archive title.
 * @return string
 */
function mathilde_clean_archive_title( $title ) {
	if ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
	} elseif ( is_author() ) {
		$title = get_the_author();
	} elseif ( is_post_type_archive() ) {
		$title = post_type_archive_title( '', false );
	}
	return $title;
}
add_filter( 'get_the_archive_title', 'mathilde_clean_archive_title' );

/**
 * Honour the ?orderby= sort parameter on archive listings.
 *
 * @param WP_Query $query Main query.
 */
function mathilde_archive_sorting( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_archive() || $query->is_home() || $query->is_search() ) ) {
		return;
	}
	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : '';
	switch ( $orderby ) {
		case 'oldest':
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'ASC' );
			break;
		case 'title':
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			break;
		case 'popular':
			$query->set( 'orderby', 'comment_count' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'mathilde_archive_sorting' );

/**
 * Pingback header on singular views.
 */
function mathilde_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'mathilde_pingback_header' );
