<?php
/**
 * Structured data (SEO / AEO) — JSON-LD output.
 *
 * Emits Organization + WebSite on every page, Article + Author + Breadcrumb
 * on single posts, FAQPage on the homepage / posts with FAQ, and Review when
 * a rating is set. Skips output if a dedicated SEO plugin is already handling
 * schema (Yoast / Rank Math), to avoid duplicates.
 *
 * @package Mathilde
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether another plugin is already outputting schema.
 *
 * @return bool
 */
function mathilde_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
}

/**
 * Print all applicable JSON-LD graphs.
 */
function mathilde_output_schema() {
	if ( mathilde_seo_plugin_active() ) {
		return;
	}

	$graph = array();

	// Organization.
	$logo  = '';
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	$org = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);
	if ( $logo ) {
		$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo );
	}
	$socials = array_filter( array(
		mathilde_option( 'social_facebook' ),
		mathilde_option( 'social_instagram' ),
		mathilde_option( 'social_pinterest' ),
		mathilde_option( 'social_tiktok' ),
	) );
	if ( $socials ) {
		$org['sameAs'] = array_values( $socials );
	}
	$graph[] = $org;

	// WebSite + search action.
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'publisher'       => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	// Single post: Article + Author + Breadcrumb (+ Review/FAQ).
	if ( is_singular( 'post' ) ) {
		$post_id = get_the_ID();
		$author  = get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
		$img     = get_the_post_thumbnail_url( $post_id, 'full' );

		$article = array(
			'@type'            => 'Article',
			'@id'              => get_permalink() . '#article',
			'headline'         => get_the_title(),
			'description'      => wp_strip_all_tags( get_the_excerpt() ),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author,
				'url'   => get_author_posts_url( (int) get_post_field( 'post_author', $post_id ) ),
			),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
			'mainEntityOfPage' => get_permalink(),
			'wordCount'        => str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) ),
		);
		if ( $img ) {
			$article['image'] = $img;
		}
		$primary = mathilde_primary_category();
		if ( $primary ) {
			$article['articleSection'] = $primary->name;
		}
		$graph[] = $article;

		// Breadcrumb.
		$crumbs = array(
			array( 'name' => __( 'Home', 'mathilde' ), 'url' => home_url( '/' ) ),
		);
		if ( $primary ) {
			$crumbs[] = array( 'name' => $primary->name, 'url' => get_term_link( $primary ) );
		}
		$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		$items    = array();
		foreach ( $crumbs as $i => $c ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $c['name'],
				'item'     => $c['url'],
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);

		// Review schema.
		$score = get_post_meta( $post_id, '_mathilde_review_score', true );
		if ( $score !== '' && (float) $score > 0 ) {
			$review = array(
				'@type'         => 'Review',
				'itemReviewed'  => array( '@type' => 'Thing', 'name' => get_the_title() ),
				'reviewRating'  => array(
					'@type'       => 'Rating',
					'ratingValue' => (float) $score,
					'bestRating'  => 5,
				),
				'author'        => array( '@type' => 'Person', 'name' => $author ),
			);

			$verdict = get_post_meta( $post_id, '_mathilde_review_verdict', true );
			if ( $verdict ) {
				$review['reviewBody'] = $verdict;
			}

			$to_notes = static function ( $meta_key ) use ( $post_id ) {
				$raw = get_post_meta( $post_id, $meta_key, true );
				if ( ! $raw ) {
					return array();
				}
				$items = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
				return array_map(
					static function ( $item ) {
						return array( '@type' => 'ListItem', 'name' => $item );
					},
					array_values( $items )
				);
			};

			$pros = $to_notes( '_mathilde_review_pros' );
			$cons = $to_notes( '_mathilde_review_cons' );
			if ( $pros ) {
				$review['positiveNotes'] = array( '@type' => 'ItemList', 'itemListElement' => $pros );
			}
			if ( $cons ) {
				$review['negativeNotes'] = array( '@type' => 'ItemList', 'itemListElement' => $cons );
			}

			$graph[] = $review;
		}
	}

	// FAQ schema (homepage or posts using the FAQ component).
	$faq_items = null;
	if ( is_front_page() && mathilde_option( 'faq_enable', true ) ) {
		$json      = mathilde_option( 'faq_items', '' );
		$faq_items = $json ? json_decode( $json, true ) : mathilde_default_faq();
	}
	if ( ! empty( $faq_items ) && is_array( $faq_items ) ) {
		$entities = array();
		foreach ( $faq_items as $f ) {
			if ( empty( $f['q'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $f['q'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $f['a'] ?? '' ),
				),
			);
		}
		if ( $entities ) {
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $entities );
		}
	}

	if ( empty( $graph ) ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'mathilde_output_schema', 20 );

/**
 * Default FAQ used for schema when none configured (kept in sync with the
 * FAQ component defaults).
 *
 * @return array
 */
function mathilde_default_faq() {
	return array(
		array( 'q' => __( 'Who is behind this blog?', 'mathilde' ), 'a' => __( 'A fashion, beauty & lifestyle enthusiast sharing honest reviews and everyday inspiration.', 'mathilde' ) ),
		array( 'q' => __( 'What topics do you cover?', 'mathilde' ), 'a' => __( 'Fashion, beauty, jewelry, wellness, travel and intentional living.', 'mathilde' ) ),
		array( 'q' => __( 'How often is new content published?', 'mathilde' ), 'a' => __( 'New articles are published several times a week.', 'mathilde' ) ),
	);
}

/**
 * Output a standard <meta name="description"> (skipped when an SEO plugin is
 * active, to avoid duplicates).
 */
function mathilde_meta_description() {
	if ( mathilde_seo_plugin_active() ) {
		return;
	}

	$desc = '';
	if ( is_singular() ) {
		$desc = get_the_excerpt();
		if ( ! $desc ) {
			$desc = wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) );
		}
	} elseif ( is_front_page() ) {
		$desc = get_bloginfo( 'description' );
		if ( ! $desc ) {
			$desc = mathilde_option( 'about_text' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	} elseif ( is_archive() ) {
		$desc = get_the_archive_description();
	}

	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( ! $desc ) {
		$desc = get_bloginfo( 'name' );
		if ( get_bloginfo( 'description' ) ) {
			$desc .= ' — ' . get_bloginfo( 'description' );
		}
	}

	printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( wp_html_excerpt( $desc, 160, '…' ) ) );
}
add_action( 'wp_head', 'mathilde_meta_description', 1 );

/**
 * Open Graph / Twitter card tags (skipped when an SEO plugin is active).
 */
function mathilde_output_open_graph() {
	if ( mathilde_seo_plugin_active() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = get_bloginfo( 'description' );
	$url   = home_url( add_query_arg( null, null ) );
	$img   = '';

	if ( is_singular() ) {
		$desc = wp_strip_all_tags( get_the_excerpt() );
		$url  = get_permalink();
		if ( has_post_thumbnail() ) {
			$img = get_the_post_thumbnail_url( get_the_ID(), 'full' );
		}
	}

	echo "\n";
	printf( '<meta property="og:type" content="%s">' . "\n", is_singular( 'post' ) ? 'article' : 'website' );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	if ( $img ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
	}
	printf( '<meta name="twitter:card" content="%s">' . "\n", $img ? 'summary_large_image' : 'summary' );
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	if ( $img ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $img ) );
	}
}
add_action( 'wp_head', 'mathilde_output_open_graph', 5 );
