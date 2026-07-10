<?php
/**
 * Sidebar — curated editorial widgets matching the mockups.
 *
 * If the "Blog Sidebar" widget area has widgets, those are shown. Otherwise a
 * polished default set renders (About card, Trending, Categories, Newsletter,
 * Instagram) so the theme looks complete out of the box.
 *
 * @package Mathilde
 */

if ( is_active_sidebar( 'sidebar-blog' ) ) {
	echo '<div class="sidebar-widgets">';
	dynamic_sidebar( 'sidebar-blog' );
	echo '</div>';
	return;
}
?>

<?php // About card. ?>
<div class="author-card widget">
	<?php
	$about_img = mathilde_option( 'about_image' );
	$about_avatar = mathilde_option( 'sidebar_avatar', $about_img );
	?>
	<div class="author-card__avatar">
		<?php if ( $about_avatar ) : ?>
			<img src="<?php echo esc_url( $about_avatar ); ?>" alt="<?php echo esc_attr( mathilde_option( 'about_eyebrow', get_bloginfo( 'name' ) ) ); ?>">
		<?php else : ?>
			<span class="m-thumb-placeholder" style="width:92px;height:92px;border-radius:50%;margin-inline:auto;"><?php mathilde_the_icon( 'user', 30 ); ?></span>
		<?php endif; ?>
	</div>
	<span class="m-eyebrow mt-3"><?php echo esc_html( mathilde_option( 'sidebar_about_eyebrow', __( 'About Me', 'mathilde' ) ) ); ?></span>
	<h3 class="author-card__name"><?php echo esc_html( mathilde_option( 'sidebar_about_name', get_bloginfo( 'name' ) ) ); ?></h3>
	<p class="author-card__bio"><?php echo esc_html( mathilde_option( 'sidebar_about_bio', __( 'Fashion, beauty & lifestyle enthusiast sharing tips on looking good, feeling great and living beautifully.', 'mathilde' ) ) ); ?></p>
	<a class="btn btn--sm" href="<?php echo esc_url( mathilde_option( 'about_link', home_url( '/about/' ) ) ); ?>"><?php esc_html_e( 'More About Me', 'mathilde' ); ?></a>
</div>

<?php
// Trending Now (most commented in last 60 days, fallback recent).
$trending = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'comment_count',
		'order'               => 'DESC',
	)
);
if ( $trending->have_posts() ) :
	?>
	<div class="widget">
		<div class="section__head" style="margin-bottom:var(--space-4);">
			<h3 class="widget__title m-eyebrow"><?php esc_html_e( 'Trending Now', 'mathilde' ); ?></h3>
		</div>
		<div class="flex flex-col gap-4">
			<?php
			$n = 0;
			while ( $trending->have_posts() ) :
				$trending->the_post();
				$n++;
				get_template_part( 'template-parts/components/post-card', null, array( 'style' => 'numbered', 'number' => $n ) );
			endwhile;
			?>
		</div>
	</div>
	<?php
endif;
wp_reset_postdata();
?>

<?php // Categories with counts. ?>
<div class="widget">
	<h3 class="widget__title m-eyebrow"><?php esc_html_e( 'Categories', 'mathilde' ); ?></h3>
	<ul class="cat-list">
		<?php
		$cats = get_terms( array( 'taxonomy' => 'category', 'orderby' => 'count', 'order' => 'DESC', 'number' => 7, 'hide_empty' => true ) );
		if ( ! is_wp_error( $cats ) ) {
			foreach ( $cats as $cat ) {
				printf(
					'<li><a href="%s">%s</a><span class="count">(%d)</span></li>',
					esc_url( get_term_link( $cat ) ),
					esc_html( $cat->name ),
					(int) $cat->count
				);
			}
		}
		?>
	</ul>
</div>

<?php // Newsletter card. ?>
<?php get_template_part( 'template-parts/components/newsletter', null, array( 'style' => 'card' ) ); ?>

<?php
// Instagram mini-grid.
$raw    = mathilde_option( 'insta_images', '' );
$images = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );
if ( empty( $images ) ) {
	$recent = get_posts( array( 'numberposts' => 6, 'fields' => 'ids' ) );
	foreach ( $recent as $pid ) {
		$url = get_the_post_thumbnail_url( $pid, 'mathilde-thumb' );
		if ( $url ) {
			$images[] = $url;
		}
	}
}
if ( ! empty( $images ) ) :
	?>
	<div class="widget">
		<div class="insta__head" style="margin-bottom:var(--space-3);">
			<h3 class="widget__title m-eyebrow" style="margin:0;"><?php esc_html_e( 'Instagram', 'mathilde' ); ?></h3>
			<a class="insta__handle" href="<?php echo esc_url( mathilde_option( 'insta_url', 'https://instagram.com/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Follow', 'mathilde' ); ?></a>
		</div>
		<div class="insta__grid" style="grid-template-columns:repeat(3,1fr);">
			<?php foreach ( array_slice( $images, 0, 6 ) as $img ) : ?>
				<a class="insta__item" href="<?php echo esc_url( mathilde_option( 'insta_url', 'https://instagram.com/' ) ); ?>" target="_blank" rel="noopener">
					<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
endif;
?>
