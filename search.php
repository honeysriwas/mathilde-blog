<?php
/**
 * Search results template.
 *
 * @package Mathilde
 */

get_header();
?>

<div class="container container--wide">
	<?php mathilde_breadcrumbs(); ?>
</div>

<div class="container container--wide">
	<header class="archive-hero">
		<span class="m-eyebrow"><?php esc_html_e( 'Search Results', 'mathilde' ); ?></span>
		<h1 class="archive-hero__title m-display">
			<?php
			/* translators: %s: search query */
			printf( esc_html__( '“%s”', 'mathilde' ), esc_html( get_search_query() ) );
			?>
		</h1>
		<p class="archive-hero__desc">
			<?php
			global $wp_query;
			printf(
				/* translators: %s: result count */
				esc_html( _n( '%s result found', '%s results found', $wp_query->found_posts, 'mathilde' ) ),
				esc_html( number_format_i18n( $wp_query->found_posts ) )
			);
			?>
		</p>
	</header>

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
