<?php
/**
 * Template tags — output helpers used across templates.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output the brand lockup — custom logo if set, else the serif wordmark.
 */
function mathilde_brand() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	$name     = get_bloginfo( 'name' );
	$subtitle = mathilde_option( 'brand_subtitle', get_bloginfo( 'description' ) );
	$tag      = ( is_front_page() && is_home() ) ? 'h1' : 'span';
	printf(
		'<a href="%1$s" rel="home"><%2$s class="brand__title">%3$s</%2$s>%4$s</a>',
		esc_url( home_url( '/' ) ),
		esc_attr( $tag ),
		esc_html( $name ),
		$subtitle ? '<span class="brand__subtitle">' . esc_html( $subtitle ) . '</span>' : ''
	);
}

/**
 * Output an "eyebrow" category label (uppercase, tracked, rose).
 *
 * @param int|null $post_id Post ID.
 * @param bool     $link    Whether to wrap in a link.
 */
function mathilde_category_eyebrow( $post_id = null, $link = true ) {
	$term = mathilde_primary_category( $post_id );
	if ( ! $term ) {
		return;
	}
	if ( $link ) {
		printf(
			'<a class="m-eyebrow m-eyebrow--cat" href="%s">%s</a>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	} else {
		printf( '<span class="m-eyebrow m-eyebrow--cat">%s</span>', esc_html( $term->name ) );
	}
}

/**
 * Output the post meta line: author · date · reading time.
 *
 * @param array $args Toggle pieces: author, date, reading_time, avatar.
 */
function mathilde_post_meta( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'author'       => true,
			'avatar'       => false,
			'date'         => true,
			'reading_time' => true,
		)
	);

	echo '<div class="m-post-meta">';

	if ( $args['author'] ) {
		echo '<span class="m-post-meta__author">';
		if ( $args['avatar'] ) {
			echo '<span class="m-post-meta__avatar">' . get_avatar( get_the_author_meta( 'ID' ), 40 ) . '</span>';
		}
		printf(
			/* translators: %s: author name */
			esc_html__( 'By %s', 'mathilde' ),
			'<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>'
		);
		echo '</span>';
	}

	if ( $args['date'] ) {
		printf(
			'<time class="m-post-meta__date" datetime="%s">%s</time>',
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() )
		);
	}

	if ( $args['reading_time'] ) {
		printf(
			'<span class="m-post-meta__time">%s %s</span>',
			esc_html( mathilde_reading_time() ),
			esc_html__( 'min read', 'mathilde' )
		);
	}

	echo '</div>';
}

/**
 * Render breadcrumbs (uses Yoast/RankMath if available, else builds its own).
 */
function mathilde_breadcrumbs() {
	if ( function_exists( 'yoast_breadcrumb' ) && ! is_front_page() ) {
		yoast_breadcrumb( '<nav class="m-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mathilde' ) . '">', '</nav>' );
		return;
	}

	if ( is_front_page() ) {
		return;
	}

	echo '<nav class="m-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mathilde' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'mathilde' ) . '</a>';

	$sep = '<span class="m-breadcrumbs__sep" aria-hidden="true">' . mathilde_icon( 'chevron-down', 14 ) . '</span>';

	if ( is_singular( 'post' ) ) {
		$term = mathilde_primary_category();
		if ( $term ) {
			echo $sep . '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
		}
		echo $sep . '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_category() || is_tax() || is_tag() ) {
		echo $sep . '<span aria-current="page">' . esc_html( single_term_title( '', false ) ) . '</span>';
	} elseif ( is_page() ) {
		echo $sep . '<span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	} elseif ( is_search() ) {
		echo $sep . '<span aria-current="page">' . esc_html__( 'Search Results', 'mathilde' ) . '</span>';
	} elseif ( is_archive() ) {
		echo $sep . '<span aria-current="page">' . esc_html( get_the_archive_title() ) . '</span>';
	}

	echo '</nav>';
}

/**
 * Numbered pagination styled to the mockups.
 */
function mathilde_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'array',
			'mid_size'  => 1,
			'end_size'  => 1,
			'prev_text' => mathilde_icon( 'arrow-left', 18 ),
			'next_text' => mathilde_icon( 'arrow-right', 18 ),
		)
	);
	if ( empty( $links ) ) {
		return;
	}
	echo '<nav class="m-pagination" aria-label="' . esc_attr__( 'Posts', 'mathilde' ) . '"><ul>';
	foreach ( $links as $link ) {
		echo '<li>' . $link . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul></nav>';
}

/**
 * Output the share row used on single posts.
 *
 * @param bool $vertical Whether to render the vertical sticky variant.
 */
function mathilde_share_buttons( $vertical = false ) {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );
	$img   = rawurlencode( (string) get_the_post_thumbnail_url( get_the_ID(), 'full' ) );

	$networks = array(
		'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $img . '&description=' . $title,
		'twitter'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
		'mail'      => 'mailto:?subject=' . $title . '&body=' . $url,
	);

	$class = $vertical ? 'm-share m-share--vertical' : 'm-share';
	echo '<div class="' . esc_attr( $class ) . '">';
	echo '<span class="m-share__label">' . esc_html__( 'Share', 'mathilde' ) . '</span>';
	foreach ( $networks as $name => $href ) {
		$target = ( 'mail' === $name ) ? '' : ' target="_blank" rel="noopener nofollow"';
		printf(
			'<a class="m-share__btn m-share__btn--%1$s" href="%2$s"%3$s aria-label="%4$s">%5$s</a>',
			esc_attr( $name ),
			esc_url( $href ),
			$target, // phpcs:ignore
			/* translators: %s: network name */
			esc_attr( sprintf( __( 'Share on %s', 'mathilde' ), ucfirst( $name ) ) ),
			mathilde_icon( $name, 18 )
		);
	}
	printf(
		'<button class="m-share__btn m-share__btn--copy" type="button" data-copy="%s" aria-label="%s">%s</button>',
		esc_url( get_permalink() ),
		esc_attr__( 'Copy link', 'mathilde' ),
		mathilde_icon( 'link', 18 )
	);
	echo '</div>';
}

/**
 * Build a table of contents from the post's H2/H3 headings.
 *
 * Returns the modified content (with anchor ids injected) and prints the TOC
 * list to the supplied buffer via the $toc reference.
 *
 * @param string $content Post content.
 * @param array  $toc     Filled with [id,text,level] entries.
 * @return string Content with ids added to headings.
 */
function mathilde_build_toc( $content, &$toc ) {
	$toc = array();
	if ( ! $content || ! preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
		return $content;
	}
	$used = array();
	foreach ( $matches as $m ) {
		$level = (int) $m[1];
		$text  = trim( wp_strip_all_tags( $m[3] ) );
		if ( '' === $text ) {
			continue;
		}
		$slug = sanitize_title( $text );
		$base = $slug;
		$i    = 2;
		while ( in_array( $slug, $used, true ) ) {
			$slug = $base . '-' . $i;
			$i++;
		}
		$used[] = $slug;
		$toc[]  = array( 'id' => $slug, 'text' => $text, 'level' => $level );

		// Inject id only if the heading doesn't already have one.
		if ( false === strpos( $m[2], 'id=' ) ) {
			$replacement = '<h' . $level . $m[2] . ' id="' . $slug . '">' . $m[3] . '</h' . $level . '>';
			$content     = str_replace( $m[0], $replacement, $content );
		}
	}
	return $content;
}

/**
 * Render social links from the assigned "social" menu, falling back to
 * Customizer URL fields.
 */
function mathilde_social_links( $class = 'm-social' ) {
	$out = '';

	$networks = array(
		'facebook'  => mathilde_option( 'social_facebook' ),
		'instagram' => mathilde_option( 'social_instagram' ),
		'pinterest' => mathilde_option( 'social_pinterest' ),
		'tiktok'    => mathilde_option( 'social_tiktok' ),
		'mail'      => mathilde_option( 'social_email' ) ? 'mailto:' . mathilde_option( 'social_email' ) : '',
	);

	foreach ( $networks as $icon => $url ) {
		if ( ! $url ) {
			continue;
		}
		$target = ( 'mail' === $icon ) ? '' : ' target="_blank" rel="noopener"';
		$out   .= sprintf(
			'<a href="%1$s"%2$s aria-label="%3$s">%4$s</a>',
			esc_url( $url ),
			$target, // phpcs:ignore
			esc_attr( ucfirst( $icon ) ),
			mathilde_icon( $icon, 18 )
		);
	}

	if ( $out ) {
		echo '<div class="' . esc_attr( $class ) . '">' . $out . '</div>'; // phpcs:ignore
	}
}

/**
 * Post thumbnail with a graceful placeholder when none is set.
 *
 * @param string $size Image size.
 * @param array  $attr Image attributes.
 */
function mathilde_post_thumbnail( $size = 'mathilde-card', $attr = array() ) {
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( $size, array_merge( array( 'loading' => 'lazy', 'alt' => the_title_attribute( array( 'echo' => false ) ) ), $attr ) );
	} else {
		printf(
			'<span class="m-thumb-placeholder" aria-hidden="true">%s</span>',
			mathilde_icon( 'sparkle', 32 )
		);
	}
}
