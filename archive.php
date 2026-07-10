<?php
/**
 * Archive template — category, tag, author, date archives.
 *
 * Matches the category mockup: hero header, sort toolbar, 4-up grid,
 * sticky sidebar, AJAX load-more / numbered pagination.
 *
 * @package Mathilde
 */

get_header();

$current_cat = is_category() ? get_queried_object() : null;
?>

<div class="container container--wide">
	<?php mathilde_breadcrumbs(); ?>
</div>

<div class="container container--wide">

	<header class="archive-hero">
		<h1 class="archive-hero__title m-display"><?php the_archive_title(); ?></h1>
		<?php
		$desc = get_the_archive_description();
		if ( $desc ) :
			?>
			<div class="archive-hero__desc"><?php echo wp_kses_post( $desc ); ?></div>
		<?php endif; ?>
	</header>

	<div class="shell">

		<div class="shell__content">

			<div class="archive-toolbar">
				<p class="text-soft" style="margin:0;font-size:0.9rem;">
					<?php
					global $wp_query;
					printf(
						/* translators: %s: number of posts */
						esc_html( _n( '%s article', '%s articles', $wp_query->found_posts, 'mathilde' ) ),
						esc_html( number_format_i18n( $wp_query->found_posts ) )
					);
					?>
				</p>
				<label class="flex items-center gap-2">
					<span class="screen-reader-text"><?php esc_html_e( 'Sort by', 'mathilde' ); ?></span>
					<select class="archive-sort" data-archive-sort>
						<?php
						$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
						$opts    = array(
							'date'    => __( 'Latest First', 'mathilde' ),
							'oldest'  => __( 'Oldest First', 'mathilde' ),
							'title'   => __( 'A – Z', 'mathilde' ),
							'popular' => __( 'Most Popular', 'mathilde' ),
						);
						foreach ( $opts as $val => $label ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $val ), selected( $orderby, $val, false ), esc_html( $label ) );
						}
						?>
					</select>
				</label>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="grid archive-grid" id="archive-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'archive' ) );
					endwhile;
					?>
				</div>

				<?php
				// Numbered pagination (works without JS); AJAX load-more is optional.
				mathilde_pagination();
				?>

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
