<?php
/**
 * Footer — Instagram strip, newsletter, footer columns, bottom bar.
 *
 * @package Mathilde
 */
?>
	</main><!-- #main -->

	<?php do_action( 'mathilde_before_footer' ); ?>

	<?php
	/* Global newsletter band (hidden on the homepage, which has its own). */
	if ( ! is_front_page() && mathilde_option( 'newsletter_footer_enable', true ) ) :
		?>
		<div class="container" id="newsletter">
			<?php get_template_part( 'template-parts/components/newsletter', null, array( 'style' => 'band' ) ); ?>
		</div>
	<?php endif; ?>

	<footer class="site-footer" role="contentinfo">
		<div class="container container--wide">
			<div class="footer-main">

				<div class="footer-brand">
					<span class="brand" style="text-align:left;display:inline-block;">
						<?php mathilde_brand(); ?>
					</span>
					<p class="footer-brand__text">
						<?php echo esc_html( mathilde_option( 'footer_about', __( 'Your destination for fashion, beauty, lifestyle, travel & business inspiration. Empowering you to live beautifully and intentionally.', 'mathilde' ) ) ); ?>
					</p>
					<?php mathilde_social_links( 'm-social' ); ?>
				</div>

				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<div class="footer-col"><?php dynamic_sidebar( 'footer-1' ); ?></div>
				<?php else : ?>
					<div class="footer-col">
						<h3 class="footer-widget__title"><?php esc_html_e( 'Explore', 'mathilde' ); ?></h3>
						<ul><?php wp_list_categories( array( 'title_li' => '', 'number' => 6 ) ); ?></ul>
					</div>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
					<div class="footer-col"><?php dynamic_sidebar( 'footer-2' ); ?></div>
				<?php else : ?>
					<div class="footer-col">
						<h3 class="footer-widget__title"><?php esc_html_e( 'Information', 'mathilde' ); ?></h3>
						<?php
						if ( has_nav_menu( 'footer' ) ) {
							wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
						} else {
							echo '<ul><li>' . esc_html__( 'Add a "Footer Menu" under Appearance → Menus.', 'mathilde' ) . '</li></ul>';
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
					<div class="footer-col"><?php dynamic_sidebar( 'footer-3' ); ?></div>
				<?php else : ?>
					<div class="footer-col">
						<h3 class="footer-widget__title"><?php esc_html_e( 'Legal', 'mathilde' ); ?></h3>
						<?php
						if ( has_nav_menu( 'legal' ) ) {
							wp_nav_menu( array( 'theme_location' => 'legal', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( $footer_img = mathilde_option( 'footer_image' ) ) : ?>
					<div class="footer-thumb"><img src="<?php echo esc_url( $footer_img ); ?>" alt=""></div>
				<?php endif; ?>

			</div>
		</div>

		<div class="container container--wide">
			<div class="footer-bottom">
				<p>
					<?php
					printf(
						/* translators: 1: year, 2: site name */
						esc_html__( '© %1$s %2$s. All Rights Reserved.', 'mathilde' ),
						esc_html( wp_date( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</p>
				<?php
				if ( has_nav_menu( 'legal' ) ) {
					wp_nav_menu( array( 'theme_location' => 'legal', 'container' => 'nav', 'menu_class' => 'm-social', 'depth' => 1, 'fallback_cb' => false ) );
				}
				?>
			</div>
		</div>
	</footer>

	<button class="to-top" type="button" aria-label="<?php esc_attr_e( 'Back to top', 'mathilde' ); ?>">
		<?php mathilde_the_icon( 'arrow-up', 20 ); ?>
	</button>

</div><!-- .site -->

<?php wp_footer(); ?>
</body>
</html>
