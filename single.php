<?php
/**
 * Single post template — editorial article experience.
 *
 * Layout: [vertical share rail] · [article] · [sticky sidebar]
 *
 * @package Mathilde
 */

get_header();

while ( have_posts() ) :
	the_post();

	// Build the TOC and get content with anchor ids injected.
	$toc           = array();
	$raw_content   = get_the_content();
	$rendered      = apply_filters( 'the_content', $raw_content );
	$rendered      = mathilde_build_toc( $rendered, $toc );

	$takeaways_raw = get_post_meta( get_the_ID(), '_mathilde_takeaways', true );
	$takeaways     = $takeaways_raw ? array_filter( array_map( 'trim', explode( "\n", $takeaways_raw ) ) ) : array();
	?>

	<article <?php post_class( 'single-article-wrap' ); ?>>

		<div class="container container--wide">
			<?php mathilde_breadcrumbs(); ?>
		</div>

		<div class="container container--wide">
			<div class="single-grid">

				<?php // Vertical share rail (desktop). ?>
				<div class="single-grid__rail">
					<?php mathilde_share_buttons( true ); ?>
				</div>

				<?php // Main article column. ?>
				<div class="single-grid__main">

					<header class="article-header">
						<?php mathilde_category_eyebrow(); ?>
						<h1 class="article-header__title"><?php the_title(); ?></h1>
						<?php mathilde_post_meta( array( 'avatar' => true ) ); ?>
					</header>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="article-hero">
							<?php the_post_thumbnail( 'mathilde-hero', array( 'class' => 'no-dim', 'fetchpriority' => 'high' ) ); ?>
						</figure>
					<?php endif; ?>

					<?php // Key takeaways. ?>
					<?php if ( ! empty( $takeaways ) ) : ?>
						<div class="takeaways reveal">
							<div class="takeaways__head"><?php mathilde_the_icon( 'sparkle', 16 ); ?> <?php esc_html_e( 'Key Takeaways', 'mathilde' ); ?></div>
							<ul>
								<?php foreach ( $takeaways as $t ) : ?>
									<li><?php mathilde_the_icon( 'check', 18 ); ?> <span><?php echo esc_html( $t ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php // Table of contents (collapsible). ?>
					<?php if ( count( $toc ) >= 3 && get_post_meta( get_the_ID(), '_mathilde_hide_toc', true ) !== '1' ) : ?>
						<nav class="toc reveal" aria-label="<?php esc_attr_e( 'Table of contents', 'mathilde' ); ?>">
							<p class="toc__title"><?php mathilde_the_icon( 'list', 16 ); ?> <?php esc_html_e( 'In This Article', 'mathilde' ); ?></p>
							<ol>
								<?php foreach ( $toc as $item ) : ?>
									<li class="toc--h<?php echo (int) $item['level']; ?>"><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						</nav>
					<?php endif; ?>

					<?php // Article body. ?>
					<div class="m-prose" data-article-body>
						<?php echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput -- already filtered. ?>
					</div>

					<?php
					wp_link_pages(
						array(
							'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'mathilde' ),
							'after'  => '</div>',
						)
					);
					?>

					<?php // Tags. ?>
					<?php if ( has_tag() ) : ?>
						<div class="tag-list mt-6"><?php the_tags( '', '' ); ?></div>
					<?php endif; ?>

					<?php // Recommended guide (ebook upsell). ?>
					<?php get_template_part( 'template-parts/components/recommended-guide' ); ?>

					<?php // Share row (horizontal, for mobile / inline). ?>
					<div class="mt-6"><?php mathilde_share_buttons( false ); ?></div>

					<?php // Review box (when a rating is set). ?>
					<?php
					$score = get_post_meta( get_the_ID(), '_mathilde_review_score', true );
					if ( $score !== '' && (float) $score > 0 ) :
						$score   = (float) $score;
						$verdict = get_post_meta( get_the_ID(), '_mathilde_review_verdict', true );
						$full    = (int) floor( $score );
						$pros    = get_post_meta( get_the_ID(), '_mathilde_review_pros', true );
						$cons    = get_post_meta( get_the_ID(), '_mathilde_review_cons', true );
						$pros    = $pros ? array_filter( array_map( 'trim', explode( "\n", $pros ) ) ) : array();
						$cons    = $cons ? array_filter( array_map( 'trim', explode( "\n", $cons ) ) ) : array();
						?>
						<div class="review-box reveal">
							<div class="flex items-center gap-4">
								<span class="review-box__score"><?php echo esc_html( number_format_i18n( $score, 1 ) ); ?></span>
								<div>
									<div class="review-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $full ) . str_repeat( '☆', max( 0, 5 - $full ) ) ); ?></div>
									<span class="m-eyebrow"><?php esc_html_e( 'Our Verdict', 'mathilde' ); ?></span>
								</div>
							</div>
							<?php if ( $verdict ) : ?>
								<p class="mt-3" style="margin-bottom:0;"><?php echo esc_html( $verdict ); ?></p>
							<?php endif; ?>
							<?php if ( $pros || $cons ) : ?>
								<div class="review-box__pc">
									<?php if ( $pros ) : ?>
										<div class="review-pc review-pc--pros">
											<span class="m-eyebrow"><?php esc_html_e( 'What we love', 'mathilde' ); ?></span>
											<ul>
												<?php foreach ( $pros as $p ) : ?>
													<li><?php mathilde_the_icon( 'check', 18 ); ?> <span><?php echo esc_html( $p ); ?></span></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endif; ?>
									<?php if ( $cons ) : ?>
										<div class="review-pc review-pc--cons">
											<span class="m-eyebrow"><?php esc_html_e( 'Worth noting', 'mathilde' ); ?></span>
											<ul>
												<?php foreach ( $cons as $c ) : ?>
													<li><span class="review-pc__minus" aria-hidden="true">–</span> <span><?php echo esc_html( $c ); ?></span></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php // Author box. ?>
					<?php get_template_part( 'template-parts/components/author-box' ); ?>

					<?php // Related posts. ?>
					<?php get_template_part( 'template-parts/components/related-posts' ); ?>

					<?php // Comments. ?>
					<?php if ( comments_open() || get_comments_number() ) : ?>
						<div class="comments-area mt-8"><?php comments_template(); ?></div>
					<?php endif; ?>

				</div>

				<?php // Sidebar. ?>
				<aside class="single-grid__aside article-rail">
					<?php get_sidebar(); ?>
				</aside>

			</div>
		</div>

	</article>

	<?php
endwhile;

get_footer();
