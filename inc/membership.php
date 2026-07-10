<?php
/**
 * Membership — paid Author/Editor contributor accounts.
 *
 * Visitors pay a one-time yearly fee via PayPal and receive an Author or
 * Editor account that auto-downgrades to Subscriber when the term expires.
 *
 * This file holds the core: settings model, plan config, user-meta helpers,
 * the expiry cron, asset enqueue, and the front-end shortcode. PayPal/REST
 * lives in membership-paypal.php; admin UI in membership-admin.php.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MATHILDE_MEMBERSHIP_OPTION = 'mathilde_membership';

/**
 * Default settings, merged over the stored option.
 *
 * @return array
 */
function mathilde_membership_defaults() {
	return array(
		'enabled'          => 1,
		'mode'             => 'sandbox',   // sandbox | live
		'client_id'        => '',
		'secret'           => '',
		'currency'         => 'USD',
		'expiry_days'      => 365,
		'require_approval' => 0,
		'redirect'         => '',          // URL after success; default = profile.
		'intro_title'      => __( 'Become a Contributor', 'mathilde' ),
		'intro_text'       => __( 'Join our editorial team. Pay once for a full year of publishing access on the blog.', 'mathilde' ),
		'plans'            => array(
			'author' => array(
				'enabled'  => 1,
				'role'     => 'author',
				'label'    => __( 'Author', 'mathilde' ),
				'price'    => '49.00',
				'tagline'  => __( 'Publish your own stories', 'mathilde' ),
				'features' => "Publish & manage your own articles\nPersonal author profile & bio page\nUpload your own images\nAuthor dashboard access\n1 full year of access",
				'featured' => 0,
			),
			'editor' => array(
				'enabled'  => 1,
				'role'     => 'editor',
				'label'    => __( 'Editor', 'mathilde' ),
				'price'    => '99.00',
				'tagline'  => __( 'Shape the whole publication', 'mathilde' ),
				'features' => "Everything in Author\nEdit & manage all posts and pages\nModerate comments\nManage categories & tags\n1 full year of access",
				'featured' => 1,
			),
		),
	);
}

/**
 * Get merged membership settings.
 *
 * @return array
 */
function mathilde_membership_settings() {
	$stored   = get_option( MATHILDE_MEMBERSHIP_OPTION, array() );
	$stored   = is_array( $stored ) ? $stored : array();
	$defaults = mathilde_membership_defaults();

	$merged          = array_merge( $defaults, $stored );
	// Deep-merge plans so partial admin saves keep default keys.
	$merged['plans'] = array();
	foreach ( $defaults['plans'] as $key => $plan ) {
		$merged['plans'][ $key ] = array_merge( $plan, ( $stored['plans'][ $key ] ?? array() ) );
	}
	return $merged;
}

/**
 * Whether the membership feature is usable on the front end.
 *
 * @return bool
 */
function mathilde_membership_active() {
	$s = mathilde_membership_settings();
	return ! empty( $s['enabled'] );
}

/**
 * Whether PayPal credentials are configured.
 *
 * @return bool
 */
function mathilde_membership_configured() {
	$s = mathilde_membership_settings();
	return ! empty( $s['client_id'] ) && ! empty( $s['secret'] );
}

/**
 * Return a single enabled plan by key, or null.
 *
 * @param string $key Plan key.
 * @return array|null
 */
function mathilde_membership_plan( $key ) {
	$s = mathilde_membership_settings();
	if ( isset( $s['plans'][ $key ] ) && ! empty( $s['plans'][ $key ]['enabled'] ) ) {
		return $s['plans'][ $key ];
	}
	return null;
}

/**
 * Map a plan key to a safe WP role. Hard-allowlist to prevent escalation.
 *
 * @param string $key Plan key.
 * @return string|null 'author' | 'editor' | null
 */
function mathilde_membership_role_for_plan( $key ) {
	$plan = mathilde_membership_plan( $key );
	if ( ! $plan ) {
		return null;
	}
	$role = $plan['role'];
	return in_array( $role, array( 'author', 'editor' ), true ) ? $role : null;
}

/**
 * Format a price with a currency symbol for common currencies.
 *
 * @param string $amount   Numeric amount.
 * @param string $currency 3-letter code.
 * @return string
 */
function mathilde_membership_format_price( $amount, $currency = 'USD' ) {
	$symbols = array( 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AUD' => 'A$', 'CAD' => 'C$', 'INR' => '₹', 'JPY' => '¥' );
	$amount  = (float) $amount;
	$display = ( floor( $amount ) === $amount ) ? number_format( $amount, 0 ) : number_format( $amount, 2 );
	$sym     = $symbols[ strtoupper( $currency ) ] ?? '';
	return $sym ? $sym . $display : $display . ' ' . strtoupper( $currency );
}

/* =========================================================================
 *  User membership state helpers
 * ========================================================================= */

/**
 * Record an active membership on a user.
 *
 * @param int    $user_id User ID.
 * @param string $plan_key Plan key.
 * @param string $txn_id   PayPal capture id.
 * @param bool   $pending  Whether this is awaiting admin approval.
 */
function mathilde_membership_grant( $user_id, $plan_key, $txn_id, $pending = false ) {
	$s    = mathilde_membership_settings();
	$days = max( 1, (int) $s['expiry_days'] );

	// Extend from the later of "now" or an existing future expiry (renewals).
	$current = (int) get_user_meta( $user_id, 'mathilde_membership_expires', true );
	$base    = ( $current > time() ) ? $current : time();
	$expires = $base + ( $days * DAY_IN_SECONDS );

	update_user_meta( $user_id, 'mathilde_membership_plan', sanitize_key( $plan_key ) );
	update_user_meta( $user_id, 'mathilde_membership_expires', $expires );
	update_user_meta( $user_id, 'mathilde_membership_started', time() );
	update_user_meta( $user_id, 'mathilde_membership_txn', sanitize_text_field( $txn_id ) );

	if ( $pending ) {
		update_user_meta( $user_id, 'mathilde_membership_status', 'pending' );
		// Hold privileges until approval.
		$u = new WP_User( $user_id );
		$u->set_role( 'subscriber' );
	} else {
		update_user_meta( $user_id, 'mathilde_membership_status', 'active' );
		$role = mathilde_membership_role_for_plan( $plan_key );
		if ( $role ) {
			$u = new WP_User( $user_id );
			$u->set_role( $role );
		}
	}

	/**
	 * Fires after a membership is granted/renewed.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $plan_key Plan key.
	 * @param int    $expires  Expiry timestamp.
	 * @param bool   $pending  Pending approval.
	 */
	do_action( 'mathilde_membership_granted', $user_id, $plan_key, $expires, $pending );
}

/**
 * Approve a pending member (admin action) — applies their paid role.
 *
 * @param int $user_id User ID.
 */
function mathilde_membership_approve( $user_id ) {
	$plan_key = get_user_meta( $user_id, 'mathilde_membership_plan', true );
	$role     = mathilde_membership_role_for_plan( $plan_key );
	if ( $role ) {
		( new WP_User( $user_id ) )->set_role( $role );
		update_user_meta( $user_id, 'mathilde_membership_status', 'active' );
		do_action( 'mathilde_membership_approved', $user_id, $plan_key );
	}
}

/**
 * Expire a member now — downgrade to subscriber.
 *
 * @param int $user_id User ID.
 */
function mathilde_membership_expire( $user_id ) {
	( new WP_User( $user_id ) )->set_role( 'subscriber' );
	update_user_meta( $user_id, 'mathilde_membership_status', 'expired' );
	do_action( 'mathilde_membership_expired', $user_id );
}

/**
 * Whether a user currently has an active, unexpired membership.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function mathilde_membership_is_active( $user_id ) {
	if ( 'active' !== get_user_meta( $user_id, 'mathilde_membership_status', true ) ) {
		return false;
	}
	$exp = (int) get_user_meta( $user_id, 'mathilde_membership_expires', true );
	return $exp > time();
}

/* =========================================================================
 *  Expiry cron
 * ========================================================================= */

/**
 * Schedule the daily expiry sweep when the theme is activated.
 */
function mathilde_membership_schedule_cron() {
	if ( ! wp_next_scheduled( 'mathilde_membership_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mathilde_membership_daily' );
	}
}
add_action( 'after_switch_theme', 'mathilde_membership_schedule_cron' );
add_action( 'init', 'mathilde_membership_schedule_cron' ); // Self-heal if missing.

/**
 * Clear the cron when the theme is switched away.
 */
function mathilde_membership_unschedule_cron() {
	$ts = wp_next_scheduled( 'mathilde_membership_daily' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'mathilde_membership_daily' );
	}
}
add_action( 'switch_theme', 'mathilde_membership_unschedule_cron' );

/**
 * Downgrade members whose term has lapsed.
 */
function mathilde_membership_check_expiries() {
	$users = get_users(
		array(
			'meta_key'   => 'mathilde_membership_status',
			'meta_value' => 'active',
			'fields'     => array( 'ID' ),
			'number'     => 500,
		)
	);
	foreach ( $users as $u ) {
		$exp = (int) get_user_meta( $u->ID, 'mathilde_membership_expires', true );
		if ( $exp && $exp < time() ) {
			mathilde_membership_expire( $u->ID );
			mathilde_membership_email_expired( $u->ID );
		}
	}
}
add_action( 'mathilde_membership_daily', 'mathilde_membership_check_expiries' );

/* =========================================================================
 *  Emails
 * ========================================================================= */

/**
 * Welcome email with login details after a successful purchase.
 *
 * @param int    $user_id  User ID.
 * @param string $password Plain password (only when auto-generated/new).
 */
function mathilde_membership_email_welcome( $user_id, $password = '' ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$plan    = get_user_meta( $user_id, 'mathilde_membership_plan', true );
	$expires = (int) get_user_meta( $user_id, 'mathilde_membership_expires', true );
	$login   = wp_login_url( admin_url() );

	$lines   = array();
	$lines[] = sprintf( __( 'Hi %s,', 'mathilde' ), $user->display_name );
	$lines[] = '';
	$lines[] = sprintf( __( 'Welcome to the team! Your %s membership is now active.', 'mathilde' ), ucfirst( $plan ) );
	$lines[] = '';
	$lines[] = __( 'Username:', 'mathilde' ) . ' ' . $user->user_login;
	if ( $password ) {
		$lines[] = __( 'Password:', 'mathilde' ) . ' ' . $password;
	}
	$lines[] = __( 'Sign in:', 'mathilde' ) . ' ' . $login;
	$lines[] = '';
	$lines[] = sprintf( __( 'Your access is valid until %s.', 'mathilde' ), date_i18n( get_option( 'date_format' ), $expires ) );
	$lines[] = '';
	$lines[] = get_bloginfo( 'name' );

	wp_mail(
		$user->user_email,
		sprintf( __( '[%s] Your contributor account is ready', 'mathilde' ), get_bloginfo( 'name' ) ),
		implode( "\n", $lines )
	);
}

/**
 * Notify a user their membership lapsed.
 *
 * @param int $user_id User ID.
 */
function mathilde_membership_email_expired( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$renew = mathilde_membership_page_url();
	wp_mail(
		$user->user_email,
		sprintf( __( '[%s] Your contributor membership has expired', 'mathilde' ), get_bloginfo( 'name' ) ),
		sprintf(
			/* translators: 1: name, 2: renew url */
			__( "Hi %1\$s,\n\nYour contributor membership has expired and your account has been moved to a reader account. Renew anytime to start publishing again:\n%2\$s\n\nThank you!", 'mathilde' ),
			$user->display_name,
			$renew
		)
	);
}

/* =========================================================================
 *  Front-end: shortcode + assets
 * ========================================================================= */

/**
 * Find the URL of the page using the Membership template or shortcode.
 * Cached in an option so we don't query on every request.
 *
 * @return string
 */
function mathilde_membership_page_url() {
	$cached = get_option( 'mathilde_membership_page_url' );
	if ( $cached ) {
		return $cached;
	}
	$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/membership.php' ) );
	if ( $pages ) {
		$url = get_permalink( $pages[0]->ID );
		update_option( 'mathilde_membership_page_url', $url, false );
		return $url;
	}
	return home_url( '/' );
}

/**
 * `[mathilde_membership]` shortcode → renders the pricing + signup UI.
 *
 * @return string
 */
function mathilde_membership_shortcode() {
	ob_start();
	mathilde_membership_enqueue();
	get_template_part( 'template-parts/membership/pricing' );
	return ob_get_clean();
}
add_shortcode( 'mathilde_membership', 'mathilde_membership_shortcode' );

/**
 * Enqueue the PayPal SDK + membership script. Safe to call multiple times.
 */
function mathilde_membership_enqueue() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$s = mathilde_membership_settings();

	if ( mathilde_membership_configured() ) {
		$sdk = add_query_arg(
			array(
				'client-id' => rawurlencode( $s['client_id'] ),
				'currency'  => rawurlencode( $s['currency'] ),
				'intent'    => 'capture',
				'components'=> 'buttons',
			),
			'https://www.paypal.com/sdk/js'
		);
		wp_enqueue_script( 'paypal-sdk', $sdk, array(), null, true );
	}

	wp_enqueue_script(
		'mathilde-membership',
		MATHILDE_URI . 'assets/js/membership.js',
		array(),
		MATHILDE_VERSION,
		true
	);

	$plans = array();
	foreach ( $s['plans'] as $key => $plan ) {
		if ( ! empty( $plan['enabled'] ) ) {
			$plans[ $key ] = array(
				'label' => $plan['label'],
				'price' => $plan['price'],
				'role'  => $plan['role'],
			);
		}
	}

	wp_localize_script(
		'mathilde-membership',
		'MathildeMembership',
		array(
			'restUrl'    => esc_url_raw( rest_url( 'mathilde/v1/' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'currency'   => $s['currency'],
			'plans'      => $plans,
			'configured' => mathilde_membership_configured(),
			'loggedIn'   => is_user_logged_in(),
			'i18n'       => array(
				'fillFields' => __( 'Please complete the account fields before paying.', 'mathilde' ),
				'invalidEmail' => __( 'Please enter a valid email address.', 'mathilde' ),
				'processing' => __( 'Verifying your payment…', 'mathilde' ),
				'success'    => __( 'Payment complete! Setting up your account…', 'mathilde' ),
				'error'      => __( 'Something went wrong. If you were charged, please contact us.', 'mathilde' ),
			),
		)
	);
}

/**
 * Auto-enqueue assets when the membership page template is in use.
 */
function mathilde_membership_maybe_enqueue() {
	if ( is_page_template( 'page-templates/membership.php' ) ) {
		mathilde_membership_enqueue();
	}
}
add_action( 'wp_enqueue_scripts', 'mathilde_membership_maybe_enqueue', 20 );
