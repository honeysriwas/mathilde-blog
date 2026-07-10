<?php
/**
 * Off-canvas mobile drawer — navigation + social + subscribe.
 *
 * @package Mathilde
 */
?>
<aside class="drawer" id="mobile-drawer" aria-label="<?php esc_attr_e( 'Mobile menu', 'mathilde' ); ?>">
	<div class="drawer__head">
		<span class="brand"><span class="brand__title" style="font-size:1.2rem;"><?php bloginfo( 'name' ); ?></span></span>
		<button type="button" class="drawer__close" data-close-overlay aria-label="<?php esc_attr_e( 'Close menu', 'mathilde' ); ?>">
			<?php mathilde_the_icon( 'close', 24 ); ?>
		</button>
	</div>

	<div class="drawer__body">
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu-mobile',
					'depth'          => 2,
					'fallback_cb'    => false,
				)
			);
		} else {
			echo '<ul class="menu-mobile">';
			wp_list_categories( array( 'title_li' => '', 'number' => 8 ) );
			echo '</ul>';
		}
		?>
	</div>

	<div class="drawer__footer">
		<a class="btn btn--block" href="<?php echo esc_url( mathilde_option( 'subscribe_url', '#newsletter' ) ); ?>"><?php esc_html_e( 'Subscribe', 'mathilde' ); ?></a>
		<?php mathilde_social_links( 'm-social mt-5' ); ?>
	</div>
</aside>
