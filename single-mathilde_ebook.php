<?php
/**
 * Single ebook — sales page (cover + details + buy box).
 *
 * @package Mathilde
 */

get_header();

while ( have_posts() ) :
	the_post();
	$ebook_id = get_the_ID();
	$subtitle    = get_post_meta( $ebook_id, '_mathilde_ebook_subtitle', true );
	$ebook_pages = (int) get_post_meta( $ebook_id, '_mathilde_ebook_pages', true );
	?>
	<article <?php post_class( 'single-article-wrap' ); ?>>
		<div class="container container--wide">
			<?php mathilde_breadcrumbs(); ?>
		</div>

		<div class="container container--wide">
			<div class="ebook-single">

				<div class="ebook-single__media reveal">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'mathilde-card', array( 'class' => 'no-dim' ) ); ?>
					<?php else : ?>
						<span class="m-thumb-placeholder" style="aspect-ratio:4/5;"><?php mathilde_the_icon( 'sparkle', 40 ); ?></span>
					<?php endif; ?>
				</div>

				<div class="ebook-single__info reveal">
					<span class="m-eyebrow text-accent"><?php esc_html_e( 'Digital Guide', 'mathilde' ); ?></span>
					<h1 class="ebook-single__title m-display"><?php the_title(); ?></h1>
					<?php if ( $subtitle ) : ?>
						<p class="ebook-single__sub"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>

					<ul class="ebook-single__facts">
						<li><?php mathilde_the_icon( 'sparkle', 16 ); ?> <?php esc_html_e( 'Instant digital download', 'mathilde' ); ?></li>
						<?php if ( $ebook_pages ) : ?>
							<li><?php mathilde_the_icon( 'list', 16 ); ?> <?php echo esc_html( sprintf( _n( '%d page', '%d pages', $ebook_pages, 'mathilde' ), $ebook_pages ) ); ?></li>
						<?php endif; ?>
						<li><?php mathilde_the_icon( 'shield', 16 ); ?> <?php esc_html_e( 'Secure PayPal checkout', 'mathilde' ); ?></li>
					</ul>

					<?php if ( trim( get_the_content() ) ) : ?>
						<div class="ebook-single__desc m-prose"><?php the_content(); ?></div>
					<?php endif; ?>
				</div>

				<aside class="ebook-single__buy reveal">
					<?php get_template_part( 'template-parts/ebooks/buy-box' ); ?>
				</aside>

			</div>
		</div>

		<?php
		// Related ebooks.
		$more_books = new WP_Query(
			array(
				'post_type'      => 'mathilde_ebook',
				'posts_per_page' => 3,
				'post__not_in'   => array( $ebook_id ),
				'orderby'        => 'rand',
				'no_found_rows'  => true,
			)
		);
		if ( $more_books->have_posts() ) :
			?>
			<div class="container container--wide section--tight" style="padding-top:var(--space-8);">
				<div class="section__head"><h2 class="m-section-title"><?php esc_html_e( 'More Guides', 'mathilde' ); ?></h2></div>
				<div class="grid grid--3 ebook-grid">
					<?php
					while ( $more_books->have_posts() ) :
						$more_books->the_post();
						get_template_part( 'template-parts/ebooks/card' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
