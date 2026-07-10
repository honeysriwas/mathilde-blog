<?php
/**
 * Membership pricing + signup UI (used by the [mathilde_membership] shortcode
 * and the Membership page template).
 *
 * @package Mathilde
 */

$s     = mathilde_membership_settings();
$plans = array();
foreach ( $s['plans'] as $key => $plan ) {
	if ( ! empty( $plan['enabled'] ) ) {
		$plans[ $key ] = $plan;
	}
}

$current_user = wp_get_current_user();
$is_member    = is_user_logged_in() && mathilde_membership_is_active( $current_user->ID );
?>
<section class="membership">

	<header class="membership__intro reveal">
		<span class="m-eyebrow text-accent"><?php esc_html_e( 'Join the Team', 'mathilde' ); ?></span>
		<h2 class="membership__title m-display"><?php echo esc_html( $s['intro_title'] ); ?></h2>
		<p class="membership__lead"><?php echo esc_html( $s['intro_text'] ); ?></p>
	</header>

	<?php if ( empty( $plans ) ) : ?>
		<p class="text-center text-soft"><?php esc_html_e( 'Contributor memberships are not available right now. Please check back soon.', 'mathilde' ); ?></p>
		</section>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $is_member ) : ?>
		<div class="membership__notice membership__notice--ok reveal">
			<?php mathilde_the_icon( 'check', 20 ); ?>
			<div>
				<strong><?php esc_html_e( 'You’re an active contributor.', 'mathilde' ); ?></strong>
				<?php
				$exp = (int) get_user_meta( $current_user->ID, 'mathilde_membership_expires', true );
				printf(
					' ' . esc_html__( 'Your access runs until %s. You can renew below to extend it.', 'mathilde' ),
					'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), $exp ) ) . '</strong>'
				);
				?>
				&nbsp;<a href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Go to your dashboard →', 'mathilde' ); ?></a>
			</div>
		</div>
	<?php endif; ?>

	<?php // Pricing cards. ?>
	<div class="membership__plans grid grid--<?php echo count( $plans ) > 1 ? '2' : '1'; ?>">
		<?php $first = true; foreach ( $plans as $key => $plan ) : ?>
			<label class="plan-card reveal<?php echo ! empty( $plan['featured'] ) ? ' plan-card--featured' : ''; ?>" data-plan="<?php echo esc_attr( $key ); ?>">
				<input type="radio" name="mathilde_plan" value="<?php echo esc_attr( $key ); ?>" <?php checked( $first ); ?> class="plan-card__radio">
				<?php if ( ! empty( $plan['featured'] ) ) : ?>
					<span class="plan-card__ribbon"><?php esc_html_e( 'Most Popular', 'mathilde' ); ?></span>
				<?php endif; ?>
				<span class="plan-card__head">
					<span class="plan-card__name"><?php echo esc_html( $plan['label'] ); ?></span>
					<span class="plan-card__tagline"><?php echo esc_html( $plan['tagline'] ); ?></span>
				</span>
				<span class="plan-card__price">
					<span class="plan-card__amount"><?php echo esc_html( mathilde_membership_format_price( $plan['price'], $s['currency'] ) ); ?></span>
					<span class="plan-card__period">/ <?php echo 365 === (int) $s['expiry_days'] ? esc_html__( 'year', 'mathilde' ) : esc_html( sprintf( __( '%d days', 'mathilde' ), (int) $s['expiry_days'] ) ); ?></span>
				</span>
				<span class="plan-card__features">
					<?php foreach ( array_filter( array_map( 'trim', explode( "\n", $plan['features'] ) ) ) as $feat ) : ?>
						<span class="plan-card__feature"><?php mathilde_the_icon( 'check', 16 ); ?> <span><?php echo esc_html( $feat ); ?></span></span>
					<?php endforeach; ?>
				</span>
				<span class="plan-card__select"><?php esc_html_e( 'Select plan', 'mathilde' ); ?></span>
			</label>
		<?php $first = false; endforeach; ?>
	</div>

	<?php // Checkout panel. ?>
	<div class="membership__checkout reveal">

		<?php if ( ! mathilde_membership_configured() ) : ?>
			<div class="membership__notice membership__notice--warn">
				<?php mathilde_the_icon( 'shield', 20 ); ?>
				<div><?php esc_html_e( 'Payments aren’t connected yet. The site owner needs to add PayPal credentials under Users → Membership.', 'mathilde' ); ?></div>
			</div>
		<?php else : ?>

			<?php if ( ! is_user_logged_in() ) : ?>
				<form class="membership__form" id="membership-form" autocomplete="on" novalidate>
					<h3 class="membership__form-title"><?php esc_html_e( 'Create your contributor account', 'mathilde' ); ?></h3>
					<p class="membership__form-sub text-soft"><?php esc_html_e( 'Your account is created automatically once payment is confirmed.', 'mathilde' ); ?></p>
					<div class="membership__fields">
						<div class="field">
							<label for="m-name"><?php esc_html_e( 'Full name', 'mathilde' ); ?></label>
							<input type="text" id="m-name" name="name" required autocomplete="name">
						</div>
						<div class="field">
							<label for="m-user"><?php esc_html_e( 'Username', 'mathilde' ); ?></label>
							<input type="text" id="m-user" name="username" required autocomplete="username" pattern="[A-Za-z0-9_.\-]{3,}">
							<span class="field__hint" data-field-hint="username"></span>
						</div>
						<div class="field">
							<label for="m-email"><?php esc_html_e( 'Email address', 'mathilde' ); ?></label>
							<input type="email" id="m-email" name="email" required autocomplete="email">
							<span class="field__hint" data-field-hint="email"></span>
						</div>
						<div class="field">
							<label for="m-pass"><?php esc_html_e( 'Password', 'mathilde' ); ?></label>
							<input type="password" id="m-pass" name="password" autocomplete="new-password" minlength="6" placeholder="<?php esc_attr_e( 'Leave blank to auto-generate', 'mathilde' ); ?>">
						</div>
					</div>
					<p class="membership__terms text-soft">
						<?php esc_html_e( 'By joining you agree to our editorial guidelines. You can cancel renewal anytime — access simply ends when the term lapses.', 'mathilde' ); ?>
					</p>
				</form>
			<?php else : ?>
				<div class="membership__form">
					<h3 class="membership__form-title"><?php echo esc_html( $is_member ? __( 'Renew your membership', 'mathilde' ) : __( 'Activate your contributor access', 'mathilde' ) ); ?></h3>
					<p class="text-soft">
						<?php
						printf(
							/* translators: %s: user display name */
							esc_html__( 'Signed in as %s. Your selected plan will be applied to this account.', 'mathilde' ),
							'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="membership__pay">
				<div id="paypal-button-container"></div>
				<div class="membership__msg" id="membership-msg" role="status" aria-live="polite"></div>
				<p class="membership__secure text-soft"><?php mathilde_the_icon( 'shield', 14 ); ?> <?php esc_html_e( 'Secure payment via PayPal. We never see your card details.', 'mathilde' ); ?></p>
			</div>

		<?php endif; ?>
	</div>

</section>
