<?php
/**
 * Ebooks — PayPal checkout, order records, secure tokenised downloads.
 *
 * Reuses the PayPal REST client from membership-paypal.php
 * (mathilde_pp_request / token).
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 *  REST routes
 * ========================================================================= */

add_action( 'rest_api_init', function () {
	register_rest_route(
		'mathilde/v1',
		'/create-ebook-order',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'mathilde_rest_create_ebook_order',
		)
	);
	register_rest_route(
		'mathilde/v1',
		'/capture-ebook-order',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'mathilde_rest_capture_ebook_order',
		)
	);
} );

/**
 * Create a PayPal order for an ebook.
 */
function mathilde_rest_create_ebook_order( WP_REST_Request $request ) {
	if ( ! mathilde_membership_configured() ) {
		return new WP_Error( 'disabled', __( 'Store is not available right now.', 'mathilde' ), array( 'status' => 400 ) );
	}
	if ( ! mathilde_rest_verify_nonce( $request ) ) {
		return new WP_Error( 'bad_nonce', __( 'Security check failed. Please refresh.', 'mathilde' ), array( 'status' => 403 ) );
	}

	$ebook_id = (int) $request->get_param( 'ebook_id' );
	if ( get_post_type( $ebook_id ) !== 'mathilde_ebook' || get_post_status( $ebook_id ) !== 'publish' ) {
		return new WP_Error( 'bad_ebook', __( 'Invalid product.', 'mathilde' ), array( 'status' => 400 ) );
	}
	$price = mathilde_ebook_price( $ebook_id );
	if ( ! $price ) {
		return new WP_Error( 'no_price', __( 'This product is not for sale.', 'mathilde' ), array( 'status' => 400 ) );
	}

	$s     = mathilde_membership_settings();
	$order = mathilde_pp_request(
		'POST',
		'/v2/checkout/orders',
		array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'description' => mb_substr( get_the_title( $ebook_id ), 0, 120 ),
					'custom_id'   => 'ebook-' . $ebook_id,
					'amount'      => array( 'currency_code' => $s['currency'], 'value' => $price ),
				),
			),
			'application_context' => array(
				'shipping_preference' => 'NO_SHIPPING',
				'user_action'         => 'PAY_NOW',
				'brand_name'          => get_bloginfo( 'name' ),
			),
		)
	);
	if ( is_wp_error( $order ) ) {
		return new WP_Error( 'pp_create', __( 'Could not start the PayPal checkout.', 'mathilde' ), array( 'status' => 502 ) );
	}
	return rest_ensure_response( array( 'id' => $order['id'] ) );
}

/**
 * Capture, verify, record the order and return a download URL.
 */
function mathilde_rest_capture_ebook_order( WP_REST_Request $request ) {
	if ( ! mathilde_membership_configured() ) {
		return new WP_Error( 'disabled', __( 'Store is not available right now.', 'mathilde' ), array( 'status' => 400 ) );
	}
	if ( ! mathilde_rest_verify_nonce( $request ) ) {
		return new WP_Error( 'bad_nonce', __( 'Security check failed. Please refresh.', 'mathilde' ), array( 'status' => 403 ) );
	}

	$order_id = sanitize_text_field( (string) $request->get_param( 'orderID' ) );
	$ebook_id = (int) $request->get_param( 'ebook_id' );
	if ( ! $order_id || get_post_type( $ebook_id ) !== 'mathilde_ebook' ) {
		return new WP_Error( 'bad_request', __( 'Missing order or product.', 'mathilde' ), array( 'status' => 400 ) );
	}
	$price = mathilde_ebook_price( $ebook_id );
	if ( ! $price ) {
		return new WP_Error( 'no_price', __( 'This product is not for sale.', 'mathilde' ), array( 'status' => 400 ) );
	}

	// Capture (fall back to read if already captured).
	$capture = mathilde_pp_request( 'POST', '/v2/checkout/orders/' . rawurlencode( $order_id ) . '/capture' );
	if ( is_wp_error( $capture ) ) {
		$capture = mathilde_pp_request( 'GET', '/v2/checkout/orders/' . rawurlencode( $order_id ) );
		if ( is_wp_error( $capture ) ) {
			return new WP_Error( 'pp_capture', __( 'We could not verify the payment.', 'mathilde' ), array( 'status' => 502 ) );
		}
	}
	if ( empty( $capture['status'] ) || 'COMPLETED' !== $capture['status'] ) {
		return new WP_Error( 'not_completed', __( 'Payment was not completed.', 'mathilde' ), array( 'status' => 402 ) );
	}

	// Verify amount + currency.
	$unit          = $capture['purchase_units'][0] ?? array();
	$paid_amount   = null;
	$paid_currency = null;
	$txn_id        = $order_id;
	if ( ! empty( $unit['payments']['captures'][0] ) ) {
		$cap           = $unit['payments']['captures'][0];
		$paid_amount   = $cap['amount']['value'] ?? null;
		$paid_currency = $cap['amount']['currency_code'] ?? null;
		$txn_id        = $cap['id'] ?? $order_id;
	} elseif ( ! empty( $unit['amount'] ) ) {
		$paid_amount   = $unit['amount']['value'] ?? null;
		$paid_currency = $unit['amount']['currency_code'] ?? null;
	}
	$s = mathilde_membership_settings();
	if ( null === $paid_amount || number_format( (float) $paid_amount, 2, '.', '' ) !== $price || strtoupper( (string) $paid_currency ) !== strtoupper( $s['currency'] ) ) {
		return new WP_Error( 'amount_mismatch', __( 'Payment amount did not match the product price.', 'mathilde' ), array( 'status' => 402 ) );
	}

	// Replay protection.
	$processed = get_option( 'mathilde_ebook_txns', array() );
	$processed = is_array( $processed ) ? $processed : array();
	if ( in_array( $txn_id, $processed, true ) ) {
		return new WP_Error( 'duplicate', __( 'This payment has already been processed.', 'mathilde' ), array( 'status' => 409 ) );
	}

	// Buyer details.
	$email = sanitize_email( (string) $request->get_param( 'email' ) );
	$name  = sanitize_text_field( (string) $request->get_param( 'name' ) );
	if ( is_user_logged_in() ) {
		$u     = wp_get_current_user();
		$email = $email ? $email : $u->user_email;
		$name  = $name ? $name : $u->display_name;
	}
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'bad_email', __( 'A valid email is required to deliver your download.', 'mathilde' ), array( 'status' => 400 ) );
	}

	// Create the order record.
	$order_post = wp_insert_post(
		array(
			'post_type'   => 'mathilde_order',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%s — %s', get_the_title( $ebook_id ), $email ),
		),
		true
	);
	if ( is_wp_error( $order_post ) ) {
		return new WP_Error( 'order_fail', __( 'We could not record your order. Please contact us — your payment succeeded.', 'mathilde' ), array( 'status' => 500 ) );
	}

	$expires = time() + 30 * DAY_IN_SECONDS;
	update_post_meta( $order_post, '_ebook_id', $ebook_id );
	update_post_meta( $order_post, '_buyer_email', $email );
	update_post_meta( $order_post, '_buyer_name', $name );
	update_post_meta( $order_post, '_amount', $price );
	update_post_meta( $order_post, '_currency', $s['currency'] );
	update_post_meta( $order_post, '_txn_id', $txn_id );
	update_post_meta( $order_post, '_user_id', get_current_user_id() );
	update_post_meta( $order_post, '_downloads', 0 );
	update_post_meta( $order_post, '_max_downloads', 5 );
	update_post_meta( $order_post, '_expires', $expires );

	$processed[] = $txn_id;
	update_option( 'mathilde_ebook_txns', array_slice( $processed, -5000 ), false );

	// Attach to the user account for re-downloads.
	if ( is_user_logged_in() ) {
		$purchases              = get_user_meta( get_current_user_id(), 'mathilde_ebook_purchases', true );
		$purchases              = is_array( $purchases ) ? $purchases : array();
		$purchases[ $ebook_id ] = $order_post;
		update_user_meta( get_current_user_id(), 'mathilde_ebook_purchases', $purchases );
	}

	$url = mathilde_ebook_download_url( $order_post );
	mathilde_ebook_email_delivery( $order_post );

	do_action( 'mathilde_ebook_purchased', $order_post, $ebook_id, $email );

	return rest_ensure_response(
		array(
			'success'  => true,
			'download' => esc_url_raw( $url ),
			'message'  => __( 'Thank you! Your download is ready — we’ve also emailed the link.', 'mathilde' ),
		)
	);
}

/* =========================================================================
 *  Tokenised download links + handler
 * ========================================================================= */

/**
 * HMAC token for an order's download link.
 *
 * @param int $order_id Order ID.
 * @param int $expires  Expiry timestamp.
 * @return string
 */
function mathilde_ebook_token( $order_id, $expires ) {
	return hash_hmac( 'sha256', $order_id . '|' . $expires, wp_salt( 'auth' ) );
}

/**
 * Build a secure download URL for an order.
 *
 * @param int $order_id Order ID.
 * @return string|false
 */
function mathilde_ebook_download_url( $order_id ) {
	if ( get_post_type( $order_id ) !== 'mathilde_order' ) {
		return false;
	}
	$expires = (int) get_post_meta( $order_id, '_expires', true );
	return add_query_arg(
		array(
			'mathilde_dl' => $order_id,
			'e'           => $expires,
			't'           => mathilde_ebook_token( $order_id, $expires ),
		),
		home_url( '/' )
	);
}

/**
 * Handle a download request: validate token / ownership, stream the file.
 */
function mathilde_ebook_download_handler() {
	if ( empty( $_GET['mathilde_dl'] ) ) {
		return;
	}
	$order_id = (int) $_GET['mathilde_dl'];
	if ( get_post_type( $order_id ) !== 'mathilde_order' ) {
		wp_die( esc_html__( 'Invalid download.', 'mathilde' ), '', array( 'response' => 404 ) );
	}

	$expires   = (int) get_post_meta( $order_id, '_expires', true );
	$token_ok  = isset( $_GET['t'] ) && hash_equals( mathilde_ebook_token( $order_id, $expires ), (string) $_GET['t'] ) && (int) ( $_GET['e'] ?? 0 ) === $expires;
	$owner_ok  = is_user_logged_in() && (int) get_post_meta( $order_id, '_user_id', true ) === get_current_user_id();

	if ( ! $token_ok && ! $owner_ok ) {
		wp_die( esc_html__( 'This download link is invalid.', 'mathilde' ), '', array( 'response' => 403 ) );
	}
	if ( $expires && time() > $expires ) {
		wp_die( esc_html__( 'This download link has expired. Please contact us to renew it.', 'mathilde' ), '', array( 'response' => 410 ) );
	}

	$downloads = (int) get_post_meta( $order_id, '_downloads', true );
	$max       = (int) get_post_meta( $order_id, '_max_downloads', true );
	if ( $max && $downloads >= $max && ! $owner_ok ) {
		wp_die( esc_html__( 'This download link has reached its limit. Please contact us.', 'mathilde' ), '', array( 'response' => 429 ) );
	}

	$ebook_id = (int) get_post_meta( $order_id, '_ebook_id', true );
	$filename = get_post_meta( $ebook_id, '_mathilde_ebook_file', true );
	$path     = mathilde_ebooks_dir() . $filename;
	if ( ! $filename || ! file_exists( $path ) ) {
		wp_die( esc_html__( 'The file is no longer available. Please contact us.', 'mathilde' ), '', array( 'response' => 404 ) );
	}

	update_post_meta( $order_id, '_downloads', $downloads + 1 );

	$nice = sanitize_file_name( get_the_title( $ebook_id ) . '.' . pathinfo( $path, PATHINFO_EXTENSION ) );
	nocache_headers();
	header( 'Content-Type: ' . ( wp_check_filetype( $path )['type'] ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . $nice . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	readfile( $path );
	exit;
}
add_action( 'init', 'mathilde_ebook_download_handler' );

/* =========================================================================
 *  Email
 * ========================================================================= */

/**
 * Email the buyer their download link.
 *
 * @param int $order_id Order ID.
 */
function mathilde_ebook_email_delivery( $order_id ) {
	$email    = get_post_meta( $order_id, '_buyer_email', true );
	$name     = get_post_meta( $order_id, '_buyer_name', true );
	$ebook_id = (int) get_post_meta( $order_id, '_ebook_id', true );
	$url      = mathilde_ebook_download_url( $order_id );
	if ( ! is_email( $email ) || ! $url ) {
		return;
	}
	$expires = (int) get_post_meta( $order_id, '_expires', true );

	$lines   = array();
	$lines[] = sprintf( __( 'Hi %s,', 'mathilde' ), $name ? $name : '' );
	$lines[] = '';
	$lines[] = sprintf( __( 'Thank you for your purchase of “%s”.', 'mathilde' ), get_the_title( $ebook_id ) );
	$lines[] = '';
	$lines[] = __( 'Download your copy here:', 'mathilde' );
	$lines[] = $url;
	$lines[] = '';
	$lines[] = sprintf( __( 'This link is valid until %s.', 'mathilde' ), date_i18n( get_option( 'date_format' ), $expires ) );
	$lines[] = '';
	$lines[] = get_bloginfo( 'name' );

	wp_mail(
		$email,
		sprintf( __( '[%s] Your download is ready', 'mathilde' ), get_bloginfo( 'name' ) ),
		implode( "\n", $lines )
	);
}
