<?php
/**
 * Header — announcement bar, sticky header, navigation, search & drawer.
 *
 * @package Mathilde
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php do_action( 'mathilde_before_header' ); ?>

<div class="site">

	<?php // Reading progress (CSS hides until a single article is present). ?>
	<div class="reading-progress" aria-hidden="true"></div>

	<?php
	/* Announcement bar */
	$announce_text = mathilde_option( 'announce_text', __( 'The Latest in Fashion, Beauty &amp; Lifestyle — Read Our New Articles', 'mathilde' ) );
	if ( mathilde_option( 'announce_enable', true ) && $announce_text ) :
		$announce_link = mathilde_option( 'announce_link', '' );
		?>
		<div class="announce" role="region" aria-label="<?php esc_attr_e( 'Announcement', 'mathilde' ); ?>">
			<div class="announce__inner">
				<?php mathilde_the_icon( 'sparkle', 16 ); ?>
				<span>
					<?php if ( $announce_link ) : ?>
						<a href="<?php echo esc_url( $announce_link ); ?>"><?php echo wp_kses_post( $announce_text ); ?></a>
					<?php else : ?>
						<?php echo wp_kses_post( $announce_text ); ?>
					<?php endif; ?>
				</span>
			</div>
			<button class="announce__close" type="button" aria-label="<?php esc_attr_e( 'Dismiss announcement', 'mathilde' ); ?>">
				<?php mathilde_the_icon( 'close', 16 ); ?>
			</button>
		</div>
	<?php endif; ?>

	<header class="site-header" id="site-header">
		<div class="container container--wide">
			<div class="header-top">

				<div class="header-top__left">
					<button class="nav-toggle" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'mathilde' ); ?>" aria-controls="mobile-drawer">
						<?php mathilde_the_icon( 'menu', 24 ); ?>
					</button>
					<?php mathilde_social_links( 'header-social' ); ?>
				</div>

				<div class="brand">
					<?php mathilde_brand(); ?>
				</div>

				<div class="header-top__right">
					<button class="header-search search-toggle" type="button" aria-label="<?php esc_attr_e( 'Search', 'mathilde' ); ?>" aria-controls="search-overlay">
						<span class="hide-mobile"><?php esc_html_e( 'Search…', 'mathilde' ); ?></span>
						<?php mathilde_the_icon( 'search', 18 ); ?>
					</button>

					<?php if ( mathilde_dark_mode_enabled() ) : ?>
						<button class="dark-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'mathilde' ); ?>" aria-pressed="false">
							<?php mathilde_the_icon( 'moon', 20 ); ?>
							<?php mathilde_the_icon( 'sun', 20 ); ?>
						</button>
					<?php endif; ?>

					<?php if ( $sub_url = mathilde_option( 'subscribe_url' ) ) : ?>
						<a class="btn btn--sm hide-mobile" href="<?php echo esc_url( $sub_url ); ?>"><?php esc_html_e( 'Subscribe', 'mathilde' ); ?></a>
					<?php else : ?>
						<a class="btn btn--sm hide-mobile" href="#newsletter"><?php esc_html_e( 'Subscribe', 'mathilde' ); ?></a>
					<?php endif; ?>
				</div>

			</div>
		</div>

		<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'mathilde' ); ?>">
			<div class="container container--wide">
				<div class="primary-nav__inner">
					<?php
					if ( has_nav_menu( 'primary' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'primary',
								'container'      => false,
								'menu_class'     => 'menu-primary',
								'depth'          => 2,
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<ul class="menu-primary">';
						wp_list_categories(
							array(
								'title_li'   => '',
								'number'     => 6,
								'orderby'    => 'count',
								'order'      => 'DESC',
							)
						);
						echo '</ul>';
					}
					?>
				</div>
			</div>
		</nav>
	</header>

	<?php do_action( 'mathilde_after_header' ); ?>

	<main id="main" class="site-main" tabindex="-1">
<?php
/* Search overlay */
get_template_part( 'template-parts/global/search-overlay' );
/* Mobile drawer */
get_template_part( 'template-parts/global/mobile-drawer' );
?>
<div class="overlay-backdrop" data-close-overlay></div>
