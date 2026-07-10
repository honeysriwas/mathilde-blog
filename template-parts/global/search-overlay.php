<?php
/**
 * Full-screen search overlay with live results.
 *
 * @package Mathilde
 */
?>
<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'mathilde' ); ?>">
	<button class="search-overlay__close" type="button" data-close-overlay aria-label="<?php esc_attr_e( 'Close search', 'mathilde' ); ?>">
		<?php mathilde_the_icon( 'close', 28 ); ?>
	</button>
	<div class="search-overlay__inner">
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="overlay-search-field"><?php esc_html_e( 'Search for:', 'mathilde' ); ?></label>
			<input type="search" id="overlay-search-field" name="s" placeholder="<?php esc_attr_e( 'Search articles…', 'mathilde' ); ?>" autocomplete="off">
		</form>
		<div class="search-overlay__results" aria-live="polite"></div>
	</div>
</div>
