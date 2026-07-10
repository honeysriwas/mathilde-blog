<?php
/**
 * AJAX endpoints — load more posts, newsletter signup, live search.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verify the front-end nonce or die.
 */
function mathilde_verify_ajax() {
	check_ajax_referer( 'mathilde_nonce', 'nonce' );
}

/**
 * Load more posts for category archives / homepage sections.
 */
function mathilde_ajax_load_more() {
	mathilde_verify_ajax();

	$paged    = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
	$cat      = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
	$orderby  = isset( $_POST['orderby'] ) ? sanitize_key( $_POST['orderby'] ) : 'date';
	$per_page = isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : (int) get_option( 'posts_per_page' );

	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'ignore_sticky_posts' => true,
	);

	if ( $cat ) {
		$args['category_name'] = $cat;
	}

	switch ( $orderby ) {
		case 'oldest':
			$args['order']   = 'ASC';
			$args['orderby'] = 'date';
			break;
		case 'popular':
			$args['orderby']  = 'comment_count';
			$args['meta_key'] = ''; // could swap for a views meta key.
			break;
		case 'title':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	$q = new WP_Query( $args );

	ob_start();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'archive' ) );
		}
	}
	wp_reset_postdata();
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'     => $html,
			'max_page' => (int) $q->max_num_pages,
			'page'     => $paged,
		)
	);
}
add_action( 'wp_ajax_mathilde_load_more', 'mathilde_ajax_load_more' );
add_action( 'wp_ajax_nopriv_mathilde_load_more', 'mathilde_ajax_load_more' );

/**
 * Newsletter signup. Stores the email as a private CPT-free option list and
 * fires an action other plugins (Mailchimp etc.) can hook into.
 */
function mathilde_ajax_newsletter() {
	mathilde_verify_ajax();

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'mathilde' ) ) );
	}

	$list = get_option( 'mathilde_newsletter_subscribers', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	if ( ! in_array( $email, $list, true ) ) {
		$list[] = $email;
		update_option( 'mathilde_newsletter_subscribers', $list, false );
	}

	/**
	 * Hook for ESP integrations.
	 *
	 * @param string $email Subscriber email.
	 */
	do_action( 'mathilde_newsletter_signup', $email );

	wp_send_json_success( array( 'message' => __( 'Thank you — you are subscribed!', 'mathilde' ) ) );
}
add_action( 'wp_ajax_mathilde_newsletter', 'mathilde_ajax_newsletter' );
add_action( 'wp_ajax_nopriv_mathilde_newsletter', 'mathilde_ajax_newsletter' );

/**
 * Live search suggestions for the header search overlay.
 */
function mathilde_ajax_live_search() {
	mathilde_verify_ajax();

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	if ( strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			's'                   => $term,
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	$results = array();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$cat       = mathilde_primary_category();
			$results[] = array(
				'title'    => get_the_title(),
				'url'      => get_permalink(),
				'thumb'    => get_the_post_thumbnail_url( get_the_ID(), 'mathilde-thumb' ),
				'category' => $cat ? $cat->name : '',
			);
		}
	}
	wp_reset_postdata();

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_mathilde_live_search', 'mathilde_ajax_live_search' );
add_action( 'wp_ajax_nopriv_mathilde_live_search', 'mathilde_ajax_live_search' );
