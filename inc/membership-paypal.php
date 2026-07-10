<?php
/**
 * Membership — PayPal REST client + REST API endpoints + account creation.
 *
 * Flow (PayPal Orders v2, one-time capture):
 *   1. Browser asks our `create-order` endpoint for an order (server builds it
 *      with the *server-side* plan price — the client price is never trusted).
 *   2. Buyer approves in the PayPal popup.
 *   3. Browser calls our `capture-order` endpoint; the server captures, verifies
 *      status == COMPLETED and the amount/currency match the plan, guards against
 *      replay, then creates/upgrades the account and grants the role.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PayPal API base URL for the active mode.
 *
 * @return string
 */
function mathilde_pp_base() {
	$s = mathilde_membership_settings();
	return ( 'live' === $s['mode'] ) ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

/**
 * Fetch (and cache) a PayPal OAuth2 access token.
 *
 * @return string|WP_Error
 */
function mathilde_pp_token() {
	$s = mathilde_membership_settings();
	if ( empty( $s['client_id'] ) || empty( $s['secret'] ) ) {
		return new WP_Error( 'pp_config', __( 'PayPal is not configured.', 'mathilde' ) );
	}

	$cache_key = 'mathilde_pp_token_' . md5( $s['mode'] . $s['client_id'] );
	$cached    = get_transient( $cache_key );
	if ( $cached ) {
		return $cached;
	}

	$resp = wp_remote_post(
		mathilde_pp_base() . '/v1/oauth2/token',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $s['client_id'] . ':' . $s['secret'] ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => array( 'grant_type' => 'client_credentials' ),
		)
	);

	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $body['access_token'] ) ) {
		return new WP_Error( 'pp_token', __( 'Could not authenticate with PayPal.', 'mathilde' ), $body );
	}

	$ttl = isset( $body['expires_in'] ) ? max( 60, (int) $body['expires_in'] - 60 ) : 300;
	set_transient( $cache_key, $body['access_token'], $ttl );
	return $body['access_token'];
}

/**
 * Low-level authenticated PayPal request.
 *
 * @param string $method HTTP method.
 * @param string $path   Path beginning with /.
 * @param array  $body   JSON body (optional).
 * @return array|WP_Error Decoded JSON on success.
 */
function mathilde_pp_request( $method, $path, $body = null ) {
	$token = mathilde_pp_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$args = array(
		'method'  => $method,
		'timeout' => 25,
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$resp = wp_remote_request( mathilde_pp_base() . $path, $args );
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = wp_remote_retrieve_response_code( $resp );
	$data = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'pp_http', __( 'PayPal request failed.', 'mathilde' ), $data );
	}
	return is_array( $data ) ? $data : array();
}

/* =========================================================================
 *  REST routes
 * ========================================================================= */

add_action( 'rest_api_init', function () {
	register_rest_route(
		'mathilde/v1',
		'/check-availability',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'mathilde_rest_check_availability',
		)
	);
	register_rest_route(
		'mathilde/v1',
		'/create-order',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'mathilde_rest_create_order',
		)
	);
	register_rest_route(
		'mathilde/v1',
		'/capture-order',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'mathilde_rest_capture_order',
		)
	);
} );

/**
 * Lightweight CSRF check for the public endpoints.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function mathilde_rest_verify_nonce( $request ) {
	$nonce = $request->get_header( 'x_wp_nonce' );
	if ( ! $nonce ) {
		$nonce = $request->get_param( '_wpnonce' );
	}
	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * Check username/email availability (called as the form is filled).
 */
function mathilde_rest_check_availability( WP_REST_Request $request ) {
	$username = sanitize_user( (string) $request->get_param( 'username' ), true );
	$email    = sanitize_email( (string) $request->get_param( 'email' ) );

	return rest_ensure_response(
		array(
			'usernameAvailable' => $username ? ! username_exists( $username ) : null,
			'emailAvailable'    => ( $email && is_email( $email ) ) ? ! email_exists( $email ) : null,
			'emailValid'        => $email ? (bool) is_email( $email ) : null,
		)
	);
}

/**
 * Create a PayPal order for the selected plan.
 */
function mathilde_rest_create_order( WP_REST_Request $request ) {
	if ( ! mathilde_membership_active() || ! mathilde_membership_configured() ) {
		return new WP_Error( 'disabled', __( 'Memberships are not available right now.', 'mathilde' ), array( 'status' => 400 ) );
	}
	if ( ! mathilde_rest_verify_nonce( $request ) ) {
		return new WP_Error( 'bad_nonce', __( 'Security check failed. Please refresh and try again.', 'mathilde' ), array( 'status' => 403 ) );
	}

	$plan_key = sanitize_key( (string) $request->get_param( 'plan' ) );
	$plan     = mathilde_membership_plan( $plan_key );
	if ( ! $plan ) {
		return new WP_Error( 'bad_plan', __( 'Invalid plan selected.', 'mathilde' ), array( 'status' => 400 ) );
	}

	$s     = mathilde_membership_settings();
	$price = number_format( (float) $plan['price'], 2, '.', '' );

	$order = mathilde_pp_request(
		'POST',
		'/v2/checkout/orders',
		array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'description' => sprintf( '%s — %s membership (1 year)', get_bloginfo( 'name' ), $plan['label'] ),
					'custom_id'   => $plan_key,
					'amount'      => array(
						'currency_code' => $s['currency'],
						'value'         => $price,
					),
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
 * Capture the approved order, verify it, and provision the account.
 */
function mathilde_rest_capture_order( WP_REST_Request $request ) {
	if ( ! mathilde_membership_active() || ! mathilde_membership_configured() ) {
		return new WP_Error( 'disabled', __( 'Memberships are not available right now.', 'mathilde' ), array( 'status' => 400 ) );
	}
	if ( ! mathilde_rest_verify_nonce( $request ) ) {
		return new WP_Error( 'bad_nonce', __( 'Security check failed. Please refresh and try again.', 'mathilde' ), array( 'status' => 403 ) );
	}

	$order_id = sanitize_text_field( (string) $request->get_param( 'orderID' ) );
	$plan_key = sanitize_key( (string) $request->get_param( 'plan' ) );
	$plan     = mathilde_membership_plan( $plan_key );
	if ( ! $order_id || ! $plan ) {
		return new WP_Error( 'bad_request', __( 'Missing order or plan.', 'mathilde' ), array( 'status' => 400 ) );
	}

	// Capture the payment.
	$capture = mathilde_pp_request( 'POST', '/v2/checkout/orders/' . rawurlencode( $order_id ) . '/capture' );
	if ( is_wp_error( $capture ) ) {
		// It may already be captured — try a read before giving up.
		$capture = mathilde_pp_request( 'GET', '/v2/checkout/orders/' . rawurlencode( $order_id ) );
		if ( is_wp_error( $capture ) ) {
			return new WP_Error( 'pp_capture', __( 'We could not verify the payment with PayPal.', 'mathilde' ), array( 'status' => 502 ) );
		}
	}

	// --- Verify the capture rigorously ------------------------------------
	if ( empty( $capture['status'] ) || 'COMPLETED' !== $capture['status'] ) {
		return new WP_Error( 'not_completed', __( 'Payment was not completed.', 'mathilde' ), array( 'status' => 402 ) );
	}

	$unit = $capture['purchase_units'][0] ?? array();
	// Amount lives under captures for a capture response.
	$paid_amount   = null;
	$paid_currency = null;
	$txn_id        = $order_id;
	if ( ! empty( $unit['payments']['captures'][0] ) ) {
		$cap           = $unit['payments']['captures'][0];
		$paid_amount   = $cap['amount']['value'] ?? null;
		$paid_currency = $cap['amount']['currency_code'] ?? null;
		$txn_id        = $cap['id'] ?? $order_id;
		if ( isset( $cap['status'] ) && 'COMPLETED' !== $cap['status'] ) {
			return new WP_Error( 'not_completed', __( 'Payment was not completed.', 'mathilde' ), array( 'status' => 402 ) );
		}
	} elseif ( ! empty( $unit['amount'] ) ) {
		$paid_amount   = $unit['amount']['value'] ?? null;
		$paid_currency = $unit['amount']['currency_code'] ?? null;
	}

	$s             = mathilde_membership_settings();
	$expected      = number_format( (float) $plan['price'], 2, '.', '' );
	$paid_norm     = number_format( (float) $paid_amount, 2, '.', '' );
	if ( null === $paid_amount || $paid_norm !== $expected || strtoupper( (string) $paid_currency ) !== strtoupper( $s['currency'] ) ) {
		return new WP_Error( 'amount_mismatch', __( 'Payment amount did not match the selected plan.', 'mathilde' ), array( 'status' => 402 ) );
	}

	// --- Replay protection -------------------------------------------------
	$processed = get_option( 'mathilde_membership_txns', array() );
	$processed = is_array( $processed ) ? $processed : array();
	if ( in_array( $txn_id, $processed, true ) ) {
		return new WP_Error( 'duplicate', __( 'This payment has already been processed.', 'mathilde' ), array( 'status' => 409 ) );
	}

	// --- Resolve / create the account -------------------------------------
	$result = mathilde_membership_provision_account( $request, $plan_key, $txn_id );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	// Mark txn processed (only after successful provisioning).
	$processed[] = $txn_id;
	update_option( 'mathilde_membership_txns', array_slice( $processed, -2000 ), false );

	return rest_ensure_response( $result );
}

/**
 * Create or upgrade the user account for a verified payment.
 *
 * @param WP_REST_Request $request  Request (account fields).
 * @param string          $plan_key Plan key.
 * @param string          $txn_id   PayPal transaction id.
 * @return array|WP_Error
 */
function mathilde_membership_provision_account( $request, $plan_key, $txn_id ) {
	$s            = mathilde_membership_settings();
	$pending      = ! empty( $s['require_approval'] );
	$new_password = '';

	if ( is_user_logged_in() ) {
		// Renewal / upgrade for the current user.
		$user_id = get_current_user_id();
	} else {
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$name     = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$username = sanitize_user( (string) $request->get_param( 'username' ), true );
		$password = (string) $request->get_param( 'password' );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'bad_email', __( 'A valid email is required to create your account.', 'mathilde' ), array( 'status' => 400 ) );
		}

		$existing = email_exists( $email );
		if ( $existing ) {
			// Treat as a renewal of an existing account (do not reset password).
			$user_id = (int) $existing;
		} else {
			// Build a unique username.
			if ( ! $username || username_exists( $username ) ) {
				$base     = $username ? $username : current( explode( '@', $email ) );
				$base     = sanitize_user( $base, true );
				$username = $base;
				$i        = 1;
				while ( username_exists( $username ) ) {
					$username = $base . $i;
					$i++;
				}
			}
			if ( ! $password || strlen( $password ) < 6 ) {
				$password     = wp_generate_password( 12, true );
				$new_password = $password;
			}
			$user_id = wp_insert_user(
				array(
					'user_login'   => $username,
					'user_email'   => $email,
					'user_pass'    => $password,
					'display_name' => $name ? $name : $username,
					'first_name'   => $name,
					'role'         => 'subscriber',
				)
			);
			if ( is_wp_error( $user_id ) ) {
				return new WP_Error( 'user_create', __( 'We could not create your account. Please contact us — your payment succeeded.', 'mathilde' ), array( 'status' => 500 ) );
			}
		}
	}

	// Grant / extend the membership.
	mathilde_membership_grant( $user_id, $plan_key, $txn_id, $pending );
	mathilde_membership_email_welcome( $user_id, $new_password );

	// Auto-login newly provisioned users for a smooth hand-off.
	if ( ! is_user_logged_in() && ! $pending ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
	}

	$redirect = $s['redirect'] ? $s['redirect'] : admin_url( 'profile.php' );
	if ( $pending ) {
		$redirect = home_url( '/' );
	}

	return array(
		'success'  => true,
		'pending'  => (bool) $pending,
		'redirect' => esc_url_raw( $redirect ),
		'message'  => $pending
			? __( 'Payment received! Your account is awaiting approval — we’ll email you shortly.', 'mathilde' )
			: __( 'Welcome aboard! Your contributor account is ready.', 'mathilde' ),
	);
}
