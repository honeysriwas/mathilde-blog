<?php
/**
 * Ebook shop archive.
 *
 * @package Mathilde
 */

get_header();
?>
<div class="container container--wide">
	<header class="archive-hero text-center" style="text-align:center;">
		<span class="m-eyebrow text-accent"><?php esc_html_e( 'The Shop', 'mathilde' ); ?></span>
		<h1 class="archive-hero__title m-display" style="margin-inline:auto;"><?php esc_html_e( 'Digital Guides &amp; Ebooks', 'mathilde' ); ?></h1>
		<p class="archive-hero__desc" style="margin-inline:auto;"><?php esc_html_e( 'Beautifully crafted, downloadable guides to help you look and feel your best — delivered instantly to your inbox.', 'mathilde' ); ?></p>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="grid grid--3 ebook-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/ebooks/card' );
			endwhile;
			?>
		</div>
		<?php mathilde_pagination(); ?>
	<?php else : ?>
		<p class="text-center text-soft" style="padding-block:var(--space-9);"><?php esc_html_e( 'No guides available yet — check back soon.', 'mathilde' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
