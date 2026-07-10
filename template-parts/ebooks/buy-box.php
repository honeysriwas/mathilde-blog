<?php
/**
 * Ebook buy box — price, guest details, PayPal buttons (or download if owned).
 *
 * @package Mathilde
 */

$ebook_id = get_the_ID();
$price    = mathilde_ebook_price( $ebook_id );
$s        = mathilde_membership_settings();
$owned    = mathilde_ebook_user_owns( $ebook_id );
?>
<div class="ebook-buy" id="ebook-buy" data-ebook-id="<?php echo esc_attr( $ebook_id ); ?>">

	<?php if ( $price ) : ?>
		<div class="ebook-buy__price">
			<span class="ebook-buy__amount"><?php echo esc_html( mathilde_membership_format_price( $price, $s['currency'] ) ); ?></span>
			<span class="ebook-buy__once"><?php esc_html_e( 'one-time · instant download', 'mathilde' ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( $owned ) : ?>
		<a class="btn btn--block" href="<?php echo esc_url( mathilde_ebook_download_url( $owned ) ); ?>">
			<?php mathilde_the_icon( 'check', 18 ); ?> <?php esc_html_e( 'Download your copy', 'mathilde' ); ?>
		</a>
		<p class="ebook-buy__owned text-soft"><?php esc_html_e( 'You already own this guide.', 'mathilde' ); ?></p>

	<?php elseif ( ! $price ) : ?>
		<p class="text-soft"><?php esc_html_e( 'This guide is not available for purchase yet.', 'mathilde' ); ?></p>

	<?php elseif ( ! mathilde_membership_configured() ) : ?>
		<div class="membership__notice membership__notice--warn">
			<?php mathilde_the_icon( 'shield', 18 ); ?>
			<div><?php esc_html_e( 'Checkout isn’t connected yet. The site owner needs to add PayPal credentials under Users → Membership.', 'mathilde' ); ?></div>
		</div>

	<?php else : ?>

		<?php if ( ! is_user_logged_in() ) : ?>
			<form class="ebook-buy__form" id="ebook-form" autocomplete="on" novalidate>
				<div class="field">
					<label for="eb-name"><?php esc_html_e( 'Your name', 'mathilde' ); ?></label>
					<input type="text" id="eb-name" name="name" required autocomplete="name">
				</div>
				<div class="field">
					<label for="eb-email"><?php esc_html_e( 'Email (where we send the download)', 'mathilde' ); ?></label>
					<input type="email" id="eb-email" name="email" required autocomplete="email">
				</div>
			</form>
		<?php endif; ?>

		<div id="ebook-paypal-container"></div>
		<div class="membership__msg" id="ebook-msg" role="status" aria-live="polite"></div>
		<div class="ebook-buy__ready hidden" id="ebook-ready">
			<a class="btn btn--block btn--rose" id="ebook-download-link" href="#"><?php mathilde_the_icon( 'check', 18 ); ?> <?php esc_html_e( 'Download now', 'mathilde' ); ?></a>
		</div>
		<p class="membership__secure text-soft"><?php mathilde_the_icon( 'shield', 14 ); ?> <?php esc_html_e( 'Secure PayPal checkout. Instant, tokenised download.', 'mathilde' ); ?></p>

	<?php endif; ?>
</div>
