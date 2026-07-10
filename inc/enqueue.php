<?php
/**
 * Asset pipeline — styles & scripts.
 *
 * Styles are split into modular files (per the wireframe) and enqueued in a
 * deterministic cascade order. Google Fonts are loaded with a preconnect for
 * performance.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preconnect to Google Fonts to shave the handshake cost.
 */
function mathilde_resource_hints( $hints, $relation ) {
	if ( 'preconnect' === $relation ) {
		$hints[] = array( 'href' => 'https://fonts.googleapis.com' );
		$hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'mathilde_resource_hints', 10, 2 );

/**
 * Enqueue front-end styles & scripts.
 */
function mathilde_enqueue_assets() {
	$ver = MATHILDE_VERSION;
	$css = MATHILDE_URI . 'assets/css/';
	$js  = MATHILDE_URI . 'assets/js/';

	/* ---- Fonts -------------------------------------------------------- */
	wp_enqueue_style(
		'mathilde-fonts',
		'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600;700&family=Jost:wght@400;500;600&display=swap',
		array(),
		null
	);

	/* ---- Design system (ordered cascade) ------------------------------ */
	wp_enqueue_style( 'mathilde-base',       $css . 'base.css',       array( 'mathilde-fonts' ), $ver );
	wp_enqueue_style( 'mathilde-typography', $css . 'typography.css', array( 'mathilde-base' ), $ver );
	wp_enqueue_style( 'mathilde-layout',     $css . 'layout.css',     array( 'mathilde-base' ), $ver );
	wp_enqueue_style( 'mathilde-components',  $css . 'components.css', array( 'mathilde-layout' ), $ver );
	wp_enqueue_style( 'mathilde-utilities',  $css . 'utilities.css',  array( 'mathilde-components' ), $ver );
	wp_enqueue_style( 'mathilde-responsive', $css . 'responsive.css', array( 'mathilde-components' ), $ver );
	wp_enqueue_style( 'mathilde-dark',       $css . 'dark-mode.css',  array( 'mathilde-responsive' ), $ver );

	// The main stylesheet (theme header) — keep last so child themes can hook.
	wp_enqueue_style( 'mathilde-style', get_stylesheet_uri(), array( 'mathilde-dark' ), $ver );

	// Inject Customizer-driven CSS variables.
	wp_add_inline_style( 'mathilde-base', mathilde_dynamic_css() );

	/* ---- Scripts ------------------------------------------------------ */
	wp_enqueue_script( 'mathilde-navigation', $js . 'navigation.js', array(), $ver, true );
	wp_enqueue_script( 'mathilde-dark-mode',  $js . 'dark-mode.js',  array(), $ver, true );
	wp_enqueue_script( 'mathilde-carousel',   $js . 'carousel.js',   array(), $ver, true );
	wp_enqueue_script( 'mathilde-newsletter', $js . 'newsletter.js', array(), $ver, true );
	wp_enqueue_script( 'mathilde-theme',      $js . 'theme.js',      array( 'mathilde-navigation' ), $ver, true );

	// Expose ajax + i18n strings to JS.
	wp_localize_script(
		'mathilde-theme',
		'MathildeData',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'mathilde_nonce' ),
			'restUrl'   => esc_url_raw( rest_url() ),
			'i18n'      => array(
				'loading'   => __( 'Loading…', 'mathilde' ),
				'loadMore'  => __( 'Load More', 'mathilde' ),
				'noMore'    => __( 'No more posts', 'mathilde' ),
				'subscribed'=> __( 'Thank you — you are subscribed!', 'mathilde' ),
				'error'     => __( 'Something went wrong. Please try again.', 'mathilde' ),
			),
		)
	);

	// Threaded comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'mathilde_enqueue_assets' );

/**
 * Preload the homepage hero image (the LCP element) so the browser fetches it
 * during head parsing instead of after first paint.
 */
function mathilde_preload_lcp_image() {
	if ( ! is_front_page() ) {
		return;
	}
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => false, // Matches the hero query (sticky leads).
		'no_found_rows'       => true,
		'fields'              => 'ids',
	);
	$cat = mathilde_option( 'hero_category', '' );
	if ( $cat ) {
		$args['category_name'] = $cat;
	}
	$ids = get_posts( $args );
	if ( empty( $ids ) ) {
		return;
	}
	$url = get_the_post_thumbnail_url( $ids[0], 'mathilde-hero' );
	if ( $url ) {
		printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $url ) );
	}
}
add_action( 'wp_head', 'mathilde_preload_lcp_image', 2 );

/**
 * Build the dynamic CSS variable block from Customizer values.
 *
 * Centralising the tokens here means the whole palette / type scale can be
 * re-themed from Appearance → Customize without touching CSS files.
 *
 * @return string
 */
function mathilde_dynamic_css() {
	$defaults = mathilde_design_defaults();

	$accent   = get_theme_mod( 'mathilde_color_accent', $defaults['accent'] );
	$ink      = get_theme_mod( 'mathilde_color_ink', $defaults['ink'] );
	$soft     = get_theme_mod( 'mathilde_color_soft', $defaults['soft'] );
	$blush    = get_theme_mod( 'mathilde_color_blush', $defaults['blush'] );
	$cream    = get_theme_mod( 'mathilde_color_cream', $defaults['cream'] );
	$border   = get_theme_mod( 'mathilde_color_border', $defaults['border'] );
	$serif    = get_theme_mod( 'mathilde_font_serif', $defaults['serif'] );
	$sans     = get_theme_mod( 'mathilde_font_sans', $defaults['sans'] );
	$base_sz  = (int) get_theme_mod( 'mathilde_base_font_size', $defaults['base_size'] );
	$radius   = (int) get_theme_mod( 'mathilde_radius', $defaults['radius'] );

	$css  = ':root{';
	$css .= '--c-accent:' . sanitize_hex_color( $accent ) . ';';
	$css .= '--c-accent-soft:' . mathilde_hex_to_rgba( $accent, 0.12 ) . ';';
	$css .= '--c-ink:' . sanitize_hex_color( $ink ) . ';';
	$css .= '--c-text:' . sanitize_hex_color( $ink ) . ';';
	$css .= '--c-text-soft:' . sanitize_hex_color( $soft ) . ';';
	$css .= '--c-blush:' . sanitize_hex_color( $blush ) . ';';
	$css .= '--c-cream:' . sanitize_hex_color( $cream ) . ';';
	$css .= '--c-border:' . sanitize_hex_color( $border ) . ';';
	$css .= '--font-serif:' . esc_attr( $serif ) . ',Georgia,serif;';
	$css .= '--font-sans:' . esc_attr( $sans ) . ',system-ui,-apple-system,sans-serif;';
	$css .= '--fs-base:' . $base_sz . 'px;';
	$css .= '--radius:' . $radius . 'px;';
	$css .= '}';

	return $css;
}
