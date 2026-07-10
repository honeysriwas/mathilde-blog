<?php
/**
 * 404 — not found.
 *
 * @package Mathilde
 */

get_header();
?>

<div class="container container--narrow" style="text-align:center;padding-block:var(--space-10);">
	<span class="m-eyebrow text-accent"><?php esc_html_e( 'Error 404', 'mathilde' ); ?></span>
	<h1 class="m-display" style="font-size:clamp(3rem,8vw,6rem);margin:var(--space-3) 0;"><?php esc_html_e( 'Page Not Found', 'mathilde' ); ?></h1>
	<p class="text-soft" style="max-width:46ch;margin:0 auto var(--space-6);">
		<?php esc_html_e( 'The page you’re looking for has moved, been removed, or never existed. Let’s get you back to the good stuff.', 'mathilde' ); ?>
	</p>
	<div style="max-width:440px;margin:0 auto var(--space-6);"><?php get_search_form(); ?></div>
	<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to Homepage', 'mathilde' ); ?></a>

	<?php
	$recent = new WP_Query( array( 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
	if ( $recent->have_posts() ) :
		?>
		<div class="grid grid--3" style="margin-top:var(--space-9);text-align:left;">
			<?php
			while ( $recent->have_posts() ) :
				$recent->the_post();
				get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'default', 'badge' => false ) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
