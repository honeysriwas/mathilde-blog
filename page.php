<?php
/**
 * Default page template.
 *
 * @package Mathilde
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'single-article-wrap' ); ?>>
		<div class="container container--wide">
			<?php mathilde_breadcrumbs(); ?>
		</div>

		<div class="container container--narrow">
			<header class="article-header text-center" style="text-align:center;margin-top:var(--space-5);">
				<h1 class="article-header__title"><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="article-hero"><?php the_post_thumbnail( 'mathilde-hero' ); ?></figure>
			<?php endif; ?>

			<div class="m-prose" data-article-body>
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'mathilde' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
