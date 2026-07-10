<?php
/**
 * Membership — admin settings page + members management.
 *
 * Settings → Membership: PayPal credentials, plan prices, options, and a list
 * of paying members with extend / expire / approve actions.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the settings page.
 */
function mathilde_membership_admin_menu() {
	add_users_page(
		__( 'Membership', 'mathilde' ),
		__( 'Membership', 'mathilde' ),
		'manage_options',
		'mathilde-membership',
		'mathilde_membership_admin_page'
	);
}
add_action( 'admin_menu', 'mathilde_membership_admin_menu' );

/**
 * Register the setting + sanitizer.
 */
function mathilde_membership_register_setting() {
	register_setting(
		'mathilde_membership_group',
		MATHILDE_MEMBERSHIP_OPTION,
		array( 'sanitize_callback' => 'mathilde_membership_sanitize' )
	);
}
add_action( 'admin_init', 'mathilde_membership_register_setting' );

/**
 * Sanitize the settings array.
 *
 * @param array $input Raw input.
 * @return array
 */
function mathilde_membership_sanitize( $input ) {
	$out = mathilde_membership_settings();

	$out['enabled']          = empty( $input['enabled'] ) ? 0 : 1;
	$out['mode']             = ( isset( $input['mode'] ) && 'live' === $input['mode'] ) ? 'live' : 'sandbox';
	$out['client_id']        = isset( $input['client_id'] ) ? sanitize_text_field( $input['client_id'] ) : '';
	$out['secret']           = isset( $input['secret'] ) ? sanitize_text_field( $input['secret'] ) : '';
	$out['currency']         = isset( $input['currency'] ) ? strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $input['currency'] ), 0, 3 ) ) : 'USD';
	$out['expiry_days']      = max( 1, (int) ( $input['expiry_days'] ?? 365 ) );
	$out['require_approval'] = empty( $input['require_approval'] ) ? 0 : 1;
	$out['redirect']         = isset( $input['redirect'] ) ? esc_url_raw( $input['redirect'] ) : '';
	$out['intro_title']      = isset( $input['intro_title'] ) ? sanitize_text_field( $input['intro_title'] ) : '';
	$out['intro_text']       = isset( $input['intro_text'] ) ? sanitize_textarea_field( $input['intro_text'] ) : '';

	foreach ( array( 'author', 'editor' ) as $key ) {
		$p = $input['plans'][ $key ] ?? array();
		$out['plans'][ $key ]['enabled']  = empty( $p['enabled'] ) ? 0 : 1;
		$out['plans'][ $key ]['label']    = isset( $p['label'] ) ? sanitize_text_field( $p['label'] ) : ucfirst( $key );
		$out['plans'][ $key ]['price']    = number_format( (float) ( $p['price'] ?? 0 ), 2, '.', '' );
		$out['plans'][ $key ]['tagline']  = isset( $p['tagline'] ) ? sanitize_text_field( $p['tagline'] ) : '';
		$out['plans'][ $key ]['features'] = isset( $p['features'] ) ? sanitize_textarea_field( $p['features'] ) : '';
		$out['plans'][ $key ]['featured'] = empty( $p['featured'] ) ? 0 : 1;
		// Role is fixed by key (security): author plan => author, editor => editor.
		$out['plans'][ $key ]['role']     = $key;
	}

	return $out;
}

/**
 * Handle member row actions (approve / extend / expire) posted from the page.
 */
function mathilde_membership_handle_actions() {
	if ( empty( $_POST['mathilde_member_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'mathilde_member_action' );

	$user_id = (int) ( $_POST['user_id'] ?? 0 );
	$action  = sanitize_key( $_POST['mathilde_member_action'] );
	if ( ! $user_id ) {
		return;
	}

	switch ( $action ) {
		case 'approve':
			mathilde_membership_approve( $user_id );
			break;
		case 'expire':
			mathilde_membership_expire( $user_id );
			break;
		case 'extend':
			$plan = get_user_meta( $user_id, 'mathilde_membership_plan', true );
			mathilde_membership_grant( $user_id, $plan ? $plan : 'author', 'manual-extend-' . time() );
			break;
	}
	add_settings_error( 'mathilde-membership', 'done', __( 'Member updated.', 'mathilde' ), 'updated' );
}
add_action( 'admin_init', 'mathilde_membership_handle_actions' );

/**
 * Render the settings + members page.
 */
function mathilde_membership_admin_page() {
	$s = mathilde_membership_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Membership', 'mathilde' ); ?></h1>
		<?php settings_errors( 'mathilde-membership' ); ?>

		<?php if ( ! mathilde_membership_configured() ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'Add your PayPal API credentials below to start accepting payments. Until then the signup page shows a “coming soon” notice.', 'mathilde' ); ?>
			</p></div>
		<?php endif; ?>

		<div class="notice notice-info inline"><p>
			<strong><?php esc_html_e( 'Security tip:', 'mathilde' ); ?></strong>
			<?php esc_html_e( 'The Editor role can edit and delete everyone’s content. Offer the Author plan to most contributors and reserve Editor for people you trust. Consider enabling “require approval”.', 'mathilde' ); ?>
		</p></div>

		<form method="post" action="options.php">
			<?php settings_fields( 'mathilde_membership_group' ); ?>
			<?php $o = MATHILDE_MEMBERSHIP_OPTION; ?>

			<h2 class="title"><?php esc_html_e( 'General', 'mathilde' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable memberships', 'mathilde' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?>> <?php esc_html_e( 'Allow visitors to buy contributor access', 'mathilde' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'PayPal mode', 'mathilde' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $o ); ?>[mode]">
							<option value="sandbox" <?php selected( $s['mode'], 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (testing)', 'mathilde' ); ?></option>
							<option value="live" <?php selected( $s['mode'], 'live' ); ?>><?php esc_html_e( 'Live', 'mathilde' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="m_cid"><?php esc_html_e( 'PayPal Client ID', 'mathilde' ); ?></label></th>
					<td><input type="text" id="m_cid" class="regular-text" name="<?php echo esc_attr( $o ); ?>[client_id]" value="<?php echo esc_attr( $s['client_id'] ); ?>" autocomplete="off"></td>
				</tr>
				<tr>
					<th scope="row"><label for="m_sec"><?php esc_html_e( 'PayPal Secret', 'mathilde' ); ?></label></th>
					<td>
						<input type="password" id="m_sec" class="regular-text" name="<?php echo esc_attr( $o ); ?>[secret]" value="<?php echo esc_attr( $s['secret'] ); ?>" autocomplete="off">
						<p class="description"><?php esc_html_e( 'From your PayPal Developer dashboard → Apps & Credentials. Match the mode (sandbox vs live).', 'mathilde' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Currency', 'mathilde' ); ?></th>
					<td><input type="text" size="4" maxlength="3" name="<?php echo esc_attr( $o ); ?>[currency]" value="<?php echo esc_attr( $s['currency'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Access length (days)', 'mathilde' ); ?></th>
					<td><input type="number" min="1" name="<?php echo esc_attr( $o ); ?>[expiry_days]" value="<?php echo esc_attr( $s['expiry_days'] ); ?>"> <span class="description"><?php esc_html_e( '365 = one year', 'mathilde' ); ?></span></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Require admin approval', 'mathilde' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[require_approval]" value="1" <?php checked( $s['require_approval'] ); ?>> <?php esc_html_e( 'Hold the role until you approve each paid member', 'mathilde' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Redirect after signup', 'mathilde' ); ?></th>
					<td><input type="url" class="regular-text" name="<?php echo esc_attr( $o ); ?>[redirect]" value="<?php echo esc_attr( $s['redirect'] ); ?>" placeholder="<?php echo esc_attr( admin_url( 'profile.php' ) ); ?>"></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Signup page copy', 'mathilde' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Heading', 'mathilde' ); ?></th>
					<td><input type="text" class="large-text" name="<?php echo esc_attr( $o ); ?>[intro_title]" value="<?php echo esc_attr( $s['intro_title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Intro text', 'mathilde' ); ?></th>
					<td><textarea class="large-text" rows="2" name="<?php echo esc_attr( $o ); ?>[intro_text]"><?php echo esc_textarea( $s['intro_text'] ); ?></textarea></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Plans', 'mathilde' ); ?></h2>
			<?php foreach ( array( 'author', 'editor' ) as $key ) :
				$p = $s['plans'][ $key ]; ?>
				<table class="form-table" role="presentation" style="border:1px solid #dcdcde;padding:0 12px;margin-bottom:16px;background:#fff;">
					<tr>
						<th scope="row" colspan="2"><h3 style="margin:.6em 0;"><?php echo esc_html( sprintf( __( '%s plan', 'mathilde' ), ucfirst( $key ) ) ); ?> <code><?php echo esc_html( $key ); ?> → <?php echo esc_html( $key ); ?> role</code></h3></th>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled', 'mathilde' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $p['enabled'] ); ?>> <?php esc_html_e( 'Offer this plan', 'mathilde' ); ?></label>
						&nbsp;&nbsp;<label><input type="checkbox" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][featured]" value="1" <?php checked( $p['featured'] ); ?>> <?php esc_html_e( 'Highlight as “most popular”', 'mathilde' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Label', 'mathilde' ); ?></th>
						<td><input type="text" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $p['label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Price', 'mathilde' ); ?></th>
						<td><input type="text" size="8" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][price]" value="<?php echo esc_attr( $p['price'] ); ?>"> <?php echo esc_html( $s['currency'] ); ?> / <?php echo esc_html( $s['expiry_days'] ); ?> <?php esc_html_e( 'days', 'mathilde' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tagline', 'mathilde' ); ?></th>
						<td><input type="text" class="regular-text" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][tagline]" value="<?php echo esc_attr( $p['tagline'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Features (one per line)', 'mathilde' ); ?></th>
						<td><textarea rows="5" class="large-text" name="<?php echo esc_attr( $o ); ?>[plans][<?php echo esc_attr( $key ); ?>][features]"><?php echo esc_textarea( $p['features'] ); ?></textarea></td>
					</tr>
				</table>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Members', 'mathilde' ); ?></h2>
		<?php mathilde_membership_render_members_table(); ?>

		<p class="description">
			<?php
			printf(
				/* translators: %s: shortcode */
				esc_html__( 'Place the signup form on any page with the %s shortcode, or assign the “Membership” page template.', 'mathilde' ),
				'<code>[mathilde_membership]</code>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Render the members management table.
 */
function mathilde_membership_render_members_table() {
	$members = get_users(
		array(
			'meta_key' => 'mathilde_membership_status',
			'orderby'  => 'meta_value',
			'number'   => 200,
		)
	);

	if ( empty( $members ) ) {
		echo '<p>' . esc_html__( 'No members yet.', 'mathilde' ) . '</p>';
		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'mathilde' ); ?></th>
				<th><?php esc_html_e( 'Plan / Role', 'mathilde' ); ?></th>
				<th><?php esc_html_e( 'Status', 'mathilde' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'mathilde' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'mathilde' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $members as $m ) :
			$plan    = get_user_meta( $m->ID, 'mathilde_membership_plan', true );
			$status  = get_user_meta( $m->ID, 'mathilde_membership_status', true );
			$expires = (int) get_user_meta( $m->ID, 'mathilde_membership_expires', true );
			?>
			<tr>
				<td><strong><?php echo esc_html( $m->display_name ); ?></strong><br><span class="description"><?php echo esc_html( $m->user_email ); ?></span></td>
				<td><?php echo esc_html( ucfirst( $plan ) ); ?> <code><?php echo esc_html( implode( ', ', $m->roles ) ); ?></code></td>
				<td><?php echo esc_html( ucfirst( $status ) ); ?></td>
				<td><?php echo $expires ? esc_html( date_i18n( get_option( 'date_format' ), $expires ) ) : '—'; ?></td>
				<td>
					<form method="post" style="display:inline">
						<?php wp_nonce_field( 'mathilde_member_action' ); ?>
						<input type="hidden" name="user_id" value="<?php echo esc_attr( $m->ID ); ?>">
						<?php if ( 'pending' === $status ) : ?>
							<button class="button button-primary button-small" name="mathilde_member_action" value="approve"><?php esc_html_e( 'Approve', 'mathilde' ); ?></button>
						<?php endif; ?>
						<button class="button button-small" name="mathilde_member_action" value="extend"><?php esc_html_e( '+1 term', 'mathilde' ); ?></button>
						<button class="button button-small" name="mathilde_member_action" value="expire" onclick="return confirm('<?php esc_attr_e( 'Downgrade this member to a reader account now?', 'mathilde' ); ?>')"><?php esc_html_e( 'Expire', 'mathilde' ); ?></button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Show a member's expiry on their profile screen (read-only context).
 *
 * @param WP_User $user User.
 */
function mathilde_membership_profile_field( $user ) {
	$status = get_user_meta( $user->ID, 'mathilde_membership_status', true );
	if ( ! $status ) {
		return;
	}
	$expires = (int) get_user_meta( $user->ID, 'mathilde_membership_expires', true );
	?>
	<h2><?php esc_html_e( 'Contributor Membership', 'mathilde' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Status', 'mathilde' ); ?></th>
			<td><?php echo esc_html( ucfirst( $status ) ); ?><?php echo $expires ? ' — ' . esc_html( sprintf( __( 'expires %s', 'mathilde' ), date_i18n( get_option( 'date_format' ), $expires ) ) ) : ''; ?></td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'mathilde_membership_profile_field' );
add_action( 'edit_user_profile', 'mathilde_membership_profile_field' );
