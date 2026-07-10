<?php
/**
 * Widget areas — sidebar and footer columns.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register sidebars.
 */
function mathilde_widgets_init() {

	register_sidebar(
		array(
			'name'          => __( 'Blog Sidebar', 'mathilde' ),
			'id'            => 'sidebar-blog',
			'description'   => __( 'Appears beside posts and archives.', 'mathilde' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title m-eyebrow">',
			'after_title'   => '</h3>',
		)
	);

	$footer_cols = array(
		'footer-1' => __( 'Footer — Explore', 'mathilde' ),
		'footer-2' => __( 'Footer — Information', 'mathilde' ),
		'footer-3' => __( 'Footer — Legal', 'mathilde' ),
	);
	foreach ( $footer_cols as $id => $name ) {
		register_sidebar(
			array(
				'name'          => $name,
				'id'            => $id,
				'description'   => __( 'Footer widget column.', 'mathilde' ),
				'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="footer-widget__title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'mathilde_widgets_init' );
