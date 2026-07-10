<?php
/**
 * Customizer — Appearance → Customize controls.
 *
 * Mirrors the panel structure described in the wireframe so the whole theme
 * is editable from the WordPress admin.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all Customizer settings & controls.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function mathilde_customize_register( $wp_customize ) {

	$defaults = mathilde_design_defaults();

	/* Helper closures for terse registration --------------------------- */
	$add_text = function ( $id, $label, $section, $default = '', $type = 'text' ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'mathilde_' . $id,
			array(
				'default'           => $default,
				'sanitize_callback' => ( 'textarea' === $type ) ? 'wp_kses_post' : 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'mathilde_' . $id,
			array(
				'label'   => $label,
				'section' => $section,
				'type'    => $type,
			)
		);
	};

	$add_check = function ( $id, $label, $section, $default = true ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'mathilde_' . $id,
			array(
				'default'           => $default,
				'sanitize_callback' => 'wp_validate_boolean',
			)
		);
		$wp_customize->add_control(
			'mathilde_' . $id,
			array(
				'label'   => $label,
				'section' => $section,
				'type'    => 'checkbox',
			)
		);
	};

	$add_color = function ( $id, $label, $section, $default ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'mathilde_' . $id,
			array(
				'default'           => $default,
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'mathilde_' . $id,
				array( 'label' => $label, 'section' => $section )
			)
		);
	};

	$add_image = function ( $id, $label, $section ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'mathilde_' . $id,
			array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' )
		);
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'mathilde_' . $id,
				array( 'label' => $label, 'section' => $section )
			)
		);
	};

	/* ================================================================== *
	 *  PANEL: Theme Options
	 * ================================================================== */
	$wp_customize->add_panel(
		'mathilde_panel',
		array(
			'title'    => __( 'Mathilde Theme Options', 'mathilde' ),
			'priority' => 5,
		)
	);

	/* ---- Colors (core "colors" section) ------------------------------ */
	$wp_customize->add_section( 'colors', array( 'title' => __( 'Colors', 'mathilde' ), 'priority' => 40 ) );
	$add_color( 'color_accent', __( 'Accent (rose)', 'mathilde' ), 'colors', $defaults['accent'] );
	$add_color( 'color_ink', __( 'Ink / text', 'mathilde' ), 'colors', $defaults['ink'] );
	$add_color( 'color_soft', __( 'Soft text', 'mathilde' ), 'colors', $defaults['soft'] );
	$add_color( 'color_blush', __( 'Blush panels', 'mathilde' ), 'colors', $defaults['blush'] );
	$add_color( 'color_cream', __( 'Cream surface', 'mathilde' ), 'colors', $defaults['cream'] );
	$add_color( 'color_border', __( 'Borders', 'mathilde' ), 'colors', $defaults['border'] );

	/* ---- Typography -------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_typography', array( 'title' => __( 'Typography', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$wp_customize->add_setting( 'mathilde_font_serif', array( 'default' => $defaults['serif'], 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'mathilde_font_serif', array(
		'label'       => __( 'Heading font (CSS family)', 'mathilde' ),
		'description' => __( 'e.g. "Playfair Display", "Cormorant Garamond"', 'mathilde' ),
		'section'     => 'mathilde_typography',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'mathilde_font_sans', array( 'default' => $defaults['sans'], 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'mathilde_font_sans', array(
		'label'   => __( 'Body font (CSS family)', 'mathilde' ),
		'section' => 'mathilde_typography',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'mathilde_base_font_size', array( 'default' => $defaults['base_size'], 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'mathilde_base_font_size', array(
		'label'       => __( 'Base font size (px)', 'mathilde' ),
		'section'     => 'mathilde_typography',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 14, 'max' => 22, 'step' => 1 ),
	) );
	$wp_customize->add_setting( 'mathilde_radius', array( 'default' => $defaults['radius'], 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'mathilde_radius', array(
		'label'       => __( 'Corner radius (px)', 'mathilde' ),
		'section'     => 'mathilde_typography',
		'type'        => 'number',
		'input_attrs' => array( 'min' => 0, 'max' => 24, 'step' => 1 ),
	) );

	/* ---- Header / Announcement --------------------------------------- */
	$wp_customize->add_section( 'mathilde_header', array( 'title' => __( 'Header & Announcement', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'announce_enable', __( 'Show announcement bar', 'mathilde' ), 'mathilde_header', true );
	$add_text( 'announce_text', __( 'Announcement text', 'mathilde' ), 'mathilde_header', __( 'The Latest in Fashion, Beauty &amp; Lifestyle — Read Our New Articles', 'mathilde' ), 'textarea' );
	$add_text( 'announce_link', __( 'Announcement link (optional)', 'mathilde' ), 'mathilde_header', '', 'url' );
	$add_text( 'brand_subtitle', __( 'Logo subtitle (when no image logo)', 'mathilde' ), 'mathilde_header' );
	$add_text( 'subscribe_url', __( 'Subscribe button URL', 'mathilde' ), 'mathilde_header', '', 'url' );

	/* ---- Hero -------------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_hero', array( 'title' => __( 'Homepage — Hero', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_text( 'hero_category', __( 'Hero category slug (blank = latest)', 'mathilde' ), 'mathilde_hero' );
	$wp_customize->add_setting( 'mathilde_hero_count', array( 'default' => 3, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'mathilde_hero_count', array( 'label' => __( 'Number of hero slides', 'mathilde' ), 'section' => 'mathilde_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 6 ) ) );

	/* ---- About ------------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_about', array( 'title' => __( 'Homepage — About', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'about_enable', __( 'Show About section', 'mathilde' ), 'mathilde_about', true );
	$add_text( 'about_eyebrow', __( 'Eyebrow label', 'mathilde' ), 'mathilde_about', __( 'About Mathilde Lacombe', 'mathilde' ) );
	$add_text( 'about_title', __( 'Title', 'mathilde' ), 'mathilde_about', __( 'Passionate About Beauty, Fashion &amp; Meaningful Living', 'mathilde' ), 'textarea' );
	$add_text( 'about_text', __( 'Text', 'mathilde' ), 'mathilde_about', __( 'A fashion, beauty & lifestyle enthusiast sharing timeless style inspiration, honest reviews, travel adventures, and everyday tips for living beautifully and intentionally.', 'mathilde' ), 'textarea' );
	$add_text( 'about_link', __( 'Button URL', 'mathilde' ), 'mathilde_about', '', 'url' );
	$add_image( 'about_image', __( 'About image', 'mathilde' ), 'mathilde_about' );

	/* ---- Category cards ---------------------------------------------- */
	$wp_customize->add_section( 'mathilde_catcards', array( 'title' => __( 'Homepage — Category Cards', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'catcards_enable', __( 'Show category cards', 'mathilde' ), 'mathilde_catcards', true );
	$add_text( 'catcards_slugs', __( 'Category slugs (comma separated, blank = top 5)', 'mathilde' ), 'mathilde_catcards' );

	/* ---- Featured In ------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_featuredin', array( 'title' => __( 'Homepage — Featured In', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'featuredin_enable', __( 'Show "Featured In" carousel', 'mathilde' ), 'mathilde_featuredin', true );
	$add_text( 'featuredin_logos', __( 'Logos — one per line (name or image URL)', 'mathilde' ), 'mathilde_featuredin', "Vogue\nElle\nByrdie\nForbes\nCosmopolitan\nGlamour", 'textarea' );

	/* ---- Post sections ----------------------------------------------- */
	$wp_customize->add_section( 'mathilde_sections', array( 'title' => __( 'Homepage — Post Sections', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	foreach ( array( 'fashion' => 'Fashion', 'beauty' => 'Beauty', 'jewelry' => 'Jewelry', 'health' => 'Health' ) as $key => $name ) {
		$add_check( "section_{$key}_enable", sprintf( __( 'Show %s section', 'mathilde' ), $name ), 'mathilde_sections', true );
		$add_text( "section_{$key}_title", sprintf( __( '%s — title', 'mathilde' ), $name ), 'mathilde_sections', $name . ' Posts' );
		$add_text( "section_{$key}_cat", sprintf( __( '%s — category slug', 'mathilde' ), $name ), 'mathilde_sections', strtolower( $name ) );
	}

	/* ---- Featured Guides (ebooks) ------------------------------------ */
	$wp_customize->add_section( 'mathilde_guides', array( 'title' => __( 'Homepage — Featured Guides', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'guides_enable', __( 'Show Featured Guides strip', 'mathilde' ), 'mathilde_guides', true );
	$add_text( 'guides_title', __( 'Title', 'mathilde' ), 'mathilde_guides', __( 'Shop the Guides', 'mathilde' ) );
	$add_text( 'guides_subtitle', __( 'Subtitle', 'mathilde' ), 'mathilde_guides', __( 'Beautifully crafted digital guides — instant download, yours forever.', 'mathilde' ) );
	$wp_customize->add_setting( 'mathilde_guides_count', array( 'default' => 4, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'mathilde_guides_count', array( 'label' => __( 'Number of guides to show', 'mathilde' ), 'section' => 'mathilde_guides', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 8 ) ) );
	$add_check( 'recommend_guide_enable', __( 'Show “Recommended Guide” card after post content', 'mathilde' ), 'mathilde_guides', true );
	$add_text( 'recommend_guide_title', __( 'Recommended guide label', 'mathilde' ), 'mathilde_guides', __( 'Recommended Guide', 'mathilde' ) );

	/* ---- Reddit community strip (needs the AI Writer plugin) ---------- */
	$wp_customize->add_section( 'mathilde_reddit', array( 'title' => __( 'Homepage — Reddit', 'mathilde' ), 'panel' => 'mathilde_panel', 'description' => __( 'Requires the Mathilde AI Writer plugin (connect Reddit in its settings).', 'mathilde' ) ) );
	$add_check( 'reddit_enable', __( 'Show “From the Community” Reddit strip', 'mathilde' ), 'mathilde_reddit', false );
	$add_text( 'reddit_title', __( 'Section title', 'mathilde' ), 'mathilde_reddit', __( 'From the Community', 'mathilde' ) );
	$add_text( 'reddit_sub', __( 'Subreddit (blank = first in plugin settings)', 'mathilde' ), 'mathilde_reddit', '' );
	$wp_customize->add_setting( 'mathilde_reddit_count', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'mathilde_reddit_count', array( 'label' => __( 'Number of posts', 'mathilde' ), 'section' => 'mathilde_reddit', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 12 ) ) );

	/* ---- AI Trust ---------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_aitrust', array( 'title' => __( 'Homepage — AI Trust', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'aitrust_enable', __( 'Show AI Trust section', 'mathilde' ), 'mathilde_aitrust', true );
	$add_text( 'aitrust_title', __( 'Section title', 'mathilde' ), 'mathilde_aitrust', __( 'AI Trust & Review', 'mathilde' ) );
	$ai_defaults = array(
		1 => array( __( 'AI-Supported Content', 'mathilde' ), __( 'We use AI tools to enhance research, writing and editing for accurate, helpful content.', 'mathilde' ) ),
		2 => array( __( 'Human-Curated', 'mathilde' ), __( 'Every article is carefully reviewed and refined to ensure quality and authenticity.', 'mathilde' ) ),
		3 => array( __( 'Transparent &amp; Honest', 'mathilde' ), __( 'We are committed to transparency, honesty and providing real value to our readers.', 'mathilde' ) ),
		4 => array( __( 'Your Trust Matters', 'mathilde' ), __( 'Your trust is our priority. We are always improving to bring you the best experience.', 'mathilde' ) ),
	);
	for ( $i = 1; $i <= 4; $i++ ) {
		$add_text( "ai_t{$i}_title", sprintf( __( 'Pillar %d — title', 'mathilde' ), $i ), 'mathilde_aitrust', $ai_defaults[ $i ][0] );
		$add_text( "ai_t{$i}_text", sprintf( __( 'Pillar %d — text', 'mathilde' ), $i ), 'mathilde_aitrust', $ai_defaults[ $i ][1], 'textarea' );
	}

	/* ---- FAQ --------------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_faq', array( 'title' => __( 'Homepage — FAQ', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'faq_enable', __( 'Show FAQ section', 'mathilde' ), 'mathilde_faq', true );
	$add_text( 'faq_title', __( 'Section title', 'mathilde' ), 'mathilde_faq', __( 'Frequently Asked Questions', 'mathilde' ) );
	$wp_customize->add_setting( 'mathilde_faq_items', array( 'default' => '', 'sanitize_callback' => 'wp_kses_post' ) );
	$wp_customize->add_control( 'mathilde_faq_items', array(
		'label'       => __( 'FAQ items (JSON)', 'mathilde' ),
		'description' => __( 'Array of {"q":"…","a":"…"}. Leave blank for defaults.', 'mathilde' ),
		'section'     => 'mathilde_faq',
		'type'        => 'textarea',
	) );
	$add_image( 'faq_image', __( 'FAQ image', 'mathilde' ), 'mathilde_faq' );

	/* ---- Newsletter -------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_newsletter', array( 'title' => __( 'Newsletter', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_text( 'newsletter_title', __( 'Title', 'mathilde' ), 'mathilde_newsletter', __( 'Join 25,000+ Readers', 'mathilde' ) );
	$add_text( 'newsletter_text', __( 'Text', 'mathilde' ), 'mathilde_newsletter', __( 'Get weekly fashion, beauty & lifestyle inspiration straight to your inbox.', 'mathilde' ), 'textarea' );
	$add_check( 'newsletter_footer_enable', __( 'Show newsletter band before footer', 'mathilde' ), 'mathilde_newsletter', true );

	/* ---- Instagram --------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_instagram', array( 'title' => __( 'Instagram', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'insta_enable', __( 'Show Instagram strip', 'mathilde' ), 'mathilde_instagram', true );
	$add_text( 'insta_handle', __( 'Handle (e.g. @name)', 'mathilde' ), 'mathilde_instagram' );
	$add_text( 'insta_url', __( 'Profile URL', 'mathilde' ), 'mathilde_instagram', '', 'url' );
	$add_text( 'insta_images', __( 'Image URLs — one per line', 'mathilde' ), 'mathilde_instagram', '', 'textarea' );

	/* ---- Sidebar ----------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_sidebar', array( 'title' => __( 'Sidebar (About card)', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_image( 'sidebar_avatar', __( 'Avatar image', 'mathilde' ), 'mathilde_sidebar' );
	$add_text( 'sidebar_about_name', __( 'Name', 'mathilde' ), 'mathilde_sidebar' );
	$add_text( 'sidebar_about_bio', __( 'Bio', 'mathilde' ), 'mathilde_sidebar', '', 'textarea' );

	/* ---- Social ------------------------------------------------------ */
	$wp_customize->add_section( 'mathilde_social', array( 'title' => __( 'Social Media', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_text( 'social_facebook', __( 'Facebook URL', 'mathilde' ), 'mathilde_social', '', 'url' );
	$add_text( 'social_instagram', __( 'Instagram URL', 'mathilde' ), 'mathilde_social', '', 'url' );
	$add_text( 'social_pinterest', __( 'Pinterest URL', 'mathilde' ), 'mathilde_social', '', 'url' );
	$add_text( 'social_tiktok', __( 'TikTok URL', 'mathilde' ), 'mathilde_social', '', 'url' );
	$add_text( 'social_email', __( 'Contact email', 'mathilde' ), 'mathilde_social' );

	/* ---- Footer ------------------------------------------------------ */
	$wp_customize->add_section( 'mathilde_footer', array( 'title' => __( 'Footer', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_text( 'footer_about', __( 'Footer about text', 'mathilde' ), 'mathilde_footer', __( 'Your destination for fashion, beauty, lifestyle, travel & business inspiration. Empowering you to live beautifully and intentionally.', 'mathilde' ), 'textarea' );
	$add_image( 'footer_image', __( 'Footer thumbnail image', 'mathilde' ), 'mathilde_footer' );

	/* ---- Dark mode --------------------------------------------------- */
	$wp_customize->add_section( 'mathilde_dark', array( 'title' => __( 'Dark Mode', 'mathilde' ), 'panel' => 'mathilde_panel' ) );
	$add_check( 'dark_mode_toggle', __( 'Show dark mode toggle in header', 'mathilde' ), 'mathilde_dark', true );

	/* Selective refresh for the brand subtitle / blog name. */
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.brand__title',
				'render_callback' => function () {
					return get_bloginfo( 'name' );
				},
			)
		);
	}
}
add_action( 'customize_register', 'mathilde_customize_register' );

/**
 * Live-preview JS for color/postMessage settings.
 */
function mathilde_customize_preview_js() {
	wp_enqueue_script(
		'mathilde-customize-preview',
		MATHILDE_URI . 'assets/js/customizer-preview.js',
		array( 'customize-preview' ),
		MATHILDE_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'mathilde_customize_preview_js' );
