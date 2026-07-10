<?php
/**
 * Main index — blog posts page fallback.
 *
 * @package Mathilde
 */

get_header();
?>

<div class="container container--wide">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<header class="archive-hero">
			<h1 class="archive-hero__title m-display"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ? get_the_title( get_option( 'page_for_posts' ) ) : __( 'The Journal', 'mathilde' ) ); ?></h1>
		</header>
	<?php endif; ?>

	<div class="shell">
		<div class="shell__content">
			<?php if ( have_posts() ) : ?>
				<div class="grid archive-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'archive' ) );
					endwhile;
					?>
				</div>
				<?php mathilde_pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</div>
		<aside class="shell__sidebar">
			<?php get_sidebar(); ?>
		</aside>
	</div>
</div>

<?php
get_footer();
