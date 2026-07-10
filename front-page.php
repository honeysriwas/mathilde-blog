<?php
/**
 * Front page (homepage) — the editorial magazine layout.
 *
 * Section order follows the wireframe / mockup. Each block is a self-contained
 * template part and most can be toggled or configured from the Customizer.
 *
 * @package Mathilde
 */

get_header();
?>

<?php
// 1 — Hero rotator.
get_template_part( 'template-parts/homepage/hero' );

// 2 — About Mathilde.
get_template_part( 'template-parts/homepage/about' );

// 3 — Explore by Category (5-up).
get_template_part( 'template-parts/homepage/category-cards' );

// 4 — As Featured In carousel.
get_template_part( 'template-parts/homepage/featured-in' );

// 5–8 — Category post sliders. Configurable per section.
$sections = array(
	array( 'key' => 'fashion', 'title' => __( 'Fashion Posts', 'mathilde' ), 'category' => 'fashion' ),
	array( 'key' => 'beauty',  'title' => __( 'Beauty Posts', 'mathilde' ),  'category' => 'beauty' ),
	array( 'key' => 'jewelry', 'title' => __( 'Jewelry Posts', 'mathilde' ), 'category' => 'jewelry' ),
	array( 'key' => 'health',  'title' => __( 'Health Posts', 'mathilde' ),  'category' => 'health' ),
);

foreach ( $sections as $sec ) {
	if ( ! mathilde_option( 'section_' . $sec['key'] . '_enable', true ) ) {
		continue;
	}
	get_template_part(
		'template-parts/homepage/posts-section',
		null,
		array(
			'title'    => mathilde_option( 'section_' . $sec['key'] . '_title', $sec['title'] ),
			'category' => mathilde_option( 'section_' . $sec['key'] . '_cat', $sec['category'] ),
			'count'    => 8,
		)
	);
}
?>

<?php // 8.5 — Featured Guides (ebooks) strip. ?>
<?php get_template_part( 'template-parts/homepage/featured-guides' ); ?>

<?php // 8.6 — From the Community (Reddit) strip. Needs the AI Writer plugin. ?>
<?php if ( mathilde_option( 'reddit_enable', false ) && class_exists( 'MAIW_Reddit_Blocks' ) ) : ?>
	<section class="section section--tight" aria-label="<?php esc_attr_e( 'From the Community', 'mathilde' ); ?>">
		<div class="container container--wide">
			<div class="section__head">
				<h2 class="m-section-title"><?php echo esc_html( mathilde_option( 'reddit_title', __( 'From the Community', 'mathilde' ) ) ); ?></h2>
			</div>
			<?php
			echo MAIW_Reddit_Blocks::render_feed_block( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup escaped within the plugin helper.
				array(
					'subreddit' => mathilde_option( 'reddit_sub', '' ),
					'sort'      => 'hot',
					'limit'     => (int) mathilde_option( 'reddit_count', 6 ),
					'heading'   => mathilde_option( 'reddit_title', __( 'From the Community', 'mathilde' ) ),
				)
			);
			?>
		</div>
	</section>
<?php endif; ?>

<?php // 9 — AI Trust & Review. ?>
<?php if ( mathilde_option( 'aitrust_enable', true ) ) : ?>
	<section class="section" aria-label="<?php esc_attr_e( 'AI Trust & Review', 'mathilde' ); ?>">
		<div class="container container--wide">
			<div class="ai-trust section">
				<div class="container">
					<div class="section__head" style="justify-content:flex-start;">
						<h2 class="m-section-title"><?php echo esc_html( mathilde_option( 'aitrust_title', __( 'AI Trust & Review', 'mathilde' ) ) ); ?></h2>
					</div>
					<?php get_template_part( 'template-parts/components/ai-trust' ); ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php // 10 — FAQ. ?>
<?php if ( mathilde_option( 'faq_enable', true ) ) : ?>
	<section class="section" aria-label="<?php esc_attr_e( 'Frequently Asked Questions', 'mathilde' ); ?>">
		<div class="container container--wide">
			<div class="section__head">
				<h2 class="m-section-title"><?php echo esc_html( mathilde_option( 'faq_title', __( 'Frequently Asked Questions', 'mathilde' ) ) ); ?></h2>
			</div>
			<?php get_template_part( 'template-parts/components/faq' ); ?>
		</div>
	</section>
<?php endif; ?>

<?php // 11 — Latest From The Blog (mixed grid). ?>
<?php
$latest = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( $latest->have_posts() ) :
	?>
	<section class="section section--tight" aria-label="<?php esc_attr_e( 'Latest from the blog', 'mathilde' ); ?>">
		<div class="container container--wide">
			<div class="section__head">
				<h2 class="m-section-title"><?php esc_html_e( 'Latest From The Blog', 'mathilde' ); ?></h2>
				<a class="m-link-all" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'View All', 'mathilde' ); ?></a>
			</div>
			<div class="grid grid--5">
				<?php
				while ( $latest->have_posts() ) :
					$latest->the_post();
					get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'default', 'badge' => false ) );
				endwhile;
				?>
			</div>
		</div>
	</section>
	<?php
endif;
wp_reset_postdata();
?>

<?php // 12 — Newsletter band. ?>
<section class="section section--tight" id="newsletter">
	<div class="container container--wide">
		<?php get_template_part( 'template-parts/components/newsletter', null, array( 'style' => 'band' ) ); ?>
	</div>
</section>

<?php // 13 — Instagram strip. ?>
<?php get_template_part( 'template-parts/homepage/instagram' ); ?>

<?php
get_footer();
